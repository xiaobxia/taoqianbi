<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use common\models\UserOrderLoanCheckLog;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">用户信息</th></tr>
    <tr>
        <td class="td21">用户ID：</td>
        <td width="200"><?php echo $loanPerson['id']; ?></td>
        <td class="td21">注册时间：</td>
        <td ><?php echo empty($loanPerson['created_at'])?'--':date('Y-m-d',$loanPerson['created_at']); ?></td>
    </tr>

    <tr>
        <td class="td21">姓名：</td>
        <td ><?php echo $loanPerson['name']; ?></td>
        <td class="td21">联系方式：</td>
        <td ><?php echo $loanPerson['phone']; ?></td>
    </tr>

    <tr>
        <td class="td21">身份证号：</td>
        <td ><?php echo $loanPerson['id_number']; ?></td>
        <td class="td21">出生日期：</td>
        <td ><?php echo empty($loanPerson['birthday'])?'--':date('Y-m-d',$loanPerson['birthday']); ?></td>
    </tr>
</table>
<table class="tb tb2 fixpadding">

    <tr><th class="partition" colspan="10">借款信息</th></tr>
    <tr>
        <td class="td21">订单号：</td>
        <td width="200"><?php echo $order['id']; ?></td>
        <td class="td21">借款类型：</td>
        <td ><?php echo UserLoanOrder::$loan_type[$order['order_type']]; ?></td>
    </tr>

    <tr>
        <td class="td21">借款金额（元）：</td>
        <td ><?php echo sprintf("%0.2f",$order['money_amount']/100); ?></td>
        <td class="td21">申请日期：</td>
        <td ><?php echo date('Y-m-d H:i:s',$order['order_time']); ?></td>
    </tr>

    <tr>
        <td class="td21">还款方式：</td>
        <td ><?php echo UserLoanOrder::$loan_method[$order['loan_method']]; ?></td>
        <td class="td21">借款利率(‱)：</td>
        <td ><?php echo $order['apr'];?></td>
    </tr>

    <tr>
        <td class="td21">借款利息（元）：</td>
        <td><?php echo sprintf("%0.2f",$order['loan_interests']/100); ?></td>
        <td class="td21">服务费（元）：</td>
        <td ><?php echo sprintf("%0.2f",$order['counter_fee']/100); ?></td>
    </tr>

    <tr>
        <td class="td21">逾期状态：</td>
        <td><?php echo $userLoanOrderRepayment['is_overdue']==1 ? '已逾期' : '未逾期' ?></td>
        <td class="td21">逾期天数（天）：</td>
        <td ><?php echo $userLoanOrderRepayment['overdue_day']; ?></td>
    </tr>

</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >订单逾期重置记录</th></tr>
    <tr>
        <?php if (empty($info)): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style="padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th >订单号：</th>
                        <th >还款订单ID：</th>
                        <th >更改前的逾期天数：</th>
                        <th >更改前的逾期状态：</th>
                        <th >更改后的逾期天数：</th>
                        <th >更改后的逾期状态：</th>
                        <th >更改时间：</th>
                    </tr>
                    <?php foreach ($info as $log): ?>
                        <tr>
                            <td><?php echo $log['order_id'];?></td>
                            <td><?php echo $log['repay_order_id'];?></td>
                            <td><?php echo $log['before_overdue_day'];?>天</td>
                            <td><?php echo $log['before_overdue_status'] == 1 ? '已逾期' : '未逾期';?></td>
                            <td><?php echo $log['after_overdue_day'];?>天</td>
                            <td><?php echo $log['after_overdue_status'] == 1 ? '已逾期' : '未逾期';?></td>
                            <td><?php echo date('Y-m-d H:i', $log['created_at']);?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>

<?php $form =  ActiveForm::begin(); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition">发起重置申请</th></tr>
    <tr>
        <td>
            <input type="hidden" value="<?php echo $userLoanOrderRepayment['id']?>" name="id" >
            <input onclick="if(confirmMsg('确认重置？')){return true;}else{return false;}" type="submit" value="重置逾期" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>


