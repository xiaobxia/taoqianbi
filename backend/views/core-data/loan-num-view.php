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
use common\models\LoanRecordPeriod;
use common\models\LoanPerson;
use common\models\fund\LoanFund;
use common\models\User;
use common\models\UserLoanOrder;
$this->showsubmenu('每日放款数据', array(
    array('借款列表', Url::toRoute('core-data/loan-num-view'), 1),
));
?>

    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    订单号：<input type="text" value="<?php echo \yii::$app->request->get('order_id', ''); ?>" name="order_id"  />&nbsp;
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    用户姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    注册时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
      <table class="tb tb2 fixpadding">
          <tr class="header">
              <th>订单号</th>
              <th>用户ID</th>
              <th>姓名</th>
              <th>手机号</th>
              <th>是否是老用户</th>
              <th>借款金额(元)</th>
              <th>借款项目</th>
              <th>借款期限</th>
              <th>申请时间</th>
              <th>子类型</th>
              <th>状态</th>
              <th>操作</th>
          </tr>

          <?php foreach ($data_list as $value): ?>
              <tr class="hover">
                  <td><?php echo $value['id']; ?></td>
                  <td><?php echo $value['user_id']; ?></td>
                  <td><?php echo $value['name']; ?></td>
                  <th><?php echo $value['phone']; ?></th>
                  <th><?php echo isset(LoanPerson::$cunstomer_type[$value['customer_type']])?LoanPerson::$cunstomer_type[$value['customer_type']]:""; ?></th>
                  <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
                  <th><?php echo isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?></th>
                  <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
                  <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
                  <th><?php echo UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?></th>
                  <th><?php echo isset($status_data[$value['status']])?$status_data[$value['status']]:""; ?></th>
                  <th>
                      <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>
                      <?php if($value['status'] == UserLoanOrder::STATUS_CHECK && $value['auto_risk_check_status']!=1 && $channel!=1){?>
                          <a onclick="if(confirmMsg('确定要跳过机审吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['check-status', 'id' => $value['id']]);?>">跳过机审</a>
                      <?php }?>
                      <?php if($value['sub_order_type']==UserLoanOrder::SUB_TYPE_RONG360 && $value['status'] == UserLoanOrder::STATUS_REVIEW_PASS){?>
                          <a onclick="if(confirmMsg('确定要取消订单吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['cancel-rong-order', 'id' => $value['id']]);?>">取消订单</a>
                      <?php }?>
                  </th>
              </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
