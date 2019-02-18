<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('credit', 'menu_decision_tree_begin');
$this->showsubmenu('授信决策详情', array(
    array('授信决策列表', Url::toRoute('decision-tree/rule-report-list'), 1),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['decision-tree/rule-report-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('_id', ''); ?>" name="_id" class="txt" style="width:60px;">&nbsp;
    日期类型：<?php echo Html::dropDownList('timeType', Yii::$app->getRequest()->get('timeType', ''), ['created_at' => '新增时间', 'updated_at' => '更新时间'], ['updated_at' => '更新时间']); ?>&nbsp;
    日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('start_time', ''); ?>"  name="start_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_time', ''); ?>"  name="end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>用户ID</th>
                <th>basic_report/347</th>
                <th>新增时间</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['_id']; ?></td>
                    <td><?php
                        if (isset($value['basic_report']['347'])) {
                            echo json_encode($value['basic_report']['347'], JSON_UNESCAPED_UNICODE);
                        } ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['updated_at']); ?></td>
                        <td><a href="<?php echo Url::toRoute(['decision-tree/rule-report-view', '_id' => $value['_id']]);?>">查看详情</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>