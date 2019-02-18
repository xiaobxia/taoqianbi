<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\TaskList;
?>
    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    创建人：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_by', ''); ?>" name="created_by" class="txt" style="width:120px;">&nbsp;
    任务标题：<input type="text" value="<?php echo Yii::$app->getRequest()->get('title', ''); ?>" name="title" class="txt" style="width:120px;">&nbsp;
    任务状态<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), TaskList::$status); ?>&nbsp;
    任务类型<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('type', ''), TaskList::$type); ?>&nbsp;
    创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_start', ''); ?>" name="created_at_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_end', ''); ?>"  name="created_at_end" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>任务id</th>
            <th>任务标题</th>
            <th>状态</th>
            <th>类型</th>
            <th>创建人</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($data_list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['title']; ?></td>
                <td><?php echo isset(TaskList::$status[$value['status']])?TaskList::$status[$value['status']]:"--"; ?></td>
                <td><?php echo isset(TaskList::$type[$value['type']])?TaskList::$type[$value['type']]:"--"; ?></td>
                <td><?php echo $value['created_by']; ?></td>
                <th><?php echo date('Y-m-d H:i:s',$value['created_at']); ; ?></th>
                <td>
                    <a href="<?php echo Url::toRoute(['task/order-detail-view', 'id' => $value['id']]); ?>">查看</a>
                    <?php if (TaskList::STATUS_FINISH == $value['status']): ?>
                        <a href="<?php echo $value['down_url']; ?>">下载</a>
                    <?php endif; ?>

                    </td>
            </tr>

        <?php endforeach; ?>
    </table>
<?php if (empty($data_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>