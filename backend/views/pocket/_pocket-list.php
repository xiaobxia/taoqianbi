<?php
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanRecordPeriod;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
use common\models\fund\LoanFund;
?>
<style>
.tb2 th { font-size: 12px; }
form input.txt {
    width: 120px;
}
</style>
<?php $form = ActiveForm::begin(['method' => "get", 'action'=>Url::toRoute(['pocket/pocket-list']), 'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'],  ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="JavaScript">
    $(function () {
       $("#export_pocket").click(function () {
           url = $('#export_pocket_href').val() + "&status=" + $("[name=status]").val() + "&begintime=" + $("[name=begintime]").val()  + "&endtime=" + $("[name=endtime]").val() ;
           location.href = url;
       });
    });
</script>
<?php
    $default_time = date("Y-m-d", time()-14*86400);
    $time = (!empty(\yii::$app->request->get('time'))) ? \yii::$app->request->get('time') : $default_time;

    $now_date = date("Y-m-d", time());
    $plan_fee_time = (!empty(\yii::$app->request->get('plan_fee_time'))) ? \yii::$app->request->get('plan_fee_time') : $now_date;
?>
<?php if ($page_type == '2'):   //放款daily-data ?>
    <?php
        $hk_date = date("Y-m-d",strtotime($time)+6*86400);
    ?>
    <b style="color: red">放款日期：
        <input type="text" value="<?php echo $time; ?>" name="time" class="txt" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>&nbsp;
    </b>
    <b style="color: red">还款日期：<?php echo $hk_date;?></b></br></br>
<?php elseif($page_type == '3'):   ////还款daily-loan-data ?>
    <?php
        $fk_date = date("Y-m-d",strtotime($plan_fee_time)-7*86400);
        if($data_list){
            if(count($data_list)>0){
                $fk_date = date("Y-m-d",$data_list[0]['re_created_at']);
            }
        }
    ?>
    <b style="color: red">放款日期：<?php echo $fk_date;?></b>&nbsp;
    <b style="color: red">还款日期：</b>
        <input type="text" value="<?php echo $plan_fee_time; ?>" name="plan_fee_time" class="txt" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:true})"/></br></br>
<?php else: ////借款管理-->借款列表?>
<?php endif ?>
用户ID：<input type="text" value="<?php echo \yii::$app->request->get('uid', ''); ?>" name="uid" class="txt" />&nbsp;
姓名：<input type="text" value="<?php echo \yii::$app->request->get('name', ''); ?>" name="name" class="txt" />&nbsp;
订单号：<input type="text" value="<?php echo \yii::$app->request->get('id', ''); ?>" name="id" class="txt" />&nbsp;
手机号：<input type="text" value="<?php echo \yii::$app->request->get('phone', ''); ?>" name="phone" class="txt" />&nbsp;
公司名称：<input type="text" value="<?php echo \yii::$app->request->get('company_name', ''); ?>" name="company_name" class="txt" />&nbsp;
审核状态：<?php echo Html::dropDownList('status', \yii::$app->request->get('status', ''), UserLoanOrder::$status); ?>
老用户：<?php echo Html::dropDownList('old_user', \yii::$app->request->get('old_user', ''), [0=>'全部',1=>'是',-1=>'否']); ?>&nbsp;
是否在白名单：<?php echo Html::dropDownList('is_white', \yii::$app->request->get('is_white', ''), [-1=>'全部',1=>'是',2=>'否']); ?>
只看公积金：<?php echo Html::dropDownList('is_gjj', \yii::$app->request->get('is_gjj', ''), [0=>'否',1=>'是']); ?>&nbsp;
渠道：<?php echo Html::dropDownList('source_id', \yii::$app->request->get('source_id', ''), [0=>'全部']+LoanPerson::$current_loan_source); ?>&nbsp;
<br />
申请时间：<input type="text" value="<?php echo \yii::$app->request->get('begintime', ''); ?>" name="begintime" class="txt" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
至&nbsp;<input type="text" value="<?php echo \yii::$app->request->get('endtime', ''); ?>" name="endtime" class="txt" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
<?php if ($page_type == '2'):   //放款daily-data ?>
    <?php
        $min_date = date("Y-m-d 00:00:00", strtotime($time));
        $max_date = date("Y-m-d 23:59:59", strtotime($time));
    ?>
<?php elseif($page_type == '3'):   ////还款daily-loan-data ?>
    <?php
        $min_date = date("Y-m-d 00:00:00", strtotime($plan_fee_time));
        $max_date = date("Y-m-d 23:59:59", strtotime($plan_fee_time));
    ?>
<?php else: ////借款管理-->借款列表?>
    放款时间：<input type="text" value="<?php echo \yii::$app->request->get('begintime2', ''); ?>" name="begintime2" class="txt" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
    至&nbsp;<input type="text" value="<?php echo \yii::$app->request->get('endtime2', ''); ?>" name="endtime2" class="txt" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
<?php endif ?>
申请金额：<input type="text" value="<?php echo \yii::$app->request->get('amount_min', ''); ?>" name="amount_min" class="txt" placeholder="申请金额下限" />&nbsp;
 至&nbsp;<input type="text" value="<?php echo \yii::$app->request->get('amount_max', ''); ?>" name="amount_max" class="txt" placeholder="申请金额上限" />

<input type="submit" name="search_submit" value="过滤" class="btn" />
<input type="hidden" value="<?php echo $page_type ?>" name="page_type">&nbsp;&nbsp;&nbsp;
<input type="hidden" value="<?php echo Url::toRoute('pocket/export-pocket-list'); ?>" id="export_pocket_href">
<!--<a id = 'export_pocket' href="javascript:void(0);" target="_blank" class="btn">导出</a>-->
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<label><input type="checkbox" name="cache" value="1" <?php if (\yii::$app->request->get('cache')==1): ?> checked <?php endif;?> class="btn" />去除缓存</label>
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>订单号/用户ID/姓名</th>
        <th>手机号</th>
        <th>是否是老用户</th>
        <th>是否在白名单</th>
        <th>借款金额(元)</th>
        <th>抵扣券金额(元)</th>
<!--        <th>借款项目</th>-->
        <th>借款期限</th>
        <th>公司名称</th>
        <?php if ($channel!=1) : ?>
        <th>申请来源</th>
        <?php endif; ?>
        <th>申请时间</th>
        <th>放款时间</th>
        <th>还款时间</th>
<!--        <th>子类型</th>-->
        <th>状态</th>
        <th>资方</th>
        <th>来源</th>
        <th>渠道</th>
        <th>操作</th>
    </tr>

    <?php foreach ($data_list as $value): ?>
    <tr class="hover">
        <td><?php echo $value['id']; ?>/<?php echo $value['user_id']; ?>/<?php echo $value['name']; ?></td>
        <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
        <th><?php echo isset(LoanPerson::$cunstomer_type[$value['customer_type']])?LoanPerson::$cunstomer_type[$value['customer_type']]:""; ?></th>
        <th><?php echo isset(UserLoanOrder::$is_black_type[$value['creditJsqb']['is_white']])?UserLoanOrder::$is_black_type[$value['creditJsqb']['is_white']]:"否"; ?></th>
        <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
        <td><?php echo (!empty($value['re_coupon_money'])) ? sprintf("%0.2f",$value['re_coupon_money']/100) : "---"; ?></td>
<!--        <th>--><?//= isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?><!--</th>-->
        <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
        <th><?php echo $value['company_name'] ?></th>
        <th><?php echo $value['reg_app_market']?></th>
        <th><?php echo empty($value['order_time']) ? '--' : date('Y-m-d H:i:s',$value['order_time']); ?></th>
        <th><?php echo empty($value['re_created_at']) ? '--' : date('Y-m-d H:i:s',$value['re_created_at']); ?></th>
        <th><?php echo empty($value['true_repayment_time'])?'--':date('Y-m-d H:i:s',$value['true_repayment_time']); ?></th>
<!--        <th>--><?//= UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?><!--</th>-->
        <th><?php echo isset($status_data[$value['id']])?$status_data[$value['id']]:""; ?></th>
        <th><?php echo $value['fund_name'] ? $value['fund_name'] : '无';?></th>
        <th><?php echo isset($value['source_id']) && isset(LoanPerson::$person_source[$value['source_id']]) ? LoanPerson::$person_source[$value['source_id']] : '-'?></th>
        <th><?php echo isset($value['sub_order_type']) ? UserLoanOrder::$sub_order_type[$value['sub_order_type']] : '-'?></th>
        <th>
            <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>
            <?php if($value['status'] == UserLoanOrder::STATUS_CHECK && $value['auto_risk_check_status']!=1 && $channel!=1){?>
                <a onclick="if(confirmMsg('确定要跳过机审吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['check-status', 'id' => $value['id']]);?>">跳过机审</a>
            <?php }?>
            <?php if($value['sub_order_type']==UserLoanOrder::SUB_TYPE_RONG360 && $value['status'] == UserLoanOrder::STATUS_REVIEW_PASS){?>
                <a onclick="if(confirmMsg('确定要取消订单吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['cancel-rong-order', 'id' => $value['id']]);?>">取消订单</a>
            <?php }?>
            <?php if($value['status'] == 3 || $value['status'] == 5 || $value['status'] == 9){?>
                <a href="<?= Url::toRoute(['pocket/daikou', 'id' => $value['id']]);?>">强制还款</a>
            <?php }?>
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
