
<?php
require_once 'kohana/include.php';

$content = 'Hello World';
$content = HTML::anchor('http://kohanaframework.org/', $content);
?>
<html>
<head>
	<title>Demo page</title>
</head>
<body>
<?php echo $content; ?>
<hr />
<?php echo URL::base(); ?>
<hr />
<?php echo Debug::dump(array(1,2,3,4,5)); ?>
</body>
</html>