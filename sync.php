<?php
echo <<<HTML
<style>
body{
font-size:12px;
}
</style>
HTML;

if (@$_REQUEST['operation'] == 'push') {
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

			require_once 'zip.class.php';
			$unzip = new Unzip();
			$unzip->Extract('package.zip', '.');
			if ($result == -1) {
				echo "<br>文件 $upfile[name] 错误.<br>";
			}
			echo "<br>完成,共建立 $unzip->total_folders 个目录,$unzip->total_files 个文件.<br><br><br>";
		}
	}
} elseif (@$_REQUEST['operation'] == 'unzip') {
	$path = './';
	$name = 'package.zip';
	$remove = 0;
	$unzippath = './';
	if (file_exists($path . $name) && is_file($path . $name)) {
		include('pclzip.class.php');
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
} elseif (@$_REQUEST['operation'] == 'md5') {
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
			move_uploaded_file($_FILES["file"]["tmp_name"], "./data/custommd5/" . $_FILES["file"]["name"]);
			echo "Stored in: " . "./data/custommd5/" . $_FILES["file"]["name"];
		}
	}
	echo '<br /><br />';
	$md5 = file_get_contents("./data/custommd5/" . $_FILES["file"]["name"]);
	$md5 = explode("\n", trim($md5));
//	$md5 = implode('<br />', $md5);
//	echo $md5;
echo '<form action="sync.php?operation=md5checkedsync" method="post">';
	$ignorelist = file_get_contents('./data/custommd5/ignorelist.txt');
	$ignorelist = explode("\n", trim($ignorelist));
	foreach ($md5 as $item) {
		$item = explode(' *', $item);
		if(in_array($item[1], $ignorelist))continue;
		if (file_exists($item[1])) {
			if (md5_file($item[1]) != $item[0]) {
				echo <<<HTML
<input type="radio" name="file[$item[1]]" value="ignore" />忽略
<input type="radio" name="file[$item[1]]" value="upload" />上传
<input type="radio" name="file[$item[1]]" value="dnload" />下载
<input type="radio" name="file[$item[1]]" value="delete" />删除
HTML;

				echo '文件：' . $item[1] . ';<br />';
			}
		} else {
			echo <<<HTML
<input type="radio" name="file[$item[1]]" value="ignore" />忽略
<input type="radio" name="file[$item[1]]" value="upload" />上传
<input type="radio" name="file[$item[1]]" value="dnload" disabled />下载
<input type="radio" name="file[$item[1]]" value="delete" />删除本地
HTML;
			echo '文件：' . $item[1] . '不存在！！<br />';
		}
	}
	echo '<input type="submit" value="submit" name="submit" />';
	echo '</form>';
} elseif (@$_REQUEST['operation'] == 'md5checkedsync') {
	$upload = $dnload = $delete = array();
	foreach($_POST['file'] as $file=>$option){
		switch($option){
			case 'ignore':
				$ignorelist = file_get_contents('./data/custommd5/ignorelist.txt');
				if (!in_array($file, explode("\n", $ignorelist))){
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
	echo '<br />upload:'.implode('<br />upload:', $upload);
	echo '<br />dnload:'.implode('<br />dnload:', $dnload);
	echo '<br />delete:'.implode('<br />delete:', $delete);
ECHO <<<HTML
<form action="http://localhost/manage/sync.php?operation=md5checkedsync" method="post">
HTML;
	foreach($upload as $item){
		echo '<input type="hidden" name="upload[]" value="'.$item.'" />';
	}
	foreach($delete as $item){
		echo '<input type="hidden" name="delete[]" value="'.$item.'" />';
	}
	echo <<<HTML
<input type="submit" name="submit" value="submit" />
</form>
HTML;

}

?>