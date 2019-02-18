<?php

use common\models\UserLoginUploadLogType;
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
//    array('用户设备上报日志', Url::toRoute('log/ygb-login-log-list'), 1)
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>



<?php //$form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['log/ygb-login-log-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
<!--用户ID：<input type="text" value="--><?php //echo Yii::$app->getRequest()->get('user_id', ''); ?><!--" name="user_id" class="txt" style="width:60px;">&nbsp;-->
<!--设备标识：<input type="text" value="--><?php //echo Yii::$app->getRequest()->get('deviceId', ''); ?><!--" name="deviceId" class="txt" style="width:60px;">&nbsp;-->
<!--<input type="submit" name="search_submit" value="过滤" class="btn">-->
<form name="listform" method="get">
    <table class="tb  fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>用户姓名/手机</th>
            <th>设备标识</th>
            <th>经度</th>
            <th>纬度</th>
            <th>具体地址</th>
            <th>登录时间</th>
            <th>客户端类型</th>
            <th>手机系统版本</th>
            <th>APP版本</th>
            <th>手机型号</th>
            <th>应用市场</th>
            <th>数盟ID</th>
        </tr>
        <?php foreach ($user_login_upload_log as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td class="td25"><?php echo $value['loanPerson']['id']; ?></td>
                <td class="td25"><?php echo $value['loanPerson']['name']."/".$value['loanPerson']['phone']; ?></td>
                <td class="td25"><?php echo $value['deviceId']; ?></td>
                <td class="td25"><?php echo $value['longitude']; ?></td>
                <td class="td25"><?php echo $value['latitude']; ?></td>
                <td class="td25"><?php echo $value['address']; ?></td>
                <td class="td25"><?php echo date('Y-m-d H:i:s',$value['time']); ?></td>
                <td class="td25"><?php echo $value['clientType']; ?></td>
                <td class="td25"><?php echo $value['osVersion']; ?></td>
                <td class="td25"><?php echo $value['appVersion']; ?></td>
                <td class="td25"><?php echo $value['deviceName']; ?></td>
                <td class="td25"><?php echo $value['appMarket']; ?></td>
                <td class="td25"><?php echo $value->uploadLogType['szlm_id'] ?? ''; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($user_login_upload_log)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
