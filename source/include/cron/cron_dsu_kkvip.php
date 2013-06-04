<?php
/**
 * [DSU] VIP Cron
 * Copyright 2010-2011, DSU Team. && Kookxiang
 */

if(!defined('IN_DISCUZ')) exit('Access Denied');

include_once libfile('class/vip');
$vip = $vip ? $vip : new vip();
$nowtime = TIMESTAMP;

if($vip->on){
	// 过期处理
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid=v.oldgroup WHERE m.uid=v.uid AND v.exptime<='{$nowtime}' AND m.adminid=0");
	$vip->query("DELETE FROM pre_dsu_vip WHERE exptime<='{$nowtime}'");

	// 成长值处理
	$vip->query("UPDATE pre_dsu_vip SET czz=czz+'{$vip->vars[vip_czzday]}' WHERE exptime>'{$nowtime}'", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET czz=czz+'{$vip->vars[vip_czz_year]}' WHERE year_pay=1 AND exptime>'{$nowtime}'", 'UNBUFFERED');

	// 过期折扣码处理
	$vip->query("DELETE FROM pre_dsu_vip_codes WHERE exptime<='$nowtime'", 'UNBUFFERED');

	// VIP等级处理
	$vip->query("UPDATE pre_dsu_vip SET level='1' WHERE czz<600", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET level='2' WHERE czz<1800 AND czz>600", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET level='3' WHERE czz<3600 AND czz>1800", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET level='4' WHERE czz<6000 AND czz>3600", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET level='5' WHERE czz<10800 AND czz>6000", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip SET level='6' WHERE czz>=10800", 'UNBUFFERED');

	// VIP用户组绑定处理
	$g = $vip->group;
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[1]} WHERE m.uid=v.uid AND v.level='1' AND m.adminid=0", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[2]} WHERE m.uid=v.uid AND v.level='2' AND m.adminid=0", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[3]} WHERE m.uid=v.uid AND v.level='3' AND m.adminid=0", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[4]} WHERE m.uid=v.uid AND v.level='4' AND m.adminid=0", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[5]} WHERE m.uid=v.uid AND v.level='5' AND m.adminid=0", 'UNBUFFERED');
	$vip->query("UPDATE pre_dsu_vip v, pre_common_member m SET m.groupid={$g[6]} WHERE m.uid=v.uid AND v.level='6' AND m.adminid=0", 'UNBUFFERED');

	// 更新缓存
	require_once libfile('function/cache');
	updatecache('dsu_kkvip');
}