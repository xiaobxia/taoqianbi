<?php
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('content','menu_data_click_count_select');

$this->showsubmenu('运营数据', array(
    array('导流页访问统计', Url::toRoute('core-data/click-count-statistics'), 1),
    array('导流页访问详情', Url::toRoute('core-data/click-count-select'), 0),
));
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => 'get','options' => ['style' => 'margin-bottom:5px;']]); ?>
    标题：<input type="text" value="<?php echo Yii::$app->getRequest()->get('title', ''); ?><?php echo isset($search['title']) ? trim($search['title']) : ''; ?>" name="title" class="txt">&nbsp;
    开始日期：<input type="text" value="<?php echo isset($start_time) ? $start_time : ''; ?>"  name="start_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    结束日期：<input type="text" value="<?php echo isset($end_time) ? $end_time : ''; ?>"  name="end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
     <input type="submit" name="search_submit" value="过滤" class="btn">
     &nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php ActiveForm::end(); ?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>日期</th>
                <th>渠道标示</th>
                <th>数量统计</th>
            </tr>
            <?php foreach ($data as $value): ?>
                <tr class="hover">
                    <td><?php echo $value->date; ?></td>
                    <td><?php echo $value->title; ?></td>
                    <td><?php echo $value->total; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
<?php ActiveForm::end(); ?>

<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>
