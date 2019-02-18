<?php 
use yii\widgets\ActiveForm;
?>

<table class="tb tb2 fixpadding">
    <?php $form = ActiveForm::begin(['id' => 'review-form']); ?>

    <tr>
        <td class="td24">放款失败备注：</td>
        <td><textarea name="remark"><?php echo !empty($post_data['remark']) ? $post_data['remark'] : ''; ?></textarea></td>
        <td class="td24"></td>
        <td></td>
    </tr>
    
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
    
    <?php ActiveForm::end() ?>
</table>