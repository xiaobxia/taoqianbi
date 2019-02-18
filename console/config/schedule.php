<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 *
 * manaual:   ./yii schedule/run --scheduleFile=@console/config/schedule.php
 *
 * crontab:
    * * * * * echo >>/tmp/schedule.log; date >>/tmp/schedule.log 2>&1
    * * * * * /usr/local/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule.php >>/tmp/schedule.log 2>&1
 */

$_date = \date('Ymd');
$_pwd = \getcwd();

$schedule->exec('touch /tmp/tqb_schedule_job.status ')->everyMinute();
$schedule->exec(': > /tmp/tqb_log/tqb_schedule.log ')->cron('0 0 * * *'); # 清空schedule.log

### 业务部分
$schedule->command('risk-util/cache-jxl-city ')->cron('*/5 * * * *'); #缓存聚信立公积金城市列表

if (YII_ENV_PROD) { #非线上环境，手动执行
    #发送例行邮件
//    $schedule->command("risk-util/send-queue-mail >>/data/tqb_log/send_queue_mail_{$_date}.log 2>&1 &\; ls ")->cron('*/30 * * * *');

    #获取公积金用户token
//    for ($n = 0; $n < 5; $n ++) {
//        $schedule->command("risk-util/get-house-fund-token {$n} >>/data/tqb_log/_house_fund_token_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *');
//    }

    #拉取聚信立公积金报告
    $total = 3;
    for ($m = 0; $m < $total; $m ++) {
        $schedule->command("risk-util/get-house-fund-report 200 {$total} {$m} >>/data/tqb_log/_house_fund_report_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *');
    }

    #重置无效的运营商认证状态
    $schedule->command("risk-util/reset-jxl-queue-status >>/data/tqb_log/_reset_jxl_queue_status_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *');

    #周期性执行
    $_set_line = 1;
    for ($i = 0; $i < $_set_line; $i++) {
        $schedule->command("credit-line/set-credit-line 347 {$i} >>/data/tqb_log/_set_credit_line_{$_date}.log 2>&1 &\; ls")->cron('* * * * *'); #计算额度  @347
    }


    $_get_ds = 1;
    for ($j = 0; $j < $_get_ds; $j++) {
        $schedule->command("credit-check/get-data-source 0 {$j} >>/data/tqb_log/_get_data_source_{$_date}.log 2>&1 &\; ls")->cron('* * * * *'); #机审步骤1(收集数据)
    }
    $schedule->command("credit-check/order-delay-to-list >>/data/tqb_log/_order_delay_to_list_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #收集数据延迟入队列

    $_auto_ck = 1;
    for ($k = 0; $k < $_auto_ck; $k++) {
        $schedule->command("risk-control/auto-check {$k} >>/data/tqb_log/_auto_check_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #机审步骤2 390 336 （执行决策）

    }

    $schedule->command("credit-check/check-orders 100 >>/data/tqb_log/_check_orders_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #老用户跳过机审(机审1.收集数据)
    # $schedule->command("risk-manual-check/check 322 >>/data/tqb_log/_risk_man_check_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #机器初审
    #$schedule->command("risk-control/reject-cs-order 500 >>/data/tqb_log/_reject_cs_order_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #!!! 初审存疑，暂时直接拒 [TODO] 0-7,18-23
    $schedule->command("risk-control/update-loan 100 >>/data/tqb_log/_risk_control_update_loan_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #人工复审（分配资方）
}

