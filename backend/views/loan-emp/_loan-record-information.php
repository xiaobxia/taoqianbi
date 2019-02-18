<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/14
 * Time: 10:30
 */
use common\models\LoanProject;
use common\models\LoanRecord;

?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款申请人信息</th></tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'id'); ?></td>
        <td width="300"><?php echo $loan_record['id']; ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'status'); ?></td>
        <td ><?php echo LoanRecord::$status_list[$loan_record['status']]; ?></td>
    </tr>
    <tr>
        <td class="td24">借款人：</td>
        <td><?php echo $loan_record['user']['realname']; ?></td>
        <td class="td24">借款类型：</td>
        <td><?php echo LoanProject::$type_list[$loan_record['type']]; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'amount'); ?></td>
        <td><?php echo sprintf('%.2f', $loan_record['amount'] / 100); ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'period'); ?></td>
        <td ><?php echo $loan_record['period']; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'created_at'); ?></td>
        <td><?php echo date('Y-m-d H:i:s', $loan_record['created_at']); ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'updated_at'); ?></td>
        <td><?php echo date('Y-m-d H:i:s', $loan_record['updated_at']); ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'contact_time'); ?></td>
        <td><?php echo !empty($loan_record['contact_time']) ? date('Y-m-d H:i:s', $loan_record['contact_time']) : '- - -'; ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'contact_username'); ?></td>
        <td><?php echo !empty($loan_record['contact_username']) ? $loan_record['contact_username'] : '- - -'; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'review_time'); ?></td>
        <td><?php echo !empty($loan_record['review_time']) ? date('Y-m-d H:i:s', $loan_record['review_time']) : '- - -'; ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'review_username'); ?></td>
        <td><?php echo !empty($loan_record['review_username']) ? $loan_record['review_username'] : '- - -'; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'loan_time'); ?></td>
        <td><?php echo !empty($loan_record['loan_time']) ? date('Y-m-d H:i:s', $loan_record['loan_time']) : '- - -'; ?></td>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'loan_username'); ?></td>
        <td><?php echo !empty($loan_record['loan_username']) ? $loan_record['loan_username'] : '- - -'; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'repay_time'); ?></td>
        <td><?php echo !empty($loan_record['repay_time']) ? date('Y-m-d H:i:s', $loan_record['repay_time']) : '- - -'; ?></td>
    </tr>
    <tr>
        <td class="td24"><?php echo $this->activeLabel($loan_record, 'remark'); ?></td>
        <td colspan="3"><?php echo $loan_record['remark']; ?></td>
    </tr>
</table>