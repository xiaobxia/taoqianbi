<?php
use common\helpers\Util;
if(Util::getMarket()==\common\models\LoanPerson::APPMARKET_XJBT){
    $this->title = \common\models\LoanPerson::$source_app[\common\models\LoanPerson::APPMARKET_XJBT].'-快速借钱，贷款神器';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php if(!empty($this->params['menu_add'])){echo $this->params['menu_add'];}else{ echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; }?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES; ?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="format-detection" content="telephone=no" />
    <meta name="appVersion" content="<?php $data = $this->actionSetHeaderUrl();if(isset($data['appVersion'])){echo $data['appVersion'];}?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl;?>/css/general.css?v=2016070501">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl;?>/css/common.css?v=2016070501">
    <script type="text/javascript" src="<?php echo $this->absBaseUrl;?>/js/jquery.min.js?v=2016070501"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl;?>/js/common.js?v=2016120901"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl;?>/js/layouts.js?v=2016081001"></script>
    <!--
    <script type="text/javascript" src="<?php echo $this->absBaseUrl;?>/js/wxpay.js?v=2016070501"></script>
    -->
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/sonic.js?v=2016070501"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/Umeng.js?v=2017080401"></script>
    <link rel="shortcut icon" href="<?php echo $this->absBaseUrl;?>/favicon.ico">
    <style type="text/css">
    <!--
    html,body {
        min-width:320px;
        max-width:480px;
        min-height:100%;
        margin:0 auto;
    }
    body *{
        max-width: 480px;
    }
    ._width_limit *{
        max-width: none;
    }
    html {
      position: relative;
      width: 100%;
      height: 100%;
    }
    ::-webkit-scrollbar{
      opacity: 0;
    }
    -->
    </style>
</head>

<body>
    <div class="kdlc_mobile_wraper container">
        <!-- 分享链接显示的logo -->
        <div class='bg _hidden'>
            <img src="<?php echo $this->shareLogo ? $this->shareLogo : $this->absBaseUrl.'/logo_share.png?v=2016070501';?>"/>
        </div>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <input class="_hidden" type="text" />
        <input class="_hidden" type="password"/>
        <!-- //用于阻止 chrome表单自动填充的占位符 -->
        <?php echo $content;?>
        <div id="circle-mask">
            <div id="circle-hint">加载中...</div>
        </div>
    </div>
    <?php if(intval(Yii::$app->getRequest()->get('is_skip')) == 1 ): ?>
    <script src="<?php echo $this->absBaseUrl;?>/js/skip-app.js?v=2016070501"></script>
    <script type="text/javascript">
        skipApp("<?php echo \common\models\MessagesDetection::getParams($this->isSkipID);?>",<?php echo intval(Yii::$app->getRequest()->get('refresh'));?>);
    </script>
    <?php endif;?>
</body>
</html>
