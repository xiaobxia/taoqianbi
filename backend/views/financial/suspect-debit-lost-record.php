<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\DeductMoneyLog;
use common\models\FinancialDebitRecord;
use common\models\SuspectDebitLostRecord;
use common\models\BankConfig;
$this->shownav('financial', 'menu_debit_debitlog_list');
$this->showsubmenu('自动扣款待观察列表');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
银行流水号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_uuid', ''); ?>" name="order_uuid" class="txt" style="width:120px;">&nbsp;
第三方支付号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" style="width:120px;">&nbsp;
标记状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), \common\models\SuspectDebitLostRecord::$STATUS_ARR, ['prompt' => '所有状态']); ?>&nbsp;
标记类型：<?php echo Html::dropDownList('mark_type', Yii::$app->getRequest()->get('mark_type', ''), \common\models\SuspectDebitLostRecord::$MARK_TYPE_ARR, ['prompt' => '所有类型']); ?>&nbsp;
<br/>
代扣类型：<?php echo Html::dropDownList('debit_type', Yii::$app->getRequest()->get('debit_type', ''), \common\models\SuspectDebitLostRecord::$DEBIT_TYPE_ARR, ['prompt' => '所有类型']); ?>&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
更新时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('update_begintime', ''); ?>" name="update_begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('update_endtime', ''); ?>"  name="update_endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
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
            <th>银行流水号</th>
            <th>第三方支付号</th>
            <th>通道</th>
            <th>扣款金额</th>
            <th>状态</th>
            <th>标记类型</th>
            <th>业务类型</th>
            <th>备注</th>
            <th>操作人</th>
            <th>更新时间</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td> <?php echo $value -> id; ?></td>
                <td> <?php echo $value -> user_id; ?></td>
                <td> <?php echo $value -> order_id; ?></td>
                <td> <?php echo $value -> order_uuid; ?></td>
                <td> <?php echo $value -> pay_order_id; ?></td>
                <td> <?php echo isset(BankConfig::$platform[$value -> platform])?BankConfig::$platform[$value -> platform]:'代扣';?></td>
                <td> <?php echo sprintf("%0.2f", ($value->money) / 100); ?></td>
                <td> <?php echo SuspectDebitLostRecord::$STATUS_ARR[$value->status]?></td>
                <td> <?php echo SuspectDebitLostRecord::$MARK_TYPE_ARR[$value->mark_type]?></td>
                <td> <?php echo SuspectDebitLostRecord::$DEBIT_TYPE_ARR[$value->debit_type]?></td>
                <td> <?php echo $value -> remark?></td>
                <td> <?php echo $value -> operator?></td>
                <td> <?php echo date('Y-m-d H:i:s',$value -> updated_at); ?></td>
                <td> <?php echo date('Y-m-d H:i:s',$value -> created_at); ?></td>
                <td>
                    <?php if ($value->status == SuspectDebitLostRecord::STATUS_DEFAULT) {?>
                        <a href="#" onclick="setRemark(<?php echo $value['id']?>)">置为失败</a>
                    <?php }?>
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
<?php $form = ActiveForm::begin(['id' => 'set_remark', 'method' => "post",'action'=>Url::toRoute(['financial/force-debit-record-failed'])]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="remark">
<?php ActiveForm::end(); ?>
<script>
    function setRemark(id){
        var remark = prompt("请输入备注","");
        if(remark == ""){
            alert("未输入备注");
        }else if(remark == null){
            return false;
        }else{
            var myform = document.forms["set_remark"];
            myform["remark"].value = remark;
            myform["id"].value = id;
            myform.submit();
        }
    }
</script>
