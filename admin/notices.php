<?php

/**
 * ECSHOP 管理中心积分兑换商品程序文件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author $
 * $Id $
*/
use JPush\Client as JPush;

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$notice   = new notice($ecs->table("notice"), $db, 'id', 'n_content');
//$image = new cls_image();
//
$app_key = '6d2f8b0a7fa598003cfa7e47';
$master_secret = '1b8e2a2f748a7bad3325b406';
$client = new JPush($app_key, $master_secret);

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['notice_list']);
    // $smarty->assign('action_link',  array('text' => $_LANG['coupon_add'], 'href' => 'coupons.php?act=coupon_add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $notice_list = get_notice_list();

    $smarty->assign('notice_list',    $notice_list['arr']);
    $smarty->assign('filter',        $notice_list['filter']);
    $smarty->assign('record_count',  $notice_list['record_count']);
    $smarty->assign('page_count',    $notice_list['page_count']);

    $sort_flag  = sort_flag($notice_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('notice_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{

    $notice_list = get_notice_list();

    $smarty->assign('notice_list',    $notice_list['arr']);
    $smarty->assign('filter',        $notice_list['filter']);
    $smarty->assign('record_count',  $notice_list['record_count']);
    $smarty->assign('page_count',    $notice_list['page_count']);

    $sort_flag  = sort_flag($notice_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('notice_list.htm'), '',
        array('filter' => $notice_list['filter'], 'page_count' => $notice_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'coupon_add')
{
    /* 权限判断 */
    

    /*初始化*/
    $coupon = array();
    $coupon['coupon_type'] = 1;
    $coupon['is_hot']      = 0;

    $smarty->assign('coupon',       $coupon);
    $smarty->assign('ur_here',     $_LANG['coupon_add']);
    $smarty->assign('action_link', array('text' => $_LANG['coupon_list'], 'href' => 'coupons.php?act=coupon_list'));
    $smarty->assign('form_action', 'coupon_insert');

    assign_query_info();
    $smarty->display('coupon_info.htm');
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'coupon_insert')
{
    /* 权限判断 */
    

    /*检查是否重复*/
    $is_only = $cou->is_only('coupon_name', $_POST['coupon_name'],0);

    if (!$is_only)
    {
        sys_msg($_LANG['goods_exist'], 1);
    }

    /*插入数据*/
    $add_time = gmtime();

    $sql = "INSERT INTO ".$ecs->table('coupons')."(coupon_name, coupon_type, merchant_code, use_condition, deductible, expiry_time, coupon_remark, create_time, update_time) ".
            "VALUES ('$_POST[coupon_name]', '$_POST[coupon_type]', '$_POST[merchant_code]', '$_POST[use_condition]', '$_POST[deductible]', '$_POST[expiry_time]', '$_POST[coupon_remark]', '$add_time', '$add_time')";
    $db->query($sql);

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'coupons.php?act=coupon_add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'coupons.php?act=coupon_list';

    admin_log($_POST[coupon_name],'add','coupons');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'coupon_edit')
{

    /* 取商品数据 */
    $sql = "SELECT c.id, c.coupon_name, c.coupon_type, c.merchant_code,c.use_condition, c.deductible, c.expiry_time , c.coupon_remark".
           " FROM " . $ecs->table('coupons') . " AS c ".
           " WHERE c.id='$_REQUEST[id]'";
    $coupon = $db->GetRow($sql);

    $smarty->assign('coupon',       $coupon);
    $smarty->assign('ur_here',     $_LANG['coupon_add']);
    $smarty->assign('action_link', array('text' => $_LANG['coupon_list'], 'href' => 'coupons.php?act=coupon_list&' . list_link_postfix()));
    $smarty->assign('form_action', 'coupon_update');

    assign_query_info();
    $smarty->display('coupon_info.htm');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='coupon_update')
{
	$update_time = gmtime();
    if ($cou->edit("coupon_name='$_POST[coupon_name]', coupon_type='$_POST[coupon_type]', merchant_code='$_POST[merchant_code]', use_condition='$_POST[use_condition]', deductible='$_POST[deductible]', expiry_time='$_POST[expiry_time]', coupon_remark='$_POST[coupon_remark]', update_time='$update_time' ", $_POST['id']))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'coupons.php?act=coupon_list&' . list_link_postfix();

        admin_log($_POST['id'], 'edit', 'coupons');

        clear_cache_files();
        sys_msg($_LANG['articleedit_succeed'], 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 批量删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'coupon_batch_remove')
{

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_coupons'], 1);
    }
    // 查询 所删优惠券 是否存在 正在未过期的
    $is_confirm = $cou->is_invalid_coupon($_POST['checkboxes']);

    if ($is_confirm) {
    	$count = 0;
	    foreach ($_POST['checkboxes'] AS $key => $id)
	    {
	        if ($cou->drop($id))
	        {
	            admin_log($id,'remove','coupons');
	            $count++;
	        }
	    }


	    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'coupons.php?act=coupon_list');
	    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
    }else{
    	// 存在未失效 优惠券
    	$lnk[] = array('text' => $_LANG['back_list'], 'href' => 'coupons.php?act=coupon_list');
    	sys_msg($_LANG['batch_remove_faired_invalid'], 0, $lnk);
    }
}

/*------------------------------------------------------ */
//-- 删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'coupon_remove')
{
    $id = intval($_GET['id']);

    // 查询 所删优惠券 是否存在 正在未过期的
    $is_confirm = $cou->is_invalid_coupon($id);

    if ($is_confirm) {
	    if ($cou->drop($id))
	    {
	        admin_log($id,'remove','coupons');
	        clear_cache_files();
	    }

	    $url = 'coupons.php?act=coupon_query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

	    ecs_header("Location: $url\n");
	    exit;
    }else{
    	// 存在未失效 优惠券
    	$lnk[] = array('text' => $_LANG['back_list'], 'href' => 'coupons.php?act=coupon_list');
    	sys_msg($_LANG['batch_remove_faired_invalid'], 0, $lnk);
    }
}


/*------------------------------------------------------ */
//-- 优惠券设置
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'notice_setting')
{
    $notice_setting = get_notice_setting();

    $smarty->assign('notice_setting',    $notice_setting);
    $smarty->assign('form_act','notice_setting_post');

    assign_query_info();
    $smarty->display('notice_setting.htm');
}

/*------------------------------------------------------ */
//-- 获取用户列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'get_users_list')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_user_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['user_id'],
                        'text' => $val['user_name']);
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 优惠券设置保存
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'notice_setting_post')
{

	$sendFlag = false;
	if (isset($_POST['source_select1'])) {
		$sendData['source_select1'] = $source_select1 = $_POST['source_select1'] ? $_POST['source_select1'] : '';
		$sendData['notice_content'] = $notice_content = trim($_POST['notice_content']) ? trim($_POST['notice_content']) : '';
	}

    if (!empty($source_select1) && $notice_content) {
		$sendFlag = true;

    	if (!empty($source_select1)) {
			foreach ($source_select1 as $key => $value) {

				$add_time = gmtime();

				$coupon_code = uuid();
                $n_type = 1;// 自定义消息
                $n_sender = 0;// 自定义消息 发送者为0 0 为系统
                $model_id = 0; // 自定义消息 无版块
                $other_url = '';//自定义消息 无其他参数

				// 发送
				$sql = "INSERT INTO " . $ecs->table('notice') . " (n_type, n_content, n_receiver, n_sender, " .
	                    "send_time, model_id, other_url) " .
	                "VALUES ('$n_type', '$notice_content', '".$value."', '".$n_sender."', " .
	                    "'$add_time', '$model_id', '$other_url')";
	            $GLOBALS['db']->query($sql);
			}
            $model_id = 0;// 自定义
            $other_param = array('notice'=>$notice_content);
            send_notice($source_select1,$model_id,$other_param);
			$sendFlag2 = true;
    	}else{
			$sendFlag2 = false;
    	}
    }


    $link[0]['text'] = '自定义消息发送';
    $link[0]['href'] = 'notices.php?act=notice_setting';

    if ($sendFlag) {
        if ($sendFlag2) {
            $tips_str = $_LANG['setting_send_succeed'];
        }else{
            $tips_str = '发送失败。';
        }
    }else{
        $tips_str = '信息不全，未选择接收用户或消息内容。';
    }

    sys_msg($tips_str, 0, $link);

}

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'coupon_log')
{
    /* 权限判断 */
    

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['coupon_log']);
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $log_list = get_coupon_loglist();

    $smarty->assign('log_list',    $log_list['arr']);
    $smarty->assign('filter',        $log_list['filter']);
    $smarty->assign('record_count',  $log_list['record_count']);
    $smarty->assign('page_count',    $log_list['page_count']);

    $smarty->assign('lang_log_type', $_LANG['log_type']);

    $sort_flag  = sort_flag($log_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('coupon_log.htm');
}

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'users_couponslist')
{

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['users_couponslist']);
    // $smarty->assign('action_link',  array('text' => $_LANG['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $users_coupons_list = get_users_couponslist();

    $smarty->assign('users_coupons_list',    $users_coupons_list['arr']);
    $smarty->assign('filter',        $users_coupons_list['filter']);
    $smarty->assign('record_count',  $users_coupons_list['record_count']);
    $smarty->assign('page_count',    $users_coupons_list['page_count']);

    $sort_flag  = sort_flag($users_coupons_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('users_couponslist.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'user_coupon_query')
{

    $users_coupons_list = get_users_couponslist();

    $smarty->assign('users_coupons_list',    $users_coupons_list['arr']);
    $smarty->assign('filter',        $users_coupons_list['filter']);
    $smarty->assign('record_count',  $users_coupons_list['record_count']);
    $smarty->assign('page_count',    $users_coupons_list['page_count']);

    $sort_flag  = sort_flag($users_coupons_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('users_couponslist.htm'), '',
        array('filter' => $users_coupons_list['filter'], 'page_count' => $users_coupons_list['page_count']));
}

/* 获得优惠券列表 */
function get_notice_list()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'n.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND n.n_content LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('notice'). ' AS n '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT n.*,u.user_name '.
               'FROM ' .$GLOBALS['ecs']->table('notice'). ' AS n '.
               'LEFT JOIN '.$GLOBALS['ecs']->table('users'). ' AS u ON u.user_id = n.n_receiver '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['send_time_str'] = date('Y-m-d H:i:s', $rows['send_time']);
        switch ($rows['model_id']) {
            case '1':
                $model_name = '订单';
                break;
            case '2':
                $model_name = '优惠券';
                break;
            case '3':
                $model_name = '积分';
                break;
            case '4':
                $model_name = '生日';
                break;
            default:
                $model_name = '自定义';
                break;
        }
        $rows['model_id_name'] = $model_name;

        $rows['n_type_name'] = ($rows['n_type'] == 1) ? '自定义消息' : '系统消息';
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function get_notice_setting()
{
	$sql = 'SELECT * FROM '.$GLOBALS['ecs']->table('notice_setting').' limit 1';
	return $GLOBALS['db']->getRow($sql);
}

function get_coupon_loglist()
{

    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'cl.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND u.user_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('coupons_log'). ' AS cl '.
			   'LEFT JOIN '.$GLOBALS['ecs']->table('users').' AS u ON u.user_id = cl.user_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT cl.*,u.user_name '.
               'FROM ' .$GLOBALS['ecs']->table('coupons_log'). ' AS cl '.
			   'LEFT JOIN '.$GLOBALS['ecs']->table('users').' AS u ON u.user_id = cl.user_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
    	$rows['create_time_str'] = date('Y-m-d H:i:s', $rows['create_time']);
    	$rows['log_type_str'] = $_LANG['log_type'][$rows['log_type']];
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function get_users_couponslist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'uc.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND u.user_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('users_coupons'). ' AS uc '.
			   'LEFT JOIN '.$GLOBALS['ecs']->table('users').' AS u ON u.user_id = uc.user_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT uc.*,u.user_name '.
               'FROM ' .$GLOBALS['ecs']->table('users_coupons'). ' AS uc '.
			   'LEFT JOIN '.$GLOBALS['ecs']->table('users').' AS u ON u.user_id = uc.user_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
    	$rows['expiration_date_str'] = date("Y-m-d",$rows['expiration_date']);
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/* 获得活动所有表单 */
function get_coupons_options_list($selected = 0){
    $sql = 'SELECT * FROM '. $GLOBALS['ecs']->table('coupons'). ' AS c '.
            'WHERE 1';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $lst .= "<option value='$row[id]'";
        $lst .= ($selected == $row['id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['coupon_name']). '</option>';
    }

    return $lst ? $lst : '';
}


/**
 * 按等级取得用户列表（用于生成下拉列表）
 *
 * @return  array       分类数组 user_id => user_name
 */
function get_user_list($filter)
{

	$user_list = array();
    $filter->keyword = json_str_iconv($filter->keyword);
    
    $where = (isset($filter->keyword) && $filter->keyword!='') ? " WHERE user_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' " : ' ';

    /* 取得数据 */
    $sql = 'SELECT user_id, user_name '.
           'FROM ' . $GLOBALS['ecs']->table('users') . $where .
           'LIMIT 50';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $user_list[$row['user_id']] = $row;
    }

    return $user_list;
}

function uuid() {
    if (function_exists ( 'com_create_guid' )) {
        return com_create_guid ();
    } else {
        mt_srand ( ( double ) microtime () * 10000 ); //optional for php 4.2.0 and up.随便数播种，4.2.0以后不需要了。
        $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) ); //根据当前时间（微秒计）生成唯一id.
        $hyphen = chr ( 45 ); // "-"
        $uuid = '' . //chr(123)// "{"
substr ( $charid, 0, 8 ) . $hyphen . substr ( $charid, 8, 4 ) . $hyphen . substr ( $charid, 12, 4 ) . $hyphen . substr ( $charid, 16, 4 ) . $hyphen . substr ( $charid, 20, 12 );
        //.chr(125);// "}"
        return $uuid;
    }
}

?>