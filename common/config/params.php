<?php

return [

    // 默认短信通道
    'smsService' => [
//        'url' => 'http://210.5.158.31:9011/hy/',
//        'uid' => '12345',
//        'account' => '123123',
//        'code' => 'ygd',
//        'password' => 'test',
    ],


    //玄武语音通知
//    'smsService_XuanWu_YYTZ'=>[
//        'url' => 'https://api.d9cloud.com/api/v1.0.0/voice/notify',
//        'account' => 'd434f775b168b6255d15c559cb344261',    //sig
//        'password' => 'c38d20a1f628b3aa8998e42c36c2ec07',   //token
//        'appid' => 'de1a4aab88427f285e8022e31d9168d7',      // appid
//        'playtimes'=>'1',                                   // 重复次数
//    ],


//     //极速荷包 天畅营销(催收通知-- 提前通知   )
//     'smsService_TianChang_CSTZ' => [
//         'url' => 'http://101.227.68.68:7891/mt',
//         'collurl' => 'http://101.227.68.68:7891/mo',
//         'balance_url' => 'http://101.227.68.68:7891/bi',
//         'account' => '220940',
//         'password' => 'jshb1823',
//         'expid' => '940',
//     ],

    //极速荷包 天畅营销( 提前 --强硬通知   )
//    'smsService_TianChang_QYTZ' => [
//        'url' => 'http://122.144.179.5:7891/mt',
//        'collurl' => 'http://122.144.179.5:7891/mo',
//        'balance_url' => 'http://122.144.179.5:7891/bi',
//        'account' => '840040',
//        'password' => 'QYU59LOP',
//        'expid' => '040',
//    ],


//     速盾  逾期 催收
//    'smsService_SuDun_CS' => [
//        'url' => 'http://118.190.144.193:7862/sms?action=send',
//        'collurl' => 'http://118.190.144.193:7862/sms?action=mo',
//        'balance_url' => 'http://118.190.144.193:7862/sms?action=overage',
//        'account' => '700042',
//        'password' => 'mWxM3x',
//        'expid' => '',
//    ],

//     大汉三通 （行业  提前还款通知）
//    'smsService_DaHan_TZ' => [
//        'url' => 'http://www.dh3t.com/json/sms/Submit',
//        'collurl' => '',
//        'balance_url' => '',
//        'account' => 'dh84111',
//        'password' => 'VK3j2tWf',
//        'expid' => '',
//    ],

//     大汉三通 （验证码 ）
//    'smsService_DaHan_YZM' => [
//        'url' => 'http://www.dh3t.com/json/sms/Submit',
//        'collurl' => '',
//        'balance_url' => '',
//        'account' => 'dh84113',
//        'password' => '25LeicD1',
//        'expid' => '',
//    ],

//     赛有 语音 逾期通知
//    'smsService_SaiYou_YY' => [
//        'url' => 'http://api.mysubmail.com/',
//        'collurl' => '',
//        'balance_url' => '',
//        'account' => '20641',
//        'password' => '',
//        'expid' => '',
//        'appkey' => '29c3b480cdc61ee73bec6244d04ab5c3',
//    ],



    //极速荷包 天畅行业
    'smsService_TianChang_HY' => [
        'url' => 'http://101.227.68.68:7891/mt',
        'collurl' => '',
        'balance_url' => '',
        'account' => '811306',
        'password' => 'DEU25VNM',
        'expid' => '',

    ],

    //亿美短信
    'smsService_YiMei' => [
        'url' => 'http://shmtn.b2m.cn',
        'collurl' => '',
        'balance_url' => '',
        'account' => 'EUCP-EMY-SMS1-2DTTL',
        'password' => 'DE47B10FBBF9FB5B',
        'expid' => '',

    ],

//     极速荷包 天畅营销
//    'smsService_TianChang_XY' => [
//        'url' => 'http://101.227.68.68:7891/mt',
//        'collurl' => 'http://101.227.68.68:7891/mo',
//        'balance_url' => 'http://101.227.68.68:7891/bi',
//        'account' => '120939',
//        'password' => 'jshb1823',
//        'expid' => '939',
//    ],

    // 希奥  验证码
    'smsServiceXQB_XiAo' => [
        'url' => 'http://sms.10690221.com:9011/hy/',
        'collurl' => 'http://sms.10690221.com:9011/hy/mo',
        'rpturl' => 'http://sms.10690221.com:9011/hy/rpt',
        'code' => 'lqhb',
        'uid' => '90430',
        'account' => '90430',
        'password' => '99bw4m',
    ],

    'amount_days_list' => [
        'days' => ["7", "14", "21", "30"],
        'interests' => ["16500", "22500", "24000", "49500"],
        'amounts' => ["100000", "100000", "100000", "100000"],//金额，没有、10
    ],
    'counter_fee_rate' => 30,//借款总的手续比例%
    'management_fee_rate'=>0.7,//管理费
    'amount_days_list_multi' => [
        'days' => ["7", "14", "21", "30"],
        'interests' => ["3500", "7000", "8500", "35000"],
        'amounts' => ["100000", "100000", "100000", "100000"],
    ],
    'ygd_act_national_day' => [//小钱包国庆节活动，期限14天，服务费120元
        'start_time' => '2016-09-24',
        'end_time' => '2016-09-30 23:59:59',
        'day' => 7,
        'counter_fee' => 12000,
        'send_message' => '国庆到！小钱包“贷”您共享7天假期，凡是9月24日至9月30期间的借款申请，借款期限均升级至14天。服务费用更是低至6折，开启买买买度假模式啦。 快点击下载小钱包。http://t.cn/RcYg96F。',
    ],

    #短信提示 使用前请注意替换参数
    'sms_content' => [
        #渠道用户注册
        'channel_user_register' => '您的'.APP_NAMES.'密码为  :param_password ， 马上下载'.APP_NAMES.'APP，登录即可查看您的授信额度。',
        #渠道订单审核被拒绝
        'channel_order_review_rejected' => '很抱歉，您在'.APP_NAMES.'的借款申请未通过；您可在App里补充更多信息，提升借款通过率。',
    ],

    # 红包发放金额的概率配置
    'packet_amount_config' => [
        'a' => 70,
        'b' => 17,
        'c' => 5,
        'd' => 5,
        'e' => 0,
        'f' => 0,
    ],

    # 控制金卡是否隐藏
    'app_golden_card' => false, //true, false

    'notify_phones' => [
        NOTICE_MOBILE,
    ],
    'br' => [
        'account' => 'sdhbStr',
        'password' => 'sdhbStr',
        'apicode' => '3002132'
    ],
    'zmop' => [
        'appid' => '300001467',
        'privateKeyFile' => '@common/config/cert/zmop/jshb_prod_private_key.pem',
        'zmPublicKeyFile' => '@common/config/cert/zmop/jshb_prod_public_key_zm.pem'
    ],
];