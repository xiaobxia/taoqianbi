<?php

return [
//    'user' => [
//        'reg-get-code' => [1, 60], //60秒内允许请求1次
//        'test' => [1, 1], //1秒内允许请求1次
//    ],

    'external-api' => [
        'get-my-orders' => [1, 1], //[1, 30]: 30秒内允许请求1次
    ],
];
