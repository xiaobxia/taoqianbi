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
use common\models\LoanRecordPeriod;
use common\models\UserLoanOrderRepayment;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
use common\models\CardInfo;
$this->shownav('loan', 'menu_ygb_zc_lqd_hkk');
if(isset($type) && $type == "list"){
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'), 1),

    ));
}

?>

    <style>
        .tb2 th{ font-size: 12px;}

        .remark{
            width:20px;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
    </style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    绑卡银行：<?php echo Html::dropDownList('bank_name', Yii::$app->getRequest()->get('bank_name', ''), CardInfo::$bankInfo, array('prompt' => '-所有银行-')); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserLoanOrderRepayment::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
    应还日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">

<input type="submit" name="search_submit" value="过滤" class="btn">
    <input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php if(isset($type) && $type == "overdue"){ ?>
    &nbsp;&nbsp;&nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php }?>
<?php $form = ActiveForm::end(); ?>
<?php if($type == 'trail'): ?>
    &nbsp;&nbsp;&nbsp;&nbsp;<a style="float: left;" onclick="return confirmMsg('是否一键审核通过')" href="<?php echo Url::toRoute(['staff-repay/batch-approve'])?>" id="batchreview"><button class="btn">一键审核通过</button></a>
<?php endif; ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>订单号</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>本金</th>
            <th>利息</th>
            <th>滞纳金</th>
            <th>已还金额</th>
            <th>申请扣款金额</th>
            <th>放款日期</th>
            <th>应还日期</th>
            <th>还款日期</th>
            <th>是否逾期</th>
            <th>逾期天数</th>
            <th>绑卡银行</th>
            <th>备注</th>
            <th>状态</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['order_id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td class="click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                <td><?php echo sprintf("%0.2f",$value['principal']/100); ?></td>
                <td><?php echo sprintf("%0.2f",$value['interests']/100); ?></td>
                <td><?php echo sprintf("%0.2f",$value['late_fee']/100); ?></td>
                <td><?php echo sprintf("%0.2f",$value['true_total_money']/100); ?></td>
                <td><?php echo sprintf("%0.2f",$value['current_debit_money']/100); ?></td>
                <td><?php echo date('Y-m-d',$value['loan_time']); ?></td>
                <td><?php echo date('Y-m-d',$value['plan_fee_time']); ?></td>
                <td><?php echo $value['true_repayment_time'] ? date('Y-m-d',$value['true_repayment_time']) : '-'; ?></td>
                <td><?php echo $value['is_overdue'] == 1 ? "是" : "否"; ?></td>
                <td><?php echo $value['overdue_day']; ?></td>
                <td><?php echo $value['cardInfo']['bank_name']; ?></td>
                <td class="remark" title='<?php echo $value['remark']; ?>'><?php echo mb_substr($value['remark'],0,5); ?></td>
                <td><?php echo isset(UserLoanOrderRepayment::$status[$value['status']])?UserLoanOrderRepayment::$status[$value['status']]:""; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    /**
     * 电话显示*，点击后正常显示
     */
    (function initClickPhoneCol() {
        $('.click-phone').each(function () {
            var $item = $(this);
            var phone = $item.attr('data-phoneraw');
            if (phone && phone.length>5) {
                var phoneshow = phone.substr(0, 3) + '****' + phone.substr(phone.length - 2, 2);
                $item.attr('data-phoneshow', phoneshow);
                $item.text(phoneshow);
            } else {
                $item.attr('data-phoneshow', phone);
                $item.text(phone);
            }
        });
        $('.click-phone').one('click', function () {
            $(this).text($(this).attr('data-phoneraw'));
        })
    })();
</script>
