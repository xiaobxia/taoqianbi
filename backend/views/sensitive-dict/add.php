<?php
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'loan_person_house_fund');
$this->showsubmenu('敏感词管理', array(
    array('列表', Url::toRoute('sensitive-dict/show-list'), 0),
    array('添加', Url::toRoute('sensitive-dict/add'), 1),
));
?>
<?php $form = ActiveForm::begin(['id' => 'black-list-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">添加敏感词</th></tr>
        <tr>
            <td>
                敏感词：<input type="text" name="name">
            </td>
        </tr>
        <tr>
            <td ><input type="submit" value="提交" name="submit_btn" class="btn"/></td>
        </tr>

    </table>
<?php ActiveForm::end(); ?>
