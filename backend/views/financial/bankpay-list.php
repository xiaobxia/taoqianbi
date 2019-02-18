<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\UserCreditMoneyLog;
use common\models\fund\LoanFund;
use common\models\BankConfig;

$this->shownav('financial', 'menu_debit_bankpay_list');
$this->showsubmenu('用户还款流水列表');

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
资方：<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), [0=>'全部']+LoanFund::getAllFundArray()); ?>&nbsp;

ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_name', ''); ?>" name="user_name" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
银行流水号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_uuid', ''); ?>" name="order_uuid" class="txt" style="width:120px;">&nbsp;
流水订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" style="width:120px;">&nbsp;
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserCreditMoneyLog::$status, ['prompt' => '所有状态']); ?>&nbsp;
还款方式：<?php echo Html::dropDownList('payment_type', Yii::$app->getRequest()->get('payment_type', ''), UserCreditMoneyLog::$payment_type, ['prompt' => '所有状态']); ?>&nbsp;
<br/>
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
成功时间<input type="text" value="<?php echo Yii::$app->getRequest()->get('success_begin_time', ''); ?>" name="success_begin_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('success_end_time', ''); ?>"  name="success_end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), UserCreditMoneyLog::$type, ['prompt' => '所有状态']); ?>&nbsp;
通道：<?php echo Html::dropDownList('debit_channel', Yii::$app->getRequest()->get('debit_channel', ''), UserCreditMoneyLog::$third_platform_name, ['prompt' => '所有']); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn"/>
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn"/>
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($info)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>资方</th>
            <th>用户ID</th>
            <th>订单ID</th>
            <th>银行流水号</th>
            <th>流水订单ID</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>金额</th>
            <th>本金</th>
            <th>利息</th>
            <th>滞纳金</th>
            <th>溢出金</th>
            <th>状态</th>
            <th>通道</th>
            <th>还款方式</th>
            <th>创建时间</th>
            <th>还款成功时间</th>
            <th>操作人</th>
            <th>备注</th>
            <th>操作</th>
        </tr>
        <?php
       $fund  =  LoanFund::getAllFundArray();
        foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value->id; ?></td>
                <td><?php echo !empty($value->userLoanOrder['fund_id'])?$fund[$value->userLoanOrder['fund_id']]:"口袋理财" ;?></td>
                <td><?php echo $value->user_id; ?></td>
                <td><?php echo $value->order_id; ?></td>
                <td><?php echo $value->order_uuid; ?></td>
                <td><?php echo $value->pay_order_id; ?></td>
                <td><?php echo $value->loanPerson['name']?></td>
                <td class="click-phone" data-phoneraw="<?php echo $value->loanPerson['phone']?>">--</td>
                <td><?php echo sprintf('%.2f', $value->operator_money / 100); ?></td>
				<td><?php echo sprintf('%.2f', $value->operator_principal / 100)?></td>
				<td><?php echo sprintf('%.2f', $value->operator_interests / 100)?></td>
				<td><?php echo sprintf('%.2f', $value->operator_late_fee / 100)?></td>
				<td><?php echo sprintf('%.2f', $value->operator_overflow / 100)?></td>
                <td>
                <?php
                    echo isset(UserCreditMoneyLog::$status[$value->status]) ? UserCreditMoneyLog::$status[$value->status] : "---";
                ?>
                </td>
                <td>
                <?php
					if($value->debit_channel){
						echo isset(UserCreditMoneyLog::$third_platform_name[$value->debit_channel]) ? UserCreditMoneyLog::$third_platform_name[$value->debit_channel] : "---";
					}else{
						echo isset(UserCreditMoneyLog::$type[$value->type]) ? UserCreditMoneyLog::$type[$value->type] : "---";
					}
                ?>
                </td>
                <td>
                <?php
                    echo isset(UserCreditMoneyLog::$payment_type[$value->payment_type]) ? UserCreditMoneyLog::$payment_type[$value->payment_type] : "---";
                ?>
                </td>
                <td><?php echo date('Y-m-d H:i:s',$value->created_at); ?></td>
                <td>
                <?php if(!empty($value->success_repayment_time)):?>
                    <?php echo date('Y-m-d H:i:s',$value->success_repayment_time); ?>
                <?php else:?>
                    --
                <?php endif;?>
                </td>
                <td><?php echo $value->operator_name; ?></td>
                <td><?php echo $value->remark; ?></td>
                <td>
                    <?php if($value->status==UserCreditMoneyLog::STATUS_SUCCESS): ?>
                        <a onclick="confirmRedirect('确定要作废吗？', '<?php echo Url::toRoute(['financial/cancel-credit-money-log', 'id' => $value->id]);?>')" href="javascript:;">作废</a>
                    <?php endif;?>
                    <a href="<?php echo Url::toRoute(['bankpay-edit', 'id' => $value->id]);?>">编辑</a>
                </td>

            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>
<?php if(isset($dataSt) && !empty($dataSt)): ?>
    <table frame="above" align="right">
        <tr>
            <td align="center" style="color: red;">金额总计：</td>
        </tr>
        <tr>
            <td style="color: red;"><?php echo sprintf("%.2f",$dataSt['operator_money'] / 100) ?></td>
        </tr>
    </table>
<?php endif; ?>
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
