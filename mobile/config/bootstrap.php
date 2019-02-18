<?php
Yii::setAlias('common', dirname(dirname(__DIR__)) . '/common');
Yii::setAlias('mobile', dirname(__DIR__));
Yii::setAlias('frontend', dirname(dirname(__DIR__)) . '/frontend');

Yii::setAlias('@creditUrl', '//qbcredit.wzdai.com');
Yii::setAlias('@mobileUrl', '//m.kdqugou.com');

Yii::$container->set('userService', 'common\services\UserService');
Yii::$container->set('loanService', 'common\services\LoanService');
Yii::$container->set('aliOssService', 'common\services\AliOssService');
Yii::$container->set('cardService', 'common\services\CardService');
Yii::$container->set('fundService', 'common\services\FundService');