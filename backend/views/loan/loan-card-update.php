<?php
use backend\components\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;

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
<?php $form = ActiveForm::begin(['id' => 'can-loan-time-update']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">重置绑定用户银行卡</th></tr>
    <tr><td class="label" >用户姓名</td><td><input type="text" name="name" value="<?php echo $loan_person['name']?>" disabled></td></tr>
    <tr><td class="label" >用户身份证</td><td><input type="text" name="name" value="<?php echo $loan_person['id_number']?>" disabled></td>
    </tr>
    <tr>
        <td class="label" >选择银行卡</td>
        <td>
            <select name="bank">
                <?php foreach($card_list as $k=>$v):?>
                    <option value='<?php echo json_encode(['key'=>$k,'val'=>$v]);?>'><?php echo $v;?></option>
                <?php endforeach;?>
            </select>
        </td>
    </tr>
    <tr><td class="label" >填写用户姓名</td><td><input type="text" name="name"></td></tr>
    <tr><td class="label" >填写用户身份证号</td><td><input type="text" name="id_number"></td></tr>
    <tr><td class="label" >填写卡号</td><td><input type="text" name="card"></td></tr>
    <tr><td class="label" >填写银行预留手机号</td><td><input type="text" name="phone"></td></tr>
    <tr><input type="text" style="display: none" name="id" value="<?php echo $id;?>"></tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn"/>
            <input type="submit" value="重置卡信息" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>

