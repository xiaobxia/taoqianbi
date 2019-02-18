<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

use common\models\risk\EscapeRule;

$this->title = '转义模版'.$template_id.':规则列表';
use backend\assets\AppAsset;

AppAsset::register($this);
?>
<html>
    <?php $this->beginPage() ?>
<head>
    <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
    <?php $this->head() ?>
</head>

<script type="text/javascript">
    var map_add = <?php echo empty($add_map)?"false":"true" ?>;
    $(function(){
        if(map_add){
            $(".map-form").show();
        }
    });
</script>
<body>
<?php $this->beginBody() ?>

<div class="escape-rule-form">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <p>
        <?php
            echo Html::a('添加',['escape-rule-add','template_id'=>$template_id],['class'=>'btn btn-success']);
        ?>
    </p>
 <?php
    $columns = [
            [
                'attribute' => 'id',
                'value' => function ($model) {
                    return $model->id;
                },
            ],
            [
                'attribute' => 'value',
                'value' => function ($model) {
                    return $model->value;
                },
            ],
            [
                'attribute' => 'sign',
                'value' => function ($model) {
                    return $model->sign;
                },
            ],
        ];


        $columns[] = [
                'header' => '操作',
                'class' => 'yii\grid\ActionColumn',
                'template' => '<table>
                        <tr><td>{update}</td><td>&nbsp</td><td>{delete}</td></tr>
                        </table>',
                'buttons' => [
                    'update' => function ($url, $model, $key){
                        return Html::a('编辑',['escape-rule-update','id' => $model->id],['class'=>'']);
                    },
                    'delete' => function ($url, $model, $key){
                        return Html::a('删除',['escape-rule-delete','id' => $model->id],['class'=>'']);
                    }
                ],
            ];
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => $columns
    ]); ?>

    </div>
    <div class="map-form" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; background-color: black; filter:alpha(opacity=50);-moz-opacity: 0.5;opacity: 0.5; display: none;"></div>
    <div class="map-form" style="position: fixed;width: 100%;height: 100%;top:0;left: 0;display: none;">
        <div class="movable-form" style="position: absolute;top:50%;left: 50%; width: 40%;height: 33%;margin-top: -10%;margin-left: -25%;background-color: white;border: 1px solid #f2f2f2;z-index: 666;padding:0 20px;border-radius: 5px;">

        <h2> 新建/编辑规则</h2>
        <?php $form = ActiveForm::begin(); ?>

            <?php echo $form->field($model, 'value')->textInput() ?>
            <?php echo $form->field($model, 'sign')->textInput() ?>

        <div class="form-group">
            <?php
            echo Html::submitButton( '保存',['class' =>'btn btn-success']);
            echo "&nbsp;";
            echo html::a('取消',['escape-rule-list','template_id'=>$template_id],['class'=>'btn btn-primary']);
            ?>
        </div>

        <?php ActiveForm::end(); ?>

        </div>
    </div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>