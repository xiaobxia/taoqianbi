<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Monitor;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Monitor */

$this->showsubmenu('监控', array(
    array('监控列表', Url::toRoute('monitor/index'), 0),
    array('新建监控', Url::toRoute('monitor/create'),0),
    array('更新', Url::toRoute(['monitor/update','id'=>$model->id]),0),
));

?>

<div class="monitor-view">

    <table class="tb tb2 fixpadding" id="creditreport">
        <tr><th class="partition" colspan="10">查看监控</th></tr>
        <tr>
            <td width="120">ID</td>
            <td><?php echo $model->id;?></td>
        </tr>
        <tr>
            <td>类型</td>
            <td><?php echo Monitor::TYPE_LIST[$model->type];?></td>
        </tr>
        <tr>
            <td>名称</td>
            <td><?php echo $model->name;?></td>
        </tr>
        <tr>
            <td>配置</td>
            <td><pre><?php echo $model->config;?></pre></td>
        </tr>
        <tr>
            <td>当前数据</td>
            <td><?php echo json_encode($model->getCheckData(), JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);?></td>
        </tr>
        <tr>
            <td>最近日志</td>
            <td><?php echo $model->recent_log;?></td>
        </tr>
        <tr>
            <td>检查间隔</td>
            <td><?php echo $model->check_interval;?></td>
        </tr>
        <tr>
            <td>状态</td>
            <td><?php echo Monitor::STATUS_LIST[$model->status];?></td>
        </tr>
        <tr>
            <td>最近检查时间</td>
            <td><?php echo $model->last_check_time ? date('Y-m-d H:i:s', $model->last_check_time) : '无';?></td>
        </tr>
        <tr>
            <td>下次检查时间</td>
            <td><?php echo $model->next_check_time ? date('Y-m-d H:i:s', $model->next_check_time) : '无';?></td>
        </tr>
        <tr>
            <td>最近通知时间</td>
            <td><?php echo $model->last_notify_time ? date('Y-m-d H:i:s', $model->last_notify_time) : '无';?></td>
        </tr>
        <tr>
            <td>创建时间</td>
            <td><?php echo $model->last_notify_time ? date('Y-m-d H:i:s', $model->created_at) : '无';?></td>
        </tr>
        <tr>
            <td>更新时间</td>
            <td><?php echo $model->last_notify_time ? date('Y-m-d H:i:s', $model->updated_at) : '无';?></td>
        </tr>
    </table>

</div>
