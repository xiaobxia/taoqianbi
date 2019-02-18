<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/12/01
 * Time: 10:40
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserLoanOrderRepayment;
use common\models\LoanPerson;
use common\models\fund\LoanFund;
$this->shownav('financial', 'menu_financial_day_notyet_principal_account');
$this->showsubmenu('每日未还本金对账');
?>

<style>
.tb2 th{ font-size: 12px;}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
    至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
    &nbsp;&nbsp;资方<?php echo Html::dropDownList('fund_id', Yii::$app->getRequest()->get('fund_id', ''), LoanFund::$loan_source); ?>
    <input type="hidden" name="operate_type" value="search"/>
    <input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;
    <input style="display: none" type="submit" name="submitcsv" value="导出"onclick="$(this).val('exportcsv');return true;"  class="btn"/>
    最后更新时间：<?php echo $update_time;?>(每小时更新一次)
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>放款日期</th>
                <th>借款本金</th>
                <th>实际打款金额</th>
                <th>实际已还金额</th>
                <th>未还本金</th>
                <th>正常未还本金</th>
                <th>未还利息</th>
                <th>未还滞纳金</th>
                <th>S1</th>
                <th>S2</th>
                <th>M1</th>
                <th>M2</th>
                <th>M3</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $key=> $item): ?>
                    <tr>
                        <td>
                        <?php if($key==='sub_total'):?>
                            <font color="red"><?php echo $item['loantime'];?></font>
                        <?php else:?>
                            <?php echo $item['loantime'];?>
                        <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['total_principal'])):?>
                                    --
                                <?php elseif($key==='sub_total'):?>
                                   <font color="red"><?php echo sprintf("%0.2f",$item['total_principal']/100); ?></font>
                                <?php else:?>
                                    <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'total_principal']); ?>"><?php echo sprintf("%0.2f",$item['total_principal']/100); ?></a>
                            <?php endif;?>
                        </td>
                        <td>
                        <?php if(empty($item['true_loan_money'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                               <font color="red"><?php echo sprintf("%0.2f",$item['true_loan_money']/100); ?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'true_loan_money']); ?>"><?php echo sprintf("%0.2f",$item['true_loan_money']/100); ?></a>
                        <?php endif;?>
                        </td>
                        <td>
                        <?php if(empty($item['true_total_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                               <font color="red"><?php echo sprintf("%0.2f",$item['true_total_principal']/100); ?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'true_total_principal','status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE]); ?>"><?php echo sprintf("%0.2f",$item['true_total_principal']/100); ?></a>
                        <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['not_yet_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                               <font color="red"><?php echo sprintf("%0.2f",$item['not_yet_principal']/100); ?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'not_yet_principal']); ?>"><?php echo sprintf("%0.2f",$item['not_yet_principal']/100); ?></a>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['not_yet_normal_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                               <font color="red"> <?php echo sprintf("%0.2f",$item['not_yet_normal_principal']/100); ?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'not_yet_normal_principal']); ?>"><?php echo sprintf("%0.2f",$item['not_yet_normal_principal']/100); ?></a>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['interests'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                               <font color="red"> <?php echo sprintf("%0.2f",$item['interests']/100); ?></font>
                            <?php else:?>
                                <font> <?php echo sprintf("%0.2f",$item['interests']/100); ?></font>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['late_fee'])):?>
                                --
                             <?php elseif($key==='sub_total'):?>
                               <font color="red"> <?php echo sprintf("%0.2f",$item['late_fee']/100); ?></font>
                             <?php else:?>
                               <font> <?php echo sprintf("%0.2f",$item['late_fee']/100); ?></font>
                             <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['s1_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                                <font color="red"><?php echo sprintf("%0.2f",$item['s1_principal']/100);?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'s1_principal','is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES]); ?>"><?php echo sprintf("%0.2f",$item['s1_principal']/100);?></a>
                            <?php endif;?>
                        </td>
                        <td>
                             <?php if(empty($item['s2_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                                <font color="red"><?php echo sprintf("%0.2f",$item['s2_principal']/100);?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'s2_principal','is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES]); ?>"><?php echo sprintf("%0.2f",$item['s2_principal']/100);?></a>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['s3_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                                <font color="red"><?php echo sprintf("%0.2f",$item['s3_principal']/100);?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'s3_principal','is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES]); ?>"><?php echo sprintf("%0.2f",$item['s3_principal']/100);?></a>
                            <?php endif;?>
                        </td>
                        <td>
                             <?php if(empty($item['s4_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                                <font color="red"><?php echo sprintf("%0.2f",$item['s4_principal']/100);?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'s4_principal','is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES]); ?>"><?php echo sprintf("%0.2f",$item['s4_principal']/100);?></a>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(empty($item['s5_principal'])):?>
                                --
                            <?php elseif($key==='sub_total'):?>
                                <font color="red"><?php echo sprintf("%0.2f",$item['s5_principal']/100);?></font>
                            <?php else:?>
                                <a href="<?php echo Url::toRoute(['staff-repay/pocket-repay-account','operate_date'=>$item['loantime'],'view_type'=>'s5_principal','is_overdue'=>UserLoanOrderRepayment::OVERDUE_YES]); ?>"><?php echo sprintf("%0.2f",$item['s5_principal']/100);?></a>
                            <?php endif;?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<div>
    <p>借款本金：用户申请借款且打款成功的总金额（包含服务费）</p>
    <p>实际打款金额：平台实际放款金额（借款本金-服务费）</p>
    <p>实际已还金额：借款用户实际还款的本金金额（包含服务费和滞纳金）</p>
    <p>未还本金：借款本金 - 实际已还本金</p>
    <p>正常未还本金：未逾期的待还本金</p>
    <p>S1：逾期1-10天的待还款本金</p>
    <p>S2：逾期11-30天的待还款本金</p>
    <p>M1：逾期31-60天的待还款本金</p>
    <p>M2：逾期61-90天的待还款本金</p>
    <p>M3：逾期91-120天的待还款本金</p>
</div>



