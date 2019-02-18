<?php
use common\helpers\Util;
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8"/>
        <meta name="format-detection" content="telephone=no">
        <title><?php echo $this->title ? $this->title : APP_NAMES; ?></title>
        <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
        <meta name="description" content="<?php echo APP_NAMES ?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
        <meta name="appVersion" content="<?php $data = $this->actionSetHeaderUrl();if(isset($data['appVersion'])){echo $data['appVersion'];}?>">
        <?php
        $source = \Yii::$app->controller->getSource();
        if($source == \common\models\LoanPerson::PERSON_SOURCE_HBJB){?>
            <link href="<?=$this->staticUrl('css/style-hbqb.css?v=20170830',2); ?>" rel="stylesheet"/>
        <?php }else if(Util::getMarket() == \common\models\LoanPerson::APPMARKET_XJBT_PRO){?>
            <link href="<?=$this->staticUrl('css/style-pro.css?v=20170830',2); ?>" rel="stylesheet"/>
        <?php }else{?>
            <link href="<?=$this->staticUrl('css/style.css?v=20170830',2); ?>" rel="stylesheet"/>
        <?php }?>
        <?php if($h5_theme_style = Util::t('h5_theme_style')){ ?>
            <link href="<?=$this->staticUrl('css/'.$h5_theme_style,2); ?>" rel="stylesheet"/>
        <?php } ?>
        <script src="<?=$this->staticUrl('js/flexible.js?v=2016110901',2); ?>"></script>
        <script type="text/javascript" src="<?=$this->staticUrl('js/spin.js',2); ?>"></script>
        <script type="text/javascript" src="<?=$this->absBaseUrl; ?>/js/jquery.min.js?v=2016012601"></script>
        <script type="text/javascript" src="<?=$this->absBaseUrl; ?>/js/common.js?v=1"></script>
        <script type="text/javascript" src="<?=$this->absBaseUrl; ?>/js/Umeng.js?v=1"></script>
    </head>
    <body>
        <?php echo $content; ?>
    </body>
</html>
