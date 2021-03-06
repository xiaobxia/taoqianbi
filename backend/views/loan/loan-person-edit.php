<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\helpers\Url;
use yii\helpers\Html;
new LoanPerson();

?>
<style>
    td.label {
        width: 170px;
        text-align: right;
        font-weight: 700;
    }
    .txt{ width: 100px;}

    .tb2 .txt, .tb2 .txtnobd {
        width: 200px;
        margin-right: 10px;
    }
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'loan-person-edit']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">编辑借款人信息</th></tr>
    <tr>
        <td class="label">借款人类型：</td>
        <td ><?php echo $form->field($loan_person, 'type')->dropDownList(LoanPerson::$person_type); ?></td>
    </tr>
    <tr>
        <td class="label">借款人来源：</td>
        <td ><?php echo $form->field($loan_person, 'source_id')->dropDownList(LoanPerson::$person_source); ?></td>
    </tr>
    <?php if(isset($company)) : ?>
    <tr>
        <td class="label">小钱包公司名称：</td>
        <td ><?php echo $form->field($company, 'company_name')->dropDownList($data); ?></td>
    </tr>
    <tr>
        <td class="label">服务费：</td>
        <td id="is_novice" width="200px"><?php echo Html::dropDownList('is_novice', 1, [
                '0' => '征收',
                '1' => '不征收',
            ]); ?></td>
        <td style="color: red;font-weight: bold">请仔细查看该用户是否需征收服务费</td>
    </tr>
    <?php endif; ?>
    <tr>
        <td class="label" id="phone">联系方式：</td>
        <td ><?php echo $form->field($loan_person, 'phone')->textInput(); ?><p id = "phone_msg"></p></td>
    </tr>
    <tr>
        <td class="label" id="uid">用户ID：</td>
        <td ><?php echo $form->field($loan_person, 'uid')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="name">借款人名称：</td>
        <td ><?php echo $form->field($loan_person, 'name')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="id_number">借款人编号：</td>
        <td ><?php echo $form->field($loan_person, 'id_number')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="birthday">借款人（生日/成立）日期：</td>
        <td ><?php echo $form->field($loan_person, 'birthday', ['options' => ['style' => 'float:left;']])
                ->textInput(array("onfocus"=>"WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true});")); ?></td>
    </tr>
    <tr>
        <td class="label" id="property">借款人性质：</td>
        <td ><?php echo $form->field($loan_person, 'property')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="contact_username">紧急联系人：</td>
        <td ><?php echo $form->field($loan_person, 'contact_username')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="contact_phone">紧急联系人：</td>
        <td ><?php echo $form->field($loan_person, 'contact_phone')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label">授信额度：</td>
        <td ><?php echo $form->field($loan_person, 'credit_limit')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" id="open_id">芝麻信用ID：</td>
        <td ><?php echo $form->field($loan_person, 'open_id')->textInput(); ?></td>
    </tr>
    <tr>
        <td class="label" >好房贷渠道代码(可为空)：</td>
        <td ><input type="text" name="shop_code" value="<?php echo $shop_code ;?>"/></td >
    </tr>
    <tr>
        <td class="label">是否可变更渠道(用户好房贷业务员)：</td>
        <td>
            <select name="can_choose">
                <option value="0">否</option>
                <option value="1">是</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="label" id="attachment">：</td>
        <td >
            <?php echo $form->field($loan_person, 'attachment')->textarea(); ?>
            <a style="color:#7f63fe;font-weight:bold;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件</a>&nbsp;&nbsp;
            （若上传多张图片用  逗号（ ，） 分隔开）
        </td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>
    $(document).ready(function(){
        var type = $('#loanperson-type').val();
        if(type == 2 || type == 3){
            //个人
            $("#id_number").text("身份证号:");
            $("#name").text("姓名:");
            $("#birthday").text("生日:");
            $("#property").text("性别:");
            $("#contact_username").text("紧急联系人(姓名):");
            $("#contact_phone").text("紧急联系人(手机号码):");
            $("#attachment").text("身份证、户口本:");
        }else if(type == 1){
            //企业
            $("#id_number").text("企业代码:");
            $("#name").text("公司名称:");
            $("#birthday").text("成立日期:");
            $("#property").text("所属行业:");
            $("#contact_username").text("企业法人:");
            $("#contact_phone").text("企业法人联系方式:");
            $("#attachment").text("营业执照等三证、法人身份证:");
        }
    });

    $('#loanperson-type').change(function(){
        var type = $('#loanperson-type').val();
        if(type == 2 || type == 3){
            //个人
            $("#id_number").text("身份证号:");
            $("#name").text("姓名:");
            $("#birthday").text("生日:");
            $("#property").text("性别:");
            $("#contact_username").text("紧急联系人(姓名):");
            $("#contact_phone").text("紧急联系人(手机号码):");
            $("#attachment").text("身份证、户口本:");
            $('#loanperson-phone').bind('input propertychange', function() {
                if ($(this).val().length == 11) {
                    $.get("<?php echo Url::toRoute(["loan/fill-loaner-detail"],true); ?>",{phone:$(this).val()},function(result){
                        var data = JSON.parse(result);
                        if (data.code == 0) {
                            $("#loanperson-uid").val(data.id);
                            $("#loanperson-name").val(data.realname);
                            $("#loanperson-id_number").val(data.id_card);
                            $("#loanperson-birthday").val(data.birthday);
                            if (data.sex == 1) {
                                $("#loanperson-property").val("男");
                            } else if (data.sex == 2) {
                                $("#loanperson-property").val("女");
                            }
                            $("#phone_msg").html("");
                        } else {
                            $("#phone_msg").html(data.msg);
                        }
                    });
                }
            });
        }else if(type == 1){
            //企业
            $("#id_number").text("企业代码:");
            $("#name").text("公司名称:");
            $("#birthday").text("成立日期:");
            $("#property").text("所属行业:");
            $("#contact_username").text("企业法人:");
            $("#contact_phone").text("企业法人联系方式:");
            $("#attachment").text("营业执照等三证、法人身份证:");
        }
    });

    $(function(){
        getFee($('#userdetail-company_name').val());
    });

    $('#userdetail-company_name').change(function(){
        getFee($(this).val());
    });

    function getFee(company_name){
        <?php foreach($fee as $k => $v):?>
        if('<?php echo $k;?>' == company_name){
            $('select[name="is_novice"]').val(<?php echo $v;?>);
        }
        <?php endforeach;?>
    }
</script>
