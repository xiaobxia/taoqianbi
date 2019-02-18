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
use common\models\HfdFinancialRecord;
use common\models\LoanHfdOrder;
?>
    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>打款ID</th>
            <th>订单ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>申请金额</th>
            <th>期望打款时间</th>
            <th>实际打款金额</th>
            <th>收款人姓名</th>
            <th>绑卡银行</th>
            <th>银行卡号</th>
            <th>业务类型</th>
            <th>打款渠道</th>
            <th>审核状态</th>
            <th>打款状态</th>
            <th>审核人</th>
            <th>申请时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($data as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <th><?php echo $value['order_id']; ?></th>
                <th><?php echo $data1[$value['id']]; ?></th>
                <th><?php echo $data2[$value['id']]; ?></th>
                <th><?php echo sprintf("%0.2f",$value['money']/1000000)."万元"; ?></th>
                <th><?php echo date('Y-m-d',$value['plan_pay_money_time']); ?></th>
                <th><?php echo empty($value['true_money']) ? "--" : sprintf("%0.2f",$value['true_money']/1000000)."万元"; ?></th>
                <th><?php echo $value['payee_name']; ?></th>
                <th><?php echo $value['payee_card_name']; ?></th>
                <td><?php echo $value['payee_card_name_branch']; ?></td>
                <td><?php echo "好房贷"; ?></td>
                <td><?php echo "线下打款"; ?></td>
                <th><?php echo !isset($value['status']) ? "--" : HfdFinancialRecord::$status[$value['status']]; ?></th>
                <?php if($value['status'] == HfdFinancialRecord::LOAN_STATUS_FINANCE_ALREADY_MONEY):   ?>
                    <th><?php echo "已打款"; ?></th>
                <?php else : ?>
                    <th><?php echo "待打款"; ?></th>
                <?php endif; ?>
                <th><?php echo $value['operator_name']; ?></th>
                <th><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></th>
                <th>
                    <a href="<?php echo Url::toRoute(['hfd-dispatch/detail','order_id'=>$value['order_id'],'hfd_financial_record_id'=>$value['id']]); ?>">详情</a>
                    <?php if ($value['status'] == HfdFinancialRecord::LOAN_STATUS_FINANCE_TRIAL): ?>
                        <a href="<?php echo Url::toRoute(['financial/hfd-check','order_id'=>$value['order_id'],'hfd_financial_record_id'=>$value['id']]); ?>">审核</a>
                    <?php endif; ?>
                    <?php if ($value['status'] == HfdFinancialRecord::LOAN_STATUS_FINANCE_LOAN_MONEY): ?>
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