<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\FinancialDebitRecord;
use common\models\BankConfig;

use common\models\fund\LoanFund;
if($tip===1){
    $this->shownav('financial', 'menu_debit_falied_list');
    $this->showsubmenu('扣款失败列表');
}elseif($tip===2){
    $this->shownav('financial', 'menu_debit_wait_list');
    $this->showsubmenu('待扣款列表');
}else{
    $this->shownav('financial', 'menu_debit_list');
    $this->showsubmenu('扣款列表');
}
$fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), [0=>'全部']+LoanFund::getAllFundArray()); ?>&nbsp;

ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
业务订单表ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_record_id', ''); ?>" name="loan_record_id" class="txt" style="width:120px;">&nbsp;
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
用户名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('username', ''); ?>" name="username" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), FinancialDebitRecord::$status, ['prompt' => '所有状态']); ?>&nbsp;
    业务类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), [5 => APP_NAMES], ['prompt' => '所有代扣类型']); ?>&nbsp;
<br/>
扣款渠道：<?php echo Html::dropDownList('platform', Yii::$app->getRequest()->get('platform', ''), BankConfig::$use_platform, ['prompt' => '所有渠道']); ?>&nbsp;
发起时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
成功时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('updated_at_begin', ''); ?>" name="updated_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('updated_at_end', ''); ?>"  name="updated_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="is_summary" value="1"  <?php if(Yii::$app->getRequest()->get('is_summary', '0')==1):?> checked <?php endif; ?> > 显示汇总(勾选后，查询变慢)&nbsp;&nbsp;&nbsp;
<?php ActiveForm::end(); ?>
<?php if(isset($tip)&&$tip == 1): ?>
    &nbsp;&nbsp;&nbsp;&nbsp;<a style="float: left;" onclick="return confirmMsg('是否一键重新发起扣款')" href="<?php echo Url::toRoute(['financial/batch-revert-debit'])?>" id="batchreview"><button class="btn">一键重新发起扣款</button></a>
<?php endif; ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($msg)):?>
<b><?php echo $msg;?></b>
<?php endif;?>
<?php if (!empty($info)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>资方</th>
            <th>用户ID</th>
            <th>订单ID</th>
            <th>还款表ID</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>实际扣款总金额</th>
            <th>预期扣款总金额</th>
            <th>预期扣款本金</th>
            <th>预期扣款利息</th>
            <th>预期扣款滞纳金</th>
            <th>扣款管理员</th>
            <th>业务类型</th>
            <th>扣款渠道</th>
            <th>扣款时间</th>
            <th>申请时间</th>
            <th>状态</th>
            <th>通知业务方结果</th>
            <th style="width: 150px; text-align: center;">操作</th>
        </tr>
        <?php
        $LoanFund = LoanFund::getAllFundArray();

        foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value->id; ?></td>
                <td><?php echo  (!empty($value->userLoanOrder) && !empty($LoanFund[$value->userLoanOrder->fund_id])) ? $LoanFund[$value->userLoanOrder->fund_id] : $fund_koudai->name;?></td>
                <td><?php echo $value->user_id; ?></td>
                <td><?php echo $value->loan_record_id; ?></td>
                <td><?php echo $value->repayment_id; ?></td>
                <td><?php echo empty( $value->loanPerson) ? "---" : $value->loanPerson->name; ?></td>
                <td class="click-phone" data-phoneraw="<?php echo empty( $value->loanPerson) ? "---" : $value->loanPerson->phone; ?>">--</td>
                <td><?php echo $value->true_repayment_money?sprintf('%.2f', $value->true_repayment_money / 100):0; ?></td>
                <td><?php echo $value->plan_repayment_money?sprintf('%.2f', $value->plan_repayment_money / 100):0; ?></td>
                <td><?php echo $value->plan_repayment_principal?sprintf('%.2f', $value->plan_repayment_principal / 100):0; ?></td>
                <td><?php echo $value->plan_repayment_interest?sprintf('%.2f', $value->plan_repayment_interest / 100):0; ?></td>
                <td><?php echo $value->plan_repayment_late_fee?sprintf('%.2f', $value->plan_repayment_late_fee / 100):0; ?></td>
                <td><?php echo $value->admin_username ? $value->admin_username : '---'; ?></td>
                <td>
                <?php
                    echo isset(FinancialDebitRecord::$types[$value->type]) ? FinancialDebitRecord::$types[$value->type] : "---";
                ?>
                </td>
                <td><?php echo empty($value->platform) ? "---" : BankConfig::$platform[$value->platform]; ?></td>
                <td><?php echo $value->true_repayment_time ? date('Y-m-d H:i:s', $value->true_repayment_time) : '---'; ?></td>
                <td><?php echo date('Y-m-d H:i', $value->created_at); ?></td>
                <td><?php echo !isset(FinancialDebitRecord::$status[$value->status]) ?  "未知" : FinancialDebitRecord::$status[$value->status]; ?></td>
                <td ><?php
                    if (!empty($value->callback_result)) {
                        $arr = json_decode($value->callback_result, true);
                        echo $arr['code'] === 0 ? "已通知" : "失败{$arr['message']}" ;
                    }else {
                        echo "---";
                    }

                    ?></td>
                <td >
                    <?php if (($value['status'] == FinancialDebitRecord::STATUS_PAYING)): ?>

                        <a href="<?php echo Url::toRoute(['financial/add-debit', 'id' => $value->id, 'user_id' => $value->user_id]); ?>">扣款</a>

                        <a href="<?php echo Url::toRoute(['financial/debit-refuse', 'id' => $value->id]); ?>">驳回</a>

                        <a href="<?php echo Url::toRoute(['financial/partial-payment', 'id' => $value->id, 'user_id' => $value->user_id]); ?>">生成扣款记录</a>

                    <?php endif; ?>

                    <a href="<?php echo Url::toRoute(['financial/debit-detail', 'id' => $value->id]); ?>">详情</a>
                    <a href="#" onclick="setRemarkTwo('<?php echo $value->id; ?>')">备注</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
    <?php if(isset($dataSt) && !empty($dataSt)): ?>
			<table frame="above" align="right">
		        <tr>
		            <td align="center" style="color: red;">实际扣款金额总计：</td>
                    <td align="right" style="color: red;"><?php echo sprintf("%.2f",($dataSt['true_repayment_money']) / 100) ?></td>
		        </tr>
		    </table>
    <?php endif; ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>
<?php $form = ActiveForm::begin(['id' => 'set_remark_two', 'method' => "post" ]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="remark_two">
<?php ActiveForm::end(); ?>
<script>
    function setRemarkTwo(id){
    	var remark_two = prompt("请输入备注","");
    	if(remark_two == ""){
    		alert("未输入备注");
       	}else if(remark_two == null){
			return false;
        }else{
        	var myform = document.forms["set_remark_two"];
        	myform["remark_two"].value = remark_two;
        	myform["id"].value = id;
    		myform.submit();
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
