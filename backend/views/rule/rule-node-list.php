<?php
/**
 *

 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<style>.tb2 th{ font-size: 12px;}</style>

<?php
    if(Yii::$app->getSession()->hasFlash('message')){
        echo "<script>alert('" . Yii::$app->getSession()->getFlash('message') . "');</script>";
    }
?>


<?php $form = ActiveForm::begin(['action' => Url::toRoute('rule/add-rule-node'), 'method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
规则名称：<input type="text" name="name" class="txt" style="width:120px;">&nbsp;
<input type="submit" value="创建节点" class="btn">
<?php $form = ActiveForm::end(); ?>

<?php $form = ActiveForm::begin(['action' => Url::toRoute('rule/add-rule-node-relation'), 'method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
规则节点id：<input type="text" name="node_id" class="txt" style="width:120px;">&nbsp;
父规则节点id：<input type="text" name="parent_id" class="txt" style="width:120px;">&nbsp;
父规则检验结果：<input type="text" name="parent_result" class="txt" style="width:120px;">&nbsp;
<input type="submit" value="添加结点关系" class="btn">
<?php $form = ActiveForm::end(); ?>

<?php $form = ActiveForm::begin(['action' => Url::toRoute('rule/add-rule-to-node'), 'method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
规则节点id：<input type="text" name="r_n_id" class="txt" style="width:120px;">&nbsp;
规则id：<input type="text" name="r_id" class="txt" style="width:120px;">&nbsp;
优先级id：<input type="text" name="order" class="txt" style="width:120px;">&nbsp;
<input type="submit" value="添加规则" class="btn">
<?php $form = ActiveForm::end(); ?>

        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>规则节点id</th>
                <th>规则名称</th>
                <td style="padding: 2px;margin-bottom: 1px;">
                    <table style="margin-bottom: 0px" class="table">
                            <tr class="header">
                                <th width="60">父规则节点id</th>
                                <th width="80">父规则节点结果</th>
                            </tr>
                    </table>
                </td>
                <th>状态</th>
                <td style="padding: 2px;margin-bottom: 1px;">
                    <table style="margin-bottom: 0px" class="table">
                            <tr class="header">
                                <th width="60">规则ID</th>
                                <th width="80">规则名称</th>
                                <th width="40">优先级</th>
                                <th width="40">状态</th>
                            </tr>
                    </table>
                </td>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value->id; ?></td>
                    <td><?php echo $value->name; ?></td>
                    <td style="padding: 2px;margin-bottom: 1px;">
                        <table style="margin-bottom: 0px" class="table">
                            <?php foreach ($value->rulerelation as $v): ?>
                                <tr class="hover">
                                    <td width="60"><?php echo $v->parent_id; ?></td>
                                    <td width="80"><?php echo $v->parent_result; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                    <th><?php echo $value->state; ?></th>
                    <td style="padding: 2px;margin-bottom: 1px;">
                        <table style="margin-bottom: 0px" class="table">
                            <?php foreach ($value->rules as $v): ?>
                                <tr class="hover">
                                    <td width="60"><?php echo $v->rule->id; ?></td>
                                    <td width="80"><?php echo $v->rule->name; ?></td>
                                    <th width="40"><?php echo $v->order; ?></th>
                                    <th width="40"><?php echo $v->state; ?></th>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>



