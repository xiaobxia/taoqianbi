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


$this->shownav('user', 'menu_ygb_login_list');
$this->showsubmenu('日志管理', array(
    array('用户设备上报日志', Url::toRoute('log/ygb-login-log-list'), 0),
    array('公司操作日志', Url::toRoute('log/company-log-list'),0),
    array('用户操作日志', Url::toRoute('log/user-log-list'),0),
    array('管理员操作日志', Url::toRoute('log/admin-operator-log-list'),1),
	array('用户注册日志', Url::toRoute('log/user-register-info-list'),0),
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>



<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['admin-operator-log-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
操作人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('admin_id', ''); ?>" name="admin_id" class="txt" style="width:60px;">&nbsp;
操作action(如：staff-repay/reset-interest)：<input type="text" value="<?php echo Yii::$app->getRequest()->get('action', ''); ?>" name="action" class="txt">&nbsp;
操作额外id（如order_id|user_id）：<input type="text" value="<?php echo Yii::$app->getRequest()->get('extra_id', ''); ?>" name="extra_id" class="txt">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<form name="listform" method="get">
    <table class="tb  fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>用户姓名</th>
            <th  style="width:160px;">操作</th>
            <th>额外数据id</th>
            <th>记录时间</th>
            <th>备注</th>
            <th>数据</th>
        </tr>
        <?php foreach ($log as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td class="td25"><?php echo $value['admin_id']; ?></td>
                <td class="td25"><?php echo $value['admin_name']; ?></td>
                <td class="td25"><?php echo $value['action'].' | '.$value['title']; ?></td>
                <td class="td25"><?php echo $value['extra_id']; ?></td>
                <td class="td25"><?php echo date('Y-m-d H:i:s',$value['log_time']); ?></td>
                <td class="td25"><?php echo $value['note']; ?></td>
                <td class="td25"><?php echo $value['data']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($log)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
