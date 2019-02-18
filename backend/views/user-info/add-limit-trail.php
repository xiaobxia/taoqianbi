<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/16
 * Time: 11:29
 */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
/**
 * @var backend\components\View $this
 */
?>
<?php echo $this->render('limit-view', [
    'information' => $information,
]); ?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">审核此项目</th></tr>
    <tr>
        <td class="td24">操作</td>
        <td><?php echo Html::radioList('operation', 1, [
                '1' => '通过',
                '2' => '拒绝'
            ]); ?></td>
    </tr>
    <tr  class="limit" >
        <td >额度：</td>
        <td><input type="text" id="limit" value="" name="limit" style="align-items: flex-start;text-align: center;"></td>
    </tr>
    <tr class="pass" style="display: none">
        <td class="td24">审核码：</td>
        <td ><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''),\common\models\LimitApply::$code, ['id'=>'code']); ?></td>
    </tr>
    <tr  class="remark" >
        <td class="td24" >备注：</td>
        <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;', 'id' => "review-remark"]); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input id="submit_btn" value="提交" name="submit_btn" class="btn" style="align-items: flex-start;text-align: center;">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script>
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').hide();
            $('.limit').show();
        }else{
            $('.pass').show();
            $('.limit').hide();
        }
    });

    $("#submit_btn").click(function(){
        var code = $(":radio:checked").val();
        if ($("#review-remark").val() == "" && code !=1 && $("#code").val()==11) {
            alert("该选项必须备注原因");
            return;
        }
        if($("#limit").val() == "" && code ==1)
        {
            alert("额度不能为空");
            return;
        }
        $("#review-form").submit();
    })
</script>