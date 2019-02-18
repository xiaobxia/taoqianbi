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
use common\models\LoanBlackList;

$this->shownav('data_analysis', 'menu_data_report_list');
?>
<style>
    .table-b table td{border:0px solid #F00}
</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<div class="table-b">
    <table width="1200" height="600" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="400">
                <b style="display: block;color: red">用户统计</b>
                <table class="tb tb2">
                    <tr class="header">
                        <th>总用户数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['all_reg_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>全要素认证用户数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['all_verif_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>绑卡用户总数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['bind_card_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>运营商认证总数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['yys_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                </table>
            </td>
            <td width="400">
                <b style="display: block;color: red">&nbsp;</b>
                <table class="tb tb2" >
                    <tr class="header">
                        <th>今日注册用户数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['reg_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>实名认证用户总数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['realname_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>芝麻认证用户总数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['zmxy_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>紧急联系人认证总数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['contacts_list_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                </table>
            </td>

        </tr>
        <tr>
            <td width="400">

                <b style="display: block;color: red">放款统计</b>
                <table class="tb tb2" >
                    <tr class="header">
                        <th>累计放款金额:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['all_loan_money']/100);?></td>
                        <td style="text-align:center">元</td>
                    </tr>
                    <tr class="header">
                        <th>当日放款金额:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['loan_money']/100);?></td>
                        <td style="text-align:center">元</td>
                    </tr>
                    <tr class="header">
                        <th>放款中总金额:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['pay_money']/100);?></td>
                        <td style="text-align:center">元</td>
                    </tr>
                    <tr class="header">
                        <th>放款失败金额:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['fail_money']/100);?></td>
                        <td style="text-align:center">元</td>
                    </tr>
                </table>
            </td>
            <td width="400">
                <b style="display: block;color: red">&nbsp;</b>
                <table class="tb tb2" >
                    <tr class="header">
                        <th>累计放款笔数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['all_loan_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>
                    <tr class="header">
                        <th>当日放款笔数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['loan_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>
                    <tr class="header">
                        <th>放款中笔数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['pay_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>
                    <tr class="header">
                        <th>放款失败笔数:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['fail_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td width="400">
                <b style="display: block;color: red">风控统计</b>
                <table class="tb tb2" >
<!--                    <tr class="header">-->
<!--                        <th>机审累计订单数:</th>-->
<!--                        <td style="text-align:center">--><?//= $all_reg_num['all_check_num'];?><!--</td>-->
<!--                        <td style="text-align:center">笔</td>-->
<!--                    </tr>-->
<!--                    <tr class="header">-->
<!--                        <th>今日机审订单数:</th>-->
<!--                        <td style="text-align:center">--><?//= isset($all_reg_num['today_check_num'])?$all_reg_num['today_check_num']:0;?><!--</td>-->
<!--                        <td style="text-align:center">笔</td>-->
<!--                    </tr>-->
                    <tr class="header">
                        <th>今日老用户申请:</th>
                        <td style="text-align:center"><?php echo isset($all_reg_num['today_old_apply_num'])?number_format($all_reg_num['today_old_apply_num']):0;?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>今日新用户申请:</th>
                        <td style="text-align:center"><?php echo isset($all_reg_num['today_new_apply_num'])?number_format($all_reg_num['today_new_apply_num']):0;?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>今日老用户已审:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['today_old_check_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>
                    <tr class="header">
                        <th>今日新用户已审:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['today_new_check_num']);?></td>
                        <td style="text-align:center">笔</td>
                    </tr>

                </table>
            </td>
            <td width="400">
                <b style="display: block;color: red">&nbsp;</b>
                <table class="tb tb2" >
<!--                    <tr class="header">-->
<!--                        <th>机审通过累计订单数:</th>-->
<!--                        <td style="text-align:center">--><?//= $all_reg_num['pas_check_num'];?><!--</td>-->
<!--                        <td style="text-align:center">笔</td>-->
<!--                    </tr>-->
<!--                    <tr class="header">-->
<!--                        <th>今日机审通过订单数:</th>-->
<!--                        <td style="text-align:center">--><?//= $all_reg_num['pas_today_check_num'];?><!--</td>-->
<!--                        <td style="text-align:center">笔</td>-->
<!--                    </tr>-->
                    <tr class="header">
                        <th>今日老用户放款:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['pas_old_check_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>今日新用户放款:</th>
                        <td style="text-align:center"><?php echo number_format($all_reg_num['pas_new_check_num']);?></td>
                        <td style="text-align:center">人</td>
                    </tr>
                    <tr class="header">
                        <th>&nbsp;</th>
                        <td style="text-align:center">&nbsp;</td>
                        <td style="text-align:center">&nbsp;</td>
                    </tr>
                    <tr class="header">
                        <th>&nbsp;</th>
                        <td style="text-align:center">&nbsp;</td>
                        <td style="text-align:center">&nbsp;</td>
                    </tr>
                </table>
            </td>
            <td width="400">
                <b style="display: block;color: red">&nbsp;</b>
                <table class="tb tb2" >
<!--                    <tr class="header">-->
<!--                        <th>机审订单通过率:</th>-->
<!--                        <td style="text-align:center">--><?//=empty($all_reg_num['all_check_num'])?0:sprintf("%0.2f", $all_reg_num['pas_check_num']/$all_reg_num['all_check_num']*100);?><!--</td>-->
<!--                        <td style="text-align:center">%</td>-->
<!--                    </tr>-->
<!--                    <tr class="header">-->
<!--                        <th>今日机审通过率:</th>-->
<!--                        <td style="text-align:center">--><?//=empty($all_reg_num['today_check_num'])?0:sprintf("%0.2f", $all_reg_num['pas_today_check_num']/$all_reg_num['today_check_num']*100);?><!--</td>-->
<!--                        <td style="text-align:center">%</td>-->
<!--                    </tr>-->
                    <tr class="header">
                        <th>今日老用户通过率:</th>
                        <td style="text-align:center"><?php echo empty($all_reg_num['today_old_apply_num'])?0:sprintf("%0.2f", $all_reg_num['pas_old_check_num']/$all_reg_num['today_old_apply_num']*100);?></td>
                        <td style="text-align:center">%</td>
                    </tr>
                    <tr class="header">
                        <th>今日新用户通过率:</th>
                        <td style="text-align:center"><?php echo empty($all_reg_num['today_new_apply_num'])?0:sprintf("%0.2f", $all_reg_num['pas_new_check_num']/$all_reg_num['today_new_apply_num']*100);?></td>
                        <td style="text-align:center">%</td>
                    </tr>
                    <tr class="header">
                        <th>&nbsp;</th>
                        <td style="text-align:center">&nbsp;</td>
                        <td style="text-align:center">&nbsp;</td>
                    </tr>
                    <tr class="header">
                        <th>&nbsp;</th>
                        <td style="text-align:center">&nbsp;</td>
                        <td style="text-align:center">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <?php $form = ActiveForm::end(); ?>
<table>
    <br>
    <br>
    <br>
    <tr class="hover"> 用户统计 ：从平台第一天上线到今天为止的所有用户进行统计（总数）</tr>
    <br>
    <br>
    <tr class="hover"> 放款统计（放款时间统计） ：从平台第一天上线到今天为止的所有放款单数、金额进行统计（总数）</tr>
    <br>
    <br>
    <tr class="hover"> 放款统计（放款时间统计） ：从今天零时开始截止当前时间的放款单数、金额进行统计（当日）</tr>
    <br>
    <br>
    <tr class="hover"> 风控统计（机审时间统计）：从平台第一天上线到今天为止的所有机审单数进行统计（总数） </tr>
    <br>
    <br>
    <tr class="hover"> 风控统计（机审时间统计）：从今天零时开始截止当前时间的机审单数进行统计（当日） </tr>
    <br>
    <br>
    <tr class="hover"> 今日机审通过率：今日机审通过订单数/ 今日机审订单数</tr>
    <br>

</table>


