<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('auto_campaign');

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $_LANG['16_auto_campaign']);
    $smarty->assign('action_link',  array('text' => $_LANG['auto_campaign_add'], 'href' => 'auto_campaign.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $campaign_list = get_campaign_list();

    $smarty->assign('campaign_list',    $campaign_list['arr']);
    $smarty->assign('filter',        $campaign_list['filter']);
    $smarty->assign('record_count',  $campaign_list['record_count']);
    $smarty->assign('page_count',    $campaign_list['page_count']);

    $sort_flag  = sort_flag($campaign_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('auto_campaign_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('auto_campaign');

    $campaign_list = get_campaign_list();

    $smarty->assign('campaign_list',    $campaign_list['arr']);
    $smarty->assign('filter',        $campaign_list['filter']);
    $smarty->assign('record_count',  $campaign_list['record_count']);
    $smarty->assign('page_count',    $campaign_list['page_count']);

    $sort_flag  = sort_flag($campaign_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('auto_campaign_list.htm'), '',
        array('filter' => $campaign_list['filter'], 'page_count' => $campaign_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件

    /* 权限判断 */
    admin_priv('auto_campaign');

    /*初始化*/
    $goods = array();
    $goods['is_show'] = 0;

    /* 创建 html editor */
    create_html_editor('FCKeditor');

    $smarty->assign('form_list', get_form_list());

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['auto_campaign_add']);
    $smarty->assign('action_link', array('text' => $_LANG['16_auto_campaign_list'], 'href' => 'auto_campaign.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('auto_campaign_info.htm');
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('auto_campaign');

    /*插入数据*/
    // $add_time = gmtime();
    $sql = "INSERT INTO ".$GLOBALS['ecs']->table('campaign')."(title, content, campaign_form, is_show) ".
            "VALUES ('$_POST[title]', '$_POST[FCKeditor]', '$_POST[is_exchange]', '$_POST[is_hot]')";
    $row = $db->query($sql);

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'auto_campaign.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'auto_campaign.php?act=list';

    admin_log($db->insert_id(),'add','campaign');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件

    /* 权限判断 */
    admin_priv('auto_campaign');

    /* 取商品数据 */
    $sql = "SELECT * ".
           " FROM " . $ecs->table('campaign') . " AS c ".
           " WHERE c.id='$_REQUEST[id]'";
    $goods = $db->GetRow($sql);

    /* 创建 html editor */
    create_html_editor('FCKeditor', $goods['content']);

    $smarty->assign('form_list', get_form_list($goods['campaign_form']));

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['auto_campaign_add']);
    $smarty->assign('action_link', array('text' => $_LANG['16_auto_campaign_list'], 'href' => 'auto_campaign.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('auto_campaign_info.htm');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('auto_campaign');

    $sql = "UPDATE " . $GLOBALS['ecs']->table('campaign') . " SET " .
            "title = '$_POST[title]', " .
            "content = '$_POST[FCKeditor]', " .
            "campaign_form = '$_POST[form_id]', " .
            "is_show = '$_POST[is_show]' " .
            "WHERE id = '$_REQUEST[id]' LIMIT 1";

    if ($GLOBALS['db']->query($sql))
    {
        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'auto_campaign.php?act=list&' . list_link_postfix();

        admin_log($_POST['id'], 'edit', 'campaign');

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
    check_authz_json('auto_campaign');

    $id = intval($_GET['id']);

    $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('campaign') . " WHERE id = '$id'";

    if ($GLOBALS['db']->query($sql))
    {
        admin_log($id,'remove','campaign');
        clear_cache_files();
    }

    $url = 'auto_campaign.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
{
    admin_priv('auto_campaign');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_campaign'], 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
        $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('campaign') . " WHERE id = '$id'";

        if ($GLOBALS['db']->query($sql))
        {
            admin_log($id,'remove','campaign');
            $count++;
        }
    }

    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'auto_campaign.php?act=list');
    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
}
elseif ($_REQUEST['act'] == 'see_post')
{
    /* 权限判断 */
    admin_priv('auto_campaign');

    $id = intval($_GET['id']);

    /* 取商品数据 */
    $sql = "SELECT * ".
           " FROM " . $ecs->table('campaign') . " AS c ".
           " WHERE c.id='$id'";
    $campaign = $db->GetRow($sql);

    $smarty->assign('campaign', $campaign);

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here',      $campaign['title'].$_LANG['auto_campaign_post_list']);
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $campaign_post_list = get_campaign_post_list($id);

    $smarty->assign('campaign_post_list',    $campaign_post_list['arr']);
    $smarty->assign('filter',        $campaign_post_list['filter']);
    $smarty->assign('record_count',  $campaign_post_list['record_count']);
    $smarty->assign('page_count',    $campaign_post_list['page_count']);

    $sort_flag  = sort_flag($campaign_post_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('auto_campaign_post_list.htm');
}

/* 获得活动列表 */
function get_campaign_list()
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'c.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND c.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('campaign'). ' AS c '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT c.* '.
               'FROM ' .$GLOBALS['ecs']->table('campaign'). ' AS c '.
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

/* 获得活动所有表单 */
function get_form_list($selected = 0){
    $sql = 'SELECT * FROM '. $GLOBALS['ecs']->table('form'). ' AS f '.
            'WHERE 1';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $lst .= "<option value='$row[id]'";
        $lst .= ($selected == $row['id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['form_name']). '</option>';
    }

    return $lst ? $lst : '';
}

/* 获得活动报名列表 */
function get_campaign_post_list($id)
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
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'cfv.id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = ' AND cfv.campaign_id = '.$id.' ';

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('campaign_form_value'). ' AS cfv '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT cfv.* '.
               'FROM ' .$GLOBALS['ecs']->table('campaign_form_value'). ' AS cfv '.
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