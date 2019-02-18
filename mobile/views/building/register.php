<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="wrapper">
    <div class="input-group">
        <input type="text" id="name" required="required" placeholder="请输入真实姓名">
    </div>
    <div class="input-group">
        <input type="text" id="phone" required="required" placeholder="请输入注册手机号">
    </div>
    <button id="btn" onclick="nextStep()"><span id="word">下一步</span></button>
    <p>注册即成为房产中介经纪人，坐享高额返佣</p>
</div>
<script type="text/javascript">
    function nextStep(){
        var phone = $("#phone").val();
        var phone_reg = /^[1]\d{10}$/;
        var name_reg = /^[\u4e00-\u9fa5 ]{2,20}$/;
        var name = $("#name").val();
        if(name == "") {
            dialog("姓名不能为空");
            return false;
        }
        if ( !name_reg.test(name) ){
            dialog("姓名不合法");
            return false;
        }
        if(phone == "") {
            dialog("手机号码不能为空");
            return false;
        }
        if ( !phone_reg.test(phone) ){
            dialog("手机号码不合法");
            return false;
        }
        var params = {
            phone:phone
        };
        var source = <?php echo $source; ?>;
        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['user/reg-get-code','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0) {
                formPost("<?=Url::toRoute(['building/confirm-password'],true)?>",{phone:phone,source:source,name:name});
            } else if(data.code == 1000) {
                dialog('此手机号已注册，' + data.message,function(){
                    window.location.href = "<?php echo Url::toRoute(['building/login'],true);?>";
                });
            } else {
                dialog(data.message);
            }
        });
    }

    // 消息弹窗
    function dialog(g,f){
        var $e=$('<div class="pop-box"><div class="pop-con"><p>'+g+"</p><button>确认</button></div></div>");
        $e.appendTo("body");
        $e.find("button").on("click",function(a){
            a.preventDefault();
            $e.remove();
            if(typeof (f) == "function"){
                f();
            }
        });
    }

</script>