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


<?php $form = ActiveForm::begin(['action' => Url::toRoute('rule/add-rule-to-node'), 'method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
父规则节点id：<input type="text" name="parent_id" class="txt" style="width:120px;">&nbsp;
父规则检验结果：<input type="text" name="parent_result" class="txt" style="width:120px;">&nbsp;
<input type="submit" value="添加结点" class="btn">
<?php $form = ActiveForm::end(); ?>

        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>规则节点id</th>
                <th>规则详情</th>
            </tr>
            <th width="110px;" class="person">验证结果详情</th>
            <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
                <table style="margin-bottom: 0px" class="table">
                    <tr class="header">
                        <th>规则ID</th>
                        <th>规则名称</th>
                        <th>规则路径</th>
                        <th>描述</th>
                        <th>优先级</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>修改时间</th>
                    </tr>
                    <?php foreach ($node['rules'] as $value): ?>
                        <tr class="hover">
                            <td><?php echo $value->rule->id; ?></td>
                            <td><?php echo $value->rule->name; ?></td>
                            <td><?php echo strtolower($value->rule->url); ?></td>
                            <th><?php echo $value->rule->description; ?></th>
                            <th><?php echo $value->order; ?></th>
                            <th><?php echo $value->state; ?></th>
                            <th><?php echo $value->create_time; ?></th>
                            <th><?php echo $value->update_time; ?></th>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>



