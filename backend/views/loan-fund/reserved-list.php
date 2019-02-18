<?php

use yii\helpers\Html;
use yii\grid\GridView;

use common\helpers\Url;
use backend\components\widgets\LinkPager;

use common\models\fund\LoanFund;

$all_funds = LoanFund::getAllFundArray();

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '日志信息';
$this->params['breadcrumbs'][] = $this->title;

echo $this->render('/loan-fund/submenus',['route'=>Yii::$app->controller->route]);
?>
<div class="">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <th>资方</th>
                <th>日期</th>
                <th>预增加额度</th>
                <th>预减少额度</th>
                <th>操作</th>
            </tr>
  		<?php
            foreach ($rows as $row):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $row['id']?></td>
                    <td><?php echo !empty($all_funds[$row['fund_id']]) ? $all_funds[$row['fund_id']] :$fund_koudai->name; ?></td>

                    <td><?php echo $row['date']?></td>
                    <td style="word-break: break-all"><?php echo $row['incr_amount']/100?></td>
                    <td><?php echo $row['decr_amount']/100?></td>
                    <td><a href="<?php echo Url::toRoute(['/loan-fund/loan-fund-day-quota-update','id'=>$row['id']])?>">编辑</a></td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $pagination]); ?>
</div>

