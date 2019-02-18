<?php
/**
 *

 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\ContentActivity;

$this->shownav('content', 'menu_operate_activity_list');
$this->showsubmenu('公告中心', array(
    array('公告列表', Url::toRoute('content-activity/list'), 1),
    array('公告添加', Url::toRoute('content-activity/add'), 0),
));

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => 'get','options' => ['style' => 'margin-bottom:5px;']]); ?>
    ID：<input type="text" value="<?php echo isset($search['id']) ? trim($search['id']) : ''; ?>" name="id" class="txt">&nbsp;
    标题：<input type="text" value="<?php echo isset($search['title']) ? trim($search['title']) : ''; ?>" name="title" class="txt">&nbsp;
    场景：<?php echo Html::dropDownList('use_case', Yii::$app->getRequest()->get('use_case', ''), ContentActivity::$use_case, array('prompt' => '-所有场景-')); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), ContentActivity::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
    开始日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('start_time', ''); ?>"  name="start_time" onfocus="WdatePicker({startDate:'%y-%M-%d 00:00:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    结束日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_time', ''); ?>"  name="end_time" onfocus="WdatePicker({startDate:'%y-%M-%d 23:59:59',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true})">&nbsp;
     <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>标题</th>
                <th>有效期</th>
                <th>banner缩略图</th>
                <th>创建人</th>
                <th>创建时间</th>
                <th>修改时间</th>
                <th>场景</th>
                <th>状态</th>
                <th>点击次数</th>
                <th>操作</th>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value->id; ?></td>
                    <td><?php echo $value->title; ?></td>
                    <td><?php echo date('Y-m-d H:i', $value->start_time) ."~".date('Y-m-d H:i', $value->end_time); ?></td>
                    <td><img src="<?php echo $value->banner; ?>" width="80"/></td>
                    <td><?php echo $value->user_admin; ?></td>
                    <td><?php echo date('Y-m-d H:i', $value->created_at) ?></td>
                    <td><?php echo date('Y-m-d H:i', $value->updated_at) ?></td>
                    <td><?php echo ContentActivity::$use_case[$value->use_case]; ?></td>
                    <td><?php echo ContentActivity::$status[$value->status]; ?></td>
                    <td><?php echo $value->count; ?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['edit', 'id' => $value->id]);?>">编辑</a>
                        <a href="<?php echo Url::toRoute(['update', 'id' => $value->id]);?>">更新状态</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
<?php ActiveForm::end(); ?>

<?php if (empty($data_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>
