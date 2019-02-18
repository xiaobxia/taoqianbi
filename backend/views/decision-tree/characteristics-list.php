<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use common\models\risk\Rule;
use common\helpers\Url;

$this->title = '特征列表';
use backend\assets\AppAsset;

AppAsset::register($this);
?>
    <html>
    <?php $this->beginPage(); ?>
    <head>
        <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
        <?php $this->head(); ?>
    </head>


    <script type="text/javascript">
        function reject(id) {
            if (confirm("确定停用特征" + id + "吗?")) {
                $.get(
                    '<?php echo Url::toRoute('decision-tree/characteristics-reject'); ?>',
                    {id: id},
                    function (ret) {
                        if (ret) {
                            $("#reject_" + id).attr('href', 'javascript:debug(' + id + ')');
                            $("#reject_" + id).text("调试");
                            $("[data-key=" + id + "]").children('td').eq(3).text("<?php echo Rule::$label_state[Rule::STATE_DISABLE] ?>");
                        } else {
                            alert("停用特征" + id + "失败");
                        }
                    },
                    'text'
                );
            }
        }
        function debug(id) {
            $.get(
                '<?php echo Url::toRoute('decision-tree/characteristics-debug'); ?>',
                {id: id},
                function (ret) {
                    if (ret) {
                        $("#reject_" + id).attr('href', 'javascript:approve(' + id + ')');
                        $("#reject_" + id).text("启用");
                        $("[data-key=" + id + "]").children('td').eq(3).text("<?php echo Rule::$label_state[Rule::STATE_DEBUG] ?>");
                    } else {
                        alert("特征" + id + "置为调试失败");
                    }
                },
                'text'
            );
        }
        function approve(id) {
            if (confirm("确定启用特征" + id + "吗?")) {
                $.get(
                    '<?php echo Url::toRoute('decision-tree/characteristics-approve'); ?>',
                    {id: id},
                    function (ret) {
                        if (ret) {
                            $("#reject_" + id).attr('href', 'javascript:reject(' + id + ')');
                            $("#reject_" + id).text("停用");
                            $("[data-key=" + id + "]").children('td').eq(3).text("<?php echo Rule::$label_state[Rule::STATE_USABLE] ?>");
                        } else {
                            alert("启用特征" + id + "失败");
                        }
                    },
                    'text'
                );
            }
        }
        function copy(id) {
            if (confirm("确定复制特征" + id + "吗?")) {
                $.post(
                    '<?php echo Url::toRoute('decision-tree/characteristics-copy'); ?>',
                    {id: id},
                    function (ret) {
                        if (ret) {
                            alert("复制特征" + id + " 成功");
                        } else {
                            alert("复制特征" + id + " 失败");
                        }
                        location = location;
                    },
                    'text'
                );
            }
        }
    </script>

    <body>
    <?php $this->beginBody() ?>

    <div class="rule-index">

        <h1><?php echo Html::encode($this->title) ?></h1>
        <div style="width: 100%;height: 5px;"></div>
        <p>
            <?php echo Html::a('新建', ['characteristics-add'], ['class' => 'btn btn-success']) ?>
        </p>
        <div style="width: 100%;height: 5px;"></div>
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
                'attribute' => 'type',
                'filter' => Rule::$label_type,
                'value' => function ($model) {
                    return (array_key_exists($model->type, Rule::$label_type)) ? Rule::$label_type[$model->type] : '未知';
                },
            ],
            [
                'attribute' => 'state',
                'filter' => Rule::$label_state,
                'value' => function ($model) {
                    return (array_key_exists($model->state, Rule::$label_state)) ? Rule::$label_state[$model->state] : '未知';
                },
            ],
            [
                'attribute' => 'tree_root',
                'filter' => Rule::$label_tree_root,
                'value' => function ($model) {
                    return (array_key_exists($model->tree_root, Rule::$label_tree_root)) ? Rule::$label_tree_root[$model->tree_root] : '未知';
                },
            ],
            [
                'attribute' => 'tree_description',
                'value' => function ($model) {
                    return $model->tree_description;
                },
            ],
            [
                'attribute' => 'source',
                'value' => function ($model) {
                    $json = $model->description;
                    $a = json_decode($json, true);
                    return isset($a['source']) ? $a['source'] : '';
                },
            ],
            [
                'attribute' => 'data_source',
                'value' => function ($model) {
                    $json = $model->description;
                    $a = json_decode($json, true);
                    return isset($a['data']) ? $a['data'] : '';
                },
            ],
            [
                'attribute' => 'value',
                'value' => function ($model) {
                    $json = $model->description;
                    $a = json_decode($json, true);
                    return isset($a['value']) ? $a['value'] : '';
                },
            ],
        ];


        $columns[] = [
            'header' => '操作',
            'class' => 'yii\grid\ActionColumn',
            'template' => '<table>
                        <tr><td>{update}</td><td>&nbsp</td><td>{approve}</td><td>&nbsp</td><td>{copy}</td><td>&nbsp</td><td>{viewdependence}</td><td>&nbsp</td><td>{viewdependencetest}</td><td>&nbsp</td><td>{test}</td></tr>
                        </table>',
            'buttons' => [
                'update' => function ($url, $model, $key) {
                    return Html::a('编辑', ['characteristics-update', 'id' => $model->id], ['class' => '']);
                },
                'approve' => function ($url, $model, $key) {
                    if ($model->state == Rule::STATE_USABLE && $model->status == Rule::STATUS_NORMAL) {
                        return '<a  href="javascript:reject(' . $model->id . ')" id="reject_' . $model->id . '">停用</a>';
                    } else if ($model->state == Rule::STATE_DEBUG && $model->status == Rule::STATUS_NORMAL) {
                        return '<a  href="javascript:approve(' . $model->id . ')" id="reject_' . $model->id . '">启用</a>';
                    } else if ($model->state == Rule::STATE_DISABLE && $model->status == Rule::STATUS_NORMAL) {
                        return '<a  href="javascript:debug(' . $model->id . ')" id="reject_' . $model->id . '">调试</a>';
                    }
                },
                'copy' => function ($url, $model, $key) {
                    if ($model->type == Rule::TYPE_EXTEND) {
                        return '<a  href="javascript:copy(' . $model->id . ')" id="' . $model->id . '">复制</a>';
                    }
                },
                'viewdependence' => function ($url, $model, $key) {
                    return Html::a('查看依赖', ['characteristics-view-dependence', 'id' => $model->id], ['class' => '']);
                },
                'viewdependencetest' => function ($url, $model, $key) {
                    return Html::a('查看依赖调试', ['characteristics-view-dependence-test', 'id' => $model->id], ['class' => '']);
                },
                'test' => function ($url, $model, $key) {
                    return Html::a('测试结果', ['characteristics-test', 'id' => $model->id], ['class' => '']);
                },
            ],
        ];
        echo GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => $columns
        ]); ?>

    </div>

    <!--     <script type="text/javascript">
    $(function () {
    $('#w0').yiiGridView({"filterUrl":"<?php echo Url::toRoute('decision-tree/characteristics-list'); ?>","filterSelector":"#w0-filters input, #w0-filters select"});
    });
    </script> -->

    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>