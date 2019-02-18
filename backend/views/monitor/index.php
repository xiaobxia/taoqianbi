<?php
use common\helpers\Url;
use yii\helpers\Html;
use yii\grid\GridView;
use common\models\Monitor;
use backend\components\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $searchModel common\models\MonitorSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->showsubmenu('监控', array(
    array('监控列表', Url::toRoute('monitor/index'), 1),
    array('新建监控', Url::toRoute('monitor/create'),0),
));

?>
<style>
    .clearfix:before,
    .clearfix:after {
        display: table;
        content: " ";
    }
    .clearfix:after {
        clear: both;
    }
    .form-group {
        float:left;
        margin-right: 10px;
    }
</style>
<div class="monitor-index">

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <table class="tb tb2 fixpadding" style="margin-top:20px;">
        <tr class="header">
            <th>ID</th>
            <th>类型</th>
            <th>名称</th>
            <!-- <th>配置</th> -->
            <th>当前数据</th>
            <th>检查间隔</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
            <?php
            $models = $dataProvider->getModels();
            foreach($models as $model):
                /* @var $model Monitor */
                ?>
        <tr class="hover">
            <td><?php echo $model->id?></td>
            <td><?php echo Monitor::TYPE_LIST[$model->type]?></td>
            <td><?php echo $model->name?></td>
            <!-- <td><pre><?php echo $model->config?></pre></td> -->
            <td><?php echo json_encode($model->getCheckData(), JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE)?></td>
            <td><?php echo $model->check_interval?></td>
            <td><?php echo Monitor::STATUS_LIST[$model->status]?></td>
            <td>
                <a href="<?php echo Url::toRoute(['monitor/view', 'id' => $model->id]);?>">查看</a>
                <a href="<?php echo Url::toRoute(['monitor/update', 'id' => $model->id]);?>">更新</a>
            </td>
        </tr>
            <?php
            endforeach;
            ?>
    </table>

    <?php echo LinkPager::widget(['pagination' => $dataProvider->getPagination()]); ?>
</div>
