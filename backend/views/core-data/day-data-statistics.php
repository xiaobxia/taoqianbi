<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

?>

<style>
    table th{text-align: center}
    table td{text-align: center}
</style>
<title>每日到期还款续借率</title>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：
    <input type="text" value="<?php echo empty(Yii::$app->getRequest()->get('begin_created_at', '')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('begin_created_at');?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d ',dateFmt:'yyyy-MM-dd ',alwaysUseStartDate:true,readOnly:true})"/>
       至<input type="text" value="<?php echo empty(Yii::$app->getRequest()->get('end_created_at', '')) ? date("Y-m-d", time()+86400*2) : Yii::$app->request->get('end_created_at'); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M-%d ',dateFmt:'yyyy-MM-dd ',alwaysUseStartDate:true,readOnly:true})"/>
	<input type="submit" name="search_submit" value="过滤" class="btn"/>&nbsp;
更新时间：<?php echo $update_time;?>
<?php $form = ActiveForm::end(); ?>

    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th colspan="1" style="text-align:center;border-right:1px solid #A9A9A9;"></th>
            <th colspan="7" style="text-align:center;border-right:1px solid #A9A9A9;">所有用户</th>
            <th colspan="7" style="color:blue;text-align: center;border-right:1px solid blue;">新用户</th>
            <th colspan="7" style="color:red;text-align: center;border-right:1px solid red;">老用户</th>
        </tr>
        <tr class="header">
            <th>日期</th>
            <th>到期单数</th>
            <th>到期金额</th>
            <th>还款单数</th>
            <th>还款金额</th>
            <th>续借单数</th>
            <th>续借金额</th>
            <th style="border-right:1px solid #A9A9A9;">续借率</th>
            <th style="color:blue;">到期单数</th>
            <th style="color:blue;">到期金额</th>
            <th style="color:blue;">还款单数</th>
            <th style="color:blue;">还款金额</th>
            <th style="color:blue;">续借单数</th>
            <th style="color:blue;">续借金额</th>
            <th style="color:blue;border-right:1px solid blue;">续借率</th>
            <th style="color:red;">到期单数</th>
            <th style="color:red;">到期金额</th>
            <th style="color:red;">还款单数</th>
            <th style="color:red;">还款金额</th>
            <th style="color:red;">续借单数</th>
            <th style="color:red;">续借金额</th>
            <th style="color:red;border-right:1px solid red;">续借率</th>
        </tr>
        <?php if(!empty($info)):?>
            <?php foreach ($info as $date=> $value): ?>
                <tr>
                    <td><?php echo $date;?></td>
                    <td><?php echo empty($value['expire_num_0'])?'--':$value['expire_num_0'];?></td>
                    <td><?php echo empty($value['expire_money_0'])?'--':sprintf("%0.2f",$value['expire_money_0']/100);?></td>
                    <td><?php echo empty($value['repay_zc_num_0'])?'--':$value['repay_zc_num_0'];?></td>
                    <td><?php echo empty($value['repay_zc_money_0'])?'--':sprintf("%0.2f",$value['repay_zc_money_0']/100);?></td>
                    <td><?php echo empty($value['repay_zcxj_num_0'])?'--':$value['repay_zcxj_num_0'];?></td>
                    <td><?php echo empty($value['repay_zcxj_money_0'])?'--':sprintf("%0.2f",$value['repay_zcxj_money_0']/100);?></td>
                    <td style="border-right:1px solid #A9A9A9;"><?php echo empty($value['zcxj_rate_0'])?'--':$value['zcxj_rate_0']*100;?>%</td>
                    <td style="color:blue;"><?php echo empty($value['expire_num_1'])?'--':$value['expire_num_1'];?></td>
                    <td style="color:blue;"><?php echo empty($value['expire_money_1'])?'--':sprintf("%0.2f",$value['expire_money_1']/100);?></td>
                    <td style="color:blue;"><?php echo empty($value['repay_zc_num_1'])?'--':$value['repay_zc_num_1'];?></td>
                    <td style="color:blue;"><?php echo empty($value['repay_zc_money_1'])?'--':sprintf("%0.2f",$value['repay_zc_money_1']/100);?></td>
                    <td style="color:blue;"><?php echo empty($value['repay_zcxj_num_1'])?'--':$value['repay_zcxj_num_1'];?></td>
                    <td style="color:blue;"><?php echo empty($value['repay_zcxj_money_1'])?'--':sprintf("%0.2f",$value['repay_zcxj_money_1']/100);?></td>
                    <td style="color:blue;border-right:1px solid blue;"><?php echo empty($value['zcxj_rate_1'])?'--':$value['zcxj_rate_1']*100;?>%</td>
                    <td style="color:red;"><?php echo empty($value['expire_num_2'])?'--':$value['expire_num_2'];?></td>
                    <td style="color:red;"><?php echo empty($value['expire_money_2'])?'--':sprintf("%0.2f",$value['expire_money_2']/100);?></td>
                    <td style="color:red;"><?php echo empty($value['repay_zc_num_2'])?'--':$value['repay_zc_num_2'];?></td>
                    <td style="color:red;"><?php echo empty($value['repay_zc_money_2'])?'--':sprintf("%0.2f",$value['repay_zc_money_2']/100);?></td>
                    <td style="color:red;"><?php echo empty($value['repay_zcxj_num_2'])?'--':$value['repay_zcxj_num_2'];?></td>
                    <td style="color:red;"><?php echo empty($value['repay_zcxj_money_2'])?'--':sprintf("%0.2f",$value['repay_zcxj_money_2']/100);?></td>
                    <td style="color:red;border-right:1px solid red;"><?php echo empty($value['zcxj_rate_2'])?'--':$value['zcxj_rate_2']*100;?>%</td>
                </tr>
            <?php endforeach; ?>
        <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php //echo LinkPager::widget(['pagination' => $pages]); ?>
<br>
<br>
<p>备注：</p>
<p>到期单数：当日到期订单数</p>
<p>到期金额：当日到期金额</p>
<p>还款单数：当日到期还款单数(正常还款 未逾期)</p>
<p>还款金额：正常还款金额</p>
<p>续借单数：正常还款后当日的续借单数(成功)</p>
<p>续借金额：正常还款后当日的续借金额</p>
<p>续借率：续借单数/还款单数</p>
<p>当天至14天以后的数据10分钟更新一次</p>
<p>7天前15分钟更新一次</p>
<p>7-30天以前的数据2小时更新一次</p>
<p>30-120天以前的数据一天更新一次（每天凌晨3点更新）</p>