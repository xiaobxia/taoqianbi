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
/**
 * @var backend\components\View $this
 */
$this->shownav('staff', 'menu_ygb_zc_lqd_lb');
?>
<?php echo $this->render('pocket-view', [
    'common' => $common,
    'private'=>$private,
]); ?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核此项目</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '还款通过',
                    '2' => '还款驳回'
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">审核备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>