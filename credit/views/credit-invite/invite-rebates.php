<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\LoanPersonInviteCash;
use common\models\LoanPersonInviteRebateDetail;
$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>

<div class="bonus">
    <div class="top" style=" overflow: hidden;">
        <?php if(!$cardInfo): ?>
            <div><span>若要领取奖金，请先绑定银行卡</span><a href="javascript:void();" id="btn_bind_list">去绑卡></a></div>
        <?php else:?>
            <p>
                <?php if(count($topAd)==5): ?>
                <?php foreach ($topAd as $ad): ?>
                <?=$ad['encode_name']; ?> 成功邀请<i><?=$ad['count']; ?></i>人 领取奖励<i><?=$ad['total_money']/100; ?></i>元；
                <?php endforeach;?>
                <?php else: ?>
                    *瑜 成功邀请<i>3</i>人 领取奖励<i>19</i>元；**峰 成功邀请<i>1</i>人 领取奖励<i>13</i>元；**荣 成功邀请<i>2</i>人 领取奖励<i>6</i>元；*博 成功邀请<i>1</i>人 领取奖励<i>3</i>元；
                <?php endif;?>

            </p>
        <?php endif;?>
    </div>
    <div class="tab"><a class="current" href="">当前奖金</a><a href="">提现历史</a></div>
    <div class="lists current-bonus">
        <div class="head">
            <p>当前可提现奖金</p>
            <h1><?php echo sprintf("%.2f", ($inviteInfo['total_money']-$inviteInfo['cashier'])/100); ?><span>元</span></h1>
            <p>历史获得奖金：<?php echo $inviteInfo['total_money']/100; ?>元</p>
            <a <?php if($inviteInfo['total_money']-$inviteInfo['cashier']<3000):?> class="disabled" <?php endif;?>  href="javascript:;" id="doWithdraw">立即提现</a>
        </div>
        <div class="list">
            <h1>好友来源</h1>
            <?php if($rebateDetail):?>
            <div>
                <ul>
                    <?php foreach ($rebateDetail as $k=>$detail):?>
                    <li <?php if($k>3):?>style="display: none;" <?php endif;?>><span><i><?=$detail['name'];?></i><br/><?=date('Y.m.d',$detail['record_time']);?></span><span>
                            <?php if($detail['type']==LoanPersonInviteRebateDetail::REBATE_TYPE_INVITE):?>
                                <b>成功申请</b>
                            <?php else: ?>
                                <em>成功借款</em>
                            <?php endif;?>
                            <br/><?=($detail['amount']/100)?></span><span><?=($detail['rebate']/100)?></span></li>
                    <?php endforeach;?>
                </ul>
                <?php if($k>3):?><a id="rebate-more" href="javascript:;">点击加载更多</a><?php endif;?>
            </div>
            <?php else:?>
             <div class="empty">
               <h3>暂无返利记录</h3>
             </div>
            <?php endif;?>
        </div>
        <div class="info">
            <h1>提现说明</h1>
            <ul>
                <li>1、每周可申请一次现金提现；</li>
                <li>2、累计满30元才可以提现，且只能全额提取；</li>
                <li>3、处于逾期状态中的用户需先还款才可以提现</li>
                <li>4、申请成功后，奖金将在3个工作日内发放到您绑定的银行卡上;</li>
                <li>5、如遇法定节假日，提现进度顺延；</li>
                <li>6、如有问题请拨打客服热线 <a href="javascript:callPhoneMehtod('400-681-2016')" >400-681-2016</a></li>
            </ul>
        </div>
    </div>

    <div class="lists history" style="display:none">
        <?php if(!$withdraw):?>
         <div class="empty">
           <h3>暂无记录哦~~</h3>
         </div>
        <?php else: ?>
        <div class="list-head">金额<span>提现日期</span></div>
        <ul>
            <?php foreach ($withdraw as $draw):?>
                <li><span>￥<?php echo sprintf("%.2f", $draw['amount']/100); ?></span><span>
                        <?php if($draw['status']==LoanPersonInviteCash::STATUS_SUBMIT):?>
                            <i>提现申请</i>
                        <?php elseif($draw['status']==LoanPersonInviteCash::STATUS_INVALID):?>
                            <i style="color: red">提现失败</i>
                        <?php else :?>
                            <i>提现完成</i>
                        <?php endif;?>
                        <br/><?=date('Y.m.d',$draw['cash_time']);?></span></li>
            <?php endforeach;?>
        </ul>
        <?php endif;?>
    </div>
</div>

<div id="apply-suc" class="popup popup-bonus-success" style="display:none">
    <div class="overlay"></div>
    <div class="dialog">
        <span class="close" onclick="function () {location.reload()}"></span>
        <div class="content">
            <p><span>恭喜哦~</span><br/>提现申请成功，奖金将在3个工作<br/>日内发放到您绑定的银行卡上</p>

                        <a href="javascript:location.reload();">确定</a>
        </div>
    </div>
</div>

<div id="no-card" class="popup popup-bonus-success popup-bonus-card" style="display:none">
    <div class="overlay"></div>
    <div class="dialog">
        <span class="close"></span>
        <div class="content">
            <p><span>抱歉哦~</span><br/>请绑定银行卡再提现</p>
            <a href="javascript:void();" id="btn_action_bind_url">去绑卡></a>
        </div>
    </div>
</div>
<div id="overdue" class="popup popup-bonus-success popup-bonus-card" style="display:none">
    <div class="overlay"></div>
    <div class="dialog">
        <span class="close"></span>
        <div class="content">
            <p><span>抱歉哦~</span><br/>您存在逾期未还的行为</p>
            <a href="javascript:void();" id="btn_action_list">去还款></a>
        </div>
    </div>
</div>
<div id="alert" class="popup" style="display:none; ">
    <div class="overlay"></div>
    <div class="error alert">
        <h2></h2>
        <p>
            <a href="javascript:$('#alert').hide();">确定</a>
        </p>
    </div>
</div>

<script>
    $(function(){
        $('div.popup span.close').click(function (e) {
            $('div.popup').hide();
        })

        $('div.tab a').click(function(e){
            $('div.tab a').removeClass('current');
            $('div.lists').hide();
            $(this).addClass('current');
            $('div.lists').eq($(this).index()).show();
            return false;
        });
        
        $("#btn_action_list").click(function(e){
            e.preventDefault();
            nativeMethod.returnNativeMethod('{"type":"8"}');
        })
        
        $("#btn_action_bind_url").click(function(e){
            e.preventDefault();
           bindUrl();
        })
        
        $("#btn_bind_list").click(function(e){
            e.preventDefault();
            bindUrl();
        })
        function bindUrl(){
            nativeMethod.returnNativeMethod('{"type":"3"}');
        }
        $("#doWithdraw").click(function () {
            if($(this). hasClass('disabled'))
            {
               return;
            }

            $(this).addClass('disabled');

            var amount = '<?php echo sprintf("%.2f", ($inviteInfo['total_money']-$inviteInfo['cashier'])/100); ?>';

            var that = this;
            $.ajax('<?php echo $baseUrl . '/credit-invite/invite-rebates-apply-cash'; ?>',{
                type : 'POST',
                dataType : 'json',
                data : "amount="+amount,
                success : function (data) {
                    if(data.code==1)
                    {
                        $("#apply-suc").show();

                    } else if(data.code==-1)
                    {
                        $("#alert h2").html(data.message);
                        $("#alert").show();
                    } else if(data.code==-2)
                    {
                        $("#no-card").show();
                    } else if(data.code==-3)
                    {
                        $("#overdue").show();
                    }

                    $(that).removeClass('disabled');
                },
                error : function (e) {
                    $("#alert h2").html("网络繁忙，请稍候尝试");
                    $("#alert").show();
                }
            })
        })

        $("#rebate-more").click(function (e) {
            $(this).parent().find("li").show();
            $(this).hide();
        })



            var $p = $('div.top p');
            var ww = $(window).width();
            var w = $p.width();
            var l = parseInt($p.css('left'),10);
            $p.css('left',l);
//            $p.width(w);
            setInterval(function(){
                l--;
                if(l < -w){
                    $p.css('left',ww);
                    l = ww;
                }
                $p.css('left',l);
            },10)
    })

    function callPhoneMehtod(phone){
        if (browser.versions.ios !== true) {
            window.nativeMethod.callPhoneMethod(phone);
        }else{
            window.location = "tel:" + phone;
        }
    }

    var browser = {
        versions: function () {
            var u = navigator.userAgent, app = navigator.appVersion;
            return {//移动终端浏览器版本信息
                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
            };
        }(),
        language: (navigator.browserLanguage || navigator.language).toLowerCase()
    }
</script>

