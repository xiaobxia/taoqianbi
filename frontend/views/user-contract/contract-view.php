<?php
use yii\helpers\Url;
?>
<script language="javascript" type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl();?>/js/sonic.js"></script>
<style type="text/css">
    #wrapper {
        min-width: 320px;
        max-width: 480px;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        margin: 0 auto;
    }
    #invest_content{
        width: 100%;
        margin: 0 auto;
        padding-left: 12px;
        padding-right: 12px;
        white-space: normal;
        line-height: 1.5em;
        background: white;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        font-size: 13px;
        padding-top: 20px;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    }

</style>
<div id="wrapper">
    <div id="invest_content"><?php echo $content;?></div>
</div>

