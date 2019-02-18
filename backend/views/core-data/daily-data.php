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
$search_date = Yii::$app->getRequest()->get('search_date', '1');
?>
<title>每日借款数据</title>
    <style>
        table th{text-align: center}
        table td{text-align: center}
    </style>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
    <script type="text/javascript">
        $(function(){
            $("#daochu_submit").click(function(){
                $.ajax({
                    url:"<?php echo Url::toRoute('core-data/daily-data'); ?>",
                    type:"get",
                    data:{add_start:"2017-03-26",add_end:"2017-05-16"},
                    success:function(data){
                        window.clearInterval(timer);
                        console.log("over..");
                    },
                    error:function(e){
                        alert("错误！！");
                        window.clearInterval(timer);
                    }
                })
            })
        })
    </script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action'=>Url::toRoute(['core-data/daily-data']), 'options' => ['style' => 'margin-top:5px;']]); ?>
    <?php echo Html::dropDownList('search_date', $search_date, array(1=>'借款日期',2=>'还款日期')) ?>
   <input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-30*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<?php if($channel==1){?>
    来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), array('prompt'=>UserLoanOrder::$sub_order_type[$sub_order_type])); ?>&nbsp;
<?php }else{?>
    来源：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
<?php }?>
&nbsp;
APP来源：<?php echo Html::dropDownList('source_type', Yii::$app->getRequest()->get('source_type', ''), $app_source); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
    <input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
    &nbsp;&nbsp;<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
    <?php if (!empty($last_update_at)): ?>
        &nbsp;&nbsp;最后更新时间：<?php echo date("n-j H:i", $last_update_at);?>
    <?php endif; ?>
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th colspan="2" style="text-align:center;border-right:1px solid #A9A9A9;">借款信息</th>
                <th colspan="4" style="text-align:center;border-right:1px solid #A9A9A9;">所有用户</th>
                <th colspan="3" style="color:blue;text-align: center;border-right:1px solid blue;">新用户</th>
                <th colspan="3" style="color:red;text-align: center;border-right:1px solid red;">老用户</th>
            </tr>
            <tr class="header">
                <th>借款日期</th>
                <th style="border-right:1px solid #A9A9A9;">还款日期</th>
                <th>借款单数</th>
                <th>借款总额</th>
                <th>借款件均</th>
                <th style="border-right:1px solid #A9A9A9;">新老用户比</th>
                <th style="text-align:center;color:blue">借款单数</th>
                <th style="text-align:center;color:blue">借款总额</th>
                <th style="text-align:center;color:blue;border-right:1px solid blue;">借款件均</th>
                <th style="text-align:center;color:red">借款单数</th>
                <th style="text-align:center;color:red">借款总额</th>
                <th style="text-align:center;color:red;border-right:1px solid red;">借款件均</th>
            </tr>
            <tr>
                <?php
                $total_all = $total_loan_num_new + $total_loan_num_old;
                $total_new_pre = (!empty($total_all)) ? round(($total_loan_num_new/$total_all)*100) : 0;
                $total_old_pre = 100 - $total_new_pre;
                ?>
                <th>汇总信息</th>
                <th style="border-right:1px solid #A9A9A9;"></th>
                <th><?php echo $total_loan_num; ?></th>
                <th><?php echo number_format(sprintf("%0.2f",$total_loan_money/100)); ?></th>
                <th><?php echo ($total_loan_num > 0) ? (round(($total_loan_money/100)/$total_loan_num, 2)) : 0; ?></th>
                <th style="border-right:1px solid #A9A9A9;"><?php echo "<span style='color:blue'>".$total_new_pre."</span>" . " : " . "<span style='color:red'>".$total_old_pre."</span>"; ?></th>
                <th style="text-align:center;color:blue"><?php echo $total_loan_num_new ?></th>
                <th style="text-align:center;color:blue"><?php echo number_format($total_loan_money_new/100) ?></th>
                <th style="text-align:center;color:blue;border-right:1px solid blue;"><?php echo ($total_loan_num_new>0)?sprintf("%0.2f",($total_loan_money_new/100)/$total_loan_num_new):0; ?></th>
                <th style="text-align:center;color:red"><?php echo $total_loan_num_old ?></th>
                <th style="text-align:center;color:red"><?php echo number_format($total_loan_money_old/100) ?></th>
                <th style="text-align:center;color:red;border-right:1px solid red;"><?php echo ($total_loan_num_old>0)?sprintf("%0.2f",($total_loan_money_old/100)/$total_loan_num_old):0; ?></th>
            </tr>
            <?php foreach ($data as $value): ?>
                <tr class="hover" style="<?php echo date('w', $value['date_time']) == 0 || date('w', $value['date_time']) == 6?'background:#d1d0fb':'';?>">
                    <?php
                        $loan_num_new = $rate*$value['loan_num_new'];
                        $loan_num_old = $rate*$value['loan_num_old'];
                        $total = $loan_num_new + $loan_num_old;
                        $new_pre = (!empty($total)) ? round(($loan_num_new/$total)*100) : 0;
                        $old_pre = 100 - $new_pre;

                        $loan_num_7 = $rate*$value['loan_num_7'];
                        $loan_money_7 = $rate*$value['loan_money_7']/100;
                        $loan_money_new = $value['loan_money_new']/100;
                        $loan_money_old = $value['loan_money_old']/100;
                    ?>
                    <!-- 借款信息 -->
                    <td class="td25"><?php echo date("n-j",$value['date_time']); ?></td>
                    <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo date("n-j",$value['date_time']+7*86400); ?></td>
                    <!-- 所有用户 -->
                    <td class="td25"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>date("Y-m-d",$value['date_time']),'loan_term'=>'7','page_type'=>'2']); ?>"target="_blank"><?php echo $loan_num_7; ?></a></td>
                    <td class="td25"><?php echo number_format(sprintf("%0.2f",$loan_money_7)); ?></td>
                    <td class="td25"><?php echo ($loan_num_7>0)?sprintf("%0.2f",$loan_money_7/$loan_num_7):0; ?></td>
                    <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo "<span style='color:blue'>".$new_pre."</span>" . " : " . "<span style='color:red'>".$old_pre."</span>"; ?></td>

                    <!-- 新用户 -->
                    <td class="td25" style="text-align:center;color:blue"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>date("Y-m-d",$value['date_time']),'old_user'=>'-1','page_type'=>'2']); ?>"target="_blank" style="color: blue"><?php echo $loan_num_new; ?></a></td>
                    <td class="td25" style="text-align:center;color:blue"><?php echo number_format(sprintf("%0.2f",$loan_money_new)); ?></td>
                    <td class="td25" style="text-align:center;border-right:1px solid blue;color:blue"><?php echo ($loan_num_new>0)?sprintf("%0.2f",$loan_money_new/$loan_num_new):0; ?></td>

                    <!-- 老用户 -->
                    <td class="td25" style="text-align:center;color:red;"><a href="<?php echo Url::toRoute(['pocket/pocket-list','time'=>date("Y-m-d",$value['date_time']),'old_user'=>'1','page_type'=>'2']); ?>"target="_blank" style="color: red"><?php echo $loan_num_old; ?></a></td>
                    <td class="td25" style="text-align:center;color:red;"><?php echo number_format(sprintf("%0.2f",$loan_money_old)); ?></td>
                    <td class="td25" style="text-align:center;border-right:1px solid red;color:red;"><?php echo ($loan_num_old>0)?sprintf("%0.2f",$loan_money_old/$loan_num_old):0; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>

<?php //echo LinkPager::widget(['pagination' => $pages]); ?>

    <table frame="above" align="right">
        <tr>
            <td align="center" style="color: red;">借款单数总计：<?php echo floor($rate*$total_loan_num) ?></td>
        </tr>
        <tr>
            <td align="center" style="color: red;">借款总额总计：<?php echo sprintf("%.2f",$rate*$total_loan_money / 100) ?></td>
        </tr>
    </table>
