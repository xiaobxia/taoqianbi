<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 *
 * manaual:   ./yii schedule/run --scheduleFile=@console/config/schedule_message.php
 *
 * crontab:
    * * * * * echo >>/tmp/schedule_message.log; date >>/tmp/schedule_message.log 2>&1
    * * * * * /usr/bin/php /data/taoqianbi/yii schedule/run --scheduleFile=@console/config/schedule.php >>/tmp/schedule_message.log 2>&1
 */

$_date = \date('Ymd');
$_pwd = \getcwd();

$schedule->exec(': > /tmp/tqb_schedule_message.log ')->cron('0 0 * * *'); # 清空schedule_message.log


if (YII_ENV_PROD) { #非线上环境，手动执行

    ############################ 定期执行
    $schedule->command("message-notice/send-message-repayment >>/data/tqb_log/_message_repayment_{$_date}.log 2>&1 &\; ls ")->cron('0 8 * * *'); #到期前一天|自动发短信提醒
    $schedule->command("message-notice/send-message-repayment-one >>/data/tqb_log/_message_repayment_one_{$_date}.log 2>&1 &\; ls ")->cron('1 8 * * *'); #到期前二天|自动发短信提醒
    $schedule->command("message-notice/send-message-repayment-two >>/data/tqb_log/_message_repayment_two_{$_date}.log 2>&1 &\; ls ")->cron('30 8 * * *'); #到期前二天|自动发短信提醒
    #$schedule->command("message-notice/send-message-repayment-two >>/data/tqb_log/_message_repayment_two_{$_date}.log 2>&1 &\; ls ")->cron('30 11 * * *'); #到期前一天|自动发短信提醒
    #$schedule->command("message-notice/send-message-repayment-three >>/data/tqb_log/_message_repayment_three_{$_date}.log 2>&1 &\; ls ")->cron('30 15 * * *'); #到期前当日|自动发短信提醒给未还新用户
    #$schedule->command("message-notice/send-message-repayment-four >>/data/tqb_log/_message_repayment_four_{$_date}.log 2>&1 &\; ls ")->cron('0 18 * * *'); #到期前一天|自动发短信提醒
    #$schedule->command("message-notice/send-message-repayment-five >>/data/tqb_log/_message_repayment_five_{$_date}.log 2>&1 &\; ls ")->cron('15 9 * * *'); #到期前一天|自动发短信提醒
    
     // 语音通知  提前还款
    #$schedule->command("message-notice/send-voice-message-repayment >>/data/tqb_log/_voice_message_repayment_{$_date}.log 2>&1 &\; ls ")->cron('0 12 * * *'); #到期前一天|自动发语音提醒
    #$schedule->command("message-notice/send-voice-message-repayment >>/data/tqb_log/_voice_message_repayment_{$_date}.log 2>&1 &\; ls ")->cron('0 20 * * *'); #到期前一天|自动发语音提醒(20点)
    #$schedule->command("message-notice/send-voice-message-repayment-two >>/data/tqb_log/_voice_message_repayment_two_{$_date}.log 2>&1 &\; ls ")->cron('0 13 * * *'); #到期前当日|自动发语音提醒
    #$schedule->command("message-notice/send-voice-message-repayment-three >>/data/tqb_log/_voice_message_repayment_three_{$_date}.log 2>&1 &\; ls ")->cron('0 19 * * *'); #到期前当日|自动发语音提醒

    #$schedule->command("message-notice/send-voice-message-repayment-six >>/data/tqb_log/_voice_message_repayment_six_{$_date}.log 2>&1 &\; ls ")->cron('0 21 * * *'); #到期前当日|自动发语音提醒21点
    #$schedule->command("message-notice/send-voice-message-repayment-seven >>/data/tqb_log/_voice_message_repayment_seven_{$_date}.log 2>&1 &\; ls ")->cron('0 18 * * *'); #到期前当日|自动发语音提醒18点
    
    // 逾期玄武
    #$schedule->command("message-notice/send-voice-message-repayment-four >>/data/tqb_log/_voice_message_repayment_four_{$_date}.log 2>&1 &\; ls ")->cron('0 17 * * *'); #逾期一天
    // 赛邮 逾期
    #$schedule->command("message-notice/send-voice-message-repayment-five >>/data/tqb_log/_voice_message_repayment_five_{$_date}.log 2>&1 &\; ls ")->cron('0 11 * * *'); #逾期一天
    
    $schedule->command("message-notice/collect-message >>/data/tqb_log/_collect_message_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #自动收取短信上行
    #$schedule->command("message-notice/push-repayment >>/data/tqb_log/_message_push_repayment{$_date}.log 2>&1 &\; ls ")->cron('30 14 * * *'); #到期前一天|逾期当天 逾期三天 逾期十天自动发短信提醒

    $schedule->command("user/down-redis-contents 1 >>/data/tqb_log/_down_redis_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #手机短信 落地
    $schedule->command("user/down-redis-contents 2 >>/data/tqb_log/_down_redis_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #手机app数据 落地
    $_drc3 = 2;
    for ($d3 = 0; $d3 < $_drc3; $d3++) {
        $schedule->command( "user/down-redis-contents 3 >>/data/tqb_log/_down_redis_{$_date}.log 2>&1 &\; ls " )->cron('* * * * *'); #用户通讯录 落地
    }

    $schedule->command("supplement/edu >>/data/tqb_log/_supplement-edu_{$_date}.log 2>&1 &\; ls ")->cron('*/20 * * * *');//额度计算中用户重新计算额度
    ############################ 其他脚本
    $schedule->command("monitor/run >>/data/tqb_log/monitor-run_{$_date}.log 2>&1 &\; ls ")->cron('*/10 * * * *'); # 监控脚本

}
