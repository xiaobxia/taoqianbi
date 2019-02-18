<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 11:41
 */
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserOrderLoanCheckLog;
use common\models\UserLoanOrder;
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" xmlns="http://www.w3.org/1999/html"></script>
    <link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <h3 style="color: #3325ff;font-size: 14px">错误订单详情页</h3>
    <table class="tb tb2 fixpadding" style="border: 1px solid">
        <tr class="header">
            <th style="border: 1px solid;text-align: center">订单ID</th>
            <th style="border: 1px solid;text-align: center">用户ID</th>
            <th style="border: 1px solid;text-align: center">失败原因</th>
            <th style="border: 1px solid;text-align: center">时间</th>

        </tr>
        <?php foreach($message as $k=> $item):?>
            <tr class="hover">
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $item['order_id']; ?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $item['user_id']; ?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $item['remark'],$item['reason_remark']?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo date('Y-m-d H:i:s',$item['updated_at'])?></td>
            </tr>
        <?php endforeach?>
    </table>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php if (empty($message)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
