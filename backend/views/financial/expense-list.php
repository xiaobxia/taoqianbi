<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\FinancialExpense;
use common\models\FinancialLoanRecord;
use common\models\UserLoanOrderRepayment;
use common\models\fund\LoanFund;
$this->shownav('financial', 'menu_financial_expense_list');
$this->showsubmenu('运营成本统计', array(
    array('运营成本统计', Url::toRoute('financial/expense-day-list'), 1),

));

?>
<style>
.tb2 th{ font-size: 12px;}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get", 'action'=>Url::toRoute(['financial/expense-list']), 'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
    &nbsp;&nbsp;资方<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), LoanFund::$loan_source); ?>
    <input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;
    更新时间：<?php echo $update_time;?>（每小时更新一次）
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                    <th>日期</th>
                    <th>拒就送单数</th>
                    <th>拒就送打款金额</th>
                    <th>红包派发金额</th>
                    <th>红包打款金额</th>
                    <th>优惠券使用金额</th>
                    <th>优惠券减免金额</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $item): ?>
                    <tr>
                        <td><?php echo $item['date'];?></td>
                        <td><?php echo empty($item['refuse_num'])?'--':$item['refuse_num'];?></td>
                        <td><?php echo empty($item['refuse_money'])?'--':number_format($item['refuse_money']/100,2);?></td>
                        <td><?php echo empty($item['redapply_money'])?'--':number_format($item['redapply_money']/100,2);?></td>
                        <td><?php echo empty($item['redloan_money'])?'--':number_format($item['redloan_money']/100,2);?></td>
                        <td><?php echo empty($item['coupaon_usemoney'])?'--':number_format($item['coupaon_usemoney']/100,2);?></td>
                        <td><?php echo empty($item['coupaon_deratemoney'])?'--':number_format($item['coupaon_deratemoney']/100,2);?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<br>
<br>
<p style="color: red">备注</p>
<p>拒就送单数：当天拒绝借款且打开成功总单数</p>
<p>拒就送打款金额：当天拒就送活动打开金额</p>
<p>红包派发金额：当天现金红包后台派发总额（非实际支出）</p>
<p>红包打开金额：当天现金红包体现审核通过总额（实际支出）</p>
<p>优惠券使用金额：当天借款使用优惠券的折扣总金额（未发生实际支出）</p>
<p>优惠券减免金额：当天还款优惠券减免总金额（实际支出）</p>


