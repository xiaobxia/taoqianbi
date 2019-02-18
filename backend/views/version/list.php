<?php
/**
 *

 */
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

$this->shownav('system', 'menu_version_config');
$this->showsubmenu('App升级管理', array(
    array('配置升级列表', Url::toRoute('list'), 1),
    array('添加新升级配置', Url::toRoute('add'), 0),
    array('添加版本级配置', Url::toRoute('list-rule'), 0),
));
?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>类型</th>
            <th>是否升级</th>
            <th>是否强制升级</th>
            <th>IOS最新版本</th>
            <th>Android最新版本</th>
            <th>操作</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value->id; ?></td>
                <td><?php echo $value->type; ?></td>
                <td><?php if($value->has_upgrade == 1){echo '升级';}elseif($value->has_upgrade == 0){ echo '不升级';} ?></td>
                <td><?php if($value->is_force_upgrade == 1){echo '强制升级';}elseif($value->has_upgrade == 0){ echo '不强制升级';} ?></td>
                <td><?php echo $value->new_ios_version; ?></td>
                <td><?php echo $value->new_version; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['edit', 'id' => $value->id]);?>">编辑</a>
                    <a class="delItem" href="javascript:void(0)" tip="<?php echo Url::toRoute(['del', 'id' => $value->id]);?>">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php ActiveForm::end(); ?>

<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    $('.delItem').click(function(){
        var url = $(this).attr('tip');
        if(confirm('确定删除该banner么？')) {
            window.location.href = url;
        }
    })
</script>
