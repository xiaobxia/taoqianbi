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
use common\models\FinancialReconcillationRecord;
$this->shownav('financial', 'menu_financial_day_reconciliation_list');
$this->showsubmenu('对账列表');
?>

<style>
.tb2 th{ font-size: 12px;}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    日期：
        <input type="text" value="<?php echo Yii::$app->getRequest()->post('created_at', ''); ?>" name="created_at" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
        <input type="submit" name="search_submit" value="过滤" class="btn"/>
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th rowspan="2">日期</th>
                <th colspan="6" style="text-align:center"><font color="red">网站侧</font></th>
                <th colspan="5" style="text-align:center"><font color="#009900">商户侧</font></th>
                <th rowspan="2">操作</th>
            </tr>
            <tr class="header">
                <th><font color="red">总金额</font></th>
                <th><font color="red">银行转账</font></th>
                <th><font color="red">代扣（易宝、联动</font></th>
                <th><font color="red">主动还款（易宝</font></th>
                <th><font color="red">支付宝</font></th>
                <th><font color="red">客户主动延期（易宝）</font></th>
                <th><font color="#009900">总金额</font></th>
                <th><font color="#009900">易宝</font></th>
                <th><font color="#009900">联动</font></th>
                <th><font color="#009900">银行卡</font></th>
                <th><font color="#009900">支付宝</font></th>
            </tr>
            <?php if(!empty($list_view_arr)):?>
                <?php foreach ($list_view_arr as $key_date=> $value): ?>
                    <tr>
                        <td><?php echo $key_date;?></td>
                        <td><font color="red"><?php echo empty($value['web_total'])?'--':sprintf("%0.2f",$value['web_total']/100);?></font></td>
                        <td><font color="red"><?php echo empty($value[2])?'--':sprintf("%0.2f",$value[2]/100);?></font></td>
                        <td><font color="red"><?php echo empty($value[1])?'--':sprintf("%0.2f",$value[1]/100);?></font></td>
                        <td><font color="red"><?php echo empty($value[4])?'--':sprintf("%0.2f",$value[4]/100);?></font></td>
                        <td><font color="red"><?php echo empty($value[3])?'--':sprintf("%0.2f",$value[3]/100);?></font></td>
                        <td><font color="red"><?php echo empty($value[5])?'--':sprintf("%0.2f",$value[5]/100);?></font></td>
                        <td><font color="#009900"><?php echo empty($value['custom_total'])?'--':sprintf("%0.2f",$value['custom_total']/100);?></font></td>
                        <td><font color="#009900"><?php echo empty($value[6])?'--':sprintf("%0.2f",$value[6]/100);?></font></td>
                        <td><font color="#009900"><?php echo empty($value[7])?'--':sprintf("%0.2f",$value[7]/100);?></font></td>
                        <td><font color="#009900"><?php echo empty($value[8])?'--':sprintf("%0.2f",$value[8]/100);?></font></td>
                        <td><font color="#009900"><?php echo empty($value[9])?'--':sprintf("%0.2f",$value[9]/100);?></font></td>
                        <td>
                        <a href="<?php echo Url::toRoute(['financial/input-info-view','operate_date'=>$key_date]); ?>">【录入】</a>
                        <a href="<?php echo Url::toRoute(['financial/look-info-view','operate_date'=>$key_date]); ?>">【查看】</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($list_view_arr)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

