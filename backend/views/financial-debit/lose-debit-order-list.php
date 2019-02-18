<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\LoseDebitOrder;
use common\models\UserCreditMoneyLog;
$this->shownav('financial', 'menu_debit_lose_order_list');
$this->showsubmenu('补单列表');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
银行流水号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_uuid', ''); ?>" name="order_uuid" class="txt" style="width:120px;">&nbsp;
第三方支付号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" style="width:120px;">&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
<input type="submit" name="search_submit" value="过滤" class="btn"/>
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn"/>
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($datas)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>用户ID</th>
            <th>订单ID</th>
            <th>银行流水</th>
            <th>第三方支付号</th>
            <th>前状态</th>
            <th>回调状态</th>
            <th>类型</th>
            <th>通道</th>
            <th>处理类型</th>
            <th>备注</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
    <?php foreach ($datas as $item): ?>
        <tr class="hover" style="text-align: center;">
            <td><?php echo $item['id']; ?></td>
            <td><?php echo $item['user_id']; ?></td>
            <td><?php echo $item['order_id']; ?></td>
            <td><?php echo $item['order_uuid']; ?></td>
            <td><?php echo $item['pay_order_id']; ?></td>
            <td><?php echo ($item['type'] == UserCreditMoneyLog::TYPE_DEBIT) ? \common\models\AutoDebitLog::$status_list[$item['pre_status']] : (isset(UserCreditMoneyLog::$status[$item['pre_status']])?UserCreditMoneyLog::$status[$item['pre_status']]:'未知状态'); ?></td>
            <td><?php echo $item['status']; ?></td>
            <td><?php echo UserCreditMoneyLog::$type[$item['type']]??'未知类型'; ?></td>
            <td><?php echo UserCreditMoneyLog::$third_platform_name[$item['debit_channel']]??'未知通道'; ?></td>
            <td><?php echo LoseDebitOrder::$STAFF_TYPE[$item['staff_type']]??'未知状态'; ?></td>
            <td><?php echo $item['remark']; ?></td>
            <td><?php echo date("Y-m-d H:i:s",$item['created_at']); ?></td>
            <td>
                <?php if ($item['staff_type'] != LoseDebitOrder::STAFF_TYPE_1) {?>
                    <a href="#" onclick="setRemark(<?php echo $item['order_id']?>,<?php echo $item['id']?>)">启用</a>
                <?php } elseif ($item['type'] == LoseDebitOrder::TYPE_DEBIT) {?>
                    <a href="#" onclick="setRemark(<?php echo $item['order_id']?>,<?php echo $item['id']?>)">生成还款数据</a>
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
<?php $form = ActiveForm::begin(['id' => 'set_remark', 'method' => "post",'action'=>Url::toRoute(['financial-debit/activate-order'])]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="order_id">
<input type="hidden" value="" name="remark">
<?php ActiveForm::end(); ?>
<script>
    function setRemark(order_id,id){
        var remark = prompt("请输入备注","");
        if(remark == ""){
            alert("未输入备注");
        }else if(remark == null){
            return false;
        }else{
            var myform = document.forms["set_remark"];
            myform["remark"].value = remark;
            myform["id"].value = id;
            myform["order_id"].value = order_id;
            myform.submit();
        }
    }
</script>
