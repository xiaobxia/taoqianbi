<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;

$this->shownav('system', 'menu_channel_list');
$this->showsubmenu('渠道管理', array(
    array('列表', Url::toRoute('channel-list'), 1),
    array('添加新渠道', Url::toRoute('channel-add'), 0),
));
?>

<?php ActiveForm::begin(['id' => 'listform']); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>ID</th>
        <th>中文名称</th>
        <th>英文名称</th>
        <th>渠道负责人</th>
        <th>渠道链接</th>
        <th>来源id</th>
        <!--        <th>是否扣量</th>-->
        <th>状态</th>
        <!--        <th>扣量折扣</th>-->
        <!--        <th>注册总数</th>-->
        <!--        <th>已扣量数</th>-->
        <th>借款显示</th>
        <th>创建时间</th>
        <th>操作</th>
    </tr>
    <?php foreach ($list as $value): ?>
        <tr class="hover">
            <td><?php echo $value->id; ?></td>
            <td><?php echo $value->name; ?></td>
            <td><?php echo $value->appMarket; ?></td>
            <td><?php echo $value->operator_name; ?></td>
            <td><?php echo $value->link; ?></td>
            <td><?php echo $value->source_id; ?></td>
            <!--            <td style="text-align:center;">--><?php //echo $value->is_withhold==1?'<font style="color:red;font-size:12px;">是</font>':'否'; ?><!--</td>-->
            <td><?php echo $value->status==1?'<span style="color:green;">启用</span>':'<span style="color:red;">停用</span>'; ?></td>
            <!--            <td style="text-align:center;">--><?php //echo $value->pv_rate; ?><!--折</td>-->
            <!--            <td style="text-align:center;">--><?php //echo $value->pre_total_pv; ?><!--</td>-->
            <!--            <td style="text-align:center;">--><?php //echo $value->pre_pv; ?><!--</td>-->
            <td><?php echo $value->loan_show==1?'<span style="color:green;">显示</span>':'<span style="color:red;">不显示</span>'; ?></td>
            <td><?php echo date('Y-m-d',$value->created_at); ?></td>
            <td><?php if($value->source_id!=21): ?>
                    <!--                <a href="--><?php //echo Url::toRoute(['channel-statistic-detail', 'role' => (array_key_exists($value->source_str, LoanPerson::$user_agent_source) ?? 'sdhb') ]);?><!--">推广详情</a>-->
                    <a href="<?php echo Url::toRoute(['channel-add', 'id' => $value->id]);?>">编辑</a>
                    <!--                <a class="delItem" href="javascript:void(0)" tip="--><?php //echo Url::toRoute(['channel-withhold', 'id' => $value->id]);?><!--">扣量</a>-->
                <?php endif; ?>
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
        if(confirm('您确定要对该渠道进行扣量，是否继续？')) {
            window.location.href = url;
        }
    })
</script>
