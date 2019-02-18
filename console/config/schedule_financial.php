<?php
/**
 * @var \omnilight\scheduling\Schedule $schedule
 *
 * manaual:   ./yii schedule/run --scheduleFile=@console/config/schedule_financial.php
 *
 * crontab:
    * * * * * echo >>/tmp/schedule.log; date >>/tmp/schedule.log 2>&1
    * * * * * /usr/local/bin/php /data/www/wzdai.com/yii schedule/run --scheduleFile=@console/config/schedule_financial.php >>/tmp/schedule_.log 2>&1
 */

$_date = \date('Ymd');
$_pwd = \getcwd();

$schedule->exec(': > /tmp/tqb_schedule_financial.log ')->cron('0 0 * * *'); # 清空schedule.log

if (YII_ENV_PROD) { #非线上环境，手动执行

    ###########################打款脚本  begin
    $schedule->command("financial-loan-pay/search-order-status >>/data/tqb_log/_search_order_status_{$_date}.log 2>&1 &\; ls ")->cron('*/20 8-23 * * *'); #查询订单打款状态(20分钟查询一次)
    ###########################每天23点55分跟59分查询订单打款状态
    $schedule->command("financial-loan-pay/search-order-status >>/data/tqb_log/_search_order_status_{$_date}.log 2>&1 &\; ls ")->cron('55,59 23 * * *'); #查询订单打款状态

    $schedule->command("financial-loan-pay/query-order-status >>/data/tqb_log/_query_order_status_{$_date}.log 2>&1 &\; ls ")->cron('*/20 * * * *'); #查询需人工审核订单的打款状态
    $schedule->command("ygd-check/ygd-zc-cw-check >>/data/tqb_log/_ygd_check_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #生成打款记录
    $schedule->command("financial-loan-pay/lite-withdraw-jshb >>/data/tqb_log/_financial_withdraw_{$_date}.log 2>&1 &\; ls ")->cron('* * * * *'); #打款脚本
    #############################打款脚本 end

    #########################还款脚本 begin
    $schedule->command("loan-collection/calculation-interest 2 0 >>/data/tqb_log/_calculation_interest_{$_date}_1.log 2>&1 &\; ls ")->cron('30 3 * * *'); #计算逾期费
    $schedule->command("loan-collection/calculation-interest 2 1 >>/data/tqb_log/_calculation_interest_{$_date}_2.log 2>&1 &\; ls ")->cron('30 3 * * *'); #计算逾期费

    $schedule->command("ygd-reject-new/get-debit-platform >>/data/tqb_log/_get-debit-platform_{$_date}.log 2>&1 &\; ls ")->cron('*/5 * * * *'); #查询批量代扣的第三方通道
    $schedule->command("ygd-reject-new/active-search-debit-status >>/data/tqb_log/_active_search_debit_status_{$_date}.log 2>&1 &\; ls ")->cron('*/10 6-23 * * *');//扣款状态查询(10分钟查询一次)
    $schedule->command("ygd-reject-new/active-search-debit-status >>/data/tqb_log/_active_search_debit_status_{$_date}.log 2>&1 &\; ls ")->cron('*/10 0-4 * * *');//扣款状态查询(10分钟查询一次)
    $schedule->command("ygd-reject-new/hc-debit-status >>/data/tqb_log/_active_search_debit_status_{$_date}.log 2>&1 &\; ls ")->cron('*/20 * * * *');//汇潮扣款状态查询

//    $schedule->command("ygd-reject-new/apply-to-financial-debit 1000 2 0 >>/data/tqb_log/_apply_financial_{$_date}.log 2>&1 &\; ls ")->cron('0 5 * * *'); #生成扣款记录
//    $schedule->command("ygd-reject-new/apply-to-financial-debit 1000 2 1 >>/data/tqb_log/_apply_financial_{$_date}.log 2>&1 &\; ls ")->cron('1 5 * * *'); #生成扣款记录

    #由于银生宝支付代扣率比较低，暂停银生宝批量代扣
//    $schedule->command("ygd-reject-new/auto-debit-one >>/data/tqb_log/_auto_debit_{$_date}.log 2>&1 &\; ls ")->cron('0 6,21 * * *'); #自动扣款 0-2还款
//    $schedule->command("ygd-reject-new/auto-debit-two >>/data/tqb_log/_auto_debit_two_{$_date}.log 2>&1 &\; ls ")->cron('*/20 * * * *'); #自动扣款 3-6天 小额500扣款
//    $schedule->command("ygd-reject-new/auto-debit-three >>/data/tqb_log/_auto_debit_three_{$_date}.log 2>&1 &\; ls ")->cron('*/30 * * * *'); #自动扣款 7-200天小额200扣款


//    $schedule->command("ygd-reject-new/review-not-confirmed-order >>/data/tqb_log/_review_not_confirmed_order_{$_date}.log 2>&1 &\; ls ")->cron('0 */4 * * *'); #合利宝代扣回调信息有“处理中”重新拉回调信息
    #$schedule->command("ygd-reject-new/review-hc-order >>/data/tqb_log/_review_hc_order_{$_date}.log 2>&1 &\; ls ")->cron('0 */3 * * *'); #汇潮支付订单重新查询

    $schedule->command("ygd-check/ali-pay-repayment >>/data/tqb_log/_alipay_repayment_{$_date}.log 2>&1 &\; ls ")->cron('*/2 * * * *'); #定时更新支付宝还款
    $schedule->command("ygd-check/wei-xin-repayment >>/data/tqb_log/_wei_xin_repayment_{$_date}.log 2>&1 &\; ls ")->cron('*/2 * * * *'); #定时更新微信还款
    $schedule->command("ygd-reject-new/update-query-number >>/data/tqb_log/_update-query-number_{$_date}.log 2>&1 &\; ls ")->cron('*/25 * * * *');//未回调 脚本置为失败的查询次数25分钟查询一次
    ######################还款脚本 end

    $schedule->command("ygd-reject-new/auto-check-over-principal >>/data/tqb_log/_auto_check_over_principal_{$_date}.log 2>&1 &\; ls ")->cron('55 23 * * *'); #逾期7天以上还完借款本金后，将借款订单修改为已还款，同时加入黑名单(23点55)
}