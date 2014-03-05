=== 微信日志 ===
Name: 微信日志 For Wordpress
Contributors: zhangji
Link: http://www.phplog.com/wxlog
Tags: 微信日志,wxlog,微信,weixin,wx,PHP日志,phplog,易信,yixin,yx
Requires at least: 3.8
Tested up to: 3.8
License: GPLv3

== Description ==

微信日志是基于Wordpress的完全开源的微信插件，利用微信日志能很方便扩展微信公众号的功能。微信日志完美支持Wordpress的插件方式来扩展更多的功能。

1. 记录用户的消息，支持回复预览。
2. 支持添加自定义回复
3. 支持自定义菜单，必是有自定义菜单权限才能使用
4. 调试功能，方便开发时测试
5. 支持插件，方便扩展更多功能
6. 官方插件功能，只要开启插件就能实现相应的功能，无需另行开发
7. 支持回复表情
8. 加QQ群：345619752了解最新动态


== Installation ==

1. 下载微信日志 For WordPress
2. 上传 wxlog 到 `/wp-content/plugins/` 目录
3. 进入后台 -> 插件 -> 已安装的插件 -> 启用微信日志 For WordPress的Token

== Screenshots ==

1. 关于微信日志。
2. 自定义菜单，支持拖拽排序
3. 回复消息预览，支持表情
4. 调试功能，方便回复测试。
3. 官方插件列表。

== Frequently asked questions ==
1. http://www.phplog.com/wxlog


== 配置步骤 ==

1. 第一步 进入微信日志的配置页面设置 Token 设置为：你的Token
2. 第二步 登录微信公众平台进行设置 URL（如：http://www.phplog.com/?wxlog，其中的wxlog就是你的Token

== 版本更新 ==

到官网http://www.phplog.com下载最新版微信日志 For WordPress，解压后将wxlog文件夹覆盖原文件夹即可。

== 注意事项 ==

1、本插件是在Wordpress3.8基础上开发的。

== Changelog ==



= 1.0.7 =
* 修正回复里无法使用"\r\n"换行 感谢网友webzhiyi
* 自定义回复支持多图文
* 修复关闭官方插件的bug
* 修复变量命名错误，升级后需要重新启用官方插件功能

= 1.0.6 =
* 修正bug图文消息保存的时候，在""号前自动填了\ 感谢网友楚三户
* 增加回复为空时，可自定义默认回复内容
* 去除了小黄鸡，移到官方插件功能里 感谢网友Pell.Chen
* 修正bug最新消息页面的关注数量和取消关注数量的链接和筛选功能 感谢网友Mura
* 增加自定义检索最新博客日志和最火博客日志和按分类检索博客日志 感谢网友Mura
* 增加预览窗口可以在直接测试回复

= 1.0.5 =
* 修正在手机显示错位的bug。
* 修正与其他插件兼容性的问题

= 1.0.4 =
* 修改文本日志保存的目录。
* 修正一个小BUG 感谢网友火恋の神父

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