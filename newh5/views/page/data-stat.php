<style type="text/css">
#data_stat_wraper{width:100%;}
#data_stat_wraper .content{padding-bottom: 15%;}
#data_stat_wraper a:hover{color: #fd5353;}
</style>
<div id="data_stat_wraper">
	<div class="content">
		<p class="a_center lh_em_2 em_3">站内统计</p>
		<p class="em_1">日期：<?php echo $date_tips;?></p>
		<p class="lh_em_3 em_1_2">PV统计（<?php echo $pv_count;?>）</p>
		<table class="gridtable">
			<tr>
				<th>访问url</th>
				<th>PV</th>
			</tr>
			<?php foreach ($pv as $pv_val):?>
			<tr>
				<td class="_word_wrap">
					<a class="_61cae4" href="<?php echo $pv_val['current_url'];?>" title="<?php echo $pv_val['current_url'];?>"><?php echo $pv_val['current_url'];?></a>
				</td>
				<td><?php echo $pv_val['pv'];?></td>
			</tr>
			<?php endforeach;?>
		</table>

		<p class="lh_em_3 em_1_2">UV统计（<?php echo $uv_count;?>）</p>
		<table class="gridtable">
			<tr>
				<th width="70%">访问url</th>
				<th>UV</th>
			</tr>
			<?php foreach ($uv as $url => $uv_val):?>
			<tr>
				<td class="_word_wrap">
					<a class="_61cae4" href="<?php echo $url;?>" title="<?php echo $url;?>"><?php echo $url;?></a>
				</td>
				<td><?php echo $uv_val;?></td>
			</tr>
			<?php endforeach;?>
		</table>
	</div>
</div>