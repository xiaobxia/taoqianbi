<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="查询" class="btn">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
    &nbsp;&nbsp;[<a href="<?php echo Url::toRoute('/main/logout');?>">退出</a>]
    &nbsp;[<a href="<?php echo Url::toRoute('channel/channel-statistic-detail');?>">返回主页</a>]
<?php $form = ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>用户ID</th>
        <th>姓名</th>
        <th>手机号</th>
        <th>用户状态</th>
        <th>注册时间</th>
        <th>提交时间</th>
    </tr>
    <?php
    $page=1;
    if(isset($_GET['page'])){
        $page=intval(trim($_GET['page']));
    }
    $per_page=15;
    if(isset($_GET['per-page'])){
        $per_page=intval(trim($_GET['per-page']));
    }

    foreach ($info as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['name']; ?></td>
            <td><?php echo $value['phone']; ?></td>
            <td><?php echo isset($value['mess'])?$value['mess']:'--'; ?></td>
            <td><?php echo isset($value['register_time'])?date('Y-m-d H:i:s', $value['register_time']):'--'; ?></td>
            <td><?php echo isset($value['submit_time'])?date('Y-m-d H:i:s', $value['submit_time']):'--';; ?></td>
        </tr>
    <?php

    endforeach; ?>
</table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>