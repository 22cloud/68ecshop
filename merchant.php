<?php

/**
 * 商户验证
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

/*------------------------------------------------------ */
//-- 团购商品 --> 团购活动商品列表
/*------------------------------------------------------ */
if ($action == 'default')
{
    $smarty->assign('action',     $action);
    /* 显示模板 */
    $smarty->display('merchant.dwt');
}

/*------------------------------------------------------ */
//-- 团购商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($action == 'check')
{
    /* 取得参数：团购活动id */
    $coupons_code = isset($_POST['coupons_code']) ? trim($_POST['coupons_code']) : 0;
    $merchant_code = isset($_POST['merchant_code']) ? trim($_POST['merchant_code']) : 0;
    $goods_amount = isset($_POST['goods_amount']) ? trim($_POST['goods_amount']) : 0;

    if (!$coupons_code && !$merchant_code && $goods_amount) {
        ecs_header("Location: ./\n");
        exit;
    }


    $day    = getdate();
    $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    $sql = "SELECT u.* " .
            "FROM " . $GLOBALS['ecs']->table('users_coupons') . " AS u " .
            "WHERE 1 ";
    $sql .= "AND u.coupon_code = '$coupons_code' ";

    $coupons_info = $GLOBALS['db']->getRow($sql);

    if (!empty($coupons_info)) {

        if ($coupons_info['is_invalid'] == 1) {
            // 过期
            show_message('该优惠券已使用');
        }elseif ($coupons_info['expiration_date'] < $today) {
            // 过期
            show_message('该优惠券已过期');
        }elseif ($coupons_info['use_condition'] > $goods_amount) {
            // 未满足使用条件
            show_message('该优惠券的消费金额满'.$coupons_info['use_condition'].'才能使用');
        }elseif ($coupons_info['merchant_code'] != $merchant_code) {
            // 未满足使用条件
            show_message('优惠券非本商户优惠券');
        }else{
            $sql = "UPDATE " . $GLOBALS['ecs']->table('users_coupons') .
                " SET is_invalid = 1, used_time = '" . gmtime() . "' " .
                "WHERE is_invalid = 0 ";    
            $sql .= "AND expiration_date >= '$today' " ;
            $sql .= "AND use_condition <= '$goods_amount' " ;
            $sql .= "AND coupon_code = '$coupons_code' ";
            $sql .= "AND merchant_code = '$merchant_code'";
            $GLOBALS['db']->query($sql);

            // 添加日志
            // 记录发放记录
            $log_type = 5;
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('coupons_log') . " ( coupon_code, log_type, user_id, create_time) " .
                    "VALUES ( '".$coupons_info['coupon_code']."', '$log_type', '".$coupons_info['user_id']."', ".gmtime()." )";
            $GLOBALS['db']->query($sql);

            $coupon_str = '优惠券信息：'.'满'.$coupons_info['use_condition'].'减'.$coupons_info['deductible'].'；使用成功';

            show_message($coupon_str);
        }
    }else{
        // 未查到优惠券
        show_message('未找到优惠券');
    }

}

elseif($action == 'use')
{

    if (!empty($coupons_info)) {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('users_coupons') .
            " SET is_invalid = 1, used_time = '" . gmtime() . "' " .
            "WHERE is_invalid = 0 ";    
        $sql .= "AND expiration_date >= '$today' " ;
        $sql .= "AND use_condition <= '$goods_amount' " ;
        $sql .= "AND coupon_code = '$coupons_code' ";
        $sql .= "AND merchant_code = '$merchant_code'";
        $GLOBALS['db']->query($sql);

        // 添加日志
        // 记录发放记录
        $log_type = 5;
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('coupons_log') . " ( coupon_code, log_type, user_id, create_time) " .
                "VALUES ( '".$coupons_info['coupon_code']."', '$log_type', '".$coupons_info['user_id']."', ".gmtime()." )";
        $GLOBALS['db']->query($sql);

        show_message('成功');
    }else{
        // 未查到优惠券
        show_message('未找到优惠券');
    }
}

?>