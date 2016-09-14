<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$nowVersion = isset($_REQUEST['v'])  ? ($_REQUEST['v']) : 0;

$returnArr = array();

include('includes/cls_json.php');
$json   = new JSON;

// 检查是否为新版本
$sql = 'select count(*) from '.$GLOBALS['ecs']->table('app_version').'where version_id = '.$nowVersion;
$checkFlag = $GLOBALS['db']->getOne($sql);
if ($checkFlag) {
	// 是最新的
	$returnCode = 1;
	$returnMsg = '当前已是最新版本';
}else{
	// 不是最新的
	$sql = 'select `version_id`,`version_intro`,`url` from '.$GLOBALS['ecs']->table('app_version');
	$versionInfo = $GLOBALS['db']->getRow($sql);

	$returnCode = 2;
	$returnMsg = '当前不是最新版本';
}

$returnArr['code'] = $returnCode;
$returnArr['msg'] = $returnMsg;
if ($versionInfo) {
	$returnArr['data'] = $versionInfo;
}

die($json->encode($returnArr));

?>