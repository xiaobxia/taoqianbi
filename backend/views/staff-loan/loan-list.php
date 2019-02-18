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
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
$this->shownav('loan', 'menu_ygb_zc_lqd_fk_lb');
$this->showsubmenu('放款列表', array(
    array('零钱包放款', Url::toRoute('staff-loan/pocket-loan-list'), 1),
//    array('房租宝放款', Url::toRoute('staff-loan-house/house-loan-list'),0),
//    array('分期购发货', Url::toRoute('installment-shop/shipments-list'),0)
));
?>

<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
借款ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
借款人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
审核状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserLoanOrder::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
业务类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
订单类型：<?php echo Html::dropDownList('card_type', Yii::$app->getRequest()->get('card_type', ''), \common\models\BaseUserCreditTotalChannel::$card_type); ?>&nbsp;
下单时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
<!--
&nbsp;&nbsp;&nbsp;&nbsp;<a style="float: left;" onclick="return confirmMsg('是否一键审核通过')" href="<?php echo Url::toRoute(['staff-loan/batch-approve'])?>" id="batchreview"><button class="btn">一键审核通过</button></a>
 -->
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>借款ID</th>
                <th>用户ID</th>
                <th>用户姓名</th>
                <th>用户手机</th>
                <th>子类型</th>
                <th>借款类型</th>
                <th>借款总额(元)</th>
                <th>借款利率(万分之一)</th>
                <th>天数</th>
                <th>利息(元)</th>
                <th>状态</th>
                <th>下单时间</th>
                <th>滞纳金(元)</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['loanPerson']['name']; ?></td>
                    <th><?php echo $value['loanPerson']['phone']; ?></th>
                    <th><?php echo UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.@\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?></th>
                    <th><?php echo isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?></th>
                    <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
                    <th><?php echo $value['apr']; ?></th>
                    <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
                    <th><?php echo sprintf("%0.2f",$value['loan_interests']/100); ?></th>
                    <th><?php echo isset(UserLoanOrder::$status[$value['status']])?UserLoanOrder::$status[$value['status']]:""; ?></th>
                    <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
                    <th><?php echo sprintf("%0.2f",$value['late_fee']/100); ?></th>
                    <th>
                        <a href="<?php echo Url::toRoute(['staff-loan/pocket-loan', 'id' => $value['id']]);?>">放款</a>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>