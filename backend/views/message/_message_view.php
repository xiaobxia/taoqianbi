<?php
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;

use common\models\message\Message;
?>
<style media="screen">
    div {
        display: block;
    }
    .message-detail-container {
        padding: 16px 74px;
        font-size: 14px;
        line-height: 24px;
        margin-bottom: 20px;
    }
    .margin-top-3 {
        margin-top: 24px !important;
    }
    .console-box-border {
        border: 1px solid #E1E6EB;
    }
    .text-center {
        text-align: center;
    }
    h4{
        font-size: 18px;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    .margin-top-2 {
        margin-top: 16px !important;
    }
    .console-title-border {
        border-bottom: 1px solid #DDD;
    }
</style>
<div class="message-detail-container console-box-border margin-top-3">
    <div class="text-center">
        <h4><?php echo $view_info->message_title ?></h4>
        <div><?php echo date('Y-m-d H:i:s',$view_info->created_at) ?><span style="margin-left:10px;">发布者：<?php echo $view_info->sender_name ?></span></div>
    </div>
    <div class="detail-content breakall">
        <div class="console-title-border margin-top-2">
        </div>
        <div class="margin-top-2">
            <?php echo $view_info->message_body; ?>

        </div>
    </div>
</div>
