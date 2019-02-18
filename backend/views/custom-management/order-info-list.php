<?php

use yii\helpers\Html;
use yii\grid\GridView;
use  common\models\fund\OrderFundInfo;
use common\models\fund\FundAccount;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use common\models\UserLoanOrder;

use backend\components\widgets\ActiveForm;
use common\models\fund\LoanFund;


/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '订单信息';
$this->params['breadcrumbs'][] = $this->title;
$pay_accounts = FundAccount::getSelectOptions(FundAccount::TYPE_PAY);
$repay_accounts = FundAccount::getSelectOptions(FundAccount::TYPE_REPAY);
$loanfund = LoanFund::getAllFundArray();

?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script type="text/javascript"
        src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <br/>

<span style="color:red;">
说明 :
51放款流程

申请订单  -> 待签约 （用户收到绑卡短信，点击链接完成签约。）-> 订单待推送（一般约几秒钟） -> 订单开始推送 （一般约几秒钟）->订单推送成功 ->  收到支付通知（最迟半小时会通知）放款成功生成满标时间，资方放款时间。
</span>

<br/>
<br/>

<?php $form = ActiveForm::begin(['method' => "get",'action'=> yii\helpers\Url::toRoute(['/custom-management/fund-loan-money-list']),'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), [0=>'全部']+[1=>'51']); ?>&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
资方订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('fund_order_id', ''); ?>" name="fund_order_id" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
订单状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserLoanOrder::$status); ?>&nbsp;
<br/>
申请时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">

打款时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_begintime', ''); ?>" name="pay_begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_endtime', ''); ?>" name="pay_endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">

状态：<?php echo Html::dropDownList('info_status', Yii::$app->getRequest()->get('info_status', ''), [10000=>'全部',10001=>'所有有效状态']+ OrderFundInfo::STATUS_LIST); ?>&nbsp;



<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>

<div class="">



    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>资方订单id</th>
                <th>资方</th>
                <th>订单id</th>
         		<th>用户ID 	</th>
         		<th>姓名</th>
         		<th>借款期限</th>
         		<th>申请金额</th>
         		<th>手续费</th>
         		<th>实际打款金额</th>
         		<th>资方订单状态</th>
         		<th>订单状态</th>
         		<th>推送时间</th>
         		<th>资方放款时间</th>
         		<th>结算时间</th>
         		<th>计划垫付时间</th>
         		<th>满标时间</th>
         	</tr>
            <?php if(!empty($rows)):
            foreach ($rows as $row):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                <th><?php echo $row['id']?></th>
                <th><?php echo $loanfund[$row['fund_id']]?></th>
     			<th><?php echo $row['order_id']?></th>
             	<th><?php echo $row['user_id']?></th>
             	<th><?php echo $row['username']?></th>
             	<th><?php echo $row['loan_term']?></th>
         		<td><?php echo sprintf("%0.2f",$row['money_amount']/100)?></td>
         		<td><?php echo sprintf("%0.2f",$row['counter_fee']/100)?></td>
          		<td><?php echo sprintf('%.2f',  ($row['money_amount'] - $row['counter_fee']) / 100); ?></td>
       			<td><?php echo OrderFundInfo::STATUS_LIST[$row['status']]?></td>
       			<td><?php echo !empty(UserLoanOrder::$status[$row['order_status']])?UserLoanOrder::$status[$row['order_status']]:'无'?></td>

       			<td><?php echo $row['order_push_time']?date('Y-m-d H:i:s', $row['order_push_time']):'无'?></td>
       			<td><?php echo $row['fund_pay_time']?date('Y-m-d H:i:s', $row['fund_pay_time']):'无'?></td>
       			<td><?php echo $row['settlement_time']?date('Y-m-d H:i:s', $row['settlement_time']):'无'?></td>
       			<td><?php echo $row['plan_payment_time']?date('Y-m-d H:i:s', $row['plan_payment_time']):'无'?></td>
       			<td><?php echo $row['fund_arrival_time']?date('Y-m-d H:i:s', $row['fund_arrival_time']):'无'?></td>

                </tr>

            <?php endforeach; endif;?>


    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $pagination]); ?>
