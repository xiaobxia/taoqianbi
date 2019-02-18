<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/6/22
 * Time: 16:19
 */
use common\models\UserCreditReviewLog;
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<?php echo $this->render('review-view', [
    'loan_person' => $loan_person,
    'list' => $list,
]);  ?>
<?php $form = ActiveForm::begin(); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核详情页</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '审核通过',
                    '2' => '审核驳回'
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>