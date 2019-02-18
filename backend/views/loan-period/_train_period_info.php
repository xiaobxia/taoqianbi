<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanPersonInfo;
?>
<table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">个人信息</th></tr>
        <tr>
            <td class="td24">QQ：</td>
            <td width="300"><?php echo isset($loan_person_info['qq']) ? $loan_person_info['qq']: '--'; ?></td>
            <td class="td24">婚姻状况：</td>
            <td><?php echo isset($loan_person_info['marital_status']) ? LoanPersonInfo::$marital_status_list[$loan_person_info['marital_status']] : '--'; ?></td>
        </tr>
        <tr>
            <td class="td24">学历：</td>
            <td width="300"><?php echo isset($loan_person_info['degree'])?LoanPersonInfo::$degree_list[$loan_person_info['degree']] : '--'; ?></td>
            <td class="td24">毕业院校：</td>
            <td><?php echo isset($loan_person_info['school_district_text']) ?$loan_person_info['school_district_text']: '--' ; ?></td>
        </tr>
        <tr>
            <td class="td24">就职状况：</td>
            <td><?php echo isset($loan_person_info['job_status']) ? LoanPersonInfo::$identity_list[$loan_person_info['job_status']] : '--'; ?></td>
        </tr>

        <tr><th class="partition" colspan="15">居住信息</th></tr>
        <tr>
            <td class="td24">现居地区域：</td>
            <td width="300"><?php echo isset($loan_person_info['present_district_text'])?$loan_person_info['present_district_text']:'--'; ?></td>
            <td class="td24">现居地址：</td>
            <td><?php echo isset($loan_person_info['present_address'])?$loan_person_info['present_address']:'--'; ?></td>
        </tr>
        <tr>
            <td class="td24">家庭地址区域：</td>
            <td width="300"><?php echo isset($loan_person_info['family_district_text'])?$loan_person_info['family_district_text']:'--'; ?></td>
            <td class="td24">家庭地址：</td>
            <td><?php echo isset($loan_person_info['family_address'])?$loan_person_info['family_address']:'--'; ?></td>
        </tr>

        <tr><th class="partition" colspan="15">联系人信息</th></tr>
        <tr>
            <td class="td24">第一联系人姓名：</td>
            <td width="300"><?php echo isset($loan_person_info['first_contact_name'])?$loan_person_info['first_contact_name']:'--'; ?></td>
            <td class="td24">第一联系人手机号：</td>
            <td><?php echo isset($loan_person_info['first_contact_phone'])?$loan_person_info['first_contact_phone']:'--'; ?></td>
        </tr>
    <tr>
        <td class="td24">与第一联系人的关系：</td>
        <td width="300"><?php echo isset($loan_person_info['first_contact_relation'])?LoanPersonInfo::$contact_list[$loan_person_info['first_contact_relation']]:'--'; ?></td>
    </tr>
        <tr>
            <td class="td24">第二联系人姓名：</td>
            <td width="300"><?php echo isset($loan_person_info['second_contact_name'])?$loan_person_info['second_contact_name']:'--'; ?></td>
            <td class="td24">第二联系人手机号：</td>
            <td><?php echo isset($loan_person_info['second_contact_phone'])?$loan_person_info['second_contact_phone']:'--'; ?></td>
        </tr>
    <tr>
        <td class="td24">与第二联系人的关系：</td>
        <td width="300"><?php echo isset($loan_person_info['second_contact_relation'])?LoanPersonInfo::$contact_list[$loan_person_info['second_contact_relation']]:'--'; ?></td>
    </tr>
        <tr>
            <td class="td24">第三联系人姓名：</td>
            <td width="300"><?php echo isset($loan_person_info['third_contact_name'])?$loan_person_info['third_contact_name']:'--'; ?></td>
            <td class="td24">第三联系人手机号：</td>
            <td><?php echo isset($loan_person_info['third_contact_phone'])?$loan_person_info['third_contact_phone']:'--'; ?></td>
        </tr>
    <tr>
        <td class="td24">与第三联系人的关系：</td>
        <td width="300"><?php echo isset($loan_person_info['third_contact_relation'])?LoanPersonInfo::$contact_list[$loan_person_info['third_contact_relation']]:'--'; ?></td>
    </tr>

    <tr><th class="partition" colspan="15">照片信息</th></tr>
        <?php if(!empty($loan_person_info_img)):?>
            <?php foreach($loan_person_info_img as $value):?>
    <tr><td><img src="<?php echo $value['url'];?>" width="100" height="150"/></td></tr>
            <?php endforeach;?>
        <?php endif;?>



</table>