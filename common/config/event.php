<?php
return [
    //注册成功之后操作
    'onSuccessRegister' => [
        //'ykxjx' => ['common\services\YkXjxSupportService', 'syncRegister'],
    ],
    
    //绑卡成功之后操作
    'onSuccessBindCard' => [
        //'ykxjx' => ['common\services\YkXjxSupportService', 'syncBindCard'],
    ],
    
    //借款成功之后操作
    'onSuccessApply' => [
        //'ykxjx' => ['common\services\YkXjxSupportService', 'syncApply'],
    ],
    
    //放款成功之后操作
    'onSuccessPocket' => [
        //'ykxjx' => ['common\services\YkXjxSupportService', 'syncPocket'],
        'rdzdb' => ['common\services\RdZdbSupportService', 'syncPocket'],
    ],
    
    //还款成功之后操作
    'onSuccessRepay' => [
        //'ykxjx' => ['common\services\YkXjxSupportService', 'syncRepay'],
        'rdzdb' => ['common\services\RdZdbSupportService', 'syncRepay'],
    ],
];