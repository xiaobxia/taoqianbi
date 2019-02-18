
<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
//$this->navWrapper('menu_data_data_daily');

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
</style>

<!--<button id="change_tag" class="btn change_tag">点击切换显示模式</button>-->
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/hour-register-data'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>日期</th>
            <th>小时</th>
            <th>注册量(人数)</th>
            <th>注册申请借款量(人数)</th>
            <th>申请借款量(笔数)</th>
            <th>财务放款量(笔数)</th>
            <th>还款量(笔数)</th>
            <th>实名认证人数/占比</th>
            <th>通讯录认证人数/占比</th>
            <th>芝麻授信认证人数/占比</th>
            <th>工作信息认证人数/占比</th>
            <th>银行卡认证人数/占比</th>
            <th>支付宝认证人数/占比</th>
        </tr>
        <?php foreach ($register_data as $value): ?>
            <tr class="hover">
                <td class="td25" ><?php echo $value['date_time']; ?></td>
                <td class="td25" ><?php echo $value['hour'].":00"; ?></td>
                <td class="td25" ><?php echo $value['register_num']; ?></td>
                <td class="td25" ><?php echo $value['register_apply_num']; ?></td>
                <td class="td25" ><?php echo $value['total_apply_num']; ?></td>
                <td class="td25" ><?php echo $value['loan_num']; ?></td>
                <td class="td25" ><?php echo $value['repayment_num']; ?></td>
                <td class="td25" ><?php echo $value['realname_num']; ?>/<?php echo sprintf("%0.2f", $value['realname_num']/$value['register_num']*100); ?>%</td>
                <td class="td25" ><?php echo $value['contacts_list_num']; ?>/<?php echo sprintf("%0.2f", $value['contacts_list_num']/$value['register_num']*100); ?>%</td>
                <td class="td25" ><?php echo $value['zmxy_num']; ?>/<?php echo sprintf("%0.2f", $value['zmxy_num']/$value['register_num']*100); ?>%</td>
                <td class="td25" ><?php echo $value['real_work_num']; ?>/<?php echo sprintf("%0.2f", $value['real_work_num']/$value['register_num']*100); ?>%</td>
                <td class="td25" ><?php echo $value['bind_card_num']; ?>/<?php echo sprintf("%0.2f", $value['bind_card_num']/$value['register_num']*100); ?>%</td>
                <td class="td25" ><?php echo $value['alipay_num']; ?>/<?php echo sprintf("%0.2f", $value['alipay_num']/$value['register_num']*100); ?>%</td>

            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($register_data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?>
</form>
<script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts3.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/common_chart3.js'); ?>" type='text/javascript'></script>
<br>
<p>注册量： 每小时整点记录当日零时起到当前时间的注册人数</p>
<p>注册申请借款量：今日注册的用户中申请借款的笔数</p>
<p>申请借款量：今日所有用户申请借款的笔数</p>
<p>财务放款量：今日所有用户申请借款并成功的笔数</p>
<p>还款量：   今日所有用户还款的笔数</p>
<p>各项认证的数据是在今日注册的用户中取得的</p>
<br>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/hour-register-data'], 'options' => ['style' => 'margin-top:5px;']]); ?>
显示折现图：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), ['0'=>'注册量','1'=>'注册申请借款量','2'=>'申请借款量','3'=>'财务放款量','4'=>'还款量','5'=>'实名认证人数','6'=>'通讯录认证人数','7'=>'芝麻授信认证人数','8'=>'工作信息认证人数','9'=>'银行卡认证人数','10'=>'支付宝认证人数']); ?>&nbsp;
 <input type="submit" name="btn" value="选择" class="btn">
<?php ActiveForm::end(); ?>

<div id="main" style="height:500px; width: 1600px;"></div>
<script src="<?php echo Url::toStatic('/js/echarts.js'); ?>"></script>
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
            var myChart = ec.init(document.getElementById('main'), theme);

            var option = {

                tooltip: {
                    trigger: 'axis'
                },
                legend: {
                    data: <?php echo json_encode($legend); ?>,
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
                    type: 'value'
                }],
                series: <?php echo json_encode($series) ?>
            };

            //除了0, 隐藏其他
            for (var key in option.legend.data) {
                if (key == 0) {
                    continue;
                }

                option.legend.selected[ option.legend.data[key] ] = false;
            }

            // 为echarts对象加载数据
            myChart.setOption(option);
        });
</script>
<br>
<p>折线图默认显示最近7天数据</p>




