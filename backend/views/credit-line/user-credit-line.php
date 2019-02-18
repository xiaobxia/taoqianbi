<?php

use backend\components\widgets\LinkPager;
use common\models\AccumulationFund;
use common\models\LoanBlackList;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_credit_line');
$this->showsubmenu('授信额度详情', array(
    array('列表', Url::toRoute('credit-line/show-list'), 1),
));
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get', 'action' => ['credit-line/show-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id"
            class="txt" style="width:60px;">&nbsp;
手机号:<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt"
           maxlength="20" style="width:120px;">&nbsp;
更新日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_start', ''); ?>" name="add_start"
            onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_end', ''); ?>" name="add_end"
         onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>电话</th>
            <th>总额度</th>
            <th>基础额度</th>
            <th>公积金额度</th>
            <th>口袋记账额度</th>
            <th>更新时间</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td class="click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                <td><?php echo $value['credit_line']; ?></td>
                <td><?php echo $value['credit_line_base']; ?></td>
                <td><?php echo $value['credit_line_gjj']; ?></td>
                <td><?php echo $value['credit_line_kdjz']; ?></td>
                <td><?php echo $value['create_time']; ?></td>
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