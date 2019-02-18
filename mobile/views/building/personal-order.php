<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\HfdOrder;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/cookie.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/personal-order.css">
<div class="wrapper">

</div>
<script>
    var params = {
        user_id:<?php echo $id; ?>,
        page:1,
        pagsize:999
    };


    KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/get-order-list','clientType'=>'h5'], true); ?>",params,function(data) {
        if(data.code == 0){
            var str;
            if(data.data.item.length == 0) {
                str = '<div class="con1">暂无订单</div> ';
                $('.wrapper').html(str);
            } else {
                var length = data.data.item.length;
                for(var i = 0;i < length;i++){
                    str = '<div class="con"><p id="title">订单号 '+ data.data.item[i].order_id +'</p><div class="hr"></div><div class="detail"><p><label>创建时间</label><span>'+ data.data.item[i].order_time + '</span></p>';
                    str = str + '<p><label>渠道来源</label><span>'+ data.data.item[i].shop_name +'</span></p>';
                    if(data.data.item[i].house_num == ""){
                        str = str + '<p><label>房子地址</label><span>'+ data.data.item[i].address +'</span></p>';
                    } else {
                        str = str + '<p><label>房产证号</label><span>'+ data.data.item[i].house_num +'</span></p>';
                    }
                    str = str + '<p><label>权利人</label><span>'+ data.data.item[i].obligee +'</span></p>';
                    if(data.data.item[i].status >= '<?php echo HfdOrder::STATUS_PENDING_TRIAL_PASS;?>'){
                        if(data.data.item[i].remark == null || data.data.item[i].remark == ""){
                            data.data.item[i].remark = "无";
                        }
                        str = str + '<p><label>借款金额</label><span>'+ data.data.item[i].apply_money +'</span></p><p><label>审核状态</label><span>'+ data.data.item[i].status_word +'</span></p><p><label>授信额度</label><span>'+ data.data.item[i].ture_money +'</span></p><p><label>审核意见</label><span>'+ data.data.item[i].remark +'</span></p>';
                    } else if(data.data.item[i].status == '<?php echo HfdOrder::STATUS_PENDING_TRIAL_NO_PASS;?>') {
                        if(data.data.item[i].remark == null || data.data.item[i].remark == ""){
                            data.data.item[i].remark = "无";
                        }
                        str = str + '<p><label>借款金额</label><span>'+ data.data.item[i].apply_money +'</span></p><p><label>审核状态</label><span>'+ data.data.item[i].status_word +'</span></p><p><label>审核意见</label><span>'+ data.data.item[i].remark +'</span></p>';
                    } else {
                        str = str + '<p><label>借款金额</label><span>'+ data.data.item[i].apply_money +'</span></p><p><label>审核状态</label><span>'+ data.data.item[i].status_word +'</span></p>';
                    }
//                    if(data.data.item[i].picture_count < 5){
//                        str = str + '<p><label>上传产证</label><img order_id='+data.data.item[i].order_id+ ' src="<?php //echo $baseUrl;?>///image/building/jia1.jpg" width="20" height="20" onclick="Upload(this);" /></p>';
//                    }

                    if(data.data.item[i].picture.length != 0){
                        for(var j = 0;j < data.data.item[i].picture.length;j++){
                            str = str + '<img height="50" width="50" src="'+ data.data.item[i].picture[j] +'" onclick="imgZoom(this);" />'
                        }
                    }
                    str = str +'</div></div>';
                    $('.wrapper').append(str);
                }
            }
        } else if(data.message == "参数丢失") {
            dialog(data.message,function(){
                window.location.href = "<?php echo Url::toRoute('building/login');?>";
            });
        } else {
            dialog(data.message);
        }
    });

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

//    function Upload(obj){
//        var order_id = $(obj).attr('order_id');
//        window.location.href = "<?php //echo Url::toRoute(['building/upload-images'],true);?>//"+"?order_id="+order_id;
//    }

    function imgZoom(obj){
        var src = $(obj).attr('src');
        var html = '';
        html += '<div id="mask" onclick="hideExDialog();" style="background: #000 url('+src+') no-repeat center center;background-size:100%;opacity: 1;filter: alpha(opacity=100);-moz-opacity: 1;-khtml-opacity: 1;left:0;"></div>';
        $(".kdlc_mobile_wraper > div").append(html);
    }
</script>