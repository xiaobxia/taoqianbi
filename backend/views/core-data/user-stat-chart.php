<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/6/20
 * Time: 13:06
 */
use yii\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;

/**
 * @var backend\views\core-data\user-stat-chart.php  用户数据综合统计图
 */
?>
<style>
    table tr th {
        font-weight: bold;
    }
    table tr th,td{
        text-align: center;
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
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/user-stat-chart'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('start_date')) ? date("Y-m-d", time()-86400*7) : Yii::$app->request->get('start_date'); ?>"  name="start_date" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('end_date')) ? date("Y-m-d", time()) : Yii::$app->request->get('end_date'); ?>"  name="end_date" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th colspan="3">综合概览</th>
            <th colspan="3" style="border-right:1px solid #A9A9A9;">所有用户</th>
            <th colspan="3" style="color:blue; border-right:1px solid blue;">新客</th>
            <th colspan="3" style="color:red; border-right:1px solid red;">老客</th>
        </tr>
        <tr class="header">
            <!-- 综合概览 -->
            <th>日期</th>
            <th>注册用户数</th>
            <th>滞纳金回收</th>

            <!-- 所有用户 -->
            <th>放款单数</th>
            <th>放款金额</th>
            <th style="border-right:1px solid #A9A9A9;">首逾(%)</th>

            <!-- 新客 -->
            <th style="color:blue">放款单数</th>
            <th style="color:blue">放款金额</th>
            <th style="color:blue;border-right:1px solid blue;">首逾(%)</th>

            <!-- 老客 -->
            <th style="color:red">放款单数</th>
            <th style="color:red">放款金额</th>
            <th style="color:red;border-right:1px solid red;">首逾(%)</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <!-- 综合概览 -->
                <td class="td25"><?php echo $value['date_2']; ?></td>
                <td class="td25"><?php echo $value['reg_num']; ?></td>
                <td class="td25"><?php echo number_format($value['repay_late_fee'], 2); ?> </td>

                <!-- 所有用户 -->
                <td class="td25"><?php echo $value['loan_num_7']; ?></td>
                <td class="td25"><?php echo number_format($value['loan_money_7'], 2); ?></td>
                <td class="td25" style="border-right:1px solid #A9A9A9;"><?php echo ($value['date'] >= $now_date) ? '-' : $value['delay_num']."%"; ?></td>

                <!-- 新客 -->
                <td style="color:blue" class="td25"><?php echo $value['loan_num_14_new']; ?></td>
                <td style="color:blue" class="td25"><?php echo number_format($value['loan_money_14_new'], 2); ?></td>
                <td style="color:blue;border-right:1px solid blue;" class="td25"><?php echo ($value['date'] >= $now_date) ? '-' : $value['delay_num_new']."%"; ?></td>

                <!-- 老客 -->
                <td style="color:red" class="td25"><?php echo $value['loan_num_14_old']; ?></td>
                <td style="color:red" class="td25"><?php echo number_format($value['loan_money_14_old'], 2); ?></td>
                <td style="color:red;border-right:1px solid red;" class="td25"><?php echo ($value['date'] >= $now_date) ? '-' : $value['delay_num_old']."%"; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>

<script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts3.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts3.china.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/common_chart3.js'); ?>" type='text/javascript'></script>

<!-- 放款额度 div -->
<div id="loan_money_div" style="height:500px;"></div>

<!-- 放款单数 div -->
<div id="loan_num_div" style="height:500px;"></div>

<!-- 注册用户数div -->
<div id="reg_num_div" style="height:500px;"></div>

<!-- 首逾 div display:inline-block-->
<div id="delay_div" style="height:500px;"></div>

<!-- 滞纳金 div-->
<div id="late_fee_div" style="height:500px;"></div>

<script type="text/javascript">
    //放款单数
    setLineChart("loan_num_div",
        <?php echo json_encode($loan_num_config['legend']);?>,
        <?php echo json_encode($x);?>,
        <?php echo json_encode($loan_num_config['series']);?>
    );

    //放款额度
    setLineChart("loan_money_div",
        <?php echo json_encode($loan_money_config['legend']);?>,
        <?php echo json_encode($x);?>,
        <?php echo json_encode($loan_money_config['series']);?>
    );

    //注册用户数
    setLineChart("reg_num_div",
        <?php echo json_encode($reg_num_config['legend']);?>,
        <?php echo json_encode($x);?>,
        <?php echo json_encode($reg_num_config['series']);?>
    );

    //逾期率
    setLineChart2("delay_div",
        <?php echo json_encode($delay_config['legend']);?>,
        <?php echo json_encode($x);?>,
        <?php echo json_encode($delay_config['series']);?>,
        20,//Y轴最小值
        //100//Y轴最大值
    );

    //滞纳金
    setLineChart("late_fee_div",
        <?php echo json_encode($late_fee_config['legend']);?>,
        <?php echo json_encode($x);?>,
        <?php echo json_encode($late_fee_config['series']);?>
    );
</script>