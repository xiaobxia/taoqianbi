<?php
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
$this->shownav('loan', 'menu_loan_lqb_reject_list');
$this->showsubmenu('借款拒绝列表');
?>
<style>
    .tb2 th { font-size: 12px; }
    form input.txt {
        width: 120px;
    }
</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
用户ID：<input type="text" value="<?php echo \yii::$app->request->get('uid', ''); ?>" name="uid" class="txt" />&nbsp;
姓名：<input type="text" value="<?php echo \yii::$app->request->get('name', ''); ?>" name="name" class="txt" />&nbsp;
手机号：<input type="text" value="<?php echo \yii::$app->request->get('phone', ''); ?>" name="phone" class="txt" />&nbsp;
公司名称：<input type="text" value="<?php echo \yii::$app->request->get('company_name', ''); ?>" name="company_name" class="txt" />&nbsp;
老用户：<?php echo Html::dropDownList('old_user', \yii::$app->request->get('old_user', ''), [0=>'全部',1=>'是',-1=>'否']); ?>&nbsp;
<br />
订单号：<input type="text" value="<?php echo \yii::$app->request->get('id', ''); ?>" name="id" class="txt" />&nbsp;
审核状态：<?php echo Html::dropDownList('status', \yii::$app->request->get('status', ''), [-3 => '初审驳回']); ?>&nbsp;
申请时间：<input type="text" value="<?php echo \yii::$app->request->get('begintime', ''); ?>" name="begintime" class="txt"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />&nbsp;
至&nbsp;<input type="text" value="<?php echo \yii::$app->request->get('endtime', ''); ?>" name="endtime" class="txt"
              onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />&nbsp;
申请金额：<input type="text" value="<?php echo \yii::$app->request->get('amount_min', ''); ?>" name="amount_min" class="txt" placeholder="申请金额下限" />&nbsp;
至&nbsp;<input type="text" value="<?php echo \yii::$app->request->get('amount_max', ''); ?>" name="amount_max" class="txt" placeholder="申请金额上限" />

<input type="submit" name="search_submit" value="过滤" class="btn" />
&nbsp;&nbsp;&nbsp;
<label><input type="checkbox" name="cache" value="1" <?php if (\yii::$app->request->get('cache')==1): ?> checked <?php endif;?> class="btn" />去除缓存</label>
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>订单号/用户ID/姓名</th>
        <th>手机号</th>
        <th>是否是老用户</th>
        <th>借款金额(元)</th>
        <th>借款期限</th>
        <th>公司名称</th>
        <?php if ($channel!=1) : ?>
            <th>申请来源</th>
        <?php endif; ?>
        <th>申请时间</th>
        <th>状态</th>
        <th>拒绝节点</th>
        <th>拒绝详情</th>
        <th>资方</th>
        <th>操作</th>
    </tr>

    <?php foreach ($data_list as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?>/<?php echo $value['user_id']; ?>/<?php echo $value['name']; ?></td>
            <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
            <th><?php echo isset(LoanPerson::$cunstomer_type[$value['customer_type']])?LoanPerson::$cunstomer_type[$value['customer_type']]:""; ?></th>
            <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
            <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
            <th><?php echo $value['company_name'] ?></th>
            <?php if ($channel!=1) : ?>
                <th><?php echo "---" ?></th>
            <?php endif; ?>
            <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
            <th><?php echo isset($status_data[$value['id']])?$status_data[$value['id']]:""; ?></th>
            <th><?php echo $value['reject_roots'] ?? ""; ?></th>
            <th width="300px"><?php echo $value['reject_detail'] ?? ""; ?></th>
            <th><?php echo $value['fund_name'] ? $value['fund_name'] : '无';?></th>
            <th>
                <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>
            </th>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($data_list)): ?>
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
