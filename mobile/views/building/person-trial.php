<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/result.css">
<div class="coin"></div>
<p style="margin-top: 0">评估结果</p>
<p>实际授信额度,待风控审核后将会在1个工作日内反馈给您,届时可至<span>个人中心->我的订单</span>查看。</p>
<button id="btn" onclick="submit()"><span id="word">朕知道了</span></button>

<script>
    function submit(){
        window.location.href = "<?php echo Url::toRoute('building/person-center');?>";
    }
</script>