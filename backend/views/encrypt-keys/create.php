<?php

use yii\helpers\Html;
use backend\assets\AppAsset;

$this->title = Yii::t('app', 'Create {modelClass}', [
    'modelClass' => 'Encrypt Keys',
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Encrypt Keys'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<html>
    <head>
        <?php $this->head() ?>
    </head>

    <body>
        <?php $this->beginBody() ?>
		<div class="encrypt-keys-create">

		    <h1><?php echo Html::encode($this->title) ?></h1>

		    <?php echo $this->render('_form', [
		        'model' => $model,
		    ]) ?>

		</div>
		<?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>