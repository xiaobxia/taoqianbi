<?php

use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use common\models\KdbInfo;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_credit_limit_list');
$this->showsubmenu('用户额度信息');

?>
    <style type="text/css">
        th {border-right: 1px dotted #deeffb;}
    </style>

<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>


    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>用户ID</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>总额度</th>
            <th>剩余额度</th>
            <th>已使用额度</th>
            <th>锁定额度</th>
            <th>操作人/ID号</th>
            <th>操作</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['name']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo sprintf('%.2f', $value['amount'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', ($value['amount'] - $value['used_amount'] - $value['locked_amount']) / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['used_amount'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['locked_amount'] / 100); ?></td>
                <td><?php echo $value['operator_name']; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['house-fund/house-fund-view', 'id' => $value['house_fund_id']]);?>" target="_blank">查看公积金</a>
                    <a href="<?php echo Url::toRoute(['user-info/amount-wait-edit', 'id' => $value['user_id']]);?>">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>