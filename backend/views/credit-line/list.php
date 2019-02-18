<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

use common\models\card_qualification\CardQualification;

$this->title = '提额申请结果';
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
            'attribute' => 'user_id',
            'value' => function ($model) {
                return $model->user_id;
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
            'attribute' => 'credit_line',
            'value' => function ($model) {
                return $model->credit_line;
            },
        ],
        [
            'attribute' => 'time_limit',
            'value' => function ($model) {
                return $model->time_limit;
            },
        ],
        [
            'attribute' => '有效期限',
            'value' => function ($model) {
                return $model->valid_time;
            },
        ],
        [
            'attribute' => '审核时间',
            'value' => function ($model) {
                return $model->update_time;
            },
        ],
        [
            'attribute' => '决策树',
            'value' => function ($model) {
                return $model->tree;
            },
        ],
    ];

    // $columns[] = [
    //     'header' => '操作',
    //     'class' => 'yii\grid\ActionColumn',
    //     'template' => '<table><tr><td>{view}</td>'.($type == 'manual'?'<td>&nbsp&nbsp</td><td>{check}</td>':'').'</tr></table>',
    //     'buttons' => [
    //         'view' => function ($url, $model, $key){
    //             return Html::a('查看',['view','id' => $model->id],['class'=>'']);
    //         },
    //     ],
    // ];



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