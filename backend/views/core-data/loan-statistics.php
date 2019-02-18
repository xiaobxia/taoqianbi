<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\LoanPerson;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$rate = Yii::$app->request->get('from_st','0') ? 1.1 : 1;
?>
<title>每日借款额度</title>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get',  'options' => ['style' => 'margin-top:5px;']]); ?>
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
    <input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
    &nbsp;&nbsp;&nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th rowspan="2">日期</th>
                <th rowspan="2">总借款金额</th>
                <th rowspan="2">总单数</th>
                <th rowspan="2">单均金额</th>
                <th colspan="2" style="text-align: center">0-1000</th>
                <th colspan="2" style="text-align: center">1000-2000</th>
                <th colspan="2" style="text-align: center">2000-3000</th>
                <th colspan="2" style="text-align: center">3000-4000</th>
                <th colspan="2" style="text-align: center">4000-5000</th>
                <th colspan="2" style="text-align: center">5000以上</th>
            </tr>
            <tr class="header">
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
                <th style="text-align: center">单数</th>
                <th style="text-align: center">占比</th>
            </tr>
            <?php foreach ($data as $date => $value): ?>
                <tr class="hover">
                    <td><?php echo $date; ?></td>
                    <td><?php echo number_format($value['loan_money']);?></td>
                    <td><?php echo $value['loan_num']; ?></td>
                    <td><?php echo empty($value['loan_num'])?0:sprintf("%0.2f",$value['loan_money']/$value['loan_num']); ?></td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_1'])?$value['loan_num_1']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_1'])?0:sprintf("%0.2f",$value['loan_num_1']/$value['loan_num']*100); ?>%</td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_2'])?$value['loan_num_2']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_2'])?0:sprintf("%0.2f",$value['loan_num_2']/$value['loan_num']*100); ?>%</td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_3'])?$value['loan_num_3']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_3'])?0:sprintf("%0.2f",$value['loan_num_3']/$value['loan_num']*100); ?>%</td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_4'])?$value['loan_num_4']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_4'])?0:sprintf("%0.2f",$value['loan_num_4']/$value['loan_num']*100); ?>%</td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_5'])?$value['loan_num_5']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_5'])?0:sprintf("%0.2f",$value['loan_num_5']/$value['loan_num']*100); ?>%</td>
                    <td style="text-align: center"><?php echo isset($value['loan_num_6'])?$value['loan_num_6']:0; ?></td>
                    <td style="text-align: center"><?php echo empty($value['loan_num_6'])?0:sprintf("%0.2f",$value['loan_num_6']/$value['loan_num']*100); ?>%</td>
                </tr>
            <?php endforeach; ?>
            <tr class="hover">
                <td>总计：</td>
                <td><?php echo number_format($all['loan_money']); ?></td>
                <td><?php echo $all['loan_num']; ?></td>
                <td><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_money']/$all['loan_num']); ?></td>
                <td style="text-align: center"><?php echo $all['loan_num_1']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_1']/$all['loan_num']*100); ?>%</td>
                <td style="text-align: center"><?php echo $all['loan_num_2']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_2']/$all['loan_num']*100); ?>%</td>
                <td style="text-align: center"><?php echo $all['loan_num_3']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_3']/$all['loan_num']*100); ?>%</td>
                <td style="text-align: center"><?php echo $all['loan_num_4']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_4']/$all['loan_num']*100); ?>%</td>
                <td style="text-align: center"><?php echo $all['loan_num_5']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_5']/$all['loan_num']*100); ?>%</td>
                <td style="text-align: center"><?php echo $all['loan_num_6']; ?></td>
                <td style="text-align: center"><?php echo empty($all['loan_num'])?0:sprintf("%0.2f",$all['loan_num_6']/$all['loan_num']*100); ?>%</td>
            </tr>
        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>

<?php //echo LinkPager::widget(['pagination' => $pages]); ?>


