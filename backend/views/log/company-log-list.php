<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\UserCompanyOperateLog;


$this->shownav('user', 'menu_company_log_list');
$this->showsubmenu('日志管理', array(
    array('用户设备上报日志', Url::toRoute('log/ygb-login-log-list'),0),
    array('公司操作日志', Url::toRoute('log/company-log-list'),1),
    array('用户操作日志', Url::toRoute('log/user-log-list'),0),
    array('管理员操作日志', Url::toRoute('log/admin-operator-log-list'),0),
	array('用户注册日志', Url::toRoute('log/user-register-info-list'),0),
));
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['log/company-log-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
公司ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('company_id', ''); ?>" name="company_id" class="txt" style="width:60px;">&nbsp;
公司名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('company_name', ''); ?>" name="company_name" class="txt" style="width:180px;">&nbsp;
操作类型：<?php echo Html::dropDownList('type',Yii::$app->getRequest()->get('type', ''),UserCompanyOperateLog::$type, ['prompt' => '所有类型']); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<form name="listform" method="get">
    <table class="tb  fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>公司ID</th>
            <th>公司名称</th>
            <th>操作类型</th>
            <th>审核人</th>
            <th>创建时间</th>
        </tr>
        <?php foreach ($log as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td class="td25"><?php echo $value['company_id']; ?></td>
                <td class="td25"><?php echo $value['company_name']; ?></td>
                <td class="td25"><?php echo empty($value['type']) ? "--" : UserCompanyOperateLog::$type[$value['type']]; ?></td>
                <td class="td25"><?php echo $value['operator_name']; ?></td>
                <td class="td25"><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($log)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
