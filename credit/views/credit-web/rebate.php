<!DOCTYPE html>
<html>
    <?php

    use yii\helpers\Url;
    use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
    ?>
    <head>
        <meta charset="UTF-8">
        <title>邀请好友活动</title>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/flexible.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/jquery.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/common.js?=2016120903"></script>
        <link href="<?= $this->staticUrl('css/act/style.css?v=2016112902'); ?>" rel="stylesheet" />
        <script>
            window.onload = function () {
                var url = document.location.href;
                var type = url.indexOf("type=");
                if (type != -1) {
                    var button = document.getElementById('button_a');
                    button.style.display = "none";
                }
            }
        </script>

        <script>
          var _hmt = _hmt || [];
          (function() {
            var hm = document.createElement("script");
            hm.src = "https://hm.baidu.com/hm.js?9278c5e37a04e5c121ac2416ce719edf";
            var s = document.getElementsByTagName("script")[0]; 
            s.parentNode.insertBefore(hm, s);
          })();
      </script>
    </head>

    <body>

        <style type="text/css">
            body {
                background-color: #fbca04;
            }
        </style>

        <div class="rebate-body">
            <div class="t_news">
                <?php if ($data) : ?>
                    <ul class="news_li">
                        <li>恭喜用户 <span>177****0809</span> 获得<span>现金返利 20.00元</span></li>
                        <li>恭喜用户 <span>131****0469</span> 获得<span>现金返利 10.00元</span></li>
                        <li>恭喜用户 <span>187****9885</span> 获得<span>现金返利 5.00元</span></li>
                        <li>恭喜用户 <span>138****8232</span> 获得<span>现金返利 100.00元</span></li>
                        <li>恭喜用户 <span>157****4793</span> 获得<span>现金返利 60.00元</span></li>
                        <li>恭喜用户 <span>139****3128</span> 获得<span>现金返利 30.00元</span></li>
                        <li>恭喜用户 <span>177****2809</span> 获得<span>现金返利 40.00元</span></li>
                        <li>恭喜用户 <span>135****0469</span> 获得<span>现金返利 30.00元</span></li>
                        <li>恭喜用户 <span>185****5787</span> 获得<span>现金返利 70.00元</span></li>
                        <li>恭喜用户 <span>139****4931</span> 获得<span>现金返利 90.00元</span></li>
                        <li>恭喜用户 <span>137****2681</span> 获得<span>现金返利 80.00元</span></li>
                        <li>恭喜用户 <span>188****0171</span> 获得<span>现金返利 10.00元</span></li>
                        <li>恭喜用户 <span>189****2642</span> 获得<span>现金返利 5.00元</span></li>
                        <li>恭喜用户 <span>147****7931</span> 获得<span>现金返利 20.00元</span></li>
                        <?php foreach ($data as $v): ?>
                            <li>恭喜用户 <span><?= $v['phone'] ?></span> 获得<span>现金返利 <?= sprintf("%.2f", $v['amount']) ?>元</span></li>
                        <?php endforeach; ?>

                    </ul>
                <?php endif; ?>
            </div>
            <div class="invitation">
                <h3></h3>
                <p class="borrow"><span>好友首次申请借款，您即可获得3元现金。 (多邀多得，上不封顶！)</span></p>
                <p class="loan"><span>好友首次成功放款，您可随机获得红包现金，最高100元</span></p>
                <a href="<?php echo Url::to(['credit-invite/invite-rebates-apply-cash']); ?>" id="btn_my_cash">我的奖金<span></span><span class="arrow-2"></span></a>
            </div>
            <div class="invitation-strategy">
                <h1>邀请攻略</h1>
                <div class="friend">
                    <h3><span>邀请好友</span></h3>
                    <p class="step1"><span>邀请人<b>发起邀请</b></span></p>
                    <p class="step2"><span>被邀请人<b>注册成功</b></span></p>
                    <p class="step3"><span>双方建立<b>邀请关系</b></span></p>
                </div>
                <div class="reward">
                    <h3><span>邀请奖励</span></h3>
                    <h4><span>>>>>>>>></span>邀请人奖励<span><<<<<<<<</span></h4>
                    <div class="apply">
                        <p><span>好友首次<b>申请借款</b></span></p>
                        <div class="right">
                            <div class="ticket">
                                <ul class="start"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                                ¥<b>3</b><span>元</span>
                                <ul class="end"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                            </div>
                            <div class="ticket_introduce"><span>邀请人</span>获得<b>3元现金</b></div>
                        </div>
                    </div>
                    <div class="success">
                        <p><span>好友首次<b>借款成功</b></span></p>
                        <div class="right">
                            <div class="ticket">
                                <ul class="start"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                                <span>最高</span>¥<b>100</b>
                                <ul class="end"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                            </div>
                            <div class="ticket_introduce"><span>邀请人</span><b>最高</b>获得<b class="red">100元现金</b>
                                <br>
                                <b>最少</b>获得好友借款金额的<b class="red">1%</b>
                            </div>
                        </div>
                    </div>
                    <h4 class="be-invited"><span>>>>>>>>></span>被邀请人奖励<span><<<<<<<<</span>
                    </h4>
                    <h5>被邀请人成功注册即可获得</h5>
                    <div class="repay-ticket">
                        <div class="ticket">
                            <ul class="start"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                            ¥<b>10</b><span>元</span><span class="repay">还款抵用券(有效期20天)</span>
                            <ul class="end"><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul>
                        </div>
                        <span class="repay-ticket-content">首单还款可以抵扣</span>
                    </div>
                    <div class="example">
                        <div>
                            <h4>*举个栗子：</h4>
                            <p>小明注册了<?php echo APP_NAMES;?>，活动期间邀请了10个小伙伴申请借款，其中A和B均成功借款1000元，其他8名小伙伴申请借款但被审核拒绝</p>
                            <p><span>小明最少可获得现金奖励 :<br>
                                    3*10+1000*1%*2= 50元；</span>
                                <span>小明最多可获得现金奖励:<br>
                                    3*10 + 100*2 = 230元</span></p>
                            <p>所有被邀请小伙伴注册成功可获得10元还款抵扣券（有效期20天）</p>
                        </div>
                    </div>
                </div>
                <a href="<?php echo Url::to(['credit-web/rebate-rule']); ?>">活动规则>></a>
            </div>
            <div class="activity-re">
                <h3>活动推荐</h3>
                <a href="<?php echo $this->baseUrl; ?>/credit-web/event-details-page"><img src="../css/img/rebate-icon-07.jpg" alt="" /></a>
                <h4>本活动最终解释权归<?php echo APP_NAMES;?>所有<br><?php echo APP_NAMES;?>所有活动与苹果公司无关</h4>
            </div>
            <a class="button" id="button_a" style="" href=""></a>
        </div>
        <script>
            $(function () {
                var $ul = $('.news_li');
                var $li = $('.news_li li').eq(0);
                var h = $li.height();
                var l = $('li', $ul).length;
                $ul.append($('li', $ul).eq(0).clone());
                var i = 0;
                setInterval(function () {
                    i++;
                    if (i > l) {
                        i = 1;
                        $ul.css('top', 0);
                    }
                    $ul.animate({top: -h * i})
                }, 3000);

                // 处理操作
                $("#button_a").click(function (e) {
                    var flag = "<?= $is_app ?>";
                    if (flag == 1) {
                        $("#button_a").attr("href", "<?php echo Url::to(['credit-invite/invite-rebate-start']); ?>");
                    } else {
                        $("#button_a").attr("href", "https://api.kdqugou.com/download-app.html");
                        window.location.href = "https://api.kdqugou.com/download-app.html";
                    }
                });
                // 我的奖金
                $("#btn_my_cash").click(function (e) {
                    var flag = "<?= $is_app ?>";
                    if (flag == 1) {
                        $("#btn_my_cash").attr("href", "<?php echo Url::to(['credit-invite/invite-rebates-apply-cash']); ?>");
                    } else {
                        $("#btn_my_cash").attr("href", "https://api.kdqugou.com/download-app.html");
                        window.location.href = "https://api.kdqugou.com/download-app.html";
                    }
                })


            })
            function getPar(par) {
                //获取当前URL
                var local_url = document.location.href;
                alert(local_url);
                //获取要取得的get参数位置
                var get = local_url.indexOf(par + "=");
                if (get == 1) {
                    return false;
                }
            }


        </script>
    </body>

</html>
