<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
/*------------------------------------------------------ */
//-- 秒杀商品 --> 秒杀商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得团购活动总数 */
    $sql = "SELECT count(*) FROM " . $ecs->table('favourable_activity'). " ORDER BY `sort_order` ASC,`end_time` DESC";
    $count = $GLOBALS['db']->getOne($sql);

    if ($count > 0)
    {
        /* 取得每页记录数 */
        $size = isset($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

        /* 计算总页数 */
        $page_count = ceil($count / $size);

        /* 取得当前页 */
        $page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
        $page = $page > $page_count ? $page_count : $page;

        /* 缓存id：语言 - 每页记录数 - 当前页 */
        $cache_id = $_CFG['lang'] . '-' . $size . '-' . $page;
        $cache_id = sprintf('%X', crc32($cache_id));
    }
    else
    {
        /* 缓存id：语言 */
        $cache_id = $_CFG['lang'];
        $cache_id = sprintf('%X', crc32($cache_id));
    }

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('favourable.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的优惠活动 */

            // 数据准备

            /* 取得用户等级 */
            $user_rank_list = array();
            $user_rank_list[0] = $_LANG['not_user'];
            $sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $user_rank_list[$row['rank_id']] = $row['rank_name'];
            }
            
            $sql = "SELECT * FROM " . $ecs->table('favourable_activity'). " ORDER BY `sort_order` ASC,`end_time` DESC";
            $res = $db->query($sql);

            $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

            $goods = array();
            while ($row = $GLOBALS['db']->fetchRow($res))
            {
                $row['start_time']  = local_date('Y-m-d H:i', $row['start_time']);
                $row['end_time']    = local_date('Y-m-d H:i', $row['end_time']);

                //享受优惠会员等级
                $user_rank = explode(',', $row['user_rank']);
                $row['user_rank'] = array();
                foreach($user_rank as $val)
                {
                    if (isset($user_rank_list[$val]))
                    {
                        $row['user_rank'][] = $user_rank_list[$val];
                    }
                }

                //优惠范围类型、内容
                if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext']))
                {
                    if ($row['act_range'] == FAR_CATEGORY)
                    {
                        $row['act_range'] = $_LANG['far_category'];
                        $row['program'] = 'category.php?id=';
                        $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
                            " WHERE cat_id " . db_create_in($row['act_range_ext']);
                    }
                    elseif ($row['act_range'] == FAR_BRAND)
                    {
                        $row['act_range'] = $_LANG['far_brand'];
                        $row['program'] = 'brand.php?id=';
                        $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
                            " WHERE brand_id " . db_create_in($row['act_range_ext']);
                    }
                    else
                    {
                        $row['act_range'] = $_LANG['far_goods'];
                        $row['program'] = 'goods.php?id=';
                        $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
                            " WHERE goods_id " . db_create_in($row['act_range_ext']);
                    }
                    $act_range_ext = $db->getAll($sql);
                    $row['act_range_ext'] = $act_range_ext;
                }
                else
                {
                    $row['act_range'] = $_LANG['far_all'];
                }

                //优惠方式

                switch($row['act_type'])
                {
                    case 0:
                        $row['act_type'] = $_LANG['fat_goods'];
                        $row['gift'] = unserialize($row['gift']);
                        if(is_array($row['gift']))
                        {
                            foreach($row['gift'] as $k=>$v)
                            {
                                $row['gift'][$k]['thumb'] = get_image_path($v['id'], $db->getOne("SELECT goods_thumb FROM " . $ecs->table('goods') . " WHERE goods_id = '" . $v['id'] . "'"), true);
                            }
                        }
                        break;
                    case 1:
                        $row['act_type'] = $_LANG['fat_price'];
                        $row['act_type_ext'] .= $_LANG['unit_yuan'];
                        $row['gift'] = array();
                        break;
                    case 2:
                        $row['act_type'] = $_LANG['fat_discount'];
                        $row['act_type_ext'] .= "%";
                        $row['gift'] = array();
                        break;
                }
                $row['url'] = './favourable.php?act=info&id='.$row['act_id'];

                $list[] = $row;
            }

            $gb_list = $list;//favourable_list($size, $page);
            // echo '<pre>';var_dump($gb_list);exit;
            $smarty->assign('gb_list',  $gb_list);

            /* 设置分页链接 */
            $pager = get_pager('favourable.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here(0, $_LANG['favourable']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('lang',             $_LANG);
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typeactivity.xml" : 'feed.php?type=activity'); // RSS URL

        assign_dynamic('favourable');
    }

    /* 显示模板 */
    $smarty->display('favourable.dwt', $cache_id);
}
elseif ($_REQUEST['act'] == 'info')
{
    $goods_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;

    $sql = "SELECT * FROM " . $ecs->table('favourable_activity'). " ORDER BY `sort_order` ASC,`end_time` DESC";
    $row = $GLOBALS['db']->getRow($sql);

    if (!empty($row)) {
        $row['start_time']  = local_date('Y-m-d H:i', $row['start_time']);
        $row['end_time']    = local_date('Y-m-d H:i', $row['end_time']);

        //享受优惠会员等级
        $user_rank = explode(',', $row['user_rank']);
        $row['user_rank'] = array();
        foreach($user_rank as $val)
        {
            if (isset($user_rank_list[$val]))
            {
                $row['user_rank'][] = $user_rank_list[$val];
            }
        }

        //优惠范围类型、内容
        if ($row['act_range'] != FAR_ALL && !empty($row['act_range_ext']))
        {
            if ($row['act_range'] == FAR_CATEGORY)
            {
                $row['act_range'] = $_LANG['far_category'];
                $row['program'] = 'category.php?id=';
                $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
                    " WHERE cat_id " . db_create_in($row['act_range_ext']);
            }
            elseif ($row['act_range'] == FAR_BRAND)
            {
                $row['act_range'] = $_LANG['far_brand'];
                $row['program'] = 'brand.php?id=';
                $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
                    " WHERE brand_id " . db_create_in($row['act_range_ext']);
            }
            else
            {
                $row['act_range'] = $_LANG['far_goods'];
                $row['program'] = 'goods.php?id=';
                $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
                    " WHERE goods_id " . db_create_in($row['act_range_ext']);
            }
            $act_range_ext = $db->getAll($sql);
            $row['act_range_ext'] = $act_range_ext;
        }
        else
        {
            $row['act_range'] = $_LANG['far_all'];
        }

        //优惠方式

        switch($row['act_type'])
        {
            case 0:
                $row['act_type'] = $_LANG['fat_goods'];
                $row['gift'] = unserialize($row['gift']);
                if(is_array($row['gift']))
                {
                    foreach($row['gift'] as $k=>$v)
                    {
                        $row['gift'][$k]['thumb'] = get_image_path($v['id'], $db->getOne("SELECT goods_thumb FROM " . $ecs->table('goods') . " WHERE goods_id = '" . $v['id'] . "'"), true);
                    }
                }
                break;
            case 1:
                $row['act_type'] = $_LANG['fat_price'];
                $row['act_type_ext'] .= $_LANG['unit_yuan'];
                $row['gift'] = array();
                break;
            case 2:
                $row['act_type'] = $_LANG['fat_discount'];
                $row['act_type_ext'] .= "%";
                $row['gift'] = array();
                break;
        }
    }

    $smarty->assign('favourable_info',$row);

    $smarty->assign('cfg', $_CFG);
    $position = assign_ur_here(0, $row['act_name']);
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置

    /* 显示模板 */
    $smarty->display('favourable_info.dwt');
}


?>
