<?php

/**
 * ECSHOP 积分商城
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: exchange.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'view';
}


/*------------------------------------------------------ */
//-- 积分兑换商品详情
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'view')
{
    $campaign_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;

    $cache_id = $campaign_id . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang'] . '-campaign';
    $cache_id = sprintf('%X', crc32($cache_id));

    if (!$smarty->is_cached('campaign.dwt', $cache_id))
    {
        $smarty->assign('id',           $campaign_id);
        $smarty->assign('cfg',          $_CFG);

        /* 获得商品的信息 */
        $campaign = get_campaign_info($campaign_id);

        if ($campaign === false)
        {
            /* 如果没有找到任何记录则跳回到首页 */
            ecs_header("Location: ./\n");
            exit;
        }
        else
        {
            $smarty->assign('goods',              $campaign);
            $smarty->assign('goods_id',           $campaign['campaign_id']);

            /* meta */
            $smarty->assign('keywords',           htmlspecialchars($campaign['keywords']));
            $smarty->assign('description',        htmlspecialchars($campaign['goods_brief']));

            assign_template();

			/* current position */
            $position = assign_ur_here('campaign', $campaign['title']);
            $smarty->assign('page_title',          $position['title']);                    // 页面标题
            $smarty->assign('ur_here',             $position['ur_here']);                  // 当前位置

            assign_dynamic('campaign');
        }
    }

    $smarty->display('campaign.dwt',      $cache_id);
}
elseif ($_REQUEST['act'] == 'ajax_post') {


	$extend_fields = '';
	$extend_fields_value = '';
	if ($_POST['campaign_age']) {
		$extend_fields = ", campaign_age";
		$extend_fields_value = ", '".$_POST['campaign_age']."'";
	}
	if ($_POST['campaign_remark']) {
		$extend_fields = ", campaign_remark";
		$extend_fields_value = ", '".$_POST['campaign_remark']."'";
	}

	if ($_POST['campaign_position']) {
		$extend_fields = ", campaign_position";
		$extend_fields_value = ", '".$_POST['campaign_position']."'";
	}

	$sql = "INSERT INTO ".$GLOBALS['ecs']->table('campaign_form_value')."(create_time,campaign_id, campaign_user_name, campaign_mobile, campaign_sex". $extend_fields .") ".
            "VALUES (".gmtime().",'$_POST[campaign_id]', '$_POST[campaign_user_name]', '$_POST[campaign_mobile]', '$_POST[campaign_sex]'". $extend_fields_value .")";

    if ($row = $db->query($sql)) {
    	show_message('提交成功。','','campaign.php?id='.$_POST[campaign_id]);	
    }else{
    	show_message('提交失败。','33','campaign.php?id='.$_POST[campaign_id]);	
    }
}

function get_campaign_info($campaign_id){
	$sql = "SELECT c.id as campaign_id, c.* ,f.* FROM " .
			$GLOBALS['ecs']->table('campaign'). " AS c," . 
			$GLOBALS['ecs']->table('form') . " AS f ".
			"WHERE c.campaign_form = f.id AND c.is_show = 1 AND c.id = " . $campaign_id . " LIMIT 1";

    $campaign = $GLOBALS['db']->getRow($sql);

    return !empty($campaign) ? $campaign : false;
}