
<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('system', 'menu_channel_list');

$this->showsubmenu('渠道推广详情<p>统计为实时更新</p>', $role);
?>
<style type="text/css">
    .itemtitle p{ display:inline;font-weight:normal;color:#999;padding-left:10px;}
</style>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
申请时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
&nbsp;&nbsp;[<a href="<?php echo Url::toRoute('/main/logout');?>">退出</a>]
&nbsp;[<a href="<?php echo Url::toRoute('channel/channel-statistic-detail');?>">返回主页</a>]
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>序列号</th>
        <th>渠道名称</th>
        <th>统计日期</th>
        <th>注册数</th>
        <?php
        if (isset($info[0]) && ($info[0]['loan_show'] == 1) ){
            echo "<th>申请数</th>
        <th>借款数</th>";
        }
        ?>
        <th>最后一次更新时间</th>
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
    $i=1+($page-1)*$per_page;
    foreach ($info as $value): ?>
        <tr class="hover">
            <td><?php echo $i; ?></td>
            <td><?php echo isset($value['name'])?$value['name']:""; ?></td>
            <td><?php echo isset($value['time'])?date('Y-m-d',$value['created_at']):""; ?></td>
            <td><a href="<?php echo Url::toRoute(['channel/register-list', 'date'=> $value['time'],'channel_id'=>$value['parent_id']]) ?>"><?php echo isset($value['pv'])?$value['pv']:0; ?></a></td>
            <?php
            if ($value['loan_show'] == 1){
                echo "<td>".(isset($value['apply_all'])?$value['apply_all']:'无')."</td>
                <td>".(isset($value['loan_all'])?$value['loan_all']:'无')."</td>";
            }
            ?>
            <td><?php echo isset($value['time'])?date('Y-m-d H:i:s',$value['updated_at']):""; ?></td>
        </tr>
    <?php
    ++$i;
    endforeach; ?>
</table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
