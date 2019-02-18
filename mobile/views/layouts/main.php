<?php
use mobile\components\ApiUrl;
use common\helpers\GlobalHelper;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; ?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES;?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/general.css?v=2016012601">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/common.css?v=2016012601">
    <?php if(!empty($this->params['load_weui'])): ?>
        <link rel="stylesheet" type="text/css" href="//m<?=APP_DOMAIN?>/css/weui.min.css?v=1.0.2">
    <?php endif;?>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js?v=2016012601"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/common.js?v=2016031601"></script>
    <script type="text/javascript">document.domain='<?php echo GlobalHelper::getDomain(); ?>';</script>
    <link rel="shortcut icon" href="<?php echo $this->absBaseUrl; ?>/ico.ico">
    <?php echo $this->baiDuStatistics();?>
</head>

<body>
    <script type="text/javascript">
        var TRACKS_TARGET_URL = "<?php echo ApiUrl::toRoute(['activity/tracks'], true); ?>";
        window.UserAgent = "<?php echo strstr($_SERVER['HTTP_USER_AGENT'],'KDLC'); ?>";
        if(getQueryString('source_tag')){
            window.localStorage.setItem("source_tag",getQueryString('source_tag'));
        }
    </script>
    <div class="kdlc_mobile_wraper container">
        <div class='bg _hidden'>
            <img src="<?php echo $this->absBaseUrl; ?>/logo_320.png?v=2015122301"/>
        </div>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <input class="_hidden" type="text" />
        <input class="_hidden" type="password"/>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <?php echo $content; ?>
    </div>
</body>
</html>
