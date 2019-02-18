<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */

use backend\components\widgets\ActiveForm;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\helpers\Url;
?>
<div class="itemtitle"><h3>初审借款信息</h3></div>
<style type="text/css">
    .td27{
        width: 5    0px;
        font-weight: 700;
    }
    tr{ height: 30px;}
</style>
<?php $form = ActiveForm::begin(); ?>
<table style="width: 650px;">
    <tr>
        <td class="td27" colspan="2">借款人ID:</td>
        <td style="width: 300px;">
            <?php echo $model['loan_person_id'];?>
            <input type="hidden" id="loanrecordperiod-loan_person_id" class="txt" value="<?php echo $model['loan_person_id'];?>" name="LoanRecordPeriod[loan_person_id]">
        </td>
    </tr>
    <tr>
        <td class="td27" colspan="2">用户ID:</td>
        <td style="width: 100px;"><?php echo $model['user_id']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">借款类型:</td>
        <td><?php echo LoanProject::$type_building[$model['type']]; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">项目信息:</td>
        <td><?php echo $loan_project_data[$model['loan_project_id']]; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">门店信息:</td>
        <td><?php echo $shop_data[$model['shop_id']]; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">还款类型:</td>
        <td><?php echo LoanRecordPeriod::$repay_type[$model['repay_type']]; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">来源:</td>
        <td><?php echo LoanRecordPeriod::$source[$model['source']]; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">借款金额:</td>
        <td><?php echo $model['amount']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">放款金额:</td>
        <td><?php echo $model['credit_amount']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">借款利率（%）:</td>
        <td><?php echo $model['apr']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">服务费率（%）:</td>
        <td><?php echo $model['service_apr']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">服务费:</td>
        <td><?php echo $model['fee_amount']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">加急费:</td>
        <td><?php echo $model['urgent_amount']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">借款期限（N个月）:</td>
        <td><?php echo $model['period']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">产品类型名称:</td>
        <td><?php echo $model['product_type_name']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">借款日期:</td>
        <td><?php echo $model['apply_time']; ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">状态:</td>
        <td><?php echo $form->field($model, 'status')->dropDownList(LoanRecordPeriod::$xfjr_status_msg, ['prompt' => '请选择状态']); ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2">备注:</td>
        <td><?php echo $form->field($model, 'remark')->textarea(); ?></td>
    </tr>
    <tr>
        <td class="td27" colspan="2"></td>
        <td >
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
