<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/6/23
 * Time: 10:08
 */
use common\models\UserCreditReviewLog;
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<style>
    .td24{
        width: 200px;
        font-weight: bold;
    }
</style>
<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">用户额度审核详情页</th></tr>
    <tr>
        <th class="td24">ID：</th>
        <td width="200px"><?php echo $list['id']; ?></td>
        <th class="td24">用户ID：</th>
        <td ><?php echo $loan_person['id']; ?></td>
    </tr>
    <tr>
        <th class="td24">姓名：</th>
        <td ><?php echo $loan_person['name']; ?></td>
        <th class="td24">手机号：</th>
        <td ><?php echo $loan_person['phone']; ?></td>
    </tr>
    <?php if($list['type'] == UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT) : ?>
    <tr>
        <th class="td24">总额度修改前额度(元)：</th>
        <td width="200px"><?php echo sprintf("%.2f",$list['before_number'] / 100); ?></td>
        <th class="td24">操作金额(元)：</th>
        <td style="color: red;font-weight: bold" width="200px"><?php echo sprintf("%.2f",$list['operate_number'] / 100); ?></td>
        <th class="td24">总额度修改后额度(元)：</th>
        <td ><?php echo sprintf("%.2f",$list['after_number'] / 100); ?></td>
    </tr>
    <?php elseif($list['type'] == UserCreditReviewLog::TYPE_POCKET_APR || $list['type'] == UserCreditReviewLog::TYPE_POCKET_REGISTER_APR) : ?>
    <tr>
        <th class="td24">零钱贷修改前利率(万分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['before_number']); ?></td>
        <th class="td24">操作利率(万分之)：</th>
        <td style="color: red;font-weight: bold" width="200px"><?php echo sprintf("%.2f",$list['operate_number']); ?></td>
        <th class="td24">零钱贷修改后利率(万分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['after_number']); ?></td>
    </tr>
    <?php elseif($list['type'] == UserCreditReviewLog::TYPE_INSTALLMENT_APR || $list['type'] == UserCreditReviewLog::TYPE_INSTALLMENT_APR) : ?>
    <tr>
        <th class="td24">分期商城修改前利率(百分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['before_number']); ?></td>
        <th class="td24">操作利率(百分之)：</th>
        <td style="color: red;font-weight: bold" width="200px"><?php echo sprintf("%.2f",$list['operate_number']); ?></td>
        <th class="td24">分期商城修改后利率(百分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['after_number'])  ; ?></td>
    </tr>
    <?php elseif($list['type'] == UserCreditReviewLog::TYPE_HOUSE_APR || $list['type'] == UserCreditReviewLog::TYPE_HOUSE_REGISTER_APR) : ?>
    <tr>
        <th class="td24">房租贷修改前利率(百分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['before_number']); ?></td>
        <th class="td24">操作利率(百分之)：</th>
        <td style="color: red;font-weight: bold" width="200px"><?php echo sprintf("%.2f",$list['operate_number']); ?></td>
        <th class="td24">房租贷修改后利率(百分之)：</th>
        <td ><?php echo sprintf("%.2f",$list['after_number'])  ; ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <th class="td24">创建人：</th>
        <td ><?php echo $list['creater_name']; ?></td>
        <th class="td24">状态：</th>
        <td ><?php echo isset($list['status']) ? UserCreditReviewLog::$status[$list['status']] : "--"; ?></td>
    </tr>
    <tr>
        <th class="td24">创建时间：</th>
        <td><?php echo date("Y-m-d H:i:s",$list['created_at']); ?></td>
        <th class="td24">审核人：</th>
        <td ><?php echo empty($list['operator_name']) ? "--" : $list['operator_name']; ?></td>
        <th class="td24">备注：</th>
        <td ><?php echo empty($list['remark']) ? "--" : $list['remark']; ?></td>
    </tr>
</table>