<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\assets\AppAsset;

use common\models\encrypt\EncryptKeys;

$this->title = '密钥 id:'.$model->id;
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
        <div class="encrypt-keys-view">

            <h1><?php echo Html::encode($this->title) ?></h1>

            <p>
                <?php echo Html::a(Yii::t('app', '返回'), ['index', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            </p>

            <?php echo DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'private_key:ntext',
                    'public_key:ntext',
                    'encrypt_type',
                    'encrypt_bits',
                    'create_time',
                    // 'update_time',
                    // 'state',
                    [
                        'label' => '状态',
                        'value' => EncryptKeys::$label_state[$model['state']],
                    ],
                    // 'status',
                ],
            ]) ?>

        </div>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>