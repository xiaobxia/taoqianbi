<?php
Yii::setAlias('common', dirname(dirname(__DIR__)) . '/common');
Yii::setAlias('newh5', dirname(__DIR__));
Yii::setAlias('frontend', dirname(dirname(__DIR__)) . '/frontend');

Yii::$container->set('userService', 'common\services\UserService');
