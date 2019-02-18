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
	    <h1 class="mui-title font-color1">添加联系人</h1>
	</header> -->
	<div class="mui-content">
		<div class="flex-container bgcolor2">
			<div class="flex-ul"><img src="<?php echo $this->absBaseUrl; ?>/image/train-period/jindu3.png"/></div>
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
		    	<li class="mui-table-view-cell">
					<a class="mui-navigate-right" first_contact_relation="0" id="first_contact_relation">第一联系人：<span id="first_contact_relation_result" class="ui-alert"></span></a>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">姓名：</label>
					<input type="text" name="first_contact_name" id="first_contact_name" class="my-input2" placeholder="请输入姓名" />
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">手机号：</label>
					<input type="text" name="first_contact_phone" id="first_contact_phone" class="my-input2" placeholder="请输入电话号码" />
				</li>

				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" second_contact_relation="0" id="second_contact_relation">第二联系人：<span id="second_contact_relation_result" class="ui-alert"></span></a>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">姓名：</label>
					<input type="text" name="second_contact_name" id="second_contact_name" class="my-input2" placeholder="请输入姓名" />
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">手机号：</label>
					<input type="text" name="second_contact_phone" id="second_contact_phone" class="my-input2" placeholder="请输入电话号码" />
				</li>

				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" third_contact_relation="0" id="third_contact_relation">第三联系人：<span id="third_contact_relation_result" class="ui-alert"></span></a>
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">姓名：</label>
					<input type="text" name="third_contact_name" id="third_contact_name" class="my-input2" placeholder="请输入姓名" />
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">手机号：</label>
					<input type="text" name="third_contact_phone" id="third_contact_phone" class="my-input2" placeholder="请输入电话号码" />
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
			<button class="mui-btn mui-btn-block bgcolor1 font-color1" type="button" onclick="save_contact_info(<?php echo $loan_person_id; ?>);">下一步</button>
		</div>
	</div>
</body>
<!--标准mui.js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.min.js"></script>
<!--App自定义的js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.picker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.poppicker.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js"></script>
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
		mui.ready(function() {
			var contact_list = <?php echo $contact_list; ?>;
			// 关系 first_
			var first_shipPicker = new mui.PopPicker();

			first_shipPicker.setData(contact_list);
			var first_showShipPickerButton = doc.getElementById('first_contact_relation');
			var first_shipResult = doc.getElementById('first_contact_relation_result');
			first_showShipPickerButton.addEventListener('tap', function(event) {
				first_shipPicker.show(function(items) {
					first_shipResult.innerText = (items[0] || {}).text;
					$("#first_contact_relation").attr("first_contact_relation",items[0].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			// 关系 second_
			var second_shipPicker = new mui.PopPicker();

			second_shipPicker.setData(contact_list);
			var second_showShipPickerButton = doc.getElementById('second_contact_relation');
			var second_shipResult = doc.getElementById('second_contact_relation_result');
			second_showShipPickerButton.addEventListener('tap', function(event) {
				second_shipPicker.show(function(items) {
					second_shipResult.innerText = (items[0] || {}).text;
					$("#second_contact_relation").attr("second_contact_relation",items[0].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			// 关系 third_
			var third_shipPicker = new mui.PopPicker();

			third_shipPicker.setData(contact_list);
			var third_showShipPickerButton = doc.getElementById('third_contact_relation');
			var third_shipResult = doc.getElementById('third_contact_relation_result');
			third_showShipPickerButton.addEventListener('tap', function(event) {
				third_shipPicker.show(function(items) {
					third_shipResult.innerText = (items[0] || {}).text;
					$("#third_contact_relation").attr("third_contact_relation",items[0].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);




		});

		//填充已经保存的数据
		//$first_contact_relation=0;
		//$first_contact_name="";
	///	$first_contact_phone="";
		//$second_contact_relation=0;
		//$second_contact_name="";
		//$second_contact_phone="";
		//$third_contact_relation=0;
		//$third_contact_name="";
		//$third_contact_phone="";
		var first_contact_relation = <?php echo "'".$first_contact_relation."'"; ?>;
		var first_contact_relation_result = <?php echo "'".$first_contact_relation_result."'"; ?>;
		$("#first_contact_relation").attr("first_contact_relation",first_contact_relation);
		$("#first_contact_relation_result").html(first_contact_relation_result);

		var second_contact_relation = <?php echo "'".$second_contact_relation."'"; ?>;
		var second_contact_relation_result = <?php echo "'".$second_contact_relation_result."'"; ?>;
		$("#second_contact_relation").attr("second_contact_relation",second_contact_relation);
		$("#second_contact_relation_result").html(second_contact_relation_result);

		var third_contact_relation = <?php echo "'".$third_contact_relation."'"; ?>;
		var third_contact_relation_result = <?php echo "'".$third_contact_relation_result."'"; ?>;
		$("#third_contact_relation").attr("third_contact_relation",third_contact_relation);
		$("#third_contact_relation_result").html(third_contact_relation_result);

		var first_contact_name = <?php echo "'".$first_contact_name."'"; ?>;
		$("#first_contact_name").val(first_contact_name);
		var first_contact_phone = <?php echo "'".$first_contact_phone."'"; ?>;
		$("#first_contact_phone").val(first_contact_phone);
		var second_contact_name = <?php echo "'".$second_contact_name."'"; ?>;
		$("#second_contact_name").val(second_contact_name);
		var second_contact_phone = <?php echo "'".$second_contact_phone."'"; ?>;
		$("#second_contact_phone").val(second_contact_phone);
		var third_contact_name = <?php echo "'".$third_contact_name."'"; ?>;
		$("#third_contact_name").val(third_contact_name);
		var third_contact_phone = <?php echo "'".$third_contact_phone."'"; ?>;
		$("#third_contact_phone").val(third_contact_phone);


	})(mui, document);

	function save_contact_info(loan_person_id)
	{
		$("#msg").html("");
		var first_contact_relation =$("#first_contact_relation").attr("first_contact_relation");
		if("0" == first_contact_relation)
		{
			showPopDialog('请选择联系人关系','确认');
			return;
		}
		var first_contact_name = $("#first_contact_name").val();
		if("" == first_contact_name)
		{
			showPopDialog('请输入联系人姓名','确认');
			return;
		}
		var first_contact_phone = $("#first_contact_phone").val();
		if("" == first_contact_phone)
		{
			showPopDialog('请输入联系人手机号','确认');
			return;
		}



		var second_contact_relation =$("#second_contact_relation").attr("second_contact_relation");
		if("0" == second_contact_relation)
		{
			showPopDialog('请选择联系人关系','确认');
			return;
		}
		var second_contact_name = $("#second_contact_name").val();
		if("" == second_contact_name)
		{
			showPopDialog('请输入联系人姓名','确认');
			return;
		}
		var second_contact_phone = $("#second_contact_phone").val();
		if("" == second_contact_phone)
		{
			showPopDialog('请输入联系人手机号','确认');
			return;
		}

		var third_contact_relation =$("#third_contact_relation").attr("third_contact_relation");
		if("0" == third_contact_relation)
		{
			showPopDialog('请选择联系人关系','确认');
			return;
		}
		var third_contact_name = $("#third_contact_name").val();
		if("" == third_contact_name)
		{
			showPopDialog('请输入联系人姓名','确认');
			return;
		}
		var third_contact_phone = $("#third_contact_phone").val();
		if("" == third_contact_phone)
		{
			showPopDialog('请输入联系人手机号','确认');
			return;
		}

		var params = {
			'first_contact_relation':first_contact_relation,
			'first_contact_name':first_contact_name,
			'first_contact_phone':first_contact_phone,
			'second_contact_relation':second_contact_relation,
			'second_contact_name':second_contact_name,
			'second_contact_phone':second_contact_phone,
			'third_contact_relation':third_contact_relation,
			'third_contact_name':third_contact_name,
			'third_contact_phone':third_contact_phone,
			'loan_person_id' :loan_person_id
		};

		$.ajax({
			type: 'post',
			url: '<?php echo Url::to(['train-period/savecontactinfo']) ; ?>',
			data : params,
			success: function(data) {
				if(0 == data.code)
				{
					window.location.href = "<?php echo Url::toRoute('train-period/page-photo');?>";
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