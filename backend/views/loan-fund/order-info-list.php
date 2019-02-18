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

?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script type="text/javascript"
        src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'action'=> yii\helpers\Url::toRoute(['/loan-fund/order-info-list']),'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>

资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), [0=>'全部']+LoanFund::getAllFundArray()); ?>&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
资方订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('fund_order_id', ''); ?>" name="fund_order_id" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
订单状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserLoanOrder::$status); ?>&nbsp;
<br/>
申请时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">

打款时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_begintime', ''); ?>" name="pay_begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_endtime', ''); ?>" name="pay_endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">

状态：<?php echo Html::dropDownList('info_status', Yii::$app->getRequest()->get('info_status', ''), [10000=>'全部',10001=>'所有有效状态']+ OrderFundInfo::STATUS_LIST); ?>&nbsp;

<input style="display: none" class="btn" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" type="submit">
<input type="checkbox" name="is_summary" value="1"  <?php if(Yii::$app->getRequest()->get('is_summary', '0')==1):?> checked <?php endif; ?> > 显示汇总(勾选后，查询变慢)&nbsp;&nbsp;&nbsp;

<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>

<div class="">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <th>资方</th>
                <th>订单ID</th>
                <th>订单状态</th>
                <th>借款期限</th>
                <th>放款时间</th>
                <th>资方订单ID</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>本金（元）</th>
                <th>所有费用（元）</th>
                <th>我方服务费（元）</th>
                <th>利息（元）</th>
                <th>保证金（元）</th>
                <th>资方服务费（元）</th>
                <th>征信费（元）</th>
                <th>逾期利息（元）</th>
                <th>逾期服务费（元）</th>
                <th>续期手续费（元）</th>
                <th>续期服务费（元）</th>
                <th>用户还款金额（元）</th>
                <th>抵用券</th>
                <!--  <th>用户真实还款（元）</th>-->
                <th>垫付金额（元）</th>
                <th>结算状态</th>
                <th>结算类型</th>
                <th>放款主体</th>
                <th>还款主体</th>
                <th>打款金额</th>
                <th>打款时间</th>
                <th>操作</th>
            </tr>
            <?php
            foreach ($rows as $row):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $row['id']?></td>
                    <td><?php echo $row['name']?></td>
                    <td><?php echo $row['order_id']?></td>
                    <td><?php echo isset(UserLoanOrder::$status[$row['order_status']])?UserLoanOrder::$status[$row['order_status']]:'未知状态'.$row['order_status']?></td>
                    <td><?php echo $row['loan_term']?></td>
                    <td><?php echo ($row['fund_pay_time'] >0 ) ? date('Y-m-d H:i:s',$row['fund_pay_time']):'无'?></td>
                    <td><?php echo $row['fund_order_id']?></td>
                    <td><?php echo common\models\fund\OrderFundInfo::STATUS_LIST[$row['status']]?></td>
                    <td><?php echo date('Y-m-d H:i:s', $row['created_at'])?></td>
                    <td><?php echo sprintf("%0.2f",$row['money_amount']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['total_fee']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['service_fee']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['interest']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['deposit']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['fund_service_fee']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['credit_verification_fee']/100)?></td>
                    <td>应收<?php echo sprintf("%0.2f",$row['cacl_overdue_interest']/100)?><br/>
                        实收<?php echo sprintf("%0.2f",$row['overdue_interest']/100)?></td>
                    <td>应收<?php echo sprintf("%0.2f",($row['late_fee']-$row['cacl_overdue_interest'])/100)?><br/>
                        实收<?php echo sprintf("%0.2f",$row['overdue_fee']/100)?>
                    </td>
                    <td><?php echo sprintf("%0.2f",$row['renew_fee']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['renew_service_fee']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['user_repay_amount']/100)?></td>
                   	<td><?php echo sprintf("%0.2f",$row['coupon_money']/100)?></td>
                   <!--  <td><?php echo sprintf("%0.2f",($row['true_total_money']-$row['coupon_money'])/100)?></td>-->
                     <td><?php echo sprintf("%0.2f",$row['prepay_amount']/100)?></td>
                    <td><?php echo !empty(OrderFundInfo::SETTLEMENT_STATUS_LIST[$row['settlement_status']])?OrderFundInfo::SETTLEMENT_STATUS_LIST[$row['settlement_status']]:0;?></td>
                    <td><?php echo !empty(OrderFundInfo::SETTLEMENT_TYPE_LIST[$row['settlement_type']])?OrderFundInfo::SETTLEMENT_TYPE_LIST[$row['settlement_type']]:0; ?></td>
                    <td><?php echo $row['pay_account_id']?$pay_accounts[$row['pay_account_id']]:'无'?></td>
                    <td><?php echo (!empty($row['repay_account_id']) && $row['repay_account_id'] && !empty($repay_accounts[$row['repay_account_id']]))?$repay_accounts[$row['repay_account_id']]:'无'?></td>
                    <td><?php echo $row['pay_money']?sprintf("%0.2f",$row['pay_money']/100).'元':'无'?></td>
                    <td><?php echo $row['pay_time']?date('Y-m-d', $row['pay_time']):'无'?></td>
                    <td>
                        <?php if( in_array($row['status'], OrderFundInfo::$allow_switch_fund_status)
                                && in_array($row['order_status'], UserLoanOrder::$allow_switch_fund_status)):?>
                        <a href="<?php echo Url::toRoute(['switch-fund','order_id'=>$row['order_id'],'return_url'=>Url::current()]);?>" >切换资方</a>
                        <?php endif;?>

                        <?php if($row['order_status']==UserLoanOrder::STATUS_PAY):?>
                            <a href="<?php echo Url::toRoute(['/financial/wy-fund-order-pay-rejected', 'id' => $row['order_id'],'return_url'=>Url::current()]);?>">放款驳回</a>
                        <?php endif;?>
                    </td>
                </tr>

            <?php endforeach;?>


    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $pagination]); ?>

<?php if(isset($dataSt) && !empty($dataSt)): ?>
			<table frame="above" align="right">
		        <tr>
		        	<td align="center" style="color: red;">本金总计：</td>
		            <td align="center" style="color: red;">所有费用总计：</td>
		            <td align="center" style="color: red;">我方服务费总计：</td>
		            <td align="center" style="color: red;">利息总计：</td>
		            <td align="center" style="color: red;">保证金总计：</td>
		            <td align="center" style="color: red;">资方服务费总计：</td>
		            <td align="center" style="color: red;">征信费（元）总计:</td>
		            <td align="center" style="color: red;"> 续期手续费总计:</td>
		            <td align="center" style="color: red;">续期服务费总计（元）</td>
		            <td align="center" style="color: red;">垫付金额总计（元）</td>
		        </tr>
		        <tr>
		         	<td style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_money_amount'] / 100) ?></td>
		            <td style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_total_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_service_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_interest'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_deposit'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_fund_service_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_credit_verification_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_renew_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_renew_service_fee'] / 100) ?></td>
		            <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['sum_prepay_amount'] / 100) ?></td>
		        </tr>
		    </table>
		<?php endif; ?>



