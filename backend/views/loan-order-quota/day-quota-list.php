<?php

use yii\helpers\Html;
use yii\grid\GridView;
use  common\models\fund\OrderFundInfo;
use common\helpers\Url;
use backend\components\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '每日配额';
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('/loan-order-quota/submenus',['route'=>Yii::$app->controller->route]);
?>
<div class="">

    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>日期</th>
                <td>普通订单放款数</td>
                <td>公积金订单放款数</td>
                <td>第三方订单放款数</td>
                <td>老用户订单放款数</td>
                <th>创建时间</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
            <?php
            foreach ($rows as $row):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $row['id']?></td>
                    <td><?php echo $row['date']?></td>
                    <td><?php echo $row['norm_orders']?></td>
                    <td><?php echo $row['gjj_orders']?></td>
                    <td><?php echo $row['other_orders']?></td>
                    <td><?php echo $row['old_user_orders']?></td>
                    <td><?php echo date('Y-m-d H:i:s', $row['created_at'])?></td>
                    <td><?php echo date('Y-m-d H:i:s', $row['updated_at'])?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['update-day-quota','id'=>$row['id']]);?>" >更新</a>
                    </td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $pagination]); ?>
</div>
