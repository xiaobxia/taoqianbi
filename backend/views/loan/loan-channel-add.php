<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/16
 * Time: 11:29
 */
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\UserCreditMoneyLog;
/**
 * @var backend\components\View $this
 */
?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">添加渠道商</th></tr>
    <tr>
        <td class="td24">渠道商电话：</td>
        <td><?php echo Html::textInput('phone','', ['style' => 'width:150px;']); ?></td>
    </tr>
    <tr>
        <td class="td24">密码：</td>
        <td><?php echo Html::textInput('password','', ['style' => 'width:150px;']); ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
