<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_About {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
	}

	//管理菜单
	public function admin_menu() {
		add_submenu_page( 'wxlog_log', '关于', '关于', 'administrator', 'wxlog_about', array( $this, 'wxlog_about_page' ) );
	}

	//关于页面内容
	public function wxlog_about_page() {
		?>
		<div class="wrap">
            <h2>关于微信日志 <a href="?page=wxlog_setting" class="add-new-h2">配置微信日志</a></h2>
            <div><pre>
            
=== wxlog ===
Name: 微信日志 For Wordpress
Contributors: zhangji
Link: http://www.phplog.com/wxlog
Tags: 微信日志,wxlog,微信,weixin,wx
Requires at least: 3.8
Tested up to: 3.8
License: GPLv3

== Description ==

微信日志是基于Wordpress的完全开源的微信插件，利用微信日志能很方便扩展微信公众号的功能。微信日志完美支持Wordpress的插件方式来扩展更多的功能。

== Function ==

◦记录用户的消息，支持回复预览。
◦支持添加自定义回复
◦支持自定义菜单，必是有自定义菜单权限才能使用
◦调试功能，方便开发时测试
◦支持小黄鸡机器人聊天，可以在配置页面设置是否开启
◦支持插件，方便扩展更多功能
◦官方插件功能，只要开启插件就能实现相应的功能，无需另行开发

== Installation ==

1、下载微信日志 For WordPress
2、上传 wxlog 到 `/wp-content/plugins/` 目录
3、进入后台 -> 插件 -> 已安装的插件 -> 启用微信日志 For WordPress的Token

== Configuration ==

1、进入微信日志的配置页面设置 Token 设置为：你的Token
2、登录微信公众平台进行设置 URL（如：http://www.phplog.com/?wxlog，其中的wxlog就是你的Token

== Upgraded version ==

到官网http://www.phplog.com下载最新版微信日志 For WordPress，解压后将wxlog文件夹覆盖原文件夹即可。

== Precautions ==

1、本插件是在Wordpress3.8基础上开发的。

== Changelog ==

= 1.0.5 =
* 修正在手机显示错位的bug。
* 修正与其他插件兼容性的问题

= 1.0.4 =
* 修改文本日志保存的目录。
* 修正一个小BUG 感谢网友火恋神父

= 1.0.3 =
* 增加官方插件功能。

= 1.0.2 =
* 修正一些bug
* 结构优化
* 增加自定义回复预览
* 增加支持插件功能
* 增强优化消息测试功能

= 1.0.1 =
* 增加基本参数设置定
* 增加自定义回复

= 1.0.0 =
* 基础版本，微信的基本功
* 消息测试功能            
            
          </pre>  </div>
		</div>
		<?php
	}

}

new WXLOG_About();