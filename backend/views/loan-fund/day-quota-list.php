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
echo $this->render('/loan-fund/submenus',['route'=>Yii::$app->controller->route]);
?>
<div class="">

    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>资方</th>
                <th>日期</th>
                <th>总限额</th>
                <th>剩余限额</th>
                <th>放款金额</th>
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
                    <td><?php echo $row['name']?></td>
                    <td><?php echo $row['date']?></td>
                    <td><?php echo sprintf("%0.2f",$row['quota']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['remaining_quota']/100)?></td>
                    <td><?php echo sprintf("%0.2f",$row['loan_amount']/100)?></td>
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
