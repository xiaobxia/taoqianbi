<?php
use common\helpers\Url;
use common\models\LoanProject;

$topmenu = $menu = array();

// 一级菜单
$topmenu = array (
    'index'             => array('首页', Url::toRoute(['main/home'])),
    'user'              => array('用户管理', Url::toRoute(['loan/ygd-list'])),
    'loan'              => array('借款管理', Url::toRoute(['pocket/pocket-list'])),
    'financial'         => array('财务管理', Url::toRoute(['financial/loan-list'])),
    'credit'            => array('风控管理', Url::toRoute(['zmop/user-zmop-list'])),
    'fund'              => array('资方管理', Url::toRoute(['loan-order-quota/index'])),
    'service'           => array('客服管理', Url::toRoute(['custom-management/ygd-loan-person-list'])),
    'content'           => array('内容管理', Url::toRoute(['repayment-config/list'])),
    'data_analysis'     => array('数据分析', Url::toRoute(['daily/daily-attribute'])),
    'data_monitor'     => array('数据监控', Url::toRoute(['core-data/user-stat-chart'])),
    'system'               => array('系统管理', Url::toRoute(['back-end-admin-user/list'])),
);

// 二级菜单-首页
$menu['index'] = array(
    'menu_home' => array('管理中心首页', Url::toRoute(['main/home'])),
);

// 二级菜单-用户管理
$menu['user']     = array(
    //用户管理
    'menu_ygd_user_begin'             => array('用户管理','groupbegin'),
    'menu_ygd_user'                   => array('用户列表', Url::toRoute(['loan/ygd-list'])),
    'menu_today_login_user'           => array('今日登录用户', Url::toRoute(['loan/login-list'])),
    'loan_person_mobile_contacts'     => array('用户通讯录', Url::toRoute(['mobile-contacts/mobile-contacts-list'])),
    'loan_person_house_fund'          => array('用户公积金', Url::toRoute(['house-fund/house-fund-list'])),
    'loan_person_alipay'              => array('用户支付宝认证', Url::toRoute(['icekredit/alipay-list'])),
    'loan_black_list'                 => array(APP_NAMES.'黑名单', Url::toRoute(['loan-black-list/show-list'])),
    'loan_blacklist_list'             => array('黑名单规则列表', Url::toRoute(['loan-blacklist-detail/list'])),
    'loan_out_del_list'               => array('注销用户记录列表', Url::toRoute(['loan/out-del-list'])),
    'menu_bankcard_list'              => array('银行卡列表', Url::toRoute(['bank-card/card-list'])),
    'menu_card_realname'              => array('用户实名列表', Url::toRoute(['bank-card/card-real-name'])),
    'menu_user_verification_list'     => array('用户认证列表', Url::toRoute(['user-info/user-verification-list'])),
    'loan_person_message_log'         => array('上行短信日志', Url::toRoute(['mobile-contacts/message-log'])),
    'loan_person_message_status'      => array('短信发送状态', Url::toRoute(['mobile-contacts/message-status'])),
    'menu_sensitive_dict'             => array('敏感词列表', Url::toRoute(['sensitive-dict/show-list'])),
    'menu_sensitive_dict_censor'      => array('敏感词用户列表', Url::toRoute(['sensitive-dict/censor-list'])),
//    'menu_ygd_address'                =>array('收获地址',Url::toRoute(['address/ygd-list'])),
    'menu_ygd_user_end'               => array('用户管理', 'groupend'),

    //额度管理
    'menu_credit_limit_begin'    => array('额度管理', 'groupbegin'),
    'menu_credit_limit_list'    => array('信用额度列表', Url::toRoute(['user-info/loan-list'])),
    'menu_credit_limit_log'        => array('信用额度使用流水', Url::toRoute(['user-info/asset-log'])),
    'menu_credit_review_log'    => array('信用额度审核', Url::toRoute(['user-info/credit-review-list'])),
    'menu_credit_modify_log'    => array('信用额度调整流水', Url::toRoute(['user-info/credit-modify-log'])),
    'menu_credit_line'    => array('授信额度详情', Url::toRoute(['credit-line/show-list'])),
    'menu_credit_limit_end'         => array('额度管理', 'groupend'),

    //利息管理
    'menu_check_begin'                => array('利息管理', 'groupbegin'),
    'menu_interest_log'               => array('利息流水列表', Url::toRoute(['user-info/interest-log'])),
//    'menu_interest_log_list'          => array('利息错误日志', Url::toRoute(['user-info/interest-error-log'])),
    'menu_lqd_interest_check_list'    => array('零钱包利息错误核对', Url::toRoute(['user-info/lqd-interest-error-check'])),
    'menu_check_end'                  => array('利息管理', 'groupend'),
);

// 二级菜单-借款管理
$menu['loan']     = array(
    //用户借款管理
    'menu_loan_begin'                => array('用户借款管理', 'groupbegin'),
    'menu_loan_lqb_list'            => array('借款列表', Url::toRoute(['pocket/pocket-list', 'page_type'=>1])),
    'menu_loan_lqb_reject_list'            => array('借款拒绝列表', Url::toRoute(['pocket/pocket-reject-list'])),
    'menu_ygb_zc_lqd_fk_lb'            => array('放款列表', Url::toRoute(['staff-loan/pocket-loan-list'])),
//    'menu_credit_add_limit_list'=> array('提额申请列表', Url::toRoute(['user-info/add-limit-list'])),
    'menu_loan_end'                    => array('用户借款管理', 'groupend'),

    //风控管理
    'menu_pay_begin'                => array('风控管理', 'groupbegin'),
    'menu_pay_loan_lqb_list'        => array('借款列表', Url::toRoute(['pocket/pocket-list'])),
    'menu_ygb_zc_lqd_auto'            => array('待机审订单列表', Url::toRoute(['pocket/pocket-auto-trail-list'])),
    'menu_ygb_zc_lqd_reject'        => array('机审拒绝订单列表', Url::toRoute(['pocket/pocket-auto-reject-list'])),
    'menu_get_order_zc_lqd_cs'            => array('订单领取', Url::toRoute(['pocket/get-order'])),
    'menu_other_zc_lqd_cs'            => array('人工初审', Url::toRoute(['pocket/other-trail-list'])),
    'menu_ygb_zc_lqd_fs'            => array('人工复审', Url::toRoute(['pocket/pocket-retrail-list'])),
//    'menu_ygb_zc_lqd_al'            => array('审核员列表', Url::toRoute(['pocket/assessor-list'])),
    'menu_audit_number_list'            => array('审核订单数量', Url::toRoute(['pocket/audit-number'])),
    'menu_push_redis'            => array('重入风控队列', Url::toRoute(['pocket/push-redis'])),
    'menu_pay_end'                    => array('风控管理', 'groupend'),

    //贷后管理
    'menu_repay_begin'                => array('贷后管理', 'groupbegin'),
    'menu_ygb_zc_lqd_hk'            => array('零钱包还款列表', Url::toRoute(['staff-repay/pocket-repay-list'])),
//    'menu_ygb_zc_lqd_yq'            => array('逾期中列表', Url::toRoute(['staff-repay/pocket-overdue-list'])),
    'menu_ygb_zc_lqd_hkk'            => array('还款卡列表', Url::toRoute(['staff-repay/repay-card-list'])),
    'menu_ygb_zc_lqd_repay_edit_log'   => array('修改实际还款金额日志列表', Url::toRoute(['staff-repay/repay-edit-log-list'])),
    'menu_repay_end'                => array('贷后管理', 'groupend'),
);


// 二级菜单-财务管理
$menu['financial'] = array(
    //打款管理
    'menu_loan_manage_begin'        => array('打款管理', 'groupbegin'),
    'menu_loan_list'                => array('打款列表', Url::toRoute(['financial/loan-list'])),
    'menu_loan_dksh_list'           => array('打款审核', Url::toRoute(['financial/loan-withdraw-list'])),
    'menu_loan_manage_end'            => array('打款管理', 'groupend'),

    //扣款管理
    'menu_loan_mange_begin'            =>array('扣款管理','groupbegin'),
    'menu_debit_list'                => array('扣款列表', Url::toRoute(['financial/debit-list'])),
    'menu_debit_wait_list'          => array('待扣款列表', Url::toRoute(['financial/debit-wait-list'])),
    'menu_debit_falied_list'        => array('扣款失败列表', Url::toRoute(['financial/debit-falied-list'])),
    'menu_debit_alipay_list'        => array('支付宝还款列表', Url::toRoute(['financial/alipay-list'])),
    'menu_debit_alipay_record'      => array('支付宝交易流水', Url::toRoute(['financial/alipay-record'])),
    'menu_debit_weixin_list'      => array('微信还款列表', Url::toRoute(['financial/weixin-list'])),
    'menu_debit_weixin_record'      => array('微信交易流水', Url::toRoute(['financial/weixin-record'])),
    'menu_debit_bankpay_list'       => array('还款日志列表', Url::toRoute(['financial/bankpay-list'])),
//    'menu_refund_list'       => array('退款列表', Url::toRoute(['financial/refund-list'])),
    'menu_debit_debitlog_list'        => array('自动扣款日志列表', Url::toRoute(['financial/deduct-money-log'])),
    'menu_debit_helibao'        => array('合利宝参数统计', Url::toRoute(['financial/heliabao-statistic'])),
    'menu_suspect_debit_list'        => array('扣款观察列表', Url::toRoute(['financial/suspect-debit-lost'])),
//    'menu_debit_yeepay_list'        => array('打款扣款结果查询', Url::toRoute(['financial/yee-pay-query'])),
    'menu_debit_lose_order_list'        => array('补单数据列表', Url::toRoute(['financial-debit/lose-debit-order'])),
    'menu_debit_rid_overdue_log_list'        => array('减免滞纳金列表', Url::toRoute(['financial-debit/rid-overdue-log-list'])),
    'menu_loan_mange_end'            =>array('扣款管理','groupend'),

    //统计管理列表
    'menu_financial_statistics_begin'=> array('统计管理列表','groupbegin'),
//    'menu_financial_subsidiary_ledger_list'        => array('收付款统计表', Url::toRoute(['financial/subsidiary-ledger-list'])),
//    'menu_financial_loan_balance_list'        => array('贷款余额统计表', Url::toRoute(['financial/loan-balance-list'])),
    'menu_financial_overdue_list'        => array('逾期数据分布', Url::toRoute(['financial/overdue-list'])),
    'menu_financial_expense_list'        => array('运营成本统计列表', Url::toRoute(['financial/expense-list'])),
    'menu_financial_day_notyet_principal_list'=> array('每日未还本金列表', Url::toRoute(['financial/day-not-yet-principal-list'])),
    'menu_financial_day_notyet_principal_account'=> array('每日未还本金对账', Url::toRoute(['financial/day-not-yet-principal-account'])),
    'menu_financial_statistics_end'    => array('统计管理列表', 'groupend'),
);

// 二级菜单-风控管理
$menu['credit'] = array(
    'menu_credit_manage_begin'        => array('用户征信管理', 'groupbegin'),
    'menu_credit_user_list'           => array('用户征信管理', Url::toRoute(['zmop/user-zmop-list'])),
//    'menu_credit_user_onoff'          => array('用户征信开关', Url::toRoute(['zmop/user-credit-onoff'])),
    'menu_ygb_jxl_status_view'        => array('用户运营商认证状态', Url::toRoute(['pocket/jxl-status-view'])),
    'menu_credit_manage_end'          => array('用户征信管理', 'groupend'),

//    'menu_zmop_manage_begin'          => array('芝麻信用管理', 'groupbegin'),
//    'menu_zmop_data_feedback_list'    => array('数据反馈管理', Url::toRoute(['zmop/data-feedback-list'])),
//    'menu_zmop_manage_end'            => array('芝麻信用管理', 'groupend'),

    'menu_error_manage_begin'        => array('征信错误信息', 'groupbegin'),
    'menu_error_message_list'        => array('错误信息', Url::toRoute(['zmop/error-message-list'])),
    'menu_error_manage_end'          => array('征信错误信息', 'groupend'),

    'menu_decision_tree_begin'              => array('决策树管理', 'groupbegin'),
    'menu_decision_tree_characteristics'    => array('特征配置', Url::toRoute(['decision-tree/characteristics-list'])),
    'menu_decision_tree_escape_template'    => array('转义模版', Url::toRoute(['decision-tree/escape-template-list'])),
    'menu_decision_tree_order_report'    => array('订单决策详情', Url::toRoute(['decision-tree/order-report-list'])),
    'menu_decision_tree_rule_report'    => array('授信决策详情', Url::toRoute(['decision-tree/rule-report-list'])),
    'menu_decision_tree_end'                => array('决策树管理', 'groupend'),

//    'menu_decision_statistical_begin'              => array('决策数据分析', 'groupbegin'),
//    'menu_decision_daily_statistic'            => array('订单驳回因素', Url::toRoute(['decision-statistic/daily-report'])),
//    'menu_decision_statistical_end'                => array('决策数据分析', 'groupend'),

//    'menu_encrypt_begin'                    => array('加密传输管理', 'groupbegin'),
//    'menu_encrypt_key'                      => array('密钥配置', Url::toRoute(['encrypt-keys/index'])),
//    'menu_encrypt_end'                      => array('加密传输管理', 'groupend'),


//    'menu_rule_risk_begin'             => array('规则任务管理', 'groupbegin'),
//    'menu_rule_risk_setting_risk'    => array('规则任务设置', Url::toRoute(['rule-risk/setting-risk'])),
//    'menu_rule_risk_end'            => array('规则任务管理', 'groupend'),

//    'menu_card_qualification_begin'             => array('金卡资格管理', 'groupbegin'),
//    'menu_card_qualification_list'            => array('资格列表', Url::toRoute(['card-qualification/list'])),
//    'menu_card_qualification_manual_list'    => array('人工审核', Url::toRoute(['card-qualification/manual-list'])),
//    'menu_card_qualification_credit_line'    => array('提额申请', Url::toRoute(['credit-line/list'])),
//    'menu_card_qualification_end'            => array('金卡资格管理', 'groupend'),

//    'menu_strategic_analysis_begin'             => array('策略分析管理', 'groupbegin'),
//    'menu_strategic_analysis_regression_task_list'=> array('回测任务列表', "http://192.168.39.214/kj_stats/backend/web/index.php?r=regression/task-list"),
//    'menu_strategic_analysis_regression_task_add'=> array('添加回测任务', "http://192.168.39.214/kj_stats/backend/web/index.php?r=regression/task-add"),
//    'menu_strategic_analysis_regression_management_list'=> array('策略列表', "http://192.168.39.214/kj_stats/backend/web/index.php?r=strategy-management/list"),
//    'menu_strategic_analysis_regression_management_add'=> array('添加策略', "http://192.168.39.214/kj_stats/backend/web/index.php?r=strategy-management/create"),
//    'menu_strategic_analysis_end'            => array('策略分析管理', 'groupend'),

//    'menu_grey_analysis_begin'             => array('灰度分析', 'groupbegin'),
//    'menu_grey_analysis_regression_task_list'=> array('日报', Url::toRoute(['grey-analysis/index'])),
//    'menu_grey_analysis_regression_task_add'=> array('统计图', Url::toRoute(['grey-analysis/chart'])),
//    'menu_grey_analysis_regression_management_list'=> array('回归分析', Url::toRoute(['grey-analysis/analysis'])),
//    'menu_grey_analysis_regression_management_add'=> array('任务列表', Url::toRoute(['grey-analysis/list'])),
//    'menu_grey_analysis_end'            => array('灰度分析', 'groupend'),

    'menu_channel_audit_situation_begin'             => array('各渠道审核情况', 'groupbegin'),
    'menu_risk-check-analysis_list'=> array('审核统计', Url::toRoute(['risk-check-analysis/message-list'])),
    'menu_risk_check_refuse_list'  => array('拒绝原因', Url::toRoute(['risk-check-analysis/rfyy-list'])),
    'menu_risk_check_registerloan_list'  => array('放款注册统计', Url::toRoute(['risk-check-analysis/register-loan-list'])),
    'menu_loan_register_info' => array('放款通过注册统计', Url::toRoute(['risk-check-analysis/data-list'])),
    'menu_loan_channel_count' => array('渠道推广转化率统计', Url::toRoute(['channel/loan-count-list'])),
    'menu_channel_audit_situation_end'            => array('各渠道审核情况', 'groupend'),

//    'menu_channel_relation_begin'             => array('用户关系管理', 'groupbegin'),
//    'menu_relation_list'=> array('关系配置', Url::toRoute(['relation/relation-list'])),
//    'menu_relationship_list'=> array('用户关系列表', Url::toRoute(['relation/relationship-list'])),
//    'menu_check_relation_network'=> array('查看用户关系网', Url::toRoute(['relation/check-relation-network'])),
//    'menu_channel_relation_end'    => array('用户关系管理', 'groupend'),
);

// 二级菜单-资方管理
$menu['fund']     = [
    //资方管理
    'menu_fund_begin' => array('资方管理', 'groupbegin'),
    'menu_loan_order_quota' => array('放款订单配额', Url::toRoute(['loan-order-quota/index'])),
    'menu_fund_list' => array('资方列表', Url::toRoute(['loan-fund/index'])),
    'menu_fund_account_list' => array('资方账号主体', Url::toRoute(['fund-account/index'])),
    'menu_order_fund_info' => array('订单关联信息', Url::toRoute(['loan-fund/order-info-list'])),
    'menu_order_fund_log' => array('日志', Url::toRoute(['loan-fund/log-list'])),
    'menu_fund_end' => array('资方管理', 'groupend'),
];

// 二级菜单-资方管理
$menu['service']     = array(
    //用户管理
    'menu_user_begin'                => array('用户管理', 'groupbegin'),
    'menu_user_list'                => array('用户列表', Url::toRoute(['custom-management/ygd-loan-person-list'])),
    'menu_user_credit_list'            => array('用户额度列表', Url::toRoute(['custom-management/loan-list'])),
    'menu_user_credit_log'            => array('用户额度流水', Url::toRoute(['custom-management/credit-modify-log'])),
    'menu_card_list'                => array('银行卡列表', Url::toRoute(['custom-management/card-list'])),
    'menu_card_realname'            => array('用户实名列表', Url::toRoute(['bank-card/card-real-name'])),
    'menu_accumulation_fund_list' => array('用户公积金列表', Url::toRoute(['custom-management/accumulation-fund-list'])),
    'menu_user_end'                     => array('用户管理', 'groupend'),

    //借款管理
    'menu_user_loan_begin'            => array('借款管理', 'groupbegin'),
    'menu_user_loan_list'            => array('借款订单列表', Url::toRoute(['custom-management/pocket-list'])),
    'menu_user_pay_list'            => array('打款列表', Url::toRoute(['custom-management/loan-money-list'])),
    'menu_fund_user_pay_list'            => array('资方打款列表', Url::toRoute(['custom-management/fund-loan-money-list'])),
    'menu_user_loan_end'             => array('借款管理', 'groupend'),

    //还款管理
    'menu_user_repay_begin'         => array('还款管理','groupbegin'),
    'menu_user_repay_list'            => array('还款订单列表', Url::toRoute(['custom-management/pocket-repay-list'])),
    'menu_user_repay_log'            => array('用户还款记录', Url::toRoute(['custom-management/bankpay-list'])),
    'menu_ygb_debit_error'          => array('还款失败', Url::toRoute(['debit-error/error-list'])),
    'menu_user_repay_end'           => array('还款管理','groupend'),

    //征信管理
    'menu_credit_begin'             => array('征信管理','groupbegin'),
    'menu_user_jxl_log'                => array('运营商认证状态', Url::toRoute(['custom-management/jxl-status-view'])),
//    'menu_zmop_data_feedback_list'    => array('芝麻信用授权列表', Url::toRoute(['custom-management/data-feedback-list'])),
    'menu_credit_end'               => array('征信管理','groupend'),
    'user_feedback_begin' => array('反馈管理', 'groupbegin'),
    'user_feedback_list' => array('用户反馈', Url::toRoute(['custom-management/feedback-list'])),
    'user_feedback_end' => array('反馈管理', 'groupend'),
);

// 二级菜单-内容管理
$menu['content'] = array(
    //运营管理
    'menu_operate_manage_begin'             => array('运营管理', 'groupbegin'),
    'menu_operate_repayment_info'            => array('还款配置', Url::toRoute(['repayment-config/list'])),
    'menu_operate_manage_end'               => array('运营管理', 'groupend'),

    //通知管理
    'menu_notice_manage_begin'          => array('通知管理', 'groupbegin'),
    'menu_operate_activity_list'        => array('公告中心', Url::toRoute(['content-activity/list'])),
    'menu_notice_manage_end'            => array('通知管理', 'groupend'),

    //app-banner管理
    'official_websit_banner_begin'      => array('app-banner管理', 'groupbegin'),
    'official_app_banner_list'          => array('app-banner列表', Url::toRoute(['app-banner/list'])),
    'official_websit_banner_end'        => array('banner', 'groupend'),

    //微信管理
//    'menu_weixin_manage_begin'        => array('微信管理','groupbegin'),
//    'menu_weixin_list'                => array('菜单管理', Url::toRoute(['weixin-contract/menu'])),
//    'menu_sendmessage_list'           => array('消息推送', Url::toRoute(['weixin-contract/msg-list'])),
//    'menu_weixin_manage_end'          => array('微信管理','groupend'),

    //发现
//    'menu_find_manage_begin'        => array('发现','groupbegin'),
//    'menu_find_loan_list'          => array('同行管理', Url::toRoute(['find/loan-list'])),
//    'menu_find_other_list'          => array('异行管理', Url::toRoute(['find/other-list'])),
//    'menu_find_colleague_banner_list'  => array('同行banner管理', Url::toRoute(['find/colleague-banner-list'])),
//    'menu_find_banner_list'         => array('异行banner管理', Url::toRoute(['find/banner-list'])),
//    'menu_find_activity_list'       => array('活动管理', Url::toRoute(['find/activity-list'])),
//    'menu_huodong_list'             =>array('首页弹出活动管理',Url::toRoute(['find/huodong-list'])),
//    'menu_find_manage_end'          => array('发现','groupend'),

    //附件管理
    'menu_attachment_begin'        => array('附件管理', 'groupbegin'),
    'menu_attachment_list'        => array('附件列表', Url::toRoute(['attachment/list'])),
//    'menu_attachment_add'        => array('添加附件', Url::toRoute(['attachment/add'])),
    'menu_attachment_end'        => array('附件管理', 'groupend'),
);

// 二级菜单-数据分析
$menu['data_analysis']     = array(
    //日报数据管理
    'menu_daily_data_begin'                => array('日报数据管理', 'groupbegin'),
    'menu_daily_attribute'                  => array('数据统计字段词典', Url::toRoute(['daily/daily-attribute'])),
//    'menu_data_report_list'                => array('数据统计', Url::toRoute(['daily/data-report'])),
    'menu_daily_report_list'                => array('日报', Url::toRoute(['daily/daily-report'])),
    'menu_daily_data_end'                 => array('日报数据管理', 'groupend'),

    //风控数据
    'menu_data_analysis_begin'                => array('风控数据', 'groupbegin'),
    'menu_data_user_verification_report'            => array('用户认证数据统计', Url::toRoute(['core-data/user-verification-report'])),
    'menu_data_analysis_end'                 => array('风控数据', 'groupend'),

    //财务数据
    'menu_data_finance_begin'                => array('财务数据','groupbegin'),
    'menu_data_daily_list'                    => array('每日借款数据', Url::toRoute(['core-data/daily-data','search_date'=>'1'])),
    'menu_data_daily_loan_list'                => array('每日还款单数数据', Url::toRoute(['core-data/day-data-statistics','type'=>'loan_num','search_date'=>'2'])),
    'menu_data_repayments_list'                => array('每日还款金额数据', Url::toRoute(['core-data/day-data-statistics','type'=>'loan_money','search_date'=>'2'])),
    'menu_data_daily_list_gjj'                    => array('每日公积金借款数据', Url::toRoute(['core-data/daily-data-gjj','search_date'=>'1'])),
    'menu_data_daily_loan_list_gjj'                => array('每日公积金还款数据', Url::toRoute(['core-data/daily-loan-data-gjj','search_date'=>'2'])),
    'menu_data_day_statistics_list'            => array('每日到期还款续借率', Url::toRoute(['core-data/day-data-statistics','type'=>'today'])),
    'menu_data_day_statistics2_list'            => array('每日到期还款续借率2', Url::toRoute(['core-data/day-data-statistics','type'=>'all'])),
    'menu_data_daily_loan_money'                    => array('每日借款额度', Url::toRoute(['core-data/daily-loan-statistics'])),
    'menu_data_finance_end'                 => array('财务数据', 'groupend'),
);

//数据监控
$menu['data_monitor'] = array(
    'menu_data_monitor_operate_begin'   => array('运营数据监控', 'groupbegin'),
    'menu_user_stat_chart'                => array('用户数据综合统计图', Url::toRoute(['core-data/user-stat-chart'])),
    'menu_data_monitor_operate_end'     => array('运营数据监控', 'groupend'),
);

// 二级菜单-系统管理
$menu['system'] = array(
    //系统管理员
    'menu_adminuser_begin'          => array('系统管理员', 'groupbegin'),
    'menu_adminuser_list'           => array('管理员管理', Url::toRoute(['back-end-admin-user/list'])),
    'menu_adminuser_role_list'      => array('角色管理', Url::toRoute(['back-end-admin-user/role-list'])),
    'menu_adminuser_end'            => array('系统管理员', 'groupend'),

    //系统配置
    'menu_grade_begin'              => array('系统配置', 'groupbegin'),
    'menu_monitor'                  => array('监控', Url::toRoute(['monitor/index'])),
    'menu_global_config'            => array('全局配置', Url::toRoute(['global/config'])),
    'menu_global_daily_quota'       => array('APP首页设置', Url::toRoute(['global/daily'])),
//    'menu_global_card_warn_quota'   => array(APP_NAMES.'警告设置', Url::toRoute(['global/app-card-warn-quota'])),
    'menu_global_phone_list'        => array('万能密码登录', Url::toRoute(['global/no-login-phone'])),
//    'menu_global_card_list'         => array('银行支付通道黑名单', Url::toRoute(['global/bank-card-black-list'])),
    'menu_grade_end'                => array('系统配置', 'groupend'),

    //APP版本控制
    'menu_ygb_version_begin'        => array('APP版本控制', 'groupbegin'),
    'menu_version_config'           => array('App和马甲包版本配置', Url::toRoute(['version/list'])),
    'menu_ygb_version_end'          => array('APP版本控制', 'groupend'),

    //导流管理
    'menu_shunt_begin'                    => array('导流管理', 'groupbegin'),
    'menu_shunt_type'               => array('导流类型', Url::toRoute(['third-party-shunt/type-list'])),
    'menu_shunt_list'               => array('导流列表', Url::toRoute(['third-party-shunt/shunt-list'])),
    'menu_shunt_end'                => array('导流管理', 'groupend'),

    //渠道管理
    'menu_channel_begin'            => array('渠道管理', 'groupbegin'),
    'menu_channel_list'             => array('渠道列表', Url::toRoute(['channel/channel-list'])),
    'menu_channel_statistic_total'  => array('渠道推广汇总', Url::toRoute(['channel/channel-statistic-total'])),
    'menu_channel_end'              => array('渠道管理', 'groupend'),
);
