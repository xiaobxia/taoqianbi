<?php
use yii\helpers\Url;
use mobile\components\CheckPayPwd;
?>
<style type="text/css">
body{
    background: #f5f5f7;
}
.lh_em_2{
    line-height: 2em;
}
.choose ul{
    background: #fff;
}
.choose ul li { border-bottom: none; padding: 0 0.4rem 0 1.4666666667rem; line-height: 1.4666666667rem; background-size: 0.88rem 0.88rem;}

.choose ul li:first-child a{
    padding-top: 0.2rem;
    height: 2.4rem;
}
.choose ul li:first-child a i{
    border-color:#31b27a;
    top:0.8333rem;
}
.choose .head {
    height:0.453333rem;
    background-color: #f5f5f5;
}
.popup .dialog a {
    font-size: 0.3466666667rem;
    color: #31b27a;
    text-decoration: none;
    display: block;
    width: 100%;
}
/*.choose ul li:nth-child(2){
    height: 0.453333rem;
    background-color: #f5f5f5;
}*/
.choose ul li:nth-child(3){
    height: 2.4rem;
    background: url('<?= $this->absBaseUrl;?>/image/apply_img/content_icon_zfb.png') no-repeat 0.2666rem 0.65rem/0.88rem 0.866666rem;
}
.choose ul li:nth-child(3) a i{
    border-color:#31b27a;
    top:0.866667rem;
}
.choose ul li .desc{
    display: inline;
    float: none;
    font-size: 0.4rem;
    padding-right: 0;
    position: relative;
    top: -0.8rem;
    color:#999;
}
.choose ul li .coupon-box span{
    display: inline;
    float: none;
    font-size: 0.426667rem;
    padding-right: 0;
}
.choose ul li .alipay{
    height: 1.466667rem;
}
.choose ul li .coupon-box{
    position: relative;
    color:#999;
}
.choose ul li .coupon-box span{
    color:#999;
}
.choose ul li .coupon-box .coupon{
    float: right;
    padding-right: 0.4rem;
}
.choose ul li .alipay-box{
   padding-top: 0.266667rem;
}
/*----------------------------*/
/*.popup .dialog h1 .dikouquan {display:inline-block; width:1.84rem;height:0.4rem;font-size: 0.333333rem;position: relative;top: 0.173333rem;left: 0.0rem; background:url(../img/dikouquan.png) no-repeat center center;}*/
.popup .dialog h1{
    font-size: 0.4rem;

}
.popup .dialog h1 .old-repayment{
    padding-right: 0.133333rem;
    text-decoration: line-through;
    color: #999;
}
.popup .dialog h1 .dikouquan span{
    padding:0.08rem 0;
}
.popup .dialog h1 .dikouquan span:first-child{
    padding-right: 0.16rem;
    padding-top: 0.133333rem;
}
.popup .dialog h1 .dikouquan span:last-child{
    border-left: 1px dashed #f2910a;
    padding: 0.026667rem 0 0.026667rem 0.066667rem;
}
.popup .dialog .cur-repayment{
    width: 7.333333rem;
    border-top: 1px solid #dbdbdb;
    border-bottom: 1px solid #dbdbdb;
    margin: 0 auto;
    font-size: 0.746667rem;
    color: #31b27a;
    text-align: center;
    height: 1.706667rem;
    line-height: 1.706667rem;
    margin-bottom: 0.693333rem;
}
.popup .dialog h1 .dikouquan{
    color: #f2910a;
    font-style: normal;
    border: 1px solid #f2910a;
    padding: 0.066667rem 0.066667rem;
    position: relative;
    top: -0.026667rem;
}
.choose ul li .desc-bankcard{
    display: block;
    float: left;
    font-size: 0.4rem;
    color: #999;
    padding-right: 0.3466666667rem;
    line-height: 0;
}
.choose ul li a{
    height: 2.4rem;
    -webkit-tap-highlight-color:transparent;
}
._bankcard{
    height: 1.4rem;
}
.space{
    height: 0.453333rem;
    width: 100%;
    background-color: #f5f5f5;
}
.choose ul li h2 b {
    display: inline-block;
    font-size: 0.2666666667rem;
    line-height: 1em;
    color: #ffca4c;
    border: 1px solid #ffca4c;
    background-color:#fff;
    padding: 0.1066666667rem;
    border-radius: 0.1066666667rem;
    vertical-align: 0.0266666667rem;
    margin-left: 0.32rem;
}
.choose ul li:nth-child(1) {
    background: url('<?= $this->absBaseUrl;?>/image/apply_img/content_icon_yhk.png') no-repeat 0.2666rem 0.533333rem/0.88rem 0.88rem;
}
.choose .other-loan{
    font-size: 0.4rem;
    color: #31b27a;
    text-decoration: underline;
    text-align: center;
    display: block;
    margin-top: 90%;
}
/*支付宝弹窗样式*/
.alipay-pop{
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,.5);
    position: fixed;
    top:0;
    display: none;
}
.alipay-pop .content{
    text-align: center;
    width: 8.133333rem;
    background-color: #fff;
    border-radius: 0.133333rem;
    margin: 32% auto;
    padding-bottom: 0.01rem;
}
.alipay-pop .content ._top{
    position: relative;
    padding-top: 0.4rem;

}
.alipay-pop .content ._top .title{
    font-size: 0.48rem;
}
.alipay-pop .content ._top i{
    position: absolute;
    width: 0.346667rem;
    height: 0.346667rem;
    background:transparent url('<?= $this->absBaseUrl;?>/image/apply_img/close.png') no-repeat 0 0/0.346667rem 0.346667rem;
    top: 0.5rem;
    right: 0.4rem;
}
.alipay-pop .content .money {
    padding: 0.4rem 0 0.1rem;
}
.alipay-pop .content .money input{
    color: #999;
    width: 100%;
    border:none;
    font-size: 0.96rem;
    text-align: center;
}
.alipay-pop .content .tips{
    color: red;
    visibility: hidden;
}
.alipay-pop .content #toAlipay{
    width: 90%;
    display: block;
    height: 1.2rem;
    line-height: 1.2rem;
    margin:0.266667rem auto;
    background-color: #31b27a;
    font-size: 0.426667rem;
    color: #fff;
    text-decoration: none;
    border-radius: 0.133333rem;
}
/*加载动画样式*/
.alipay-pop .loading{
    width: 100%;
    height: 100%;
    position: absolute;
    top:0;
    background-color: rgba(0,0,0,.5);
    display: none;
    z-index: 999;
}
.alipay-pop .spinner {
  margin: 60% auto;
  width: 2.133333rem;
  height: 2.133333rem;
  position: relative;
  text-align: center;
  -webkit-animation: rotate 2.0s infinite linear;
  animation: rotate 2.0s infinite linear;
}

.alipay-pop .dot1, .alipay-pop .dot2 {
  width: 60%;
  height: 60%;
  display: inline-block;
  position: absolute;
  top: 0;
  background-color: #31b27a;
  border-radius: 100%;

  -webkit-animation: bounce 2.0s infinite ease-in-out;
  animation: bounce 2.0s infinite ease-in-out;
}

.alipay-pop .dot2 {
  top: auto;
  bottom: 0px;
  -webkit-animation-delay: -1.0s;
  animation-delay: -1.0s;
}

@-webkit-keyframes rotate { 100% { -webkit-transform: rotate(360deg) }}
@keyframes rotate { 100% { transform: rotate(360deg); -webkit-transform: rotate(360deg) }}

@-webkit-keyframes bounce {
  0%, 100% { -webkit-transform: scale(0.0) }
  50% { -webkit-transform: scale(1.0) }
}

@keyframes bounce {
  0%, 100% {
    transform: scale(0.0);
    -webkit-transform: scale(0.0);
  } 50% {
    transform: scale(1.0);
    -webkit-transform: scale(1.0);
  }
}
</style>
<head>
</head>
<div class="choose">
    <div class="head">
        <!--<h3><?=$this->title?></h3>-->
    </div>
    <ul>
        <?php if($useFy){ ?>
            <li>
                <?php if($selectBank){ ?>
                <a href="javascript:showBanks('<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>')">
                    <?php }else{ ?>
                    <a href="<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>">
                        <?php } ?>
                        <h2>银行卡还款<b>官方推荐</b></h2>
                        <span><?=$order['bank_info']?></span>
                        <i></i>
                    </a>
            </li>
        <?php }else{ ?>
            <li>
            <!-- 银行卡打点 -->
                <a href="javascript:<?=($selectBank ? 'showBanks()':'showPayPwd()');?>" class="bankPoint">
                   <div class="clearfix _bankcard">
                       <h2>银行卡还款<b>官方推荐</b></h2>
                       <span><?=$order['bank_info']?></span>
                       <i></i>
                   </div>
                   <span class="desc-bankcard">适用于银行卡还款方便的用户</span>
                </a>
                 <!--<div class="coupon-box"><span>请选择优惠券</span><span class="coupon">暂无可用</span><i></i></div>-->
            </li>
        <?php } ?>
            <div class="space"></div>
        <li>
            <a href="javascript:void(0);" class="clearfix" id="alipay">
                <div class="alipay-box clearfix">
                    <h2 class="alipay">支付宝还款</h2>
                    <span>查看</span>
                    <i></i>
                </div>
                <div><span class="desc">适用于支付宝还款方便的用户</span></div>
            </a>
        </li>
    </ul>
    <?php if ($repayment['overdue_day'] > 10 || in_array(Yii::$app->user->identity->phone,[NOTICE_MOBILE])) {?>
        <a href="<?=Url::toRoute(['loan/loan-repayment-aliapy','id'=>$order['id']])?>" class="other-loan">其他还款方式</a>
    <?php }?>
    <!-- <p class="lh_em_2">备注：若在借款期间内未主动发起还款，则默认于还款日当天从绑定银行卡<?=$order['bank_info']?>自动扣除所借款项，请保证在扣款之前帐户资金充足。</p> -->
</div>
<!-- 支付宝弹窗 -->
<div class="alipay-pop">
    <?php $form = \yii\widgets\ActiveForm::begin(['id'=>'aliyPaymentForm']); ?>
        <div class="content">
            <div class="_top">
                <div class="title">还款金额</div>
                <i class="close-alipay"></i>
            </div>
            <input type="hidden" name = "id" value="<?=$id?>">
            <div class="money">
                <input type="number" name = "operateMoney" value="<?php echo sprintf("%0.2f",$repayment['remain_money_amount'] / 100);?>" readonly="readonly"></div>
            <div class="tips">请输入整百的还款金额</div>
            <a href="#" id="toAlipay">打开支付宝</a>
        </div>
    <?php \yii\widgets\ActiveForm::end(); ?>
    <div class="loading">
        <!-- <img src="<?= $this->absBaseUrl?>/image/loading.gif" alt=""> -->
        <div class="spinner">
          <div class="dot1"></div>
          <div class="dot2"></div>
        </div>
    </div>
</div>
<?php
if($selectBank){
    if($order['coupon_id'] == 0 && $repayment['coupon_id']){
        //无抵扣卷
        $header = '<h2>还款总额</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</h1>';
    }elseif($repayment['is_overdue']){
        //逾期
        $header = '<h2>还款总额</h2><h1><span>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</span>'.
                  '<span class="dikouquan">已逾期抵扣劵不可用</span></h1>'.
                  '<div class="cur-repayment">'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</div>';
    }elseif(($order['coupon_id'] || $repayment['coupon_id']) && $free_money > 0){
        //有抵扣卷
        $header = '<h2>还款总额</h2><h1><span class="old-repayment">'.sprintf("%0.2f",$repayment['total_money'] / 100).'</span>'
                  .'<span class="dikouquan"><span>'.sprintf("%0.2f",$free_money / 100).'元</span><span>抵扣券</span></span></h1><div class="cur-repayment">'.sprintf("%0.2f",($repayment['remain_money_amount']) / 100).'</div>';
    }else{
        $header = '<h2>还款总额</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</h1>';
    }
    echo CheckPayPwd::widget([
        'js_callback' => 'success_callback',
        'header' => $header,
    ]);
}else{
    echo CheckPayPwd::widget([
        'success_url' => Url::toRoute(['loan/pay-apply','id'=>$order['id']]),
        'header' => '<h2>还款总额</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'元</h1>',
    ]);
}
?>
<?php  if($selectBank){ ?>
    <div class="popup-select" style="display: none;">
        <div class="overlay"></div>
        <div class="content">
            <div class="close-div"><span class="close"></span></div>
            <h3>选择银行卡</h3>
            <div class="select-content">
                <?php $bad_cards = []; ?>
                <?php foreach($myCards as $card){ ?>
                    <?php if(isset($card['bank_maintaining'])){ ?>
                        <?php $bad_cards[] = $card; ?>
                    <?php } else { ?>
                        <h4 class="bank" onclick="selectBank(<?=$card['card_id']?>)"><?=$card['bank_name'].'('.$card['card_no_end'].')'?></h4>
                    <?php }?>
                <?php }?>
                <a href="<?=Url::toRoute(['loan/bind-card','source_id'=>$order['id'],'source'=>'add-card'])?>" class="add-bank"><span class="add"></span>添加银行卡</a>
                <?php if(count($bad_cards) > 0){ ?>
                    <?php foreach($bad_cards as $card){ ?>
                        <h4 class="bank single-out"><span><?=$card['bank_name'].'('.$card['card_no_end'].')'?><br><small><?=$card['bank_maintaining_info']?></small></span></h4>
                    <?php }?>
                <?php }?>
            </div>
        </div>
    </div>
    </div>
    <script>
    try {
        setWebViewFlag();
        //页面进入时打点
        MobclickAgent.onEvent('return_type')
    } catch(e) {
        console.log(e);
    }
        <?php if ($type == 1): ?>
        showBanks();
        <?php endif; ?>
        var repayment_card_id = 0;
        var repayment_url = '';
        function showBanks(url){
            <?php if ($isPayed) {?>
                showExDialog("<?=$alertMgs;?>","确定","hideLoading");
                return false;
            <?php } ?>
            if(url){
                repayment_url = url;
            }
            $(".popup-select").show();
            $(".content").removeClass("popup-out");
            $(".content").addClass("popup-in");
        }
        function selectBank(card_id){
            repayment_card_id = card_id;
            if(repayment_url){
                var params = {card_id:repayment_card_id,id:<?= $order['id'] ?>};
                formPost(repayment_url,params,'get');
            }else{
                showPayPwd(card_id);
            }
        }
        function success_callback(pwd_sign){
            var params = {pay_pwd_sign:pwd_sign,card_id:repayment_card_id,id:<?= $order['id'] ?>};
            formPost('<?= Url::toRoute(['loan/pay-apply', 'id' => $repayment['order_id']]) ?>',params,'get');
        }
        $(function() {
            $(".close, .overlay").click(function(){
                $(".popup-select").hide();
                $(".content").addClass("popup-out");
                $(".content").removeClass("popup-in");
            });

            $(".bank").click(function(){
                $(".popup-select").hide();
                $(".p-bank").text($(this).html());
                $(".p-bank").removeClass("placeholder");
                $(".p-bank").addClass("normal");
            });
            //1.点击支付宝还款后
            <?php if (YII_ENV_PROD) {?>
                <?php if ($repayment['overdue_day'] > 10 || in_array(Yii::$app->user->identity->phone,[NOTICE_MOBILE])) {?>
                    var flag = false;
                <?php } else {?>
                    var flag = false;
                <?php } ?>
            <?php } else {?>
                var flag = true;
            <?php } ?>
            $("#alipay").on('click',function () {
                //支付宝埋点
                try {
                    MobclickAgent.onEventWithLabel("return_type", "支付宝还款");
                } catch(e) {
                    console.log(e);
                }
                if (flag) {
                    <?php if ($isPayed) {?>
                        showExDialog("<?=$alertMgs;?>","确定","hideLoading");
                        return false;
                    <?php } ?>
                    $('.alipay-pop').css('display','block');
                    //点击关闭弹框
                    $('.close-alipay').on('click',function () {
                        $('.alipay-pop').css('display','none')
                    })
                    //点击跳转去支付宝时
                    document.getElementById('toAlipay').onclick = function (e)
                    {
                        $('.loading').css('display','block');
                        $.ajax({
                            url : "<?php echo Url::toRoute('loan/ali-pay-apply')?>",
                            method : "post",
                            type : 'json',
                            data : $("#aliyPaymentForm").serialize(),//参数
                            success:function (res) {
                                $('.alipay-pop').css('display','none');
                                if (res.code == 1) {
                                    window.location.href="<?php echo Url::toRoute(['loan/alipay-result']) ?>?id="+res.id;
                                    $("head").append(res.response);
                                } else {
                                    showExDialog(res.msg,"确定","hideLoading");
                                }
                            },
                            error:function () {
                                showExDialog("网络异常，请稍后重试","确定","hideLoading");
                            }
                        })
                    }
                } else {
                    window.location.href="<?=Url::toRoute(['loan/loan-repayment-aliapy','id'=>$order['id']])?>";
                }
            })
        });

        // -------打点--------------
        $('.bankPoint').on("click",function () {
            try {
                MobclickAgent.onEventWithLabel("return_type", "银行卡还款");
            } catch(e) {
                console.log(e);
            }
        })

        //隐藏loading
        function hideLoading() {
            $(".loading").hide();
            $(".ex_dialog").hide();
            return nativeMethod.returnNativeMethod('{"type":"0","is_help":"1"}')
        }
    </script>
    <script>
        function ID(id) {
            return !id ? null : document.getElementById(id);
        }

        /**
         * 获取class
         * @param sName 必需。规定查找的类名
         */
         function getClass(sName){
            if(document.getElementsByClassName){
                return document.getElementsByClassName(sName);
            }else{
                var aTmp = document.getElementsByTagName('*'), aRes = [], arr = [], aTmpLen = aTmp.length;
                for(var i=0;i<aTmpLen;i++){
                    arr = aTmp[i].className.split(' ');
                    var arrLen = arr.length;
                    for (var j=0;j<arrLen;j++){
                        if(arr[j] == sName) aRes.push(aTmp[i]);
                    }
                }
                return aRes;
            }
        }

        /**
         * 通过类名移除元素
         * @params cName
        */
        function removeElementByClass(cName){
            if(window.jQuery){
                $('.'+cName).remove();
            }else{
                var class_arr = getClass(cName), len = class_arr.length;
                for (var i = len - 1; i >= 0; i--) { //
                    var obj = class_arr[i];
                    obj.parentNode.removeChild(obj);
                }
            }
        }

        function hideMask(){
            var obj = ID('mask');
            obj && obj.parentNode.removeChild(obj);
            document.body.style.overflow = 'auto';
        }
        /**
         * 关闭遮罩层
         * @params cName 默认 ex_dialog
        */
        function hideExDialog(cName){
            hideMask();
            cName = cName || 'ex_dialog';
            removeElementByClass(cName);
        }

        /**
         * 显示遮罩层
         * @params cName 默认 mask
         * @params bgColor 默认 #000
         * @params opacityVal 默认 45
        */
        function showMask(cName, bgColor, opacityVal){
            cName = cName || 'mask';
            bgColor = bgColor || '#000';
            opacityVal = opacityVal || 45;
            document.body.style.overflow = 'hidden';
            var layer = document.createElement('div');
            layer.className = cName;
            layer.id = 'mask';
            layer.style.cssText = "position:fixed; z-index:1; top:0; "+""+" width:100%; height:100%; background-color:"+bgColor+"; opacity:"+(opacityVal/100)+"; filter:alpha(opacity="+opacityVal+"); -moz-opacity:"+(opacityVal/100)+"; -khtml-opacity:"+(opacityVal/100)+";";
            document.body.appendChild(layer);
        }

        /**
         * 显示遮罩层
         * @params content
         * @params btn1
         * @params func1 默认 hideExDialog
         * @params btn2
         * @params func2 默认 hideExDialog
         * @params pTop 顶部距离 默认 15%
         * @params cName 默认 ex_dialog
         * @params params 额外参数（对象）
        */
        function showExDialog(content,btn1,func1,btn2,func2,pTop,cName,params){
            func1 = func1 || 'hideExDialog';
            func2 = func2 || 'hideExDialog';
            func1 = func1.indexOf('(') > 0 ? func1+';' : func1+'();';
            func2 = func2.indexOf('(') > 0 ? func2+';' : func2+'();';
            pTop = pTop || '15%';
            cName = cName || 'ex_dialog';
            hideExDialog(cName);
            showMask(cName);
            var bg_color = '#ffffff'; // 弹框默认背景颜色
            var txt_color = '#000000'; // 弹框默认字体颜色
            var btn_bg_color = '#1ec8e1'; // 按钮默认背景颜色
            var btn_txt_color = '#ffffff'; // 按钮默认字体颜色
            var btn_txt_size = 'inherit'; // 按钮默认字体大小
            if(params){
                if(params.bg_color != undefined){
                    bg_color = params.bg_color;
                }
                if(params.txt_color != undefined){
                    txt_color = params.txt_color;
                }
                if(params.btn_bg_color != undefined){
                    btn_bg_color = params.btn_bg_color;
                }
                if(params.btn_txt_color != undefined){
                    btn_txt_color = params.btn_txt_color;
                }
                if(params.btn_txt_size != undefined){
                    btn_txt_size = params.btn_txt_size;
                }
            }
            var dialogWraper = document.createElement('div');
            dialogWraper.className = cName;
            dialogWraper.id = 'dialog-wraper';
            dialogWraper.style.cssText = "width: 80%;/*max-width: 380px;*/margin: auto;";
            document.body.appendChild(dialogWraper);
            var str = '';
            str += '<div style="font-size:'+btn_txt_size+';position: fixed;z-index:2;top:'+pTop+';left:10%;width:80%;background-color:'+bg_color+';color:'+txt_color+';border-radius:5px;-moz-border-radius:5px 5px 5px 5px;-webkit-border-radius:5px;">';
            str += '<div class="_content" style="text-align:center;margin:12% auto;padding:0 10%;letter-spacing:1px;font-size:120%;">'+content+'</div>';
            if(arguments[1]) str += '<div class="_btn" style="display:inline-block;width: '+((arguments[1] && arguments[3]) ? '35%' : '80%')+';padding: 2.5% 0;margin-left: 10%;margin-bottom: 4%;background-color: '+btn_bg_color+';color: '+btn_txt_color+';text-align: center;border-radius: 4px;-moz-border-radius: 5px 5px 5px 5px;-webkit-border-radius: 4px;font-size:130%;" onclick="'+func1+'">'+btn1+'</div>';
            if(arguments[3]) str += '<div class="_btn" style="display:inline-block;width: 35%;padding: 3% 0;margin-left: 10%;margin-bottom: 4%;background-color: #ccc;color: #fff;text-align: center;border-radius: 4px;-moz-border-radius: 4px 4px 4px 4px;-webkit-border-radius: 4px;" onclick="'+func2+'">'+btn2+'</div>';
            str += '</div>';
            ID('dialog-wraper').innerHTML = str;
        }
    </script>
<?php } ?>

