<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use common\models\Shop;
use backend\components\widgets\ActiveForm;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\helpers\Url;
use common\models\LoanContract;
?>
<div class="itemtitle"><h3>编辑合同信息</h3></div>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style type="text/css">
    .tb2 .txt, .tb2 .txtnobd {
        width: 150px;
        margin-right: 10px;
    }
    .td27{ width: 100px;}
    tr{ height: 30px;}
</style>
<?php $form = ActiveForm::begin(['id' => 'loan-form']); ?>
<table style="width: 650px;">
    <tr>
        <td class="td27" colspan="2">合同模板:</td>
        <td style="width: 300px;">
            <?php echo $form->field($model, 'type')->dropDownList(LoanContract::$contract_template_list, ['prompt' => '请选择借款类型']); ?>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">产品型号:</td>
        <td style="width: 300px;">
            <input type="text" name="product_model"/>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">服务费:</td>
        <td style="width: 300px;">
            <input type="text" name="service_charge" value="0"/>
            <span>%</span>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">期数:</td>
        <td style="width: 300px;">
            <span><?php echo $info['period'];?></span>
            <span>个月</span>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">单期还款金额:</td>
        <td style="width: 300px;">
            <input type="text" name="single_repay_money" value="<?php echo $info['single_repay_money'];?>"/>
            <span>元</span>
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">首期还款日:</td>
        <td style="width:300px;"><input style="width:160px" type="text" name="first_repay_date" onfocus="aaabb()"/></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">发货地址:</td>
        <td style="width: 300px;"><textarea style="width:300px" name="ship_address"></textarea></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">
            <input type="hidden" name="remark" value="<?php echo $remark;?>"/>
        </td>
        <td >
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>
    function aaabb(){
        WdatePicker({
            startDate:'%y/%M/%d %H:%m:00',
            dateFmt:'yyyy-MM-dd HH:mm:00',
            alwaysUseStartDate:true,
            readOnly:true});
    }
</script>
