<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('auto_form');

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['auto_form']);
    $smarty->assign('action_link',  array('text' => $_LANG['auto_form_add'], 'href' => 'auto_form.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $form_list = get_form_list();

    $smarty->assign('form_list',    $form_list['arr']);
    $smarty->assign('filter',        $form_list['filter']);
    $smarty->assign('record_count',  $form_list['record_count']);
    $smarty->assign('page_count',    $form_list['page_count']);

    $sort_flag  = sort_flag($form_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('auto_form_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('auto_form');

    $form_list = get_form_list();

    $smarty->assign('form_list',    $form_list['arr']);
    $smarty->assign('filter',        $form_list['filter']);
    $smarty->assign('record_count',  $form_list['record_count']);
    $smarty->assign('page_count',    $form_list['page_count']);

    $sort_flag  = sort_flag($form_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('auto_form_list.htm'), '',
        array('filter' => $form_list['filter'], 'page_count' => $form_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('auto_form');

    /*初始化*/
    $goods = array();

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['auto_form_add']);
    $smarty->assign('action_link', array('text' => $_LANG['auto_form_list'], 'href' => 'auto_form.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('auto_form_info.htm');
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('auto_form');

    /*插入数据*/
    // $add_time = gmtime();
    $sql = "INSERT INTO ".$GLOBALS['ecs']->table('form')."(form_name, has_field_remark, has_field_age, has_field_position) ".
            "VALUES ('$_POST[form_name]', '$_POST[has_field_remark]', '$_POST[has_field_age]', '$_POST[has_field_position]')";
    $row = $db->query($sql);

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'auto_form.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'auto_form.php?act=list';

    admin_log($db->insert_id(),'add','auto_form');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('auto_form');

    /* 取商品数据 */
    $sql = "SELECT * ".
           " FROM " . $ecs->table('form') . " AS f ".
           " WHERE f.id='$_REQUEST[id]'";
    $goods = $db->GetRow($sql);

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['auto_form_add']);
    $smarty->assign('action_link', array('text' => $_LANG['auto_form_list'], 'href' => 'auto_form.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('auto_form_info.htm');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('auto_form');

    $sql = "UPDATE " . $GLOBALS['ecs']->table('from') . " SET " .
            "form_name = '$_POST[form_name]', " .
            "has_field_remark = '$_POST[has_field_remark]', " .
            "has_field_age = '$_POST[has_field_age]', " .
            "has_field_position = '$_POST[has_field_position]' " .
            "WHERE id = '$_REQUEST[id]' LIMIT 1";

    if ($GLOBALS['db']->query($sql))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'auto_form.php?act=list&' . list_link_postfix();

        admin_log($_POST['id'], 'edit', 'auto_form');

        clear_cache_files();
        sys_msg($_LANG['articleedit_succeed'], 0, $link);
    }
    else
    {
        die($GLOBALS['db']->error());
    }

}

/*------------------------------------------------------ */
//-- 删除活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('auto_form');

    $id = intval($_GET['id']);

    $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('form') . " WHERE id = '$id'";

    if ($GLOBALS['db']->query($sql))
    {
        admin_log($id,'remove','auto_form');
        clear_cache_files();
    }

    $url = 'auto_form.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
{
    admin_priv('auto_form');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_form'], 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('form') . " WHERE id = '$id'";

        if ($GLOBALS['db']->query($sql))
        {
            admin_log($id,'remove','auto_form');
            $count++;
        }
    }

    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'auto_form.php?act=list');
    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
}

/* 获得活动列表 */
function get_form_list()
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'f.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND f.form_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('form'). ' AS f '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT f.* '.
               'FROM ' .$GLOBALS['ecs']->table('form'). ' AS f '.
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
