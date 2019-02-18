<?php

use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use yii\grid\GridView;
use common\models\fund\LoanFund;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '资金渠道';
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('/loan-order-quota/submenus');
?>
<div class="loan-fund-index">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <td>普通订单放款数（不包括老用户）</td>
                <td>公积金订单放款数</td>
                <td>第三方订单放款数</td>
                <td>老用户放款数</td>
                <td>更新时间</td>
                <th>操作</th>
            </tr>
            <?php
            foreach ($list as $model):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $model->id?></td>
                    <td><?php echo $model->norm_orders?></td>
                    <td><?php echo $model->gjj_orders?></td>
                    <td><?php echo $model->other_orders?></td>
                    <td><?php echo $model->old_user_orders?></td>
                    <td><?php echo date('Y-m-d H:i:s',$model->updated_at)?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['update', 'id' => $model->id]);?>">更新</a>
                    </td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
