
<?php
use yii\widgets\ActiveForm;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
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
					data: <?php echo json_encode($legend);?>,
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
				if (key < 2) {
					continue;
				}

				option.legend.selected[ option.legend.data[key] ] = false;
			}

			// 为echarts对象加载数据
			myChart.setOption(option);
		});
</script>

<div class="no-result" style="color: red;">备注：</div>
<div class="no-result">监控当天数据</div>

