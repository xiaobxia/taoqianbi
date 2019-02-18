<?php
/**
 * Created by PhpStorm.
 * User: Kevin
 * Date: 2016/3/14
 * Time: 14:59
 */
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>

<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/style.css?v=20150601101">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/cookie.js?v=2015062301" ></script>
<div id="user_reg_wraper">
    <div class="padding _666 em__9 a_right" id="tips">已有账号？<a class="fa5558" href="<?php echo Url::toRoute(["user/user-login"],true); ?>">点击登录</a></div>
    <input class="padding em_1" id="phone_number" type="text" maxlength="11" placeholder="请输入手机号" value="<?php echo $user_phone; ?>" onkeyup="JustFloat(this);"/>
    <div class="padding fd5457 em__8" id="msg">&nbsp;&nbsp;</div>
    <div class="padding">
        <div class="fff em_1 a_center _b_radius" id="btn" onclick="getRegCode();">下一步</div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        Initialization();
    });
    $(window).resize(function(){
        Initialization();
    });
    function Initialization(){
        fontSize();
        isOneScreen();
    }

    function getRegCode(){
        var phone_number = $("#phone_number").val();
        var mobile_reg = /^[1]\d{10}$/;

        if(phone_number == ""){
            $('#msg').html('手机号不能为空！');
            return false;
        }
        if ( !mobile_reg.test(phone_number) ){
        $('#msg').html('手机号不合法');
        return false;
        }
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
            error: function(XMLHttpRequest, textStatus, errorThrown){

                $('#msg').html('&nbsp;&nbsp;');
            }
        });

//        console.log("data=".data);
//        if(data.code == 0){
//            var url_redirect = "<?php //echo \yii\helpers\Html::encode(Yii::$app->getRequest()->get('url_redirect')) ;?>//";
//            window.location.href = "<?php //echo Url::to(['user/reg-phone'], true);?>//" + '?phone_number=' + phone_number + '&url_redirect=' + url_redirect;
//        }else{
//            $('#msg').html('&nbsp;&nbsp;');
//            showExDialog(data.message,"确认");
//        }





    }


</script>
