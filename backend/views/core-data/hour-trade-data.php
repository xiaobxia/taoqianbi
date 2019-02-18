<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;

?>
<style>
    table tr th {
        font-weight: bold;
    }
    .change_tag {
        float: right;
        margin-right: 10%;
    }
    .bz {
        text-align: left;
        font-size: 12px;
        margin-left: 10px;
        line-height: 1.5;
    }
    table th{text-align: center}
    table td{text-align: center}
</style>
<title>每日借还款数据对比</title>
<!--<button id="change_tag" class="btn change_tag">点击切换显示模式</button>-->
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php
$form = ActiveForm::begin([
    'id' => 'search_form',
    'method'=>'get',
    'action' => ['core-data/hour-trade-data'],
    'options' => ['style' => 'margin-top:5px;'],
]);
?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>" name="add_start"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
  至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>" name="add_end"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})" />&nbsp;
<script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts.js'); ?>"></script>
借款显示：<?php echo Html::dropDownList('loan_type', Yii::$app->getRequest()->get('loan_type', ''), ['0'=>'总放款金额','1'=>'总申请人数','2'=>'总申请金额','3'=>'总放款人数','4'=>'总通过率','5'=>'老申请人数','6'=>'老申请金额','7'=>'老放款人数','8'=>'老放款金额','9'=>'老通过率','10'=>'新申请人数','11'=>'新申请金额','12'=>'新放款人数','13'=>'新放款金额','14'=>'新通过率']); ?>&nbsp;
还款显示：<?php echo Html::dropDownList('repay_type', Yii::$app->getRequest()->get('repay_type', ''), ['0'=>'总还款率','1'=>'总还款人数','2'=>'总还款金额','3'=>'老还款人数','4'=>'老还款金额','5'=>'老还款率','6'=>'新还款人数','7'=>'新还款金额','8'=>'新还款率','9'=>'总主动还款人数','10'=>'新主动还款人数','11'=>'老主动还款人数']); ?>&nbsp;
统计类型：<?php echo Html::dropDownList('data_type', Yii::$app->getRequest()->get('data_type', ''), ['0'=>'汇总','1'=>'分时']);?>
<input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;更新时间：<?php echo $update_time;?>
<br>
借款显示折现图：
<div id="main_loan" style="height:500px; width: 1200px;"></div>
<script type="text/javascript">
//路径配置
require.config({
    paths: {
        echarts: '<?php echo Url::toStatic('/js'); ?>'
    }
});

// 使用
require([
    'echarts',
    'echarts/theme/macarons',
    'echarts/chart/line',
    'echarts/chart/bar',
],
function (ec, theme) {
    // 基于准备好的dom，初始化echarts图表
    var myChart = ec.init(document.getElementById('main_loan'), theme);

    var option = {

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: <?php echo json_encode($legend_loan); ?>,
            selected: {},
            padding:[30,100]
        },
        toolbox: {
            show: true,
            feature: {
                mark: {show: true},
                dataView: {show: true, readOnly: false},
                magicType: {show: true, type: ['line', 'bar']},
                restore: {show: true},
                saveAsImage: {show: true}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                boundaryGap: false,
                data: <?php echo json_encode($x) ?>
            }
        ],
        yAxis: [{
            type: 'value',
            axisLabel: {
                formatter: function (value, index) {
                    return value.toFixed(2);
                }
            }
        }],
        series: <?php echo json_encode($series_loan) ?>
    };

    //除了0, 隐藏其他
    for (var key in option.legend.data) {
        if (key == 0||key == 1) {
            continue;
        }

        option.legend.selected[ option.legend.data[key] ] = false;
    }

    // 为echarts对象加载数据
    myChart.setOption(option);
});
</script>
<br>
还款显示折现图（0-1点为提前还款）：
<div id="main_repay" style="height:500px; width: 1200px;"></div>
<script type="text/javascript">
//路径配置
require.config({
    paths: {
        echarts: '<?php echo Url::toStatic('/js'); ?>'
    }
});

// 使用
require([
    'echarts',
    'echarts/theme/macarons',
    'echarts/chart/line',
    'echarts/chart/bar',
],
function (ec, theme) {
    // 基于准备好的dom，初始化echarts图表
    var myChart = ec.init(document.getElementById('main_repay'), theme);

    var option = {

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: <?php echo json_encode($legend_repay); ?>,
            selected: {},
            padding:[30,100]
        },
        toolbox: {
            show: true,
            feature: {
                mark: {show: true},
                dataView: {show: true, readOnly: false},
                magicType: {show: true, type: ['line', 'bar']},
                restore: {show: true},
                saveAsImage: {show: true}
            }
        },
        calculable: true,
        xAxis: [
            {
                type: 'category',
                boundaryGap: false,
                data: <?php echo json_encode($xs) ?>
            }
        ],
        yAxis: [{
            type: 'value',
            axisLabel: {
                formatter: function (value, index) {
                    return value.toFixed(2);
                }
            }
        }],
        series: <?php echo json_encode($series_repay) ?>
    };

    //除了0, 隐藏其他
    for (var key in option.legend.data) {
        if (key == 0||key == 1) {
            continue;
        }

        option.legend.selected[ option.legend.data[key] ] = false;
    }

    // 为echarts对象加载数据
    myChart.setOption(option);
});
</script>
<br>
<br>
<!--<DIV  style=" OVERFLOW-X: scroll;">-->
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding" style="width: 1700px;" >
            <tr class="header">
                <th colspan="2" style="text-align:center;border-right:1px solid #A9A9A9;"></th>
                <th colspan="15" style="text-align: center;border-right:1px solid #A9A9A9;color: blue">借款</th>
                <th colspan="12" style="text-align: center;border-right:1px solid #A9A9A9;color: red">还款</th>
            </tr>
            <tr class="header">
                <th></th>
                <th style="border-right:1px solid #A9A9A9"></th>
                <th colspan="5" style="text-align:center;border-right:1px solid #A9A9A9;color: blue">所有用户</th>
                <th colspan="5" style="text-align:center;border-right:1px solid #A9A9A9;color: blue">老用户</th>
                <th colspan="5" style="text-align:center;border-right:1px solid #A9A9A9;color: blue">新用户</th>
                <th colspan="4" style="text-align:center;border-right:1px solid #A9A9A9;color: red">所有用户</th>
                <th colspan="4" style="text-align:center;border-right:1px solid #A9A9A9;color: red">老用户</th>
                <th colspan="4" style="text-align:center;border-right:1px solid #A9A9A9;color: red">新用户</th>
            </tr>
            <tr class="header">
                <th>日期</th>
                <th style="border-right:1px solid #A9A9A9">小时</th>
                <th style="color: blue">申请人数</th>
                <th style="color: blue">申请金额</th>
                <th style="color: blue">放款人数</th>
                <th style="color: blue">放款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: blue">通过率</th>
                <th style="color: blue">申请人数</th>
                <th style="color: blue">申请金额</th>
                <th style="color: blue">放款人数</th>
                <th style="color: blue">放款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: blue">通过率</th>
                <th style="color: blue">申请人数</th>
                <th style="color: blue">申请金额</th>
                <th style="color: blue">放款人数</th>
                <th style="color: blue">放款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: blue">通过率</th>
                <th style="color: red">还款人数</th>
                <th style="color: red">主动还款人数</th>
                <th style="color: red">还款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: red">还款率</th>
                <th style="color: red">还款人数</th>
                <th style="color: red">主动还款人数</th>
                <th style="color: red">还款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: red">还款率</th>
                <th style="color: red">还款人数</th>
                <th style="color: red">主动还款人数</th>
                <th style="color: red">还款金额</th>
                <th style="border-right:1px solid #A9A9A9;color: red">还款率</th>
            </tr>

            <?php foreach ($trade_data as $date =>$item): ?>
                <?php foreach ($item as $hour =>$value): ?>
                    <tr class="hover">
                        <td><?php echo $date; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo $hour.":00"; ?></td>
                        <td><?php echo isset($value['apply_num_0'])?$value['apply_num_0']:0; ?></td>
                        <td><?php echo isset($value['apply_money_0'])?$value['apply_money_0']/100:0; ?></td>
                        <td><?php echo isset($value['loan_num_0'])?$value['loan_num_0']:0; ?></td>
                        <td><?php echo isset($value['loan_money_0'])?$value['loan_money_0']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['pass_rate_0'])?$value['pass_rate_0']*100:0; ?>%</td>
                        <td><?php echo isset($value['apply_num_2'])?$value['apply_num_2']:0; ?></td>
                        <td><?php echo isset($value['apply_money_2'])?$value['apply_money_2']/100:0; ?></td>
                        <td><?php echo isset($value['loan_num_2'])?$value['loan_num_2']:0; ?></td>
                        <td><?php echo isset($value['loan_money_2'])?$value['loan_money_2']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['pass_rate_2'])?$value['pass_rate_2']*100:0; ?>%</td>
                        <td><?php echo isset($value['apply_num_1'])?$value['apply_num_1']:0; ?></td>
                        <td><?php echo isset($value['apply_money_1'])?$value['apply_money_1']/100:0; ?></td>
                        <td><?php echo isset($value['loan_num_1'])?$value['loan_num_1']:0; ?></td>
                        <td><?php echo isset($value['loan_money_1'])?$value['loan_money_1']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['pass_rate_1'])?$value['pass_rate_1']*100:0; ?>%</td>
                        <td><?php echo isset($value['repayment_num_0'])?$value['repayment_num_0']:0; ?></td>
                        <td><?php echo isset($value['repayment_num_0'])?$value['active_repayment_0']:0; ?></td>
                        <td><?php echo isset($value['repayment_money_0'])?$value['repayment_money_0']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['repay_rate_0'])?$value['repay_rate_0']*100:0; ?>%</td>
                        <td><?php echo isset($value['repayment_num_2'])?$value['repayment_num_2']:0; ?></td>
                        <td><?php echo isset($value['repayment_num_2'])?$value['active_repayment_2']:0; ?></td>
                        <td><?php echo isset($value['repayment_money_2'])?$value['repayment_money_2']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['repay_rate_2'])?$value['repay_rate_2']*100:0; ?>%</td>
                        <td><?php echo isset($value['repayment_num_1'])?$value['repayment_num_1']:0; ?></td>
                        <td><?php echo isset($value['repayment_num_1'])?$value['active_repayment_1']:0; ?></td>
                        <td><?php echo isset($value['repayment_money_1'])?$value['repayment_money_1']/100:0; ?></td>
                        <td style="border-right:1px solid #A9A9A9"><?php echo isset($value['repay_rate_1'])?$value['repay_rate_1']*100:0; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </table>
        <?php if (empty($register_data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
        <?php echo LinkPager::widget(['pagination' => $pages]); ?>
    </form>
<?php ActiveForm::end(); ?>

<br>
<p>折线图默认显示24小时内数据、提前还款统一归到零点</p>
<p>申请人数： 每小时整点记录当日零时起到当前时间的申请人数</p>
<p>放款人数： 每小时整点记录当日零时起到当前时间的放款人数</p>
<p>申请金额： 每小时整点记录当日零时起到当前时间的申请金额</p>
<p>放款金额： 每小时整点记录当日零时起到当前时间的放款金额</p>
<p>通过率：放款人数/申请人数</p>
<p>还款人数： 每小时整点记录当日零时起到当前时间的还款人数</p>
<p>还款金额： 每小时整点记录当日零时起到当前时间的还款金额</p>
<p>还款率： 还款人数/放款人数</p>
<br>



