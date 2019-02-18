<?php


use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\UserWithdraw;
use common\models\UserAccount;
use common\models\BankConfig;
use common\helpers\StringHelper;


$this->showsubmenu('笨鸟借款人同步');
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'auto-form']); ?>
    <table class="tb tb2">
        <tr  height="60">
            <td class="td24">同步日期</td>
            <td><?php echo $form->field($model, 'created_at', ['options' => ['style' => 'float:left;']])->textInput(['style' => 'width:100px;height:20px;','onFocus' => "WdatePicker()"]); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="同步" name="submit_btn" class="btn">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>