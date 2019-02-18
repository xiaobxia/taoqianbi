<!DOCTYPE html>
<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 2015/6/8
 * Time: 16:55
 */
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
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
	    <h1 class="mui-title font-color1">工作信息</h1>
	</header> -->
	<div class="mui-content">
		<div class="flex-container bgcolor2">
			<div class="flex-ul"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/jindu2.png"/></div>
			<div class="flex-div">
				<span class="tr">个人信息</span>
				<span class="tl">工作信息</span>
				<span class="tr">联系人&nbsp;&nbsp;</span>
				<span class="tr">信用信息</span>
			</div>
		</div>
		<div class="mui-content-padded"></div>
		<form class="mui-input-group">
		    <ul class="mui-table-view">
		    	<li class="mui-input-row my-input-row">
					<label class="my-label2">公司名称：</label>
					<input type="text" name="company_name" id="company_name" class="my-input2" placeholder="如：浙江*****有限公司" value="<?php echo isset($loanPersoninfo['company_name']) ? $loanPersoninfo['company_name']:'';?>"/>
				</li>
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" id="showCityPicker3">公司地址：
						<span id="cityResult3" class="ui-alert" company_area="<?php echo isset($loanPersoninfo['company_area']) ? $loanPersoninfo['company_area'] : '';?>"
							  company_city="<?php echo isset($loanPersoninfo['company_city']) ? $loanPersoninfo['company_city'] : '';?>"
							  company_province="<?php echo isset($loanPersoninfo['company_province']) ? $loanPersoninfo['company_province'] : '';?>">
							<?php echo isset($loanPersoninfo['company_pca']) ? $loanPersoninfo['company_pca'] : '';?>
						</span>
					</a>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">详细地址：</label>
					<input type="text" name="company_address" id="company_address" class="my-input2" placeholder="杭州乐清市******" value="<?php echo isset($loanPersoninfo['company_address']) ? $loanPersoninfo['company_address'] : '';?>"/>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">公司电话：</label>
					<input type="text" name="company_phone" id="company_phone" class="my-input2" placeholder="区号-座机号或手机号" value="<?php echo isset($loanPersoninfo['company_phone']) ? $loanPersoninfo['company_phone'] : '';?>"/>
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
			<button class="mui-btn mui-btn-block bgcolor1 font-color1" type="button" id="submit">下一步</button>
		</div>
	</div>
</body>
<!--标准mui.js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.min.js"></script>
<!--App自定义的js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.picker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.poppicker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/city.data.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/city.data-3.js" type="text/javascript" charset="utf-8"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js" type="text/javascript" charset="utf-8"></script>
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
	(function($mui, doc) {
		$mui.init();
		$mui.ready(function() {
			// 公司地址
			var cityPicker3 = new $mui.PopPicker({
				layer: 3 // 数据在此处处理
			});
			var pca = <?php echo $pca; ?>;		//获取省市区数据
			cityPicker3.setData(pca);
			var showCityPickerButton = doc.getElementById('showCityPicker3');
			var cityResult3 = doc.getElementById('cityResult3');
			showCityPickerButton.addEventListener('tap', function(event) {
				cityPicker3.show(function(items) {
					cityResult3.innerText = (items[0] || {}).text + " " + (items[1] || {}).text + " " + (items[2] || {}).text;
					$('#cityResult3').attr('company_province',(items[0] || {}).value);
					$('#cityResult3').attr('company_city',(items[1] || {}).value);
					$('#cityResult3').attr('company_area',(items[2] || {}).value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);


			// 表单提交
			$('#submit').click(function(event){
				var param = {
					'company_name' : $('#company_name').val(),
					'company_address' : $('#company_address').val(),
					'company_phone' : $('#company_phone').val(),
					'company_area' : $('#cityResult3').attr('company_area'),
					'company_city' : $('#cityResult3').attr('company_city'),
					'company_province' : $('#cityResult3').attr('company_province'),
					'company_pca' : $('#cityResult3').html(),
				};
				$.ajax({
					type : "POST",
					url : "set-companyinfo",
					data : param,
					dataType : "json",
					success : function(data){
						if(data.code == 0){
							window.location.href = "<?php echo Url::toRoute('train-period/page-contact-add');?>";
						}else{
							showPopDialog(data.message,'确认');
						}
					},
					error : function(XMLHttpRequest, textStatus, errorThrown){
						showPopDialog('服务器忙','确认');
					}
				});
			})
		});
	})(mui, document);
</script>
</html>