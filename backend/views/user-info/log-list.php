<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\UserCreditLog;

$this->shownav('user', 'menu_credit_limit_log');
$this->showsubmenu('用户额度变化流水');
?>

    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:80px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    操作类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), UserCreditLog::$tradeTypes,array('prompt' => '-所有类型-')); ?>&nbsp;
    按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th rowspan="2">ID</th>
            <th rowspan="2">用户ID</th>
            <th rowspan="2">姓名</th>
            <th rowspan="2">手机号</th>
            <th rowspan="2">操作类型</th>
            <th rowspan="2">操作额度</th>
            <th colspan="3" style="text-align:center;">额度信息</th>
            <th rowspan="2">银行卡</th>
            <th rowspan="2">操作IP</th>
            <th rowspan="2">操作时间</th>
            <th rowspan="2">备注</th>
        </tr>
        <tr class="header">
            <th>总额度</th>
            <th>已使用额度</th>
            <th>锁定额度</th>
        </tr>
        <?php foreach ($loan_collection_list as $value): ?>

            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <th><?php echo $data1[$value['id']] ?></th>
                <th class="click-phone" data-phoneraw="<?php echo $data2[$value['id']]; ?>">--</th>
                <th><?php echo empty($value['type']) ? "" : UserCreditLog::$tradeTypes[$value['type']]; ?></th>
                <th><?php echo sprintf("%.2f",$value['operate_money'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['total_money'] / 100); ?></th>
                <th><?php echo sprintf("%.2f",$value['used_money'] / 100); ?></th>
	    	<th><?php echo sprintf("%.2f",$value['unabled_money'] / 100); ?></th>
                <td><?php echo $data3[$value['id']]; ?></td>
                <td><?php echo $value['created_ip']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                <td><?php echo $value['remark']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($loan_collection_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
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