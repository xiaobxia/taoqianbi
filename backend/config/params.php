<?php
return [
    // 接口文档配置
    'apiList' => [
        [
            'class' => \frontend\controllers\ExternalApiController::class,
            'label' => '['.APP_NAMES.']外部对接接口',
        ],
        [
            'class' => \credit\controllers\CreditAppController::class,
            'label' => '手机信用卡APP全局接口',
        ],
        [
            'class' => \credit\controllers\CreditUserController::class,
            'label' => '手机信用卡APP用户',
        ],
        [
            'class' => \credit\controllers\CreditCardController::class,
            'label' => '手机信用卡APP个人信息',
        ],
        [
            'class' => \credit\controllers\CreditLoanController::class,
            'label' => '手机信用卡APP借款信息',
        ],
        [
            'class' => \credit\controllers\PictureController::class,
            'label' => '手机信用卡APP上传图片信息',
        ],
        [
            'class' => \credit\controllers\CreditInfoController::class,
            'label' => '手机信用卡APP其他信息',
        ],
        [
            'class' => \credit\controllers\CreditreportController::class,
            'label' => '手机信用卡APP征信管理',
        ],
        [
            'class' => \credit\controllers\NoticeController::class,
            'label' => '手机信用卡APP首页弹窗相关',
        ],
        [
            'class' => \credit\controllers\InfoCaptureController::class,
            'label' => '手机信用卡APP数据采集',
        ],
        [
            'class' => \credit\controllers\OfficialWebsiteController::class,
            'label' => '官网相关接口',
        ],
    ],

    'adminEmail' => NOTICE_MAIL,

    // 权限配置Controller,只能是后台backend命名空间下的
    'permissionControllers' => [
        'BackEndAdminUserController' => '后台账号管理',
        'DebitController' => '扣款',
        'LoanController' => '消费金融借款管理',
        'ZmopController' => '芝麻信用管理',
        'FinancialController' => '财务管理',
        'FinancialDebitController' => '补单管理',
        'LoanPersonBadInfoController' => '借款人不良信息管理',
        'UserInfoController'  =>'额度直接管理',
        'BankCardController' => '小钱包银行卡管理',
        'PocketController' => '零钱包管理',
        'StaffLoanController' => '零钱贷放款管理',
        'StaffRepayController' => '贷后管理',
        'TdController' => '同盾管理',
        'JxlController' => '聚信立(蜜罐)管理',
        'LogController' => '登录日志管理',
        'MobileContactsController' => '用户通讯录',
        'LoanBlackListController' => '黑名单管理',
        'SensitiveDictController' => '敏感词管理',
        'LoanBlacklistDetailController' => '黑名单规则管理',
        'YxzcController' => '宜信至诚阿福管理',
        'YdController' => '有盾征信管理',
        'BqsController' => '白骑士征信管理',
        'CoreDataController' => '数据分析',
        'RuleController' => '风控模型',
        'DailyController' => '小钱包日报数据管理',
        'CustomManagementController' => '客服管理',
        'TaskController' => '宽表任务',
        'RuleRiskController' => '规则任务管理',
        'ContentActivityController' => '运营活动管理',
        'DecisionTreeController' => '决策树配置管理',
        'CreditLineController' => '提额申请管理',
        'SolrLogController' => 'Solr日志管理',
        'PayrollCardController' => '网银认证管理',
        'FundAccountController' => '资方账号主体',
        'LoanFundController' => '资方管理',
        'RiskCheckAnalysisController' => '各渠道审核情况',
        'HouseFundController' => '用户管理-用户公积金',
        'AppBannerController' => 'App-banner列表',
        'LoanOrderQuotaController' => '每日限单管理',
        'GlobalController' => '后台全局配置',
        'DebitErrorController' => '扣款失败列表',
        'VersionController' => '版本控制列表',
        'YysController' => '运营商数据',
        'RepaymentConfigController' => '内容管理-运营管理-还款配置',
        'IcekreditController' => '用户管理-用户支付宝认证',
        'BrController' => '百融管理',
        'LzfController' => '孚临灵芝分管理',
        'CollectionController' => '借款列表-查看详情-催收详情',
        'ChannelController' => '渠道统计',
    ],

    //权限下的二级方法
    //附件管理
    'AttachmentController'=>[
        'actionList' => '附件列表',
        'actionAdd' => '添加附件',
        'actionDelete' => '删除附件',
    ],

    //分销渠道配置
    'DistributionChannel'=>[
        'yingke'=>[
            'username'=>['yingke','dukangyang1','chingfeng'],
            'source_id'=>37,
            'sub_order_type'=>6,
            'type'=>23
        ],
        'lyqb'=>[
            'username'=>['lyqb'],
            'source_id'=>39,
            'sub_order_type'=>7,
            'type'=>24
        ],
        'rdzdb'=>[
            'username'=>['rdzdb', 'dongxiaojuan'],
            'source_id'=>40,
            'sub_order_type'=>8,
        ],
    ],


    //后台有导出关键数据权限的帐号
    'ExportDataUser' => [
        'dolphy',
        'suky',
        'clark',
        'wangcheng',
        'liuna',
        'yuanlin',
        'zouyan',
        'zhoushan',
        'yeman',
    ],
    'menupermissionControllers' => [
        'user' => '用户管理',
        'loan' => '借款管理',
        'financial' => '财务管理',
        'credit' => '风控管理',
        'fund' => '资方管理',
        'service' => '客服管理',
        'content' => '内容管理',
        'data_analysis' => '数据分析',
        'data_monitor'=>'数据监控',
        'system'=>'系统管理',
    ],
    'menu'=>[
        ['indexs'=>'首页',
            'index'=>[
                'menu_home'=>'管理中心首页',
            ]]
        ,
        ['users' => '用户管理',
            'user'=>[
                //用户管理
                'menu_ygd_user_begin'             =>'用户管理',
                'menu_ygd_user'                   =>'用户列表',
                'menu_today_login_user'           =>'今日登录用户',
                'loan_person_mobile_contacts'     => '用户通讯录',
                'loan_person_house_fund'          => '用户公积金',
                'loan_person_alipay'              => '用户支付宝认证',
                'loan_black_list'                 => APP_NAMES.'黑名单',
                'loan_blacklist_list'             => '黑名单规则列表',
                'loan_out_del_list'               => '注销用户记录列表',
                'menu_bankcard_list'              => '银行卡列表',
                'menu_card_realname'              => '用户实名列表',
                'menu_user_verification_list'     => '用户认证列表',
                'loan_person_message_log'         => '上行短信日志',
                'loan_person_message_status'      => '短信发送状态',
                'menu_sensitive_dict'             => '敏感词列表',
                'menu_sensitive_dict_censor'      => '敏感词用户列表',
                'menu_ygd_address'                =>'收获地址',
                'menu_ygd_user_end'               => '用户管理',

                //额度管理
                'menu_credit_limit_begin'    => '额度管理',
                'menu_credit_limit_list'    => '信用额度列表',
                'menu_credit_limit_log'        => '信用额度使用流水',
                'menu_credit_review_log'    => '信用额度审核',
                'menu_credit_modify_log'    => '信用额度调整流水',
                'menu_credit_line'    => '授信额度详情',
                'menu_credit_limit_end'         => '额度管理',

                //利息管理
                'menu_check_begin'                => '利息管理',
                'menu_interest_log'               => '利息流水列表',
                'menu_lqd_interest_check_list'    => '零钱包利息错误核对',
                'menu_check_end'                  => '利息管理',
            ]],
        ['loans' => '借款管理',
            'loan'=>[
                //用户借款管理
                'menu_loan_begin'                => '用户借款管理',
                'menu_loan_lqb_list'            => '借款列表',
                'menu_loan_lqb_reject_list'            => '借款拒绝列表',
                'menu_ygb_zc_lqd_fk_lb'            => '放款列表',
                'menu_loan_end'                    => '用户借款管理',

                //风控管理
                'menu_pay_begin'                => '风控管理',
                'menu_pay_loan_lqb_list'        => '借款列表',
                'menu_ygb_zc_lqd_auto'            => '待机审订单列表',
                'menu_ygb_zc_lqd_reject'        => '机审拒绝订单列表',
                'menu_get_order_zc_lqd_cs'            => '订单领取',
                'menu_other_zc_lqd_cs'            => '人工初审',
                'menu_ygb_zc_lqd_fs'            => '人工复审',
                'menu_audit_number_list'            => '审核订单数量',
                'menu_push_redis'            => '重入风控队列',
                'menu_pay_end'                    => '风控管理',

                //贷后管理
                'menu_repay_begin'                => '贷后管理',
                'menu_ygb_zc_lqd_hk'            => '零钱包还款列表',
                'menu_ygb_zc_lqd_hkk'            => '还款卡列表',
                'menu_ygb_zc_lqd_repay_edit_log'   => '修改实际还款金额日志列表',
                'menu_repay_end'                => '贷后管理',
            ]],
        ['financials' => '财务管理',
            'financial'=>[
                //打款管理
                'menu_loan_manage_begin'        => '打款管理',
                'menu_loan_list'                => '打款列表',
                'menu_loan_dksh_list'           => '打款审核',
                'menu_loan_manage_end'            => '打款管理',

                //扣款管理
                'menu_loan_mange_begin'            =>'扣款管理',
                'menu_debit_list'                => '扣款列表',
                'menu_debit_wait_list'          => '待扣款列表',
                'menu_debit_falied_list'        => '扣款失败列表',
                'menu_debit_alipay_list'        => '支付宝还款列表',
                'menu_debit_alipay_record'      => '支付宝交易流水',
                'menu_debit_weixin_list'        => '微信还款列表',
                'menu_debit_weixin_record'        => '微信交易流水',
                'menu_debit_bankpay_list'       => '还款日志列表',
//                'menu_refund_list'       => '退款列表',
                'menu_debit_debitlog_list'        => '自动扣款日志列表',
                'menu_debit_helibao'        => '合利宝参数统计',
                'menu_debit_debitlog_list'        => '扣款观察列表',
//                'menu_debit_yeepay_list'        => '打款扣款结果查询',
                'menu_debit_lose_order_list'        => '补单数据列表',
                'menu_debit_rid_overdue_log_list'        => '减免滞纳金列表',
                'menu_loan_mange_end'            =>'扣款管理',
                //统计管理列表
                'menu_financial_statistics_begin'=> '统计管理列表',
                'menu_financial_overdue_list'        => '逾期数据分布',
                'menu_financial_expense_list'        => '运营成本统计列表',
                'menu_financial_day_notyet_principal_list'=> '每日未还本金列表',
                'menu_financial_day_notyet_principal_account'=> '每日未还本金对账',
                'menu_financial_statistics_end'    => '统计管理列表',
            ]],
        ['credits' => '风控管理',
            'credit'=>[
                'menu_credit_manage_begin'        => '用户征信管理',
                'menu_credit_user_list'           => '用户征信管理',
                'menu_ygb_jxl_status_view'        => '用户运营商认证状态',
                'menu_credit_manage_end'          => '用户征信管理',


                'menu_error_manage_begin'        => '征信错误信息',
                'menu_error_message_list'        => '错误信息',
                'menu_error_manage_end'          => '征信错误信息',

                'menu_decision_tree_begin'              => '决策树管理',
                'menu_decision_tree_characteristics'    => '特征配置',
                'menu_decision_tree_escape_template'    => '转义模版',
                'menu_decision_tree_order_report'    => '订单决策详情',
                'menu_decision_tree_rule_report'    => '授信决策详情',
                'menu_decision_tree_end'                => '决策树管理',

                'menu_channel_audit_situation_begin'             => '各渠道审核情况',
                'menu_risk-check-analysis_list'=> '审核统计',
                'menu_risk_check_refuse_list'  => '拒绝原因',
                'menu_risk_check_registerloan_list'  => '放款注册统计',
                'menu_loan_register_info' => '放款通过注册统计',
                'menu_channel_audit_situation_end'            => '各渠道审核情况',

            ]],
        ['funds' => '资方管理',
            'fund'=>[
                //资方管理
                'menu_fund_begin' => '资方管理',
                'menu_loan_order_quota' => '放款订单配额',
                'menu_fund_list' => '资方列表',
                'menu_fund_account_list' => '资方账号主体',
                'menu_order_fund_info' => '订单关联信息',
                'menu_order_fund_log' => '日志',
                'menu_fund_end' => '资方管理',
            ]],
        ['services' => '客服管理',
            'service'=>[
                //用户管理
                'menu_user_begin'                => '用户管理',
                'menu_user_list'                => '用户列表',
                'menu_user_credit_list'            => '用户额度列表',
                'menu_user_credit_log'            => '用户额度流水',
                'menu_card_list'                => '银行卡列表',
                'menu_card_realname'            => '用户实名列表',
                'menu_accumulation_fund_list' => '用户公积金列表',
                'menu_user_end'                     => '用户管理',

                //借款管理
                'menu_user_loan_begin'            => '借款管理',
                'menu_user_loan_list'            => '借款订单列表',
                'menu_user_pay_list'            => '打款列表',
                'menu_fund_user_pay_list'            => '资方打款列表',
                'menu_user_loan_end'             => '借款管理',

                //还款管理
                'menu_user_repay_begin'         => '还款管理',
                'menu_user_repay_list'            => '还款订单列表',
                'menu_user_repay_log'            => '用户还款记录',
                'menu_ygb_debit_error'          => '还款失败',
                'menu_user_repay_end'           => '还款管理',

                //征信管理
                'menu_credit_begin'             => '征信管理',
                'menu_user_jxl_log'                => '运营商认证状态',
                'menu_credit_end'               => '征信管理',

                'user_feedback_begin' => '反馈管理',
                'user_feedback_list' => '用户反馈',
                'user_feedback_end' => '反馈管理'

            ]],
        ['contents' => '内容管理',
            'content'=>[
                //运营管理
                'menu_operate_manage_begin'             => '运营管理',
                'menu_operate_repayment_info'            => '还款配置',
                'menu_operate_manage_end'               => '运营管理',

                //通知管理
                'menu_notice_manage_begin'          => '通知管理',
                'menu_operate_activity_list'        => '公告中心',
                'menu_notice_manage_end'            => '通知管理',

                //app-banner管理
                'official_websit_banner_begin'      => 'app-banner管理',
                'official_app_banner_list'          => 'app-banner列表',
                'official_websit_banner_end'        => 'banner',


                //附件管理
                'menu_attachment_begin'        => '附件管理',
                'menu_attachment_list'        => '附件列表',
                'menu_attachment_end'        => '附件管理',
            ]],
        ['data_analysiss' => '数据分析',
            'data_analysis'=>[
                //日报数据管理
                'menu_daily_data_begin'                => '日报数据管理',
                'menu_daily_attribute'                  => '数据统计字段词典',
                'menu_daily_report_list'                => '日报',
                'menu_daily_data_end'                 => '日报数据管理',

                //风控数据
                'menu_data_analysis_begin'                => '风控数据',
                'menu_data_user_verification_report'            => '用户认证数据统计',
                'menu_data_analysis_end'                 => '风控数据',

                //财务数据
                'menu_data_finance_begin'                => '财务数据',
                'menu_data_daily_list'                    => '每日借款数据',
                'menu_data_daily_loan_list'                => '每日还款单数数据',
                'menu_data_repayments_list'                => '每日还款金额数据',
                'menu_data_daily_list_gjj'                    => '每日公积金借款数据',
                'menu_data_daily_loan_list_gjj'                => '每日公积金还款数据',
                'menu_data_day_statistics_list'            => '每日到期还款续借率',
                'menu_data_day_statistics2_list'            => '每日到期还款续借率2',
                'menu_data_daily_loan_money'                    => '每日借款额度',
                'menu_data_finance_end'                 => '财务数据',
            ]],
        ['data_monitors'=>'数据监控',
            'data_monitor'=>[
                'menu_data_monitor_operate_begin'   => '运营数据监控',
                'menu_user_stat_chart'                => '用户数据综合统计图',
                'menu_data_monitor_operate_end'     => '运营数据监控',

            ]],
        ['systems'=>'系统管理',
            'system'=>[
                //系统管理员
                'menu_adminuser_begin'            => '系统管理员',
                'menu_adminuser_list'            => '管理员管理',
                'menu_adminuser_role_list'        => '角色管理',
                'menu_adminuser_end'             => '系统管理员',

                //系统配置
                'menu_grade_begin'                => '系统配置',
                'menu_monitor'                      => '监控',
                'menu_global_daily_quota'         => 'APP首页设置',
                'menu_global_card_warn_quota'         => APP_NAMES.'警告设置',
                'menu_global_phone_list'         => '万能密码登录',
                'menu_grade_end'                  => '系统配置',

                //APP版本控制
                'menu_ygb_version_begin'                => 'APP版本控制',
                'menu_version_config'              => APP_NAMES.'App和马甲包版本配置',
                'menu_ygb_version_end'                  => 'APP版本控制',

                //导流管理
                'menu_shunt_begin'              => '导流管理',
                'menu_shunt_type'               => '导流类型',
                'menu_shunt_list'               => '导流列表',
                'menu_shunt_end'                => '导流管理',

                //渠道管理
                'menu_channel_begin'            => '渠道管理',
                'menu_channel_list'             => '渠道列表',
                'menu_channel_statistic_total'  => '渠道推广汇总',
                'menu_channel_end'              => '渠道管理',
            ]],
    ],
];
