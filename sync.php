<?php

if (@$_REQUEST['operation'] == 'push') {
//	if ((($_FILES["file"]["type"] == "image/gif")
//		|| ($_FILES["file"]["type"] == "image/jpeg")
//		|| ($_FILES["file"]["type"] == "application/octet-stream"))
//		&& (true)
//	) {
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
//	} else {
//		echo "Invalid file";
//	}
} elseif (@$_REQUEST['operation'] == 'unzip') {
	$path    = './';
	$name    = 'package.zip';
	$remove  = 0;
	$unzippath = './';
	if(file_exists($path.$name) && is_file($path.$name)){
		include('pclzip.class.php');
		$Zip = new PclZip($path.$name);
		$result = $Zip->extract($path.(('./' == $unzippath || '。/' == @$_POST['unzippath'])?'':$unzippath), $remove);
		if($result){
			exit('skl');
			$statusCode = 200;
			$list = $Zip->listContent();
			$fold = 0; $fil = 0; $tot_comp = 0; $tot_uncomp = 0;
			foreach($list as $key=>$val){if ($val['folder']=='1') {++$fold;}else{++$fil;$tot_comp += $val['compressed_size'];$tot_uncomp += $val['size'];}}
			$message  = '<font color="green">解压目标文件：</font><font color="red"> '.g2u($name).'</font><br />';
			$message .= '<font color="green">解压文件详情：</font><font color="red">共'.$fold.' 个目录，'.$fil.' 个文件</font><br />';
			$message .= '<font color="green">压缩文档大小：</font><font color="red">'.dealsize($tot_comp).'</font><br />';
			$message .= '<font color="green">解压文档大小：</font><font color="red">'.dealsize($tot_uncomp).'</font><br />';
			$message .= '<font color="green">解压总计耗时：</font><font color="red">'.G('_run_start','_run_end',6).' 秒</font><br />';
		}else{
			exit('error');
			$statusCode = 300;
			$message   .= '<font color="blue">解压失败：</font><font color="red">'.$Zip->errorInfo(true).'</font><br />';
			$message   .= '<font color="green">执行耗时：</font><font color="red">'.G('_run_start','_run_end',6).' 秒</font><br />';
		}
	}

}

?>