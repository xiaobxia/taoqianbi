<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

use common\models\card_qualification\CardQualification;

$this->title = ($type=='manual')?'人工审核列表':'资格列表';
use backend\assets\AppAsset;

AppAsset::register($this);
?>
<html>
    <?php $this->beginPage() ?>
<head>
    <style type="text/css">
        #yii-debug-toolbar {
            display: none;
        }
        #yii-debug-toolbar-min {
            display: none !important;
        }
    </style>
    <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
    <?php $this->head() ?>
</head>

<body>
<?php $this->beginBody() ?>

<div class="rule-index">
    <h1><?php echo Html::encode($this->title) ?></h1>
<?php
    $columns = [
        [
            'attribute' => 'id',
            'value' => function ($model) {
                return $model->id;
            },
        ],
        [
            'attribute' => 'loan_person_id',
            'value' => function ($model) {
                return $model->loan_person_id;
            },
        ],
        [
            'attribute' => '用户姓名',
            'value' => function ($model) {
                return $model->user->name;
            },
        ],
        [
            'attribute' => '联系电话',
            'value' => function ($model) {
                return $model->user->phone;
            },
        ],
        [
            'attribute' => $type == 'manual'?'审核结果':'qualification',
            'filter' => CardQualification::$label_qualification,
            'value' => function ($model) {
                return (array_key_exists($model->qualification, CardQualification::$label_qualification)) ? CardQualification::$label_qualification[$model->qualification] : '未知';
            },
        ],
        [
            'attribute' => 'type',
            'filter' => CardQualification::$label_type,
            'value' => function ($model) {
                return (array_key_exists($model->type, CardQualification::$label_type)) ? CardQualification::$label_type[$model->type] : '未知';
            },
        ],
    ];

    $columns[] = [
        'header' => '操作',
        'class' => 'yii\grid\ActionColumn',
        'template' => '<table><tr><td>{view}</td>'.($type == 'manual'?'<td>&nbsp&nbsp</td><td>{check}</td>':'').'</tr></table>',
        'buttons' => [
            'view' => function ($url, $model, $key){
                return Html::a('查看',['view','id' => $model->id],['class'=>'']);
            },
        ],
    ];

    if($type == 'manual'){
        $columns[count($columns)-1]['buttons']['check'] = function ($url, $model, $key){
                return Html::a('人工审核',['check','id' => $model->id],['class'=>'']);
            };
    }

    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => $columns
    ]); ?>

    </div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>