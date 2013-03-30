<?php


if (@!$_REQUEST['submit']) {
	$_REQUEST['submit'] = '';
}
if (@!$_REQUEST['operation']) {
	$_REQUEST['operation'] = '';
}
if ($_REQUEST['operation'] == 'curlrequest') {
	//接收POST參數的URL
	$url = 'http://www.google.com';

//POST參數,在這個陣列裡,索引是name,值是value,沒有限定組數
	$postdata = array(
		'post_name'=>'post_value','acc'=>'hsin','nick'=>'joe');

//函式回覆的值就是取得的內容
	$result = sendpost($url,$postdata);

	function sendpost($url, $data){
//先解析url 取得的資訊可以看看http://www.php.net/parse_url
		$url = parse_url($url);
		$url_port = $url['port']==''?(($url['scheme']=='https')?443:80):$url['port'];
		if(!$url) return "couldn't parse url";

//對要傳送的POST參數作處理
		$encoded = "";
		while(list($k,$v)=each($data)){
			$encoded .= ($encoded?'&':'');
			$encoded .= rawurlencode($k)."=".rawurlencode($v);
		}

//開啟一個socket
		$fp = fsockopen($url['host'],$url_port);
		if(!$fp) return "Failed to open socket to ".$url['host'];

//header的資訊
		fputs($fp,"Host: ".$url['host']."\n");
		fputs($fp,'POST '.$url['path'].($url['query']?'?'.$url['query']:'')." HTTP/1.0\r\n");
		fputs($fp,"Content-type: application/x-www-form-urlencoded\n");
		fputs($fp,"Content-length: ".strlen($encoded)."\n");
		fputs($fp,"Connection: close\n\n");
		fputs($fp,$encoded."\n");

//取得回應的內容
		$line = fgets($fp,1024);
		if(!eregi("^HTTP/1.. 200", $line)) return;
		$results = "";
		$inheader = 1;
		while(!feof($fp)){
			$line = fgets($fp,2048);
			if($inheader&&($line == "\n" || $line == "\r\n")){
				$inheader = 0;
			}elseif(!$inheader){
				$results .= $line;
			}
		}

		fclose($fp);
		return $results;
	}
	/*function sendpost($url, $data) {
		//先解析url
		$url = parse_url($url);
		$url_port = "80";
		if (!$url) return "couldn't parse url";
		//将参数拼成URL key1=value1&key2=value2 的形式
		$encoded = "";
		while (list($k, $v) = each($data)) {
			$encoded .= ($encoded ? '&' : '');
			$encoded .= rawurlencode($k) . "=" . rawurlencode($v);
		}
		$len = strlen($encoded);
		//拼上http头
		$out = "POST " . $url['path'] . " HTTP/1.1\r\n";
		$out .= "Host:" . $url['host'] . "\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n";
		$out .= "\r\n";
		$out .= $encoded . "\r\n";
		//打开一个sock
		$fp = @fsockopen($url['host'], $url_port);
		var_dump($fp);
		$line = "";
		if (!$fp) {
			echo "$errstr($errno)\n";
		} else {
			fwrite($fp, $out);
			while (!feof($fp)) {
				$line .= fgets($fp, 2048);
			}
			//去掉头文件
			if ($line) {
				$body = cript：r($line, "\r\n\r\n");
			 $body = substr($body, 4, strlen($body));
			 $line = $body;
		  }
			fclose($fp);
			return $line;
		}
	}
	$arrVal["eee"] = "Hello";
	$arrVal["ee"] = "Sorry";
	$reuslt = "";
	header("Location: http://127.0.0.1/manage/sync.php");
	echo 'aksdjhlasdjk';
	$reuslt = sendpost("http://127.0.0.1/manage/sync.php?operation=pulltolocal", $arrVal);
	var_dump($reuslt);*/

} else {


	require_once 'sync/functions.php';
	require_once 'sync/pclzip.class.php';
	$localdir = './';

	define('IGNORE_FILE_LIST', implode('|', array(
		'.git*'
	, '*.md'
	, '*.markdown'
	, '.htaccess'
	, 'Thumbs.db'
	)));
	$ignores = addcslashes(IGNORE_FILE_LIST, '.');
	$ignores = strtr($ignores, array('?' => '.?', '*' => '.*'));
	$ignores = '/^(' . $ignores . ')$/i';

	$head =  <<<HTM
<!DocType HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body{ font-size:12px; }
.exploreritem{ float:left; width:128px; height:128px; border:2px solid #777; text-aligin:center;  margin:7px; font-size:10px; }
.exploreritem .submit{ width: 100%; height: 80px; background-repeat: no-repeat; background-position: center center; border: none; cursor:pointer; line-height:11; }

.archive{background-image:url(../WebFTP/Static/icons/big/archive.png);}
.asp{background-image:url(../WebFTP/Static/icons/big/asp.png);}
.audio{background-image:url(../WebFTP/Static/icons/big/audio.png);}
.authors{background-image:url(../WebFTP/Static/icons/big/authors.png);}
.bin{background-image:url(../WebFTP/Static/icons/big/bin.png);}
.bmp{background-image:url(../WebFTP/Static/icons/big/bmp.png);}
.c{background-image:url(../WebFTP/Static/icons/big/c.png);}
.calc{background-image:url(../WebFTP/Static/icons/big/calc.png);}
.cd{background-image:url(../WebFTP/Static/icons/big/cd.png);}
.copying{background-image:url(../WebFTP/Static/icons/big/copying.png);}
.cpp{background-image:url(../WebFTP/Static/icons/big/cpp.png);}
.css{background-image:url(../WebFTP/Static/icons/big/css.png);}
.deb{background-image:url(../WebFTP/Static/icons/big/deb.png);}
.default{background-image:url(../WebFTP/Static/icons/big/default.png);}
.doc{background-image:url(../WebFTP/Static/icons/big/doc.png);}
.draw{background-image:url(../WebFTP/Static/icons/big/draw.png);}
.eps{background-image:url(../WebFTP/Static/icons/big/eps.png);}
.exe{background-image:url(../WebFTP/Static/icons/big/exe.png);}
.floder-home{background-image:url(../WebFTP/Static/icons/big/floder-home.png);}
.floder-open{background-image:url(../WebFTP/Static/icons/big/floder-open.png);}
.floder-page{background-image:url(../WebFTP/Static/icons/big/floder-page.png);}
.floder-parent{background-image:url(../WebFTP/Static/icons/big/floder-parent.png);}
.floder{background-image:url(../WebFTP/Static/icons/big/floder.png);}
.gif{background-image:url(../WebFTP/Static/icons/big/gif.png);}
.gzip{background-image:url(../WebFTP/Static/icons/big/gzip.png);}
.h{background-image:url(../WebFTP/Static/icons/big/h.png);}
.hpp{background-image:url(../WebFTP/Static/icons/big/hpp.png);}
.html{background-image:url(../WebFTP/Static/icons/big/html.png);}
.ico{background-image:url(../WebFTP/Static/icons/big/ico.png);}
.image{background-image:url(../WebFTP/Static/icons/big/image.png);}
.install{background-image:url(../WebFTP/Static/icons/big/install.png);}
.java{background-image:url(../WebFTP/Static/icons/big/java.png);}
.jpg{background-image:url(../WebFTP/Static/icons/big/jpg.png);}
.js{background-image:url(../WebFTP/Static/icons/big/js.png);}
.log{background-image:url(../WebFTP/Static/icons/big/log.png);}
.makefile{background-image:url(../WebFTP/Static/icons/big/makefile.png);}
.package{background-image:url(../WebFTP/Static/icons/big/package.png);}
.pdf{background-image:url(../WebFTP/Static/icons/big/pdf.png);}
.php{background-image:url(../WebFTP/Static/icons/big/php.png);}
.playlist{background-image:url(../WebFTP/Static/icons/big/playlist.png);}
.png{background-image:url(../WebFTP/Static/icons/big/png.png);}
.pres{background-image:url(../WebFTP/Static/icons/big/pres.png);}
.psd{background-image:url(../WebFTP/Static/icons/big/psd.png);}
.py{background-image:url(../WebFTP/Static/icons/big/py.png);}
.rar{background-image:url(../WebFTP/Static/icons/big/rar.png);}
.rb{background-image:url(../WebFTP/Static/icons/big/rb.png);}
.readme{background-image:url(../WebFTP/Static/icons/big/readme.png);}
.rpm{background-image:url(../WebFTP/Static/icons/big/rpm.png);}
.rss{background-image:url(../WebFTP/Static/icons/big/rss.png);}
.rtf{background-image:url(../WebFTP/Static/icons/big/rtf.png);}
.script{background-image:url(../WebFTP/Static/icons/big/script.png);}
.source{background-image:url(../WebFTP/Static/icons/big/source.png);}
.sql{background-image:url(../WebFTP/Static/icons/big/sql.png);}
.tar{background-image:url(../WebFTP/Static/icons/big/tar.png);}
.tex{background-image:url(../WebFTP/Static/icons/big/tex.png);}
.text{background-image:url(../WebFTP/Static/icons/big/text.png);}
.tiff{background-image:url(../WebFTP/Static/icons/big/tiff.png);}
.unknown{background-image:url(../WebFTP/Static/icons/big/unknown.png);}
.vcal{background-image:url(../WebFTP/Static/icons/big/vcal.png);}
.video{background-image:url(../WebFTP/Static/icons/big/video.png);}
.xml{background-image:url(../WebFTP/Static/icons/big/xml.png);}
.zip{background-image:url(../WebFTP/Static/icons/big/zip.png);}
</style>
</head>
<body>
$ignores
	<br /><br /><hr />
HTM;



	if ($_REQUEST['operation'] == 'md5checkedsync') {

		if (!@$_REQUEST['delete']) {
			$_REQUEST['delete'] = array();
		}
		foreach ($_REQUEST['delete'] as $delete) {
			unlink(u2g("$delete"));
			echo '删除文件' . $delete . ';<br />';
		}
		if (!@$_REQUEST['upload']) {
			$_REQUEST['upload'] = array();
		}
		$files = array();
		foreach ($_REQUEST['upload'] as $file) {
			$files[] = u2g($file);
		}
		$Zip = new PclZip('./package.zip');
		//$_REQUEST['includefiles'] = array('./xwb.php', './userapp.php');
		$Zip->create($files);
		$list = $Zip->listContent();
		if ($list) {
			$fold = 0;
			$fil = 0;
			$tot_comp = 0;
			$tot_uncomp = 0;
			foreach ($list as $key => $val) {
				if ($val['folder'] == '1') {
					++$fold;
				} else {
					++$fil;
					$tot_comp += $val['compressed_size'];
					$tot_uncomp += $val['size'];
				}
			}
			$message = '<font color="green">压缩目标文件：</font><font color="red"> ' . g2u(basename($zipname)) . '</font><br />';
			$message .= '<font color="green">压缩文件详情：</font><font color="red">共' . $fold . ' 个目录，' . $fil . ' 个文件</font><br />';
			$message .= '<font color="green">压缩文档大小：</font><font color="red">' . dealsize($tot_comp) . '</font><br />';
			$message .= '<font color="green">解压文档大小：</font><font color="red">' . dealsize($tot_uncomp) . '</font><br />';
			$message .= '<font color="green">压缩执行耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
			echo <<<FOM
<form action="http://localhost/manage/sync.php?operation=push" method="post" enctype="multipart/form-data">
<label for="file">Filename:</label>
<input type="file" name="file" id="file" />
<br />
<input type="submit" name="submit" value="Submit" />
</form>
FOM;

		} else {
			exit ($localdir . "package.zip 不能写入,请检查路径或权限是否正确.<br>");
		}
	} elseif ($_REQUEST['operation'] == 'push') {
		while(list($k,$v) = each($_POST)){
			echo "<h1>".$k."=".$v."<h1><br />";
		}
		if ($_FILES["file"]["error"] > 0) {
			echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
		} else {
			echo "Upload: " . $_FILES["file"]["name"] . "<br />";
			echo "Type: " . $_FILES["file"]["type"] . "<br />";
			echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
			echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";

			if (file_exists("upload/" . $_FILES["file"]["name"])) {
				echo $_FILES["file"]["name"] . " already exists. ";
			} else {
				move_uploaded_file($_FILES["file"]["tmp_name"],
					"./" . $_FILES["file"]["name"]);
				echo "Stored in: " . "./" . $_FILES["file"]["name"];

				$path = './';
				$name = 'package.zip';
				$remove = 0;
				$unzippath = './';
				if (file_exists($path . $name) && is_file($path . $name)) {
					$Zip = new PclZip($path . $name);
					$result = $Zip->extract($path . (('./' == $unzippath || '。/' == @$_POST['unzippath']) ? '' : $unzippath), $remove);
					if ($result) {
						exit('skl');
						$statusCode = 200;
						$list = $Zip->listContent();
						$fold = 0;
						$fil = 0;
						$tot_comp = 0;
						$tot_uncomp = 0;
						foreach ($list as $key => $val) {
							if ($val['folder'] == '1') {
								++$fold;
							} else {
								++$fil;
								$tot_comp += $val['compressed_size'];
								$tot_uncomp += $val['size'];
							}
						}
						$message = '<font color="green">解压目标文件：</font><font color="red"> ' . g2u($name) . '</font><br />';
						$message .= '<font color="green">解压文件详情：</font><font color="red">共' . $fold . ' 个目录，' . $fil . ' 个文件</font><br />';
						$message .= '<font color="green">压缩文档大小：</font><font color="red">' . dealsize($tot_comp) . '</font><br />';
						$message .= '<font color="green">解压文档大小：</font><font color="red">' . dealsize($tot_uncomp) . '</font><br />';
						$message .= '<font color="green">解压总计耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
					} else {
						exit('error');
						$statusCode = 300;
						$message .= '<font color="blue">解压失败：</font><font color="red">' . $Zip->errorInfo(true) . '</font><br />';
						$message .= '<font color="green">执行耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
					}
				}
			}
		}
	}elseif($_REQUEST['operation']=='calltopick'){
		$file = './package.zip';

		if (file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();
			readfile($file);
			exit;
		}
	} elseif ($_REQUEST['operation'] == 'unzip') {
		$path = './';
		$name = 'package.zip';
		$remove = 0;
		$unzippath = './';
		if (file_exists($path . $name) && is_file($path . $name)) {
			$Zip = new PclZip($path . $name);
			$result = $Zip->extract($path . (('./' == $unzippath || '。/' == @$_POST['unzippath']) ? '' : $unzippath), $remove);
			if ($result) {
				exit('skl');
				$statusCode = 200;
				$list = $Zip->listContent();
				$fold = 0;
				$fil = 0;
				$tot_comp = 0;
				$tot_uncomp = 0;
				foreach ($list as $key => $val) {
					if ($val['folder'] == '1') {
						++$fold;
					} else {
						++$fil;
						$tot_comp += $val['compressed_size'];
						$tot_uncomp += $val['size'];
					}
				}
				$message = '<font color="green">解压目标文件：</font><font color="red"> ' . g2u($name) . '</font><br />';
				$message .= '<font color="green">解压文件详情：</font><font color="red">共' . $fold . ' 个目录，' . $fil . ' 个文件</font><br />';
				$message .= '<font color="green">压缩文档大小：</font><font color="red">' . dealsize($tot_comp) . '</font><br />';
				$message .= '<font color="green">解压文档大小：</font><font color="red">' . dealsize($tot_uncomp) . '</font><br />';
				$message .= '<font color="green">解压总计耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
			} else {
				exit('error');
				$statusCode = 300;
				$message .= '<font color="blue">解压失败：</font><font color="red">' . $Zip->errorInfo(true) . '</font><br />';
				$message .= '<font color="green">执行耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
			}
		}
	} elseif ($_REQUEST['operation'] == 'md5') {
//	if ($_FILES["file"]["error"] > 0) {
//		echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
//	} else {
//		echo "Upload: " . $_FILES["file"]["name"] . "<br />";
//		echo "Type: " . $_FILES["file"]["type"] . "<br />";
//		echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
//		echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";
//
//		if (file_exists("upload/" . $_FILES["file"]["name"])) {
//			echo $_FILES["file"]["name"] . " already exists. ";
//		} else {
//			move_uploaded_file($_FILES["file"]["tmp_name"], "./data/custommd5/" . $_FILES["file"]["name"]);
//			echo "Stored in: " . "./data/custommd5/" . $_FILES["file"]["name"];
//		}
//	}
//	echo '<br /><br />';
//	$md5 = file_get_contents("./data/custommd5/" . $_FILES["file"]["name"]);
//	$md5 = explode("\n", trim($md5));
//	$md5 = implode('<br />', $md5);
//	echo $md5;
		echo '<form action="sync.php?operation=aftermd5check" method="post">';
		$ignorelist = file_get_contents('./data/custommd5/ignorelist.txt');
		$ignorelist = explode("\n", trim($ignorelist));
		foreach ($_POST['file'] as $file => $md5) {
//		$item = explode(' *', $item);
			if (in_array($file, $ignorelist)) continue;
			if (file_exists($file)) {
				if (md5_file($file) != $md5) {
					echo <<<HTML
<input type="radio" name="file[$file]" value="ignore" />忽略
<input type="radio" name="file[$file]" value="upload" />上传
<input type="radio" name="file[$file]" value="dnload" />下载
<input type="radio" name="file[$file]" value="delete" />删除
HTML;

					echo '文件：' . ($file) . ';<br />';
				}
			} else {
				echo <<<HTML
<input type="radio" name="file[$file]" value="ignore" />忽略
<input type="radio" name="file[$file]" value="upload" />上传
<input type="radio" name="file[$file]" value="dnload" disabled />下载
<input type="radio" name="file[$file]" value="delete" />删除本地
HTML;
				echo '文件：' . ($file) . '不存在！！<br />';
			}
		}
		echo '<input type="submit" value="submit" name="submit" />';
		echo '</form>';
	} elseif ($_REQUEST['operation'] == 'aftermd5check') {
		$upload = $dnload = $delete = array();
		foreach ($_POST['file'] as $file => $option) {
			switch ($option) {
				case 'ignore':
					$ignorelist = file_get_contents('./data/custommd5/ignorelist.txt');
					if (!in_array($file, explode("\n", $ignorelist))) {
						$fp = fopen('./data/custommd5/ignorelist.txt', 'a');
						fwrite($fp, "\n$file");
						fclose($fp);
					}
					break;
				case 'upload':
					$upload[] = $file;
					break;
				case 'dnload':
					$dnload[] = $file;
					break;
				case 'delete':
					$delete[] = $file;
					break;
			}
		}
		echo '<br />upload:' . implode('<br />upload:', $upload);
		echo '<br />dnload:' . implode('<br />dnload:', $dnload);
		echo '<br />delete:' . implode('<br />delete:', $delete);
		ECHO <<<HTML
<form action="http://localhost/manage/sync.php?operation=md5checkedsync" method="post">
HTML;
		foreach ($upload as $item) {
			echo '<input type="hidden" name="upload[]" value="' . $item . '" />';
		}
		foreach ($delete as $item) {
			echo '<input type="hidden" name="delete[]" value="' . $item . '" />';
		}
		echo <<<HTML
<input type="submit" name="submit" value="submit" />
</form>
HTML;

	} else {
		if (@!$_REQUEST['submit']) {
			if (@$_REQUEST['checkdir'] != '') {
				$localdir = $_REQUEST['currentdir'] . ($_REQUEST['checkdir']);
			}
			echo $head;
			echo <<<HTM
			选择要压缩的文件或目录：<br>
			<form name="myform" method="post" action="$_SERVER[PHP_SELF]">
HTM;
			$fdir = opendir($localdir);
			function checkfiletype($filename) {
				$ext = strrchr($filename, '.');
				$ext = substr($ext, 1);
				switch ($ext) {
					case 'txt':
						$type = 'text';
						break;
					case 'htm':
						$type = 'html';
						break;
					default:
						$type = $ext;
				}
				return $type;
			}

			echo '<div class="exploreritem">';
			echo "<input name='includefiles[]' type='checkbox' value='' disabled /><br />";
			echo "<input type='hidden' name='currentdir' value='$localdir' />";
			echo '<input type="submit" name="submit" class="submit floder-parent" value=".." />';
			echo '</div>';
			while ($file = readdir($fdir)) {
				if ($file == '.' || $file == '..' || preg_match($ignores, $file)) continue;

				echo '<div class="exploreritem">';
				echo "<input name='includefiles[]' type='checkbox' value='$file' /><br />";
				if (is_dir($localdir . $file)) {
					echo '<input type="submit" name="checkdir" class="submit floder-page" value="' . $file . '" />';
				} else {
					echo '<input type="submit" name="submit" class="submit ' . checkfiletype($file) . '" value="' . $file . '" />';
				}
				echo '</div>';
			}
			?>
			<br/>
			<div style="clear:both;">
				<input type='button' value='反选' onclick='selrev();'>
				<input type='button' value='测试' onclick='ssd()'>
				<input type='text' name='list' style="width:400px;"/>
				<input type="submit" name="submit" value="zip">
				<input type="submit" name="submit" value="md5">
			</div>
			<script language='javascript'>
				function selrev() {
					with (document.myform) {
						for (i = 0; i < elements.length; i++) {
							var thiselm = elements[i];
							if (thiselm.name.match(/includefiles\[\]/))    thiselm.checked = !thiselm.checked;
						}
					}
				}
				function ssd() {
					with (document.myform) {
						for (i = 0; i < elements.length; i++) {
							var thiselm = elements[i];
							if (thiselm.name.match(/includefiles\[\]/))    thiselm.indeterminate = !thiselm.indeterminate;
						}
					}
				}
			</script>
			</form>
		<?php
		} elseif ($_REQUEST['submit'] == 'md5') {

//		$fp = fopen('./md5.xml', 'w');
//		fwrite($fp, '');
//		fclose($fp);
//		$fp = fopen('./md5.xml', 'a');
			$hiddenform = '';
			function listfiles($dir = ".") {
				global $sublevel, $localdir, $fp, $ignores, $hiddenform;
				$sub_file_num = 0;
				$dir = preg_replace('/^\.\//i', '', $dir);
				$realdir = $localdir . $dir;
				if (is_file("$realdir")) {
					//fwrite($fp, md5_file($realdir) . ' *' . $dir."\n");
					$hiddenform .= '<input type="hidden" name="file[' . g2u($dir) . ']" value="' . md5_file($realdir) . '" />';
					return 1;
				}

				$handle = opendir("$realdir");
				$sublevel++;
				while ($file = readdir($handle)) {
					if ($file == '.' || $file == '..' || preg_match($ignores, $file)) continue;
					$sub_file_num += listfiles("$dir/$file");
				}
				closedir($handle);
				$sublevel--;
				return $sub_file_num;
			}


			echo "正在校验文件...<br><br>";
			$filenum = 0;
			$sublevel = 0;
			if (!@$_REQUEST['includefiles']) {
				$_REQUEST['includefiles'] = array();
			}
			$list = array_merge(explode(' ', @$_REQUEST['list']), $_REQUEST['includefiles']);


			foreach ($list as $file) {
				$filenum += listfiles($file);
			}

			//$package->createfile();
			//fclose($fp);
			echo "<br>校验完成,共添加 $filenum 个文件.<br>";
			echo <<<FOM
<form action="http://localhost/manage/sync.php?operation=md5" method="post"
enctype="multipart/form-data">
<label for="file">Filename:</label>
$hiddenform
<br />
<input type="submit" name="submit" value="Submit" />
</form>
FOM;
		} elseif ($_REQUEST['submit'] == 'zip') {
			if (!@$_REQUEST['includefiles']) {
				$_REQUEST['includefiles'] = array();
			}
			$files = array_merge(explode(' ', @$_REQUEST['list']), $_REQUEST['includefiles']);
			$Zip = new PclZip('./package.zip');
			//$_REQUEST['includefiles'] = array('./xwb.php', './userapp.php');
			$Zip->create($files);
			$list = $Zip->listContent();
			if ($list) {
				$fold = 0;
				$fil = 0;
				$tot_comp = 0;
				$tot_uncomp = 0;
				foreach ($list as $key => $val) {
					if ($val['folder'] == '1') {
						++$fold;
					} else {
						++$fil;
						$tot_comp += $val['compressed_size'];
						$tot_uncomp += $val['size'];
					}
				}
				$message = '<font color="green">压缩目标文件：</font><font color="red"> ' . g2u(basename($zipname)) . '</font><br />';
				$message .= '<font color="green">压缩文件详情：</font><font color="red">共' . $fold . ' 个目录，' . $fil . ' 个文件</font><br />';
				$message .= '<font color="green">压缩文档大小：</font><font color="red">' . dealsize($tot_comp) . '</font><br />';
				$message .= '<font color="green">解压文档大小：</font><font color="red">' . dealsize($tot_uncomp) . '</font><br />';
				$message .= '<font color="green">压缩执行耗时：</font><font color="red">' . G('_run_start', '_run_end', 6) . ' 秒</font><br />';
				echo $message;
				echo <<<FOM
<form action="http://localhost/manage/sync.php?operation=messagetopick" method="post" enctype="multipart/form-data">
<label for="file">Filename:</label>
<br />
<input type="submit" name="submit" value="Submit" />
</form>
FOM;

			} else {
				exit ($localdir . "package.zip 不能写入,请检查路径或权限是否正确.<br>");
			}
		}
	}
}
?>