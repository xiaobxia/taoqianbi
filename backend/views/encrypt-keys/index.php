<?php

use yii\helpers\Html;
use common\helpers\Url;
use yii\grid\GridView;
use backend\assets\AppAsset;

$this->title = Yii::t('app', '密钥配置');
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
        <div class="encrypt-keys-index">

            <h1><?php echo Html::encode($this->title) ?></h1>

            <p>
                <button id="update-keys" class="btn btn-success">更新密钥</button>
            </p>

            <?php echo GridView::widget([
                'dataProvider' => $dataProvider,
                // 'filterModel' => $filterModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    'id',
                    // 'private_key:ntext',
                    // 'public_key:ntext',
                    'encrypt_type',
                    'encrypt_bits',
                    'create_time',
                    // 'update_time',
                    // 'state',
                    // 'status',

                    // ['class' => 'yii\grid\ActionColumn'],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '操作',
                        'template' => '<table><tr>{list}</tr></table>',
                        'buttons' => [
                            'list' => function ($url, $model, $key) {
                                return '<a class="glyphicon glyphicon-eye-open" title ="详情" href=' . Url::toRoute(['encrypt-keys/view', 'id' => $model['id']]) . '></a>'  ;
                            }
                        ]
                    ],
                ],
            ]); ?>

        </div>
        <?php $this->endBody() ?>
        <script type="text/javascript">
            $('#update-keys').click(function(){
                if (!window.confirm("是否确认替换新密钥?"))
                    return;
                $(this).attr('disabled', 'disabled');
                $(this).text("正在生成中...");
                $.ajax({
                    url : "<?php echo Url::toRoute('encrypt-keys/update'); ?>",
                    type : 'POST',
                    error : function(result){
                        alert(result);
                        $('#update-keys').removeAttr('disabled');
                        $('#update-keys').text("更新密钥");
                    },
                    success : function(result){
                        alert(result);
                        location.href = window.location;
                    }
                });
            });

        </script>
    </body>
</html>
<?php $this->endPage() ?>