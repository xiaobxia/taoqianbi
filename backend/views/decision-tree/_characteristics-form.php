<?php

use common\helpers\Url;
use yii\bootstrap\Collapse;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use common\models\risk\Rule;
use common\models\risk\RuleExtendMap;
use backend\assets\AppAsset;
use backend\models\AdminUser;

AppAsset::register($this);
$templatelist = ["0"=>"无"];
if(!empty($escapetemplate)){
    foreach ($escapetemplate as $key => $value) {
        $templatelist[$value['id']] = $value['name'];
    }
}

$admin_list = AdminUser::find()->asArray()->all();
$admin_name_list = [];
foreach ($admin_list as $admin) {
    $admin_name_list[$admin['id']] = $admin['username'];
}

?>
<html>
<?php $this->beginPage() ?>
<head>
<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
<?php $this->head() ?>
</head>
<script type="text/javascript">
    var type = <?php echo count($ruleModel->type)?$ruleModel->type:"-1" ?>;
    var extend_type = <?php echo count($ruleModel->extend_type)?$ruleModel->extend_type:"-1" ?>;
    var map_add = <?php echo empty($add_map)?"false":"true" ?>;
    var template_id = <?php echo !empty($ruleModel)&&!empty($ruleModel->template_id)?$ruleModel->template_id:"0" ?>;
    var test = <?php echo empty($test)?"false":"true" ?>;
    $(function(){
        if(map_add){
            $(".map-form").show();
        }
        if(template_id && template_id !== 0){
            templateTable(template_id,function(ret){$(".template-tabel").html(ret);});
        }

        // $(".type-basic-label").parent().parent().addClass("type-basic");
        // $(".type-expression-label").parent().parent().addClass("type-expression");
        // $(".type-mapping-label").parent().parent().addClass("type-mapping");
        // $(".type-mapping").hide();
        // $(".type-expression").hide();
        if(type==1){
            $(".type-basic").hide();
            if(extend_type==0){
                $(".type-expression").show();
                $(".type-mapping").hide();
            }else if(extend_type==1){
                $(".type-expression").hide();
                $(".type-mapping").show();
            }
        }
        $(".type").change(function(){
            if($(this).val()==0){
                $(".extend_type").hide();
                $(".type-expression").hide();
                $(".type-mapping").hide();
                $(".type-basic").show();
            }else if($(this).val()==1){
                $(".extend_type").css('display','inline-block');
                $(".type-basic").hide();
                if($(".extend_type").val()==0){
                    $(".type-expression").show();
                    $(".type-mapping").hide();
                }else if($(".extend_type").val()==1){
                    $(".type-expression").hide();
                    $(".type-mapping").show();
                }
            }
        });
        $(".extend_type").change(function(){
            if($(this).val()==0){
                $(".type-expression").show();
                $(".type-mapping").hide();
            }else if($(this).val()==1){
                $(".type-expression").hide();
                $(".type-mapping").show();
            }
        });
        $(".field-rule-expression .help-block").css("color","#000");
        $(".field-ruleextendmap-expression .help-block").css("color","#000");
        $("#rule-expression").focus(function(){
            $(this).off('.yiiActiveForm');
        });
        $("#rule-expression").blur(function(){
            validateExpression($("#rule-expression").val(),function(ret){
                $(".field-rule-expression .help-block").html(ret).show();
            });
        });
        if($("#rule-expression").val()){
            validateExpression($("#rule-expression").val(),function(ret){
                $(".field-rule-expression .help-block").html(ret).show();
            });
        }

        $("#ruleextendmap-expression").focus(function(){
            $(this).off('.yiiActiveForm');
        });
        $("#ruleextendmap-expression").blur(function(){
            validateExpression($("#ruleextendmap-expression").val(),function(ret){
                $(".field-ruleextendmap-expression .help-block").html(ret).show();
            });
        });
        if($("#ruleextendmap-expression").val()){
            validateExpression($("#ruleextendmap-expression").val(),function(ret){
                $(".field-ruleextendmap-expression .help-block").html(ret).show();
            });
        }

        $("#rule-template_id").change(function(){
            if($(this).val() != "0"){
                templateTable($(this).val(),function(ret){$(".template-tabel").html(ret);});
            }else{
                $(".template-tabel").html("");
            }
        });

        if(test){
            $(".characteristics-form input,select,textarea").attr("disabled", true);
            $(".form-buttons,.map-form").remove();
            $(".test-form").show();
        }
    });
    function validateExpression(exp,callback){
        var ids = "";
        var trans_str = exp.replace(/@(\d+)/g,function(m,key,index,str){
            ids += key+",";
            return '特征'+key;
        });
        ids = ids.substring(0,ids.length-1);
        $.get(
            '<?php echo Url::toRoute('/decision-tree/validate-expression');?>',
            {'id' : ids},
            function(ret){
                if(ret){
                    trans_str = "<span style='color:red;'>特征"+ret+"不存在</span>";
                }
                callback(trans_str);
            },
            'text'
        );
    }

    function templateTable(template_id,callback){
        $.get(
            '<?php echo Url::toRoute('/decision-tree/get-escape-rule');?>',
            {'template_id' : template_id},
            function(ret){
                var thtml = "";
                if(ret){
                    var j = 0;
                    for (var i in ret) {
                        if(j == 0){
                            thtml += "<tr><td>"+ret[i]['value']+":"+ret[i]['sign']+"</td>";
                            j ++;
                        }else if(j == 3){
                            thtml += "<td>"+ret[i]['value']+":"+ret[i]['sign']+"</td></tr>";
                            j = 0;
                        }else{
                            thtml += "<td>"+ret[i]['value']+":"+ret[i]['sign']+"</td>";
                            j ++;
                        }
                    }
                    thtml += "</tr>";
                }
                callback(thtml);
            },
            'json'
        );
    }

    function testrule(){
        var user_id = parseInt($("#test-user_id").val());
        if(!user_id){
            $("#test-result").text('输入用户名先');
            return;
        }
        $("#test-result").text('');
        var rule_id = '<?php echo $ruleModel->id ?>';
        $.get(
            '<?php echo Url::toRoute('decision-tree/test-rule'); ?>',
            {'rule_id':rule_id,'user_id':user_id},
            function(ret){
                $("#test-result").text(ret['result']);
            },
            'json'
        );
    }
</script>

<body>
<?php $this->beginBody() ?>

<div class="characteristics-form">
    <?php $form = ActiveForm::begin(); ?>

    <?php echo $form->field($ruleModel, 'name')->textInput() ?>
    <?php echo $form->field($ruleModel, 'tree_root')->dropDownList(Rule::$label_tree_root, ['prompt'=>'' , 'style'=>'width:30%']) ?>
    <?php echo $form->field($ruleModel, 'tree_description')->textInput() ?>
    <div class="form-group  field-rule-type required" >
        <label class="control-label" for="rule-type">类型</label>
        <div style="height: 34px;overflow: hidden;">
            <select class="type form-control" style="width: 30%;display: inline-block;" name="Rule[type]">
                <option value="0" <?php echo $ruleModel->type!==null&&$ruleModel->type!==""&&$ruleModel->type==Rule::TYPE_BASIC ? 'selected' : '' ?>>基础</option>
                <option value="1" <?php echo $ruleModel->type==Rule::TYPE_EXTEND ? 'selected' : '' ?>>扩展</option>
            </select>
            <select class="extend_type form-control" style="width: 30%;margin-left: 2%;display: <?php echo !empty($ruleModel->type)&&$ruleModel->type==Rule::TYPE_EXTEND ? 'inline-block' : 'none' ?>;margin-left: 20px;" name="Rule[extend_type]">
                <option value="0" <?php echo $ruleModel->extend_type!==null&&$ruleModel->extend_type!==""&&$ruleModel->extend_type==Rule::EXTEND_TYPE_EXPRESSION ? 'selected' : '' ?>>表达式</option>
                <option value="1" <?php echo $ruleModel->extend_type==Rule::EXTEND_TYPE_MAPPING ? 'selected' : '' ?>>映射</option>
            </select>
        </div>
    </div>
    <div class="type-basic">
        <?php echo $form->field($ruleModel, 'module')->textInput() ?>
        <?php echo $form->field($ruleModel, 'url')->textInput() ?>
        <?php echo $form->field($ruleModel, 'params')->textArea() ?>
        <?php echo $form->field($ruleModel,'description')->textInput() ?>
    </div>
    <div class="type-expression" style="display: none;">
        <?php echo $form->field($ruleModel, 'expression')->textInput() ?>
    </div>

    <div class="type-mapping" style="display: none;">
        <?php echo $form->field($ruleModel, 'result')->textInput() ?>

        <h2>映射列表</h2>
        <div style="width: 100%;height: 5px;"></div>
        <div class="form-buttons">
            <?php
                echo $ruleModel->isNewRecord ? Html::submitButton('添加',['class'=>'btn btn-success']) : Html::a('添加', ['extend-add','rule_id' => $ruleModel->id], ['class'=>'btn btn-success']);;
            ?>
        </div>
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
                    'attribute' => 'expression',
                    'value' => function ($model) {
                        return $model->expression;
                    },
                ],
                [
                    'attribute' => 'result',
                    'value' => function ($model) {
                        return $model->result;
                    },
                ],
                [
                    'attribute' => 'order',
                    'value' => function ($model) {
                        return $model->order;
                    },
                ],
                [
                    'attribute' => 'state',
                    'filter' => RuleExtendMap::$label_state,
                    'value' => function ($model) {
                        return (array_key_exists($model->state, RuleExtendMap::$label_state)) ? RuleExtendMap::$label_state[$model->state] : '未知';
                    },
                ],
            ];

            if(empty($test)){
                $columns[] = [
                    'header' => '操作',
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '<table>
                            <tr><td>{update}</td><td>&nbsp</td><td>{approve}</td><td>&nbsp</td><td>{delete}</td></tr>
                            </table>',
                    'buttons' => [
                        'update' => function ($url, $model, $key){
                            return Html::a('编辑',['extend-update' ,'id' => $model->id],['class'=>'']);
                        },
                        'approve' => function ($url, $model, $key){
                            if($model->state==RuleExtendMap::STATE_USABLE&&$model->status == RuleExtendMap::STATUS_NORMAL){
                                return Html::a('停用',['extend-reject','id' => $model->id],['class'=>'']);
                            }else if($model->state==RuleExtendMap::STATE_DISABLE&&$model->status == RuleExtendMap::STATUS_NORMAL){
                                return Html::a('启用',['extend-approve','id' => $model->id],['class'=>'']);
                            }
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('删除', ['extend-delete','id' => $model->id], ['class'=>'']);
                        },
                    ],
                ];
            }

            $columns[] = [
                'header' => '操作记录',
                'class' => \yii\grid\Column::class,
                'content'  => function ($model) use($admin_name_list) {
                        $operate_log = \common\models\risk\RuleOperateLog::find()->where(['rule_id' => $model->id])->orderBy('id desc')->all();
                        $tbody = '';
                        if (!empty($operate_log)) {

                            foreach ($operate_log as $record) {
                                $tbody .= '<tr>';
                                $tbody .= '<td>' . ($admin_name_list[$record['user_id']] ?? '') . '</td>';
                                $tbody .= '<td>' . \common\models\risk\RuleOperateLog::$label_state[$record['operate']] . '</td>';
                                $tbody .= '<td>' . $record['remark'] . '</td>';
                                $tbody .= '<td>' . $record['create_time'] . '</td>';
                                $tbody .= '</tr>';
                                }

                        }

                        return '<a data-toggle="collapse" data-parent="#accordion" href="#collapse'.$model->id.'">查看/收起</a>
                                <div class="panel-group" id="accordion">
                                    <div id="collapse'.$model->id.'" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <td>操作者</td>
                                                        <td>操作类型</td>
                                                        <td>说明</td>
                                                        <td>操作时间</td>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ' . $tbody .'
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>';
                    },

            ];
            echo GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => $columns
            ]);
        ?>
    </div>
    <?php echo $form->field($ruleModel, 'template_id')->dropDownList($templatelist) ?>

    <div class="escape-template grid-view" style="width:100%; max-height: 105px; overflow:auto;">
        <table class="table table-striped table-bordered" style="width: 100%;text-align: center;">
        <tbody class="template-tabel">

        </tbody>
        </table>
    </div>
</div>
<div class="form-group form-buttons">
<div style="width: 100%;height: 5px;"></div>
    <?php
        echo Html::submitButton('保存', ['class' =>  'btn btn-success' ]);
        echo "&nbsp;";
        echo html::a('取消',['characteristics-list'],['class'=>'btn btn-primary']);
    ?>
<div style="width: 100%;height: 5px;"></div>
</div>

<div class="test-form" style="display: none;">
    <h2>测试结果</h2>
    <label class="control-label">用户id</label>
    <input type="text" id="test-user_id" class="form-control">
    <div style="width: 100%;height: 5px;"></div>
    <a class="btn btn-success" href="javascript:testrule()">提交</a>
    <div style="width: 100%;height: 5px;"></div>
    <label class="control-label">结果</label>
    <textarea id="test-result" class="form-control" style="min-height: 200px;overflow-y: visible;resize: vertical;"></textarea>
</div>
<?php ActiveForm::end(); ?>
    <div class="map-form" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; background-color: black; filter:alpha(opacity=50);-moz-opacity: 0.5;opacity: 0.5; display: none;"></div>
    <div class="map-form" style="position: fixed;width: 100%;height: 100%;top:0;left: 0;display: none;">
        <div class="movable-form" style="position: absolute;top:50%;left: 50%; width: 40%;height: 40%;margin-top: -20%;margin-left: -25%;background-color: white;border: 1px solid #f2f2f2;z-index: 666;padding:0 20px;border-radius: 5px;">

        <h2> 依赖关系</h2>
        <?php $form = ActiveForm::begin(); ?>

    <table>
        <?php echo $form->field($extendModel, 'expression')->textInput() ?>

        <?php echo $form->field($extendModel, 'result')->textInput() ?>

        <?php echo $form->field($extendModel, 'order')->textInput() ?>
    </table>
        <div class="form-group">
            <?php
            echo Html::submitButton( '保存', ['class' =>'btn btn-success']);
            echo "&nbsp;";
            echo html::a('取消',['characteristics-update','id'=>$ruleModel->id],['class'=>'btn btn-primary']);
            ?>
        </div>

        <?php ActiveForm::end(); ?>

        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>