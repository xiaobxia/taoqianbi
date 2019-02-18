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
<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/process.css?v=2015061901">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/cookie.js?v=2015062301" ></script>
<div id="wrapper" class="m_background">
    <div class="header">
        <div class="general_head_bg">
            <div class="m_input_text_bg" style="width: 68%;float: left;border-radius: 0;border-bottom: solid 1px #dbdbdb;">
                <input type="text" id="input_message" class="m_input_text" name="pwd_set" placeholder="请输入短信验证码"/>
            </div>
            <div id="click_register" class="click_get" onclick="getRegCode();">点击获取</div>
            <div style="clear: both"></div>
        </div>
        <div id="pwd_rule_words">
            请输入6-16位字母和数字，区分大小写
        </div>
        <div id="pwd_set">
            <div class="m_input_text_bg">
                <input type="password"  maxlength="16" id="input_pwd_set" name="pwd_set" class="m_input_text" placeholder="请设置登录密码" />
            </div>
        </div>
        <div id="pwd_set_again" class="m_input_text_bg">
            <input type="password"  maxlength="16" id="input_pwd_set_again" name="pwd_set_again" class="m_input_text input_border" placeholder="请重复登录密码" />
        </div>
        <div id="pwd_tip" style="text-align: center;margin-top: 2%;color: #fb5353;">&nbsp;&nbsp;</div>
    </div>
    <div class="content">
        <div id="is_agree">
            <img src="<?php echo $this->absBaseUrl; ?>/image/site/check_box.png" id="check_box_img" width="3%" onclick="check_box_change();">
            <a class="_000" href="http://api.kdqugou.com/page/detail?id=36">我同意《用户使用协议》</a>
        </div>
        <div id="register" class="m_button" style="margin-top: 3%" onclick="user_register();">
            注册
        </div>
    </div>
</div>
<script type="text/javascript">
    var is_enable_reg = false;
    var check_label = true;
    var anni_lottery = <?php echo $anni_lottery;?>;

    $(function(){
        getRegCode();
    });
    $(document).ready(function(){
        Initialization();
    });
    $(window).resize(function(){
        Initialization();
    });
    function Initialization(){
        fontSize();
        isOneScreen();
        $(".m_input_text_bg").css("height",$(document.body).width() * 0.16 + "px");
        $(".m_input_text").css("height",$(document.body).width() * 0.16 + "px");
        $(".click_get").css("height",$(document.body).width() * 0.16 + "px");
        $(".click_get").css("line-height",$(document.body).width() * 0.16 + "px");
    }
    function check_box_change(){
        check_label = !check_label;
        if(check_label){
            $("#check_box_img").attr("src","<?php echo $this->absBaseUrl; ?>/image/site/check_box.png");
            $("#register").css("background-color","#fb5353");
            $("#register").attr("onclick","user_register()");
        }else{
            $("#check_box_img").attr("src","<?php echo $this->absBaseUrl; ?>/image/site/check_box_blur.png");
            $("#register").css("background-color","#999999");
            $("#register").attr("onclick","");
        }
    }
    function codeTiming(){
        $('#click_register').removeAttr("onclick");
        var time = 60;
        $('#click_register').html( time + ' 秒');
        var interval = setInterval(function(){
            time--;
            $('#click_register').html( time + ' 秒');
            if( time < 0){
                clearInterval(interval);
                time = 60;
                $('#click_register').html("点击获取");
                $('#click_register').attr("onclick","getRegCodeAgain()");
            }
        },1000);
    }
    function getRegCode(){
        codeTiming();
    }
    function getRegCodeAgain(){
        codeTiming();
       var phone_number=<?php echo $phone_number; ?>;
        var params = {
            'phone': phone_number
        };

        $.ajax({
            type: 'post',
            url: '<?php echo Url::to(['user/reg-get-code']) ; ?>',
            data : params,
            success: function(data) {
                if(0 == data.code){
                    var url_redirect = "<?php echo \yii\helpers\Html::encode(Yii::$app->getRequest()->get('url_redirect')) ;?>";
                    window.location.href = "<?php echo Url::to(['user/reg-phone'], true);?>" + '?phone_number=' + phone_number + '&url_redirect=' + url_redirect;

                }else{
                    $('#msg').html('&nbsp;&nbsp;');
                    showExDialog(data.message,"确认");
                }

            },
            error: function(){
               // showExDialog(data.message,"确认");
            }
        });
    }
    function valid_login_pwd(phone_number){
        var preg_text = /^[0-9a-zA-Z]{6,16}$/;
        if (!preg_text.exec(phone_number)){
            return false;
        }
        return true;
    }
    function user_register(){
        var pwd_number = $("#input_pwd_set").val();
        var re_pwd_number = $("#input_pwd_set_again").val();
        if( !valid_login_pwd(pwd_number)) {
            document.getElementById("pwd_tip").innerHTML = "密码格式有误！";
            return false;
        } else if(pwd_number != re_pwd_number){
            document.getElementById("pwd_tip").innerHTML = "密码两次不一致！";
            return false;
        }
        var code = $("#input_message").val();
        var source_tag = window.localStorage.getItem("source_tag");
        var appMarket = source_tag ? source_tag : window.source_tag;
        var url = '<?php echo ApiUrl::toRoute(["user/register"],true); ?>&appMarket=' + appMarket;

        var params = {
            'phone' : <?php echo $phone_number; ?>,
            'code' : code,
            'password' : pwd_number
        };

        KD.util.post(url, params, function(data){
            if(data.code == 0){
               // window.localStorage.setItem("real_verify_status",data.user.real_verify_status);
              //  window.localStorage.setItem("card_bind_status",data.user.card_bind_status);
               // window.localStorage.setItem("set_paypwd_status",data.user.set_paypwd_status);
                setCookie('SESSIONID',data.sessionid,'h12');
              //  setCookie('show_packet_tips_time','48h','h48');
                var url_redirect = "<?php echo \yii\helpers\Html::encode(Yii::$app->getRequest()->get('url_redirect')) ;?>";
                setCookie('url_redirect',url_redirect,'h48');
                window.location.href = "<?php echo Url::to(['train-period/page-personal-center'], true);?>";
            }else{
                showExDialog(data.message,"确认");
            }
        });


    }
</script>