<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use common\models\fund\LoanFund;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('financial', 'menu_financial_subsidiary_ledger_list');
if(isset($type) && $type == "day"){
    $this->showsubmenu('收付款统计表', array(
        array('日表', Url::toRoute('financial/subsidiary-ledger-day-list'), 1),
        array('月表', Url::toRoute('financial/subsidiary-ledger-month-list'),0)

    ));
} elseif(isset($type) && $type == "month"){
     $this->showsubmenu('收入统计表', array(
        array('日表', Url::toRoute('financial/subsidiary-ledger-day-list'), 0),
        array('月表', Url::toRoute('financial/subsidiary-ledger-month-list'),1)
    ));
    }
?>

<style>
.tb2 th{ font-size: 12px;}
table th{text-align: center}
table td{text-align: center}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：
    <?php if($type=='day'):?>
        <input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d ',dateFmt:'yyyy-MM-dd ',alwaysUseStartDate:true,readOnly:true})"/>
       至<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d ',dateFmt:'yyyy-MM-dd ',alwaysUseStartDate:true,readOnly:true})"/>
    <?php else:?>
        <input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM ',alwaysUseStartDate:true,readOnly:true})"/>
       至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM ',alwaysUseStartDate:true,readOnly:true})"/>
    <?php endif?>
资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), LoanFund::$loan_source); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;&nbsp;
<input style="display: none" type="submit" name="submitcsv" value="导出"onclick="$(this).val('exportcsv');return true;"  class="btn"/>&nbsp;
更新时间：<?php echo $update_time;?> &nbsp;<?php if($type == "day"):?>(每小时更新一次)<?php else: ?>(每天更新一次)<?php endif; ?>

<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>日期</th>
                <th>借款单数</th>
                <th>借款申请金额</th>
                <th>银行打款金额</th>
                <th>综合费用</th>
                <th>续期金额</th>
                <th>实收还款总金额</th>
                <th>实收还款本金</th>
                <th>实收滞纳金</th>
                <th>实收续期服务费</th>
                <th>实收续期利息</th>
                <th>优惠券减免金额</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $value): ?>
                    <tr>
                        <td>
                            <?php if($type == "day"):?>
                                <?php echo empty($value['date']) ? '--' : $value['date'];?>
                            <?php else: ?>
                                <?php if (strlen($value['date']) <= 7 ) $value['date'] = $value['date'].'01';  echo empty($value['date']) ? '--' : date("Y-m", strtotime($value['date']));?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo empty($value['loan_num'])?'--':$value['loan_num'];?></td>
                        <td><?php echo empty($value['loan_money'])?'--':number_format($value['loan_money']/100,2);?></td>
                        <td><?php echo empty($value['true_loan_money'])?'--':number_format($value['true_loan_money']/100,2);?></td>
                        <td><?php echo empty($value['counter_fee'])?'--':number_format($value['counter_fee']/100,2);?></td>
                        <td><?php echo empty($value['rollover_money'])?'--':number_format($value['rollover_money']/100,2);?></td>
                        <td><?php echo empty($value['true_total_principal'])?'--':number_format($value['true_total_principal']/100,2);?></td>
                        <td><?php echo empty($value['true_total_money'])?'--':number_format($value['true_total_money']/100,2);?></td>
                        <td><?php echo empty($value['late_fee'])?'--':number_format($value['late_fee']/100,2);?></td>
                        <td><?php echo empty($value['true_rollover_counterfee'])?'--':number_format($value['true_rollover_counterfee']/100,2);?></td>
                        <td><?php echo empty($value['true_rollover_apr'])?'--':number_format($value['true_rollover_apr']/100,2);?></td>
                        <td><?php echo empty($value['coupon_money'])?'--':number_format($value['coupon_money']/100,2);?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<br>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<br>
<br>
<br>
<p style="color: red">备注</p>
<?php if($type == "day"):?>
<p>借款单数：当天申请借款且打款成功的单数</p>
<p>借款申请金额：当天用户申请借款且打款成功的总金额（包含综合费用）</p>
<p>银行打款金额：当天平台实际放款金额</p>
<p>综合费用：当天借款申请金额-当天银行打款金额</p>
<p>续期金额：当天应还款金额，申请续期服务后延期还款的总金额</p>
<p>实收还款总金额：实收还款本金+实收滞纳金+实收续期服务费+实收续期利息-优惠券金额</p>
<p>实收还本金：当天用户化还款本金金额（包含综合费用）</p>
<?php else: ?>
<p>借款单数：当月申请借款且打款成功的单数</p>
<p>借款申请金额：当月用户申请借款且打款成功的总金额（包含综合费用）</p>
<p>银行打款金额：当月平台实际放款金额</p>
<p>综合费用：当月借款申请金额-当月银行打款金额</p>
<p>续期金额：当月应还款金额，申请续期服务后延期还款的总金额</p>
<p>实收还款总金额：实收还款本金+实收滞纳金+实收续期服务费+实收续期利息-优惠券金额</p>
<p>实收还款本金：当月用户还款本金金额（包含综合费用）</p>
<?php endif; ?>
<table  align="right" width="300">
        <tr>
            <td colspan="10"><hr  color="red" /></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th><strong>统计时间</strong></th></td>
            <td style="color: red;"><strong><?php echo $update_time;?></strong></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>放款单数总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['loan_num'])?'--':$sub_info['loan_num'];?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>借款申请金额总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['loan_money'])?'--':number_format($sub_info['loan_money']/100,2);?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>银行打款金额总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['true_loan_money'])?'--':number_format($sub_info['true_loan_money']/100,2);?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>续期金额总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['rollover_money'])?'--':number_format($sub_info['rollover_money']/100,2);?></td>
        </tr>
         <tr style="color: red;">
            <td colspan="" ><th>实收还款总金额总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['true_total_principal'])?'--':number_format($sub_info['true_total_principal']/100,2);?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>实收借款本金总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['true_total_money'])?'--':number_format($sub_info['true_total_money']/100,2);?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>实收续期服务费总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['true_rollover_counterfee'])?'--':number_format("%0.2f",$sub_info['true_rollover_counterfee']/100,2);?></td>
        </tr>
        <tr style="color: red;">
            <td colspan="" ><th>实收续期利息总计:</th></td>
            <td style="color: red;" ><?php echo empty($sub_info['true_rollover_apr'])?'--':number_format($sub_info['true_rollover_apr']/100,2);?></td>
        </tr>
</table>


