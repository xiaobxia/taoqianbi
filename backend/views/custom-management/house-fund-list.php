<?php

use backend\components\widgets\LinkPager;
use common\models\AccumulationFund;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('service', 'menu_accumulation_fund_list');
$this->showsubmenu('公积金管理', array(
    array('公积金列表', Url::toRoute('custom-management/accumulation-fund-list'), 1),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['custom-management/accumulation-fund-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
    手机号:<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" maxlength="20" style="width:120px;">&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), AccumulationFund::$status, ['prompt' => '所有类型']); ?>&nbsp;
    更新时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_start', ''); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_end', ''); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>用户ID</th>
                <th>姓名</th>
                <th>公积金姓名</th>
                <th>手机号</th>
                <th>身份证号</th>
                <th>城市</th>
                <th>备注</th>
                <th>状态</th>
                <th>更新时间</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <?php
                    $real_name = '';
                    if ($value['status'] == AccumulationFund::STATUS_SUCCESS) {
                        $data_json = $value['data'];
                        if ($data_arr = json_decode($data_json, true)) {
                            $real_name = $data_arr['real_name'] ?? '';
                        }
                    }
                ?>
                <tr class="hover">
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['loanPerson']['name']; ?></td>
                    <td><?php echo $real_name; ?></td>
                    <td class="click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                    <td><?php echo $value['loanPerson']['id_number']; ?></td>
                    <td><?php echo $value['city']; ?></td>
                    <td><?php echo $value['message']; ?></td>
                    <td><?php echo AccumulationFund::$status[$value['status']]; ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['updated_at']); ?></td>
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