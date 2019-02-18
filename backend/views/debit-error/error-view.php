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
$this->shownav('staff', 'menu_ygb_zc_lqd_lb');
?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">错误详情</th></tr>
    <tr>
        <td class="td21">ID：</td>
        <td width="200"><?php echo $debitErrorLog['id']?></td>
        <td class="td21">用户ID：</td>
        <td ><?php echo $debitErrorLog['user_id']?></td>
    </tr>

    <tr>
        <td class="td21">卡号：</td>
        <td ><?php echo $debitErrorLog['card_no']?></td>
        <td class="td21">手机号：</td>
        <td ><?php echo $debitErrorLog['phone']?></td>
    </tr>
    <tr>
        <td class="td21">错误信息：</td>
        <td colspan="4"><?php echo $debitErrorLog['error_msg']?></td>
    </tr>
</table>
<?php $form = ActiveForm::begin(['id' => 'review-form','action'=>Url::toRoute('debit-error/set-remark')]); ?>
    <input type="hidden" name="debitErrorId" value="<?php echo $debitErrorLog['id']?>">
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">添加备注</th></tr>
        <tr>
            <td class="td24">备注说明：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn"/>
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>