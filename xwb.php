<?php
if( is_file( 'xwb/index.php' ) ){
	echo($_GET['m']);
	require 'xwb/index.php';
}else{
	exit('CAN NOT RUN THE PLUGIN!');
}
