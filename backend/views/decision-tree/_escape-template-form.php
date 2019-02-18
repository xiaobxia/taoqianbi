<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use common\helpers\Url;

use common\models\risk\EscapeTemplate;

$this->title = '转义模版列表';
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

<div class="escape-template-form">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <p>
        <?php
            echo Html::a('添加',['escape-template-add'],['class'=>'btn btn-success']);
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
                'attribute' => 'name',
                'value' => function ($model) {
                    return $model->name;
                },
            ],
            [
                'attribute' => 'state',
                'filter' => EscapeTemplate::$label_state,
                'value' => function ($model) {
                    return (array_key_exists($model->state, EscapeTemplate::$label_state)) ? EscapeTemplate::$label_state[$model->state] : '未知';
                },
            ],
        ];


        $columns[] = [
                'header' => '操作',
                'class' => 'yii\grid\ActionColumn',
                'template' => '<table>
                        <tr><td>{update}</td><td>&nbsp</td><td>{approve}</td><td>&nbsp</td><td>{viewrules}</td></tr>
                        </table>',
                'buttons' => [
                    'update' => function ($url, $model, $key){
                        return Html::a('编辑',['escape-template-update','id' => $model->id],['class'=>'']);
                    },
                    'approve' => function ($url, $model, $key){
                        if($model->state==EscapeTemplate::STATE_USABLE&&$model->status == EscapeTemplate::STATUS_NORMAL){
                            return Html::a('停用',['escape-template-reject','id' => $model->id],['class'=>'']);
                        }else if($model->state==EscapeTemplate::STATE_DISABLE&&$model->status == EscapeTemplate::STATUS_NORMAL){
                            return Html::a('启用',['escape-template-approve','id' => $model->id],['class'=>'']);
                        }else{
                            return Html::a('调试',['',''],['class'=>'']);
                        }
                    },
                    'viewrules' => function ($url, $model, $key) {
                        return Html::a('查看规则', ['escape-rule-list','template_id' => $model->id], ['class'=>'']);
                    },
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
        <div class="movable-form" style="position: absolute;top:50%;left: 50%; width: 40%;height: 25%;margin-top: -10%;margin-left: -25%;background-color: white;border: 1px solid #f2f2f2;z-index: 666;padding:0 20px;border-radius: 5px;">

        <h2> 新建/编辑模版</h2>
        <?php $form = ActiveForm::begin(); ?>

            <?php echo $form->field($model, 'name')->textInput() ?>

        <div class="form-group">
            <?php
            echo Html::submitButton( '保存', ['class' =>'btn btn-success']);
            echo "&nbsp;";
            echo html::a('取消',['escape-template-list'],['class'=>'btn btn-primary']);
            ?>
        </div>

        <?php ActiveForm::end(); ?>

        </div>
    </div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>