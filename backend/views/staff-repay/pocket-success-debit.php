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
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">手动置为已还款</th></tr>
        <tr>
            <td class="td24">还款类型</td>
            <td><?php echo Html::radioList('operation', 2, [
                    '2' => '减免滞纳金'
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">应还金额(元)：</td>
            <td><?php echo ($repayment_money/100); ?></td>
        </tr>
        <tr>
            <td class="td24">实际还款金额(元)：</td>
            <td><input type="text" name="money" value="<?php echo $money/100; ?>" readonly="true"/></td>
        </tr>
        <tr>
            <td class="td24">（注明还款情况减免理由等）</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="hidden" name="view_type" value="cuishou"/>
                <input type="submit" value="提交" name="submit_btn" class="btn"/>
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>