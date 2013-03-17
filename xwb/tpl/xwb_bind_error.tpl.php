<?php if (!defined('IS_IN_XWB_PLUGIN')) {die('access deny!');}?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>绑定错误提示</title>
<link type="text/css" rel="stylesheet" href="<?php echo XWB_plugin::getPluginUrl('images/xwb_'. XWB_S_VERSION .'.css');?>" />
<link href="<?php echo XWB_plugin::getPluginUrl('images/xwb_admin.css');?>" rel="stylesheet" type="text/css" />
<script language="javascript" language="javascript">
	function xwb_unbind(){
		if(window.confirm('解除绑定？')){
			document.getElementById('unbindFrm').submit();
			setTimeout("window.location.reload();", 500);
		}
	}
</script>

</head>

<body>
<div class="bind-setting xwb-plugin">
	<p class="alert-tips"><?php if(isset($extra_data['api_error']['error']) && false !== strpos($extra_data['api_error']['error'], 'token')): ?>授权出错了......<?php else: ?>与新浪微博API通讯时发生错误！<?php endif; ?></p>
	<div class="bing-text">
            <?php if ( 'api' == $errorType ) { ?>
            	<?php if(isset($extra_data['api_error']['error_code'])):   $api_error = $extra_data['api_error'];  ?>
            		<?php if($api_error['error_code'] == '21315' || $api_error['error_code'] == '21327'  || false !== strpos($api_error['error'], 'expire')): ?>
					<p>您赋予给该网站的授权已经过期，请重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21317'  || false !== strpos($api_error['error'], 'reject')): ?>
					<p>您已经拒绝了该网站的授权，请重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21316'  || false !== strpos($api_error['error'], 'revoke')): ?>
					<p>您已经取消了该网站的授权。请重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21332'  || false !== strpos($api_error['error'], 'invalid_access_token')): ?>
					<p>您对该网站的授权已经无效。请重新用新浪微博登录进行授权。</p>
					<?php else: ?>
					<p>出现了接口错误。如有需要，请重新用新浪微博登录进行授权。</p>
					<?php endif; ?>
					<p>详细错误原因：<?php echo htmlspecialchars($api_error['error_code']. ': '. $api_error['error']); ?></p>
				<?php else: ?>
				<p>服务器无法连接到新浪微博API服务器；或新浪微博API服务器无响应。</p>
				<p>稍候一下，然后重新打开此页面；如果此错误信息重复出现，<strong>请联系网站管理员处理。</strong></p>
            	<?php endif; ?>
			<?php } elseif ('file' == $errorType) { ?>
				<p>请确保拥有权限，无法创建数据缓存文件。</p>
			<?php } ?>
    </div>
    
    <div class="setting-box">
        <form id="unbindFrm" action="<?php echo XWB_plugin::getEntryURL('xwbSiteInterface.unbind');?>" method="post" target="xwbSiteRegister" >
        	<input type="hidden" name="<?php echo XWB_TOKEN_NAME; ?>" value="<?php echo $unbind_tokenhash; ?>" />
			<h3>解除绑定</h3>
			<div class="xwb-plugin-btn"><input type="button" class="button" value="解除绑定" onclick="xwb_unbind();return false;" ></div>
			<p class="tips"></p>
		</form>
    </div>
    
    <div class="setting-box">
    	<h3>重新授权</h3>
    	<a href="<?php echo XWB_plugin::getEntryURL('xwbAuth.login');?>" target="_blank">点击此处，重新用新浪微博登录进行授权。</a>
    	<br />
    	<?php if(isset($extra_data['sina_uid']) && $extra_data['sina_uid'] > 0): ?>
    		请确保当前你在weibo.com下登录为：<a target="_blank" href="http://weibo.com/u/<?php echo htmlspecialchars($extra_data['sina_uid']); ?>">http://weibo.com/u/<?php echo htmlspecialchars($extra_data['sina_uid']); ?></a>
    	<?php endif; ?>
    </div>
    
</div>
<iframe src="" name="xwbSiteRegister" frameborder="0" height="0" width="0"></iframe>
</body>
</html>
