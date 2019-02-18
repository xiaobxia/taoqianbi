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
use common\models\fund\LoanFund;

if(isset($type) && $type == "list"){
    $this->shownav('loan', 'menu_ygb_zc_lqd_hk');
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'), 1),
        array('还款审核', Url::toRoute('staff-repay/pocket-repay-trail-list'),0),
        array('扣款列表', Url::toRoute('staff-repay/pocket-repay-cut-list'),0)
    ));
} elseif(isset($type) && $type == "trail"){
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'),0),
        array('还款审核', Url::toRoute('staff-repay/pocket-repay-trail-list'),1),
        array('扣款列表', Url::toRoute('staff-repay/pocket-repay-cut-list'),0)
    ));
}elseif(isset($type) && $type == "cut"){
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'),0),
        array('还款审核', Url::toRoute('staff-repay/pocket-repay-trail-list'),0),
        array('扣款列表', Url::toRoute('staff-repay/pocket-repay-cut-list'),1)
    ));
}elseif(isset($type) && $type == "overdue"){
    $this->shownav('loan', 'menu_ygb_zc_lqd_yq');
    $this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'),0),
        array('还款审核', Url::toRoute('staff-repay/pocket-repay-trail-list'),0),
        array('扣款列表', Url::toRoute('staff-repay/pocket-repay-cut-list'),1)
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
资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), [0=>'全部']+LoanFund::getAllFundArray()); ?>&nbsp;
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
业务类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
订单类型：<?php echo Html::dropDownList('card_type', Yii::$app->getRequest()->get('card_type', ''), \common\models\BaseUserCreditTotalChannel::$card_type); ?>&nbsp;
<?php if($type == 'list'):?>
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserLoanOrderRepayment::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
<?php endif;?>
是否逾期：<?php echo Html::dropDownList('is_overdue', Yii::$app->getRequest()->get('is_overdue', ''), UserLoanOrderRepayment::$overdue, array('prompt' => '-所有状态-')); ?>&nbsp;
应还日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true})">
<br/>
还款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('r_begintime', ''); ?>" name="r_begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('r_endtime', ''); ?>"  name="r_endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true})">
&nbsp;&nbsp;&nbsp;逾期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('overdue_from_day', ''); ?>" name="overdue_from_day" placeholder="开始区间值，不填无限">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('overdue_to_day', ''); ?>"  name="overdue_to_day" placeholder="结束区间值，不填无限">(天)
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php if(isset($type) && ($type == "overdue"||$type=='list')){ ?>
&nbsp;&nbsp;&nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php }?>
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
<?php if($type == 'trail'): ?>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="float: left;" onclick="return confirmMsg('是否一键审核通过')" href="<?php echo Url::toRoute(['staff-repay/batch-approve'])?>" id="batchreview"><button class="btn">一键审核通过</button></a>
<?php endif; ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>还款订单ID</th>
                <th>资方</th>
                <th>订单号</th>
                <th>用户ID</th>
                <th>姓名</th>
                <th>手机号</th>
                <th>本金</th>
                <th>利息</th>
                <th>滞纳金</th>
                <th>已还金额</th>
                <th>申请扣款金额</th>
                <th>抵扣券金额</th>
                <th>放款日期</th>
                <th>应还日期</th>
                <th>还款日期</th>
                <th>是否逾期</th>
                <th>逾期天数</th>
                <th>备注</th>
                <th>子类型</th>
                <th>渠道</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php
            $fund = LoanFund::getAllFundArray();
            $fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);

            foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo !empty(($fund[$value['userLoanOrder']['fund_id']]))?$fund[$value['userLoanOrder']['fund_id']]:$fund_koudai->name; ?></td>
                    <td><?php echo $value['order_id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['loanPerson']['name']; ?></td>
                    <td class="click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                    <td><?php echo sprintf("%0.2f",$value['principal']/100); ?></td>
                    <td><?php echo sprintf("%0.2f",$value['interests']/100); ?></td>
                    <td><?php echo sprintf("%0.2f",$value['late_fee']/100); ?></td>
                    <td><?php echo sprintf("%0.2f",$value['true_total_money']/100); ?></td>
                    <td><?php echo sprintf("%0.2f",$value['current_debit_money']/100); ?></td>
                    <td><?php echo (!empty($value['coupon_money'])) ? sprintf("%0.2f",$value['coupon_money']/100) : "-"; ?></td>
                    <td><?php echo date('Y-m-d',$value['loan_time']); ?></td>
                    <td><?php echo date('Y-m-d',$value['plan_fee_time']); ?></td>
                    <td><?php echo $value['true_repayment_time'] ? date('Y-m-d',$value['true_repayment_time']) : '-'; ?></td>
                    <td><?php echo $value['is_overdue'] == 1 ? "是" : "否"; ?></td>
                    <td><?php echo $value['overdue_day']; ?></td>
                    <td class="remark" title='<?php echo $value['remark']; ?>'><?php echo mb_substr($value['remark'],0,5); ?></td>
                    <th><?php echo (isset(UserLoanOrder::$sub_order_type[$value['userLoanOrder']['sub_order_type']])?UserLoanOrder::$sub_order_type[$value['userLoanOrder']['sub_order_type']]:"").'('.@\common\models\BaseUserCreditTotalChannel::$card_types[$value['userLoanOrder']['card_type']].')'; ?></th>
                    <th><?php echo isset($value['loanPerson']['source_id']) ? LoanPerson::$person_source[$value['loanPerson']['source_id']] : '-' ?></th>
                    <td><?php echo isset(UserLoanOrderRepayment::$status[$value['status']])?UserLoanOrderRepayment::$status[$value['status']]:""; ?></td>
                    <td>
                        <?php if($type == 'list'):?>
                            <a href="<?php echo Url::toRoute(['pocket-view', 'id' => $value['id']]);?>">查看</a>
                            <a href="<?php echo Url::toRoute(['pocket-remark', 'id' => $value['id']]); ?>">备注</a>
                            <a href="<?php echo Url::toRoute(['repayment-voucher', 'id' => $value['id']]); ?>">还款凭证</a>
                            <?php if($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){?>
                                <a href="<?php echo Url::toRoute(['force-finish-debit', 'id' => $value['id']]);?>">置为已还款</a>
                            <?php }?>
                            <?php if($value['status'] ==UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){?>
                            <a href="<?php echo Url::toRoute(['repay-edit', 'id' => $value['id']]);?>">修改实际还款金额</a>
                                <a onclick="setLive('<?php echo $value['order_id']; ?>')" href="javascript:;">置为生息中</a>
                            <?php }else{?>
<!--                            <a href="--><?php //echo Url::toRoute(['repay-delay', 'id' => $value['order_id']]);?><!--">延期</a>-->
                            <?php }?>
<!--                            --><?php //if($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){?>
<!--                            <a href="--><?php //echo Url::toRoute(['force-part-debit', 'id' => $value['id']]);?><!--">部分还款</a>-->
<!--                            --><?php //}?>
                            <?php if($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE && $value['true_total_money']!=$value['coupon_money']): ?>
                                <a onclick="confirmRedirect('确定要取消部分还款吗？', '<?php echo Url::toRoute(['cancel-part-repay', 'id' => $value['order_id']]);?>')" href="javascript:;">取消部分还款</a>
                            <?php endif; ?>
                        <?php elseif($type == 'trail'):?>
                            <a href="<?php echo Url::toRoute(['pocket-trail', 'id' => $value['id']]);?>">审核</a>
                        <?php elseif($type == 'retrail'):?>
                            <a href="<?php echo Url::toRoute(['pocket-retrail', 'id' => $value['id']]);?>">复审</a>
                        <?php elseif($type == 'cut'):?>
                        <a href="<?php echo Url::toRoute(['pocket-cut', 'id' => $value['id']]);?>">扣款</a>
                        <?php endif;?>
                        <?php if(UserLoanOrderRepayment::STATUS_NORAML >= $value['status'] /*&& strtotime(date('Y-m-d', time())) > strtotime(date('Y-m-d',$value['plan_repayment_time']))*/):?>
                            <a href="<?php echo Url::toRoute(['pocket-view-apply', 'id' => $value['id']]);?>">申请还款</a>
                        <?php endif;?>
                        <?php if($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){?>
                        <!--<a onclick="if(confirmMsg('确定要将该记录加入催收名单吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['add-collection', 'id' => $value['id']]);?>">入催</a>
                          -->
                        <?php }?>
                        <?php if($value['overdue_day'] <= 3 && $value['overdue_day'] > 0):?>
                            <a href="<?php echo Url::toRoute(['reset-overdue', 'id' => $value['id']]);?>">重置逾期</a>
                        <?php endif;?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<script type="application/javascript">
    function setLive(order_id) {
        var name=prompt("请输入原因","操作错误");
        if(name){
            var  url = "<?php echo Url::toRoute(['reset-interest']);?>"+'&id='+order_id+'&note='+name;
            window.location.href = url;
        } else if(name==='') {
            alert("原因不能为空")
        }
    }
</script>
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
