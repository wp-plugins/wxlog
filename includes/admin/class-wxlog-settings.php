<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_Settings {

	private $settings;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	//初始化设置
	private function init_settings() {
		global $WXLOG;
		$this->settings = apply_filters( 'wxlog_settings',
			array(
				'general' => array('基本设置',
					array(
						array(
							'name' 		=> 'wxlog_token',
							'std' 		=> '',//初始值
							'placeholder'	=> '请输入Token',//背景提示
							'label' 	=> '微信 Token',
							'desc'		=> sprintf( '微信公众平台 <code>%s</code>', 'http://mp.weixin.qq.com/' )
						),
						array(
							'name' 		=> 'wxlog_default_image',
							'std' 		=> '',//初始值
							'placeholder'	=> '请输入默认缩略图',//背景提示
							'label' 	=> '默认缩略图',
							'desc'		=> '当博客日志没有设置缩略图，将显示本图片'
						),												
						array(
							'name' 		=> 'wxlog_post_max',
							'std' 		=> '',
							'placeholder'	=> '5',//初始值
							'label' 	=> '搜索博客结果最大条数',
							'desc'		=> '',
							'type'      => 'select',
							'options'   => array(
								'1'       => '1条',
								'2'       => '2条',
								'3'       => '3条',
								'4'       => '4条',
								'5'       => '5条',
								'6'       => '6条',
								'7'       => '7条',
								'8'       => '8条',
								'9'       => '9条',
								'10'      => '10条',
								'0'      => '关闭搜索博客'
							)
						),
						array(
							'name' 		=> 'wxlog_blacklist_user',
							'std' 		=> '',//初始值
							'label' 	=> '黑名单',
							'placeholder' => '请输入黑名单',//背景提示
							'desc'		=> '这里填写FromUserName字段，多个用户请用英文,号隔开',
							'type' 			=> 'textarea'
						),
						array(
							'name' 		=> 'wxlog_blacklist_message',
							'std' 		=> '您已经被管理员列入黑名单',//初始值
							'placeholder'	=> '',
							'label' 	=> '黑名单自动回内容',
							'desc'		=> '',
							'type'      => 'select',
							'options'   => array(
								'您已经被管理员列入黑名单'       => '您已经被管理员列入黑名单',
								'您的消息已经被屏蔽'       => '您的消息已经被屏蔽',
								'custom'      => '自定义回复内容'								
							)
						),
						array(
							'name' 		=> 'wxlog_blacklist_message_custom',
							'std' 		=> '',
							'label' 	=> '自定义回复内容'
						),
					),
				),
				'advanced' => array('高级',
					array(
						array(
							'name' 		=> 'wxlog_simsimi',
							'cb_label'  => '开启',
							'std' 		=> '1',
							'label' 	=> '是否开启小黄鸡',
							'desc'		=> '开启小黄鸡聊天',
							'type' 		=> 'checkbox'
						),
						array(
							'name' 		=> 'wxlog_txt_log',
							'cb_label'  => '开启',
							'std' 		=> '1',
							'label' 	=> '是否开启文本日志',
							'desc'		=> '日志保存在/wp-content/uploads/wxlog_logs/目录下',
							'type' 		=> 'checkbox'
						),						
					)
				)
			)
		);
	}

	/**
	 * register_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {
		$this->init_settings();
		foreach ( $this->settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) )
					add_option( $option['name'], $option['std'] );
				register_setting( 'wxlog_log', $option['name'] );
			}
		}
	}

	//管理菜单
	public function admin_menu() {
		add_submenu_page( 'wxlog_log', '配置', '配置', 'administrator', 'wxlog_setting', array( $this, 'wxlog_settings_page' ) );
	}

	//设置页面内容
	public function wxlog_settings_page() {
		global $WXLOG;
		$this->init_settings();
		?>
		<div class="wrap">
			<form method="post" action="options.php">
				<?php settings_fields( 'wxlog_log' ); ?>
				<?php screen_icon(); ?>
			    <h2 class="nav-tab-wrapper">
			    	<?php
			    		foreach ( $this->settings as $key => $section ) {
			    			echo '<a href="#settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
			    		}
			    	?>
			    </h2><br/>
				<?php
					if ( ! empty( $_GET['settings-updated'] ) ) {
						flush_rewrite_rules();
						echo '<div class="updated fade"><p>设置已保存。</p></div>';
					}
					foreach ( $this->settings as $key => $section ) {
						echo '<div id="settings-' . sanitize_title( $key ) . '" class="settings_panel">';
						echo '<table class="form-table">';
						foreach ( $section[1] as $option ) {
							$placeholder = ( ! empty( $option['placeholder'] ) ) ? 'placeholder="' . $option['placeholder'] . '"' : '';
							echo '<tr valign="top"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';
							if ( ! isset( $option['type'] ) ) $option['type'] = '';
							$value = get_option( $option['name'] );
							switch ( $option['type'] ) {
								case "checkbox" :
									?><label><input id="setting-<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php checked( '1', $value ); ?> /> <?php echo $option['cb_label']; ?></label><?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
								case "textarea" :
									?><textarea id="setting-<?php echo $option['name']; ?>" class="large-text" cols="50" rows="3" name="<?php echo $option['name']; ?>" <?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea><?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
								case "select" :
									?><select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $option['name']; ?>"><?php
										foreach( $option['options'] as $key => $name )
											echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
									?></select><?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
								default :
									?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo $placeholder; ?> /><?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
							}
							echo '</td></tr>';
						}
						echo '</table></div>';
					}
				?>
				<p class="submit"><input type="submit" class="button-primary" value="保存设置" /></p>
		    </form>
		</div>
		<?php $WXLOG->add_inline_js("jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			jQuery('#setting-wxlog_blacklist_message').change(function(){
				if ( jQuery(this).val() == 'custom' ) {
					jQuery('#setting-wxlog_blacklist_message_custom').closest('tr').show();
				} else {
					jQuery('#setting-wxlog_blacklist_message_custom').closest('tr').hide();
				}
			}).change();
			jQuery('.nav-tab-wrapper a:first').click();");
	}

}

new WXLOG_Settings();