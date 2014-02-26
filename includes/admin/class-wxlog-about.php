<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_About {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
	}

	//管理菜单
	public function admin_menu() {
		add_submenu_page( 'wxlog_log', '使用帮助', '使用帮助', 'administrator', 'wxlog_about', array( $this, 'wxlog_about_page' ) );
	}

	//关于页面内容
	public function wxlog_about_page() {
		?>
		<div class="wrap">
            <h2>使用帮助 <a href="?page=wxlog_setting" class="add-new-h2">配置微信日志</a></h2>
            <div><pre>
 
<b>官方最新消息</b>
<div id="wxlog_news"></div>
<b>相关网址 </b>        
WP插件网址：http://wordpress.org/plugins/wxlog/
WP插件SVN网址：http://plugins.svn.wordpress.org/wxlog/
微信日志网址：http://www.phplog.com/wxlog/            
微信公众平台：http://mp.weixin.qq.com/

<b>回复内容优先级</b>
1 黑名单过滤
2 调用自定义回复
3 调用官方免费插件
4 调用WP插件
5 调用WP数据
6 默认的回复内容	

<b>配置步骤</b>
第一步 进入微信日志的配置页面设置 Token 设置为：你的Token
第二步 登录微信公众平台进行设置 URL（如：http://www.phplog.com/?wxlog，其中的wxlog就是你的Token
	

          </pre>  </div>
          
          
		</div>
		<?php  global $WXLOG; $WXLOG->add_inline_js("jQuery('#wxlog_news').load('http://www.phplog.com/?wxlog_plugins&wn=1');");
	}

}

new WXLOG_About();