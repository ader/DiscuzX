<?php if (!defined('IS_IN_XWB_PLUGIN')) {die('access deny!');}?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Xweibo错误提示</title>
<link type="text/css" rel="stylesheet" href="<?php echo XWB_plugin::getPluginUrl('images/xwb_'. XWB_S_VERSION .'.css');?>" />
</head>

<body>
<div class="bind-setting xwb-plugin">
	<p class="alert-tips"><?php if(isset($extra_data['api_error']['error']) && false !== strpos($extra_data['api_error']['error'], 'token')): ?>授权出错了......<?php else: ?>出错啦！<?php endif; ?></p>
	<div class="bing-text">
		<p><?php echo $info; ?></p>
            	<?php if(isset($extra_data['api_error']['error_code'])):   $api_error = $extra_data['api_error'];  ?>
            		<?php if($api_error['error_code'] == '21315' || $api_error['error_code'] == '21327'  || false !== strpos($api_error['error'], 'expire')): ?>
					<p>您赋予给该网站的授权已经过期，请转到绑定页面，重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21317'  || false !== strpos($api_error['error'], 'reject')): ?>
					<p>您已经拒绝了该网站的授权，请转到绑定页面，重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21316'  || false !== strpos($api_error['error'], 'revoke')): ?>
					<p>您已经取消了该网站的授权。请转到绑定页面，重新用新浪微博登录进行授权。</p>
					<?php elseif($api_error['error_code'] == '21332'  || false !== strpos($api_error['error'], 'invalid_access_token')): ?>
					<p>您对该网站的授权已经无效。请转到绑定页面，重新用新浪微博登录进行授权。</p>
					<?php else: ?>
					<p>出现了接口错误。如有需要，请转到绑定页面，重新用新浪微博登录进行授权。</p>
					<?php endif; ?>
					<p>详细错误原因：<?php echo htmlspecialchars($api_error['error_code']. ': '. $api_error['error']); ?></p>
            	<?php endif; ?>
    </div>
    
    <div class="setting-box">
		<p><a href="<?php echo XWB_plugin::siteUrl(); ?>">返回首页</a></p>
		<p><a href="<?php echo XWB_plugin::getEntryURL("xwbSiteInterface.bind");?>">转到绑定页面</a></p>
		<p><a href="http://bbs.x.weibo.com/" target="_blank">我是站长，寻求帮助</a></p>
    </div>
    
    
</div>



</body>
</html>
