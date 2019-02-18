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
	用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
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
        <th>手续费率</th>
		<th>操作人/ID号</th>
		<th>操作</th>
	</tr>
	<?php foreach ($loan_collection_list as $value): ?>
	<tr class="hover" style="text-align: center;">
		<td><?php echo $value['id']; ?></td>
		<td><?php echo $value['name']; ?></td>
		<td class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</td>
		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['amount'] / 100); ?></td>
		<td><?php echo sprintf('%.2f', ($value['userCreditTotal']['amount'] - $value['userCreditTotal']['used_amount'] - $value['userCreditTotal']['locked_amount']) / 100); ?></td>
		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['used_amount'] / 100); ?></td>
		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['locked_amount'] / 100); ?></td>
        <td><?php echo sprintf('%.2f', $value['userCreditTotal']['counter_fee_rate']) . ' %'; ?></td>
		<td><?php echo $value['userCreditTotal']['operator_name']; ?></td>
		<td>
			<a href="<?php echo Url::toRoute(['user-info/amount-edit', 'id' => $value['id']]);?>">编辑</a>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
<?php if (empty($loan_collection_list)): ?>
	<div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    /**
     * 电话显示*，点击后正常显示
     */
    (function initClickPhoneCol() {
        $('.click-phone').each(function () {
            var $item = $(this);
            var phone = $item.attr('data-phoneraw');
            if (phone && phone.length>5) {
                var phoneshow = phone.substr(0, 3) + '****' + phone.substr(phone.length - 2, 2);
                $item.attr('data-phoneshow', phoneshow);
                $item.text(phoneshow);
            } else {
                $item.attr('data-phoneshow', phone);
                $item.text(phone);
            }
        });
        $('.click-phone').one('click', function () {
            $(this).text($(this).attr('data-phoneraw'));
        })
    })();
</script>
