<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Monitor;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Monitor */

$this->showsubmenu('监控', array(
    array('监控列表', Url::toRoute('monitor/index'), 0),
    array('新建监控', Url::toRoute('monitor/create'),0),
));

?>
<div class="monitor-create">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
