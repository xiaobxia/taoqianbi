<?php
return [
    #每小时发送渠道统计前缀分类的数据到对应的邮箱联系人
    'channel_classify_send_mail'=>[
        //小米渠道
        'xiaomi'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],
        'WAP-xiaomi'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],
        //陌陌(品众)
        'WAP-momo'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //陌陌-麦广
        'WAP-maimo'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //陌陌-乐推
        'WAP-lemo'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //广点通
        '-guangdt'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //网易有道
        'H5-WY'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //DNS劫持
        'H5-DNS'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //应用宝
        'card-yyb-qudao'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //陌陌-乐推2
        'WAP-tuimo'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //百度手机助手
        'card-baidu'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //360手机助手
        'card-360'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //小米
        'card-xiaomi'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //百度信息流康赛
        'baiduxin'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

         //今日头条-乐推
        'letuitt'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

            //亿玛DSP
        'WAP-yima'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        'card-yima'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

         //今日头条-（乐推）
        'card-letuitt'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

        //广点通（需要合并的WAP-guangdt）
        'card-guangdt'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],
//            兑吧-（慧投）
        'huitoudb'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],
        //uc浏览器
        'WAP-uc'=>[
            'yuchen'=>NOTICE_MAIL,   //余晨
        ],

    ],

    //邮件主题 ,邮件的标题要和上面的键名对应，因为是通过键名来查找对应的邮件联系人
    'channel_classify_send_mail_header'=>[
        'xiaomi'              =>      APP_NAMES.'-分渠道数据-小米',
        'WAP-xiaomi'          =>      APP_NAMES.'-分渠道数据-小米',
        'WAP-momo'            =>      APP_NAMES.'-分渠道数据-陌陌(品众)',
        'WAP-maimo'           =>      APP_NAMES.'-分渠道数据-陌陌(麦广)',
        'WAP-lemo'            =>      APP_NAMES.'-分渠道数据-陌陌(乐推)',
        '-guangdt'         =>      APP_NAMES.'-分渠道数据-广点通',
        'H5-WY'                =>      APP_NAMES.'-分渠道数据-网易有道',
        'H5-DNS'               =>      APP_NAMES.'-分渠道数据-DNS劫持',
        'card-yyb-qudao'     =>      APP_NAMES.'-分渠道数据-应用宝',
        'WAP-tuimo'           =>       APP_NAMES.'-分渠道数据-陌陌(乐推)2',
        'card-baidu'           =>       APP_NAMES.'-分渠道数据-百度手机助手',
        'card-360'            =>       APP_NAMES.'-分渠道数据-360手机助手',
        'card-xiaomi'         =>       APP_NAMES.'-分渠道数据-小米',
         'baiduxin'          =>         APP_NAMES.'-分渠道数据-百度信息流康赛',
        'letuitt'             =>         APP_NAMES.'-分渠道数据-今日头条(乐推)',
        'WAP-yima'             =>         APP_NAMES.'-分渠道数据-亿玛DSP',
        'card-yima'             =>         APP_NAMES.'-分渠道数据-亿玛DSP',
        'card-letuitt'         =>         APP_NAMES.'-分渠道数据-(乐推)今日头条',
        'huitoudb'               =>          APP_NAMES.'-分渠道数据-（慧投）兑吧',
        'WAP-uc'                =>           APP_NAMES.'-分渠道数据-uc浏览器',
    ],

    //认证转化率分析每小时转发邮件地址
    'verification_mail_address' => [
        'yuchen@koudaild.com',   //余晨
    ],

];
