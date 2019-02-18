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
 * * * * * /usr/local/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule_slave.php >>/tmp/schedule.log 2>&1
 * * * * * /usr/local/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule_stats.php >>/tmp/schedule.log 2>&1
 */

$_date = \date('Ymd');
$_pwd = \getcwd();

$schedule->exec('touch /tmp/tqb_schedule_job.status ')->everyMinute();
$schedule->exec(': > /tmp/tqb_schedule.log ')->cron('0 0 * * *'); # 清空schedule.log

if (YII_ENV_PROD) {
    ################################################################## 统计脚本begin 只在106.15.72.93上执行##################################################################
    ################# CoreDataController #####################
    $schedule->command("core-data/daily-loan >>/data/tqb_log/_daily_loan_{$_date}.log 2>&1 &\; ls ")->cron('*/5 * * * *'); #每日放款数据
    $schedule->command("core-data/daily-loan3 >>/data/tqb_log/_daily_loan_{$_date}.log 2>&1 &\; ls ")->cron('45 8 * * *'); # 更新银行通知延期导致的放款数据变更

    ################# DailyController #####################
    $schedule->command("daily/order-reject-reason-rank >>/data/tqb_log/_order_reject_reason_rank_{$_date}.log 2>&1 &\; ls ")->cron('14,34,54 * * * *'); #每日订单拒绝理由统计排行
    $schedule->command("daily/repay-rates-list >>/data/tqb_log/_repay_rates_list_{$_date}.log 2>&1 &\; ls ")->cron('0 */2 * * *'); #每日还款分析（天）（贷款余额）
    $schedule->command("daily/repay-rates-list 3 >>/data/tqb_log/_repay_rates_list_{$_date}.log 2>&1 &\; ls ")->cron('0 */6 * * *'); #每日还款分析(月)（逾期分布）
    $schedule->command("daily/trade-list >>/data/tqb_log/_trade_list_{$_date}.log 2>&1 &\; ls ")->cron('0 */1 * * *'); #收付款统计天(当天)
    $schedule->command("daily/trade-list 2 >>/data/tqb_log/_trade_list_{$_date}.log 2>&1 &\; ls ")->cron('0 7 * * *'); #收付款统计天(3个月)
    $schedule->command("daily/operating-costs >>/data/tqb_log/_operating_costs_{$_date}.log 2>&1 &\; ls ")->cron('10 */1 * * *'); #运营成本统计
    $schedule->command("daily/loan-repay-list >>/data/tqb_log/_loan_repay_list_{$_date}.log 2>&1 &\; ls ")->cron('*/15 * * * *'); #每日借还款数据对比
    $schedule->command("daily/daily-data >>/data/tqb_log/_daily_data_{$_date}.log 2>&1 &\; ls ")->cron('0 0 * * * *'); #日报
    $schedule->command("daily/day-data-statistics-run >>/data/tqb_log/_day_data_statistics_run_{$_date}.log 2>&1 &\; ls ")->cron('*/10 * * * *'); #每日到期还款续借率（当天的+14天）
    $schedule->command("daily/day-data-statistics-run1 >>/data/tqb_log/_day_data_statistics_run_{$_date}.log 2>&1 &\; ls ")->cron('*/15 * * * *'); #每日到期还款续借率（7天内）
    $schedule->command("daily/day-data-statistics-run2 >>/data/tqb_log/_day_data_statistics_run_{$_date}.log 2>&1 &\; ls ")->cron('0 */2 * * *'); #每日到期还款续借率（7-30天以内的）
    $schedule->command("daily/day-data-statistics-run3 >>/data/tqb_log/_day_data_statistics_run_{$_date}.log 2>&1 &\; ls ")->cron('0 3 * * *'); #每日到期还款续借率（30天-120以内的）

    $schedule->command("daily/loan-money-list >>/data/tqb_log/_loan_money_list_{$_date}.log 2>&1 &\; ls ")->cron('20 */2 * * *'); #每日借款额度统计

    $schedule->command("daily/daily-loan-info>>/data/tqb_log/_daily_loan_info_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #每日注册,借款,通过，通过率统计

    $schedule->command("daily/change-regisger-load-data >>/data/tqb_log/_change_regisger_load_data_{$_date}.log 2>&1 &\; ls ")->cron('50 23 * * *'); #每日放款注册统计(每天23点50分统计)

    ################# FinanceDataController 财务 #####################
    $schedule->command("finance-data/day-not-yet-principal 1 >>/data/tqb_log/day-not-yet-principal-{$_date}.log 2>&1 &\; ls ")->cron('10 */1 * * *'); #每日未还本金列表
    #===================每天凌晨1点执行渠道每日成本统计==================
    $schedule->command("channel-count/channel-loan-count ".date('Y-m-d', strtotime('-1 day'))." >> /data/tqb_log/channel-loan-count-{$_date}.log 2>&1 &\; ls ")->cron('0 1 * * *');
    ################################################################## 统计脚本end##################################################################

}
