<?php

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
    $_REQUEST['act'] = 'list';
}
/*------------------------------------------------------ */
//-- 秒杀商品 --> 秒杀商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得团购活动总数 */
    $count = promotion_goods_count();
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
    if (!$smarty->is_cached('promotion_goods.dwt', $cache_id))
    {
        if ($count > 0)
        {
            /* 取得当前页的团购活动 */
            $gb_list = promotion_goods_list($size, $page);
            // echo '<pre>';var_dump($gb_list);exit;
            $smarty->assign('gb_list',  $gb_list);

            /* 设置分页链接 */
            $pager = get_pager('promotion_goods.php', array('act' => 'list'), $count, $page, $size);
            $smarty->assign('pager', $pager);
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('categories', get_categories_tree()); // 分类树
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('top_goods',  get_top10());           // 销售排行
        $smarty->assign('promotion_info', get_promotion_info());
        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typegroup_buy.xml" : 'feed.php?type=group_buy'); // RSS URL

        assign_dynamic('promotion_goods');
    }

    /* 显示模板 */
    $smarty->display('promotion_goods.dwt', $cache_id);
}


/* 取得秒杀商品总数 */
function promotion_goods_count()
{
    $time = gmtime();

	$sql = 'SELECT COUNT(*) ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 取得某页的所有秒杀商品
 * @param   int     $size   每页记录数
 * @param   int     $page   当前页
 * @return  array
 */
function promotion_goods_list($size, $page)
{
    /* 取得团购活动 */
    $gb_list = array();
    $time = gmtime();

    $order_type = $GLOBALS['_CFG']['recommend_order'];

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, " .
                "g.is_best, g.is_new, g.is_hot, g.is_promote, RAND() AS rnd " .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' ";
    $sql .= $order_type == 0 ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY rnd';

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $goodsItem['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goodsItem['promote_price'] = '';
        }

        $goodsItem['id']           = $row['goods_id'];
        $goodsItem['name']         = $row['goods_name'];
        $goodsItem['brief']        = $row['goods_brief'];
        $goodsItem['brand_name']   = $row['brand_name'];
        $goodsItem['goods_style_name']   = add_style($row['goods_name'],$row['goods_name_style']);
        $goodsItem['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $goodsItem['short_style_name']   = add_style($goodsItem['short_name'],$row['goods_name_style']);
        $goodsItem['market_price'] = price_format($row['market_price']);
        $goodsItem['shop_price']   = price_format($row['shop_price']);
        $goodsItem['thumb']        = '../'.get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $goodsItem['goods_img']    = '../'.get_image_path($row['goods_id'], $row['goods_img']);
        $goodsItem['url']          = build_uri('goods', array('gid' => $row['goods_id'],'redirect' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), $row['goods_name']);
		$goodsItem['sell_count']   =selled_count($row['goods_id']);
		$goodsItem['pinglun']   =get_evaluation_sum($row['goods_id']);

		$goods[] = $goodsItem;
    }

    return $goods;
}

?>
