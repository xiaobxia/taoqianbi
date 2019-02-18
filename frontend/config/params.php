<?php
return [

    'code_text'=>[
        'LOGIN_DISABLED'=>-2,
        'SUCCESS'=>0,
        'MOBILE_REGISTERED'=>1000,
        'ZM_AUTHORISED'=>11,
        'UNTIED_BANK_CARD'=>1,
        'UNSET_TRADE_PASSWORD'=>2,
        'NEED_CAPTCHA'=>12,
    ],

    'code'=>[
        -2 => '登录态失效',
        0 => '成功',
        1 => '未绑定银行卡',
        2 => '未设置交易密码',
        11 => '芝麻信用已授权',
        1000=>'手机号已注册',
        12=>'需要手机验证码'
    ],

    'channel' => [
        'shortUrl' => [
            'url' => 'http://'.SITE_DOMAIN.'/newh5/web/page/jshbreg',
        ]
    ]
];
