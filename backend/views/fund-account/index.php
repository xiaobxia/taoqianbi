<?php

use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use yii\grid\GridView;
use common\models\fund\FundAccount;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '资金渠道';
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('/fund-account/submenus');
?>
<div class="loan-fund-index">

    <table class="tb tb2 fixpadding" id="order_list">
            <tr class="header">
                <th>ID</th>
                <th>名称</th>
        	    <th>状态</th>
                <th>账户类型</th>

            	<th>操作</th>
            </tr>
            <?php
            $models = $dataProvider->getModels();
            foreach ($models as $model):
                /* @var $model LoanFund */
                ?>
                <tr class="hover">
                    <td><?php echo $model->id?></td>
                    <td><?php echo $model->name?></td>
               		<td><?php echo FundAccount::STATUS_LIST[$model->status]?></td>
                    <td><?php echo FundAccount::ACCOUNT_TYPE[$model->account_type]?></td>

                   <td>
                        <a href="<?php echo Url::toRoute(['update', 'id' => $model->id]);?>">更新</a>
                    </td>
                </tr>

            <?php endforeach;?>
    </table>
</div>
<?php echo LinkPager::widget(['pagination' => $dataProvider->pagination]); ?>
</div>
