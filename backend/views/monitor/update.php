<?php

use yii\helpers\Html;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Monitor */

$this->showsubmenu('监控', array(
    array('监控列表', Url::toRoute('monitor/index'), 0),
    array('新建监控', Url::toRoute('monitor/create'),0),
    array('查看', Url::toRoute(['monitor/view','id'=>$model->id]),0),
));

?>
<div class="monitor-update">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
