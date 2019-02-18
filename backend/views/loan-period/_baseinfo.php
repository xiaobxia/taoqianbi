<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">基本信息</th></tr>
    <tr>
        <td class="td24">ID：</td>
        <td width="300"><?php echo $loan_record_period->id; ?></td>
        <td class="td24">产品类型名称：</td>
        <td><?php echo $loan_record_period->product_type_name; ?></td>
    </tr>
    <tr>
        <td class="td24">借款状态：</td>
        <td width="300"><?php echo LoanRecordPeriod::$status[$loan_record_period->status]; ?></td>
        <td class="td24">借款时间：</td>
        <td><?php echo empty($loan_record_period->apply_time) ? "--" : date("Y-m-d H:i:s", $loan_record_period->apply_time); ?></td>
    </tr>
    <tr>
        <td class="td24">借款类型：</td>
        <td width="300"><?php echo LoanProject::$type_list[$loan_record_period->type]; ?></td>
        <td class="td24">借款期限：</td>
        <td><?php echo $loan_record_period->period; ?></td>
    </tr>
    <tr>
        <td class="td24">借款金额：</td>
        <td width="300"><?php echo sprintf("%.2f", $loan_record_period->amount / 100); ?></td>
        <td class="td24">放款金额：</td>
        <td><?php echo  sprintf("%.2f", $loan_record_period->credit_amount / 100); ?></td>
    </tr>
    <tr>
        <td class="td24">初审申请成功时间：</td>
        <td width="300"><?php echo empty($loan_record_period->trial_success_time) ? "--" : date("Y-m-d H:i:s", $loan_record_period->trial_success_time); ?></td>
        <td class="td24">复审申请成功时间：</td>
        <td><?php echo empty($loan_record_period->review_success_time) ? "--" :date("Y-m-d H:i:s", $loan_record_period->review_success_time); ?></td>
    </tr>
    <tr>
        <td class="td24">已补充初审资料：</td>
        <td width="300"><?php echo empty($loan_record_period->supply_trial_time) ? "--" :date("Y-m-d H:i:s", $loan_record_period->supply_trial_time); ?></td>
        <td class="td24">已补充复审资料：</td>
        <td><?php echo empty($loan_record_period->supply_review_time) ? "--" :date("Y-m-d H:i:s", $loan_record_period->supply_review_time); ?></td>
    </tr>

    <?php if(!empty($loan_person_info)): ?>
    <tr><th class="partition" colspan="15">个人信息</th></tr>
    <tr>
        <td class="td24">QQ：</td>
        <td width="300"><?php echo $loan_person_info['qq']; ?></td>
        <td class="td24">婚姻状况：</td>
        <td><?php echo $loan_person_info['marital_status']; ?></td>
    </tr>
    <tr>
        <td class="td24">学历：</td>
        <td width="300"><?php echo $loan_person_info['degree']; ?></td>
        <td class="td24">毕业院校：</td>
        <td><?php echo $loan_person_info['school_district_text']; ?></td>
    </tr>
    <tr>
        <td class="td24">就职状况：</td>
        <td><?php echo $loan_person_info['job_status']; ?></td>
    </tr>

        <tr><th class="partition" colspan="15">居住信息</th></tr>
        <tr>
            <td class="td24">现居地区域：</td>
            <td width="300"><?php echo $loan_person_info['present_district_text']; ?></td>
            <td class="td24">现居地址：</td>
            <td><?php echo $loan_person_info['present_address']; ?></td>
        </tr>
        <tr>
            <td class="td24">家庭地址区域：</td>
            <td width="300"><?php echo $loan_person_info['family_district_text']; ?></td>
            <td class="td24">家庭地址：</td>
            <td><?php echo $loan_person_info['family_address']; ?></td>
        </tr>

        <tr><th class="partition" colspan="15">联系人信息</th></tr>
        <tr>
            <td class="td24">第一联系人姓名：</td>
            <td width="300"><?php echo $loan_person_info['first_contact_name']; ?></td>
            <td class="td24">第一联系人手机号：</td>
            <td><?php echo $loan_person_info['first_contact_phone']; ?></td>
        </tr>
        <tr>
            <td class="td24">第二联系人姓名：</td>
            <td width="300"><?php echo $loan_person_info['second_contact_name']; ?></td>
            <td class="td24">第二联系人手机号：</td>
            <td><?php echo $loan_person_info['second_contact_phone']; ?></td>
        </tr>
        <tr>
            <td class="td24">第三联系人姓名：</td>
            <td width="300"><?php echo $loan_person_info['third_contact_name']; ?></td>
            <td class="td24">第三联系人手机号：</td>
            <td><?php echo $loan_person_info['third_contact_phone']; ?></td>
        </tr>
    <?php endif;?>

    <tr><th class="partition" colspan="15">关联表ID信息</th></tr>
    <tr>
        <td class="td24">门店ID：</td>
        <td width="300"><?php echo $loan_record_period->shop_id; ?></td>
        <td class="td24">借款项目ID：</td>
        <td><?php echo $loan_record_period->loan_project_id; ?></td>
    </tr>
    <tr>
        <td class="td24">初审表ID：</td>
        <td width="300"><?php echo $loan_record_period->loan_trial_id; ?></td>
        <td class="td24">复审表ID：</td>
        <td><?php echo $loan_record_period->loan_review_id; ?></td>
    </tr>
    <tr>
        <td class="td24">总审核表ID：</td>
        <td width="300"><?php echo $loan_record_period->loan_audit_id; ?></td>
        <td class="td24">总还款记录表ID：</td>
        <td><?php echo $loan_record_period->loan_repayment_id; ?></td>
    </tr>
    <tr><th class="partition" colspan="15">用户信息</th></tr>
    <tr>
        <td class="td24">用户ID：</td>
        <td width="300"><?php echo $loan_record_period->user_id; ?></td>
        <td class="td24">用户名：</td>
        <td><?php echo !empty($user) ?  $user->username : ""; ?></td>
    </tr>
    <tr>
        <td class="td24">用户身份证号码：</td>
        <td width="300"><?php echo !empty($user) ? $user->id_card : ""; ?></td>
        <td class="td24">真实姓名：</td>
        <td><?php echo  !empty($user) ? $user->realname : ""; ?></td>
    </tr>
    <tr>
        <td class="td24">实名认证状态：</td>
        <td width="300"><?php echo !empty($user)  ? $user->real_verify_status : ""; ?></td>
        <td class="td24">绑定银行卡状态：</td>
        <td><?php echo !empty($user)  ? $user->card_bind_status : ""; ?></td>
    </tr>
    <tr><th class="partition" colspan="15">门店信息</th></tr>
    <tr>
        <td class="td24">店主用户id：</td>
        <td width="300"><?php echo empty($shop) ? "" : $shop->shopkeeper_id; ?></td>
        <td class="td24">店主姓名：</td>
        <td><?php echo empty($shop) ? "" : $shop->shopkeeper_name; ?></td>
    </tr>
    <tr>
        <td class="td24">身份证号：</td>
        <td width="300"><?php echo empty($shop) ? "" : $shop->shopkeeper_card_id; ?></td>
        <td class="td24">店主手机号码：</td>
        <td><?php echo empty($shop) ? "" : $shop->shopkeeper_phone; ?></td>
    </tr>
    <tr>
        <td class="td24">店铺地址：</td>
        <td width="300"><?php echo empty($shop) ? "" : $shop->province.' / '.$shop->city.' / '.$shop->area; ?></td>
        <td class="td24">店铺名称：</td>
        <td><?php echo empty($shop) ? "" : $shop->shop_name; ?></td>
    </tr>
    <tr><th class="partition" colspan="15">其他信息</th></tr>
    <tr>
        <td class="td24">还款类型：</td>
        <td width="300"><?php echo @LoanRecordPeriod::$repay_type[$loan_record_period->repay_type]; ?></td>
        <td class="td24">来源：</td>
        <td><?php echo @LoanRecordPeriod::$source[$loan_record_period->source]; ?></td>
    </tr>
    <tr>
        <td class="td24">借款利率：</td>
        <td width="300"><?php echo $loan_record_period->apr; ?></td>
        <td class="td24">服务费率：</td>
        <td><?php echo $loan_record_period->service_apr; ?></td>
    </tr>
</table>