<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRecord;
use common\models\LoanRepayment;


$this->shownav('user', 'loan_person_mobile_contacts');
$this->showsubmenu('用户手机短信息', array(
    array('列表', Url::toRoute('mobile-contacts/phone-message-list'), 1)
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>



<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => '', 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
短信手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:60px;">&nbsp;
短信内容：<input type="text" value="<?php echo Yii::$app->getRequest()->get('message_content', ''); ?>" name="message_content" class="txt" style="width:60px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="get">
    <table class="tb  fixpadding">
        <tr class="header">
            <th>用户ID</th>
            <th>短信发送手机号</th>
            <th>短信内容</th>
            <th>短信发送日期</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo $value['message_content']; ?></td>
                <td><?php echo $value['message_date']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
