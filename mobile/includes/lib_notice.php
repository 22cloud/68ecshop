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
            'apns_production' => true,
        ));
    try {
        $pusher->send();
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        print $e;exit;
    }
}

// 创建用户生日的定时任务
function create_schedule_user_birth()
{
    include_once(ROOT_PATH . 'includes/cls_transport.php');

    if (!$user_id = $_SESSION['user_id'])
    {
        return false;
    }

    $run_schedule = false;
    // 检查用户是否已有生日定时任务
    $year = date('Y');
    $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('users_schedule')." WHERE year = '$year' AND user_id = '$user_id'";
    $has_schedule = $GLOBALS['db']->getOne($sql);
    if (!$has_schedule) {
        $run_schedule = true;
    }

    // 获取用户的生日信息
    $sql = "select birthday FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = ".$user_id;
    $birthday = $GLOBALS['db']->getOne($sql);

    if ($birthday != '0000-00-00' && $run_schedule) {
        $now_year_birthday = $year.'-'.date("m-d",strtotime($birthday));
        if (strtotime($now_year_birthday) >= strtotime(date('Y-m-d',time()))) {
            $trigger_time = $now_year_birthday.' 16:50:00';
            $run_schedule = true;
        }else{
            $run_schedule = false;
        }
    }else{
        $run_schedule = false;
    }


    if ($run_schedule) {
        $app_key = '99883b14e738658ee3ae177f';
        $master_secret = '0fd97e9a88dcbb8a805e99c6';
        $base64_auth_string = base64_encode($app_key.':'.$master_secret);

        $header_data = array(
                'Authorization' => $base64_auth_string
            );

        $schedulename = 'birth_notice_'.$user_id;
        $birthday_show_info = '生日快乐！！';

        // $post_data = array(
        //         'name' => $schedulename,
        //         "enabled" => true,
        //         "trigger" => array(
        //                 'single' => array(
        //                         'time' => $trigger_time
        //                     )
        //             ),
        //         "push" => array(
        //                 'platform' => 'all',
        //                 'audience' => 'all',
        //                 'notification' => array(
        //                         "ios" => array(
        //                             "alert" => $birthday_show_info,
        //                             "sound" => "default",
        //                             "extras" => array(
        //                                 'url' => 'user.php?act=notices'
        //                             )
        //                         )
        //                     ),
        //                 'message' => array(
        //                         'msg_content' => $birthday_show_info
        //                     ),
        //                 'options' => array(
        //                         'time_to_live' => 60
        //                     ),
        //             )
        //     ); 
        // $t = new transport(-1,-1,-1,1);
        // $ct = 1;
        // $scheduleInfo = $t->request('https://api.jpush.cn/v3/schedules', json_encode($post_data) ,'POST', $header_data, $ct);
        // $scheduleInfo_arr = json_decode($scheduleInfo['body']);

        $app_key = '99883b14e738658ee3ae177f';
        $master_secret = '0fd97e9a88dcbb8a805e99c6';
        $client = new JPush($app_key, $master_secret);
        $ios_extra = array(
                'extras' => array(
                    'url' => 'user.php?act=notices'
                )
            );
        // 推送消息
        // $pusher = $client->push();
        // $pusher->setPlatform('all');
        // $pusher->addAlias(array($user_id));
        // $pusher->iosNotification($birthday_show_info,$ios_extra);
        // $pusher->build();
        // 
$payload = $client->push()
    ->setPlatform("all")
    ->addAlias(array($user_id))
    ->iosNotification($birthday_show_info,$ios_extra)
    ->options(array(
            'apns_production' => true,
        ))
    ->build();

        $response = $client->schedule()->createSingleSchedule($schedulename, $payload, array("time"=>$trigger_time));

        $birth_schedule_id = $scheduleInfo_arr['body']['schedule_id'];
        // 记录生日的定时任务ID与用户的关系
        if ($birth_schedule_id) {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('users_schedule') . " (user_id, schedule_id, year, birth_day) " .
                "VALUES ('$user_id', '$birth_schedule_id', '".$year."', '".date("m-d",strtotime($birthday))."' )";
            $GLOBALS['db']->query($sql);
        }
    }

}