<?php
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\LoanTask;
use common\helpers\Url;

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>.tb2 th{ font-size: 12px;}</style>

<?php $form = ActiveForm::begin([
        'method' => "get",
        'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'],
        'id' => 'form'
]); ?>

待认证：<?php echo Html::dropDownList('user_status_no_ver', Yii::$app->request->get('user_status_no_ver', ''), [
    1 => '待认证',
], ['prompt' => '-所有状态-']); ?>&nbsp;&nbsp;
待申请：<?php echo Html::dropDownList('user_status_wait', Yii::$app->request->get('user_status_wait', ''), [
        1 => '待申请',
], ['prompt' => '-所有状态-']); ?>&nbsp;&nbsp;
借款中：<?php echo Html::dropDownList('user_status_loan', Yii::$app->request->get('user_status_loan', ''), [
        1 => '借款中',
], ['prompt' => '-所有状态-']); ?>&nbsp;&nbsp;
逾期中：<?php echo Html::dropDownList('user_status_over', Yii::$app->request->get('user_status_over', ''), [
        1 => '逾期中',
], ['prompt' => '-所有状态-']); ?>&nbsp;&nbsp;
黑名单：<?php echo Html::dropDownList('user_status_black', Yii::$app->request->get('user_status_black', ''), [
        1 => '黑名单',
], ['prompt' => '-所有状态-']); ?>&nbsp;&nbsp;
已认证：<?php echo Html::dropDownList('user_status_is_ver', Yii::$app->request->get('user_status_is_ver', ''), [
        1 => '已认证',
], ['prompt' => '-不限-']); ?>&nbsp;&nbsp;<br><br>
成功借款次数：<?php echo Html::dropDownList('order_num', Yii::$app->request->get('order_num', ''), [
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => '8+'
], ['prompt' => '-不限-']); ?>&nbsp;&nbsp;
用户来源：<?php echo Html::dropDownList('source_id', Yii::$app->request->get('source_id', ''), LoanPerson::$current_loan_source, ['prompt' => '-所有来源-']); ?>&nbsp;
是否绑定微信：<?php echo Html::dropDownList('weixin', Yii::$app->request->get('weixin', ''), [
    1 => '绑定', // 绑定
    2 => '未绑定', // 未绑定
], ['prompt' => '-不限-']); ?>&nbsp;&nbsp;
性别：<?php echo Html::dropDownList('sex', Yii::$app->request->get('sex', ''), [
    '男' => '男',
    '女' => '女'
], ['prompt' => '-不限-']); ?>&nbsp;&nbsp;
<!--籍贯：<?/*= Html::dropDownList('verication_status', Yii::$app->request->get('type', ''), LoanPerson::$person_type, ['prompt' => '-所有类型-']); */?>&nbsp;&nbsp;-->
年龄：<?php echo Html::dropDownList('age', Yii::$app->request->get('age', ''), $age_list, ['prompt' => '-不限-']); ?>&nbsp;&nbsp;
曾经借款被拒：<?php echo Html::dropDownList('refuse', Yii::$app->request->get('refuse', ''), LoanPerson::$search_status, ['prompt' => '-不限-']); ?>&nbsp;<br><br>

注册时间：<input type="text" value="<?php echo Yii::$app->request->get('reg_begintime', ''); ?>" name="reg_begintime"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
-- <input type="text" value="<?php echo Yii::$app->request->get('reg_endtime', ''); ?>" name="reg_endtime"
        onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />&nbsp;


最后一次放款时间：<input value="<?php echo Yii::$app->request->get('last_begintime', ''); ?>" type="text"  name="last_begintime"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
-- <input type="text" value="<?php echo Yii::$app->request->get('last_endtime', ''); ?>" name="last_endtime"
        onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />

任务名称：<input value="" type="text"  name="task_name" />
<input type="submit" name="search_submit" value="生成任务" class="btn" /> &nbsp;
<!-- &nbsp;&nbsp;<input type="submit" id="export" name="submitcsv" value="exportcsv" class="btn"> -->
<label class="label" style="display: none">正在查询...</label>
<?php $form = ActiveForm::end(); ?>
<hr>
<br>
<?php $form = ActiveForm::begin(["id" => "add-quan-user-form", "method" => 'post','action' => Url::toRoute(['loan/public-import','type'=>'4']), 'options' => ['enctype' => 'multipart/form-data']]); ?>
导入csv/txt：<?php echo Html::fileInput('file'); ?><label style="color:red;">(备注：txt文件中手机号以逗号分隔)</label>
任务名称：<input value="" type="text"  name="task_name" />
<input type="submit" value="导入" class="btn"><br>
<?php ActiveForm::end(); ?>
<br>
<hr>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <!-- <th>查询结果：</th> -->
        <th>ID</th>
        <th>任务名称</th>
        <!-- <th>文件路径</th> -->
        <th>状态</th>
        <th>创建时间</th>
        <th>更新时间</th>
        <th>操作</th>
    </tr>

    <?php foreach ($taskList as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <th><?php echo $value['name']; ?></th>
            <!-- <th><?php echo $value['file']; ?></th> -->

            <th><?php echo isset(LoanTask::$status[$value['status']])? LoanTask::$status[$value['status']]:""; ?></th>

            <th><?php echo date("Y-m-d H:i",$value['created_at']); ?></th>
            <th><?php echo date("Y-m-d H:i",$value['updated_at']); ?></th>
            <td>
                <!-- <a href="JavaScript:;" onclick="delBlackList(<?php echo $value['id'];?>)">删除</a> -->
            <?php if(!empty($value['file']) ):?>
        		<a href="<?php echo Url::toRoute(['loan/download','id'=> $value['id']])?>">下载</a> |
            <?php endif;?>

                <a href="<?php echo Url::toRoute(['loan/publish-task','id'=> $value['id']])?>">重新执行</a>
                <?php if(!empty($value['file']) ):?>
                    | <a href="<?php echo Url::toRoute(['loan/public-list','id'=> $value['id']])?>">生成公共集合</a>
                <?php endif;?>
                <?php if (isset($public[$value['id']])) { ?>
                    | <a href="<?php echo Url::toRoute(['loan/delete-public-list','id'=> $public[$value['id']]])?>">删除当前公共集合</a>
                <?php } ?>
            </td>

        </tr>
    <?php endforeach; ?>

</table>

<script>
    $(function() {
        $('.btn').on('click', function(){
            $('.btn').css('display', 'none');
            $('.label').show();
        });
    })
</script>
