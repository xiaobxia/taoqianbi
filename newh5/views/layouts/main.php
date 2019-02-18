<?php
use common\helpers\Util;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <?php $this->title = APP_NAMES; ?>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= $this->title ? $this->title : \Yii::$app->name;?></title>
    <meta name="keywords" content="<?= $this->keywords ? $this->keywords : \Yii::$app->name;?>">
    <meta name="description" content="<?= $this->description ? $this->description : \common\helpers\Util::loadConfig('@common/message/m_xqb')['share_body'];?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="appVersion" content="<?php $data = $this->actionSetHeaderUrl();if(isset($data['appVersion'])){echo $data['appVersion'];}?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link rel="stylesheet" type="text/css" href="<?= $this->absBaseUrl;?>/css/general.css?v=2017030801">
    <link rel="stylesheet" type="text/css" href="<?= $this->absBaseUrl;?>/css/common.css?v=2017030801">
    <link rel="stylesheet" type="text/css" href="<?= $this->absBaseUrl;?>/css/download.css">
    <script type="text/javascript" src="<?= $this->absBaseUrl;?>/js/jquery.min.js?v=2017030801"></script>
    <script type="text/javascript" src="<?= $this->absBaseUrl;?>/js/common<?php if(\Yii::$app->controller->isFromWZD()){echo '_wzd';}elseif(\Yii::$app->controller->isFromHBJB()){echo '_hbqb';}?>.js?v=2017032502"></script>
    <script type="text/javascript" src="<?= $this->absBaseUrl;?>/js/m-layouts.js?v=20181116"></script>
    <script type="text/javascript" src="<?= $this->absBaseUrl; ?>/js/sonic.js?v=2017030801"></script>
    <script type="text/javascript" src="<?= $this->absBaseUrl; ?>/js/flexable.js"></script>
    <script type="text/javascript" src="<?= $this->absBaseUrl; ?>/js/Umeng.js"></script>
    <style type="text/css">
    /*
    html,body{min-width:320px;max-width:480px;min-height:100%;margin:0 auto;}
    body *{max-width:480px; }
    ._width_limit *{max-width:none;}
    html{position:relative;width:100%;height:100%;}
    ::-webkit-scrollbar{opacity:0;}
    .container{margin:0 auto;width:100%;height:100%;background: #338eff;}
    .container .padding{padding:0 6.25%;}
    */
    </style>
</head>

<body>
    <div class="container">
        <!-- 分享链接显示的logo -->
        <div class='bg _hidden'>
            <img src="<?= $this->shareLogo ? $this->shareLogo : $this->absBaseUrl.'/image/common/logo_120.png?v=2017030801';?>"/>
        </div>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <input class="_hidden" type="text" />
        <input class="_hidden" type="password"/>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <?= $content;?>
    </div>
</body>
</html>
