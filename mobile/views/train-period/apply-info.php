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

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
	<title><?php echo $this->title ? $this->title : '口袋快借'; ?></title>
	<!--标准mui.css-->
	<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.css" rel="stylesheet"/>
	<!--App自定义的css-->
	<link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css" rel="stylesheet"/>
	<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.picker.css" rel="stylesheet" />
	<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.poppicker.css" rel="stylesheet" />
	<style>
		#pop_dialog{width:100%;position:fixed;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%);max-width:480px;display:none;z-index:10000;}
		#pop_dialog .pop_dialog{background:#fff;width:80%;padding-bottom:10px;border-radius:5px;margin:0 auto;}
		#pop_dialog .message{text-align:center;padding:28px;}
		#pop_dialog .convert_btn{display:block;width:80%;margin:0 auto;padding:10px;background:#FD5353;text-align:center;color:#fff;border-radius:5px;}
		#mask{position:fixed;background-color:black;max-width:480px;top:0;width:100%;opacity:.65;z-index:9999;}
		#pop_dialog .no_confirm{float:left;display:block;width:38%;padding:8px;background:#CCC;text-align:center;color:#fff;border-radius:3px;margin-left:10px;}
		#pop_dialog .yes_confirm{float:right;display:block;width:38%;padding:8px;background:#FD5353;text-align:center;color:#fff;border-radius:3px;margin-right:10px;}
		.clear{ clear:both; }
	</style>
</head>
<body>
<!-- <header class="mui-bar mui-bar-nav bgcolor1">
	<h1 class="mui-title font-color1">申请分期</h1>
</header> -->
<div class="mui-content">
	<form class="mui-input-group">
		<ul class="mui-table-view">
			<li class="mui-table-view-cell">
				<a class="mui-navigate-right" id="showCityPicker3">培训地址：
						<span id="cityResult3" class="ui-alert">
						</span>
				</a>
			</li>
			<li class="mui-table-view-cell">
				<a class="mui-navigate-right" id="showUserPicker">培训学校：
						<span id="userResult" shop_id="0" class="ui-alert">
						</span>
				</a>
			</li>
			<li class="mui-table-view-cell">
				<a class="mui-navigate-right" id="showCoursePicker">报名课程：
						<span id="courseResult" course_id="0" class="ui-alert">
						</span>
				</a>
			</li>
			<li class="my-input-row mui-input-row">
				<label class="my-label1">分期金额：</label>
				<input type="number" id="price" class="my-input2" placeholder="请输入金额" max="" value=""/>
			</li>
			<li class="my-input-row mui-input-row">
				<label class="my-label1">分期期限：</label>
				<input type="number" id="period" class="my-input2" placeholder="请输入分期期限" max="" value=""/>
			</li>
		</ul>
	</form>
	<!-- 半透明遮掩 -->
	<div id="mask" ></div>
	<!-- 用户确认框 -->
	<div id="pop_dialog" align="center">
		<div class="pop_dialog">
			<p class="message"></p>
			<p class="confirm"><a class="no_confirm" onclick="hideDialog()">取消</a><a class="yes_confirm" onclick="">确定</a><div class="clear"></div></p>
			<span class="convert_btn" onclick="hideDialog()">朕知道了</span>
		</div>
	</div>
	<div class="padding _666 em__9 fd5457 em__9" id="msg">&nbsp;&nbsp;</div>
	<div class="mui-content-padded">
		<button class="mui-btn mui-btn-block bgcolor1 font-color1" id="submit" type="button">提交申请</button>
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
	$(function(){
        $('#pop_dialog .confirm').hide();
		$('#pop_dialog .convert_btn').hide();
    });
	// 给用户提示的弹框
	function showDialog()
	{
		$("#mask").height(window.innerHeight);
		$("#mask").show();
	}
	function showPopDialog(message,btn){
		$('#pop_dialog .message').html(message);
		if(btn){
			$('#pop_dialog .convert_btn').hide();
			$('#pop_dialog .confirm').show();
		}else{
			$('#pop_dialog .confirm').hide();
			$('#pop_dialog .convert_btn').show();
		}
		showDialog();
		$('#pop_dialog').show();
	}
	// 隐藏兑换完毕弹框
	function hideDialog()
	{
		$("#mask").hide();
		$("#pop_dialog").hide();
	}
	function toUrl(url){
		window.location.href = url;
	}
	(function($mui, doc) {
		$mui.init();
		$mui.ready(function() {
			// 培训地址
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
					var area_id = items[2].value;
					var param = {area_id : area_id};
					doc.getElementById('userResult').innerText = '';
					$('#userResult').attr('shop_id','');
					userPicker.setData();
					doc.getElementById('courseResult').innerText = '';
					$('#courseResult').attr('course_id','');
					coursePicker.setData();
					//获取学校信息
					$.ajax({
						type : "POST",
						url : "getshop-byarea",
						data : param,
						dataType : "json",
						success : function(data){
							if(data.code == 0){
								school = data.data;
								userPicker.setData(school);
							}else{
								school = undefined;
								course = undefined;
							}
						},
						error : function(XMLHttpRequest, textStatus, errorThrown){
							school = undefined;
							course = undefined;
							showPopDialog('服务器忙，请稍后再试');
						}
					});
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			//培训学校
			var userPicker = new $mui.PopPicker();	//培训学校
			var school;
			userPicker.setData(school);
			var showUserPickerButton = doc.getElementById('showUserPicker');
			var userResult = doc.getElementById('userResult');
			showUserPickerButton.addEventListener('tap', function(event) {
				if(school == undefined){
					return false;
				}
				userPicker.show(function(items) {
					goods_id = (items[0] || {}).value;
					userResult.innerText = (items[0] || {}).text;
					//返回 false 可以阻止选择框的关闭
					//return false;
					var shop_id = items[0].value;
					$('#userResult').attr('shop_id',shop_id);
					var param = {shop_id : shop_id};
					doc.getElementById('courseResult').innerText = '';
					$('#courseResult').attr('course_id','');
					coursePicker.setData();
					$.ajax({
						type : "POST",
						url : "getgoods-byshop",
						data : param,
						dataType : "json",
						success : function(data){
							if(data.code == 0){
								course = data.data
								coursePicker.setData(course);
							}else{
								course = undefined;
							}
						},
						error : function(XMLHttpRequest, textStatus, errorThrown){
							course = undefined;
							showPopDialog('服务器忙，请稍后再试');
						}
					});

				});
			}, false);


			// 报名课程
			var coursePicker = new $mui.PopPicker();	//实例化报名课程
			var course;
			coursePicker.setData(course);
			var showCoursePickerButton = doc.getElementById('showCoursePicker');
			var courseResult = doc.getElementById('courseResult');
			showCoursePickerButton.addEventListener('tap', function(event) {
				if(course == undefined){
					return false;
				}
				coursePicker.show(function(items) {
					courseResult.innerText = (items[0] || {}).text;
					var course_id = (items[0] || {}).value;
					$('#courseResult').attr('course_id',course_id);
					var param = {goods_id : course_id}
					$.ajax({
						type : "POST",
						url : "get-goodsdetail",
						data : param,
						dataType : "json",
						success : function(data){
							if(data.code == 0){
								$('#price').attr('placeholder','输入金额须少于'+data.data.price+'元');
								$('#period').attr('placeholder','输入月份须少于'+data.data.period+'个月');
							}else{
								showPopDialog('服务器忙，请稍后再试');
							}
						},
						error : function(XMLHttpRequest, textStatus, errorThrown){
							showPopDialog('服务器忙，请稍后再试');
						}
					});
				});
			}, false);


			// 表单提交
			$('#submit').click(function(event){
				var param = {
					'shop_id' : $('#userResult').attr('shop_id'),
					'goods_id' : $('#courseResult').attr('course_id'),
					'price' : $('#price').val(),
					'period' : $('#period').val(),
				};
				if(param.shop_id == '' || param.goods_id == '' || param.price == '' || param.period == ''){
					showPopDialog('请填写必要信息再提交申请');
					return false;
				}
				$.ajax({
					type : "POST",
					url : "set-trainorders",
					data : param,
					dataType : "json",
					success : function(data){
						if(data.code == 0){
							$('.yes_confirm').attr('onclick',"toUrl('"+data.url+"')");
							showPopDialog('提交申请成功，请进行下一步',1)
							// window.location.href = data.url;
						}else{
							showPopDialog(data.message);
						}
					},
					error : function(XMLHttpRequest, textStatus, errorThrown){
						showPopDialog('服务器忙，请稍后再试');
					}
				});
			});
		});
	})(mui, document);
</script>
</html>