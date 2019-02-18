<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\Setting;
/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_global_config');
$this->showsubmenu('全局配置');
?>
<style type="text/css">
    .tb {
        width: auto;
    }
    .tb2 th {
        font-size: 15px;
    }
    .key {
        color: #0991cc;
        text-align: left;
        font-weight: 600;
        padding-right: 10px;
    }
    .config {
        padding: 5px;
    }
</style>
<?php ActiveForm::begin(['id' => 'config_form', 'method' => "get", 'action' => ['global/config']]); ?>
    <table class="tb tb2 fixpadding">
        <?php foreach($message as $k => $v): ?>
            <tr class="config">
                <th class="key"><?php echo Setting::$comments[$k]?></th>
                <td>
                    <?php echo Html::dropDownList($k, $v, Setting::$global_config); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr class="hover">
            <td><input type="submit" name="setting_submit" value="提交" class="btn"></td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>

