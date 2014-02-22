<?php

if(!function_exists('wxlog_xml_to_array')){
	function wxlog_xml_to_array($xml){

		$postObj = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$arr['ToUserName'] = trim(@$postObj->ToUserName);
		$arr['FromUserName'] = trim(@$postObj->FromUserName);
		$arr['CreateTime'] = trim(@$postObj->CreateTime);
		$arr['MsgType'] = trim(@$postObj->MsgType);
		if($arr['MsgType']=='text'){
			$arr['Content'] = trim(@$postObj->Content);
		}
		if($arr['MsgType']=='image'){
			$arr['PicUrl'] = trim(@$postObj->PicUrl);
			$arr['MediaId'] = trim(@$postObj->MediaId);
			$arr['Content'] = $arr['PicUrl'];
		}
		if($arr['MsgType']=='voice'){
			$arr['MediaId'] = trim(@$postObj->MediaId);
			$arr['Format'] = trim(@$postObj->Format);
			//服务号必须开通语音识别功能
			$arr['Recognition'] = trim(@$postObj->Recognition);
			$arr['Content'] = $arr['Recognition'];
		}
		if($arr['MsgType']=='video'){
			$arr['MediaId'] = trim(@$postObj->MediaId);
			$arr['ThumbMediaId'] = trim(@$postObj->ThumbMediaId);
		}
		if($arr['MsgType']=='location'){
			$arr['Location_X'] = trim(@$postObj->Location_X);
			$arr['Location_Y'] = trim(@$postObj->Location_Y);
			$arr['Scale'] = trim(@$postObj->Scale);
			$arr['Label'] = trim(@$postObj->Label);
			$arr['Content'] = 'location_'.$arr['Location_X'].'_'.$arr['Location_Y'].'_'.$arr['Scale'].'_'.$arr['Label'];
		}
		if($arr['MsgType']=='link'){
			$arr['Title'] = trim(@$postObj->Title);
			$arr['Description'] = trim(@$postObj->Description);
			$arr['Url'] = trim(@$postObj->Url);
		}
		if($arr['MsgType']=='event'){
			$arr['Event'] = trim(@$postObj->Event);
			$arr['EventKey'] = trim(@$postObj->EventKey);
			$arr['Ticket'] = trim(@$postObj->Ticket);
			if($arr['event']!='LOCATION'){
				$arr['Latitude'] = trim(@$postObj->Latitude);
				$arr['Longitude'] = trim(@$postObj->Longitude);
				$arr['Precision'] = trim(@$postObj->Precision);
			}

			$event = strtolower($arr['Event']);
			if($event == 'subscribe' || $event == 'unsubscribe'){ //订阅和取消订阅
				$arr['Content'] = $event;
			}elseif($event == 'click'){	//点击事件
				$arr['Content'] = strtolower($arr['EventKey']);
			}elseif($event == 'view'){	//查看网页事件

			}

		}
		if($arr['MsgType']!='event'){
			$arr['MsgId'] = trim(@$postObj->MsgId);
		}
		return $arr;
	}
}

if(!function_exists('wxlog')){
	function wxlog($toppath,$logs){
	  $Ts=fopen($toppath,"a+");
	  fputs($Ts,$logs."\r\n");
	  fclose($Ts);
	}
}


//判断当前用户是否为微信用户
if(!function_exists('is_wxlog_weixin')){
	function is_wxlog_weixin(){
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
				return true;
			}
		}
		return false;
	}
}

if(!function_exists('get_post_wxlog_excerpts')){
    //获取日志摘要
    function get_post_wxlog_excerpts($post, $excerpt_length=240){
        if(!$post) $post = get_post();
        $post_excerpt = $post->post_excerpt;
        if($post_excerpt == ''){
            $post_content = $post->post_content;
            $post_content = do_shortcode($post_content);
            $post_content = wp_strip_all_tags( $post_content );
            $excerpt_length = apply_filters('excerpt_length', $excerpt_length);
            $excerpt_more   = apply_filters('excerpt_more', ' ' . '&hellip;');
            $post_excerpt = mb_strimwidth($post_content,0,$excerpt_length,$excerpt_more,'utf-8');
        }
        $post_excerpt = wp_strip_all_tags( $post_excerpt );
        $post_excerpt = trim( preg_replace( "/[\n\r\t ]+/", ' ', $post_excerpt ), ' ' );
        return $post_excerpt;
    }
}


if(!function_exists('get_post_wxlog_first_image')){
	function get_post_wxlog_first_image($post_content){
		preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post_content, $matches);
		if($matches){
			return $matches[1][0];
		}else{
			return false;
		}
	}
}

if(!function_exists('get_post_wxlog_thumb')){
	function get_post_wxlog_thumb($post,$size){
		$thumbnail_id = get_post_thumbnail_id($post->ID);
		if($thumbnail_id){
			$thumb = wp_get_attachment_image_src($thumbnail_id, $size);
			$thumb = $thumb[0];
		}else{
			$thumb = get_post_wxlog_first_image($post->post_content);
		}
		if(empty($thumb)){
			$thumb = get_option('wxlog_default_image');
		}
		$thumb = apply_filters('weixin_thumb',$thumb,$size,$post);
		return $thumb;
	}
}







if(!function_exists('get_wxlog_plugin_data')){

	//获取插件数据
	function get_wxlog_plugin_data( $plugin_file, $markup = true, $translate = true ) {

		$default_headers = array(
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'TextDomain' => 'Text Domain',
			'DomainPath' => 'Domain Path',
			'Network' => 'Network',
			'ClassName' => 'ClassName',
			// Site Wide Only is deprecated in favor of Network.
			'_sitewide' => 'Site Wide Only',
		);

		$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

		// Site Wide Only is the old header for Network
		if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
			_deprecated_argument( __FUNCTION__, '3.0', sprintf( __( 'The <code>%1$s</code> plugin header is deprecated. Use <code>%2$s</code> instead.' ), 'Site Wide Only: true', 'Network: true' ) );
			$plugin_data['Network'] = $plugin_data['_sitewide'];
		}
		$plugin_data['Network'] = ( 'true' == strtolower( $plugin_data['Network'] ) );
		unset( $plugin_data['_sitewide'] );

		if ( $markup || $translate ) {
			$plugin_data = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, $markup, $translate );
		} else {
			$plugin_data['Title']      = $plugin_data['Name'];
			$plugin_data['AuthorName'] = $plugin_data['Author'];
		}

		return $plugin_data;
	}
}

if(!function_exists('get_wxlog_plugins')){
	//获取插件列表
	function get_wxlog_plugins($plugin_folder = '') {

		if ( ! $cache_plugins = wp_cache_get('plugins', 'plugins') )
			$cache_plugins = array();

		if ( isset($cache_plugins[ $plugin_folder ]) )
			return $cache_plugins[ $plugin_folder ];

		$wp_plugins = array ();
		$plugin_root = WP_PLUGIN_DIR;
		if ( !empty($plugin_folder) )
			$plugin_root .= $plugin_folder;

		// Files in wp-content/plugins directory
		$plugins_dir = @ opendir( $plugin_root);
		$plugin_files = array();
		if ( $plugins_dir ) {
			while (($file = readdir( $plugins_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( is_dir( $plugin_root.'/'.$file ) ) {
					$plugins_subdir = @ opendir( $plugin_root.'/'.$file );
					if ( $plugins_subdir ) {
						while (($subfile = readdir( $plugins_subdir ) ) !== false ) {
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							if ( substr($subfile, -4) == '.php' )
								$plugin_files[] = "$file/$subfile";
						}
						closedir( $plugins_subdir );
					}
				} else {
					if ( substr($file, -4) == '.php' )
						$plugin_files[] = $file;
				}
			}
			closedir( $plugins_dir );
		}

		if ( empty($plugin_files) )
			return $wp_plugins;

		foreach ( $plugin_files as $plugin_file ) {
			if ( !is_readable( "$plugin_root/$plugin_file" ) )
				continue;

			$plugin_data = get_wxlog_plugin_data( "$plugin_root/$plugin_file", false, false ); //Do not apply markup/translate as it'll be cached.
			if ( empty ( $plugin_data['Name'] ) )
				continue;

			$wp_plugins[plugin_basename( $plugin_file )] = $plugin_data;
		}

		uasort( $wp_plugins, '_sort_uname_callback' );

		$cache_plugins[ $plugin_folder ] = $wp_plugins;
		wp_cache_set('plugins', $cache_plugins, 'plugins');

		return $wp_plugins;
	}
}

if(!function_exists('is_wxlog_plugin')){
	//判断是否为WWY插件
	function is_wxlog_plugin( $plugin ) {
		if($plugin['ClassName']){
			return true;
		}else{
			return false;
		}
		static $term;
		if ( is_null( $term ) )
			$term = wp_unslash( 'wxlog_' );

		foreach ( $plugin as $value )
			if ( stripos( $value, $term ) !== false )
				return true;

		return false;
	}
}

if(!function_exists('is_active_wxlog_plugin')){
	//判断插件是否激活
	function is_active_wxlog_plugin( $file ) {
		if ( in_array( $file, (array) get_option( 'active_plugins', array() ) ) )
			return true;

		return false;
	}
}

if(!function_exists('wxlog_get_xml')){
	function wxlog_get_xml($url,$xmlData){
		//第一种发送方式，也是推荐的方式：
		$header[] = "Content-type: text/xml";        //定义content-type为xml,注意是数组
		$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
		$response = curl_exec($ch);
		if(curl_errno($ch)){
			print curl_error($ch);
		}
		curl_close($ch);
		return $response;
	}
}

function XML_unserialize($xml){
    $xml_parser = new XML();
    $data = $xml_parser->parse($xml);
    $xml_parser->destruct();
    return $data;
}

class XML{
    var $parser;   #a reference to the XML parser
    var $document; #the entire XML structure built up so far
    var $parent;   #a pointer to the current parent - the parent will be an array
    var $stack;    #a stack of the most recent parent at each nesting level
    var $last_opened_tag; #keeps track of the last tag opened.

    function XML(){
        $this->parser = xml_parser_create();
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'open','close');
        xml_set_character_data_handler($this->parser, 'data');
    }
    function destruct(){ xml_parser_free($this->parser); }
    function parse($data){
        $this->document = array();
        $this->stack    = array();
        $this->parent   = &$this->document;
        return xml_parse($this->parser, $data, true) ? $this->document : NULL;
    }
    function open($parser, $tag, $attributes){
        $this->data = ''; #stores temporary cdata
        $this->last_opened_tag = $tag;
        if(is_array($this->parent) and array_key_exists($tag,$this->parent)){ #if you've seen this tag before
            if(is_array($this->parent[$tag]) and array_key_exists(0,$this->parent[$tag])){ #if the keys are numeric
                #this is the third or later instance of $tag we've come across
                $key = $this->count_numeric_items($this->parent[$tag]);
            }else{
                #this is the second instance of $tag that we've seen. shift around
                if(array_key_exists("$tag attr",$this->parent)){
                    $arr = array('0 attr'=>&$this->parent["$tag attr"], &$this->parent[$tag]);
                    unset($this->parent["$tag attr"]);
                }else{
                    $arr = array($this->parent[$tag]);
                }
                $this->parent[$tag] = &$arr;
                $key = 1;
            }
            $this->parent = &$this->parent[$tag];
        }else{
            $key = $tag;
        }
        if($attributes) $this->parent["$key attr"] = $attributes;
        $this->parent  = &$this->parent[$key];
        $this->stack[] = &$this->parent;
    }
    function data($parser, $data){
        if($this->last_opened_tag != NULL) #you don't need to store whitespace in between tags
            $this->data .= $data;
    }
    function close($parser, $tag){
        if($this->last_opened_tag == $tag){
            $this->parent = $this->data;
            $this->last_opened_tag = NULL;
        }
        array_pop($this->stack);
        if($this->stack) $this->parent = &$this->stack[count($this->stack)-1];
    }
	function count_numeric_items(&$array){
		return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
	}
}