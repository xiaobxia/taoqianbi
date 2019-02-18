<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/8/1
 * Time: 16:05
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\LoanHfdOrder;
use common\models\LoanPersonHfdOperate;
use common\models\LoanHfdRiskPerson;
use common\models\HfdOrderCheckFlow;
use common\models\HfdFinancialRecord;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">风控审核的详情页</th></tr>
    <tr>
        <td class="td24">审核人:</td>
        <td class="td24">订单类型:</td>
        <td class="td24">审核时间:</td>
        <td class="td24">审核前状态:</td>
        <td class="td24">审核后状态:</td>
        <td class="td24">备注:</td>
        <?php foreach($review_log as $item) :?>
        <?php if($item['hfd_financial_record_id'] != 0) :?>
        <td class="td24">财务打款ID:</td>
        <?php break; ?>
        <?php endif; ?>
        <?php endforeach; ?>
   </tr>
    <?php foreach($review_log as $value) :?>
    <tr>
        <td><?php echo $value['operator_name'];?>
        <td><?php echo empty($value['type']) ? "--" : HfdOrderCheckFlow::$type[$value['type']];?></td>
        <td><?php echo date("Y-m-d H:i:s",$value['created_at']);?></td>
        <?php if($value['type'] > HfdOrderCheckFlow::TYPE_FINANCE && $value['type'] <= HfdOrderCheckFlow::TYPE_FINANCE_LOAN): ?>
            <td><?php echo HfdFinancialRecord::$status[$value['before_status']];?></td>
            <td><?php echo HfdFinancialRecord::$status[$value['after_status']];?></td>
        <?php else :?>
            <td><?php echo LoanHfdOrder::$status[$value['before_status']];?></td>
            <td><?php echo LoanHfdOrder::$status[$value['after_status']];?></td>
        <?php endif; ?>
        <td><?php echo empty($value['remark']) ? "--" : $value['remark'];?></td>
        <?php if(!empty($value['hfd_financial_record_id'])) :?>
        <td><?php echo $value['hfd_financial_record_id'];?></td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
</table>