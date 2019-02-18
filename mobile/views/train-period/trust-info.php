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
	<script src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js" type="text/javascript" charset="utf-8"></script>
	<script src="<?php echo $this->absBaseUrl; ?>/js/jquery.cookie.js" type="text/javascript" charset="utf-8"></script>
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
	    <h1 class="mui-title font-color1">信用信息</h1>
	</header> -->
	<div class="mui-content">
		<div class="flex-container bgcolor2">
			<div class="flex-ul"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/jindu4.png"/></div>
			<div class="flex-div">
				<span class="tl">个人信息</span>
				<span class="">工作信息</span>
				<span class="tr">联系人&nbsp;&nbsp;</span>
				<span class="tr">信用信息</span>
			</div>
		</div>
		<div class="mui-content-padded"></div>
		<form class="mui-input-group">
		    <ul class="mui-table-view">
		    	<li class="mui-input-row my-input-row">
					<p class="my-font1">口袋快借承诺确保您的信息安全</p>
				</li>
		    	<li class="mui-table-view-cell">
					<?php if($zmop_judge): ?>
					<a>
						<div class="my-image my-img2"></div>
						<p class="my-font2" style="color:green;font-weight: bold">芝麻信用已授权</p>
						<span id="shipResult" class="ui-alert"></span>
					</a>
					<?php else: ?>
					<a class="" id="showShipPicker" onclick="getRegCodeAgain();">
						<div class="my-image my-img2"></div>
						<p class="my-font2" id="zmop-text">点击获取芝麻信用授权短信</p>
						<span id="shipResult" class="ui-alert"></span>
					</a>
					<?php endif; ?>
				</li>
				<li class="mui-table-view-cell">
					<?php if($jxl_judge): ?>
						<a>
							<div class="my-image my-img3"></div>
							<p class="my-font2" style="color:green;font-weight: bold">聚信立已授权</p>
							<span id="shipResult" class="ui-alert"></span>
						</a>
					<?php else: ?>
					<a id="jxlSms" onclick="getJxlAgain();">
						<div class="my-image my-img3"></div>
						<p class="my-font2" id="jxl-text">点击获取聚信立授权短信</p>
						<span id="shipResult" class="ui-alert"></span>
					</a>
					<?php endif; ?>
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
		<div class="my-agree">
			<span></span>
			<p>我同意《<a href="">口袋分期用户服务协议</a>》</p>
		</div>
		<div class="mui-content-padded">
			<button id="return" class="mui-btn mui-btn-block bgcolor1 font-color1" type="button">返回</button>
		</div>
	</div>
</body>
<!--标准mui.js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.min.js"></script>
<!--App自定义的js-->
<script>
	$(function(){
		if($.cookie('zmopsmstag') == 1){
			getRegCode();
		}
		if($.cookie('jxlsmstag') == 1){
			getJxl();
		}
	});

	$('#return').click(function(){
		window.location.href = "<?php echo $this->absBaseUrl.'/train-period/page-personal-center';?>";
	});
	//调用芝麻信用短信记时
	function getRegCode(){
		codeTiming();
	}
	//芝麻信用短信记时
	function codeTiming(){
		$('#showShipPicker').removeAttr("onclick");
		var time = 60;
		var interval = setInterval(function(){
			time--;
			$('#zmop-text').html('请在'+ time + ' 秒后，点击获取芝麻信用授权短信');
			if( time < 0){
				clearInterval(interval);
				time = 60;
				$.cookie('zmopsmstag',null);
				$('#zmop-text').html("点击获取芝麻信用授权短信");
				$('#showShipPicker').attr("onclick","getRegCodeAgain()");
			}
		},1000);
	}
	//调用芝麻信用短信接口
	function getRegCodeAgain(){
		$.cookie('zmopsmstag',1,7);
		codeTiming();
		var params;
		$.ajax({
			type: 'post',
			url: '<?php echo Url::to(['train-period/zmop-sms']) ; ?>',
			data : params,
			success: function(data) {
				if(data.code == 0){
					showPopDialog.log(data.message);
				}else{
					showPopDialog(data.message,"确认");
				}
			},
			error: function(data){
				showPopDialog('服务器忙，请稍后再试',"确认");
			}
		});
	}


	//调用聚信立短信记时
	function getJxl(){
		JxlTiming();
	}
	//聚信立短信记时
	function JxlTiming(){
		$('#jxlSms').removeAttr("onclick");
		var time = 60;
		var interval = setInterval(function(){
			time--;
			$('#jxl-text').html('请在'+ time + ' 秒后，点击获取聚信立授权短信');
			if( time < 0){
				clearInterval(interval);
				time = 60;
				$.cookie('jxlsmstag',null);
				$('#jxl-text').html("点击获取聚信立授权短信");
				$('#jxlSms').attr("onclick","getJxlAgain()");
			}
		},1000);
	}
	//调用聚信立短信接口
	function getJxlAgain(){
		$.cookie('jxlsmstag',1,7);
		JxlTiming();
		var params;
		$.ajax({
			type: 'post',
			url: '<?php echo Url::to(['train-period/jxl-sms']) ; ?>',
			data : params,
			success: function(data) {
				if(data.code == 0){
					showPopDialog(data.message,'确认');
				}else{
					showPopDialog(data.message,"确认");
				}
			},
			error: function(data){
				showPopDialog('服务器忙，请稍后再试',"确认");
			}
		});
	}

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

		});
	})(mui, document);
</script>
</html>