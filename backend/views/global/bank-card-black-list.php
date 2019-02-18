<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */

$this->shownav('system', 'menu_global_card_list');
$this->showsubmenu('列表', array(
    array('列表', Url::toRoute('global/bank-card-black-list'), 1),
    array('添加', Url::toRoute('global/bank-card-black-add'), 0)
));
?>


<?php ActiveForm::begin(['id' => 'listform']); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>logo</th>
        <th>银行</th>
        <th>维护时间</th>
        <th>提示信息</th>
        <th>操作</th>
    </tr>
    <?php foreach ($data_list as $value): ?>
        <tr class="hover">
            <td><img src="<?php echo $value['url']; ?>" ></td>
            <td><?php echo $value['bank_name']; ?></td>
            <?php if (isset($value['begin_time'])): ?>
            <td><?php echo date('Y-m-d H:i:s', $value['begin_time']) ?> ~ <?php echo date('Y-m-d H:i:s', $value['end_time']) ?></td>
            <td><?php echo $value['remark'] ?></td>
            <?php endif; ?>
            <td>
                <a href="<?php echo Url::toRoute(['bank-card-black-edit', 'bank_id' =>$value['bank_id']]); ?>">编辑</a>
                <a href="<?php echo Url::toRoute(['bank-card-black-del', 'bank_id' => $value['bank_id'] ]); ?>">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php ActiveForm::end(); ?>

<?php if (empty($data_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
