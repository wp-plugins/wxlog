<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WL {

	var $wxlog_log_id;
	
	public function __construct() {
		global $wpdb;

		if(get_option( 'wxlog_txt_log' )){
			$upload_dir = wp_upload_dir();
			$dir = $upload_dir['basedir'].'/wxlog_logs/';
			if (!is_dir($dir)) @mkdir($dir, 0777);
			//echo $dir.date("Y-m-d").'.txt';
			wxlog($dir.date("Y-m-d").'.txt','GET:'.var_export($_GET, TRUE));
			wxlog($dir.date("Y-m-d").'.txt','HTTP_RAW_POST_DATA:'.var_export(@$GLOBALS["HTTP_RAW_POST_DATA"], TRUE));		
		}
			
		if(isset($_GET['echostr']))
			$this->valid();//第一次验证
		
		$postStr = (isset($GLOBALS["HTTP_RAW_POST_DATA"]))?$GLOBALS["HTTP_RAW_POST_DATA"]:'';
		if($_POST["test"]){
			$postStr = @$_POST["HTTP_RAW_POST_DATA"];
		}
		//$postStr = unicode_encode($postStr);
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
			$contentStr = get_option( 'wxlog_blacklist_message_custom' );
			if(in_array($postArray['FromUserName'],explode(',',str_replace('，',',',$wxlog_blacklist_user)))){
				if(!empty($contentStr)){
					$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $contentStr);
					if($resultStr){
						$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
						exit($resultStr);
					}
				}else{
					return '';
				}
			}
		}
		
		//调用自定义回复数据
		$custom_reply_array = $wpdb->get_results( "SELECT ID,keyword FROM {$wpdb->wxlog_custom_reply} where status = 2" );
		foreach($custom_reply_array as $key=>$value){
			if(in_array($postArray['Content'],explode(',',str_replace('，',',',$value->keyword)))){
				$custom_reply = $wpdb->get_row( "SELECT * FROM {$wpdb->wxlog_custom_reply} where ID = {$value->ID}" );
				break;
			}
		}//d($custom_reply);
		if($custom_reply){
			if($custom_reply->msgtype=='text'){
				$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], stripslashes($custom_reply->content));
			}elseif($custom_reply->msgtype=='news'){
				$contentArr = array();
				$wxlog_news_list_title = explode('|phplogcom|',stripslashes($custom_reply->title));
				$wxlog_news_list_url = explode('|phplogcom|',stripslashes($custom_reply->url));
				$wxlog_news_list_image_url = explode('|phplogcom|',stripslashes($custom_reply->image_url));
				$wxlog_news_list_description = explode('|phplogcom|',stripslashes($custom_reply->description));
				foreach($wxlog_news_list_title as $key=>$value){
					$contentArr[$key]['title'] =  $wxlog_news_list_title[$key];
					$contentArr[$key]['description'] =  $wxlog_news_list_description[$key];
					$contentArr[$key]['image_url'] =  $wxlog_news_list_image_url[$key];
					$contentArr[$key]['url'] =  $wxlog_news_list_url[$key];
				}
				$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $contentArr);
			}
			if($resultStr){
				$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
				exit($resultStr);
			}
		}


		//调用官方插件
		$wxlog_my_plugins = get_option( 'wxlog_my_plugins' );
		if($wxlog_my_plugins){
			//print_r($wxlog_my_plugins);
			$content = file_get_contents('http://www.phplog.com/?wxlog_plugins&format=json&host='.$_SERVER['HTTP_HOST'].'&id='.$wxlog_my_plugins.'&key='.$postArray['Content']);
			//print_r($content);
			$content = json_decode($content);
			//print_r($content);
			if($content->content){
				if(is_array($content->content)){
					$resultStr = $this->reply_news($postArray['FromUserName'], $postArray['ToUserName'], $content->content);
				}else{
					$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $content->content);
				}
				if($resultStr){
					$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
					exit($resultStr);
				}
			}
		}
		
		//调用插件
		$plugins = get_wxlog_plugins();
		//print_r($plugins);
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
		
		//默认的回复内容		
		$contentStr = get_option( 'wxlog_default_content' );
		if($contentStr){
			$resultStr = $this->reply_text($postArray['FromUserName'], $postArray['ToUserName'], $contentStr);
		}
		if($resultStr){
			$wpdb->update( $wpdb->wxlog_log, array( 'reply' => $resultStr,'status' => 2 ), array( 'ID' => $this->wxlog_log_id ), array( '%s','%d' ), array( '%d' ) );
			exit($resultStr);
		}
    }

	//查询数据库
	public function get_posts($keyword){
		$post_max = ( $wxlog_post_max = get_option( 'wxlog_post_max' ) ) ? $wxlog_post_max : '5';
		switch ($keyword) {
			case get_option( 'wxlog_post_new' ) :
			 	break;
			case get_option( 'wxlog_post_hot' ) :
				if(get_option( 'wxlog_post_hot_field_name' )){
					$query_array['meta_key'] = 'views';//访问统计的字段名
					$query_array['orderby'] = 'meta_value_num';
					$query_array['order'] = 'DESC';
					break;
				}
			case get_option( 'wxlog_post_hot_comment' ) :
				$query_array['orderby'] = 'comment_count';
				$query_array['order'] = 'DESC';
				break;
			case in_array($keyword,explode(',',str_replace('，',',',get_option( 'wxlog_post_category' )))) :
				$query_array['cat'] = $keyword;
			 	break;
			default :
				$query_array['s'] = $keyword;
				break;
		}//print_r($query_array);
		
		$query_array['showposts'] = $post_max;
		$query_array['posts_per_page'] = $post_max;
		$query_array['post_status'] = 'publish';
		$query_array['ignore_sticky_posts'] = 1;
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
        sort($tmpArr,SORT_STRING);//print_r($tmpArr);
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
		return sprintf($textTpl, str_replace('\r\n',"\r\n",wxlog_emoji(wxlog_qqface($contentStr))));
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