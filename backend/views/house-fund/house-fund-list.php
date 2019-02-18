<?php

use backend\components\widgets\LinkPager;
use common\models\AccumulationFund;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'loan_person_house_fund');
$this->showsubmenu('公积金管理列表', array(
    array('公积金列表', Url::toRoute('house-fund/house-fund-list'), 1),
));
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin([
    'id' => 'search_form',
    'method'=>'get',
    'action' => ['house-fund/house-fund-list'],
    'options' => ['style' => 'margin-top:5px;']
]); ?>
    ID：<input type="text" value="<?php echo \yii::$app->request->get('id', ''); ?>" name="id" class="txt" style="width:60px;" />&nbsp;
    用户ID：<input type="text" value="<?php echo \yii::$app->request->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;" />&nbsp;
    token:<input type="text" value="<?php echo \yii::$app->request->get('token', ''); ?>" name="token" class="txt" maxlength="20" style="width:120px;" />&nbsp;
    手机号:<input type="text" value="<?php echo \yii::$app->request->get('phone', ''); ?>" name="phone" class="txt" maxlength="20" style="width:120px;" />&nbsp;
    身份证号:<input type="text" value="<?php echo \yii::$app->request->get('id_card', ''); ?>" name="id_card" class="txt" maxlength="20" style="width:120px;" />&nbsp;
    来源：<input type="text" value="<?php echo \yii::$app->request->get('channel', ''); ?>" name="channel" class="txt" style="width:70px;" />&nbsp;
    状态：<?php echo Html::dropDownList('status', \yii::$app->request->get('status', ''), AccumulationFund::$status, ['prompt' => '所有类型']); ?>&nbsp;
    更新时间：<input type="text" value="<?php echo \yii::$app->request->get('add_start', ''); ?>" name="add_start"
                onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
    至：<input type="text" value="<?php echo \yii::$app->request->get('add_end', ''); ?>" name="add_end"
             onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;

    <input type="submit" name="search_submit" value="过滤" class="btn" />
<?php ActiveForm::end(); ?>

<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>

<?php else : ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>电话</th>
            <th>身份证号</th>
            <th>token</th>
            <th>城市</th>
            <th>来源</th>
            <th>备注</th>
            <th>状态</th>
            <th>更新时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td class="click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                <td><?php echo $value['loanPerson']['id_number']; ?></td>
                <td><?php echo $value['token']; ?></td>
                <td><?php echo $value['city']; ?></td>
                <td><?php echo $value['channel']; ?></td>
                <td><?php echo $value['message']; ?></td>
                <td><?php echo AccumulationFund::$status[$value['status']]; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['updated_at']); ?></td>
                <td><a href="<?php echo Url::toRoute(['house-fund/house-fund-view', 'id' => $value['id']]);?>">查看</a>
                    <a href="<?php echo Url::toRoute(['house-fund/house-fund-new', 'id' => $value['id']]);?>">重新获取公积金信息</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?>

<?php endif; ?>
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

