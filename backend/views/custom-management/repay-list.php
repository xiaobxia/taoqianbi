<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/25
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

$this->shownav('service', 'menu_user_repay_list');
if (isset($type) && $type == "list") {
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('custom-management/pocket-repay-list'), 1),
        array('还款审核', Url::toRoute('custom-management/pocket-repay-trail-list'), 0),
        array('扣款列表', Url::toRoute('custom-management/pocket-repay-cut-list'), 0)
    ));
} elseif (isset($type) && $type == "trail") {
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('custom-management/pocket-repay-list'), 0),
        array('还款审核', Url::toRoute('custom-management/pocket-repay-trail-list'), 1),
        array('扣款列表', Url::toRoute('custom-management/pocket-repay-cut-list'), 0)
    ));
} elseif (isset($type) && $type == "cut") {
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('custom-management/pocket-repay-list'), 0),
        array('还款审核', Url::toRoute('custom-management/pocket-repay-trail-list'), 0),
        array('扣款列表', Url::toRoute('custom-management/pocket-repay-cut-list'), 1)
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
<?php $form = ActiveForm::begin(['method' => "get", 'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;']]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;"/>&nbsp;
    订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;"/>&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;"/>&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;"/>&nbsp;
    <input type="submit" name="search_submit" value="搜索" class="btn"/>
    <?php $form = ActiveForm::end(); ?>
    <?php if ($type == 'trail'): ?>
        &nbsp;&nbsp;&nbsp;&nbsp;<a style="float: left;" onclick="return confirmMsg('是否一键审核通过')" href="<?php echo Url::toRoute(['staff-repay/batch-approve']) ?>" id="batchreview"><button class="btn">一键审核通过</button></a>
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
            <th>抵扣券金额</th>
            <th>申请扣款金额</th>
            <th>放款日期</th>
            <th>应还日期</th>
            <th>还款日期</th>
            <th>是否逾期</th>
            <th>逾期天数</th>
            <th>备注</th>
            <th>用户留言</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
<?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['order_id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php echo $value['loanPerson']['phone']; ?></td>
                <td><?php echo sprintf("%0.2f", $value['principal'] / 100); ?></td>
                <td><?php echo sprintf("%0.2f", $value['interests'] / 100); ?></td>
                <td><?php echo sprintf("%0.2f", $value['late_fee'] / 100); ?></td>
                <td><?php echo sprintf("%0.2f", $value['true_total_money'] / 100); ?></td>
                <td><?php echo sprintf("%0.2f", $value['coupon_money'] / 100); ?></td>
                <td><?php echo sprintf("%0.2f", $value['current_debit_money'] / 100); ?></td>
                <td><?php echo date('Y-m-d', $value['loan_time']); ?></td>
                <td><?php echo date('Y-m-d', $value['plan_fee_time']); ?></td>
                <td><?php echo $value['true_repayment_time'] ? date('Y-m-d', $value['true_repayment_time']) : '-'; ?></td>
                <td><?php echo $value['is_overdue'] == 1 ? "是" : "否"; ?></td>
                <td><?php echo $value['overdue_day']; ?></td>
                <td class="remark" title='<?php echo $value['remark']; ?>'><?php echo mb_substr($value['remark'], 0, 5); ?></td>
                <td><?php echo $value['user_book']; ?></td>
                <td><?php echo isset(UserLoanOrderRepayment::$status[$value['status']]) ? UserLoanOrderRepayment::$status[$value['status']] : ""; ?></td>
                <td>
    <?php if ($type == 'list'): ?>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-view', 'id' => $value['id']]); ?>">查看</a>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-remark', 'id' => $value['id']]); ?>">备注</a>
                        <?php if ($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
                            <!--<a href="<?php echo Url::toRoute(['staff-repay/force-finish-debit', 'id' => $value['id']]); ?>">置为已还款</a>-->
                        <?php } ?>
                        <?php if ($value['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
                           <!-- <a href="<?php echo Url::toRoute(['staff-repay/repay-edit', 'id' => $value['id']]); ?>">修改实际还款金额</a>-->
                        <?php } ?>
                    <?php elseif ($type == 'trail'): ?>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-trail', 'id' => $value['id']]); ?>">审核</a>
                    <?php elseif ($type == 'retrail'): ?>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-retrail', 'id' => $value['id']]); ?>">复审</a>
                    <?php elseif ($type == 'cut'): ?>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-cut', 'id' => $value['id']]); ?>">扣款</a>
                    <?php endif; ?>
                    <?php if (UserLoanOrderRepayment::STATUS_NORAML >= $value['status'] /* && strtotime(date('Y-m-d', time())) > strtotime(date('Y-m-d',$value['plan_repayment_time'])) */): ?>
                        <a href="<?php echo Url::toRoute(['staff-repay/pocket-view-apply', 'id' => $value['id']]); ?>">发起还款申请</a>
                    <?php endif; ?>
                    <?php if ($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
                    <!--<a onclick="if(confirmMsg('确定要将该记录加入催收名单吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['add-collection', 'id' => $value['id']]); ?>">入催</a>-->
    <?php } ?>
                </td>
            </tr>
    <?php endforeach; ?>
    </table>
    <?php if (empty($info)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>