<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('credit', 'menu_decision_tree_begin');
$this->showsubmenu('订单决策详情', array(
    array('订单决策列表', Url::toRoute('decision-tree/order-report-list'), 1),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['decision-tree/order-report-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
    订单ID:<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
    日期类型：<?php echo Html::dropDownList('timeType', Yii::$app->getRequest()->get('timeType', ''), ['created_at' => '新增时间', 'updated_at' => '更新时间'], ['updated_at' => '更新时间']); ?>&nbsp;
    日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('start_time', ''); ?>"  name="start_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_time', ''); ?>"  name="end_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>标识</th>
                <th>用户ID</th>
                <th>订单ID</th>
                <th>节点</th>
                <th>is_real</th>
                <th>basic_report</th>
                <th>新增时间</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['_id']; ?></td>
                    <td class="click-phone" data-phoneraw="<?php echo $value['identity']; ?>">--</td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['order_id']; ?></td>
                    <td><?php echo $value['root_ids']; ?></td>
                    <td><?php echo $value['is_real'] ? '是' : '否'; ?></td>
                    <td><?php
                        if (isset($value['basic_report'][$value['root_ids']])) {
                            echo json_encode($value['basic_report'][$value['root_ids']], JSON_UNESCAPED_UNICODE);
                        } ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['updated_at']); ?></td>
                        <td><a href="<?php echo Url::toRoute(['decision-tree/order-report-view', '_id' => $value['_id']->__toString()]);?>">查看详情</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
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