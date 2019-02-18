<?php
/**
 * User: 李振国
 * Date: 2016/10/27
 */
use backend\components\widgets\ActiveForm;
use common\models\loan\LoanCollection;
use common\helpers\Url;
use yii\helpers\Html;
use backend\models\AdminUserRole;
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
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'user-add-form']); ?>
<table class="tb tb2 fixpadding">
    <!-- <tr><th class="partition" colspan="15">添加催收人信息</th></tr> -->
     <tr>
        <td class="label">机构：</td>
        <td ><?php echo $form->field($loan_collection, 'outside')->dropDownList(LoanCollection::outside()); ?></td>
    </tr>
    <tr>
        <td class="label">用户组：</td>
        <td ><?php echo $form->field($loan_collection, 'group')->dropDownList(LoanCollection::group()); ?></td>
    </tr>

    <tr>
        <td class="label">姓名：</td>
        <td ><?php echo $form->field($loan_collection, 'real_name')->textInput(['placeholder'=>'真实姓名']); ?></td>
    </tr>

    <tr>
        <td class="label">手机号：</td>
        <td ><?php echo $form->field($loan_collection, 'phone')->textInput(['placeholder'=>'用于接收验证码']); ?></td>
    </tr>
     <tr>
        <td class="label">登录名：</td>
        <td ><?php echo $form->field($loan_collection, 'username')->textInput(['placeholder'=>'建议使用姓名拼音']); ?>
            <span>(只能是字母、数字或下划线，不能重复，添加后不能修改)</span>

        </td>
    </tr>
    <tr>
        <td class="label">登录密码：</td>
        <td ><?php echo $form->field($loan_collection, 'password')->textInput(['value'=>123456,'placeholder'=>'初始密码']); ?>
            <span id="pwd-original" style="color:blue;display: none;">(原手机号登录账户已存在，登录密码不变)</span>
        </td>
    </tr>
    <tr>
        <td class="label">角色：</td>
        <td >
            <?php echo $form->field($loan_collection, 'is_monitor')->radioList(['1'=>'组长','0'=>'普通催收员']) ?>

        </td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
        <?php echo Html::submitButton('提交',['class'=>'btn btn-primary', 'name'=>'submit_btn']);?>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

<script type="text/javascript">
    $(function(){
        $("[id=loancollection-phone]").blur(function(){
            $.ajax({
                url:"<?php echo Url::toRoute(['back_end_admin-user/phone-ajax']) ?>",
                type:"get",
                dataType:"json",
                data:{phone:$(this).val()},
                success:function(res){
                    if(res != ''){
                        console.log('yes');
                        $("[id=loancollection-username]").val(res.username).attr("readonly","readonly");
                        $("[id=loancollection-password]").css('display','none');
                        $("[id=pwd-original]").css('display','block');
                    }else{
                        $("[id=loancollection-username]").attr("readonly",false);
                        $("[id=loancollection-password]").css('display','block');
                        $("[id=pwd-original]").css('display','none');
                    }
                },
                error:function(res){
                    alert('ajax error'+res);
                }
            });
        });
    });
</script>