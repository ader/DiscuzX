<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 13-2-17
 * Time: 下午4:29
 * To change this template use File | Settings | File Templates.
 */



require './source/class/class_core.php';
$discuz = C::app();
$discuz->init();
//print_r(file_get_contents('php://input'));
//print_r($discuz);
error_reporting(E_ALL);
$data = json_decode(file_get_contents('php://input'));
//echo(count($data));
//exit;
foreach($data as $item=>$value){
	//$___item = $___update[1];
	//echo serialize($value->links);
	//print_r('item:'.$___item.'<br />');
	//print_r('value:'.$value);
	//print_r($item,'=>',$value);
	//$value = $data[0];
	DB::insert('vizto_flink', array('title' => $value->name, 'data' => (serialize(($value->links)))));
	//array_push($SQL_ARR, '("'.$value->name.'", '.addslashes(serialize(dstripslashes(array('href'=>$value->links->href,'text'=>$value->links)))).')');
	//DB::query("INSERT INTO ".DB::table('common_admincp_session')." (uid, adminid, panel, ip, dateline, errorcount)
	//VALUES ('{$this->adminuser['uid']}', '{$this->adminuser['adminid']}', '$this->panel', '{$this->core->var['clientip']}', '".TIMESTAMP."', '0')");
}
//$SQL .= implode(',', $SQL_ARR);
//print_r($SQL);
//exit;
?>