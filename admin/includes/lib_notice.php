<?php
use JPush\Client as JPush;

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

function send_notice($user_ids= array() ,$model_id=0, $other_param= array())
{

    if (empty($user_ids))
    {
        return false;
    }
    
    $add_time = gmtime();
    $n_type = 2;// 系统发送
    $n_sender = 0;// 自定义消息 发送者为0 0 为系统
    $model_id = $model_id;
    // 根据model_id 处理$other_param
    $notice_content = '';
    
    switch ($model_id) {
        case '1':
            $notice_content = '您的订单'.$other_param['order_sn'].'状态更改为'.$other_param['status_name'];
            $extra_url = 'user.php?act=order_detail&order_id='.$other_param['order_id'];
            break;

        case '2':
            $notice_content = '您收到一张优惠券，请点击查看。';
            $extra_url = 'user.php?act=coupons';
            break;

        case '3':
            $notice_content = '恭喜您，获得'.$other_param['score'].'积分。';
            $extra_url = 'user.php?act=notices';
            break;

        case '4':
            $notice_content = '您的生日到了，生日快乐。';
            $extra_url = 'user.php?act=notices';
            break;
        
        default:
            $notice_content = $other_param['notice'] ? $other_param['notice'] : '';
            $extra_url = 'user.php?act=notices';
            break;
    }
    if (!empty($other_param)) {
        $other_url = json_encode($other_param);
    }else{
        $other_url = '';
    }

    foreach ($user_ids as $key => $value) {
        // 发送
        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('notice') . " (n_type, n_content, n_receiver, n_sender, " .
                "send_time, model_id, other_url) " .
            "VALUES ('$n_type', '$notice_content', '".$value."', '".$n_sender."', " .
                "'$add_time', '$model_id', '$other_url')";
        $GLOBALS['db']->query($sql);
    }

    $app_key = '99883b14e738658ee3ae177f';
    $master_secret = '0fd97e9a88dcbb8a805e99c6';
    $client = new JPush($app_key, $master_secret);
    $ios_extra = array(
            'extras' => array(
                'url' => $extra_url
            ),
        );
    // 推送消息
    $pusher = $client->push();
    $pusher->setPlatform('all');
    $pusher->addAlias($user_ids);
    // $pusher->setNotificationAlert($notice_content);
    $pusher->iosNotification($notice_content,$ios_extra);
    $pusher->options(array(
            'apns_production' => false,
        ));
    try {
        $pusher->send();
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        // print $e;exit;
    }
}