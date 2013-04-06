<?php
require_once 'source/kohana/include.php';

$content = 'Hello World';
$content = HTML::anchor('http://kohanaframework.org/', $content);
function debug($var, $val) {
	echo "***DEBUGGING\nVARIABLE: $var\nVALUE:";
	if (is_array($val) || is_object($val) || is_resource($val)) {
		print_r($val);
	} else {
		echo "\n$val\n";
	}
	echo "***\n";
}

//$c = mysql_connect();
$host = $_SERVER["SERVER_NAME"];

//call_user_func_array('debug', array("host", $host));
////call_user_func_array('debug', array("c", $c));
//call_user_func_array('debug', array("_POST", $_POST));
//debug("host", $host);
//debug("_POST", $_POST);
?>
<html>
<head>
	<title>Demo page</title>
</head>
<body>
<?php echo $content; ?>
<hr/>
<?php echo URL::base(); ?>
<hr/>
<?php echo Debug::dump(array(1, 2, 3, 4, 5)); ?>
</body>
</html>