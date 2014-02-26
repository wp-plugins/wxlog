<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_Custom_reply_edit {

	public function __construct() {
	}

	//关于页面内容
	public function edit_page() {
		global $wpdb;
		$id = intval($_GET['edit']);
		if($_POST){
			
			$data = array( 
				'keyword' => $_POST['keyword'], 
				'msgtype' => $_POST['msgtype'], 
				'title' => $_POST['title'], 
				'url' => $_POST['url'], 
				'image_url' => $_POST['image_url'], 
				'content' => $_POST['content'], 
				'status' => $_POST['status'] 
			);
			$data_format = array( '%s', '%s', '%s', '%s', '%s', '%s','%d' );
			
			if($id>0){
				$wpdb->update( $wpdb->wxlog_custom_reply, $data, array( 'ID' => $id ), $data_format, array( '%d' ) );
			}else{
				$wpdb->insert( $wpdb->wxlog_custom_reply, $data, $data_format );
			}
			
			$updated = 1;

		}
				
		if($id>0){
			$custom_reply = $wpdb->get_row("SELECT * FROM {$wpdb->wxlog_custom_reply} WHERE ID={$id}");
		}
		
		?>
		<div class="wrap">
            <h2><?php if($id>0){echo '编辑';}else{echo '添加';}?>自定义回复 <a href="javascript:history.go(-1);" class="add-new-h2">返回</a></h2>
			<?php if($updated){?>
			<div class="updated fade below-h2"><p>保存成功。</p></div>
			<?php if($phplog){?>
			<script language="javascript">
            jQuery(document).ready(function(){
                window.location.href='<?=admin_url( 'admin.php?page=wxlog_custom_reply' )?>';
            });
            </script>
			<?php }?>
			<?php }?>
            <form method="post" action="?page=wxlog_custom_reply&edit=<?=$id?>">
            <table class="form-table">
            <tbody><tr valign="top">
            <th scope="row"><label for="blogname">关键字</label></th>
            <td><input name="keyword" type="text" id="keyword" value="<?=$custom_reply->keyword?>" class="regular-text">
            <p class="description">多个关键词请用英文,格开，<code>例如: h,help,帮助</code></p></td>
            </tr>
            <tr valign="top">
            <th scope="row"><label for="default_role">回复类型</label></th>
            <td>
            <select name="msgtype" id="msgtype">
                <option<?php if($custom_reply->msgtype=='text') echo ' selected="selected"';?> value="text">文本消息</option>
                <option<?php if($custom_reply->msgtype=='news') echo ' selected="selected"';?> value="news">图文消息</option>
            </select>
            </td>
            </tr>
 
            <tr valign="top" class="set-news"<?php if($custom_reply->msgtype!='news') echo ' style="display:none;"';?>><th scope="row">标题</th>
            <td><input name="title" type="text" id="title" value="<?=$custom_reply->title?>" class="regular-text"></td>
            </tr>
            <tr valign="top" class="set-news"<?php if($custom_reply->msgtype!='news') echo ' style="display:none;"';?>><th scope="row">URL</th>
            <td><input name="url" type="text" id="url" value="<?=$custom_reply->url?>" class="regular-text"></td>
            </tr>
            <tr valign="top" class="set-news"<?php if($custom_reply->msgtype!='news') echo ' style="display:none;"';?>><th scope="row">图片</th>
            <td><input name="image_url" type="text" id="image_url" value="<?=$custom_reply->image_url?>" class="regular-text"></td>
            </tr>

            <tr valign="top"><th scope="row">回复内容</th><td>
            <textarea name="content" id="content" cols="50" rows="3" style="height:280px;" class="regluar-text ltr"><?=stripslashes($custom_reply->content)?></textarea>
            <p class="description">仅对文本消息有效，其它的回复类型只作为描述</p></td></tr>

            <tr valign="top"><th scope="row">是否有效</th><td>
            <label title="ag:i"><input type="radio" name="status" value="2" <?php if($custom_reply->status==2) echo 'checked="checked"';?>> <span>有效</span></label>
                <label title="g:i A"><input type="radio" name="status" value="0" <?php if($custom_reply->status!=2) echo 'checked="checked"';?>> <span>无效</span></label>
            </td></tr></tbody></table>

            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="保存更改"></p>
            </form>

		</div>

		<?php global $WXLOG; $WXLOG->add_inline_js("jQuery('#msgtype').change(function(){
				if ( jQuery(this).val() == 'news' ) {
					jQuery('.set-news').show();
				} else {
					jQuery('.set-news').hide();
				}
			}).change();");
	}

}