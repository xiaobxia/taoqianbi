<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\LoanProject;

?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款项目信息</th></tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'loan_project_name'); ?></td>
        <td width="300"><?php echo $loan_project['loan_project_name']; ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'id'); ?></td>
        <td ><?php echo $loan_project['id']; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'type'); ?></td>
        <td ><?php echo LoanProject::$type_list[$loan_project['type']]; ?></td>
        <td class="td24">金额范围（元）：</td>
        <td><?php echo sprintf('%d - %d', $loan_project['amount_min'] / 100, $loan_project['amount_max'] / 100); ?></td>
    </tr>
    <tr>
        <td class="td24">期限范围（月）</td>
        <td><?php echo sprintf('%d - %d', $loan_project['period_min'], $loan_project['period_max']); ?></td>
        <td class="td24">年龄范围（岁）</td>
        <td><?php echo sprintf('%d - %d', $loan_project['age_min'], $loan_project['age_max']); ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'created_at'); ?></td>
        <td><?php echo date('Y-m-d H:i:s', $loan_project['created_at']); ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'updated_at'); ?></td>
        <td><?php echo date('Y-m-d H:i:s', $loan_project['updated_at']); ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'region'); ?></td>
        <td colspan="3"><?php echo $loan_project['region']; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'description'); ?></td>
        <td colspan="3"><?php echo $loan_project['description']; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'rule_description'); ?></td>
        <td colspan="3"><?php echo $loan_project['rule_description']; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'success_number'); ?></td>
        <td colspan="3"><?php echo $loan_project['success_number']."人"; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_project, 'show_img_url'); ?></td>
        <td colspan="3"><img src="<?php echo $loan_project['show_img_url']; ?>" width="50" /></td>
    </tr>
</table>
