<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRecord;
use common\models\LoanRepayment;

$this->showsubmenu('催收详情', array(
    array('列表', Url::toRoute('collection/collection-record-list'), 1)
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>



<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => '', 'options' => ['style' => 'margin-top:5px;']]); ?>
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:60px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="get">
    <table class="tb  fixpadding">
        <tr class="header">
            <th>订单ID</th>
            <th>联系人</th>
            <th>联系人关系</th>
            <th>催收内容</th>
            <th>催收日期</th>
        </tr>
        <?php foreach ($collection_data as $value): ?>
            <tr class="hover">
                <td><?php echo $value['user_loan_order_id']; ?></td>
                <td><?php echo $value['contact_name']; ?></td>
                <td><?php echo $value['relation']; ?></td>
                <td><?php echo $value['remark']; ?></td>
                <td><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($collection_data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
