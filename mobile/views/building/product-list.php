<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/product-list.css">
<div class="wrapper">
    <div class="input-group" id="blank">
        <div class="check" onclick="Touch(this)" id="check1" value="1">
            <p><span class="span_title" ></span><a id="title1"></a></p>
            <div class="hr"></div>
            <div class="con">
                <p class="content first-label" id="first"><label></label><span></span></p>
                <p class="content" id="second"><label></label><span></span></p>
            </div>
            <div class="con2">
                <p class="content first-label" id="third"><label></label><span></span></p>
                <p class="content" id="fourth"><label></label><span></span></p>
            </div>
        </div>
    </div>
    <div class="input-group">
        <div class="check" id="check2" onclick="Touch(this)" value="2">
            <p><span class="span_title"></span><a id="title2"></a></p>
            <div class="hr"></div>
            <div class="con">
                <p class="content first-label" id="fifth"><label></label><span></span></p>
                <p class="content" id="sixth"><label></label><span></span></p>
            </div>
            <div class="con2">
                <p class="content first-label" id="seventh"><label></label><span></span></p>
                <p class="content" id="eighth"><label></label><span></span></p>
            </div>
        </div>
    </div>
    <footer><button id="btn" onclick="Apply()"><span id="word">马上申请</span></button></footer>
</div>
<script>
    KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/get-product-list','clientType' => 'h5'],true); ?>",'',function(data){
        if(data.code == 0){
            if(data.data.item[0].type == 1) {
                $("#title1").html(data.data.item[0].title);
                $("#first :nth-child(1)").html(data.data.item[0].period.name);
                $("#first :nth-child(2)").html(data.data.item[0].period.value);
                $("#second :nth-child(1)").html(data.data.item[0].mortgage_rate.name);
                $("#second :nth-child(2)").html(data.data.item[0].mortgage_rate.value);
                $("#third :nth-child(1)").html(data.data.item[0].apr.name);
                $("#third :nth-child(2)").html(data.data.item[0].apr.value);
                $("#fourth :nth-child(1)").html(data.data.item[0].epayment_method.name);
                $("#fourth :nth-child(2)").html(data.data.item[0].epayment_method.value);
            }
            if (data.data.item[1].type == 2) {
                $("#title2").html(data.data.item[1].title);
                $("#fifth :nth-child(1)").html(data.data.item[1].period.name);
                $("#fifth :nth-child(2)").html(data.data.item[1].period.value);
                $("#sixth :nth-child(1)").html(data.data.item[1].mortgage_rate.name);
                $("#sixth :nth-child(2)").html(data.data.item[1].mortgage_rate.value);
                $("#seventh :nth-child(1)").html(data.data.item[1].apr.name);
                $("#seventh :nth-child(2)").html(data.data.item[1].apr.value);
                $("#eighth :nth-child(1)").html(data.data.item[1].epayment_method.name);
                $("#eighth :nth-child(2)").html(data.data.item[1].epayment_method.value);
            }
        } else {
            dialog(data.message);
        }
    });
    var type;
    function Touch(e){
        var span = $(e).children("p").children('span');
        if($(e).hasClass("check_border")) {
            type = undefined;
            $(e).removeClass("check_border");
            span.removeClass('icon');
        } else {
            type = $(e).attr('value');
            $('.check').removeClass("check_border");
            $('span').removeClass('icon');
            $(e).addClass("check_border");
            span.addClass('icon');
        }
    }

    function Apply() {
        if(type == 1 || type == 2) {
            window.location.href = "<?php echo Url::toRoute(['building/personal-info'],true);?>" + '?type=' + type;
        } else {
            dialog("请选择一种类型");
        }
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