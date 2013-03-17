<?php
/**
 * 插件程序常量配置文件，由svn41经过删减和重写而来
 * @author xionghui<xionghui1@staff.sina.com.cn>
 * @modifier yaoying <yaoying@staff.sina.com.cn>
 * @copyright SINA INC.
 * @version $Id: common.cfg.php 1026 2012-09-28 03:42:09Z yaoying $
 *
 */

//-----------------------------------------------------------------------
// 是否在插件程序中的标识
define('IS_IN_XWB_PLUGIN',		true);
define('XWB_P_PROJECT', 'xwb4dx');
define('XWB_P_VERSION',		'2.1.5');
define('XWB_P_INFO_API',	'http://x.weibo.com/service/stdVersion.php?p='. XWB_P_PROJECT. '&v='. XWB_P_VERSION );
//-----------------------------------------------------------------------

// 路径配置相关
define('XWB_P_ROOT',			dirname(__FILE__) );
define('XWB_P_DIR_NAME',		basename(XWB_P_ROOT) );
define('XWB_P_DATA',		XWB_P_ROOT. DIRECTORY_SEPARATOR. 'log' );
//-----------------------------------------------------------------------
// XWB 所用的SESSION数据存储变量名
define('XWB_CLIENT_SESSION',	'XWB_P_SESSION');

//-----------------------------------------------------------------------
//获取模块路由的变量名
define('XWB_R_GET_VAR_NAME',	'm');
//默认路由
define('XWB_R_DEF_MOD',			'xwbSiteInterface');
//默认路由方法
define('XWB_R_DEF_MOD_FUNC',	'default_action');

//XWB全局数据存储变量名
define('XWB_SITE_GLOBAL_V_NAME','XWB_SITE_GLOBAL_V_NAME');

//-----------------------------------------------------------------------
// 微博 api url
define('XWB_API_URL', 	'http://api.t.sina.com.cn/');
// 微博API使用的字符集，大写 如果是UTF-8 则表示为  UTF-8
define('XWB_API_CHARSET',		'UTF8');
//微博评论回推地址
define('XWB_PUSHBACK_URL', 'http://service.x.weibo.com/pb/');

//-----------------------------------------------------------------------
//插件所服务的站点根目录。这是本文件唯一出现"S"类别的常量
define('XWB_S_ROOT',	dirname(XWB_P_ROOT));

//表单验证token
define('XWB_TOKEN_NAME', 'tokenhash');

//-----------------------------------------------------------------------
//API 相关（Oauth 2.0）配置
define('OAUTH2_WEIBO_API_URL', 	'https://api.weibo.com/2/');

///OAUTH 2.0 认证请求方法
/**
 * OAUTH 2.0外部请求代理URL
 * 由于新浪微博的OAUTH 2.0 callback地址只允许填写1个，但有些网站已经先期部署了2.0版本，
 * 此选项即设置认证请求方法。
 * 1：xweibo原生模式。xweibo不经过中转页自己跳转、自己获取code。
 * 2：中转url之通过API获取access token。xweibo跳转到指定的中转URL，由其跳转到OAuth 2.0认证页面；
 * 认证成功后让中转URL带code跳转回指定的页面中，由xweibo直接通过微博API获取access token
 * 3：中转url之通过第三方模式获取access token。xweibo跳转到指定的中转URL，由其跳转到OAuth 2.0认证页面；
 * 认证成功后让中转url带code跳转回指定的页面中，由xweibo通过其它方式获取access token（需要自己写代码）
 * @var integer
 */
define('OAUTH2_AUTHRIZE_TYPE', 1);

/**
 * OAUTH 2.0 REDIRECT SOURCE
 * OAUTH2_AUTHRIZE_TYPE为1时候，请填写文件名。
 * OAUTH2_AUTHRIZE_TYPE为2或者3时候，请填写完整的URL。xweibo将跳转到指定的url中
 * @var integer
 */
define('OAUTH2_REDIRECT_SOURCE', '');

/**
 * OAUTH 2.0 REDIRECT SOURCE
 * OAUTH2_AUTHRIZE_TYPE为2时，需要在此填写在开放平台填写的完整回调URL
 * OAUTH2_AUTHRIZE_TYPE为1时，选填。一旦填入，将以此为值
 * @var string
 */
define('OAUTH2_PLATFORM_CALLBACK_URL', '');

/**
 * 
 * 是否显示OAUTH2可能引起错误的模块
 * @var integer
 */
define('OAUTH2_ERROR_VISIBLE',0);

/**
 * 是否开启获取当前用户未读消息数
 * 默认不开启（即没有小黄签），原因：resetCount为高权限
 */
define('OAUTH2_ENABLE_UNREAD', 0);