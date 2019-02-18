<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\LoanProject;
use common\helpers\Url;

?>
<style>
    td.label {
        width: 96px;
        text-align: right;
        font-weight: 700;
    }
</style>
<?php $form = ActiveForm::begin(['id' => 'loan-project-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款项目信息</th></tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'loan_project_name'); ?></td>
        <td ><?php echo $form->field($loan_project, 'loan_project_name')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'type'); ?></td>
        <td ><?php echo $form->field($loan_project, 'type')->dropDownList(LoanProject::$type_list); ?></td>
    </tr>
    <tr>
        <?php $loan_project['amount_min'] = StringHelper::safeConvertIntToCent($loan_project['amount_min'])?>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'amount_min'); ?></td>
        <td ><?php echo $form->field($loan_project, 'amount_min')->textInput(); ?></td>
    </tr>
    <tr>
        <?php $loan_project['amount_max'] = StringHelper::safeConvertIntToCent($loan_project['amount_max'])?>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'amount_max'); ?></td>
        <td ><?php echo $form->field($loan_project, 'amount_max')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'period_min'); ?></td>
        <td ><?php echo $form->field($loan_project, 'period_min')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'period_max'); ?></td>
        <td ><?php echo $form->field($loan_project, 'period_max')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'age_min'); ?></td>
        <td ><?php echo $form->field($loan_project, 'age_min')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'age_max'); ?></td>
        <td ><?php echo $form->field($loan_project, 'age_max')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'region'); ?></td>
        <td ><?php echo $form->field($loan_project, 'region')->textarea(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'description'); ?></td>
        <td ><?php echo $form->field($loan_project, 'description')->textarea(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'rule_description'); ?></td>
        <td ><?php echo $form->field($loan_project, 'rule_description')->textarea(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'success_number'); ?></td>
        <td ><?php echo $form->field($loan_project, 'success_number')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'mark'); ?></td>
        <td >
            <?php echo $form->field($loan_project, 'mark')->textInput(); ?>
            可为空，例：电动车分期标记为 ‘bike’ ，新增特殊项目时，需修改 LoanProject.php。
        </td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'show_img_url'); ?></td>
        <td >
            <?php echo $form->field($loan_project, 'show_img_url')->textarea(); ?>
            <a style="color:#7f63fe;font-weight:bold;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件</a>&nbsp;&nbsp;
            如输入图片链接：（http://res.kdqugou.com/article/20160119/3569da9823f657.png）将在App项目列表页显示出图标：<img src="http://res.kdqugou.com/article/20160119/3569da9823f657.png" width="30" />
        </td>
    </tr>
    <tr>
        <td class="label"><?php echo $this->activeLabel($loan_project, 'status'); ?></td>
        <td ><?php echo $form->field($loan_project, 'status')->radioList(LoanProject::$status);?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
