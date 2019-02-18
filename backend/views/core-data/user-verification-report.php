
<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use common\models\LoanPerson;
use common\models\Channel;
use yii\helpers\Html;
//$this->navWrapper('menu_data_data_daily');
$this->shownav('data_analysis', 'menu_data_user_verification_report');
?>
<script src="<?php echo Url::toStatic('/js/echarts.js'); ?>"></script>
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
    <?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/user-verification-report'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
APP来源：<?php echo Html::dropDownList('source_type', Yii::$app->getRequest()->get('source_type', ''), LoanPerson::$app_loan_source); ?>&nbsp;
渠道类型：<?php echo Html::dropDownList('appMarket_type', Yii::$app->getRequest()->get('appMarket_type', ''), Channel::$channel_type); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;
更新时间：<?php echo $update_time;?>&nbsp;(每小时更新一次)
    <?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>日期</th>
                <th>注册总数</th>
                <th style="color:blue;">实名/占比</th>
                <th style="color:blue;">通讯录/占比</th>
                <th style="color:blue;">芝麻授信/占比</th>
                <th style="color:blue;">运营商/占比</th>
                <th style="color:blue;">绑卡/占比</th>
                <th style="color:red;">支付宝/占比</th>
                <th style="color:red;">公积金/占比</th>
                <th>全要素认证人数/占比</th>
                <th>认证未申请/占比</th>
                <th>认证已申请/占比</th>
                <th>通过/占比</th>
                <th>被拒/占比</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['date']; ?></td>
                    <td class="td25"><?php echo $value['reg_num']; ?></td>
                    <td class="td25" style="color:blue;"><?php echo empty($value['reg_num'])?1: $value['realname_num']; ?>/<?php echo sprintf("%0.2f", $value['realname_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:blue;"><?php echo $value['contacts_list_num']; ?>/<?php echo sprintf("%0.2f", $value['contacts_list_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:blue;"><?php echo $value['zmxy_num']; ?>/<?php echo sprintf("%0.2f", $value['zmxy_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:blue;"><?php echo $value['jxl_num']; ?>/<?php echo sprintf("%0.2f", $value['jxl_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:blue;"><?php echo $value['bind_card_num']; ?>/<?php echo sprintf("%0.2f", $value['bind_card_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:red;"><?php echo $value['alipay_num']; ?>/<?php echo sprintf("%0.2f", $value['alipay_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25" style="color:red;"><?php echo $value['public_funds_num']; ?>/<?php echo sprintf("%0.2f", $value['public_funds_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25"><?php echo $value['all_verif_num']; ?>/<?php echo sprintf("%0.2f", $value['all_verif_num']/$value['reg_num']*100); ?>%</td>
                    <td class="td25"><?php echo $value['unapply_num']; ?>/<?php echo empty($value['all_verif_num'])?'--':sprintf("%0.2f", $value['unapply_num']/$value['all_verif_num']*100); ?>%</td>
                    <td class="td25"><?php echo $value['apply_num']; ?>/<?php echo empty($value['all_verif_num'])?'--':sprintf("%0.2f", $value['apply_num']/$value['all_verif_num']*100); ?>%</td>
                    <td class="td25"><?php echo $value['apply_success_num']; ?>/<?php echo empty($value['apply_num'])?'--':sprintf("%0.2f", $value['apply_success_num']/$value['apply_num']*100); ?>%</td>
                    <td class="td25"><?php echo $value['apply_fail_num']; ?>/<?php echo empty($value['apply_num'])?'--':sprintf("%0.2f", $value['apply_fail_num']/$value['apply_num']*100); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?>

    <script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
    <script src="<?php echo Url::toStatic('/js/echarts3.min.js'); ?>" type='text/javascript'></script>
    <script src="<?php echo Url::toStatic('/js/common_chart3.js'); ?>" type='text/javascript'></script>

<div id="main" style="height:500px; width: 1600px;"></div>
<script type="text/javascript">
    // 路径配置
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
                    selected: {}
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

<div class="no-result" style="color: red;">备注：</div>
<div class="no-result">更新时间：每日凌晨更新前1天数据，记录后数据留存不变动</div>
<div class="no-result">注册总数：指当天新注册用户的人数</div>
<div class="no-result">通讯录/占比:当天注册人中已填写通讯录的人数/(已填写通讯录人数/注册总数)</div>
<div class="no-result">其他同上一致</div>
