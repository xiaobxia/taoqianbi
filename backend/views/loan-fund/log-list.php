<?php

use yii\helpers\Html;
use yii\grid\GridView;
use  common\models\fund\OrderFundInfo;
use common\helpers\Url;
use backend\components\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '日志信息';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <th>资方</th>
                <th>订单ID</th>
                <th>消息</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            <?php
            foreach ($rows as $row):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $row['id']?></td>
                    <td style="min-width: 100px;"><?php echo $row['name']?></td>
                    <td><?php echo $row['order_id']?></td>
                    <td style="word-break: break-all"><?php echo $row['content']?></td>
                    <td><?php echo date('Y-m-d H:i:s', $row['created_at'])?></td>
                    <td>
                    </td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $pagination]); ?>
</div>
