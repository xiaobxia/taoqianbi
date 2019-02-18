<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style>
.clearfix {
  clear: both;
  overflow: hidden; }
	#credit_card_action_wraper {
	  background-color: #f5f5f5; }
	  #credit_card_action_wraper .title {
	    width: 100%;
	    height: 1.253333rem;
	    line-height: 1.253333rem;
	    color: #999;
	    font-size: 0.373333rem;
	    box-sizing: border-box;
	    padding: 0 0.333333rem; }

	  #credit_card_action_wraper ul li {
	    font-size: 0.4rem;
	    color: #333;
	    height: 1.333333rem;
	    line-height: 1.333333rem;
	    background-color: #FFF;
	    padding-left:0.333333rem; 
	}
		#credit_card_action_wraper ul li>div{
			border-bottom: 1px solid #ccc;
		}
	    #credit_card_action_wraper ul li .l_title {
	      float: left;
	      width: 2.133333rem;
	      font-size: 0.426667rem;
	      color: #666; }
	  #credit_card_action_wraper ul li:nth-child(1) {
	    margin-bottom: 0.4rem;}
	    #credit_card_action_wraper ul li:nth-child(1)>div{
	    	border: none;
	    }
	    #credit_card_action_wraper ul li:nth-child(1) span {
	      float: left; }
	  #credit_card_action_wraper ul li:nth-child(2) {
	    position: relative;}
	    #credit_card_action_wraper ul li:last-child>div{
	    	border: none;
	    }
		#credit_card_action_wraper ul li ._select{
			float: right;
		}
		#credit_card_action_wraper ul li .l_select{
			border: 1px solid #ccc;
			border-radius: 0.066667rem;
			-webkit-border-radius: 0.066667rem;
			float: left;
			width: 60%;
			margin-top: 0.133333rem;
			line-height: 0.933333rem;
			background: url(http://res.koudailc.com/article/20160401/356fdf093f2d13.png) no-repeat 97% center/0.186667rem 0.146667rem;
		}
		#credit_card_action_wraper ul li .l_select>div{
			overflow: hidden;
			width: 92%;
		}
		#credit_card_action_wraper ul li .l_select #credit_id{
			font-size: 0.4rem;
			width: 108%;
			padding: 0.226667rem;
			outline: none;
			-webkit-appearance: none;
			appearance:none;
			-moz-appearance:none;
			background-color: transparent;
		}
		#credit_card_action_wraper ul li .l_select #credit_id option{
			font-size: 0.2rem;
		}
		#credit_card_action_wraper ul li #code{
			width: 47%;
		}
		#credit_card_action_wraper ul li #action{
			font-size: 0.4rem;
			width: 25%;
			text-align: center;
			background-color: #fff;
			padding-left: 0.133333rem;
			color: #B6843D;
			border-left: 1px solid #ccc;
		}
		#credit_card_action_wraper ._btn{
			display: block;
			width: 90%;
			margin:1.546667rem auto;
			height: 1.306667rem;
			background-color: #4c516a;
			line-height: 1.306667rem;
			text-decoration: none;
			color: #fff;
			font-size: 0.48rem;
			text-align: center;
			border-radius: 0.133333rem;
		}
		#credit_card_action_wraper .lh{
			line-height: 1.26rem;
		}
</style>
<div id="credit_card_action_wraper">
	<p class="title">请填写认证账单里面的银行卡</p>
	<ul>
		<li class="clearfix">
			<div class="l_title">持卡人</div><span><?php echo $person->name?></span>
		</li>
		<li >
			<div class="clearfix">
				<div class="l_title">选择银行</div>
				<div class="l_select">
				    <div>
				    	<select id="credit_id">
                                <?php foreach ($card_list as $bank):?>
                                    <option value ="<?php echo $bank['bank_id']?>"><?php echo $bank['bank_name']?></option>
                                <?php endforeach;?>
				    	</select>
				    </div>
				</div>
			</div>
		</li>
		<li>
			<div class="clearfix">
				<div class="l_title">卡号</div>
				<div>
					<input class="em_1 lh" id="credit_no" maxlength="30" placeholder="请输入银行卡号"/>
				</div>
			</div>
		</li>
		<li>
			<div class="clearfix">
				<div class="l_title">手机号</div>
				<div>
					<input class="em_1 lh" id="phone" maxlength="11" placeholder="请输入银行预留手机号"/>
				</div>
			</div>
		</li>
		<li>
			<div class="clearfix">
				<div class="l_title">验证码</div>
				<div>
					<input class="em_1 lh" id="code" maxlength="6" placeholder="请输入验证码" />
					<button id="action" onclick="getCode()">获取验证码</button>
				</div>
			</div>
		</li>
	</ul>
	<a href="javascript:save();" class="_btn" >确定更换</a>
</div>
<script>
	var popParam = {"btn_bg_color":"#4c516a"};
	function getCode(){
	    var credit_no = $.trim( $("#credit_no").val() ),
	        credit_id = $.trim( $("#credit_id").val() ),
	        phone = $("#phone").val();
	    if (credit_no == "" || credit_id == "" || phone == "") {
	        return showExDialog("请先完善信息", '确定',"","","","","",popParam);
	    }
	    if (!isPhone(phone)) {
	        return showExDialog('手机号码格式不正确', '确定',"","","","","",popParam);
	    }

	    var url = "<?= ApiUrl::toRouteCredit(['credit-card/get-code'], true); ?>";
	    var params = {
	        credit_no:credit_no,
	        credit_id:credit_id,
	        phone:phone,
	        type:2
	    };
	    $.post(url, params, function(data) {
	        if (data && data.code == 0) {
	            getCodeCountDown('获取', 'num秒','action');
	        }
	        else if (data.message) {
	            showExDialog(data.message, '确定',"","","","","",popParam);
	        }
	        else {
	            showExDialog('绑卡获取验证码异常，请稍后重试', '确定',"","","","","",popParam);
	        }
	    });
	}
	function save() {
	    var credit_no = $("#credit_no").val(),
	        credit_id = $("#credit_id").val(),
	        phone = $("#phone").val(),
	        code = $("#code").val();
	    if (credit_no == "" || credit_id == "" || phone == "" || code == "") {
	        return showExDialog("请先完善信息", '确定',"","","","","",popParam);
	    }

	    drawCircle();

	    var url = "<?= ApiUrl::toRouteH5Mobile(['app-page/do-bind-card'], true); ?>";
	    var params = {
	        credit_no:credit_no,
	        credit_id:credit_id,
	        phone:phone,
	        code:code,
            type: 2
	    };
	    $.post(url, params, function(data) {
	        hideCircle();

	        if (data.code == 0) {
	            jumpTo(getSourceUrl());
	        }
	        else {
	            showExDialog(data.message || '绑卡异常，请稍后重试', '确定',"","","","","",popParam);
	        }
	    });

	    $("._btn").css('background','#1782e0')
	}
</script>
