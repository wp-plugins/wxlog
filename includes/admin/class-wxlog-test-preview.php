<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WXLOG_Test_preview {

	public function __construct() {
	}

	//关于页面内容
	public function preview_page() {
		global $wpdb;
		$id = intval($_GET['preview']);
		if($id>0 and empty($_GET['signature'])){
			$wxlog_log = $wpdb->get_row("SELECT * FROM {$wpdb->wxlog_log} WHERE ID={$id}");
		}
		if($wxlog_log){
			$_GET['HTTP_RAW_POST_DATA'] = $wxlog_log->message;
			$_GET['timestamp'] = strtotime($wxlog_log->timestamp);
		}
		if($_GET['HTTP_RAW_POST_DATA']){
			$message = wxlog_xml_to_array($_GET['HTTP_RAW_POST_DATA']);//print_r($message);
		}else{exit('null');}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PHP日志-微信日志-预览</title>
<meta name="title" content="PHP日志-微信日志-预览">
<meta name="keywords" content="PHP日志-微信日志-预览">
<meta name="description" content="PHP日志-微信日志-预览" />
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<link rel="stylesheet" type="text/css" href="/wp-content/plugins/wxlog/includes/admin/css/preview.css">
<?php if($zhangji){?>
<link rel="stylesheet" type="text/css" href="https://res.wx.qq.com/zh_CN/htmledition/style/comm18f87a.css">
<?php }?>
<div id="container" style="rgba(0,0,0,0.5);">
  <div id="chat" class="chatPanel normalPanel" ctrl="1" style="">
    <div class="content">
      <div class="chat lightBorder" style="visibility: visible; ">
        <div class="chatContainer" style="height: 480px; ">
          <div class="rightOpBtn groupChat" id="rightOpBtn" click="toggleChatMgr" style="display: none; "><a href="javascript:;"></a></div>
          <div class="backToChat" id="leftOpBtn" click="toggleChatMgr" style="display: none; "><a href="javascript:;"></a></div>
          <div class="chatMainPanel" id="chatMainPanel">
            <div class="chatTitle">
              <div class="chatNameWrap">
                <p class="chatName" id="messagePanelTitle">PHP日志</p>
              </div>
            </div>
            <div class="chatScorll" style="position: relative; ">
              <div id="chat_chatmsglist" class="chatContent" ctrl="1" style="position: absolute; ">
                <div class="chatItem me" un="item_1390543792332">
                  <div class="time"> <span class="timeBg left"></span>
                    <?=date('Y-m-d H:i:s',$_GET['timestamp']+3600*8)?>
                    <span class="timeBg right"></span> </div>
                  <div class="chatItemContent"> <img class="avatar" src="/wp-content/plugins/wxlog/includes/admin/images/man.jpg" onerror="reLoadImg(this)" un="avatar_phplog" title="PHP日志" click="showProfile" username="phplog.com">
                    <div class="cloud cloudText" un="cloud_1020740958" msgid="1020740958">
                      <div class="cloudPannel" style="">
                        <div class="sendStatus"> </div>
                        <div class="cloudBody">
                          <div class="cloudContent">
                            <pre style="white-space:pre-wrap"><?php if($message['MsgType']=='image'){echo '<img src="'.$message['PicUrl'].'" style="max-width:260px;">';}else{echo $message['Content'];}?></pre>
                          </div>
                        </div>
                        <div class="cloudArrow "></div>
                      </div>
                    </div>
                  </div>
                </div>
      <?php if($wxlog_log->reply){
				$content = $wxlog_log->reply;
			}else{
				$url = site_url().'/?'.TOKEN.'&signature='.$_GET['signature'].'&timestamp='.$_GET['timestamp'].'&nonce='.$_GET['nonce'];
				$content = get_xml($url,$_GET['HTTP_RAW_POST_DATA']);
			}
			$reply = wxlog_xml_to_array($content);
			 //echo '<pre>';
			 //echo $content;
			 //print_r($reply);
			 if($reply['MsgType']=='text'){?>
                <div class="chatItem you" un="item_1020740959">
                  <div class="chatItemContent"> <img class="avatar" src="/wp-content/plugins/wxlog/includes/admin/images/man.jpg" onerror="reLoadImg(this)" un="avatar_phplog" title="PHP日志" click="showProfile" username="phplog.com">
                    <div class="cloud cloudText" un="cloud_1020740959" msgid="1020740959">
                      <div class="cloudPannel" style="">
                        <div class="sendStatus"> </div>
                        <div class="cloudBody">
                          <div class="cloudContent">
                            <pre style="white-space:pre-wrap"><?=$reply['Content']?></pre>
                          </div>
                        </div>
                        <div class="cloudArrow "></div>
                      </div>
                    </div>
                  </div>
                </div>
         <?php }?>
         <?php if($reply['MsgType']=='news'){
				$reply = XML_unserialize($content);
				if($reply['xml']['ArticleCount']==1){
					$reply['xml']['Articles']['item'][0]=$reply['xml']['Articles']['item'];
				}?>
                <div class="chatItem you" un="item_1020740964"> <!--media mesg-->
                  <div class="media">
                    <div class="mediaPanel"> <a href="<?=$reply['xml']['Articles']['item'][0]['Url']?>" target="_blank">
                      <div class="mediaImg">
                        <div class="mediaImgPanel"><img src="<?=$reply['xml']['Articles']['item'][0]['PicUrl']?>" style="visibility: inherit; height: 273.75px; width: 365px; top: -54.875px; left: 0px; " onload="jQuery.genImgCentralStyle(this);"></div>
                        <div class="mediaImgFooter">
                          <p class="mesgTitleTitle left">
                            <?=$reply['xml']['Articles']['item'][0]['Title']?>
                          </p>
                          <div class="clr"></div>
                        </div>
                      </div>
                      </a>
                      <div class="mediaContent">
                        <?php foreach($reply['xml']['Articles']['item'] as $key=>$value){if($key>0){?>
                        <a href="<?=$value['Url']?>" target="_blank">
                        <div class="mediaMesg"><span class="mediaMesgDot"></span>
                          <div class="mediaMesgTitle left">
                            <p class="left">
                              <?=$value['Title']?>
                            </p>
                            <div class="clr"></div>
                          </div>
                          <div class="mediaMesgIcon right"><img src="<?=$value['PicUrl']?>"></div>
                          <div class="clr"></div>
                        </div>
                        </a>
                        <?php }}?>
                      </div>
                    </div>
                  </div>
                </div>
          <?php }?>
          <?php if($reply['MsgType']=='image'){
			  $reply = XML_unserialize($content);
				if($reply['xml']['ArticleCount']==1){
					$reply['xml']['Articles']['item'][0]=$reply['xml']['Articles']['item'];
				}?>
                <div class="chatItem you" un="item_1020741112">
                  <div class="time"><span class="timeBg left"></span> <?=date('Y-m-d H:i:s',$reply['xml']['CreateTime'])?> <span class="timeBg right"></span> </div>
                  
                  <!--media mesg content-->
                  <div class="media mediaFullText"> <a href="<?=$reply['xml']['Articles']['item'][0]['Url']?>" style="text-decoration: none;" target="_blank"></a>
                    <div class="mediaPanel"> <a href="<?=$reply['xml']['Articles']['item'][0]['Url']?>" style="text-decoration: none;" target="_blank">
                      <div class="mediaHead"><span class="title left"><?=$reply['xml']['Articles']['item'][0]['Title']?></span><span class="time right">01-27</span>
                        <div class="clr"></div>
                      </div>
                      <div class="mediaImg"><img src="<?=$reply['xml']['Articles']['item'][0]['PicUrl']?>" style="visibility: inherit; height: 188.203125px; width: 365px; top: -12.1015625px; left: 0px; " onload="jQuery.genImgCentralStyle(this);"></div>
                      <div class="mediaContent mediaContentP">
                        <p><?=$reply['xml']['Articles']['item'][0]['Description']?></p>
                      </div>
                      </a>
                      <div class="mediaFooter"><a href="<?=$reply['xml']['Articles']['item'][0]['Url']?>" style="text-decoration: none;" target="_blank"></a> <a href="<?=$reply['xml']['Articles']['item'][0]['Url']?>" style="text-decoration: none;" target="_blank"> <span class="mesgIcon left"></span> <span class="left" style="line-height:39px;">查看全文</span>
                        <div class="clr"></div>
                        </a> </div>
                    </div>
                  </div>
                </div>
         <?php }?>
              </div>
              <div class="scrollbarBox" style="position: absolute; right: 0px; top: 0px; height: 100%; ">
                <div class="scrollbar" style="position: absolute; right: 0px; top: 89px; opacity: 0; height: 42px; z-index: 0; display: block; "></div>
              </div>
            </div>
            <div id="chat_editor" class="chatOperator lightBorder" ctrl="1">
              <div class="inputArea" style="">
                <div class="attach"> <a href="javascript:;" id="sendEmojiIcon" class="func expression" click="showEmojiPanel" title="选择表情" style=""></a> <a href="javascript:;" id="screenSnapIcon" class="func screensnap" click="screenSnap" title="发送截屏" style=""></a>
                  <input type="hidden" name="uploadmediarequest" value="{BaseRequest:{}}">
                  <a href="javascript:;" class="func file" style="position:relative;display:block;margin:0;" title="文件图片" id="uploadFileContainer">
                  <div style="position: absolute;top:0;left:0; width: 100%; height: 100%;overflow:hidden;filter:alpha(opacity=0);opacity:0;cursor:pointer;">
                    <div>
                      <input change="sendAppMsg@form" type="file" name="filename" style="width:100%;height:100%;margin:0;cursor:pointer;font-size:100px;">
                    </div>
                  </div>
                  </a> </div>
                <textarea type="text" id="textInput" class="chatInput lightBorder"></textarea>
                <a href="javascript:;" class="chatSend" click="sendMsg@.inputArea"><b>发送</b></a>
                <div id="recordInput" class="recordInput chatInput" style="display:none;"></div>
                <div class="clr"></div>
                <textarea type="text" id="textInput" class="chatInput lightBorder" style="visibility: hidden; position: absolute; left: -1000px; padding: 0px 10px; width: 403px; overflow: hidden; "></textarea>
              </div>
              <div class="dragUploaderPanel" id="dragPanel" style="display:none;">
                <div intxt="释放鼠标" outtxt="请将文件拖拽到这里发送" style="text-align:center;"> </div>
              </div>
              <div class="emojiPanel" style="display:none;" id="emojiPanel"></div>
            </div>
          </div>
        </div>
      </div>
      <div style="clear: both; visibility: visible; "></div>
    </div>
  </div>
</div>
</body></html>
<?php		
	}
}
?>
