<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\Setting;
/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_global_card_warn_quota');
$this->showsubmenu(APP_NAMES.'警告设置');
?>


<?php if(\Yii::$app->session->hasFlash('message')): ?>
    <tr><td><p style="color: red"> <?php echo \yii::$app->session->getFlash('message'); ?></p></td></tr>
<?php endif ?>

<?php $form = ActiveForm::begin(['id' => 'setting-form','method' => "post", 'action' => ['global/app-card-warn-quota']]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">白卡警告额度设置</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            <div class="form-group field-setting-svalue">
                <label class="control-label" for="setting-svalue-white-card">Svalue</label>
                <input type="text" id="setting-svalue-white-card" class="form-control" name="white_card_warn_quota" value="<?php echo (isset($values['white_card_warn_quota'])?$values['white_card_warn_quota']:0)/100 ?>">
                <div class="help-block"></div>
            </div>
        </td>
        <td class="vtop tips2">单位：元，当前显示值：<?php echo (isset($values['white_card_warn_quota'])?$values['white_card_warn_quota']:0)/100 ?></td>
    </tr>

    <tr><td class="td27" colspan="2">金卡警告额度设置</td></tr>
    <tr class="noborder">
        <td class="vtop rowform">
            <div class="form-group field-setting-svalue">
                <label class="control-label" for="setting-svalue-golden-card">Svalue</label>
                <input type="text" id="setting-svalue-golden-card" class="form-control" name="golden_card_warn_quota" value="<?php echo (isset($values['golden_card_warn_quota'])?$values['golden_card_warn_quota']:0)/100 ?>">
                <div class="help-block"></div>
            </div>
        </td>
        <td class="vtop tips2">单位：元，当前显示值：<?php echo (isset($values['golden_card_warn_quota'])?$values['golden_card_warn_quota']:0)/100 ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="hidden" value="2" name="type"/>
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
