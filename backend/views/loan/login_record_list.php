<?php

use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use common\models\LoanPerson;
use yii\helpers\Html;

$this->shownav('user', 'menu_today_login_user');
$this->showsubmenu('今日登录用户', array(
    array('列表', Url::toRoute('loan/login-list'), 1)
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>



<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['loan/login-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
联系人手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="mobile" class="txt" style="width:60px;">&nbsp;
联系人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:60px;">&nbsp;
用户类型：<?php echo Html::dropDownList('customer_type', Yii::$app->getRequest()->get('customer_type', ''), [-1=>'全部',1=>'老用户',0=>'新用户']); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php if(!empty(Yii::$app->request->get('user_id'))): ?>
    <input style="display: none" type="button" name="search_submit" value="导出到CVS" onclick="exportmobile(<?php echo Yii::$app->request->get('user_id'); ?>);" class="btn">
<?php endif;?>
<?php ActiveForm::end(); ?>
<form name="listform" method="get">
    <table class="tb fixpadding">
        <tr class="header">
            <th>用户ID</th>
            <th>用户姓名</th>
            <th>手机号</th>
            <th>来源渠道</th>
            <th>是否是老用户</th>
            <th>登录时间</th>
            <th>登录ip</th>
        </tr>
        <?php foreach ($login_list as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['user_id'] ?? ''; ?></td>
                <td class="td25"><?php echo $value['name'] ?? ''; ?></td>
                <td class="td25"><?php echo $value['phone'] ?? ''; ?></td>
                <td class="td25"><?php echo LoanPerson::$current_loan_source[$value['source_id']] ?? ''; ?></td>
                <td class="td25"><?php echo LoanPerson::$cunstomer_type[$value['customer_type']] ?? ''; ?></td>
                <td class="td25"><?php echo date('Y-m-d H:i:s',$value['created_at']) ?? ''; ?></td>
                <td class="td25"><?php echo $value['created_ip'] ?? ''; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <input type="checkbox" name="all_check">全选&nbsp;&nbsp;&nbsp;&nbsp;
    <?php if (empty($loan_mobile_contacts_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>

    <?php echo LinkPager::widget(['pagination' => $pages]); ?>
