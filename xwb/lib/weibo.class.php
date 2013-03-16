<?php

require_once dirname(__FILE__). "/conv_2/conv_2_base.class.php";

$GLOBALS['__CLASS_STATIC_xwbOAuthV2_tool_http'] = array(
'boundary'=>''
);

/**
 * 微博api操作类(OAuth 2.0)
 * $useType参数已废弃，不再使用
 * @author xionghui<xionghui1@staff.sina.com.cn>
 * @author yaoying <yaoying@staff.sina.com.cn>
 * @since 2012-07-02
 * @copyright Xweibo (C)1996-2099 SINA Inc.
 * @version $Id: weibo.class.php 1024 2012-09-26 10:41:32Z yaoying $
 */
class weibo
 {

	var $format = 'json';

	/**
	 * Contains the last HTTP status code returned. 
	 *
	 * @ignore
	 */
	var $http_code;
	
	/**
	 * Contains the last API call.
	 * 原来的：var $last_req_url = '';
	 *
	 * @ignore
	 */
	var $url;
	
	/**
	 * 最后一次访问类型
	 *
	 * @ignore
	 */
	var $last_req_type;
	
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	var $useragent = 'Sae T OAuth2 v0.1/Xweibo 2.5';
	
	var $http;
	
	var $logType = 'api';	
	
	/**
	 * @ignore
	 */
	var $client_id;
	/**
	 * @ignore
	 */
	var $client_secret;
	/**
	 * @ignore
	 */
	var $access_token;
	/**
	 * @ignore
	 */
	var $refresh_token;
	
	var $_refresh_token_ed = array();
	
	var $is_exit_error = true;
	
	/**
	 * 记录一个php周期中。所出现的request错误次数。
	 * @var unknown_type
	 */
	var $req_error_count = 0;
	
	var $error = array();
	
	/**
	 * 构造函数
	 *
	 * @param @oauth_token
	 * @param @oauth_token_secret
	 * @return
	 */
	function weibo($oauth_token = NULL, $oauth_token_secret = NULL)
	{
		$this->client_id = XWB_APP_KEY;
		$this->client_secret = XWB_APP_SECRET_KEY;
		$this->setConfig();
		$this->http = XWB_plugin::getHttp(false);
	}
	
	/// 指定USER TOKEN
	function setTempToken($oauth_token, $oauth_token_secret){
		$this->access_token = $oauth_token;
		$this->refresh_token = $oauth_token_secret;
	}
	
	/**
	 * 设置
	 */
	function setConfig()
	{	
		/// 用户实例
        $sess = XWB_plugin::getUser();
		$token = $sess->getToken();
		if (!empty($token['oauth_token'])) {
			$this->access_token = isset($token['oauth_token']) ? $token['oauth_token'] : '';
			$this->refresh_token = isset($token['oauth_token_secret']) ? $token['oauth_token_secret'] : '';
        }
	}
	
	/**
	 * (xweibo增加)获取access token和refresh token
	 * 请使用此方法，不要直接访问类属性！
	 * @param bool $oauth1_compact 是否采取同时返回兼容数组模式？
	 * 此时将设置两个oauth_token和oauth_token_secret，对应access_token和refresh_token
	 */
	function getToken($oauth1_compact = true){
		$return = array(
			'access_token' => $this->access_token,
			'refresh_token' => $this->refresh_token,
		);
		if($oauth1_compact){
			$return['oauth_token'] = $this->access_token;
			$return['oauth_token_secret'] = $this->refresh_token;
		}
		return $return;
	}
	
	/**
	 * 手动设置Client信息
	 * - oauth2_convert 
	 *		__code_internal__ 内部code，与外部无关
	 *
	 * - oauth2_convert 
	 *		true（改造完毕）
	 *
	 * @param int $type 类型，1代表内置key，2代表指定key
	 * @param $appkey
	 * @param $appsecret
	 * @return unknown
	 */
	function setClientId($type = 1, $appkey = '', $appsecret = '', $clearToken = false){
		if(1 == $type){
			$this->client_id = WB_AKEY;
			$this->client_secret = WB_SKEY;
		}else{
			$this->client_id = $appkey;
			$this->client_secret = $appsecret;			
		}
		if($clearToken){
			$this->setTempToken(null, null);
		}
	}
	

	/**
	 * 获取client id属性
	 * @param string $name 名称，client_id、client_secret或者其它任意值
	 * @param string|array client_id或者client_secret时会返回字符串，否则将返回此两者信息组合的数组
	 */	
	function getClientId($name = null){
		if('client_id' == $name){
			return $this->client_id;
		}elseif('client_secret' == $name){
			return $this->client_secret;
		}else{
			return array(
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
			);
		}
	}
			
	
	/**
	 * 设置错误提示
	 *
	 * @param string $error
	 * @return mixed ，如果$this->is_exit_error为false，则返回带error和error_code的数组
	 */
	function setError($error)
	{
		$error_ori = $error;
		if(!is_array($error)){
			$error = (array)json_decode($error, true);
		}
		
		//目前未知OAuth 2.0和1.0的错误码关系，故进行简要处理
		/*
		 error: 错误码
		 error_code: 错误的内部编号
		 error_description: 错误的描述信息(不一定有)
		 error_url: 可读的网页URI，带有关于错误的信息，用于为终端用户提供与错误有关的额外信息。(不一定有)
		 */
		if(empty($error)){
			$error = array(
				'error' => 'unknown_error_not_json',
				'error_code' => 1050000,
				'error_description' => $error_ori,
			);
		}elseif(!isset($error['error'])){
			$error = array(
				'error' => 'unknown_error_blank',
				'error_code' => 1050000,
				'error_description' => $error_ori,			
			);
		}
		
		$this->error = $error;
		
		$final_err = 'WEIBO_OAUTH2_ERROR_CODE_'. (isset($error['error_code']) ? $error['error_code'] : 'UNKNOWN') .':'. (isset($error['error']) ? $error['error'] : 'unknown_error_blank');
		if(isset($error['error_description']) && !empty($error['error_description'])){
			$final_err .= '('. $error['error_description']. ')';
		}
		
		//DEBUG 日志
		$req_url = $this->url;
		XWB_plugin::LOG("[WEIBO CLASS]\t[ERROR_OAUTH2]\t#{$this->req_error_count}\t{$final_err}\t{$req_url}\tERROR ARRAY:\r\n".print_r($error, 1));
		//DEBUG END
		
		if (!$this->is_exit_error) {
			return $error;
		}
		
		XWB_plugin::showError('和新浪微博通讯出现错误', false, array('api_error'=>$error));
		
	}
	
	/**
	 * 获取错误提示
	 *
	 * @param $useType string
	 * @return unknown
	 */
	function getError($useType = 'array')
	{
		if ('array' == $useType) {
			return $this->error;
		}
		return json_encode($this->error);
	}


	//数据集(timeline)接口

	/**
	 * 获取最新更新的公共微博消息
	 *
	 * @param $useType string
	 * @return array|string
	 */
	 function getPublicTimeline($useType = true)
	 {
		$url = $this->oauth2_url('statuses/public_timeline');
		$params = array();
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_statuses');
		return $response;
	 }


	/**
	 * 获取当前用户所关注用户的最新微博信息
	 *
	 * @param $count int
	 * @param page int
	 * @param since_id int
	 * @param max_id int
	 * @return array|string
	 */
	 function getHomeTimeline($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/friends_timeline');
		$params = array();
		if ($since_id) {
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$params['max_id'] = $max_id;
		}
		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}	

		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_statuses');
		return $response;
	 }


	/**
	 * 获取当前用户所关注用户的最新微博信息
	 *
	 * @param $count int
	 * @param $page int
	 * @param $since_id int
	 * @param $max_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function getFriendsTimeline($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		return $this->getHomeTimeline($count, $page, $since_id, $max_id, $useType);
	 }


	/**
	 * 获取用户发布的微博信息列表
	 *
	 * @param int|string $id 用户id。（用于uid中）查询优先级最高。如果此参数存在，则覆盖$user_id和$name
	 * @param int|string $user_id 用户user id （用于uid中）查询优先级低于$user_id。如果此参数存在，则覆盖$name
	 * @param string $name （用于screen_name中）用户昵称 查询最低。
	 * @param int|string $since_id 返回比since_id的的微博数据
	 * @parma int|string $max_id 返回不大于max_id的微博数据
	 * @param int $count 获取条数
	 * @param int $page 页码数
	 * @param $useType string
	 * @return array|string
	 */
	 function getUserTimeline($id = null, $user_id = null, $name = null, $since_id = null, $max_id = null, $count = null, $page = null, $useType = true)
	 {

		$url = $this->oauth2_url('statuses/user_timeline');
	 	
		$params = array();		
	 	
		$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		$params['base_app'] = $base_app;
		if ($since_id) {
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$params['max_id'] = $max_id;
		}
		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}
		
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_statuses');
		return $response;
	 }


	 /**
	  * 获取@当前用户的微博列表
	  *
	  * @param $count int
	  * @param page int
	  * @param since_id int
	  * @param max_id int
	  * @param @useType string
	  * @return array|string
	  */
	 function getMentions($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/mentions');

		$params = array();
		if ($since_id) {
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$params['max_id'] = $max_id;
		}
		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}
		
		$response = $this->oAuthRequest($url, 'get', $params);
		
		$this->oauth2_compact_convert($response, 'common_only_statuses');
		return $response;
	 }


	/**
	 * 获取当前用户发送及收到的评论列表
	 *
	 * @param $count int
	 * @param $page int
	 * @param $since_id int
	 * @param $max_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function getCommentsTimeline($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		$url = $this->oauth2_url('comments/timeline');

		$params = array();
		if ($since_id) {
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$params['max_id'] = $max_id;
		}
		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}

		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'comments'));
		return $response;
	 }


	/**
	 * 获取当前用户发出的评论
	 *
	 * @param $count int
	 * @param $page int
	 * @param $since_id int
	 * @param $max_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function getCommentsByMe($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		$url = $this->oauth2_url('comments/by_me');

		$params = array();

		if ($since_id) {
			$params['since_id'] = $since_id;
		}
		if ($max_id) {
			$params['max_id'] = $max_id;
		}
		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}

		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'comments'));
		return $response;
	 }


	/**
	 * 获取当前用户收到的评论列表
	 *
	 * @param $list
	 * @param $count
	 * @param $page
	 * @param $since_id
	 * @param $max_id
	 * @return array
	 */
	function getCommentsToMe($list = null, $count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	{
		if (empty($list)) {
			$url = $this->oauth2_url('comments/to_me');

			$params = array();
			if ($since_id) {
				$params['since_id'] = $since_id;
			}
			if ($max_id) {
				$params['max_id'] = $max_id;
			}
			if ($count) {
				$params['count'] = $count;
			}
			if ($page) {
				$params['page'] = $page;
			}
		
			$response = $this->oAuthRequest($url, 'get', $params);
			$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'comments'));
			
		} else {
			$response = $list;
		}
		
		return $response;
	}


	/**
	 * 获取指定微博的评论列表
	 *
	 * @param $id int
	 * @param $count int
	 * @param $page int
	 * @param $useType string
	 * @return array|string
	 */
	 function getComments($id, $count = null, $page = null, $useType = true)
	 {
		$url = $this->oauth2_url('comments/show');

		$params = array();
		$params['id'] = $id;

		if ($count) {
			$params['count'] = $count;
		}
		if ($page) {
			$params['page'] = $page;
		}

		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'comments'));
		return $response;
	 }


	/**
	 * 批量获取一组微博的评论数及转发数
	 *
	 * @param $ids string
	 * @param $useType string
	 * @return array|string
	 */
	 function getCounts($ids, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/counts');
		
		$params = array();
		if (is_array($ids)) {
			$params['ids'] = implode(',', $ids);
		} else {
			$params['ids'] = $ids;
		}
		
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'getCounts');
		return $response;
	 }


	/**
	 * 获取当前用户未读消息数
	 *
	 * @param int|string $with_new_status 默认为0。1表示结果包含是否有新微博，0表示结果不包含是否有新微博
	 * @param int|string $since_id 微博id，返回此条id之后，是否有新微博产生，有返回1，没有返回0
	 * @param $useType string
	 * @return array|string
	 */
	 function getUnread($with_new_status = null, $since_id = null, $useType = true)
	 {
	 	
	 	if(!OAUTH2_ENABLE_UNREAD){
			$response = array();
			$this->oauth2_compact_convert($response, 'getUnread_def');
			return $response;
	 	}
	 	
		$url = 'https://rm.api.weibo.com/2/remind/unread_count.json';
		
		if($uid == 0){
			$uid = USER::uid();
		}
		$params['uid'] = $uid;
		
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'getUnread');
		return $response;
	 }


	 //访问接口

	/**
	 * 根据ID获取单条微博信息内容
	 *
	 * @param $id int
	 * @param $user_id int
	 * @param $name string
	 * @param $useType string
	 * @return array|string
	 */
	 function getStatuseShow($id, $useType = true)
	 {
	 	$url = $this->oauth2_url('statuses/show');
		
		$params = array('id'=>$id);
		$response = $this->oAuthRequest($url, 'get', $params);

		return $response;
	 }


	/**
	 * 发布一条微博信息
	 *
	 * @param $status string
	 * @param $useType string
	 * @return array|string
	 */
	 function update($status, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/update');
		$params = array();
		//$params['status'] = urlencode($status);
		$params['status'] = $status;
		
		$response = $this->oAuthRequest($url, 'post', $params);
		
		return $response;
	 }


	 /**
	  * 上传图片并发布一条微博信息
	  *
	  * @param $status string
	  * @param $pid string
	  * @param $lat string
	  * @param $long string
	  * @param $useType string
	  * @return array|string
	  */
	 function upload($status, $pic, $lat = null, $long = null, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/upload');

		$params = array();
		//$params['status'] = urlencode($status);
		$params['status'] = $status;
		$params['pic'] = '@'.$pic;
		
		if ($lat) {
			$params['lat'] = $lat;
		}
		if ($long) {
			$params['long'] = $long;
		}
		$response = $this->oAuthRequest($url, 'post', $params, true);
		return $response;
	 }


	/**
	 * 删除微博
	 *
	 * @param $id int
	 * @param $useType string
	 * @return array|string
	 */
	 function destroy($id, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/destroy');

		$params = array('id'=>$id);

		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	/**
	 * 转发一条微博信息（可加评论）
	 *
	 * @param $id int
	 * @param $status string
	 * @param $useType string
	 * @return array|string
	 */
	 function repost($id, $status = null, $useType = true)
	 {
		$url = $this->oauth2_url('statuses/repost');

		$params = array();
		$params['id'] = $id;
		if ($status) {
			//$params['status'] = urlencode($status);
			$params['status'] = $status;
		}
		//$params['is_comment'] = $is_comment;

		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	/**
	 * 对一条微博信息进行评论
	 *
	 * @param $id int
	 * @param $comment string
	 * @param $useType string
	 * @return array|string
	 */
	 function comment($id, $comment, $cid = null, $useType = true)
	 {
	 	if($cid > 0){
	 		return $this->reply($id, $cid, $comment);
	 	}
	 	
		$url = $this->oauth2_url('comments/create');
		
		$params = array();
		$params['id'] = $id;
		$params['comment'] = $comment;
		//$params['comment_ori'] = $comment_ori;
		
		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	/**
	 * 删除当前用户的微博评论信息
	 *
	 * @param $id int
	 * @param $useType string
	 * @return array|string
	 */
	 function comment_destroy($id, $useType = true)
	 {
		$url = $this->oauth2_url('comments/destroy');

		$params = array('cid'=>$id);
		
		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	 /**
	  * 回复微博评论信息
	  *
	  * @param $id int
	  * @param $cid int
	  * @param $comment string
	  * @param $useType string
	  * @return array|string
	  */
	 function reply($id, $cid, $comment, $useType = true)
	 {
		$url = $this->oauth2_url('comments/reply');
		
		$params = array();
		$params['id'] = $id;
		$params['comment'] = $comment;
		$params['cid'] = $cid;
		
		$response = $this->oAuthRequest($url, 'post', $params);
		
		return $response;
	 }



	 //用户接口

	/**
	 * 根据用户ID获取用户资料（授权用户）
	 *
	 * @param $id int|string
	 * @param $user_id int
	 * @param $name string
	 * @param $useType string
	 * @return array|string
	 */
	function getUserShow($id = null, $user_id = null, $name = null, $useType = true)
	{
		$url = $this->oauth2_url('users/show');

		$params = array();
		
		$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		$response = $this->oAuthRequest($url, 'get', $params);
		return $response;
	}


	/**
	 * 获取当前用户关注对象列表及最新一条微博信息
	 *
	 * @param $id int|string
	 * @parmas $user_id int
	 * @param $name string
	 * @param $cursor
	 * @param $count
	 * @param $useType string
	 * @return array|string
	 */
	 function getFriends($id = null, $user_id = null, $name = null, $cursor = null, $count = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/friends');

		$params = array();		

	 	$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		if ($cursor) {
			$params['cursor'] = $cursor;
		}
		if ($count) {
			$params['count'] = $count;
		}
		
		$params['trim_status'] = 0;  //xweibo适应性改动
		
		$response = $this->oAuthRequest($url, 'get', $params);
		return $response;
	 }


	/**
	 * 获取当前用户粉丝列表及最新一条微博信息
	 *
	 * @param $id int|string
	 * @param $user_id int
	 * @param $name string
	 * @param $cursor string
	 * @param $count int
	 * @param $useType string
	 * @return array|string
	 */
	 function getFollowers($id = null, $user_id = null, $name = null, $cursor = null, $count = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/followers');
		
		$params = array();		

	 	$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		if ($cursor) {
			$params['cursor'] = $cursor;
		}
		if ($count) {
			$params['count'] = $count;
		}
		$params['trim_status'] = 0;  //xweibo适应性改动

		$response = $this->oAuthRequest($url, 'get', $params);
		
		return $response;
	 }



	 //私信接口

	/**
	 * 获取当前用户最新私信列表
	 *
	 * @param $count int
	 * @param $page int
	 * @param $since_id int
	 * @param $max_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function getDirectMessages($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		return $this->oauth2_method_not_exist('getDirectMessages');
	 }


	/**
	 * 获取当前用户发送的最新私信列表
	 *
	 * @param $count int
	 * @param $page int
	 * @param $since_id int
	 * @param $max_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function getSentDirectMessages($count = null, $page = null, $since_id = null, $max_id = null, $useType = true)
	 {
		return $this->oauth2_method_not_exist('getSentDirectMessages');
	 }


	/**
	 * 发送一条私信
	 *
	 * @param $id int|string
	 * @param $text string
	 * @param $name string
	 * @param $user_id int
	 * @param $useType string
	 * @return array|string
	 */
	 function sendDirectMessage($id, $text, $name = null, $user_id = null, $useType = true)
	 {
		return $this->oauth2_method_not_exist('sendDirectMessage');
	 }


	/**
	 * 删除一条私信
	 *
	 * @param $id int
	 * @param $useType string
	 * @return array|string
	 */
	 function deleteDirectMessage($id, $useType = true)
	 {
		return $this->oauth2_method_not_exist('deleteDirectMessage');
	 }



	 //关注接口

	/**
	 * 关注某用户
	 *
	 * @param $id int|string
	 * @param $user_id int
	 * @param $name string
	 * @param $follow string
	 * @param $useType string
	 * @return array|string
	 */
	 function createFriendship($id = null, $user_id = null, $name = null, $follow = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/create');
		
		$params = array();
		
	 	$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		if ($follow) {
			$params['follow'] = $follow;
		}

		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	/**
	 * 取消关注
	 *
	 * @param $id int|string
	 * @param $user_id int
	 * @param $name string
	 * @param $useType string
	 * @return array|string
	 */
	 function deleteFriendship($id = null, $user_id = null, $name = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/destroy');

		$params = array();
		$final_user = '';
		if (!empty($user_id)) {
			$final_user = $user_id;
		}elseif (!empty($name)) {
			$final_user = $name;
		}
		if(is_numeric($final_user)){
			$params['uid'] = $final_user;
		}elseif(!empty($final_user)){
			$params['screen_name'] = $final_user;
		}

		$response = $this->oAuthRequest($url, 'post', $params);

		return $response;
	 }


	/**
	 * 是否关注某用户
	 *
	 * @param $user_a int
	 * @param $user_b int
	 * @param $useType string
	 * @return array|string
	 */
	 function existsFriendship($user_a, $user_b, $useType = true)
	 {
		$response = $this->getFriendship($user_b, null, $user_a, null);
		$this->oauth2_compact_convert($response, 'existsFriendship');
		return $response;
	 }


	/**
	 * 获取两个用户关系的详细情况
	 *
	 * @param $target_id int
	 * @param $target_screen_name string
	 * @param $source_id int
	 * @param $source_screen_name string
	 * @param $useType string
	 * @return array|string
	 */
	 function getFriendship($target_id = null, $target_screen_name = null, $source_id = null, $source_screen_name = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/show');

		$params = array();
		if ($target_id) {
			$params['target_id'] = $target_id;
		}
		if ($target_screen_name) {
			$params['target_screen_name'] = $target_screen_name;
		}
		if ($source_id) {
			$params['source_id'] = $source_id;
		}
		if ($source_screen_name) {
			$params['source_screen_name'] = $source_screen_name;
		}

		$response = $this->oAuthRequest($url, 'get', $params);

		return $response;
	 }



	 //Social Graph接口

	/**
	 * 获取用户关注对象uid列表
	 *
	 * @param $id int
	 * @param $user_id int
	 * @param $name string
	 * @param $cursor string
	 * @param $count int
	 * @param $useType string
	 * @return array|string
	 */
	 function getFriendIds($id = null, $user_id = null, $name = null, $cursor = null, $count = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/friends/ids');

		$params = array();

	 	$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}		
		
		if ($cursor) {
			$params['cursor'] = $cursor;
		}
		if ($count) {
			$params['count'] = $count;
		}

		$response = $this->oAuthRequest($url, 'get', $params);

		return $response;
	 }


	/**
	 * 获取用户粉丝对象uid列表
	 *
	 * @param $id int
	 * @param $user_id int
	 * @param $name string
	 * @param $useType string
	 * @return array|string
	 */
	 function getFollowerIds($id = null, $user_id = null, $name = null, $cursor = null, $count = null, $useType = true)
	 {
		$url = $this->oauth2_url('friendships/followers/ids');

		$params = array();
		
	 	$param_uid = !empty($id) ? $id : (!empty($user_id) ? $user_id : null);
		$param_screen_name = !empty($name) ? $name : null;
		if(!empty($param_uid)){
			$params['uid'] = $param_uid;
		}elseif(!empty($param_screen_name)){
			$params['screen_name'] = $param_screen_name;
		}
		
		if ($cursor) {
			$params['cursor'] = $cursor;
		}
		if ($count) {
			$params['count'] = $count;
		}

		$response = $this->oAuthRequest($url, 'get', $params);

		return $response;
	 }



	 //帐号接口

	/**
	 * 验证当前用户身份是否合法
	 *
	 * @param $useType string
	 * @return array|string
	 */
	 function verifyCredentials($uid = 0)
	 {
	 	//尝试使用session的uid
	 	if($uid <= 0){
	 		$uid = XWB_plugin::getBindInfo('sina_uid');
	 	}
	 	
		return $this->getUserShow(null, $uid, null, true);
	 }
	 
	 /**
	  * OAuth授权之后，获取授权用户的UID
	  * @see http://open.weibo.com/wiki/2/account/get_uid
	  * @return array
	  */
	 function get_uid(){
		$url = $this->oauth2_url('account/get_uid');
		$params = array();
		$response = $this->oAuthRequest($url, 'get', $params);
		return $response;
	 } 

	/**
	 * 获取当前用户API访问频率限制
	 *
	 * @param $useType string
	 * @return array|string
	 */
	 function getRateLimitStatus($useType = true)
	 {
		$url = $this->oauth2_url('account/rate_limit_status');
		
		$params = array();
		$response = $this->oAuthRequest($url, 'get', $params);

		return $response;
	 }


	/**
	 * 当前用户退出登录
	 *
	 * @param $useType string
	 * @return array|string
	 */
	 function endSession($useType = true)
	 {
		$url = $this->oauth2_url('account/end_session');
		$response = $this->oAuthRequest($url, 'get');
		return $response;
	 }


	/**
	 * 更改头像
	 *
	 * @param $image string
	 * @param $useType string
	 * @return array|string
	 */
	 function updateProfileImage($image, $useType = true)
	 {
		return $this->oauth2_method_not_exist('updateProfileImage');
	 }


	/**
	 * 更改资料
	 *
	 * @param $name string
	 * @param $gender string
	 * @param $province int
	 * @param $city int
	 * @param $description string
	 * @param $params
	 * @param $useType string
	 * @return array|string
	 */
	 function updateProfile($params, $useType = true)
	 {
		return $this->oauth2_method_not_exist('updateProfile');
	 }


	/**
	 * 注册新浪微博帐号
	 *
	 * @param $params array
	 * @return array|string
	 */
	 function register($params, $useType = true)
	 {
		return $this->oauth2_method_not_exist('register');
	 }



	 //收藏接口

	/**
	 * 获取当前用户的收藏列表
	 *
	 * @param $page int
	 * @param $useType string
	 * @return array|string
	 */
	 function getFavorites($page = null, $useType = true)
	 {
		$url = $this->oauth2_url('favorites');
		$params = array();
		if ($page) {
			$params['page'] = $page;
		}
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'getFavorites');
		return $response;
	 }


	/**
	 * 添加收藏
	 *
	 * @param $id int
	 * @param $useType string
	 * @return array|string
	 */
	 function createFavorite($id, $useType = true)
	 {
		$url = $this->oauth2_url('favorites/create');

		$params = array();
		$params['id'] = $id;
		$response = $this->oAuthRequest($url, 'post', $params);
		$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'status'));
		return $response;
	 }


	/**
	 * 删除当前用户收藏的微博信息
	 *
	 * @param $id int
	 * @param $useType string
	 * @return array|string
	 */
	 function deleteFavorite($id, $useType = true)
	 {
		$url = $this->oauth2_url('favorites/destroy');
		$params = array('id' => $id);
		$response = $this->oAuthRequest($url, 'post', $params);
		$this->oauth2_compact_convert($response, 'common_only_body', array('body'=>'status'));
		return $response;
	 }


	 //oauth

    /**
     * Set API URLS
     */
    /**
     * @ignore
     */
 	function accessTokenURL()  { return 'https://api.weibo.com/oauth2/access_token'; }
    /**
     * @ignore
     */
    function authenticateURL() { return null; }
    /**
     * @ignore
     */
 	function authorizeURL()    { return 'https://api.weibo.com/oauth2/authorize'; }
    /**
     * @ignore
     */
    function requestTokenURL() { return null; }

    /**
     * Get a request_token from Weibo
     *
     * @return array a key/value array containing oauth_token and oauth_token_secret
     */
    function getRequestToken($oauth_callback = NULL, $useType = 'string')
	{
        return array('oauth_token' => 'REQUEST_TOKEN', 'oauth_token_secret' => 'REQUEST_TOKEN_SECRET');
    }

    /**
     * Get the authorize URL
     * @see http://open.weibo.com/wiki/Oauth2/authorize
     * 
	 * - oauth2_status: 
	 *		__code_internal__ 内部code，与外部无关
	 *		param_change 参数改变
	 *		return_change 返回改变 
	 * - oauth2_convert:
	 * 		true（改造完毕）
	 * true 
	 * @param array|string $param 参数集合。已有参数：
	 * response_type 支持的值包括 code 和token 默认值为code
	 * state 用于保持请求和回调的状态。在回调时,会在Query Parameter中回传该参数。xweibo会将其打包为一个query值
	 * display 授权页面类型 可选范围: 
	 *  - default		默认授权页面		
	 *  - mobile		支持html5的手机		
	 *  - popup			弹窗授权页		
	 *  - wap1.2		wap1.2页面		
	 *  - wap2.0		wap2.0页面		
	 *  - js			js-sdk 专用 授权页面是弹窗，返回结果为js-sdk回掉函数		
	 *  - apponweibo	站内应用专用,站内应用不传display参数,并且response_type为token时,默认使用改display.授权后不会返回access_token，只是输出js刷新站内应用父框架
	 * forcelogin 强制登录？
	 * @param bool $sign_in_with_Weibo 参数已失效
	 * @param string $url 回调地址
	 * @param string $lang 语言
     * @return string
     */
    function getAuthorizeURL($param, $sign_in_with_Weibo = TRUE , $url = '')
	{
		if(!is_array($param)){
			$param_new = array();
			$param_new = (array)parse_str($param, $param_new);
			$param = $param_new;
		}
		
		if(!isset($param['response_type'])){
			$param['response_type'] = 'code';
		}
	
		if(!empty($param['state']) && is_array($param['state'])){
			$param['state'] = base64_encode(xwbOAuthV2_tool_http::build_http_query($param['state']));		
		}
		
		if(!isset($param['display'])){
			$param['display'] = null;
		}
		
		$param['client_id'] = $this->client_id;
		$param['redirect_uri'] = $url;
		
		if(isset($param['forcelogin']) && true == $param['forcelogin']){
			$param['forcelogin'] = 'true';
		}
		
		$url = xwbOAuthV2_tool_http::get_url($this->authorizeURL(), $param);

		$url .= '&lang=zh-Hans';
		
		return $url;
		
    }

	/**
	 * Get the authorize Token
	 *
	 * @param string $token
	 * @param string $user
	 * @param string $password
	 * @param string $useType
	 *
	 * @return array
	 */
	function getAuthorizeToken($token, $user, $password, $useType = 'json')
	{
		return $this->oauth2_method_not_exist('getAuthorizeToken');
	}

    /**
     * 获取Access token
     * @see http://open.weibo.com/wiki/OAuth2/access_token
	 * - oauth2_convert 
	 *		param_change 参数改变
	 *		return_change 返回改变 
	 *
	 * - oauth2_convert 
	 *		true（改造完毕）
	 *
	 * @param string $code （参数改变）此参数当且仅当$param['grant_type'] = 'authorization_code'时，等同于“调用authorize获得的code值”
	 * @param array $param （参数改变）参数集合。必填key有：
	 *  - grant_type 请求的类型,可以为:code / authorization_code, password, token / refresh_token
	 * 可选key有：
	 *  - 当grant_type为code / authorization_code时，须有： array('code'=>..., 'redirect_uri'=>...)
	 *  - 当grant_type为password时，须有： array('username'=>..., 'password'=>...)
	 *  - 当grant_type为token / refresh_token时，须有： array('refresh_token'=>...)
     * @return array array("oauth_token" => the access token,
     *                "oauth_token_secret" => the access secret)
     */
    function getAccessToken($code = FALSE, $param = array(), $useType = 'string')
	{
		if(!isset($param['grant_type'])){
			$param['grant_type'] = 'authorization_code';
		}
		
		if(!empty($code) && ($param['grant_type'] == 'authorization_code' || $param['grant_type'] == 'code')){
			$param['code'] = $code;
		}
		
		switch($param['grant_type']){
			case 'token':			
			case 'refresh_token':
				$param['grant_type'] = 'refresh_token';
				break;
			case 'code':				
			case 'authorization_code':
				$param['grant_type'] = 'authorization_code';					
				break;
			case 'password':
				$param['grant_type'] = 'password';		
				break;
			default:
				return $this->oauth2_method_not_exist('getAccessToken_'. $param['grant_type']);
		}
		
		$param['client_id'] = $this->client_id;
		$param['client_secret'] = $this->client_secret;
		$token = $this->oAuthRequest($this->accessTokenURL(), 'POST', $param);
		if(isset($token['error'])){
			return $token;
		}else{
			$this->setTempToken($token['access_token'], isset($token['refresh_token']) ? $token['refresh_token'] : '');
			if(isset($token['uid'])){
				$token['user_id']  = $token['uid'];
			}
			return $this->getToken() + $token;
		}
    }
    
	/**
	 * 搜索微博用户
	 * 此接口无权限使用使用users/show接口替换
	 * 
	 * @param $params array
	 * @param $useType bool
	 * @return array|string
	 */
	function searchUser($params, $useType = true)
	{
		$ori_exit_error = $this->is_exit_error;
		$this->is_exit_error = false;
		$response  = $this->getUserShow(null, null, $params['q'], true);
		$this->is_exit_error = $ori_exit_error;
		if(isset($response['error'])){
			return array();
		}
		return array($response);
	}


	/**
	 * 搜索微博文章
	 *
	 * @param $q string
	 * @param $page int
	 * @param $rpp string
	 * @param $callback string
	 * @param $geocode string
	 * @param $useType string
	 * @return array|string
	 */
	function search($q = null, $page = null, $rpp = null, $callback = null, $geocode = null, $useType = true)
	{
		$params = array(
			'q' => $q,
			'page' => $page,
			'rpp' => $rpp,
			'callback' => $callback,
			'geocode' => $geocode,
		);
		return $this->searchStatuse($params, $useType);
	}

	/**
	 * 搜索微博文章
	 *
	 * @param $q string
	 * @param $filter_ori stirng
	 * @param $filter_pic string
	 * @param $province int
	 * @param $city int
	 * @param $starttime string
	 * @param $endtime string
	 * @param $page int
	 * @param $count int
	 * @param $callback string
	 * @param $useType string
	 * @return array|string
	 */
	function searchStatuse($params, $useType = true)
	{
		$url = $this->oauth2_url('search/statuses');
		if(!isset($params['needcount'])){
			$params['needcount'] = 'true';
		}		
		$response = $this->oAuthRequest($url, 'get', $params);
		$this->oauth2_compact_convert($response, 'searchStatuse', $params);
		return $response;		
		
	}


	/**
	 * 获取省份及城市编码ID与文字对应
	 * @see http://open.weibo.com/wiki/%E7%9C%81%E4%BB%BD%E5%9F%8E%E5%B8%82%E7%BC%96%E7%A0%81%E8%A1%A8
	 * 
	 * oauth 2.0存在问题：
	 * 			警告：此接口已不存在（被移除）。
	 * 			进行了旧数据兼容：使用OAuth 1.0数据
	 * 
	 * - oauth2_convert 
	 *		not_exist 已不存在，返回虚值
	 *
	 * - oauth2_convert 
	 *		true（改造完毕）
	 *
	 * @return array
	 */
	function getProvinces($useType = true)
	{
		$response = array();
		$this->oauth2_compact_convert($response, 'getProvinces');
		return $response;
	}
	
	/**
	 * 通过微博（评论、私信）ID获取其MID
	 * @see http://open.weibo.com/wiki/2/statuses/querymid
	 */
	function querymid($id, $type = 1, $is_batch = 0, $useType = true){
		$url = $this->oauth2_url('statuses/querymid');
		
		$params = array();
		$params['id'] = $id;
		$params['type'] = $type;
		$params['is_batch'] = $is_batch;
		$response = $this->oAuthRequest($url, 'get', $params);
		return $response;
	}
	

	/**
	 * 将respond给log下来，以作为OAUTH DEBUG证据
	 * 需要定义XWB_DEV_LOG_ALL_RESPOND并且设置为true，才记录
	 * 
	 * @param string $url 完整调用OATUH的URL
	 * @param string $method 调用方法
	 * @param integer $respondCode 返回状态代号
	 * @param mixed $respondResult 返回结果
	 * @param mixed $extraMsg 额外需要记录的内容
	 */
	function logRespond( $url, $method, $respondCode, $respondResult = null , $extraMsg = array() ){
		//调用这个类的当前页面的url
		$callURL = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '__UNKNOWN__';
		
		//oauth url简略提取，以用作统计
		$oauth_short_url = str_replace( XWB_API_URL, '', ( strpos($url, '?') !== false ? substr( $url, 0, strpos($url, '?') ) : $url) );
		
		if( $respondCode == 0 ){
			//timeout
			$respondResult = '__CONNECTION MAYBE TIME OUT ?__';
		}elseif ( $respondCode == -1 ){
			$respondResult = '__CAN NOT CONNECT TO API SERVER; OR CREATE A WRONG OAUTH REQUEST URL. PLEASE INSPECT THE LOG__';
		}
		
		if( empty($respondResult) ){
			$respondResult = '__NO RESPOND RESULT__';
		}
		
		//extraMsg数组中，triggered_error是用于存放fsockopenHttp的trigger_error信息
		if( isset($extraMsg['triggered_error']) &&  empty($extraMsg['triggered_error']) ){
			unset($extraMsg['triggered_error']);
		}
		if(isset($extraMsg['key_string'])){
			$extraMsg['key_string'] = strtr($extraMsg['key_string'], array(XWB_APP_SECRET_KEY => '%APP_SKEY%'));
		}
		
		$time_process = isset($extraMsg['time_process']) ? round((float)$extraMsg['time_process'], 6) : 0;
		unset($extraMsg['time_process']);
		
		$error_count_log = '';
		if( $this->req_error_count > 0 ){
			$error_count_log = '[REQUEST ERROR COUNT IN THIS PHP LIFETIME] '. $this->req_error_count."\r\n";
		}
		
		$msg = $method. "\t".
				$respondCode. "\t".
				$time_process. " sec.\t".
				$oauth_short_url. "\t".
				"\r\n". str_repeat('-', 5). '[EXTRA MESSAGE START]'. str_repeat('-', 5)."\r\n".
				$error_count_log.
				'[CALL URL]'. $callURL. "\r\n".
				'[OAUTH REQUEST URL]'. $url. "\r\n".
				'[RESPOND RESULT]'. "\r\n". print_r($respondResult, 1). "\r\n\r\n".
				'[EXTRA LOG MESSAGE]'. "\r\n". print_r($extraMsg, 1). "\r\n".
				str_repeat('-', 5). '[EXTRA MESSAGE END]'. str_repeat('-', 5)."\r\n\r\n\r\n"
				;
		
		$logFile = XWB_P_DATA.'/oauth_respond_log_'. date("Y-m-d_H"). '.txt.php';
		XWB_plugin::LOG($msg, $logFile);
		
		return 1;
		
	}
	
	/**
	 * 当发现用户取消授权后，对其进行解绑操作
	 * 属于本插件特殊用途的函数，仅用于方法oAuthRequest中
	 */
	function _delBindCheck( $errmsg = '' ){
		if( XWB_S_UID <= 0 ){
			return false;
		}
		
		XWB_plugin::delBindUser(XWB_S_UID); //远程API
		$sess = XWB_plugin::getUser();
		$sess->clearToken();
		dsetcookie($this->_getBindCookiesName(XWB_S_UID) , -1, 604800);
		return true;
	}
	
 	/**
	 * 获取Bind cookies名称
	 * 属于本插件特殊用途的函数，仅用于方法_delBindCheck中
	 * @param integer $uid
	 * @return string
	 */
	function _getBindCookiesName($uid){
		return 'sina_bind_'. $uid;
	}
	

	 /**
	  * 获取一个oauth2 url
	  * @param unknown_type $url 两边请不要有/，否则会报：Request Api not found!
	  * @param unknown_type $format
	  * @return string
	  */
	 function oauth2_url($url, $format = 'json'){
	 	return OAUTH2_WEIBO_API_URL. $url. '.'. $format;
	 }
	 	

    /**
     * Format and sign an OAuth / API request
     * 目前仅支持get和post方法
     *
     * @return array
     */
    function oAuthRequest($url, $method, $parameters , $multi = false)
	{
		$this->last_req_type = 'oAuthRequest';
		$this->http->setHeader('Authorization', 'OAuth2 '. $this->access_token);
		$result = $this->http($url, $method, $parameters, $multi);
		if($this->http_code != 200){
			//$this->_delBindCheck( isset($result['error']) ? (string)$result['error'] : (string)$result );  //@todo
			if(0 == $this->http_code){
				$result = array("error_code" => "50000", "error" => "timeout" );
				return $this->setError($result);
			}
			
			//refresh token重试
			/*
			if(strpos($result, 'expired_token') !== false){
				$_refresh = $this->_auto_refreshToken();		//@todo	
				if($_refresh){
					return $this->oAuthRequest($url, $method, $parameters, $useType, $multi);
				}
			}
			*/
			return $this->setError($result);
		}
		
		return xwbOAuthV2_tool_http::json_decode($result);
		
    }
 	
    /**
     * Make an HTTP request
     * @param string $url 完整的URL
     * @param string $method 方法，大写 
     * @param array $parameters 参数。$method为GET时，附加到URL后；其余时候，作为body部分提交。
     * @param bool $multi 
     * @return 原始数据
     */
	function http($url, $method, $parameters = array(), $multi = false) {
		
		$method = strtoupper($method);
		$time_start = microtime ();
		$this->http->setHeader('API-RemoteIP', (string)XWB_plugin::getIP());
		$this->http->setHeader('User-Agent', $this->useragent);
		
        switch ($method) {
        	case 'GET':
				$this->http->setUrl(xwbOAuthV2_tool_http::get_url($url, $parameters));
				$result = $this->http->request('get', true);
				break;

			case 'DELETE':
			default:
				$this->http->setUrl($url);
				$this->http->setData(xwbOAuthV2_tool_http::to_postdata($parameters, $multi));
				if($multi){
					$this->http->setHeader('Content-Type', "multipart/form-data; boundary=" . $GLOBALS['__CLASS_STATIC_xwbOAuthV2_tool_http']['boundary']);
				}
				$result = $this->http->request($method == 'DELETE' ? 'delete' : 'post', true);
				break;
      	 }
		$time_end = microtime ();
		$time_process = array_sum ( explode ( " ", $time_end ) ) - array_sum ( explode ( " ", $time_start ) );
		
		$this->http_code = $code = $this->http->getState();
		$this->url = $http_url = $this->http->getUrl();
		
		if( 200 != $code ){
			$this->req_error_count++;
		}
		
		if( defined('XWB_DEV_LOG_ALL_RESPOND') && XWB_DEV_LOG_ALL_RESPOND == true ){
			$this->logRespond ( $this->url,
							$method,
							( int ) $code,
							$result,
							array ('param' => $parameters,
									'time_process' => $time_process,
									'triggered_error' => $this->http->get_triggered_error (),
									'access_token' => empty($this->access_token) ? '[X]' : (substr($this->access_token, 0, 5). '......'),
									)
			);
		}
		
		return $result;
		
	}
	

	 /**
	  * 对OAuth 2.0数据格式进行兼容到OAuth 1.0数据格式的转换
	  * 如果存在错误，且_force_pass_data为false，则不转换
	  * @param array &$data
	  * @param string $name
	  * @param array $param 参数。其中本方法存在的特殊参数有：
	  * bool _force_pass_data 只要存在此参数并且不为false，就会强制将整个$data传入到转换类中（不管是否存在错误）
	  * @return bool
	  */
	 function oauth2_compact_convert(&$data, $name, $param = array()){
	 	//_force_pass_data为true时，将整个data体传入
	 	if(isset($param['_force_pass_data']) && $param['_force_pass_data'] != false){
	 		$classname = 'conv_2/conv_2_'. $name;
	 		$o = APP::O($classname);
	 		$data = $o->convert($data, $param);
	 		return true;
	 	}
	 	
	 	if(isset($data['error'])){
	 		return false;
	 	}
	 	
	 	$classname = 'conv_2/conv_2_'. $name;
	 	$o = APP::O($classname);
	 	$data = $o->convert($data, $param);
	 	return true;
	 }
	 
	 /**
	  * 调用v2接口不存在的方法时，所采取的方法
	  * @param string $method_name
	  * @return mixed
	  */
	 function oauth2_method_not_exist($method_name = null){
	 	$this->req_error_count++;	 	
	 	$this->http_code = -1;
		$this->url = null;
		
		if( defined('XWB_DEV_LOG_ALL_RESPOND') && XWB_DEV_LOG_ALL_RESPOND == true ){
			$this->logRespond ( $this->url,
							'NOT_EXIST',
							( int )$this->http_code,
							'__['. $method_name. ']NO SUCH METHOD IN OAUTH 2.0__',
							array ('method' => $method_name,
									'access_token' => empty($this->access_token) ? '[X]' : (substr($this->access_token, 0, 5). '......'),
									)
			);
		}
		
		return $this->setError(array(
				'error' => 'method_not_exist_in_v2',
				'error_code' => 599999,
				'error_description' => 'NO SUCH METHOD IN OAUTH 2.0',
			));
	 }
	
	
}



class xwbOAuthV2_tool_http{
	
    /**
     * builds the data one would send in a POST request
     */
    function to_postdata( $parameters, $multi = false ) {
    	if( $multi ){
    		return xwbOAuthV2_tool_http::build_http_query_multi($parameters);
   		}else{
        	return xwbOAuthV2_tool_http::build_http_query($parameters);
    	}
    }
	
	
	/**
	 * @ignore
	 */
	function build_http_query_multi($params) {
		if (!$params) return '';

		uksort($params, 'strcmp');

		$pairs = array();

		$GLOBALS['__CLASS_STATIC_xwbOAuthV2_tool_http']['boundary'] = $boundary = uniqid('------------------');
		$MPboundary = '--'.$boundary;
		$endMPboundary = $MPboundary. '--';
		$multipartbody = '';

		foreach ($params as $parameter => $value) {

			if( in_array($parameter, array('pic', 'image')) && $value{0} == '@' ) {
				$url = ltrim( $value, '@' );
				$content = file_get_contents( $url );
				$array = explode( '?', basename( $url ) );
				$filename = $array[0];

				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"'. "\r\n";
				$multipartbody .= "Content-Type: image/unknown\r\n\r\n";
				$multipartbody .= $content. "\r\n";
			} else {
				$multipartbody .= $MPboundary . "\r\n";
				$multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
				$multipartbody .= $value."\r\n";
			}

		}
		
		$multipartbody .= $endMPboundary;
		return $multipartbody;
	}
	
    function build_http_query($params) {
        if (!$params){
        	return '';
        }elseif(!is_array($params)){
        	return $params;
        }
        
        //value无数组时，采取内置函数进行编码
        if(!xwbOAuthV2_tool_http::_check_value_has_arr($params)){
        	return http_build_query($params);
        }

        // Urlencode both keys and values
        $keys = xwbOAuthV2_tool_http::urlencode_rfc3986(array_keys($params));
        $values = xwbOAuthV2_tool_http::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        //OAuth 2.0无排序要求，忽略
        //uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                //OAuth 2.0无排序要求，忽略。
                //natsort($value);
                foreach ($value as $duplicate_value) {
                    $pairs[] = $parameter . '=' . $duplicate_value;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }
    
    function _check_value_has_arr($params){
    	if(!is_array($params) || empty($params)){
    		return false;
    	}
    	$has_arr = false;
    	foreach($params as $val){
    		if(is_array($val)){
    			$has_arr = true;
    			break;
    		}
    	}
    	return $has_arr;
    }
    
    function urlencode_rfc3986($input) {
        if (is_array($input)) {
            return array_map(array('xwbOAuthV2_tool_http', 'urlencode_rfc3986'), $input);
        } else if (is_scalar($input)) {
            return str_replace(
                '+',
                ' ',
                str_replace('%7E', '~', rawurlencode($input))
            );
        } else {
            return '';
        }
    }
	
	
	/**
	 * 获取一条URL指令
	 * @param string $url
	 * @param array|string $paramter
	 */
	function get_url($url, $paramter = ''){
		if(empty($paramter)){
			return $url;
		}
		if(is_array($paramter)){
			$paramter = xwbOAuthV2_tool_http::build_http_query($paramter);
		}
		
		if(strpos($url, '?') === false){
			return $url. '?'. $paramter;
		}else{
			return $url. '&'. $paramter;
		}
	}
	
	function json_decode($result){
		return json_decode(preg_replace('#(?<=[,\{\[])\s*("\w+"):(\d{6,})(?=\s*[,\]\}])#si', '${1}:"${2}"', $result), true);
	}
	
}
