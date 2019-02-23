<?php
use yii\helpers\Url;
use mobile\components\CheckPayPwd;
?>
<script type="text/javascript" src="<?=$this->absBaseUrl; ?>/js/callalipay.js"></script>
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
        border-color:#1ec8e1
        top:1.1rem;
    }
    .choose .head {
        height:0.453333rem;
        background-color: #f5f5f5;
    }
    .popup .dialog a {
        font-size: 0.3466666667rem;
        color: #1ec8e1
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
        border-color:#1ec8e1
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
        /*padding-top: 0.266667rem;*/
    }
    /*----------------------------*/
    /*.popup .dialog h1 .dikouquan {display:inline-block; width:1.84rem;height:0.4rem;font-size: 0.333333rem;position: relative;top: 0.173333rem;left: 0.0rem; background:url(../img/dikouquan.png) no-repeat center center;}*/
    .popup .dialog{
        border-radius: 0.186667rem;
        -webkit-border-radius: 0.186667rem;
    }
    .popup .dialog h1{
        font-size: 0.88rem;
        color: #1ec8e1
        line-height: 0.88rem;
        /*    padding-bottom: 0.72rem;*/
    }
    .popup .dialog h1 span{
        display: block;
        padding-bottom: 0.1rem;
    }
    .popup .dialog h1 .tips{
        display: block;
        font-size: 0.346667rem;
        color: #999;
        line-height: 0.346667rem;
        padding-bottom: 0.32rem;
    }
    .popup .dialog h1 .tips span{
        text-decoration: line-through;
    }
    .popup .dialog h2{
        font-size: 0.373333rem;
        color: #666;
        padding: 0.453333rem 0 0.266667rem;
        line-height: 0.37rem;
    }
    .popup .dialog{
        padding: 0;

    }
    .popup .dialog .title{
        text-align: center;
        font-size: 0.48rem;
        color: #333;
        padding: 0.2rem 0;
        border-bottom: 1px solid #d1d1d1;
    }
    .popup .dialog .title span.close{
        position: absolute;
        top: 0.3rem;
        left: 0.3rem;
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
        color: #1ec8e1
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
    .choose ul li:nth-child(5) {
        background: url('<?= $this->absBaseUrl;?>/image/apply_img/weixin.png') no-repeat 0.2666rem 0.533333rem/0.88rem 0.88rem;
    }
    .choose .other-loan{
        font-size: 0.4rem;
        color: #1ec8e1
        text-decoration: underline;
        text-align: center;
        display: block;
        margin-top: 90%;
    }
    #bankcard_num{
        position: relative;
        top:0.28rem;
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
        background-color: #1ec8e1
        font-size: 0.426667rem;
        color: #fff;
        text-decoration: none;
        border-radius: 0.133333rem;
    }
    /*支付宝维护弹窗样式*/
    .alipay-pop2{
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,.5);
        position: fixed;
        top:0;
        display: none;
    }
    .alipay-pop2 .content{
        text-align: center;
        width: 8.133333rem;
        background-color: #fff;
        border-radius: 0.133333rem;
        margin: 32% auto;
        padding-bottom: 0.01rem;
    }
    .alipay-pop2 .content ._top{
        position: relative;
        padding-top: 0.4rem;

    }
    .alipay-pop2 .content ._top .title{
        font-size: 0.48rem;
    }
    .alipay-pop2 .content ._top i{
        position: absolute;
        width: 0.346667rem;
        height: 0.346667rem;
        background:transparent url('<?= $this->absBaseUrl;?>/image/apply_img/close.png') no-repeat 0 0/0.346667rem 0.346667rem;
        top: 0.5rem;
        right: 0.4rem;
    }
    .alipay-pop2 .content .money {
        padding: 0.4rem 0 0.1rem;
    }
    .alipay-pop2 .content .money p{
        color: #999 !important;
        width: 100%;
        /*border:none;*/
        font-size: 0.4rem;
        text-align: center;
    }
    .alipay-pop2 .content .tips{
        color: red;
        visibility: hidden;
    }
    .alipay-pop2 .content #toAlipay{
        width: 90%;
        display: block;
        height: 1.2rem;
        line-height: 1.2rem;
        margin:0.266667rem auto;

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
        background-color: #1ec8e1
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
    /*银行卡输入密码后的等待15秒弹框*/
    .roll_pop{
        width: 100%;
        height: 100%;
        position: fixed;
        bottom:0;
        text-align: center;
        background-color: rgba(0,0,0,.5);
        display: none;
    }
    .roll_pop .box{
        width: 100%;
        height: 7.066667rem;
        position: absolute;
        bottom: 0;
        background-color: #fff;
    }
    .roll_pop .titile{
        padding: 0.453333rem 0;
        color:#666;
        background-color: #f2f2f2;
        font-size: 0.506667rem;
    }
    .roll_pop #font{
        width: 2.8rem;
        height: 2.8rem;
        line-height: 2.666667rem;
        background-color: transparent;
        font-size: 0.773333rem;
        color: #1ec8e1
        margin: -2.8rem auto;
    }
    .roll_pop #waiting{
        color: #666;
        font-size: 0.4rem;
        margin-top: 2.8rem;
    }
    .popup .overlay{
        background: rgba(0,0,0,.2);
    }
</style>
<head>
</head>
<div class="choose">
    <div class="head">
        <!--<h3><?=$this->title?></h3>-->
    </div>
    <ul>
        <!-- <li>
            <a href="javascript:void(0);" class="clearfix" id="alipay">
                   <div class="alipay-box clearfix">
                         <h2 class="alipay">支付宝还款<b>官方推荐</b></h2>
                          <span>查看</span>
                    <i></i>
                </div>
                <div><span class="desc">适用于支付宝还款方便的用户</span></div>
            </a>
        </li>
        <div class="space"></div> -->
        <?php if($useFy){ ?>
            <li>
                <?php if($selectBank){ ?>
                <a href="javascript:showBanks('<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>')">
                    <?php }else{ ?>
                    <a href="<?=Url::toRoute(['loan/confirm-code','id'=>$order['id']])?>">
                        <?php } ?>
                        <h2>银行卡还款</h2>
                        <span id="bankcard_num"><?=$order['bank_info']?></span>
                        <i></i>
                    </a>
            </li>
        <?php }else{ ?>
            <li>
                <!-- 银行卡打点 -->
                <a href="javascript:<?=($selectBank ? 'showBanks()':'showPayPwd()');?>" class="bankPoint">
                    <div class="clearfix _bankcard">
                        <h2>银行卡还款</h2>
                        <span id="bankcard_num"><?=$order['bank_info']?></span>
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
                    <span></span>
                    <i></i>
                </div>
                <div><span class="desc">适用于支付宝还款方便的用户</span></div>
            </a>
        </li>
        <div class="space"></div>
        <li>
            <a href="javascript:void(0);" class="weixin" id="weixin">
                <div class="clearfix weixin">
                    <h2 class="weixin">微信还款</h2>
                    <span></span>
                    <i></i>
                </div>
                <div><span class="desc">适用于微信还款方便的用户</span></div>
            </a>
        </li>
    </ul>
    <?php if ($repayment['overdue_day'] > 10 || in_array(Yii::$app->user->identity->phone,[NOTICE_MOBILE])) {?>
        <a href="<?=Url::toRoute(['loan/loan-repayment-aliapy','id'=>$order['id']])?>" class="other-loan">其他还款方式</a>
    <?php }?>
</div>
<!-- 支付宝弹窗 -->
<div class="alipay-pop">
    <?php $form = \yii\widgets\ActiveForm::begin(['id'=>'aliyPaymentForm']); ?>
    <div class="content">
        <div class="_top">
            <div class="title">还款金额</div>
            <i class="close-alipay"></i>
        </div>
        <input type="hidden" name = "id" value="<?=$id?>" />
        <input type="hidden" name="loanrepaymenttype" value="<?=$loan_repaymenttype?>" />
        <div class="money">
            <input type="number" name = "operateMoney" value="<?php if(!empty($part_money)) {
                echo sprintf("%0.2f", $part_money);
            } else {
                echo sprintf("%0.2f",$repayment['remain_money_amount'] / 100);
            } ?>" readonly="readonly"></div>
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
<!--支付宝系统维护-->
<div class="alipay-pop2">

    <div class="content">
        <div class="_top">
            <div class="title">提示</div>
            <i class="close-alipay"></i>
        </div>
        <input type="hidden" name = "id" value="<?=$id?>">
        <div class="money">
            <p>
                系统维护中:<br>从<?=date("Y-m-d H:i",$zhifubao['bentime'])?>开始<br>到<?=date("Y-m-d H:i",$zhifubao['endtime'])?>结束
            </p>
        </div>
        <div class="tips"></div>
        <a href="#" id="toAlipay"></a>
    </div>

    <div class="loading">
        <div class="spinner">
            <div class="dot1"></div>
            <div class="dot2"></div>
        </div>
    </div>
</div>
<!-- 等待15s环形进度条 -->
<div class="roll_pop">
    <div class="box">
        <p class="titile">请稍等</p>
        <canvas id="canvas"></canvas>
        <div id="font">15s</div>
        <p id="waiting">还款结果确认中....</p>
    </div>
</div>
<?php
if($selectBank){
    if($order['coupon_id'] == 0 && $repayment['coupon_id']){
        //无抵扣券
        $header = '<h2>还款金额（元）</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</h1>';
    }elseif($repayment['is_overdue']){
        //逾期
        if(!empty ($part_money) ) { //部分还款
            $header = '<h2>还款金额（元）</h2><h1><span>'.sprintf("%0.2f",$part_money).'</span>'.
                '<span class="tips">部分还款，实际应还'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</span></h1>';
        } else {
            $header = '<h2>还款金额（元）</h2><h1><span>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).
                '</span>'.'<span class="tips">已逾期，抵扣券不可用</span></h1>';
        }
        // $header .= '<span class="tips">已逾期，抵扣劵不可用</span></h1>';
    }elseif(($order['coupon_id'] || $repayment['coupon_id']) && $free_money > 0){
        //有抵扣券
        // $header = '<h2>还款金额（元）</h2><h1><span class="old-repayment">'.sprintf("%0.2f",$repayment['total_money'] / 100).'</span>'
        //           .'<span class="dikouquan"><span>'.sprintf("%0.2f",$free_money / 100).'元</span><span>抵扣券</span></span></h1><div class="cur-repayment">'.sprintf("%0.2f",($repayment['remain_money_amount']) / 100).'</div>';

        $header = '<h2>还款金额（元）</h2><h1><span>' .sprintf("%0.2f",$repayment['total_money'] / 100) .'</span>'.
            '<span class="tips"><span>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</span>&nbsp;抵扣券减免'.sprintf("%0.2f",$free_money / 100).'</span></h1>';
    }else{
        if(!empty($part_money)) {
            $header = '<h2>还款金额（元）</h2><h1>'.sprintf("%0.2f",$part_money).'</h1>';
        }else{
            $header = '<h2>还款金额（元）</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'</h1>';
        }
    }
    echo CheckPayPwd::widget([
        'js_callback' => 'success_callback',
        'header' => $header,
    ]);
}else{
    echo CheckPayPwd::widget([
        'success_url' => Url::toRoute(['loan/pay-apply','id'=>$order['id']]),
        'header' => '<h2>还款金额（元）</h2><h1>'.sprintf("%0.2f",$repayment['remain_money_amount'] / 100).'元</h1>',
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
            MobclickAgent.onEvent('returntype','选择还款方式页面事件')
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
                $('#defray i').eq(0).css("border","1px solid #1ec8e1");
            }
        }
        function success_callback(pwd_sign){
            //canvas进度条动画
            var canvas = document.getElementById('canvas'),
                ctx = canvas.getContext('2d');
            function toRadian(angle) {
                return angle*Math.PI/180
            }
            //获取终端尺寸
            var w = document.body.clientWidth || window.innerWidth,
                h = document.body.clientHeight || window.innerHeight;
            //响应式尺寸
            function fw(width){
                return width/750*w
            }
            var n = 270;
            var m=0;
            canvas.width =fw(210);
            canvas.height = fw(210);
            canvas.style.marginTop = fw(34)+'px';
            //画圈
            function circle(lw,color,rx,ry,r,ang1,ang2){
                with(ctx){
                    beginPath();
                    lineWidth=lw;
                    strokeStyle = color;
                    arc(rx,ry,r,ang1,ang2);
                    stroke()
                }
            }
            var apply_url = '<?= Url::toRoute(['loan/pay-apply-new', 'id' => $repayment['order_id']]) ?>';
            var status_url = '<?= Url::toRoute("loan/get-pay-status-new") ?>';
            var params = {pay_pwd_sign:pwd_sign,card_id:repayment_card_id,id:<?= $order['id'] ?>,'money':<?=$part_money?>,'loanrepaymenttype':<?=$loan_repaymenttype?>};
            var title = '';
            //扣款申请状态值
            $.ajax({type:"post",url:apply_url,data:params,success:function(data){
                    if(data && data.code==0){
                        $('.roll_pop').show();
                        //收起输入键盘
                        $('#defray input').blur();
                        $('#defray').hide();
                        //随百分比变动的环
                        var timer1 = setInterval(function(){
                            ctx.clearRect(0,0,fw(210),fw(210));
                            n = n-360/150;
                            n=n.toFixed(1);
                            circle(fw(6),'#ccc',fw(105),fw(105),fw(99),0,2*Math.PI);
                            circle(fw(8),'#1ec8e1',fw(105),fw(105),fw(98),toRadian(-90),toRadian(n));
                            if (n==-90) {
                                clearInterval(timer1);
                            }
                        },100);
                        var timer2 = setInterval(function(){
                            m++;
                            document.getElementById('font').innerText = (15-m)+'s';

                        }, 1000);
                        //查询申请还款、扣款申请状态值，每三秒请求一次。
                        var timer3 = setInterval(function(){
                            //查询还款状态信息
                            $.ajax({type:"post",url:status_url,data:[],success:function(data){
                                    title = data.msg;

                                    if(data){

                                        if(data.code==0){
                                            //如果代扣成功清除定时器
                                            clearInterval(timer1);
                                            clearInterval(timer2);
                                            clearInterval(timer3);
                                            window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code='+data.code+'&step=two&title='+title+'&order_id='+"<?=  $order['id']; ?>";
                                        }

                                        if((15-m)<3) {//15s时清除定时器，并跳转到还款失败页面
                                            clearInterval(timer1);
                                            clearInterval(timer2);
                                            clearInterval(timer3);
                                            window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code='+data.code+'&step=two&title='+title+'&order_id='+"<?=  $order['id']; ?>";
                                        }

                                    }else{
                                        window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code=-2&title=代扣进行中,请稍后查看详情&step=two'+'&order_id='+"<?=  $order['id']; ?>";
                                    }

                                },error:function(){
                                    window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code=-2&title=代扣进行中,请稍后查看详情&step=two'+'&order_id='+"<?=  $order['id']; ?>";
                                }});
                        },3000)

                    }else{
                        var error_msg = '';
                        if(data && data.code==-1){
                            error_msg = data.msg;
                        }else{
                            error_msg = '服务器连接超时，请重新申请还款';
                        }
                        window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code=-1&step=one&title='+error_msg+'&order_id='+"<?=  $order['id']; ?>";
                    }
                },error:function(){
                    window.location.href = "<?= Url::toRoute(['loan/repayment-success']);?>"+'?code=-1&step=one&title=操作失败，请稍后重试'+'&order_id='+"<?=  $order['id']; ?>";
                }});


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
            var flag = true;
            <?php } else {?>
            var flag = false;
            <?php } ?>
            <?php } else {?>
            var flag = true;
            <?php } ?>
            $("#alipay").on('click',function () {
                //支付宝埋点
                try {
                    MobclickAgent.onEventWithLabel("returntype_zhifubao", "选择还款方式-支付宝还款");
                } catch(e) {
                    console.log(e);
                }
                //汇潮支付宝还款下线
                var flag=false;
                if (flag) {
                    <?php if ($isPayed) {?>
                    showExDialog("<?=$alertMgs;?>","确定","hideLoading");
                    return false;
                    <?php } ?>
                    <?php if($zhifubao['status']==1){?>
                    //$('.alipay-pop2').css('display','block');
                    window.location.href="<?=Url::toRoute(['loan/loan-repayment-aliapy','id'=>$order['id']])?>";
                    <?php }else{?>
                    $('.alipay-pop').css('display','block');
                    <?php }?>
                    //点击关闭弹框
                    $('.close-alipay').on('click',function () {
                        $('.alipay-pop').css('display','none');
                        $('.alipay-pop2').css('display','none');
                    })
                    //点击跳转去支付宝时
                    document.getElementById('toAlipay').onclick = function (e)
                    {
                        $('.loading').css('display','block');
                        $.ajax({
                            url : "<?php echo Url::toRoute('loan/hc-ali-pay-apply')?>",
                            type : "post",
                            dataType : "json",
                            data : $("#aliyPaymentForm").serialize(),//参数
                            success:function (res) {
                                $('.alipay-pop').css('display','none');
                                if (res.code == 1) {
                                    var aliPayURL = res.hcAliyPayUrl;
                                    callappjs.callAlipay(aliPayURL);
//                                    window.location.href="<?php //echo Url::toRoute(['loan/alipay-result']) ?>//?id="+res.id;
//                                    $("head").append(res.response);
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
                MobclickAgent.onEventWithLabel("returntype_card", "选择还款方式-银行卡还款");
            } catch(e) {
                console.log(e);
            }
        })
        $('.weixin').on("click",function () {
            window.location.href="<?=Url::toRoute(['loan/loan-repayment-weixin','id'=>$order['id']])?>";
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

