<?php

use backend\components\widgets\LinkPager;
use common\models\UserVerification;
use yii\widgets\ActiveForm;
use common\models\User;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_user_verification_list');
$this->showsubmenu('用户认证列表');
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:70px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
   <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>用户ID</th>
                <th>用户姓名</th>
                <th>手机号码</th>
                <th>设置支付密码</th>
                <th>身份认证</th>
                <th>工作信息</th>
                <th>紧急联系人</th>
                <th>绑定银行卡</th>
                <th>芝麻信用</th>
                <th>支付宝</th>
                <th>信用卡添加</th>
                <th>聚信立</th>
                <th>认证更多</th>
                <th>淘宝</th>
                <th>京东</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['id']; ?></td>
                    <td class="td25"><?php echo $value['user_id']; ?></td>
                    <td class="td25"><?php echo $value['loanPerson']['name']; ?></td>
                    <td class="td25 click-phone" data-phoneraw="<?php echo $value['loanPerson']['phone']; ?>">--</td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_pay_pwd_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_verify_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_work_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_contact_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_bind_bank_card_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_zmxy_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_alipay_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_credit_card_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_jxl_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_more_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_taobao_status']]; ?></td>
                    <td class="td25"><?php echo UserVerification::$verification_status[$value['real_jd_status']]; ?></td>
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

