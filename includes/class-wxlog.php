<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WL {

	var $wxlog_log_id;
	
	public function __construct() {
		global $wpdb;

		if(get_option( 'wxlog_txt_log' )){
			$upload_dir = wp_upload_dir();
			$dir = $upload_dir['basedir'].'/wxlog_logs/';
			if (!is_dir($dir)) @mkdir($dir, 0777);
			wxlog($dir.date("Y-m-d").'.txt','GET:'.var_export($_GET, TRUE));
			wxlog($dir.date("Y-m-d").'.txt','HTTP_RAW_POST_DATA:'.var_export(@$GLOBALS["HTTP_RAW_POST_DATA"], TRUE));		
		}
			
		if(isset($_GET['echostr']))
			$this->valid();//第一次验证
		
		$postStr = (isset($GLOBALS["HTTP_RAW_POST_DATA"]))?$GLOBALS["HTTP_RAW_POST_DATA"]:'';
		if($_POST["test"]){
			$postStr = @$_POST["HTTP_RAW_POST_DATA"];
		}
		if (!empty($postStr) and $this->checkSignature()){
			$postArray = wxlog_xml_to_array($postStr);
			$this->wxlog_log_id = $this->insert_wxlog_log($postArray,$postStr,$reply);
			$this->responseMsg($postArray,$postStr);
		}
	}

    public function insert_wxlog_log($data,$message='',$reply=''){
		global $wpdb;
		
		$wpdb->insert(
			$wpdb->wxlog_log,
			array(
				'fromusername'      => $data['FromUserName'],
				'msgtype' 			=> $data['MsgType'],
				'content'           => $data['Content'],
				
				'signature'         => $_GET['signature'],
				'timestamp'         => date("Y-m-d H:i:s",$_GET['timestamp']),
				'nonce'             => $_GET['nonce'],
				
				'user_id'           => 1,
				'user_ip'           => $this->get_user_ip(),
				'user_agent'        => $this->get_user_ua(),

				'message'           => $message,
				'reply'             => $reply,
				'status'            => 0,
			),
			array(
				'%s',
				'%s',
				'%s',
				
				'%s',
				'%s',
				'%s',
				
				'%d',
				'%s',
				'%s',
				
				'%s',
				'%s',
				'%d'
			)
		);
		return $wpdb->insert_id;
    } 


	private function get_user_ip() {
		return sanitize_text_field( ! empty( $_SERVER['HTTP_X_FORWARD_FOR'] ) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'] );
	}
	private function get_user_ua() {
		$ua = sanitize_text_field( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
		if ( strlen( $ua ) > 200 )  
			$ua = substr( $ua, 0, 199 );
		return $ua;
	}

    public function responseMsg($postArray,$postStr){
		global $wpdb;
		
		if(empty($postArray['Content'])){
			return;
		}
		$postArray['Content'] = strtolower($postArray['Content']);

		//黑名单过滤		
		$wxlog_blacklist_user = get_option( 'wxlog_blacklist_user' );
		if($wxlog_blacklist_user){
			$contentStr = get_option( 'wxlog_blacklist_message' );
			if($contentStr=='custom')
				$contentStr = get_option( 'wxlog_blacklist_message_custom' );
			if(in_array($postArray['FromUserName'],explode(',',$wxlog_blacklist_user))){
				if(empty($contentStr)){
					$contentStr = "您已经被列入黑名单。";
				}
				$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $contentStr);
				if($resultStr){
					$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
					exit($resultStr);
				}
			}
		}
		
		//调用自定义回复数据
		$custom_reply_array = $wpdb->get_results( "SELECT ID,keyword FROM {$wpdb->wxlog_custom_reply} where status = 2" );
		foreach($custom_reply_array as $key=>$value){
			if(in_array($postArray['Content'],explode(',',$value->keyword))){
				$custom_reply = $wpdb->get_row( "SELECT * FROM {$wpdb->wxlog_custom_reply} where ID = {$value->ID}" );
				break;
			}
		}//d($custom_reply);		
		if($custom_reply){
			if($custom_reply->msgtype=='text'){
				$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $custom_reply->content);
			}elseif($custom_reply->msgtype=='news'){
				$contentArr = array();
				$contentArr[0]['title'] =  $custom_reply->title;
				$contentArr[0]['description'] =  $custom_reply->content;
				$contentArr[0]['image_url'] =  $custom_reply->image_url;
				$contentArr[0]['url'] =  $custom_reply->url;
				$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $contentArr);
			}
			if($resultStr){
				$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
				exit($resultStr);
			}
		}


		//调用官方插件
		$wllog_my_plugins = get_option( 'wllog_my_plugins' );
		if($wllog_my_plugins){
			$content = file_get_contents('http://www.phplog.com/?wxlog_plugins&host='.$_SERVER['HTTP_HOST'].'&id='.$wllog_my_plugins.'&key='.$postArray['Content']);
			if($content){
				if(is_array($content)){
					$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $content);
				}else{
					$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $content);
				}
				if($resultStr){
					$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
					exit($resultStr);
				}
			}
		}
		
		//调用插件
		$plugins = get_wxlog_plugins();
		//d($plugins);
		foreach($plugins as $key=>$value){
			if(is_wxlog_plugin($value) and is_active_wxlog_plugin($key) and $value['key']==''){
				if(file_exists(WP_PLUGIN_DIR .'/'. $key)){
					include_once( WP_PLUGIN_DIR .'/'. $key );
				}
				$classname = $value['ClassName'];
				if(class_exists($classname)){
					$pluginObj = new $classname;
					$content = $pluginObj->get_content( $postArray['Content'] );
					if($content){
						if(is_array($content)){
							$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $content);
						}else{
							$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $content);
						}
						if($resultStr){
							$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
							exit($resultStr);
						}
					}
				}
			}
		}//d($plugins);


		//调用Wordpress数据
		if(get_option( 'wxlog_post_max' )){
			$contentArr = $this->get_posts($postArray['Content']);
			if($contentArr){
				if(is_array($contentArr)){
					$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $contentArr);
					if($resultStr){
						$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
						exit($resultStr);
					}
				}
			}
		}
		//是否开启小黄鸡，可以到高级里设置。
		if(get_option( 'wxlog_simsimi' )){
			$contentStr = $this->simsimi( $postArray['Content'] );
			if($contentStr){
				$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $contentStr);
				if($resultStr){
					$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
					exit($resultStr);
				}
			}
		}
		
		$contentStr = base64_decode("5qyi6L+O5YWz5rOo5oiR55qE5b6u5L+h5pel5b+X44CCIGh0dHA6Ly93d3cucGhwbG9nLmNvbS93eGxvZw==");
		$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $contentStr);
		if($resultStr){
			$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
			exit($resultStr);
		}
    }

	

	//查询数据库
	public function get_posts($keyword){
		$post_max = ( $wxlog_post_max = get_option( 'wxlog_post_max' ) ) ? $wxlog_post_max : '5';
		$query_array = array(
			's' 					=> $keyword, 
			'posts_per_page'		=> $post_max, 
			'post_status' 			=> 'publish', 
			'ignore_sticky_posts'	=> 1
		);
		$query = new WP_Query($query_array);
		$contentArr = '';
		$i = 0;
		if($query->have_posts()){
			while ($query->have_posts()) {
				$query->the_post();
				global $post;
				if($contentArr){
					$contentArr[$i]['image_url'] = get_post_wxlog_thumb($post, array(80,80));
				}else{
					$contentArr[$i]['image_url'] = get_post_wxlog_thumb($post, array(640,320));
				}
				$contentArr[$i]['title'] = get_the_title();
				$contentArr[$i]['description'] = get_post_wxlog_excerpts($post,150);
				$contentArr[$i]['url'] = get_permalink();
				$i++;
			}
		}
		return $contentArr;
	}


	//小黄鸡智能机器人接口
	public function simsimi( $keyword ){
		if ( $keyword<>'' ){
			$curlPost = 'para='.$keyword;
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, 'http://www.xiaohuangji.com/ajax.php');
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_NOBODY, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
			$return_str = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($return_str === false) {
				return false;//连接失败
			}
			if ($http_code !== 200) {
				return false;//连接失败
			}		
			curl_close($curl);
			if(md5($return_str)=='3f46a0d792a6a4104c11283d4768192d'){
				//抱歉，小黄鸡还不能理解，求您教我,使用"问...答....."句式(不准带标点~)
				return false;
			}
			return $return_str;
		}return false;
	}

	//公众平台绑定验证
    public function valid(){ 
        $echoStr = $_GET["echostr"];  
        if($this->checkSignature()){ 
            echo $echoStr; 
            exit; 
        } 
    } 

    public function checkSignature() { 
        $signature = $_GET["signature"]; 
        $timestamp = $_GET["timestamp"]; 
        $nonce = $_GET["nonce"]; 
        $token = TOKEN; 
        $tmpArr = array($token, $timestamp, $nonce);//print_r($tmpArr);
        sort($tmpArr);//print_r($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
  
        if( $tmpStr == $signature ){ 
            return true; 
        }else{ 
            return false; 
        } 
    } 


	//你可以设FuncFlag字段为1来对消息进行星标，你可以在实时消息的星标消息分类中找到该消息
	//回复文本消息
    public function reply_text($toUsername, $fromUsername, $contentStr){
		$textTpl = "<xml>
<ToUserName><![CDATA[".$toUsername."]]></ToUserName>
<FromUserName><![CDATA[".$fromUsername."]]></FromUserName>
<CreateTime>".time()."</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
<FuncFlag>0</FuncFlag>
</xml>";
		return sprintf($textTpl, $contentStr);
	}

	//回复图片消息
    public function reply_image($toUsername, $fromUsername, $contentStr){
		$textTpl = "官方未开放";
		return sprintf($textTpl, $contentStr);
	}

	//回复语音消息
    public function reply_voice($toUsername, $fromUsername, $contentStr){
		$textTpl = "官方未开放";
		return sprintf($textTpl, $contentStr);
	}
	
	
	//回复视频消息
    public function reply_video($toUsername, $fromUsername, $contentStr){
		$textTpl = "官方未开放";
		return sprintf($textTpl, $contentStr);
	}	


	//回复音乐消息
    public function reply_music($toUsername, $fromUsername, $contentStr){
		$textTpl = "官方未开放";
		return sprintf($textTpl, $contentStr);
	}	

	//回复图文消息
    public function reply_news($toUsername, $fromUsername, $contentArr){
		$contentStr = '';
		foreach($contentArr as $value){
			if(!$value['description']) $value['description'] = $value['title'];
			$contentStr .= '<item>
<Title><![CDATA['.html_entity_decode($value['title'], ENT_QUOTES, "utf-8" ).']]></Title>
<Description><![CDATA['.html_entity_decode($value['description'], ENT_QUOTES, "utf-8" ).']]></Description>
<PicUrl><![CDATA['.$value['image_url'].']]></PicUrl>
<Url><![CDATA['.$value['url'].']]></Url>
</item>';
		}
		$newsTpl = "<xml>
<ToUserName><![CDATA[".$toUsername."]]></ToUserName>
<FromUserName><![CDATA[".$fromUsername."]]></FromUserName>
<CreateTime>".time()."</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%d</ArticleCount>
<Articles>
%s
</Articles>
<FuncFlag>1</FuncFlag>
</xml>";
		return sprintf($newsTpl, count($contentArr), $contentStr);
	}

}


$WL = new WL();