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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title><?php echo $this->title ? $this->title : '口袋快借'; ?></title>
    <!--标准mui.css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.css" rel="stylesheet"/>
    <!--App自定义的css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016040501" rel="stylesheet"/>
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
	    <h1 class="mui-title font-color1">个人信息</h1>
	</header> -->
	<div class="mui-content">
		<form class="mui-input-group">
		    <ul class="mui-table-view">
		    	<li class="mui-input-row my-input-row">
					<label class="my-label1">姓名：</label>
					<input type="text" id="realname" name="realname" class="my-input1" placeholder="请输入姓名" />
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label2">身份证号：</label>
					<input type="text" id="id_number" name="id_number" class="my-input2" placeholder="请输入有效身份证号" />
				</li>
				<li class="mui-input-row my-input-row">
					<label class="my-label1">Q Q：</label>
					<input type="text" id="qq" name="qq" class="my-input1" placeholder="" />
				</li>
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" marital_status ="0"  id='showUserPicker'>婚姻状况：<span id='userResult' class="ui-alert"></span></a>
				</li>
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" degree ="0" id='showCoursePicker'>学历：<span id='courseResult' class="ui-alert"></span></a>
				</li>
				<li class="mui-table-view-cell">
					<a class="mui-navigate-right" job_status ="0" id='showJobPicker'>就职状态：<span id='jobResult' class="ui-alert"></span></a>
				</li>
				<li class="mui-input-row my-input-row" style="display: none" id="custome_show">
					<label class="my-label2">毕业院校：</label>
					<input type="text" name="custom_college" class="my-input2" id="custom_college" placeholder="请输入毕业院校名称" />
				</li>
				<li class="mui-table-view-cell" style="display: none" id="choose_show">
					<a class="mui-navigate-right" school_area ="0" school_id ="0" id="showCityPicker3">毕业院校：<span id="cityResult3" class="ui-alert"></span></a>
				</li>
			</ul>
			<!-- 半透明遮掩 -->
			<div id="mask" ></div>
			<!-- 登录弹框 -->
			<div id="pop_dialog" align="center" class="_hidden1" style="opacity:1;z-index: 10000">
				<div class="pop_dialog">
					<p class="message"></p>
					<span class="convert_btn" onclick="hideDialog()"></span>
				</div>
			</div>
		</form>
		<div class="padding _666 em__9 fd5457 em__9" id="msg">&nbsp;&nbsp;</div>
		<div class="mui-content-padded">
			<button id="submit_btn_" class="mui-btn mui-btn-block bgcolor1 font-color1" type="button" onclick="save_person_info(<?php echo $loan_person_id; ?>);">下一步</button>
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
			// 婚姻状况
			var marital = <?php echo $marital; ?>;
			var degree = <?php echo $degree; ?>;
			var identity = <?php echo $identity; ?>;
			var college = <?php echo $college; ?>;
			var userPicker = new mui.PopPicker();
			userPicker.setData(marital);
			var showUserPickerButton = doc.getElementById('showUserPicker');
			var userResult = doc.getElementById('userResult');
			showUserPickerButton.addEventListener('tap', function(event) {
				userPicker.show(function(items) {
//					userResult.innerText = JSON.stringify(items[0]);
					userResult.innerText = (items[0] || {}).text;
					$("#showUserPicker").attr("marital_status",items[0].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);
			
			// 学历
			var coursePicker = new mui.PopPicker();
			coursePicker.setData(degree);
			var showCoursePickerButton = doc.getElementById('showCoursePicker');
			var courseResult = doc.getElementById('courseResult');
			showCoursePickerButton.addEventListener('tap', function(event) {
				coursePicker.show(function(items) {

//					userResult.innerText = JSON.stringify(items[0]);
					courseResult.innerText = (items[0] || {}).text;
					$("#showCoursePicker").attr("degree",items[0].value);
					if(3 > items[0].value){

						$("#custome_show").show();
						$("#choose_show").hide();
					}else {
						$("#choose_show").show();
						$("#custome_show").hide();
					}
				});
			}, false);
			
			// 就职状态
			var jobPicker = new mui.PopPicker();
			jobPicker.setData(identity);
			var showJobPickerButton = doc.getElementById('showJobPicker');
			var jobResult = doc.getElementById('jobResult');
			showJobPickerButton.addEventListener('tap', function(event) {
				jobPicker.show(function(items) {
//					userResult.innerText = JSON.stringify(items[0]);
					jobResult.innerText = (items[0] || {}).text;
					$("#showJobPicker").attr("job_status",items[0].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			// 培训地址
			var cityPicker3 = new mui.PopPicker({
				layer: 2 // 数据在此处处理
			});
			cityPicker3.setData(college);
			var showCityPickerButton = doc.getElementById('showCityPicker3');
			var cityResult3 = doc.getElementById('cityResult3');
			showCityPickerButton.addEventListener('tap', function(event) {
				cityPicker3.show(function(items) {
					cityResult3.innerText = (items[0] || {}).text + " " + (items[1] || {}).text;
					$("#showCityPicker3").attr("school_area",items[0].value);
					$("#showCityPicker3").attr("school_id",items[1].value);
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);

			//填充已经保存的数据
			var realname = <?php echo "'".$realname."'"; ?>;
			var id_number = <?php echo "'".$id_number."'"; ?>;
			var qq = <?php echo "'".$qq."'"; ?>;
			var marital_status = <?php echo $marital_status; ?>;
			var marital_status_text = <?php echo "'".$marital_status_text."'"; ?>;
			var _degree = <?php echo $_degree; ?>;
			var degree_text = <?php echo "'".$degree_text."'"; ?>;
			var job_status = <?php echo $job_status; ?>;
			var job_status_text = <?php echo "'".$job_status_text."'"; ?>;
			var school_area = <?php echo $school_area; ?>;
			var school_id = <?php echo $school_id; ?>;
			var school_name = <?php echo "'".$school_name."'"; ?>;
			var school_district_text = <?php echo "'".$school_district_text."'"; ?>;
			var is_verify = <?php echo $is_verify; ?>;
			$("#realname").val(realname);
			$("#id_number").val(id_number);
			$("#qq").val(qq);
			$("#showUserPicker").attr("marital_status",marital_status);
			$("#userResult").html(marital_status_text);

			$("#showCoursePicker").attr("degree",_degree);
			$("#courseResult").html(degree_text);

			$("#showJobPicker").attr("job_status",job_status);
			$("#jobResult").html(job_status_text);

			$("#custom_college").val(school_name);

			$("#showCityPicker3").attr("school_area",school_area);
			$("#showCityPicker3").attr("school_id",school_id);
			$("#cityResult3").html(school_district_text);

			if("0" != _degree)
			{
				if(3 >_degree)
				{
					$("#custome_show").show();
					$("#choose_show").hide();
				}
				else
				{
					$("#custome_show").hide();
					$("#choose_show").show();
				}
			}

			if(1 == is_verify)
			{
				$("#realname").attr("disabled","disabled");
				$("#id_number").attr("disabled","disabled");
			}
			var is_save = <?php echo $is_save ? $is_save : 'undefined'; ?>;
			if(is_save){
				$("#qq").attr("disabled","disabled");
				$("#showUserPicker").attr("disabled","disabled");
				$("#userResult").attr("disabled","disabled");
				$("#showCoursePicker").attr("disabled","disabled");
				$("#courseResult").attr("disabled","disabled");
				$("#showJobPicker").attr("disabled","disabled");
				$("#jobResult").attr("disabled","disabled");
				$("#custome_show").attr("disabled","disabled");


				$("#custom_college").attr("disabled","disabled");
				$("#choose_show").attr("disabled","disabled");
				$("#showCityPicker3").attr("disabled","disabled");
				$("#cityResult3").attr("disabled","disabled");
				$("#submit_btn_").attr("disabled","disabled");
			}




		});
	})(mui, document);

	function save_person_info(loan_person_id)
	{
		$("#msg").html("&nbsp;&nbsp;");
		var realname = $("#realname").val();
		if("" == realname)
		{
			$("#msg").html("姓名不能为空");
			return;
		}

		var id_number = $("#id_number").val();
		if("" == id_number)
		{
			$("#msg").html("身份证不能为空");
			return;
		}

		var qq = $("#qq").val();

		var marital_status =$("#showUserPicker").attr("marital_status");
		if("0" == marital_status)
		{
			$("#msg").html("请选择婚姻状况");
			return;
		}

		var degree =$("#showCoursePicker").attr("degree");
		if("0" == degree)
		{
			$("#msg").html("请选择学历");
			return;
		}

		var job_status =$("#showJobPicker").attr("job_status");
		if("0" == job_status)
		{
			$("#msg").html("请选择就职状态");
			return;
		}

		var school_area = 0;
		var school_name = "";
		var school_id=0;
		var school_district_text = "";
		console.log(degree);
		if(3 >degree)
		{
			school_district_text = $("#custom_college").val();
			school_name = school_district_text;
			if("" == school_district_text)
			{
				$("#msg").html("请输入毕业院校名称");
				return;
			}
		}
		else
		{
			school_area = $("#showCityPicker3").attr("school_area");
			school_id = $("#showCityPicker3").attr("school_id");
			school_district_text=$("#cityResult3").html();
			school_name = school_district_text;
			if(("0" == school_area)||("0" == school_id))
			{
				$("#msg").html("请选择毕业院校");
				return;
			}
		}

		var params = {
			'realname':realname,
			'id_number':id_number,
			'qq':qq,
			'marital_status':marital_status,
			'degree':degree,
			'job_status':job_status,
			'school_area':school_area,
			'school_name':school_name,
			'school_id':school_id,
			'school_district_text':school_district_text,
			'loan_person_id' :loan_person_id
		};

		$.ajax({
			type: 'post',
			url: '<?php echo Url::to(['train-period/savepersoninfo']) ; ?>',
			data : params,
			success: function(data) {
				if(0 == data.code)
				{
					window.location.href = "<?php echo Url::toRoute('train-period/page-live');?>";
				}
				else
				{
					showPopDialog(data.message,'确认');
				}
			},
			error: function(ok){
				//showPopDialog(data.message,'确认');
			}
		});
	}
</script>
</html>