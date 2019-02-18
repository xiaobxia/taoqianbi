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
$this->showsubmenu('通话记录', array(
    array('列表', Url::toRoute('mobile-contacts/mobile-contacts-list'), 1),
    array('提交', Url::toRoute($_SERVER['HTTP_REFERER']), 1),
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>



<!-- <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['mobile-contacts/mobile-contacts-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
联系人手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('mobile', ''); ?>" name="mobile" class="txt" style="width:60px;">&nbsp;
联系人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:60px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn"> -->

    <table class="tb  fixpadding">
        <tr class="header">
            <th>用户ID</th>
            <th>用户姓名</th>
            <th>关系</th>
            <th>联系人手机</th>
            <th>通话次数</th>
        </tr>
        <?php foreach ($loan_mobile_contacts_list as $key=>$value): ?>
            <tr class="hover">
                <td class="td22"><?php echo $key; ?></td>
                <td class="td22"><?php echo $value['name']; ?></td>
                <td class="td22"><?php echo $value['relation']; ?></td>
                <td class="td22"><?php echo $value['phone']; ?></td>
                <td class="td22"><?php echo $value['times']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_mobile_contacts_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>

