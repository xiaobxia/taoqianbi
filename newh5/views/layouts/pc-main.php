<?php
use common\helpers\Util;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $this->title; ?></title>
    <meta name="keywords" content="<?php echo $this->keywords? $this->keywords : ''?>">
    <meta name="description" content="<?php echo $this->description? $this->description : ''?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $this->absBaseUrl;?><!--/css/index-xyjk.css">-->
<!--    <link rel="stylesheet" type="text/css" href="--><?php //echo $this->absBaseUrl;?><!--/css/index-xyjk.css.map">-->
    <link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl;?>/css/css3-2.css">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link rel="shortcut icon" href="<?= $this->icon ? $this->icon : $this->absBaseUrl.'/image/common/ico.ico';?>">
</head>

<body>
    <div class="container">
        <?php echo \newh5\components\Header::widget();?>
        <?php echo $content;?>
        <?php echo \newh5\components\Footer::widget();?>
    </div>
</body>
</html>