<?php

/**
 * 微信支付插件
 * 
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/wxpay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'wxpay_desc';

    /* 作者 */
    $modules[$i]['author']  = 'TOTLIANGB';

    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';

    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';

    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';

    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'wxpay_appid',           'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_mchid',               'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_key',               'type' => 'text',   'value' => ''),
        array('name' => 'wxpay_appsecret',           'type' => 'text',   'value' => '')
    );

    return;
}


/**
 * 类
 */
class wxpay
{

    function __construct()
    {
        ini_set('date.timezone','Asia/Shanghai');
        //error_reporting(E_ERROR);

        $this->wxpay();
    }

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function wxpay()
    {
    }

    /**
     * 获取二维码 图片url
     * @return [type] [description]
     */
    function get_code($order, $payment)
    {

        require_once ROOT_PATH . "wxpayAPI/lib/WxPay.Api.php";
        require_once ROOT_PATH . "wxpayAPI/d/WxPay.NativePay.php";
        require_once ROOT_PATH . "wxpayAPI/d/log.php";

        $notify = new NativePay();

        $input = new WxPayUnifiedOrder();
        // echo WxPayConfig::APPID;exit;
        // WxPayConfig::APPID = $payment['wxpay_appid'];

        // WxPayConfig::MCHID = $payment['wxpay_mchid'];

        // WxPayConfig::KEY = $payment['wxpay_key'];

        // WxPayConfig::APPSECRET = $payment['wxpay_appsecret'];
        $input->SetBody($order['order_sn']);
        $input->SetAttach($order['order_sn']);
        $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
        $input->SetTotal_fee($order['order_amount']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($order['order_sn']);
        $input->SetNotify_url(return_url(basename(__FILE__, '.php')));
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($order['order_sn']);
        $result = $notify->GetPayUrl($input);
        $url2 = $result["code_url"];

        $img_url = './wxpayAPI/d/qrcode.php?data='.urlencode($url2);

        $return_img = '<div style="text-align:center">
            <img alt="模式二扫码支付" src="'.$img_url.'" style="width:300px;height:300px;"/>
        </div>';

        return $return_img;
    }

    function return_notify_url()
    {

    }


}
