<?php

return [
    'url' => [
        'kdlc_shenma' => [
            1 => 'http://windmap.xicp.io:17464/api/kd/notify-loan',
        ],
        'kdlc_51dqd' => [
            0 => 'http://nice.51duanqidai.com:8080/manager/koudai/callback',
            1 => 'http://nice.51duanqidai.com:8080/manager/koudai/callback',
            2 => 'http://nice.51duanqidai.com:8080/manager/koudai/callback',
        ],
    	'kdlc_df' => [
    		//1 => 'http://118.186.255.63:20086/fundproviders.callback',
    		//1 => 'http://118.186.255.63:20093/fundproviders.callback',
    		1 => 'http://111.202.110.46:20093/fundproviders.callback',
    		//1 => 'http://kdlcgateway.dafy.com/fundproviders.callback',
    	],
    	'kdlc_jsy' => [
    		1 => 'http://pay.jisujie.com/kdlc/updateorderstatus',
    		2 => 'http://paytest.jisujie.com/kdlc/updateorderstatus',
    	],
    	'kdlc_mbd' => [
    		1 => 'http://115.28.234.12:8080/pay/api/1.0.0/notify/koudailcStatusNotify',
    		2 => 'http://115.28.234.12:8080/pay/api/1.0.0/notify/koudailcStatusNotify',
    	],
    	'kdlc_mo9' => [
    			1 => 'https://new.mo9.com/gateway/proxypay/koudaipay/notify.mhtml',
    			2 => 'https://new.mo9.com/gateway/proxypay/koudaipay/notify.mhtml',
    	],
    ],
    'tryCount' => [
        'DEFAULT' => 3,
        'kdlc_shenma' => [
            'DEFAULT' => 1,
            1 => 2,
        ],
    ],
];
