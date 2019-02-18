<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/14
 * Time: 10:41
 */
use common\services\AreaService;

?>
    <!--借款申请人信息-->
<table  class="tb tb2 fixpadding">
    <tr><th class="partition">借款信息</th></tr>
    <tr>
        <td class="td24">用户名：</td>
        <td width="300"><?php echo $loan_record['user']['username']; ?></td>
        <td class="td24">联系方式：</td>
        <td ><?php echo $loan_record['user']['phone']; ?></td>
    </tr>
    <tr>
        <td class="td24">借款人：</td>
        <td><?php echo $loan_record['user']['realname']; ?></td>
        <td class="td24">身份证：</td>
        <td><?php echo $loan_record['user']['id_card']; ?></td>
    </tr>
    <tr>
        <td class="td24">手机归属地:</td>
        <?php $areaService = new AreaService(); ?>
        <td><?php echo $areaService->getPhoneAdress($loan_record['user']['username']); ?></td>
    </tr>
</table>

    <!--借款信息-->
<?php echo $this->render('_loan-record-information', ['loan_record' => $loan_record]); ?>
