<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_Custom_menu {

	private $settings;

	public function __construct() {
		
		if($_POST['button']){
			$button = $this->make_menu($_POST['button']);
			$access_token = $this->access_token();
			$_POST['wxlog_custom_menu'] = '{"menu":'.$button.'}';
			if($access_token){
				$con = file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$access_token);
			 	$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;
				$con = $this->get_json($url,$button);
			}
			//echo $button;
			//echo '<pre>',print_r(json_decode($button));die;
			unset($_POST['button']);
		}
		
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	function access_token(){
		$ACCESS_TOKEN = json_decode(file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.get_option('wxlog_AppId').'&secret='.get_option('wxlog_AppSecret')));
		return $ACCESS_TOKEN->access_token;
		$expires_in = $ACCESS_TOKEN->expires_in;//有效时间，单位秒
	}	

	function make_menu($arr){
		$data = '{"button":[';
		foreach($arr as $key=>$value){
			//主菜单
			$data.='{"name":"'.$value['name'].'",';
			//子菜单
			if($value['sub_button']){
				$data.='"sub_button":[';
			}else{
				if($key==(count($arr)-1)){
					$data.='"type":"click","key":"'.$value['key'].'"';
				}else{
					$data.='"type":"click","key":"'.$value['key'].'",';
				}
			}
			foreach($value['sub_button'] as $k=>$v){
				if($k==(count($value['sub_button'])-1)){
					$n='';
				}else{
					$n=',';
				}
				if ( stripos( $v['key'], 'http' ) !== false ){
					$data.='{"type":"view","name":"'.$v['name'].'","url":"'.$v['key'].'"}'.$n;
				}else{
					$data.='{"type":"click","name":"'.$v['name'].'","key":"'.$v['key'].'"}'.$n;
				}
			}
			if($value['sub_button']){
				$data.=']';
			}
			if($key==(count($arr)-1)){
				$data.='}';
			}else{
				$data.='},';
			}
		}
		$data.=']}';
		return $data;	
	}

	function make_menu2($arr){
		//无效
		foreach($arr as $key=>$value){
			if($value['sub_button']){
				unset($arr[$key]['key']);
				foreach($value['sub_button'] as $k=>$v){
					if ( stripos( $v['key'], 'http' ) !== false ){
						$arr[$key]['sub_button'][$k]['type'] = 'view';
						$arr[$key]['sub_button'][$k]['url'] = $v['key'];
						unset($arr[$key]['sub_button'][$k]['key']);
					}else{
						$arr[$key]['sub_button'][$k]['type'] = 'click';
					}
				}
			}else{
				if ( stripos( $value['key'], 'http' ) !== false ){
					$arr[$key]['type'] = 'view';
					$arr[$key]['url'] = $value['key'];
					unset($arr[$key]['key']);
				}else{
					$arr[$key]['type'] = 'click';
				}
			}
		}
		return json_encode(array('button'=>$arr));	
	}

	function get_json($url, $data){
	   $ch = curl_init();
	   $header = "Accept-Charset: utf-8";
	   curl_setopt($ch, CURLOPT_URL, $url);
	   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	   curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	   curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	   curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	   $tmpInfo = curl_exec($ch);
	   if (curl_errno($ch)) {
		  return false;
	   }else{
		  // var_dump($tmpInfo);
		  return true;
	   }
	}

	//初始化设置
	private function init_settings() {
		$this->settings = apply_filters( 'wxlog_custom_menu',
			array(
				'custom_menu' => array('自定义菜单',
					array(
						array(
							'name' 		=> 'wxlog_AppId',
							'std' 		=> '',//初始值
							'placeholder'	=> '请输入AppId',//背景提示
							'label' 	=> 'AppId',
							'desc'		=> sprintf( '微信公众平台 <code>%s</code>', 'http://mp.weixin.qq.com/' )
						),
						array(
							'name' 		=> 'wxlog_AppSecret',
							'std' 		=> '',//初始值
							'placeholder'	=> '请输入AppSecret',//背景提示
							'label' 	=> 'AppSecret',
							'desc'		=> sprintf( '微信公众平台 <code>%s</code>', 'http://mp.weixin.qq.com/' )
						),
						array(
							'name' 		=> 'wxlog_custom_menu',
							'std' 		=> json_encode($get_menu->menu->button),//初始值
							'label' 	=> '菜单',
							'placeholder' => '',//背景提示
							'desc'		=> '只要正确输入AppId和AppSecret就能自动获取到菜单，自定义菜单编辑后将在24小时后对所有用户生效或重新关注亦可生效。',
							'type' 			=> 'textarea'
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
				register_setting( 'wxlog_custom_menu', $option['name'] );
			}
		}
	}

	//管理菜单
	public function admin_menu() {
		add_submenu_page( 'wxlog_log', '自定义菜单', '自定义菜单', 'administrator', 'wxlog_custom_menu', array( $this, 'wxlog_custom_menu_page' ) );
	}

	//设置页面内容
	public function wxlog_custom_menu_page() {
		global $WXLOG;
		$this->init_settings();
		?>

<div class="wrap">
  <form method="post" id="options_form" action="options.php">
    <?php settings_fields( 'wxlog_custom_menu' ); ?>
    <?php screen_icon(); ?>
    <h2 class="nav-tab-wrapper">
      <?php
			    		foreach ( $this->settings as $key => $section ) {
			    			echo '<a href="#settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
			    		}
			    	?>
    </h2>
    <br/>
    <?php
					if ( ! empty( $_GET['settings-updated'] ) ) {
						flush_rewrite_rules();
						echo '<div class="updated fade"><p>自定义菜单已保存。</p></div>';
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
									?>
    <label>
      <input id="setting-<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php checked( '1', $value ); ?> />
      <?php echo $option['cb_label']; ?></label>
    <?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
								case "textarea" :
									if($option['name']=='wxlog_custom_menu'){
										$this->wxlog_custom_menu($value);
									}
									?>
    <textarea style="display:none;" id="setting-<?php echo $option['name']; ?>" class="large-text" cols="50" rows="3" name="<?php echo $option['name']; ?>" <?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea>
    <?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
								case "select" :
									?>
    <select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $option['name']; ?>">
      <?php
										foreach( $option['options'] as $key => $name )
											echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
									?>
    </select>
    <?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;							
								
								default :
									?>
    <input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo $placeholder; ?> />
    <?php
									if ( $option['desc'] )
										echo ' <p class="description">' . $option['desc'] . '</p>';
								break;
							}
							echo '</td></tr>';
						}
						echo '</table></div>';
					}
				?>
    <p class="submit">
      <input type="button" class="button-primary" value="保存设置" />
    </p>
  </form>
</div>
<?php $WXLOG->add_inline_js("jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			jQuery('.nav-tab-wrapper a:first').click();");

	}

	//初始化设置
	private function wxlog_custom_menu($menu) {
		$access_token = $this->access_token();
		if($menu=='null' or empty($menu) and $access_token){
			$menu = file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$access_token);
		}
		$get_menu = json_decode($menu);
		
		//echo '<pre>';print_r($get_menu->menu->button);
		//echo '<pre>';print_r($_POST);
		if($access_token){
?>

<input type="button" value="增加一级菜单" onClick="add_menu1();">

    <div id="dashboard-widgets-wrap">
      <div id="dashboard-widgets" class="metabox-holder">
        <div id='postbox-container-1' class='postbox-container' style="width:100%">
          <div id="normal-sortables" class="meta-box-sortables">
            <?php if($get_menu->menu->button){
			   foreach($get_menu->menu->button as $key=>$value){
			?>
            <div id="m_<?=$key+1?>" class="postbox">
              <div class="handlediv" title="点击以切换"><br /></div>
              <h3 class='hndle'> <span>一级名称：</span>
                <input names="button[][name]" type="text" value="<?=$value->name?>" class="ts" />
                <span>关键字：<span>
					<?php  if($value->url){?>
                  <input names="button[][key]" type="text" value="<?=$value->url?>" class="ts" />
                    <?php }else{?>
                  <input names="button[][key]" type="text" value="<?=$value->key?>" class="ts" />
                    <?php }?>
                <a href="javascript:del_menu('m_<?=$key+1?>');">删除</a> | <a href="javascript:add_menu2('<?=$key+1?>');">新增子菜单</a> </h3>
              <div class="inside">
                <div class="main">
                
                  <div class="widgets-holder-wrap" style="border: 0;">
                <div class="widgets-sortables ui-sortable" id="sidebar-<?=$key+1?>">
                  <?php  if($value->sub_button){
                     	foreach($value->sub_button as $k=>$val){
                 		?>
                  <div class="widget" id="m_<?=$key+1?>_<?=$k+1?>">
                    <div class="widget-top" style="min-height: 35px;">
                      <div class="widget-title" style="padding: 4px 9px;">
                        <h4>
                          <label>二级名称：</label>
                          <input names="sub_button[][name]" type="text" value="<?=$val->name?>" class="ts" />
                          <label>关键字：</label>
                          <input names="sub_button[][key]" type="text" value="<?=$val->key?>" class="ts" />
                          <a onclick="del_menu('m_<?=$key+1?>_<?=$k+1?>');" href="javascript:vod(0);">删除</a>
                        </h4>
                      </div>
                    </div>
                  </div>
                  <?php }}?>
                </div>
              </div>
                  
                  
                </div>
              </div>
            </div>
            <?php }}?>
          </div>
        </div>
      </div>
    </div>
    <!-- dashboard-widgets-wrap --> 

<textarea style="display:none;" class="large-text" cols="50" rows="3">实时获取：<?php echo file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$access_token); ?></textarea>

<script>
jQuery(document).ready(function(){
	/*判断序列号是否正确以及获取该序列号的相关信息*/
	jQuery(".button-primary").click(function(){
		goto();
	});
	jQuery("#setting-wxlog_custom_menu").val('<?=$menu?>');	
});	
function goto(){
	var _name = jQuery("input[names='button[][name]']");
	var _key = jQuery("input[names='button[][key]']");
	if(_name.length>0){
		for (var i=0;i<_name.length;i++){
			if(_name[i].value==''){
				alert("请输入一级菜单名称!");
				_name[i].focus();
				return false;
			}
			jQuery(_name[i]).attr("name",'button['+i+'][name]');
			jQuery(_key[i]).attr("name",'button['+i+'][key]');
			
			var _sub_div = jQuery(_name[i]).parents('.postbox');
			var _sub_name = _sub_div.find("input[names='sub_button[][name]']");
			//alert(_sub_name.length);
			for (var ii=0;ii<_sub_name.length;ii++){
				if(_sub_name[ii].value==''){
					alert("请输入二级菜单名称!");
					_sub_name[ii].focus();
					return false;
				}
			}
			var _sub_key = _sub_div.find("input[names='sub_button[][key]']");
			for (var iii=0;iii<_sub_key.length;iii++){
				if(_sub_key[iii].value==''){
					alert("请输入二级菜单关键字!");
					_sub_key[iii].focus();
					return false;
				}
				jQuery(_sub_name[iii]).attr("name",'button['+i+'][sub_button]['+iii+'][name]');
				jQuery(_sub_key[iii]).attr("name",'button['+i+'][sub_button]['+iii+'][key]');
				
			}
			if(_sub_name.length==0){
				if(_key[i].value==''){
					alert("请输入一级菜单关键字!");
					_key[i].focus();
					return false;
				}
			}
		}
	}
	//return false;
	//jQuery("#frm_submit").attr("action","?act=submit");
	jQuery("#options_form").submit();

}	

//删除行
function add_menu2(i){
	var menu2=jQuery("#sidebar-"+i+" .widget");
	var len=menu2.length;
	if(len>=5){
		alert('最多只能增加5个二级菜单');return;
	}
	jQuery("#sidebar-"+i).append('<div id="m_'+i+'_'+(len+1)+'" class="widget">'+
                    '<div class="widget-top" style="min-height: 35px;">'+
                      '<div class="widget-title" style="padding: 4px 9px;">'+
                              '<h4>'+
                                  '<label>二级名称：</label>'+
                                  ' <input names="sub_button[][name]" type="text" value="" class="ts" />'+
                                  ' <label>关键字：</label>'+
                                  ' <input names="sub_button[][key]" type="text" value="" class="ts" />'+
                                  ' <a onclick="del_menu(\'m_'+i+'_'+(len+1)+'\');" href="javascript:vod(0);">删除</a>'+
                              '</h4>'+

			 '</div></div>'+
	'</div>');
}


//删除行
function add_menu1(){
	var menu1=jQuery(".postbox");
	var len=menu1.length;
	if(len>=3){
		alert('最多只能增加3个一级菜单');return false;
	}
	jQuery("#normal-sortables").append('<div id="m_'+(len+1)+'" class="postbox" >'+
              '<div class="handlediv" title="点击以切换"><br /></div>'+
              '<h3 class="hndle"> <span>一级名称：</span>'+
                '<input names="button[][name]" type="text" value="" class="ts" />'+
                '<span>关键字：<span>'+
                '<input names="button[][key]" type="text" value="" class="ts" />'+
                ' <a href="javascript:del_menu(\'m_'+(len+1)+'\');">删除</a> | <a href="javascript:add_menu2(\''+(len+1)+'\');">新增子菜单</a>'+
			  '</h3>'+
              '<div class="inside">'+
                '<div class="main">'+
                  '<div class="sidebars-column-1">'+
                  '<div class="widgets-holder-wrap" style="border: 0;">'+
                '<div class="widgets-sortables ui-sortable" id="sidebar-'+(len+1)+'">'+
			 '</div></div></div></div></div>'+
	'</div>');
}

//删除行
function del_menu(id){
	jQuery("#"+id).remove();
}
</script>

<?php 

wp_enqueue_script( 'dashboard' );
wp_enqueue_script('admin-widgets');
		}else{echo '<div class="error fade below-h2"><p>获取自定义权限失败。</p></div>';?>
			
<script>
jQuery(document).ready(function(){
	/*判断序列号是否正确以及获取该序列号的相关信息*/
	jQuery(".button-primary").click(function(){
		jQuery("#options_form").submit();
	});
});				
</script>			
<?php 	}
	}

}

new WXLOG_Custom_menu();