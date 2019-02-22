<?php

$_site_url = SITE_DOMAIN; //TODO change_me
return [
    'version_id'            => 101,
    'app_msg_name'          => 'smsServiceXQB_XiAo',
    'sub_order_type'        => 1,
    'from_app'              => 1,
    'tb_user_password'      => \common\models\UserPassword::class,
    'tb_user_pay_password'  => \common\models\UserPayPassword::class,
    'h5_theme_style'        => '',
    'ios_url'               => APP_IOS_DOWNLOAD_URL, //TODO clark appstore_url
    'company_name'          => APP_NAMES,
    'app_name'              => APP_NAMES,
    'card_title_1'          => APP_NAMES,
    'card_title_2'          => APP_NAMES,

    //初始额度
    'card_amount'           => 100000,

    //渠道对应额度表
    'credit_db_channel_name'=> \common\models\UserCreditTotal::class,

    //帮助中心
    'help_url'              => "http://{$_site_url}/credit/web/credit-web/help-center",

    //还款方式
    'repayment_help_url'    => "http://{$_site_url}/credit/web/credit-web/repayment-process",

    //关于我们
    'about_url'             => "http://{$_site_url}/newh5/web/app-page/about-company",

    'invite_url'            => '', //TODO clark func_not_implement

    //注册协议
    'register_protocol_url' => "http://{$_site_url}/credit/web/credit-web/safe-login-txt",

    //借款协议 TODO URLREWRITE
    'protocol_url'          => "", //TODO clark "http://{$_site_url}/frontend/web/page/detail?id=627"

    //logo
    'share_logo'            => 'logo_share.png',
    'share_body'            => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',

    //基本信息
    'callCenter'            => SITE_TEL,
    'callQQService'         => QQ_SERVICE,
    'companyAddress'        => COMPANY_ADDRESS,
    'companyEmail'          => NOTICE_MAIL,

    //认证列表
    'verification'          => [
        'title_mark_must_color'     => '#ff8003', //必填 色值
        'title_mark_option_color'   => '#999999', //选填 色值
        'operator_color'            => '#1ec8e1', //已填写  色值
    ],

    //公积金提示信息
    'tip_message'           =>'认证公积金最高提额度至5000元',

    //服务时间
    'serverTime'            =>"<font color='#999999'>在线时间:9:00 ~ 21:00</font>",
    'weekServerTime'        =>"<font color='#999999'>周末及法定节假日时间9:00 ~ 18:00</font>",
];
