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
use common\models\UserRepaymentPeriod;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
if(isset($type) && $type == "list"){
    $this->showsubmenu('房租宝贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/house-repay-list'), 1),
        array('还款审核', Url::toRoute('staff-repay/house-repay-trail-list'),0),
        array('扣款列表', Url::toRoute('staff-repay/house-repay-cut-list'),0)
    ));
} elseif(isset($type) && $type == "trail"){
    $this->showsubmenu('房租宝贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/house-repay-list'),0),
        array('还款审核', Url::toRoute('staff-repay/house-repay-trail-list'),1),
        array('扣款列表', Url::toRoute('staff-repay/house-repay-cut-list'),0)
    ));
}elseif(isset($type) && $type == "cut"){
    $this->showsubmenu('房租宝贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/house-repay-list'), 0),
        array('还款审核', Url::toRoute('staff-repay/house-repay-trail-list'),0),
        array('扣款列表', Url::toRoute('staff-repay/house-repay-cut-list'),1)
    ));
}
?>

<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
还款ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
总还款ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pid', ''); ?>" name="pid" class="txt" style="width:120px;">&nbsp;
借款人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
审核状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), UserRepaymentPeriod::$status, array('prompt' => '-所有状态-')); ?>&nbsp;
预期还款时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>还款ID</th>
                <th>总还款ID</th>
                <th>用户ID</th>
                <th>用户姓名</th>
                <th>用户手机</th>
                <th>预期还款金额/总金额(元)</th>
                <th>预期还款时间</th>
                <th>计划还滞纳金</th>
                <th>期数</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><a href="<?php echo Url::toRoute(['','pid'=>$value['repayment_id']]);?>"><?php echo $value['repayment_id']; ?></a></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['loanPerson']['name']; ?></td>
                    <th><?php echo $value['loanPerson']['phone']; ?></th>
                    <th><?php echo sprintf('%0.2f',$value['plan_repayment_money']/100); ?>/<?php echo  sprintf('%0.2f',$value['userRepayment']['repayment_amount']/100);?></th>
                    <th><?php echo date('Y-m-d',$value['plan_repayment_time']);?></th>
                    <th><?php echo sprintf('%0.2f',$value['plan_late_fee']/100); ?></th>
                    <th><?php echo $value['period'];?>/<?php echo $value['userRepayment']['period'];?></th>
                    <th><?php echo isset(UserRepaymentPeriod::$status[$value['status']])?UserRepaymentPeriod::$status[$value['status']]:'--'?></th>
                    <th>
                        <?php if($type == 'list'):?>
                            <a href="<?php echo Url::toRoute(['fzd-view', 'id' => $value['id']]);?>">查看</a>
                        <?php elseif($type == 'trail'):?>
                            <a href="<?php echo Url::toRoute(['fzd-trail', 'id' => $value['id']]);?>">审核</a>
                        <?php elseif($type == 'retrail'):?>
                            <a href="<?php echo Url::toRoute(['fzd-retrail', 'id' => $value['id']]);?>">审核</a>
                        <?php elseif($type == 'cut'):?>
                            <a href="<?php echo Url::toRoute(['fzd-cut', 'id' => $value['id']]);?>">扣款</a>
                        <?php endif;?>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>