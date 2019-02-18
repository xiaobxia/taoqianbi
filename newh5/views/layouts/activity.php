<?php
use common\helpers\Util;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <?php
    if(Util::getMarket() ==\common\models\LoanPerson::APPMARKET_XJBT){
        $this->title = '现金白条';
    }
    else if(Util::getMarket() == \common\models\LoanPerson::APPMARKET_XH)
    {
        $this->title = APP_NAMES;
    }
    ?>
    <title><?= $this->title ? $this->title : \Yii::$app->name; ?></title>
    <meta name="keywords" content="<?= $this->keywords ? $this->keywords : \Yii::$app->name;?>">
    <meta name="description" content="<?= $this->description ? $this->description : \common\helpers\Util::loadConfig('@common/message/m_xqb')['share_body'];?>">
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link rel="shortcut icon" href="<?= $this->icon ? $this->icon : $this->absBaseUrl.'/image/common/ico.ico';?>">
    <!-- 公用CSS -->
    <link rel="stylesheet" type="text/css" href="<?= $this->absBaseUrl;?>/css/general.css">
    <link rel="stylesheet" type="text/css" href="<?= $this->absBaseUrl;?>/css/common.css">
    <!-- 公用JS -->
    <script type="text/javascript" src="<?= $this->absBaseUrl;?>/js/common<?php if(\Yii::$app->controller->isFromWZD()){echo '_wzd';}elseif(\Yii::$app->controller->isFromHBJB()){echo '_hbqb';}?>.js"></script>
    <script src="<?= $this->absBaseUrl;?>/js/m-layouts.js?v=20181116"></script>
    <script src="<?= $this->absBaseUrl;?>/js/jquery.min.js"></script>
    <script src="<?= $this->absBaseUrl;?>/js/flexable.js"></script>
    <?php if($this->showDownload && $this->source_app): ?>
        <script src="<?= $this->absBaseUrl.'/js/download_'.$this->source_app.'.js';?>"></script>
    <?php endif; ?>
    <!-- 补充CSS -->
    <link rel="stylesheet" href="<?= $this->absBaseUrl;?>/css/extra-css/turntable.css?_v=2017081801">
</head>
<body>
    <!-- 分享链接显示的logo -->
    <div class='bg _hidden'>
        <img src="<?= $this->shareLogo ? $this->shareLogo : $this->absBaseUrl.'/image/common/logo_120.png';?>"/>
    </div>
    <div class="container">
        <?= $content; ?>
    </div>
</body>
<script type="text/javascript">
    webVisitStat(); // 上报浏览数据
</script>
</html>