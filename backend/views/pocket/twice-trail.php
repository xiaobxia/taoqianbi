<?php
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
    'information' => $information
]); ?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核此项目</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '复审通过',
                    '2' => UserLoanOrder::$status[UserLoanOrder::STATUS_REPEAT_CANCEL]
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">审核码：</td>
            <td class="pass"><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''), $pass_tmp); ?></td>
            <td class="reject" style="display: none"><?php echo Html::dropDownList('nocode', Yii::$app->getRequest()->get('code', ''), $reject_tmp); ?></td>
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
<script>
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').show();
            $('.pass select').attr('name','code');

            $('.reject').hide();
            $('.reject select').attr('name','nocode');
        }else{
            $('.pass').hide();
            $('.pass select').attr('name','nocode');
            $('.reject').show();
            $('.reject select').attr('name','code');
        }
    });
</script>
