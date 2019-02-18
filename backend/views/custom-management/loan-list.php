<?php

use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use common\models\KdbInfo;
use common\models\UserCreditReviewLog;
/**
 * @var backend\components\View $this
 */
$this->shownav('service', 'menu_user_credit_list');
$this->showsubmenu('用户额度信息');

?>
<style type="text/css">
th {border-right: 1px dotted #deeffb;}
</style>

<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
	用户ID：<input type="text" value="" name="id" class="txt" style="width:120px;"/>&nbsp;
	手机号：<input type="text" value="" name="phone" class="txt" style="width:120px;"/>&nbsp;
	姓名：<input type="text" value="" name="name" class="txt" style="width:120px;"/>&nbsp;
        <input type="submit" name="search_submit" value="搜索" class="btn"/>
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
		<th>零钱包利率(万分之)</th>
		<th>房租宝利率(百分之)</th>
		<th>分期商城利率(百分之)</th>
		<th>操作人/ID号</th>
		<th>操作</th>
	</tr>
    <?php if(!empty($loan_collection_list)):?>
    	<?php foreach ($loan_collection_list as $value): ?>
    	<tr class="hover" style="text-align: center;">
    		<td><?php echo $value['id']; ?></td>
    		<td><?php echo $value['name']; ?></td>
    		<td><?php echo $value['phone']; ?></td>
    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['amount'] / 100); ?></td>
    		<td><?php echo sprintf('%.2f', ($value['userCreditTotal']['amount'] - $value['userCreditTotal']['used_amount'] - $value['userCreditTotal']['locked_amount']) / 100); ?></td>
    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['used_amount'] / 100); ?></td>
    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['locked_amount'] / 100); ?></td>
    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['pocket_apr']); ?></td>

    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['house_apr']); ?></td>
    		<td><?php echo sprintf('%.2f', $value['userCreditTotal']['installment_apr']); ?></td>

    		<td><?php echo $value['userCreditTotal']['operator_name']; ?></td>
            <td>
    			<a href="<?php echo Url::toRoute(['user-info/amount-edit', 'id' => $value['id'],'list_type'=>'custom']);?>">编辑</a>
    		</td>
    	</tr>
    	<?php endforeach; ?>
    <?php endif;?>
</table>
<?php if (empty($loan_collection_list)): ?>
	<div class="no-result">暂无记录</div>
<?php endif; ?>
