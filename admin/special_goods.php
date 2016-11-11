<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*初始化数据交换对象 */
$special   = new special($ecs->table("special_goods"), $db, 'id', 'goods_id');
//$image = new cls_image();

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('special_goods');

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['special_goods_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['special_goods_add'], 'href' => 'special_goods.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $goods_list = get_special_goodslist();

    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);

    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('special_goods_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('special_goods');

    $goods_list = get_special_goodslist();

    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);

    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('special_goods_list.htm'), '',
        array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('special_goods');

    /*初始化*/
    $goods = array();
    $goods['is_exchange'] = 1;
    $goods['is_hot']      = 0;
    $goods['option']      = '<option value="0">'.$_LANG['make_option'].'</option>';

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['special_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['special_goods_list'], 'href' => 'special_goods.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('special_goods_info.htm');
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('special_goods');

    /*检查是否重复*/
    $is_only = $special->is_only('goods_id', $_POST['goods_id'],0, " goods_id ='$_POST[goods_id]'");

    if (!$is_only)
    {
        sys_msg($_LANG['goods_exist'], 1);
    }

    /*插入数据*/
    $add_time = gmtime();
    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }
    $sql = "INSERT INTO ".$ecs->table('special_goods')."(goods_id, goods_total_number, special_price, is_special, add_time, update_time) ".
            "VALUES ('$_POST[goods_id]', '$_POST[goods_total_number]', '$_POST[special_price]', '$_POST[is_special]', '$add_time', '$add_time')";
    $db->query($sql);

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'special_goods.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'special_goods.php?act=list';

    admin_log($_POST['goods_id'],'add','special_goods');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('special_goods');

    /* 取商品数据 */
    $sql = "SELECT cg.goods_id, cg.goods_total_number, cg.special_price, cg.is_special, g.goods_name ".
           " FROM " . $ecs->table('special_goods') . " AS cg ".
           "  LEFT JOIN " . $ecs->table('goods') . " AS g ON g.goods_id = cg.goods_id ".
           " WHERE cg.id='$_REQUEST[id]'";
    $goods = $db->GetRow($sql);
    $goods['option']  = '<option value="'.$goods['goods_id'].'">'.$goods['goods_name'].'</option>';

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['special_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['special_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('special_goods_info.htm');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('special_goods');

    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }

    if ($special->edit("goods_id='$_POST[goods_id]', goods_total_number='$_POST[goods_total_number]', special_price='$_POST[special_price]', is_special='$_POST[is_special]' ", $_POST['id']))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'special_goods.php?act=list&' . list_link_postfix();

        admin_log($_POST['goods_id'], 'edit', 'special_goods');

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
elseif ($_REQUEST['act'] == 'batch_remove')
{
    admin_priv('special_goods');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_goods'], 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
        if ($special->drop($id))
        {
            admin_log($id,'remove','special_goods');
            $count++;
        }
    }

    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'special_goods.php?act=list');
    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
}

/*------------------------------------------------------ */
//-- 删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('special_goods');

    $id = intval($_GET['id']);
    if ($special->drop($id))
    {
        admin_log($id,'remove','special_goods');
        clear_cache_files();
    }

    $url = 'special_goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);

    make_json_result($arr);
}

/* 获得商品列表 */
function get_special_goodslist()
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'sg.goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND g.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('special_goods'). ' AS sg '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = sg.goods_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT sg.* , g.goods_name '.
               'FROM ' .$GLOBALS['ecs']->table('special_goods'). ' AS sg '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = sg.goods_id '.
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
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
?>