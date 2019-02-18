<?php
$this->navWrapper('menu_data_position_analysis', $menu,'用户画像分布');
?>
<style>
.chart-div {
	margin-top: 30px;
	width: 1780px;
	height: 600px;
	float: left;
}
</style>

<script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts3.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/common_chart3.js'); ?>" type='text/javascript'></script>


<div id="all-user-address" class="chart-div"></div>
<div id="invest-user-address" class="chart-div"></div>
<div id="all-user-age" class="chart-div" style="height: 400px"></div>
<p id="all-user-sex" class="chart-div" style="height: 400px;width:830px;"></p>
<p id="invest-time" class="chart-div" style="height: 400px;width:830px;"></p>
<script type='text/javascript'>
setBarChart('<?php echo $all_user_address_chart_b['title']; ?>', "all-user-address",
    <?php echo json_encode($array_user_register_total);?>
);
setBarChart('<?php echo $all_user_address_chart_c['title']; ?>', "invest-user-address",
    <?php echo json_encode($array_user_register_current);?>
);
setBarChart('<?php echo $all_user_address_chart_d['title']; ?>', "all-user-age",
    <?php echo json_encode($array_user_register_age);?>
);
setBarChart("<?php echo $all_user_address_chart_e['title'];?>", 'all-user-sex',
    <?php echo json_encode($array_user_register_sex); ?>);

setBarChart("<?php echo $all_user_address_chart_f['title'];?>", 'invest-time',
    <?php echo json_encode($array_user_register_model); ?>);
</script>
