<?php
/**
 * @tutorial 多机部署时，slave部分只能配置可多机运行的脚本（不影响业务）！这些已经注释掉了。
 *
 * @var \omnilight\scheduling\Schedule $schedule
 * manaual:   ./yii schedule/run --scheduleFile=@console/config/schedule_slave.php
 * manaual:   ./yii schedule/run --scheduleFile=@console/config/schedule_stats.php
 *
 * crontab:
    * * * * * echo >>/tmp/schedule.log; date >>/tmp/schedule.log 2>&1
 * * * * * /usr/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule_slave.php >>/tmp/schedule.log 2>&1
 * * * * * /usr/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule_stats.php >>/tmp/schedule.log 2>&1
 */

$_date = \date('Ymd');
$_pwd = \getcwd();

$schedule->exec(': > /tmp/tqb_schedule_weixin.log ')->cron('0 0 * * *'); # 清空schedule.log

if (YII_ENV_PROD) {
    ################# UserCouponController #####################
//    $schedule->command("weixin-msg/loan-tips >>/data/tqb_log/_loan_tip_{$_date}.log 2>&1 &\; ls ")->cron('0 12 * * *'); # 14天12点推送用户还款信息
//    $schedule->command("weixin-msg/loan-tips >>/data/tqb_log/_loan_tip_{$_date}.log 2>&1 &\; ls ")->cron('0 18 * * *'); # 14天18点推送用户还款信息
//    $schedule->command("weixin-msg/loan-tips >>/data/tqb_log/_loan_tip_{$_date}.log 2>&1 &\; ls ")->cron('0 22 * * *'); # 14天22点推送用户还款信息
//    $schedule->command("weixin-msg/loan-tip >>/data/tqb_log/_loan_tips_{$_date}.log 2>&1 &\; ls ")->cron('0 22 * * *'); # 13天推送用户还款信息
//    $schedule->command("weixin-msg/pay-status >>/data/tqb_log/_pay_status_{$_date}.log 2>&1 &\; ls ")->cron('*/2 * * * *'); # 推送用户还款信息
//    $schedule->command("weixin-msg/loan-success >>/data/tqb_log/_loan_success_{$_date}.log 2>&1 &\; ls ")->cron('*/2 * * * *'); # 推送用户借款成功信息

    #####################pv统计  #############################
//    $schedule->command("log/get-log >>/data/tqb_log/_pv_log_{$_date}.log 2>&1 &\; ls ")->cron('*/20 * * * *'); #统计pv
    ################################################################## 统计脚本end##################################################################
    $schedule->command("channel-check/source-statistic >>/data/tqb_log/_source_statistic_{$_date}.log 2>&1 &\; ls ")->cron('0 */1 * * *'); #渠道注册统计（每1个小时更新一次）
    $schedule->command("channel-check/source-statistic >>/data/tqb_log/_source_statistic_{$_date}.log 2>&1 &\; ls ")->cron('58 23 * * *'); #渠道注册统计（每天23点58做当天最后一次更新）
}
