<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/24
 * Time: 19:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\FinancialReconcillationRecord;
$this->shownav('financial', 'menu_financial_day_direct_list');
$this->showsubmenu('直连服务费列表');
?>
<style>
.tb2 th{ font-size: 12px;}
</style>
<?php $form = ActiveForm::begin(['method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<?php echo Html::textarea('remark', Yii::$app->getRequest()->post('remark', ''), ['style' => 'background-color:white', 'rows' => 6, 'cols' => 50])?><br />
<input type="submit" value="提交" name="save"  />
<?php if(!empty($info)): ?>
<div>
    <hr style="color: red;"/>
    <label style="color: red;"><strong>手续费:</strong></label>
    <label style="color: red;"><strong><?php echo sprintf("%0.2f",$info/100); ?></strong></label>
</div>
<?php endif;?>
<?php $form = ActiveForm::end(); ?>