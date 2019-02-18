<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2015/6/8
 * Time: 16:55
 */
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title><?php echo $this->title ? $this->title : '口袋快借'; ?></title>
    <!--标准mui.css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.css" rel="stylesheet"/>
    <!--App自定义的css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css" rel="stylesheet"/>
    <link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.picker.css" rel="stylesheet" />
	<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.poppicker.css" rel="stylesheet" />
	<style>
		#pop_dialog{width:100%;position:fixed;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%);max-width:480px;display: none;}
		#pop_dialog .pop_dialog{background:#fff;width:80%;padding-bottom:10px;border-radius:5px;margin:0 auto}
		#pop_dialog .message{text-align:center;padding:28px}
		#pop_dialog .convert_btn{display:block;width:80%;margin:0 auto;padding:10px;background:#FD5353;text-align:center;color:#fff;border-radius:5px}
		#mask{position:fixed;background-color:black;max-width:480px;top:0;width:100%;opacity:.65;z-index:9999}
	</style>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
	    <h1 class="mui-title font-color1">居住信息</h1>
	</header> -->
	<div class="mui-content">
		<div class="flex-container bgcolor2">
			<img src="<?php echo $this->absBaseUrl; ?>/image/train-period/jindu1.gif"/>
			<div class="flex-div">
				<span class="tl">居住信息</span>
				<span class="">工作信息</span>
				<span class="tr">联系人&nbsp;&nbsp;</span>
				<span class="tr">信用信息</span>
			</div>
		</div>
		<div class="mui-content-padded"></div>
		<form class="mui-input-group">
		    <ul class="mui-table-view">
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" present_province ="0" present_city ="0" present_area="0" id="showCityPicker3">现居住地址区域：<span id="cityResult3" class="ui-alert"></span></a>
				</li>
		    	<li class="mui-input-row my-input-row">
					<label class="my-label2">现居地址：</label>
					<input type="text" id="present_address" name="present_address" class="my-input2" placeholder="请输入详细居住地址" />
				</li>
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" family_province ="0" family_city ="0" family_area="0" id="showHomePicker3">家庭地址区域：<span id="homeResult3" class="ui-alert"></span></a>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">家庭地址：</label>
					<input type="text" id="family_address" name="family_address" class="my-input2" placeholder="请输入详细地址" />
				</li>
			</ul>
		</form>
		<!-- 半透明遮掩 -->
		<div id="mask" ></div>
		<!-- 登录弹框 -->
		<div id="pop_dialog" align="center" class="_hidden1" style="opacity:1;z-index: 10000">
			<div class="pop_dialog">
				<p class="message"></p>
				<span class="convert_btn" onclick="hideDialog()"></span>
			</div>
		</div>
		<div class="padding _666 em__9 fd5457 em__9" id="msg">&nbsp;&nbsp;</div>
		<div class="mui-content-padded">
			<button class="mui-btn mui-btn-block bgcolor1 font-color1" type="button" onclick="save_live_info(<?php echo $loan_person_id; ?>);">下一步</button>
		</div>
	</div>
</body>

<!--标准mui.js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js"></script>
<!--App自定义的js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.picker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.poppicker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/city.data.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/city.data-3.js" type="text/javascript" charset="utf-8"></script>
<script>
	// 给用户提示的弹框
	function showDialog()
	{
		$("#mask").height(window.innerHeight);
		$("#mask").show();
	}
	function showPopDialog(message,btn){
		$('#pop_dialog div p').html(message);
		$('#pop_dialog div span').html(btn);
		$('#pop_dialog').show();
		showDialog();
	}
	// 隐藏兑换完毕弹框
	function hideDialog()
	{
		$("#mask").hide();
		$("#pop_dialog").hide();
	}
	(function(mui, doc) {
		mui.init();
	//	var value = $("#residence").val();
	//	console.log(value);
		mui.ready(function() {
			// 现居省市

			var cityPicker3 = new mui.PopPicker({
				layer: 3 // 数据在此处处理
			});
			var pca = <?php echo $pca; ?>;
			var show_already_data = <?php echo $show_already_data; ?>;
			$("#showCityPicker3").attr("present_province",show_already_data.present_province);
			$("#showCityPicker3").attr("present_city",show_already_data.present_city);
			$("#showCityPicker3").attr("present_area",show_already_data.present_area);
			$("#cityResult3").html(show_already_data.present_district_text);
			$("#present_address").val(show_already_data.present_address);

			$("#showHomePicker3").attr("family_province",show_already_data.family_province);
			$("#showHomePicker3").attr("family_city",show_already_data.family_city);
			$("#showHomePicker3").attr("family_area",show_already_data.family_area);
			$("#homeResult3").html(show_already_data.family_district_text);
			$("#family_address").val(show_already_data.family_address);

			cityPicker3.setData(pca);
			var showCityPickerButton = doc.getElementById('showCityPicker3');
			var cityResult3 = doc.getElementById('cityResult3');
			showCityPickerButton.addEventListener('tap', function(event) {
				cityPicker3.show(function(items) {
					cityResult3.innerText = (items[0] || {}).text + " " + (items[1] || {}).text + " " + (items[2] || {}).text;
					$("#showCityPicker3").attr("present_province",items[0].value);
					$("#showCityPicker3").attr("present_city",items[1].value );
					$("#showCityPicker3").attr("present_area",items[2].value );
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			// 老家省市
			var homePicker3 = new mui.PopPicker({
				layer: 3 // 数据在此处处理
			});
			homePicker3.setData(pca);
			var showHomePickerButton = doc.getElementById('showHomePicker3');
			var homeResult3 = doc.getElementById('homeResult3');
			showHomePickerButton.addEventListener('tap', function(event) {
				homePicker3.show(function(_items) {
					homeResult3.innerText = (_items[0] || {}).text + " " + (_items[1] || {}).text + " " + (_items[2] || {}).text;
					$("#showHomePicker3").attr("family_province",_items[0].value);
					$("#showHomePicker3").attr("family_city",_items[1].value );
					$("#showHomePicker3").attr("family_area",_items[2].value );
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);
		});
	})(mui, document);

	function save_live_info(loan_person_id)
	{

		var params = {
			'present_province' : $("#showCityPicker3").attr("present_province"),
			'present_city' : $("#showCityPicker3").attr("present_city"),
			'present_area' : $("#showCityPicker3").attr("present_area"),
			'present_district_text' : $("#cityResult3").html(),
			'present_address' : $("#present_address").val(),
			'family_province' : $("#showHomePicker3").attr("family_province"),
			'family_city' : $("#showHomePicker3").attr("family_province"),
			'family_area' : $("#showHomePicker3").attr("family_province"),
			'family_district_text' : $("#homeResult3").html(),
			'family_address' : $("#family_address").val(),
			'loan_person_id' :loan_person_id,
		};


		$.ajax({
			type: 'post',
			url: '<?php echo Url::to(['train-period/saveaddress']) ; ?>',
			data : params,
			success: function(data) {
				if(0 == data.code)
				{
					window.location.href = "<?php echo Url::toRoute('train-period/page-job');?>";
				}
				else
				{
					showPopDialog(data.message,'确认');
				}
			},
			error: function(ok){
				showPopDialog(data.message,'确认');
			}
		});
	}
</script>
</html>