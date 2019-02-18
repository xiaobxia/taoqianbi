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
.tb2 th{ font-size: 12px;}
</style>
<title>每月分段逾期数据</title>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    月份：
    <input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM',alwaysUseStartDate:true,readOnly:true})"/>
       至<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M',dateFmt:'yyyy-MM',alwaysUseStartDate:true,readOnly:true})"/>
	<input type="submit" name="search_submit" value="过滤" class="btn"/>
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>月份</th>
                <th>7天待收本金/滞纳金</th>
                <th>14天待收本金/滞纳金</th>
                <th>7天逾期本金/滞纳金</th>
                <th>14天逾期本金/滞纳金</th>
                <th>7天/14天逾期本金m0阶段金额</th>
                <th>7天/14天逾期本金m1阶段金额</th>
                <th>7天/14天逾期本金m2阶段金额</th>
                <th>7天/14天逾期本金m3阶段金额</th>
                <th>7天/14天逾期本金m3+阶段金额</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $value): ?>
                    <tr>
                        <td><?php echo empty($value['month']) ? '--' : $value['month'];?></td>
                        <td><?php echo sprintf("%0.2f",$value['duein_principal_7']/100) . '/' . sprintf("%0.2f",$value['duein_latefee_7']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['duein_principal_14']/100) . '/' . sprintf("%0.2f",$value['duein_latefee_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_7']/100) . '/' . sprintf("%0.2f",$value['overdue_latefee_7']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_14']/100) . '/' . sprintf("%0.2f",$value['overdue_latefee_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_m0_7']/100) . '/' . sprintf("%0.2f",$value['overdue_principal_m0_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_m1_7']/100) . '/' . sprintf("%0.2f",$value['overdue_principal_m1_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_m2_7']/100) . '/' . sprintf("%0.2f",$value['overdue_principal_m2_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_m3_7']/100) . '/' . sprintf("%0.2f",$value['overdue_principal_m3_14']/100)?></td>
                        <td><?php echo sprintf("%0.2f",$value['overdue_principal_m3plus_7']/100) . '/' . sprintf("%0.2f",$value['overdue_principal_m3plus_14']/100)?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<div class="no-result" style="color: red;">备注：</div>
<div class="no-result">更新时间：月初自动更新上一月数据</div>
<div class="no-result">逾期m0阶段: 逾期天数<30天</div>
<div class="no-result">逾期m1阶段: 逾期天数≥30天&＜60天</div>
<div class="no-result">逾期m2阶段: 逾期天数≥60天&＜90天</div>
<div class="no-result">逾期m3阶段: 逾期天数≥90天&＜120天</div>
<div class="no-result">逾期m3+阶段: 逾期天数≥120天</div>
