<?php
use yii\helpers\Url;
use yii\helpers\Html;
use mobile\components\ApiUrl;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/cookie.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/person-center.css">
<div class="wrapper">
    <div>
        <p><img src="<?php echo $baseUrl;?>/image/building/name.png"/><label>姓名</label><span class="content" id="name">…</span></p>
        <p><img src="<?php echo $baseUrl;?>/image/building/account.png"/><label>账户</label><span class="content" id="account">…</span></p>
        <p><img src="<?php echo $baseUrl;?>/image/building/channel.png"/><label>所属渠道</label><span class="content" id="channel">…</span></p>
        <p onclick="Touch()"><img src="<?php echo $baseUrl;?>/image/building/order.png"/><label>我的订单</label><span  id="order"></span></p>
    </div>
    <div>
        <button id="btn" onclick="loginOut()"><span id="word">退出登录</span></button>
    </div>
</div>

<script>
    var params = {
        user_id:<?php echo $id; ?>
    };

    KD.util.post("<?php echo ApiUrl::toRoute(['hfd/get-person-center','clientType'=>'h5'], true); ?>",params,function(data) {
        if(data.code == 0){
            $("#name").html(data.data.item.name);
            $("#account").html(data.data.item.mobile);
            $("#channel").html(data.data.item.shop_name);
        }else if(data.code == -2){
            dialog(data.message,function(){
                window.location.href = "<?php echo Url::toRoute('building/login');?>";
            });
        }else if (data.message == "参数丢失") {
            dialog(data.message,function(){
                window.location.href = "<?php echo Url::toRoute('building/login');?>";
            });
        }else {
            dialog(data.message);
        }
    });

    function Touch(){
        window.location.href = "<?php echo Url::toRoute('building/personal-order');?>";
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

    //退出登录
    function loginOut(){
        KD.util.post("<?php echo ApiUrl::toRoute(['user/logout','clientType'=>'h5'], true); ?>","",function(data) {
            if(data.code == 0){
                dialog(data.message,function(){
                    window.location.href = "<?php echo Url::toRoute('building/login');?>";
                });
            } else {
                dialog(data.message);
            }
        });
    }
</script>