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
echo $this->render('/loan-fund/submenus');
?>
<div class="loan-fund-index">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <th>名称</th>
                <th>每日默认限额</th>
                <th>今天余下额度</th>
                <th>状态</th>
                <th>创建时间</th>
                <th>优先级</th>
                <th>年化利率</th>
                <th>保证金率</th>
                <th>资方服务费率</th>
                <th>预签约类型</th>
                <th>放款主体</th>
                <th>扣款主体</th>
                <th>类型</th>
                <th>配额类型</th>
                <th>操作</th>
            </tr>
            <?php
            $models = $dataProvider->getModels();

            //print_r($models);

            //exit();



            foreach ($models as $model):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $model->id?></td>
                    <td><?php echo $model->name?></td>
                    <td><?php echo $model->day_quota_default/100?>元</td>
                    <td><?php echo $model->getTodayRemainingQouta()/100?>元</td>
                    <td><?php echo LoanFund::STATUS_LIST[$model->status]?></td>
                    <td><?php echo date('Y-m-d H:i:s', $model->created_at)?></td>
                    <td><?php echo $model->score?></td>
                    <td><?php echo $model->interest_rate?>%</td>
                    <td><?php echo !method_exists($model->getService(), 'getDeposit')?$model->deposit_rate.'%':'代码中配置'?></td>
                    <td><?php echo !method_exists($model->getService(), 'getFundServiceFee')?$model->fund_service_fee_rate.'%':'代码中配置'?></td>
                    <td><?php echo LoanFund::PRE_SIGN_LIST[$model->pre_sign_type]?></td>
                    <td><?php echo $model->payAccount?($model->payAccount->name):'无'?></td>
                    <td><?php echo $model->debitAccount?($model->debitAccount->name):'无'?></td>
                    <td><?php echo LoanFund::TYPE_LIST[$model->type]?></td>
                    <td><?php echo LoanFund::QUOTA_TYPE_LIST[$model->quota_type]?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['update', 'id' => $model->id]);?>">更新</a>
                    </td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $dataProvider->pagination]); ?>
</div>
