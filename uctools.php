<?php
/*
[Discuz!] Tools (C)2001-2008 Comsenz Inc.
This is NOT a freeware, use is subject to license terms

$Id: uctools.php 1193 2010-01-20 09:35:41Z songlixin $
*/

/**********************    密码配置区 开始*******************************/
$tool_password = 'ft6gy7hu8'; // ☆★☆★☆★ 请您设置一个工具包的高强度密码，不能为空！☆★☆★☆★
/**********************    密码配置区 结束*******************************/
error_reporting(E_ALL); //E_ERROR | E_WARNING | E_PARSE | E_ALL
@set_time_limit(0);
define('TOOLS_ROOT', dirname(__FILE__) . "/");
define('VERSION', '2009');
define('Release', '100120');
$functionall = array(array('all', 'all_repair', '检查或修复数据库', '对所有数据表进行检查修复工作。'), array('all', 'all_runquery', '快速设置(SQL)', '可以运行任意SQL语句，请慎用。'), array('all', 'all_restore', '恢复数据库备份', '恢复论坛数据备份。'), array('all', 'all_setadmin', '重置创始人密码', '重置 UCenter 的创始人密码'),);
$toolbar = array(array('phpinfo', 'INFO'), array('datago', '转码'), array('all_logout', '退出'),);
//初始化
$plustitle = '';
$lockfile = '';
//临时文件放置的目录，getplace()函数中设置
$docdir = '';
$action = '';
$target_fsockopen = '0';
$alertmsg = ' onclick="alert(\'点击确定开始运行,可能需要一段时间,请稍候\');"';
foreach (array('_COOKIE', '_POST', '_GET') as $_request) {
	foreach ($$_request as $_key => $_value) {
		($_key{0} != '_' && $_key != 'tool_password' && $_key != 'lockfile') && $$_key = taddslashes($_value);
	}
}
$whereis = getplace();

require_once $cfgfile;

if ($whereis && !in_array($whereis, array('is_uc'))) {
	$alertmsg = '';
	errorpage('<ul><li>工具箱（UCenter专用版）必须放在 UCenter 的根目录下才能正常使用。</li><li>如果你确实放在了上述程序目录下，请检查上述程序运配置文件（config）的可读写权限是否正确</li>');
}
if (@file_exists($lockfile)) {
	$alertmsg = '';
	errorpage("<h6>工具箱（UCenter专用版）已关闭，如需开启只要通过 FTP 删除 $lockfile 文件即可！ </h6>");
} elseif ($tool_password == '') {
	$alertmsg = '';
	errorpage('<h6>工具箱（UCenter专用版）密码默认为空，第一次使用前请您修改本文件中$tool_password设置密码！</h6>');
}
if ($action == 'login') {
	setcookie('toolpassword', md5($toolpassword), 0);
	echo '<meta http-equiv="refresh" content="2 url=?">';
	errorpage("<h6>请稍等，程序登录中！</h6>");
}
if (isset($toolpassword)) {
	if ($toolpassword != md5($tool_password)) {
		$alertmsg = '';
		errorpage("login");
	}
} else {
	$alertmsg = '';
	errorpage("login");
}
getdbcfg();
$mysql = mysql_connect($dbhost, $dbuser, $dbpw);
mysql_select_db($dbname);
$my_version = mysql_get_server_info();
if ($my_version > '4.1') {
	$serverset = $dbcharset ? 'character_set_connection=' . $dbcharset . ', character_set_results=' . $dbcharset . ', character_set_client=binary' : '';
	$serverset .= $my_version > '5.0.1' ? ((empty($serverset)) ? '' : ',') . 'sql_mode=\'\'' : '';
	$serverset && mysql_query("SET $serverset");
}
//流程开始
if ($action == 'all_repair') {
	$counttables = $oktables = $errortables = $rapirtables = 0;
	$doc         = $docdir . '/repaireport.txt';
	if ($check) {
		$tables = mysql_query("SHOW TABLES");
		if ($iterations) {
			$iterations--;
		}
		while ($table = mysql_fetch_row($tables)) {
			$counttables += 1;
			$answer = checktable($table[0], $iterations, $doc);
		}
		if ($simple) {
			htmlheader();
			echo '<h4>检查修复数据库</h4>
			    <h5>检查结果:</h5>
					<table>
						<tr><th>检查表(张)</th><th>正常表(张)</th><th>修复的表(张)</th><th>出错(个)</th></tr>
						<tr><td>' . $counttables . '</td><td>' . $oktables . '</td><td>' . $rapirtables . '</td><td>' . $errortables . '</td></tr>
					</table>
				<p>检查结果没有错误后请返回工具箱（UCenter专用版）首页反之则继续修复</p>
				<p><b><a href="uctools.php?action=all_repair">继续修复</a>&nbsp;&nbsp;&nbsp;&nbsp;<b><a href="' . $doc . '">修复报告</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="uctools.php">返回首页</a></b></p>
				</td></tr></table>';
			specialdiv();
		}
	} else {
		htmlheader();
		@unlink($doc);
		echo "<h4>检查修复数据库</h4>
		<div class='specialdiv'>
				操作提示：
				<ul>
				<li>您可以通过下面的方式修复已经损坏的数据库。点击后请耐心等待修复结果！</li>
				<li>本程序可以修复常见的数据库错误，但无法保证可以修复所有的数据库错误。(需要 MySQL 3.23+)</li>
				</ul>
				</div>
				<h5>操作：</h5>
				<ul>
				<li><a href=\"?action=all_repair&check=1&simple=1\">检查并尝试修复数据库1次</a>
				<li><a href=\"?action=all_repair&check=1&iterations=5&simple=1\">检查并尝试修复数据库5次</a> (因为数据库读写关系可能有时需要多修复几次才能完全修复成功)
				</ul>";
		specialdiv();
	}
	htmlfooter();
} elseif ($action == 'all_restore') { //导入数据库备份
	ob_implicit_flush();
	$backdirarray = array( //不同的程序存放备份文件的目录是不同的
		'is_dz' => 'forumdata', 'is_uc' => 'data/backup', 'is_uch' => 'data', 'is_ss' => 'data');
	if (!get_cfg_var('register_globals')) {
		@extract($HTTP_GET_VARS);
	}
	$sqldump = '';
	htmlheader();
	?><h4>数据库恢复实用工具 </h4><?php
	echo "<div class=\"specialdiv\">操作提示：<ul>
		<li>只能恢复存放在服务器(远程或本地)上的数据文件,如果您的数据不在服务器上,请用 FTP 上传</li>
		<li>数据文件必须为 Discuz! 导出格式,并设置相应属性使 PHP 能够读取</li>
		<li>请尽量选择服务器空闲时段操作,以避免超时.如程序长久(超过 10 分钟)不反应,请刷新</li></ul></div>";
	if ($file) {
		if (!mysql_select_db($dbname)) {
			mysql_query("CREATE DATABASE $dbname;");
		}
		if (strtolower(substr($file, 0, 7)) == "http://") {
			echo "从远程数据库恢复数据 - 读取远程数据:<br><br>";
			echo "从远程服务器读取文件 ... ";
			$sqldump = @fread($fp, 99999999);
			@fclose($fp);
			if ($sqldump) {
				echo "成功<br><br>";
			} elseif (!$multivol) {
				cexit("失败<br><br><b>无法恢复数据</b>");
			}
		} else {
			echo "<div class=\"specialtext\">从本地恢复数据 - 检查数据文件:<br><br>";
			if (file_exists($file)) {
				echo "数据文件 $file 存在检查 ... 成功<br><br>";
			} elseif (!$multivol) {
				cexit("数据文件 $file 存在检查 ... 失败<br><br><br><b>无法恢复数据</b></div>");
			}
			if (is_readable($file)) {
				echo "数据文件 $file 可读检查 ... 成功<br><br>";
				@$fp = fopen($file, "r");
				@flock($fp, 3);
				$sqldump = @fread($fp, filesize($file));
				@fclose($fp);
				echo "从本地读取数据 ... 成功<br><br>";
			} elseif (!$multivol) {
				cexit("数据文件 $file 可读检查 ... 失败<br><br><br><b>无法恢复数据</b></div>");
			}
		}
		if ($multivol && !$sqldump) {
			cexit("分卷备份范围检查 ... 成功<br><br><b>恭喜您,数据已经全部成功恢复!安全起见,请务必删除本程序.</b></div>");
		}
		echo "数据文件 $file 格式检查 ... ";
		if ($whereis == 'is_uc') {

			$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", substr($sqldump, 0, 256))));
			$method   = 'multivol';
			$volume   = $identify[4];
		} else {
			@list(, , , $method, $volume) = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", preg_replace("/^(.+)/", "\\1", substr($sqldump, 0, 256)))));
		}
		if ($method == 'multivol' && is_numeric($volume)) {
			echo "成功<br><br>";
		} else {
			cexit("失败<br><br><b>数据非 Discuz! 分卷备份格式,无法恢复</b></div>");
		}
		if ($onlysave == "yes") {
			echo "将数据文件保存到本地服务器 ... ";
			$filename = TOOLS_ROOT . './' . $backdirarray[$whereis] . strrchr($file, "/");
			@$filehandle = fopen($filename, "w");
			@flock($filehandle, 3);
			if (@fwrite($filehandle, $sqldump)) {
				@fclose($filehandle);
				echo "成功<br><br>";
			} else {
				@fclose($filehandle);
				die("失败<br><br><b>无法保存数据</b>");
			}
			echo "成功<br><br><b>恭喜您,数据已经成功保存到本地服务器 <a href=\"" . strstr($filename, "/") . "\">$filename</a>.安全起见,请务必删除本程序.</b></div>";
		} else {
			$sqlquery = splitsql($sqldump);
			echo "拆分操作语句 ... 成功<br><br>";
			unset($sqldump);

			echo "正在恢复数据,请等待 ... </div>";
			foreach ($sqlquery as $sql) {
				$dbversion = mysql_get_server_info();
				$sql       = syntablestruct(trim($sql), $dbversion > '4.1', $dbcharset);
				if (trim($sql)) {
					@mysql_query($sql);
				}
			}
			if ($auto == 'off') {
				$nextfile = str_replace("-$volume.sql", '-' . ($volume + 1) . '.sql', $file);
				cexit("<ul><li>数据文件 <b>$volume#</b> 恢复成功,如果有需要请继续恢复其他卷数据文件</li><li>请点击<b><a href=\"?action=all_restore&file=$nextfile&multivol=yes\">全部恢复</a></b>	或许单独恢复下一个数据文件<b><a href=\"?action=all_restore&file=$nextfile&multivol=yes&auto=off\">单独恢复下一数据文件</a></b></li></ul>");
			} else {
				$nextfile = str_replace("-$volume.sql", '-' . ($volume + 1) . '.sql', $file);
				echo "<ul><li>数据文件 <b>$volume#</b> 恢复成功,现在将自动导入其他分卷备份数据.</li><li><b>请勿关闭浏览器或中断本程序运行</b></li></ul>";
				redirect("?action=all_restore&file=$nextfile&multivol=yes");
			}
		}
	} else {
		$exportlog = array();
		if (is_dir(TOOLS_ROOT . './' . $backdirarray[$whereis])) {
			$dir = dir(TOOLS_ROOT . './' . $backdirarray[$whereis]);
			while ($entry = $dir->read()) {
				$entry = "./" . $backdirarray[$whereis] . "/$entry";
				if (is_file($entry) && preg_match("/\.sql/i", $entry)) {
					$filesize = filesize($entry);
					$fp       = @fopen($entry, 'rb');
					@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
					@fclose($fp);
					if (preg_match("/\-1.sql/i", $entry) || $identify[3] == 'shell') {
						$exportlog[$identify[0]] = array('version' => $identify[1], 'type' => $identify[2], 'method' => $identify[3], 'volume' => $identify[4], 'filename' => $entry, 'size' => $filesize);
					}
				} elseif (is_dir($entry) && preg_match("/backup\_/i", $entry)) {
					$bakdir = dir($entry);
					while ($bakentry = $bakdir->read()) {
						$bakentry = "$entry/$bakentry";
						if (is_file($bakentry)) {
							@$fp = fopen($bakentry, 'rb');
							@$bakidentify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
							@fclose($fp);
							if (preg_match("/\-1\.sql/i", $bakentry) || $bakidentify[3] == 'shell') {
								$identify['bakentry'] = $bakentry;
							}
						}
					}
					if (preg_match("/backup\_/i", $entry)) {
						$exportlog[filemtime($entry)] = array('version' => $bakidentify[1], 'type' => $bakidentify[2], 'method' => $bakidentify[3], 'volume' => $bakidentify[4], 'bakentry' => $identify['bakentry'], 'filename' => $entry);
					}
				}
			}
			$dir->close();
		} else {
			echo 'error';
		}
		krsort($exportlog);
		reset($exportlog);

		$title = '<h5><a href="?action=all_restore">【恢复数据】</a>';
		if ($dz_version >= 700 || $whereis == 'is_uc' || $whereis == 'is_uch' || $ss_version >= 70) {
			$title .= '&nbsp;&nbsp;&nbsp;<a href="?action=all_backup&begin=1">【备份数据】</a></h5>';
		} else {
			$title .= '</h5>';
		}
		$exportinfo = $title . '<table><caption>&nbsp;&nbsp;&nbsp;数据库文件夹</caption><tr><th>备份项目</th><th>版本</th><th>时间</th><th>类型</th><th>查看</th><th>操作</th></tr>';
		foreach ($exportlog as $dateline => $info) {
			$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8 * 3600) : '未知';
			switch ($info['type']) {
				case 'full':
					$info['type'] = '全部备份';
					break;
				case 'standard':
					$info['type'] = '标准备份(推荐)';
					break;
				case 'mini':
					$info['type'] = '最小备份';
					break;
				case 'custom':
					$info['type'] = '自定义备份';
					break;
			}
			$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
			$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
			$info['url']    = str_replace(".sql", '', str_replace("-$info[volume].sql", '', substr(strrchr($info['filename'], "/"), 1)));
			$exportinfo .= "<tr>\n" . "<td>" . $info['url'] . "</td>\n" . "<td>$info[version]</td>\n" . "<td>$info[dateline]</td>\n" . "<td>$info[type]</td>\n";
			if ($info['bakentry']) {
				$exportinfo .= "<td><a href=\"?action=all_restore&bakdirname=" . $info['url'] . "\">查看</a></td>\n" . "<td><a href=\"?action=all_restore&file=$info[bakentry]&importsubmit=yes\">[全部导入]</a></td>\n</tr>\n";
			} else {
				$exportinfo .= "<td><a href=\"?action=all_restore&filedirname=" . $info['url'] . "\">查看</a></td>\n" . "<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes\">[全部导入]</a></td>\n</tr>\n";
			}
		}
		$exportinfo .= '</table>';
		echo $exportinfo;
		unset($exportlog);
		unset($exportinfo);
		echo "<br>";
		//查看目录里的备份文件列表，一级目录下
		if (!empty($filedirname)) {
			$exportlog = array();
			if (is_dir(TOOLS_ROOT . './' . $backdirarray[$whereis])) {
				$dir = dir(TOOLS_ROOT . './' . $backdirarray[$whereis]);
				while ($entry = $dir->read()) {
					$entry = "./" . $backdirarray[$whereis] . "/$entry";
					if (is_file($entry) && preg_match("/\.sql/i", $entry) && preg_match("/$filedirname/i", $entry)) {
						$filesize = filesize($entry);
						@$fp = fopen($entry, 'rb');
						@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
						@fclose($fp);

						$exportlog[$identify[0]] = array('version' => $identify[1], 'type' => $identify[2], 'method' => $identify[3], 'volume' => $identify[4], 'filename' => $entry, 'size' => $filesize);
					}
				}
				$dir->close();
			}
			krsort($exportlog);
			reset($exportlog);

			$exportinfo = '<table>
							<caption>&nbsp;&nbsp;&nbsp;数据库文件列表</caption>
							<tr>
							<th>文件名</th><th>版本</th>
							<th>时间</th><th>类型</thd>
							<th>大小</th><td>方式</th>
							<th>卷号</th><th>操作</th></tr>';
			foreach ($exportlog as $dateline => $info) {
				$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8 * 3600) : '未知';
				switch ($info['type']) {
					case 'full':
						$info['type'] = '全部备份';
						break;
					case 'standard':
						$info['type'] = '标准备份(推荐)';
						break;
					case 'mini':
						$info['type'] = '最小备份';
						break;
					case 'custom':
						$info['type'] = '自定义备份';
						break;
				}
				$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
				$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
				$exportinfo .= "<tr>\n" . "<td><a href=\"$info[filename]\" name=\"" . substr(strrchr($info['filename'], "/"), 1) . "\">" . substr(strrchr($info['filename'], "/"), 1) . "</a></td>\n" . "<td>$info[version]</td>\n" . "<td>$info[dateline]</td>\n" . "<td>$info[type]</td>\n" . "<td>" . get_real_size($info[size]) . "</td>\n" . "<td>$info[method]</td>\n" . "<td>$info[volume]</td>\n" . "<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes&auto=off\">[导入]</a></td>\n</tr>\n";
			}
			$exportinfo .= '</table>';
			echo $exportinfo;
		}
		// 查看目录里的备份文件列表， 二级目录下，其中二级目录是随机产生的
		if (!empty($bakdirname)) {
			$exportlog   = array();
			$filedirname = TOOLS_ROOT . './' . $backdirarray[$whereis] . '/' . $bakdirname;
			if (is_dir($filedirname)) {
				$dir = dir($filedirname);
				while ($entry = $dir->read()) {
					$entry = $filedirname . '/' . $entry;
					if (is_file($entry) && preg_match("/\.sql/i", $entry)) {
						$filesize = filesize($entry);
						@$fp = fopen($entry, 'rb');
						@$identify = explode(',', base64_decode(preg_replace("/^# Identify:\s*(\w+).*/s", "\\1", fgets($fp, 256))));
						@fclose($fp);

						$exportlog[$identify[0]] = array('version' => $identify[1], 'type' => $identify[2], 'method' => $identify[3], 'volume' => $identify[4], 'filename' => $entry, 'size' => $filesize);
					}
				}
				$dir->close();
			}
			krsort($exportlog);
			reset($exportlog);

			$exportinfo = '<table>
					<caption>&nbsp;&nbsp;&nbsp;数据库文件列表</caption>
					<tr>
					<th>文件名</th><th>版本</th>
					<th>时间</th><th>类型</th>
					<th>大小</th><th>方式</th>
					<th>卷号</th><th>操作</th></tr>';
			foreach ($exportlog as $dateline => $info) {
				$info['dateline'] = is_int($dateline) ? gmdate("Y-m-d H:i", $dateline + 8 * 3600) : '未知';
				switch ($info['type']) {
					case 'full':
						$info['type'] = '全部备份';
						break;
					case 'standard':
						$info['type'] = '标准备份(推荐)';
						break;
					case 'mini':
						$info['type'] = '最小备份';
						break;
					case 'custom':
						$info['type'] = '自定义备份';
						break;
				}
				$info['volume'] = $info['method'] == 'multivol' ? $info['volume'] : '';
				$info['method'] = $info['method'] == 'multivol' ? '多卷' : 'shell';
				$exportinfo .= "<tr>\n" . "<td><a href=\"$info[filename]\" name=\"" . substr(strrchr($info['filename'], "/"), 1) . "\">" . substr(strrchr($info['filename'], "/"), 1) . "</a></td>\n" . "<td>$info[version]</td>\n" . "<td>$info[dateline]</td>\n" . "<td>$info[type]</td>\n" . "<td>" . get_real_size($info[size]) . "</td>\n" . "<td>$info[method]</td>\n" . "<td>$info[volume]</td>\n" . "<td><a href=\"?action=all_restore&file=$info[filename]&importsubmit=yes&auto=off\">[导入]</a></td>\n</tr>\n";
			}
			$exportinfo .= '</table>';
			echo $exportinfo;
		}
		echo "<br>";
		cexit("");
	}
} elseif ($action == 'all_runquery') { //运行sql
	if (!empty($_POST['sqlsubmit']) && $_POST['queries']) {
		runquery($queries);
	}
	htmlheader();
	runquery_html();
	htmlfooter();
} elseif ($action == 'all_setadmin') { //重置管理员帐号密码，
	$sql_findadmin = '';
	$sql_select    = '';
	$sql_update    = '';
	$sql_rspw      = '';
	$secq          = '';
	$rspw          = '';
	$username      = '';
	$uid           = '';
	all_setadmin_set($tablepre, $whereis);
	$info    = '';
	$info_uc = '';
	htmlheader();
	?>
	<h4>重置创始人密码</h4>
	<?php
	//查询已经存在的管理
	if (!empty($_POST['loginsubmit'])) {
		if ($whereis == 'is_uc') {
			define(ROOT_DIR, dirname(__FILE__) . "/");
			$configfile  = ROOT_DIR . "./data/config.inc.php";
			$uc_password = $_POST["password"];
			$salt        = substr(uniqid(rand()), 0, 6);
			if (!$uc_password) {
				$info = "密码不能为空";
			} else {
				$md5_uc_password = md5(md5($uc_password) . $salt);
				$config          = file_get_contents($configfile);
				$config          = preg_replace("/define\('UC_FOUNDERSALT',\s*'.*?'\);/i", "define('UC_FOUNDERSALT', '$salt');", $config);
				$config          = preg_replace("/define\('UC_FOUNDERPW',\s*'.*?'\);/i", "define('UC_FOUNDERPW', '$md5_uc_password');", $config);
				$fp              = @fopen($configfile, 'w');
				@fwrite($fp, $config);
				@fclose($fp);
				$info = "UCenter创始人密码更改成功为：$uc_password";
			}
		}
		errorpage($info, '重置管理员帐号', 0, 0);
	} else {
		?>
		<form action="?action=all_setadmin" method="post">
			<table>

				<?php
				if ($rspw) {
					?>
					<tr>
						<th width="30%">请输入密码</th>
						<td width="70%"><input class="textinput" type="text" name="password" size="25"></td>
					</tr>
				<?php
				} else {
					?>
					<tr>
						<th width="30%">密码修改提示</th>
						<td width="70%">管理员密码请登录UC后台去改。<a href=11 target='_blank'>点击进入UC后台</a></td>
					</tr>
				<?php
				}
				if ($secq) {
					?>
					<tr>
						<th width="30%">是否清除安全提问</th>
						<td width="70%"><input class="radio" name="issecques" value="1" checked="checked" type="radio">是&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input
								name="issecques" value="" class="radio" type="radio">否
						</td>
					</tr>
				<?php
				}
				?>
			</table>
			<input type="submit" name="loginsubmit" value="提 &nbsp; 交">
		</form>
	<?php
	}
	specialdiv();
	htmlfooter();
} elseif ($action == 'all_setlock') { //锁定工具箱
	touch($lockfile);
	if (file_exists($lockfile)) {
		echo '<meta http-equiv="refresh" content="3 url=?">';
		errorpage("<h6>成功关闭工具箱（UCenter专用版）！强烈建议您在不需要本程序的时候及时进行删除</h6>", '锁定工具箱（UCenter专用版）');
	} else {
		errorpage('注意您的目录没有写入权限，我们无法给您提供安全保障，请删除论坛根目录下的tool.php文件！', '锁定工具箱（UCenter专用版）');
	}
} elseif ($action == 'all_logout') { //退出登陆
	setcookie('toolpassword', '', -86400 * 365);
	errorpage("<h6>您已成功退出,欢迎下次使用.强烈建议您在不使用时删除此文件.</h6>");
} elseif ($action == 'phpinfo') {
	echo phpinfo(13);
	exit;
} elseif ($action == 'datago') {
	htmlheader();
	!$tableno && $tableno = 0;
	!$do && $do = 'create';
	!$start && $start = 0;
	$limit = 2000;
	echo '<h4>数据库编码转换</h4>';
	echo "<div class=\"specialdiv\">操作提示：<ul>
		<li><font color=red>转换后请自行修改配置文件中的数据库前缀、页面编码、数据库编码</font></li>
		<li>详细转换教程：<a href='http://www.discuz.net/thread-1460873-1-1.html'><font color=red>使用Tools转换数据库编码教程</font></a></li>
		<li>如果数据库过大，可能需要过多时间</li>
		</ul></div>";
	if ($submit) {
		do_datago($mysql, $tableno, $do, $start, $limit);
	} elseif ($my_version > '4.1') {
		datago_output();
	} else {
		echo '数据库版本不支持数据库编码';
	}
	htmlfooter();
} elseif ($action == 'all_backup') {
	htmlheader();
	echo "<script type='text/javascript'>
			function jumpurl(url){
				location.href = url;
				return false;
			}
		</script>";
	if ($begin == '1') {
		echo "<h4>数据库备份</h4><div class=\"specialdiv\">操作提示：<ul>
			<li>数据库备份通过api/dbbak.php来执行，请确保这个文件存在</li>
			<li>备份前请关闭论坛访问，以免备份数据不完整</li>
			<li>请尽量选择服务器空闲时段操作,以避免超时。如程序长久(超过 10 分钟)不反应,请刷新</li></ul></div>";
		$title = '<h5><a href="?action=all_restore">【恢复数据】</a>';
		$title .= '&nbsp;&nbsp;&nbsp;<a href="?action=all_backup&begin=1">【备份数据】</a></h5>';
		echo $title;
		$begin = '<button style="margin:0px;" onclick=jumpurl("uctools.php?action=all_backup")>开始备份</button>';
		cexit($begin);
	}
	$notice = "<div class=\"specialdiv\">操作提示：<ul>
			<li>接口文件不存在！</li>
			</ul></div>";
	if (!file_exists('./api/dbbak.php')) {
		cexit($notice);
	}
	if ($nexturl) {
		$url = $nexturl;
	} else {
		$url = getbakurl($whereis);
	}
	dobak($url, $num);
	htmlfooter();
} else {
	htmlheader();
	echo '<h4>欢迎您使用 Comsenz 系统维护工具箱（UCenter专用版）</h4>
		<tr><td><br>';
	echo '<h5>Comsenz 系统维护工具箱（UCenter专用版）功能简介：</h5><ul>';
	foreach ($functionall as $value) {
		$apps = explode('_', $value['0']);
		if (in_array(substr($whereis, 3), $apps) || $value['0'] == 'all') {
			echo '<li>' . $value[2] . '：' . $value[3] . '</li>';
		}
	}
	echo '</ul>';
	htmlfooter();
}
//函数区
function cexit($message) {
	echo $message;
	specialdiv();
	htmlfooter();
}


function get_real_size($size) {
	$kb = 1024;
	$mb = 1024 * $kb;
	$gb = 1024 * $mb;
	$tb = 1024 * $gb;

	if ($size < $kb) {
		return $size . ' Byte';
	} elseif ($size < $mb) {
		return round($size / $kb, 2) . ' KB';
	} elseif ($size < $gb) {
		return round($size / $mb, 2) . ' MB';
	} elseif ($size < $tb) {
		return round($size / $gb, 2) . ' GB';
	} else {
		return round($size / $tb, 2) . ' TB';
	}
}

function htmlheader() {
	global $uch_version, $alertmsg, $whereis, $functionall, $dz_version, $ss_version, $toolpassword, $tool_password, $toolbar, $plustitle;
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<title>Comsenz 系统维护工具箱（UCenter专用版） ' . VERSION . '-New</title>
		<style type="text/css"><!--
		body {font-family: Tahoma,Arial, Helvetica, sans-serif, "宋体";font-size: 12px;color:#000;line-height: 120%;padding:0;margin:0;background:#DDE0FF;overflow-x:hidden;word-break:break-all;white-space:normal;scrollbar-3d-light-color:#606BFF;scrollbar-highlight-color:#E3EFF9;scrollbar-face-color:#CEE3F4;scrollbar-arrow-color:#509AD8;scrollbar-shadow-color:#F0F1FF;scrollbar-base-color:#CEE3F4;}
        a:hover {color:#60F;}
		ul {padding:2px 0 10px 0;margin:0;}
		textarea,table,td,th,select{border:1px solid #868CFF;border-collapse:collapse;}
		table li {margin-left:10px;}
		input{margin:10px 0 0px 30px;border-width:1px;border-style:solid;border-color:#FFF #64A7DD #64A7DD #FFF;padding:2px 8px;background:#E3EFF9;}
			input.radio,input.checkbox,input.textinput,input.specialsubmit {margin:0;padding:0;border:0;padding:0;background:none;}
			input.textinput,input.specialsubmit {border:1px solid #AFD2ED;background:#FFF;}
			input.textinput {padding:4px 0;} 			input.specialsubmit {border-color:#FFF #64A7DD #64A7DD #FFF;background:#E3EFF9;padding:0 5px;}
		option {background:#FFF;}
		select {background:#F0F1FF;}
		#header {border-top:4px solid #86B9D6;height:60px;width:100%;padding:0;margin:0;}
		    h2 {font-size:20px;font-weight:normal;position:absolute;top:20px;left:20px;padding:10px;margin:0;}
		    h3 {font-size:14px;position:absolute;top:28px;right:20px;padding:10px;margin:0;}
		#content {height:510px;background:#F0F1FF;overflow-x:hidden;z-index:1000;}
		    #nav {top:60px;left:0;height:510px;width:180px;border-right:1px solid #DDE0FF;position:absolute;z-index:2000;}
		        #nav ul {padding:0 10px;padding-top:30px;}
		        #nav li {list-style:none;}
		        #nav li a {font-size:14px;line-height:180%;font-weight:400;color:#000;}
		        #nav li a:hover {color:#60F;}
		    #textcontent {padding-left:200px;height:510px;width:80%;*width:100%;line-height:160%;overflow-y:auto;overflow-x:hidden;}
			    h4,h5,h6 {padding:4px;font-size:16px;font-weight:bold;margin-top:20px;margin-bottom:5px;color:#006;}
				h5,h6 {font-size:14px;color:#000;}
				h6 {color:#F00;padding-top:5px;margin-top:0;}
				.specialdiv {width:70%;border:1px dashed #C8CCFF;padding:0 5px;margin-top:20px;background:#F9F9FF;}
				.specialdiv2 {height:240px;width:60%;border:1px dashed #C8CCFF;padding:15px;margin-top:20px;background:#F9F9FF;overflow-y:scroll;}
				#textcontent ul {margin-left:30px;}
				textarea {width:78%;height:300px;text-align:left;border-color:#AFD2ED;}
				select {border-color:#AFD2ED;}
				table {width:74%;font-size:12px;margin-left:18px;margin-top:10px;}
				    table.specialtable,table.specialtable td {border:0;}
					td,th {padding:5px;text-align:left;}
				    caption {font-weight:bold;padding:8px 0;color:#3544FF;text-align:left;}
				    th {background:#D9DCFF;font-weight:600;}
					td.specialtd {text-align:left;}
				.specialtext {background:#FCFBFF;margin-top:20px;padding:5px 40px;width:64.5%;margin-bottom:10px;color:#006;}
		#footer p {padding:0 5px;text-align:center;}
		#jsmenu {margin-left:-200px;margin-top:-110px;border:5px solid #868CFF;width:400px;height:140px;padding:4px 10px 0 10px; text-align:left;background:#FFF; left:50%; top:50%; position:absolute; font:12px;zIndex:10001;}
		.button {margin-top:20px;}
		.infobox {background:#FFF;border-bottom:4px solid #868CFF;border-top:4px solid #868CFF;margin-bottom:10px;padding:30px;text-align:center;width:90%;}
		pre {*margin-top:10px;}
		.current { font-weight: bold; color: #090 !important; border-bottom-color: #F90 !important; }
		-->
		</style>
		</head>
		<script>function $(id) {return document.getElementById(id);}
		function menuclose(){
			$(\'jsmenu\').style.display = \'none\';
		}
		</script>
		<body>
	<div id = "jsmenu" style="display:none">
	<h6>提示：</h6>
	提示：在进行此操作前建议备份数据库，以免处理过程中出现错误造成数据丢失！！<br/>
	<input class=button onclick=menuclose() type=button value=我知道了></input>
	</div>
        <div id="header">
		<h2>< Comsenz Tools ' . VERSION . ' > Ucenter 专版</h2>
		<h3>[ <a href="?" target="_self">首页</a> ]&nbsp;
		[ <a href="?action=all_setlock" target="_self">锁定</a> ]&nbsp;';
	if ($toolpassword == md5($tool_password)) {
		foreach ($toolbar as $value) {
			echo '[ <a href="?action=' . $value[0] . '" target="_self">' . $value[1] . '</a> ]&nbsp';
		}
	}
	echo '</h3></div>
		<div id="nav">';
	echo '<ul>'; //导航菜单中根据不同的目录显示不同
	if ($toolpassword == md5($tool_password)) {
		foreach ($functionall as $value) {
			$apps = explode('_', $value['0']);
			if (in_array(substr($whereis, 3), $apps) || $value['0'] == 'all') {
				if ($whereis == 'is_ss' && $value[1] == 'all_setadmin' && $ss_version < 70) {
					continue;
				}
				echo '<li>[ <a href="?action=' . $value[1] . '" target="_self">' . $value[2] . '</a> ]</li>';
			}
		}
	} else {
		echo '<li>[ <a href="uctools.php" target="_self">使用前请登录</a> ]</li>';
	}
	echo '</ul>';
	echo '</div>
		<div id="content">
		<div id="textcontent">';
}

//页面底部
function htmlfooter() {
	echo '</div></div>
		<div id="footer"><p>Comsenz 系统维护工具箱（UCenter专用版） &nbsp;Release:' . Release . '&nbsp;
		 &copy; <a href="http://www.comsenz.com" style="color: #000000; text-decoration: none">Comsenz Inc.</a> 2001-2009 </font></td></tr><tr style="font-size: 0px; line-height: 0px; spacing: 0px; padding: 0px; background-color: #698CC3">
		</p></div>
		</body>
		</html>';
	exit;
}

//错误信息
function errorpage($message, $title = '', $isheader = 1, $isfooter = 1) {
	if ($isheader) {
		htmlheader();
	}
	!$isheader && $title = '';
	if ($message == 'login') {
		$message = '<h4>工具箱登录</h4>
				<form action="?" method="post">
					<table class="specialtable"><tr>
					<td width="20%"><input class="textinput" type="password" name="toolpassword"></input></td>
					<td><input class="specialsubmit" type="submit" value="登 录"></input></td></tr></table>
					<input type="hidden" name="action" value="login">
				</form>';
	} else {
		$message = "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$message</td></tr></table>";
	}
	echo $message;
	if ($isfooter) {
		htmlfooter();
	}
}

//跳转
function redirect($url) {
	echo "<script>";
	echo "function redirect() {window.location.replace('$url');}\n";
	echo "setTimeout('redirect();', 2000);\n";
	echo "</script>";
	echo "<br><br><a href=\"$url\">如果您的浏览器没有自动跳转，请点击这里</a>";
	cexit("");
}

/**
 * 检查目录里下的文件权限函数
 * @param unknown_type $directory
 */

//检查sql语句
function splitsql($sql) {
	$sql          = str_replace("\r", "\n", $sql);
	$ret          = array();
	$num          = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach ($queriesarray as $query) {
		$queries = explode("\n", trim($query));
		foreach ($queries as $query) {
			$ret[$num] .= $query[0] == "#" ? NULL : $query;
		}
		$num++;
	}

	return ($ret);
}

function syntablestruct($sql, $version, $dbcharset) {

	if (strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
		return $sql;
	}
	if (substr(trim($sql), 0, 9) == 'SET NAMES' && !$version) {
		return '';
	}
	$sqlversion = strpos($sql, 'ENGINE=') === FALSE ? FALSE : TRUE;

	if ($sqlversion === $version) {

		return $sqlversion && $dbcharset ? preg_replace(array('/ character set \w+/i', '/ collate \w+/i', "/DEFAULT CHARSET=\w+/is"), array('', '', "DEFAULT CHARSET=$dbcharset"), $sql) : $sql;
	}

	if ($version) {
		return preg_replace(array('/TYPE=HEAP/i', '/TYPE=(\w+)/is'), array("ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"), $sql);

	} else {
		return preg_replace(array('/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'), array('', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'), $sql);
	}
}

function stay_redirect() {
	global $action, $actionnow, $step, $stay, $convertedrows, $allconvertedrows;
	$nextstep = $step + 1;
	echo '<h4>数据库冗余数据清理</h4><table>
			<tr><th>正在进行' . $actionnow . '</th></tr><tr>
			<td>';
	if ($stay) {
		$actions = isset($action[$nextstep]) ? $action[$nextstep] : '结束';
		echo "$actionnow 操作完毕.共处理<font color=red>{$convertedrows}</font>条数据." . ($stay == 1 ? "&nbsp;&nbsp;&nbsp;&nbsp;" : '') . '<br><br>';
		echo "<a href='?action=dz_mysqlclear&step=" . $nextstep . "&stay=1'>( $actions )，请点击这里！</a><br>";
	} else {
		if (isset($action[$nextstep])) {
			echo '即将进入：' . $action[$nextstep] . '......';
		}
		$allconvertedrows = $allconvertedrows + $convertedrows;
		$timeout          = $GLOBALS['debug'] ? 5000 : 2000;
		echo "<script>\r\n";
		echo "<!--\r\n";
		echo "function redirect() {\r\n";
		echo "	window.location.replace('?action=dz_mysqlclear&step=" . $nextstep . "&allconvertedrows=" . $allconvertedrows . "');\r\n";
		echo "}\r\n";
		echo "setTimeout('redirect();', $timeout);\r\n";
		echo "-->\r\n";
		echo "</script>\r\n";
		echo "[<a href='?action=dz_mysqlclear' style='color:red'>停止运行</a>]<br><br><a href=\"" . $scriptname . "?step=" . $nextstep . "\">如果您的浏览器长时间没有自动跳转，请点击这里！</a>";
	}
	echo '</td></tr></table>';
}

//检查数据库表字段
function loadtable($table, $force = 0) {
	global $carray;
	$discuz_tablepre = $carray['tablepre'];
	static $tables = array();

	if (!isset($tables[$table])) {
		if (mysql_get_server_info() > '4.1') {
			$query = @mysql_query("SHOW FULL COLUMNS FROM {$discuz_tablepre}$table");
		} else {
			$query = @mysql_query("SHOW COLUMNS FROM {$discuz_tablepre}$table");
		}
		while ($field = @mysql_fetch_assoc($query)) {
			$tables[$table][$field['Field']] = $field;
		}
	}

	return $tables[$table];
}

//获得数据表的最大和最小 id 值
function validid($id, $table) {
	global $start, $maxid, $mysql, $tablepre;
	$sql    = mysql_query("SELECT MIN($id) AS minid, MAX($id) AS maxid FROM {$tablepre}$table");
	$result = mysql_fetch_array($sql);
	$start  = $result['minid'] ? $result['minid'] - 1 : 0;
	$maxid  = $result['maxid'];
}

//提示
function specialdiv() {
	echo '<div class="specialdiv">
		<h6>注意：</h6>
		<ul>
		<li>当您使用完毕Comsenz 系统维护工具箱（UCenter专用版）后，请点击锁定工具箱以确保系统的安全！下次使用前只需要在/data目录下删除tool.lock文件即可开始使用。</li></ul></div>';
}

//判断目录
function getplace() {
	global $lockfile, $cfgfile, $docdir;
	$whereis = FALSE;
	if (is_writeable('./config/config_global.php') && is_dir('source')) { //判断Discuz!目录
		$whereis  = 'is_dzx';
		$lockfile = './data/tools.lock';
		$cfgfile  = './config/config_global.php';
		$docdir   = './data';
	}
	if (is_writeable('./config.inc.php') && is_writeable('./forumdata')) { //判断Discuz!目录
		$whereis  = 'is_dz';
		$lockfile = './forumdata/tools.lock';
		$cfgfile  = './config.inc.php';
		$docdir   = './forumdata';
	}
	if (is_writeable('./data/config.inc.php') && is_dir('./control')) { //判断UCenter目录
		$whereis  = 'is_uc';
		$lockfile = './data/tools.lock';
		$cfgfile  = './data/config.inc.php';
		$docdir   = './data';
	}
	if (is_writeable('./config.php') && is_dir('source')) { //判断UCenter Home目录
		$whereis  = 'is_uch';
		$lockfile = './data/tools.lock';
		$cfgfile  = './config.php';
		$docdir   = './data';
	}
	if (is_writeable('./config.php') && file_exists('./batch.common.php')) { //判断SupeSite目录
		$whereis  = 'is_ss';
		$lockfile = './data/tools.lock';
		$cfgfile  = './config.php';
		$docdir   = './data';
	}

	return $whereis;
}

//获得数据库配置信息
function getdbcfg() {
	global $uc_dbcharset, $uc_dbhost, $uc_dbuser, $uc_dbpw, $uc_dbname, $uc_tablepre, $dbhost, $dbuser, $dbpw, $dbname, $dbcfg, $whereis, $cfgfile, $tablepre, $dbcharset, $dz_version, $ss_version, $uch_version;
	if (@!include($cfgfile)) {
		htmlheader();
		cexit("<h4>请先上传config文件以保证您的数据库能正常链接！</h4>");
	}
	if (UC_DBHOST) {
		$uc_dbhost    = UC_DBHOST;
		$uc_dbuser    = UC_DBUSER;
		$uc_dbpw      = UC_DBPW;
		$uc_dbname    = UC_DBNAME;
		$uc_tablepre  = UC_DBTABLEPRE;
		$uc_dbcharset = UC_DBCHARSET;
	}
	switch ($whereis) {
		case 'is_dzx':
			$dbhost    = $dbhost;
			$dbuser    = $dbuser;
			$dbpw      = $dbpw;
			$dbname    = $dbname;
			$tablepre  = $tablepre;
			$dbcharset = !$dbcharset ? (strtolower($charset) == 'utf-8' ? 'utf8' : $charset) : $dbcharset;
			define('IN_DISCUZ', TRUE);
			@require_once "./discuz_version.php";
			$dz_version = DISCUZ_VERSION;
			if ($dz_version >= '7.1') {
				$dz_version = intval(str_replace('.', '', $dz_version)) . '0';
			} else {
				$dz_version = intval(str_replace('.', '', $dz_version));
			}
			break;
		case 'is_dz':
			$dbhost    = $dbhost;
			$dbuser    = $dbuser;
			$dbpw      = $dbpw;
			$dbname    = $dbname;
			$tablepre  = $tablepre;
			$dbcharset = !$dbcharset ? (strtolower($charset) == 'utf-8' ? 'utf8' : $charset) : $dbcharset;
			define('IN_DISCUZ', TRUE);
			@require_once "./discuz_version.php";
			$dz_version = DISCUZ_VERSION;
			if ($dz_version >= '7.1') {
				$dz_version = intval(str_replace('.', '', $dz_version)) . '0';
			} else {
				$dz_version = intval(str_replace('.', '', $dz_version));
			}
			break;
		case 'is_uc':
			$dbhost    = UC_DBHOST;
			$dbuser    = UC_DBUSER;
			$dbpw      = UC_DBPW;
			$dbname    = UC_DBNAME;
			$tablepre  = UC_DBTABLEPRE;
			$dbcharset = !UC_DBCHARSET ? (strtolower(UC_CHARSET) == 'utf-8' ? 'utf8' : UC_CHARSET) : UC_DBCHARSET;
			break;
		case 'is_uch':
			$dbhost   = $_SC["dbhost"];
			$dbuser   = $_SC["dbuser"];
			$dbpw     = $_SC["dbpw"];
			$dbname   = $_SC["dbname"];
			$tablepre = $_SC["tablepre"];
			if (file_exists("./ver.php")) {
				require './ver.php';
				$uch_version = X_VER;
			} else {
				$common  = 'common.php';
				$version = fopen($common, 'r');
				$version = fread($version, filesize($common));
				$len     = strpos($version, 'define(\'D_BUG\')');
				$version = substr($version, 0, $len);
				$cache   = fopen('./data/version.php', 'w');
				fwrite($cache, $version);
				fclose($cache);
				require_once './data/version.php';
				$uch_version = intval(str_replace('.', '', X_VER));
				unlink('./data/version.php');
			}
			$uch_version = intval(str_replace('.', '', $uch_version));
			$dbcharset   = !$_SC['dbcharset'] ? (strtolower($_SC["charset"]) == 'utf-8' ? 'utf8' : $_SC["charset"]) : $_SC['dbcharset'];
			break;
		case 'is_ss':
			$dbhost    = $dbhost ? $dbhos : $_SC['dbhost'];
			$dbuser    = $dbuser ? $dbuser : $_SC['dbuser'];
			$dbpw      = $dbpw ? $dbpw : $_SC['dbpw'];
			$dbname    = $dbname ? $dbname : $_SC['dbname'];
			$tablepre  = $tablepre ? $tablepre : $_SC['tablepre'];
			$dbcharset = !$dbcharset ? (strtolower($charset) == 'utf-8' ? 'utf8' : $charset) : $dbcharset;
			if (!$dbcharset) {
				$dbcharset = !$_SC['dbcharset'] ? (strtolower($_SC['charset']) == 'utf-8' ? 'utf8' : $_SC['charset']) : $_SC['dbcharset'];
			}
			if ($_SC['dbhost'] || $_SC['dbuser']) {
				$common  = 'common.php';
				$version = fopen($common, 'r');
				$version = fread($version, filesize($common));
				$len     = strpos($version, 'define(\'S_RELEASE\'');
				$version = substr($version, 0, $len);
				$cache   = fopen('./data/version.php', 'w');
				fwrite($cache, $version);
				fclose($cache);
				require_once './data/version.php';
				$ss_version = intval(str_replace('.', '', S_VER));
				unlink('./data/version.php');
			}
			break;
		default:
			$dbhost = $dbuser = $dbpw = $dbname = $tablepre = $dbcharset = '';
			break;
	}
}

function taddslashes($string, $force = 0) {
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if (!MAGIC_QUOTES_GPC || $force) {
		if (is_array($string)) {
			foreach ($string as $key => $val) {
				$string[$key] = taddslashes($val, $force);
			}
		} else {
			$string = addslashes($string);
		}
	}

	return $string;
}

function pregcharset($charset, $color = 0) {
	if (strpos('..' . strtolower($charset), 'gbk')) {
		if ($color) {
			return '<font color="#0000CC">gbk</font>';
		} else {
			return 'gbk';
		}
	} elseif (strpos('..' . strtolower($charset), 'latin1')) {
		if ($color) {
			return '<font color="#993399">latin1</font>';
		} else {
			return 'latin1';
		}
	} elseif (strpos('..' . strtolower($charset), 'utf8')) {
		if ($color) {
			return '<font color="#993300">utf8</font>';
		} else {
			return 'utf8';
		}
	} elseif (strpos('..' . strtolower($charset), 'big5')) {
		if ($color) {
			return '<font color="#006699">big5</font>';
		} else {
			return 'big5';
		}
	} else {
		return $charset;
	}
}

function show_tools_message($message, $url = 'uctools.php', $time = '2000') {
	echo "<script>";
	echo "function redirect() {window.location.replace('$url');}\n";
	echo "setTimeout('redirect();', $time);\n";
	echo "</script>";
	echo "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$message<br><a href=\"$url\">如果您的浏览器没有自动跳转，请点击这里</a></td></tr></table>";
	exit;
}

function fileext($filename) {
	return trim(substr(strrchr($filename, '.'), 1, 10));
}

function cutstr($string, $length, $dot = ' ...') {
	global $charset;
	if (strlen($string) <= $length) {
		return $string;
	}
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut = '';
	if (strtolower($charset) == 'utf-8') {
		$n = $tn = $noc = 0;
		while ($n < strlen($string)) {

			$t = ord($string[$n]);
			if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1;
				$n++;
				$noc++;
			} elseif (194 <= $t && $t <= 223) {
				$tn = 2;
				$n += 2;
				$noc += 2;
			} elseif (224 <= $t && $t < 239) {
				$tn = 3;
				$n += 3;
				$noc += 2;
			} elseif (240 <= $t && $t <= 247) {
				$tn = 4;
				$n += 4;
				$noc += 2;
			} elseif (248 <= $t && $t <= 251) {
				$tn = 5;
				$n += 5;
				$noc += 2;
			} elseif ($t == 252 || $t == 253) {
				$tn = 6;
				$n += 6;
				$noc += 2;
			} else {
				$n++;
			}
			if ($noc >= $length) {
				break;
			}
		}
		if ($noc > $length) {
			$n -= $tn;
		}
		$strcut = substr($string, 0, $n);
	} else {
		for ($i = 0; $i < $length; $i++) {
			$strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
		}
	}
	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	return $strcut . $dot;
}

function checkfiles($currentdir, $ext = '', $sub = 1, $skip = '') {
	global $md5data, $dz_files;
	$dir   = @opendir($currentdir);
	$exts  = '/(' . $ext . ')$/i';
	$skips = explode(',', $skip);

	while ($entry = @readdir($dir)) {
		$file = $currentdir . $entry;
		if ($entry != '.' && $entry != '..' && (preg_match($exts, $entry) || $sub && is_dir($file)) && !in_array($entry, $skips)) {
			if ($sub && is_dir($file)) {
				checkfiles($file . '/', $ext, $sub, $skip);
			} else {
				$md5data[$file] = md5_file($file);
			}
		}
	}
}

function loadtable_ucenter($table, $force = 0) {
	global $carray;
	$discuz_tablepre = $carray['UC_DBTABLEPRE'];
	static $tables = array();

	if (!isset($tables[$table])) {
		if (mysql_get_server_info() > '4.1') {
			$query = @mysql_query("SHOW FULL COLUMNS FROM {$discuz_tablepre}$table");
		} else {
			$query = @mysql_query("SHOW COLUMNS FROM {$discuz_tablepre}$table");
		}
		while ($field = @mysql_fetch_assoc($query)) {
			$tables[$table][$field['Field']] = $field;
		}
	}

	return $tables[$table];
}

function runquery($queries) { //执行sql语句
	global $tablepre, $whereis;
	$sqlquery      = splitsql(str_replace(array(' cdb_', ' {tablepre}', ' `cdb_'), array(' ' . $tablepre, ' ' . $tablepre, ' `' . $tablepre), $queries));
	$affected_rows = 0;
	foreach ($sqlquery as $sql) {
		$sql = syntablestruct(trim($sql), $my_version > '4.1', $dbcharset);
		if (trim($sql) != '') {
			mysql_query(stripslashes($sql));
			if ($sqlerror = mysql_error()) {
				break;
			} else {
				$affected_rows += intval(mysql_affected_rows());
			}
		}
	}
	if (strpos($queries, 'seccodestatus') && $whereis == 'is_dz') {
		dz_updatecache();
	}
	if (strpos($queries, 'bbclosed') && $whereis == 'is_dz') {
		dz_updatecache();
	}
	if (strpos($queries, 'template') && $whereis == 'is_uch') {
		uch_updatecache();
	}
	if (strpos($queries, 'seccode_login') && $whereis == 'is_uch') {
		uch_updatecache();
	}
	if (strpos($queries, 'close') && $whereis == 'is_uch') {
		uch_updatecache();
	}
	errorpage($sqlerror ? $sqlerror : "数据库升级成功,影响行数: &nbsp;$affected_rows", '数据库升级');

	if (strpos($queries, 'settings') && $whereis == 'is_dz') {
		require_once './include/cache.func.php';
		updatecache('settings');
	}
}

function runquery_html() { //输出快速设置的所有选项
	global $whereis, $tablepre;
	echo "<h4>快速设置(SQL)</h4>
		<form method=\"post\" action=\"uctools.php?action=all_runquery\">
		<h5>请下拉选择程序内置的快速设置</h4>
		<font color=red>提示：</font>也可以自己书写SQL执行，不过请确保您知道该SQL的用途，以免造成不必要的损失.<br/><br/>";
	if ($whereis == 'is_uc') {
		echo "<select name=\"queryselect\" onChange=\"queries.value = this.value\">
			<option value = ''>可选择TOOLS内置升级语句</option>
			<option value = \"TRUNCATE TABLE " . $tablepre . "notelist;\">清空通知列表</option>
			</select>";
	}
	echo "<br />
			<br /><textarea name=\"queries\">$queries</textarea><br />
			<input type=\"submit\" name=\"sqlsubmit\" value=\"提 &nbsp; 交\">
			</form>";
}

function topattern_array($source_array) { //将数组正则化
	$source_array = preg_replace("/\{(\d+)\}/", ".{0,\\1}", $source_array);
	foreach ($source_array as $key => $value) {
		$source_array[$key] = '/' . $value . '/i';
	}

	return $source_array;
}

function all_setadmin_set($tablepre, $whereis) { //重设管理员根据程序生成各种变量
	global $ss_version, $dz_version, $sql_findadmin, $sql_select, $sql_update, $sql_rspw, $secq, $rspw, $username, $uid;
	if ($whereis == 'is_uc') {
		$secq = 0;
		$rspw = 1;
	}
}

function datago_output($whereis) {
	global $dbhost, $dbuser, $dbpw, $dbname, $dbcfg;
	$charsets = array('gbk', 'latin1', 'utf8');
	$options  = "<option value=''>";
	foreach ($charsets as $value) {
		$options .= "<option value=\"$value\">$value";
	}
	echo '<h5>数据库编码转换</h5>';
	echo '<form method=get action=uctools.php?action=datago><table>
		<tbody>
		<input name=action type=hidden value=datago>
		<tr><th width=20%>源数据库</th><td><input class=textinput name=fromdbname value=' . $dbname . '></input>&nbsp;&nbsp;默认为tools所在程序的数据库,如果不知道其作用请不要修改</td></tr>
		<tr><th width=20%>目的编码</th><td><select name=todbcharset>' . $options . '</select>&nbsp;&nbsp;转换允许：\'latin1\'<=>\'gbk\',\'gbk\'<=>\'utf8\'</td></tr></tbody></table>
		<input name=submit type=submit value=转换></input>
		</form>';
}

function do_datago($mysql, $tableno, $do, $start, $limit) {
	global $whereis, $dbhost, $dbuser, $dbpw, $tablepre, $fromdbname, $todbcharset, $dbcfg, $dbcharset;
	$allowcharset = array('latin1' => 'gbk', 'gbk' => 'utf8', 'utf8' => 'latin1');
	$tablename    = 'Tables_in_' . strtolower($fromdbname) . ' (' . $tablepre . '%)';
	$mysql        = mysql_connect($dbhost, $dbuser, $dbpw);
	mysql_select_db($fromdbname);
	mysql_query("SET sql_mode=''");
	$query = mysql_query('SHOW TABLES LIKE \'' . $tablepre . '%\'');
	while ($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
		$tablearray[] = $t[$tablename];
	}
	$table     = $tablearray["$tableno"];
	$query     = mysql_query('SHOW TABLE STATUS LIKE ' . '\'' . $table . '\'');
	$tableinfo = array();

	while ($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
		$charset        = explode('_', $t['Collation']);
		$t['Collation'] = $charset[0];
		$tableinfo      = $t;
	}
	if ($allowcharset[$tableinfo['Collation']] != $todbcharset && $allowcharset[$todbcharset] != $tableinfo['Collation']) {
		if (strpos($tableinfo['Name'], $todbcharset) == 0) {
			$table = '';
		} else {
			echo "<h4>$title</h4><br><br><table><tr><th>提示信息</th></tr><tr><td>$tableinfo[Name] 表数据库编码出错</td></tr></table>";
			exit;
		}
	}
	mysql_query("SET NAMES '$tableinfo[Collation]'");

	if ($do == 'create') {
		$tablecreate = array();
		foreach ($tablearray as $key => $value) {
			$query = mysql_query("SHOW CREATE TABLE $value");
			while ($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
				$t['Create Table'] = str_replace($tablepre, $whereis . '_', $t['Create Table']);
				$t['Create Table'] = str_replace("$tableinfo[Collation]", "$todbcharset", $t['Create Table']);
				$t['Create Table'] = str_replace($whereis . '_', $todbcharset . $whereis . '_', $t['Create Table']);
				$t['Table']        = str_replace($tablepre, $todbcharset . $whereis . '_', $t['Table']);
				$tablecreate[]     = $t;
			}
		}
		mysql_query('SET NAMES \'' . $todbcharset . '\'');
		if (mysql_get_server_info() > '5.0') {
			mysql_query("SET sql_mode=''");
		}
		foreach ($tablecreate as $key => $value) {
			mysql_query("DROP TABLE IF EXISTS `$value[Table]`");
			mysql_query($value['Create Table']);
			$count++;
		}
		$toolstip .= '所有的表创建完成，数据库共有 ' . $count . ' 个表！<br>';
		show_tools_message($toolstip, "uctools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&submit=%D7%AA%BB%BB");

	} elseif ($do == 'data') {
		$count    = 0;
		$data     = array();
		$newtable = str_replace($tablepre, $todbcharset . $whereis . '_', $table);
		if ($table) {
			mysql_query("SET NAMES '$tableinfo[Collation]'");
			$query = mysql_query("SELECT * FROM $table LIMIT $start,$limit");

			while ($t = mysql_fetch_array($query, MYSQL_ASSOC)) {
				$data[] = $t;
			}
			unset($t);
			$todbcharset2 = $todbcharset;
			if ($tableinfo['Collation'] == 'utf8' || $todbcharset == 'utf8') {
				$todbcharset2 = $tableinfo['Collation'];
			}
			mysql_query('SET NAMES \'' . $todbcharset2 . '\'');
			if (mysql_get_server_info() > '5.0') {
				mysql_query("SET sql_mode=''");
			}
			if ($start == 0) {
				mysql_query("TRUNCATE TABLE $newtable");
			}

			foreach ($data as $key => $value) {
				$sql = '';
				foreach ($value as $tokey => $tovalue) {
					$tovalue = addslashes($tovalue);
					$sql     = $sql ? $sql . ",'" . $tovalue . "'" : "'" . $tovalue . "'";
				}
				mysql_query("INSERT INTO $newtable VALUES($sql)") or mysql_errno();
				$count++;
			}
			if ($count == $limit) {
				$start += $count;
				show_tools_message("正在转移 $table 表的从 $start 条记录开始的后 $limit 条记录", "uctools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&tableno=$tableno&start=$start&submit=%D7%AA%BB%BB");
			} else {
				$tableno++;
				show_tools_message("正在转移 $table 表的从 $start 条记录开始的后 $limit 条记录", "uctools.php?action=datago&do=data&fromdbname=$fromdbname&todbcharset=$todbcharset&tableno=$tableno&submit=%D7%AA%BB%BB", $time = '1000');
			}
		} elseif ($dbcharset == 'latin1' || $todbcharset == 'latin1') {
			echo "<div class=\"specialdiv2\" id=\"serialize\">转换提示：<ul>
				</ul></div>";
			echo '<script>$("serialize").innerHTML+="<li>转换完成！转换后的数据库前缀为：<font color=red>' . $todbcharset . $whereis . '_ </font></li>";
				$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
		} else {
			$toolstip = '数据编码转换完毕，修复序列化数据。';
			show_tools_message($toolstip, "uctools.php?action=datago&do=serialize&fromdbname=$fromdbname&todbcharset=$todbcharset&submit=%D7%AA%BB%BB");
		}

	} elseif ($do == 'serialize' && $dbcharset != 'latin1' && $todbcharset != 'latin1') {
		if ($whereis == 'is_ss') {
			$a = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
			foreach ($a as $num) {
				mysql_query("TRUNCATE TABLE " . $todbcharset . $whereis . '_' . "cache_" . $num);
			}
		}
		$arr   = getlistarray($whereis, 'datago');
		$limit = '3000';
		echo "<div class=\"specialdiv2\" id=\"serialize\">转换提示：<ul>
					</ul></div>";
		foreach ($arr as $field) {
			$stable = $todbcharset . $whereis . '_' . $field[0];
			$sfield = $field[1];
			$sid    = $field[2];
			$query  = mysql_query("SELECT $sid,$sfield FROM $stable ORDER BY $sid DESC LIMIT $limit");
			while ($values = mysql_fetch_array($query, MYSQL_ASSOC)) {
				$data = $values[$sfield];
				$id   = $values[$sid];
				$data = preg_replace_callback('/s:([0-9]+?):"([\s\S]*?)";/', '_serialize', $data);
				$data = taddslashes($data);
				if (mysql_query("update `$stable` set `$sfield`='$data' where `$sid`='$id'")) {
					$toolstip = $stable . ' 表的 ' . $sid . ' 为 ' . $id . ' 的 ' . $sfield . ' 字段，修复成功<br/>';
				} else {
					$toolstip = $stable . ' 表的 ' . $sid . ' 为 ' . $id . ' 的 ' . $sfield . ' 字段，<font color=red>修复失败</font><br/>';
				}
				echo '<script>$("serialize").innerHTML+="' . $toolstip . '";
					$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
			}
		}
		mysql_close($mysql);
		echo '<script>$("serialize").innerHTML+="<li>转换完成！请检查修复记录。转换后的数据库前缀为：<font color=red>' . $todbcharset . $whereis . '_ </font></li>";
			$("serialize").scrollTop=$("serialize").scrollHeight;</script>';
	}
}

function _config_form($title = '', $varname = '', $end = '1') {
	global $$varname;
	$ucapi = UC_API;
	$ucip  = UC_IP;
	$form  = '';
	$form .= "<th width=20%>$title</th>";
	if ($$varname || isset($$varname)) {
		$form .= "<td><input class=textinput name=" . $varname . "2 value=" . $$varname . "></input></td>";
	} else {
		$form .= "<td></td>";
	}
	if ($end == '1') {
		$form .= '';
	} elseif ($end == '2') {
		$form .= '</tr>';
	} elseif ($end == '3') {
		$form .= '</tr><tr>';
	}
	echo $form;
}

function writefile($filename, $writetext, $openmod = 'w') {
	if (@$fp = fopen($filename, $openmod)) {
		flock($fp, 2);
		fwrite($fp, $writetext);
		fclose($fp);

		return TRUE;
	} else {
		return FALSE;
	}
}

function xml2array($xml) {
	$arr = xml_unserialize($xml, 1);
	preg_match('/<error errorCode="(\d+)" errorMessage="([^\/]+)" \/>/', $xml, $match);
	$arr['error'] = array('errorcode' => $match[1], 'errormessage' => $match[2]);

	return $arr;
}

function getbakurl($whereis, $action) {
	if ($whereis != 'is_uc') {
		require_once './uc_client/client.php';
		require_once './uc_client/model/base.php';
	} else {
		define('IN_UC', TRUE);
		define('UC_ROOT', './');
		require_once './model/base.php';
	}

	$base   = new base();
	$salt   = substr(uniqid(rand()), -6);
	$action = !empty($action) ? $action : 'export';
	$url    = 'http://' . $_SERVER['HTTP_HOST'] . str_replace('uctools.php', 'api/dbbak.php', $_SERVER['PHP_SELF']);
	if ($whereis == 'is_dz') {
		$apptype = 'discuz';
	} elseif ($whereis == 'is_uc') {
		$apptype = 'ucenter';
	} elseif ($whereis == 'is_uch') {
		$apptype = 'uchome';
	} elseif ($whereis == 'is_ss') {
		$apptype = 'supesite';
	}
	$url .= '?apptype=' . $apptype;
	$code = $base->authcode('&method=' . $action . '&time=' . time(), 'ENCODE', UC_KEY);
	$url .= '&code=' . urlencode($code);

	return $url;
}

function dobak($url, $num = '1') {
	global $whereis;
	$num    = !empty($num) ? $num : '0';
	$return = file_get_contents($url);
	if ($whereis != 'is_uc') {
		require_once './uc_client/lib/xml.class.php';
	} else {
		require_once './lib/xml.class.php';
	}
	$arr = xml2array($return);

	if ($arr['error']['errormessage'] == 'explor_success') {
		echo "<div class=\"specialdiv\">备份提示：<ul>
			<li>>>>>>>>>备份完成<<<<<<<<</li>
			<li>>>>>>>>>共：" . $num . "个文件<<<<<<<<</li>
			</ul></div>";
	} else {
		$num++;
		echo "<div class=\"specialdiv\">备份提示：<ul>
			<li>" . $arr['fileinfo']['file_name'] . "......" . $arr['error']['errormessage'] . "</li>
			</ul></div>";
	}
	if ($arr['nexturl']) {
		$url = './uctools.php?action=all_backup&nexturl=' . urlencode($arr['nexturl']) . '&num=' . $num;
		show_tools_message($arr['fileinfo']['file_name'], $url, $time = 2000);
	}
}

function getgpc($k, $var = 'G') {
	switch ($var) {
		case 'G':
			$var = & $_GET;
			break;
		case 'P':
			$var = & $_POST;
			break;
		case 'C':
			$var = & $_COOKIE;
			break;
		case 'R':
			$var = & $_REQUEST;
			break;
	}
	return isset($var[$k]) ? $var[$k] : NULL;
}

function getlistarray($whereis, $type) {
	global $dz_version, $ss_version, $uch_version;
	if ($whereis == 'is_dz' && $dz_version >= '710') {
		if ($type == 'datago') {
			$list = array(array('advertisements', 'parameters', 'advid'), array('request', 'value', 'variable'), array('settings', 'value', 'variable'),);
		}
	} elseif ($whereis == 'is_uch' && $uch_version >= '15') {
		if ($type == 'datago') {
			$list = array(array('ad', 'adcode', 'adid'), array('blogfield', 'tag', 'blogid'), array('blogfield', 'related', 'blogid'), array('feed', 'title_data', 'feedid'), array('feed', 'body_data', 'feedid'), array('share', 'body_data', 'sid'),);
		}
	} elseif ($whereis == 'is_uc') {
		if ($type == 'datago') {
			$list = array(array('feed', 'title_data', 'feedid'), array('feed', 'body_data', 'feedid'), array('settings', 'v', 'k'),);
		}
	} elseif ($whereis == 'is_ss' && $ss_version >= 70) {
		if ($type == 'datago') {
			$list = array(array('ads', 'parameters', 'adid'), array('blocks', 'blocktext', 'blockid'),);
		}
	}

	return $list;
}

function _serialize($str) {
	global $dbcharset, $todbcharset;
	$charset       = $dbcharset == 'utf8' ? 'utf-8' : $dbcharset;
	$tempdbcharset = $todbcharset == 'utf8' ? 'utf-8' : $todbcharset;
	$charset       = strtoupper($charset);
	$tempdbcharset = strtoupper($tempdbcharset);
	$temp          = iconv($charset, $tempdbcharset, $str[2]);
	$l             = strlen($temp);

	return 's:' . $l . ':"' . $str[2] . '";';
}

function infobox($str, $link) {
	if ($link) {
		$button = "<input class='button' type='submit' onclick=\"location.href='" . $link . "'\" value='开始' name='submit'/>";
	}
	echo "<div class='infobox'><p>$str</p>$button</div>";
}

//检查数据表
function checktable($table, $loops = 0, $doc) {
	global $db, $nohtml, $simple, $counttables, $oktables, $errortables, $rapirtables;
	$query = mysql_query("show create table $table");
	if ($createarray = mysql_fetch_array($query)) {
		if (strpos($createarray[1], 'TYPE=HEAP')) {
			$counttables--;

			return;
		}
	}
	$result = mysql_query("CHECK TABLE $table");
	if (!$result) {
		$counttables--;

		return;
	}
	$message = "\n>>>>>>>>>>>>>Checking Table $table\r\n---------------------------------\r\n";
	@writefile($doc, $message, 'a');
	$error = 0;
	while ($r = mysql_fetch_row($result)) {
		if ($r[2] == 'error') {
			if ($r[3] == "The handler for the table doesn't support check/repair") {
				$r[2] = 'status';
				$r[3] = 'This table does not support check/repair/optimize';
				unset($bgcolor);
				$nooptimize = 1;
			} else {
				$error   = 1;
				$bgcolor = 'red';
				unset($nooptimize);
			}
			$view = '错误';
			$errortables += 1;
		} else {
			unset($bgcolor);
			unset($nooptimize);
			$view = '正常';
			if ($r[3] == 'OK') {
				$oktables += 1;
			} elseif ($r[3] == 'The storage engine for the table doesn\'t support check') {
				$oktables += 1;
			}
		}
		$message = "$r[0] | $r[1] | $r[2] | $r[3]\r\n";
		@writefile($doc, $message, 'a');
	}
	if ($error) {
		$message = ">>>>>>>>正在修复中 / Repairing Table $table\r\n";
		@writefile($doc, $message, 'a');
		$result2 = mysql_query("REPAIR TABLE $table");
		while ($r2 = mysql_fetch_row($result2)) {
			if ($r2[3] == 'OK') {
				$bgcolor = 'blue';
				$rapirtables += 1;
			} else {
				unset($bgcolor);
			}
			$message = "$r2[0] | $r2[1] | $r2[2] | $r2[3]\r\n";
			@writefile($doc, $message, 'a');
		}
	}
	if (($result2[3] == 'OK' || !$error) && !$nooptimize) {
		$message = ">>>>>>>>>>>>>Optimizing Table $table\r\n";
		@writefile($doc, $message, 'a');
		$result3 = mysql_query("OPTIMIZE TABLE $table");
		$error   = 0;
		while ($r3 = mysql_fetch_row($result3)) {
			if ($r3[2] == 'error') {
				$error   = 1;
				$bgcolor = 'red';
			} else {
				unset($bgcolor);
			}
			$message = "$r3[0] | $r3[1] | $r3[2] | $r3[3]\r\n\r\n";
			@writefile($doc, $message, 'a');
		}
	}
	if ($error && $loops) {
		checktable($table, ($loops - 1), $doc);
	}
}

?>