<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\statistics\EncryptKeys */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
    'modelClass' => 'Encrypt Keys',
]) . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Encrypt Keys'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="encrypt-keys-update">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>