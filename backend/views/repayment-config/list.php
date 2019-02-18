<?php

use common\helpers\Url;
use yii\widgets\LinkPager;
use common\models\LoanBlackFlow;

$this->shownav('content', 'menu_operate_repayment_info');
$this->showsubmenu('还款配置', array(
    array('还款比例列表', Url::toRoute('repayment-config/list'), 1),
    array('还款配置添加', Url::toRoute('repayment-config/add'), 0),
));
?>
<table class="tb tb2 fixpadding">
    <tr class="msg_push_list_table_header header">
        <th>ID</th>
        <th>还款比例</th>
        <th>最小还款限额</th>
        <th>操作</th>
    </tr>
    <?php foreach ($data as $val): ?>
        <tr class="hover">
            <td class="tb25"><?php echo $val->id; ?></td>
            <td class="tb25"><?php echo $val->percent; ?></td>
            <td class="tb25"><?php echo sprintf("%0.2f", $val->max / 100); ?></td>
            <td class="tb25">
                <a href="<?php echo Url::toRoute(['repayment-config/add', 'id' => $val->id]); ?>">编辑</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>