<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_payment.php');
require(ROOT_PATH . 'includes/lib_order.php');

$pay_code = 'alipay';

	$plugin_file = 'includes/modules/payment/' . $pay_code . '.php';

    /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
    if (file_exists($plugin_file))
    {
        /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
        include_once($plugin_file);
        $payment = new $pay_code();
        $msg     = (@$payment->refund_respond()) ? 'success' : 'fail';   
    }
    else
    {
        $msg = 'fail';
    }

    echo $msg;