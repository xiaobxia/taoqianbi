<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\DeductMoneyLog;
use common\models\FinancialDebitRecord;
use common\models\BankConfig;
$this->shownav('financial', 'menu_debit_debitlog_list');
$this->showsubmenu('自动扣款日志列表');

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
银行流水号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_uuid', ''); ?>" name="order_uuid" class="txt" style="width:120px;">&nbsp;
第三方支付号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" style="width:120px;">
逾期天数：<input type="text" value="<?php echo Yii::$app->getRequest()->get('overdue_day', ''); ?>" name="overdue_day">
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), \common\models\AutoDebitLog::$status_list, ['prompt' => '所有状态']); ?>&nbsp;
类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), \common\models\AutoDebitLog::$type_list, ['prompt' => '所有类型']); ?>&nbsp;
<br/>
银行：<?php echo Html::dropDownList('bank_id', Yii::$app->getRequest()->get('bank_id', ''), \common\models\CardInfo::$bankInfo, ['prompt' => '所有银行']); ?>&nbsp;
扣款通道：<?php echo Html::dropDownList('platforms', Yii::$app->getRequest()->get('platforms', ''), BankConfig::$platform, ['prompt' => '所有通道']); ?>&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
回调时间<input type="text" value="<?php echo Yii::$app->getRequest()->get('callback_begin_time', ''); ?>" name="callback_begin_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('callback_end_time', ''); ?>"  name="callback_end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
<input type="submit" name="search_submit" value="过滤" class="btn"/>
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn"/>
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($info)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>用户ID</th>
            <th>订单ID</th>
            <th>类型</th>
            <th>银行</th>
            <th>状态</th>
            <th>应还总额</th>
            <th>已还金额</th>
            <th>逾期天数</th>
            <th>银行流水号</th>
            <th>第三方支付号</th>
            <th>扣款金额</th>
            <th>扣款渠道</th>
            <th>回调信息</th>
            <th>创建时间</th>
            <th>回调时间</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value->id; ?></td>
                <td><?php echo $value->user_id; ?></td>
                <td><?php echo $value->order_id; ?></td>
                <td ><?php echo \common\models\AutoDebitLog::$type_list[$value->debit_type]; ?></td>
                <td ><?php echo $value->cardInfo->bank_name??''; ?></td>
                <td><?php echo \common\models\AutoDebitLog::$status_list[$value->status]; ?></td>
                <td><?php echo isset($value->userLoanOrderRepayment)?\common\helpers\StringHelper::safeConvertIntToCent($value->userLoanOrderRepayment->total_money):''; ?></td>
                <td><?php echo isset($value->userLoanOrderRepayment)?\common\helpers\StringHelper::safeConvertIntToCent($value->userLoanOrderRepayment->true_total_money):''; ?></td>
                <td><?php echo isset($value->userLoanOrderRepayment)?$value->userLoanOrderRepayment->overdue_day:''; ?></td>
                <td><?php echo $value->order_uuid; ?></td>
                <td><?php echo $value->pay_order_id; ?></td>
                <td><?php echo sprintf("%0.2f", ($value->money) / 100);?></td>
                <td><?php echo empty($value->platform) ? '--' : BankConfig::$platform[$value->platform]; ?></td>
                <td>
                    <?php if(!empty($value->callback_remark)): ?>
                        <?php
                            $remark = json_decode($value->callback_remark,true);
                            if($remark){
                                if(isset($remark['err_msg'])){
                                    echo $remark['err_msg'];
                                }elseif (isset($remark['data']['err_msg'])){
                                    echo $remark['data']['err_msg'];
                                }elseif (isset($remark['message'])){
                                    echo $remark['message'];
                                }elseif (isset($remark['data']['error_msg'])){
                                    echo $remark['data']['error_msg'];
                                }
                            } else {
                                echo $value->callback_remark;
                            }
                        ?>
                    <?php elseif(!empty($value->remark)):
                        echo $value->remark;
                        ?>
                    <?php endif; ?>
                </td>
                <td><?php echo date('Y-m-d H:i:s',$value->created_at); ?></td>
                <td>
                    <?php if(!empty($value->callback_at)):?>
                        <?php echo date('Y-m-d H:i:s',$value->callback_at); ?>
                    <?php else:?>
                        --
                    <?php endif;?>
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
