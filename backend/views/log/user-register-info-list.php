<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\LoanPerson;

$this->shownav('user', 'menu_ygb_login_list');
$this->showsubmenu('日志管理', array(
    array('用户设备上报日志', Url::toRoute('log/ygb-login-log-list'), 0),
    array('公司操作日志', Url::toRoute('log/company-log-list'),0),
    array('用户操作日志', Url::toRoute('log/user-log-list'),0),
    array('管理员操作日志', Url::toRoute('log/admin-operator-log-list'),0),
    array('用户注册日志', Url::toRoute('log/user-register-info-list'),1),
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<?php
$form = ActiveForm::begin([
    'id' => 'search_form',
    'method'=>'get',
    'action' => Url::toRoute(['log/user-register-info-list']),
    'options' => ['style' => 'margin-top:5px;'],
]); ?>
ID：<input type="text" value="<?php echo \yii::$app->request->get('id', ''); ?>" name="id" class="txt" style="width:60px;" />&nbsp;
用户ID：<input type="text" value="<?php echo \yii::$app->request->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;" />&nbsp;
设备类型：<input type="text" value="<?php echo \yii::$app->request->get('clientType', ''); ?>" name="clientType" class="txt" style="width:60px;" />&nbsp;
来源：<input type="text" value="<?php echo \yii::$app->request->get('source', ''); ?>" name="source" class="txt" style="width:60px;" />&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn" />

<table class="tb  fixpadding">
    <tr class="header">
        <th>ID</th>
        <th>用户ID</th>
        <th>设备类型</th>
        <th>系统版本</th>
        <th>APP版本</th>
        <th>手机型号</th>
        <th>应用市场</th>
        <th>设备标识</th>
        <th>时间</th>
        <th>注册来源</th>
    </tr>
    <?php foreach ($log as $value): ?>
        <tr class="hover">
            <td class="td25"><?php echo $value['id']; ?></td>
            <td class="td25"><?php echo $value['user_id']; ?></td>
            <td class="td25"><?php echo $value['clientType']; ?></td>
            <td class="td25"><?php echo $value['osVersion']; ?></td>
            <td class="td25"><?php echo $value['appVersion']; ?></td>
            <td class="td25"><?php echo $value['deviceName']; ?></td>
            <td class="td25"><?php echo $value['appMarket']; ?></td>
            <td class="td25"><?php echo $value['deviceId']; ?></td>
            <td class="td25"><?php echo $value['date']; ?></td>
            <td class="td25">
                <?php echo empty($value['source'])
                    ? '-'
                    : (array_key_exists($value['source'], LoanPerson::$person_source) ? LoanPerson::$person_source[$value['source']] : '-'); ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($log)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
