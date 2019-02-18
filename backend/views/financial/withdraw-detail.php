<?php
use yii\helpers\Html;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\FinancialLoanRecord;
use common\models\asset\AssetOrder;
use common\helpers\StringHelper;

$this->shownav('financial', 'menu_loan_list');
$this->showsubmenu('打款详情');
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    .control-label{display: none;}
    .red{color:red}
</style>
<table class="tb tb2 fixpadding">
    <?php $form = ActiveForm::begin(['id' => 'review-form']); ?>

    <tr>
        <th class="partition" colspan="15">借款用户信息</th>
    </tr>
    <tr>
        <td class="td24">用户ID：</td>
        <td width="300"><?php echo $message['user']['id']; ?></td>
        <td class="td24">用户手机：</td>
        <td><?php echo $message['user']['phone']; ?></td>
    </tr>
    <tr>
        <td class="td24">用户名：</td>
        <td width="300"><?php echo $message['user']['name']; ?></td>
        <td class="td24">生日：</td>
        <td><?php echo date("Y-m-d", $message['user']['birthday']) ?></td>
    </tr>
    <tr>
        <th class="partition" colspan="15">打款信息</th>
    </tr>
    <tr>
        <td class="td24">业务订单号：</td>
        <td>
            <?php if(in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)){ ?>
            <a href="<?php echo Url::toRoute(['asset/orders-detail', 'id' => $withdraw['business_id']]);?>">
            <?php echo $withdraw['business_id'].'(点击查看)'; ?>
            </a>
            <?php }else{?>
            <?php echo $withdraw['business_id']; ?>
            <?php }?>
        </td>
        <td class="td24">借款来源</td>
        <td><?php echo empty($withdraw['type']) ? "---" : FinancialLoanRecord::$types[$withdraw['type']] ?></td>
    </tr>
    <tr>
        <td class="td24">打款金额：</td>
        <td class="red"><?php echo $withdraw['money'] / 100;?>元</td>
        <td class="td24">打款手续费：</td>
        <td class="red"><?php echo $withdraw['counter_fee'] / 100; ?>元</td>
    </tr>
    <tr>
        <td class="td24">实际打款金额</td>
        <td class="red"><?php echo ($withdraw['money'] - $withdraw['counter_fee'])/ 100;?>元</td>
        <td class="td24">申请时间：</td>
        <td><?php echo date('Y-m-d H:i:s',$withdraw['created_at']); ?></td>
    </tr>
    <tr>
        <td class="td24">订单ID</td>
        <td><?php echo $withdraw['order_id']; ?></td>
        <td class="td24">打款状态</td>
        <td><?php echo !empty($withdraw['status']) ? FinancialLoanRecord::$ump_pay_status[$withdraw['status']] : '无效状态'; ?></td>
    </tr>
    <tr>
        <td class="td24">打款摘要：</td>
        <td><?php echo $withdraw['pay_summary'] ?></td>
        <td class="td24">申请打款渠道类型：</td>
        <td>
            <?php
            echo empty($withdraw['payment_type']) ? "---" : FinancialLoanRecord::$payment_types[$withdraw['payment_type']];
            ?>
        </td>
    </tr>
    <tr>
        <th class="partition" colspan="15">打款银行卡信息</th>
    </tr>
    <tr>
        <td class="td24">打款银行卡号：</td>
        <td width="300"><?php echo $withdraw['card_no'] ?></td>
        <td class="td24">打款银行名称：</td>
        <td><?php echo $withdraw['bank_name'] ?></td>
    </tr>
    <tr>
        <td class="td24">持卡人姓名：</td>
        <td width="300"><?php echo $message['user_bank']['name'] ?></td>
        <td class="td24">开户行地址：</td>
        <td><?php echo $message['user_bank']['bank_address'] ?></td>
    </tr>

    <tr>
        <th class="partition" colspan="15">审核</th>
    </tr>

    <tr>
        <td class="td24">审核人：</td>
        <td><?php echo $withdraw['review_username'] ?></td>
        <td class="td24">审核时间：</td>
        <td><?php echo empty($withdraw['review_time']) ? "---" : date("Y-m-d H:i:s", $withdraw['review_time']) ?></td>
    </tr>

    <?php if(($withdraw['review_result'] != FinancialLoanRecord::REVIEW_STATUS_NO) && ($withdraw['review_result']) != FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED): ?>
        <tr>
            <td class="td24">审核结果：</td>
            <td>
                <?php echo FinancialLoanRecord::$review_status[$withdraw['review_result']]; ?>
            </td>
        </tr>
        <tr>
            <td class="td24">审核备注：</td>
            <td>
                <?php echo !empty($withdraw['review_remark']) ? $withdraw['review_remark'] : ''; ?>
            </td>
        </tr>
    <?php endif; ?>
    <!-- 暂定为审核过了就不能再审核了 -->
    <?php if ($withdraw['review_result'] == FinancialLoanRecord::REVIEW_STATUS_NO or (!empty($withdraw['notify_result']) and json_decode($withdraw['notify_result'], true)['ret_code'] == FinancialLoanRecord::UMP_PAY_FAILED)): ?>
        <tr>
            <td class="td24">审核结果：</td>
            <td><?php echo $form->field($withdraw, 'review_result')->radioList([
                    FinancialLoanRecord::REVIEW_STATUS_APPROVE => FinancialLoanRecord::$review_status[FinancialLoanRecord::REVIEW_STATUS_APPROVE],
                    FinancialLoanRecord::REVIEW_STATUS_REJECT => FinancialLoanRecord::$review_status[FinancialLoanRecord::REVIEW_STATUS_REJECT],
                ]); ?>
            </td>
        </tr>
        <tr>
            <td class="td24">打款类型：</td>
            <td colspan="15">
                <?php //$withdraw->payment_type = FinancialLoanRecord::PAYMENT_TYPE_CMB; ?>
                <?php echo $form->field($withdraw, 'payment_type')->dropDownList(FinancialLoanRecord::$payment_types); ?>
            </td>
        </tr>

        <tr>
            <td class="td24">审核备注：</td>
            <td>

                <textarea id="financialloanrecord-review_remark" class="form-control" rows="10" cols="30" name="FinancialLoanRecord[review_remark]"><?php echo $withdraw['review_remark']; ?></textarea>

            </td>
            <td class="td24"></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    <?php endif;?>

    <?php if ($withdraw['review_result'] == FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED): ?>
        <tr>
            <td class="td24">操作</td>
            <?php if(!in_array(Yii::$app->user->identity->username, ["railszhu","qianwang","gutingting","cheyanbing","zhuxiaoyu","zhougan"])): ?>
            <td colspan="15">
                <?php echo Html::radioList('operation', FinancialLoanRecord::CMB_FAILED_PAYING, [FinancialLoanRecord::CMB_FAILED_PAYING => "直连已打款",]); ?>
            </td>
            <?php endif;?>
            <?php if(in_array(Yii::$app->user->identity->username, ["railszhu","qianwang","gutingting","cheyanbing","zhuxiaoyu","zhougan"])): ?>
            <td colspan="15">
                <?php echo Html::radioList('operation', FinancialLoanRecord::CMB_FAILED_PAYING, FinancialLoanRecord::$cmb_failed_list); ?>
            </td>
            <?php endif;?>
        <tr>
            <td class="td24">审核备注：</td>
            <td><?php echo $form->field($withdraw, 'review_remark')->textarea(); ?></td>
            <td class="td24"></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    <?php elseif (($withdraw['review_result'] == FinancialLoanRecord::REVIEW_STATUS_APPROVE || $withdraw['review_result'] == FinancialLoanRecord::REVIEW_STATUS_REJECT) && Yii::$app->user->identity->role=='superadmin' && $withdraw['status'] != FinancialLoanRecord::UMP_PAY_SUCCESS):?>
        <tr><th class="partition" colspan="15">
                <?php if(time()-$withdraw['updated_at']>40*60): ?>
                    <a href="<?php echo Url::toRoute(['financial/reset-status', 'id' => $withdraw['id']])?>" class="btn">重置状态</a>
                <?php endif; ?>
                <?php if(time()-$withdraw['updated_at']>40*60 && time()-$withdraw['created_at']>40*60): ?>
                    <a style="margin-left:30px;" href="javascript:void(0);" onclick="cancelorder();" class="btn">驳回订单</a>
                <?php endif; ?>
            </th></tr>
    <?php endif;?>
    <?php ActiveForm::end(); ?>

    <?php if (!empty($withdraw['result'])): ?>
    <tr><th class="partition" colspan="15">申请提现返回结果</th></tr>
        <tr>
            <td class="td24">提现额度：</td>
            <td></td>
            <td class="td24">订单日期：</td>
            <td></td>
        </tr><tr>
            <td class="td24">提现订单ID：</td>
            <td></td>
            <td class="td24">联动交易ID：</td>
            <td></td>
        </tr><tr>
            <td class="td24">返回码：</td>
            <td></td>
            <td class="td24">返回消息：</td>
            <td></td>
        </tr><tr>
            <td class="td24">交易状态：</td>
            <td></td>
            <td class="td24"></td>
            <td></td>
    </tr>
    <?php endif;?>

    <?php if (!empty($notify_result)): ?>
    <tr><th class="partition" colspan="15">异步通知结果</th></tr>
    <tr>
        <td class="td24">提现额度：</td>
        <td><?php echo $notify_result['amount'] / 100; ?></td>
        <td class="td24">订单日期：</td>
        <td><?php echo $notify_result['mer_date']; ?></td>
    </tr><tr>
        <td class="td24">提现订单ID：</td>
        <td><?php echo $notify_result['order_id']; ?></td>
        <td class="td24">联动交易ID：</td>
        <td><?php echo $notify_result['trade_no']; ?></td>
    </tr><tr>
        <td class="td24">返回码：</td>
        <td><?php echo $notify_result['ret_code']; ?></td>
        <td class="td24">返回消息：</td>
        <td><?php echo $notify_result['ret_msg']; ?></td>
    </tr><tr>
        <td class="td24">交易状态：</td>
        <td><?php echo $notify_result['trade_state']; ?></td>
        <td class="td24">通知时间：</td>
        <td><?php echo date('Y-m-d H:i:s', $notify_result['notify_time']); ?></td>
    </tr>
    <?php endif;?>
    <tr><th class="partition" colspan="15">打款成功重新通知业务方</th></tr>

    <tr><th class="partition" colspan="15"><a href="<?php echo Url::toRoute(['financial/loan-notice', 'id' => $withdraw['id']])?>" class="btn">打款成功重新通知业务方</a></th></tr>
</table>
<?php
if(in_array($withdraw['type'], FinancialLoanRecord::$other_platform_type)){
    echo $this->render('/asset/_operator_log_list',['table_name'=>AssetOrder::tableName(),'table_id'=>$withdraw['business_id']]);
}
?>
<script>
    function cancelorder() {
        var url="<?php echo Url::toRoute(['financial/cancel-order', 'id' => $withdraw['id']])?>";
        if(confirm('请确认该款项没有打款成功，否则会造成资金流失，是否继续？')){
            location.href=url;
        }
    }
</script>
