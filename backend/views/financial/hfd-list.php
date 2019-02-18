<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\HfdOrder;
use common\models\LoanHfdOrder;
?>
    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), LoanHfdOrder::$status,array('prompt' => '-所有状态-')); ?>&nbsp;
    创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>id</th>
            <th>订单ID</th>
            <th>借款金额(万元)</th>
            <th>借款年化利率(%)</th>
            <th>借款期限</th>
            <th>借款用途</th>
            <th>还款来源</th>
            <th>放款条件</th>
            <th>状态</th>
            <th>创建时间</th>
            <th>操作人</th>
            <th>操作</th>
        </tr>
        <?php foreach ($data as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <th><?php echo $value['order_id']; ?></th>
                <th><?php echo sprintf("%0.2f",$value['true_loan_money']/1000000); ?></th>
                <th><?php echo sprintf("%0.2f",$value['true_loan_apr']); ?></th>
                <th><?php echo $value['loan_peroid'].'月'; ?></th>
                <td><?php echo $value['loan_purpose']; ?></td>
                <td><?php echo $value['repayment_source']; ?></td>
                <th><?php echo empty($value['loan_condition']) ? "--" : LoanHfdOrder::$loan[$value['loan_condition']]; ?></th>
                <th><?php echo LoanHfdOrder::$status[$value['status']]; ?></th>
                <th><?php echo date('Y-m-d H:i:s',$value['created_at']); ; ?></th>
                <th><?php echo $value['operator_name']; ?></th>
                <th>
                    <a href="<?php echo Url::toRoute(['hfd-dispatch/detail','order_id'=>$value['order_id'],'hfd_financial_record_id'=>$value['id']]); ?>">详情</a>
                    <?php if ($value['status'] == LoanHfdOrder::LOAN_STATUS_FINANCE_PLAY_MONEY): ?>
                        <a href="<?php echo Url::toRoute(['financial/hfd-check','order_id'=>$value['order_id'],'hfd_financial_record_id'=>$value['id']]); ?>">审核</a>
                    <?php endif; ?>
                    <?php if ($value['status'] == LoanHfdOrder::LOAN_STATUS_FINANCE_STAY_MONEY): ?>
                        <a href="<?php echo Url::toRoute(['financial/hfd-loan','order_id'=>$value['order_id'],'hfd_financial_record_id'=>$value['id']]); ?>">打款</a>
                    <?php endif; ?>

                </th>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>