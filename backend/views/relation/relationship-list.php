<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/2/13
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use common\models\risk\UserRelationship;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;display:inline-block'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户id：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
    关系id：<input type="text" value="<?php echo Yii::$app->getRequest()->get('relation_id', ''); ?>" name="relation_id" class="txt" style="width:120px;">&nbsp;
    名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('value', ''); ?>" name="value1" class="txt" style="width:120px;">&nbsp;
    是否启用：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', -100), UserRelationship::$status); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
<a href="<?php echo Url::toRoute('relation/relationship-add'); ?>" style="float:right;margin-right: 40px;display: inline-block;height: 34px;width: 120px;background-color: #2473B2;color: #ffffff;line-height: 34px;font-size: 14px;text-align: center;border-radius: 8px;">添加</a>
<div style="clear: both;"></div>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>id</th>
                <th>用户id</th>
                <th>关系id</th>
                <th>名称</th>
                <th>是否启用</th>
                <th>备注</th>
                <th>操作</th>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <th><?php echo $value['user_id']; ?></th>
                    <th><?php echo $value['relation_id'] ?></th>
                    <th><?php echo $value['value']; ?></th>
                    <th><?php echo $value['status'] == 1?'是':'否'; ?></th>
                    <th><?php echo $value['message']; ?></th>
                   <th>
                       <?php if(UserRelationship::find()->where(['id'=>$value['id']])->one()):?>
                           <a href="<?php echo Url::toRoute(['relation/relationship-edit', 'id' => $value['id']]); ?>" style="color: #0099CC;display: inline-block;text-align: center;">编辑</a>
                       <?php endif;?>
                   </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script type="text/javascript">
    function to_more(id){
        window.location.href = <?php echo '"' . urldecode(Url::toRoute(['regression/regression-result-list', 'id' => '" + id'])); ?>";
    }
</script>
