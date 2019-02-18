<!DOCTYPE html>
<html>
    <?php

    use yii\helpers\Url;
    use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
    ?>
    <head>
        <meta charset="UTF-8">
        <title>慢就赔活动</title>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/flexible.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/jquery.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/common.js?=2016120903"></script>
        <link href="<?= $this->staticUrl('css/act/slow.css?v=2016113001'); ?>" rel="stylesheet" />
    </head>
    <script>
          var _hmt = _hmt || [];
          (function() {
            var hm = document.createElement("script");
            hm.src = "https://hm.baidu.com/hm.js?9278c5e37a04e5c121ac2416ce719edf";
            var s = document.getElementsByTagName("script")[0]; 
            s.parentNode.insertBefore(hm, s);
          })();
      </script>
    <body>
    <body>
        <style>
            body {
                background-color: #454486;
            }
        </style>
        <div class="slow-body">
            <div class="header">
                <h1>飓风审核 拒绝等待</h1>
                <h3>活动时段: 12月5日-1月15日 (每日9：00-18：00)</h3>
            </div>
            <div class="t_news">
                    <ul class="news_li">
                        <li>用户<span>186****2021</span> 获得慢就赔红包<span>20.00元</span></li>
                        <li>用户<span>177****0809</span> 获得慢就赔红包<span>10.00元</span></li>
                        <li>用户<span>131****0469</span> 获得慢就赔红包<span>5.00元</span></li>
                        <li>用户<span>187****9885</span> 获得慢就赔红包<span>100.00元</span></li>
                        <li>用户<span>138****8232</span> 获得慢就赔红包<span>60.00元</span></li>
                        <li>用户<span>157****4793</span> 获得慢就赔红包<span>10.00元</span></li>
                        <li>用户<span>188****0171</span> 获得慢就赔红包<span>5.00元</span></li>
                        <li>用户<span>189****2642</span> 获得慢就赔红包<span>10.00元</span></li>
                        <li>用户<span>147****7931</span> 获得慢就赔红包<span>20.00元</span></li>
                        
                        <?php if ($data) : ?>
                            <?php foreach ($data as $v): ?>
                                <li>用户 <span><?= $v['phone'] ?></span> 获得慢就赔红包<span><?= sprintf("%.2f", $v['amount']) ?>元</span></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
            </div>
            <div class="slow">
                <p>活动时段内成功提交借款申请，且审核时间<span>大于2小时</span>，即可随机获得红包赔偿，最高可得<span>100元</span>！</p>
            </div>
            <div class="step">
                <span class="step1">提交申请</span>
                <span class="step2">风控审核</span>
                <span class="step3">审核通过</span>
            </div>
            <div class="time">
                <p class="time-icon">时间<span>>2小时</span></p>
                <p class="compensation">随机获得<span>红包</span>赔偿，最高<span>100元</span></p>
                <p>满足条件后7天内领取，过期作废</p>
                <a href="<?php echo Url::to(['credit-web/rebate-rule-slow']); ?>"><span>点此查看<b>详细规则</b></span></a>
            </div>
            <div class="refuse">
                <h4>审核不通过还能参加拒就赔活动哦！</h4>
                <a href="<?php echo $this->baseUrl; ?>/credit-web/event-details-page"><img src="../css/img/slow-icon-09.jpg" alt="" /></a>
            </div>
            <div class="footer">
                <p>本活动最终解释权归<?php echo APP_NAMES;?>所有<br>
                    <?php echo APP_NAMES;?>所有活动与苹果公司无关
                </p>
                <a href="" id="zjmobliestart" target="_blank">点此享受极速借贷</a>
            </div>
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
                    $ul.animate({
                        top: -h * i
                    })
                }, 3000);



                $("#zjmobliestart").click(function () {
                    var flag = "<?= $is_app ?>";
                    if (flag == 1) {
                        nativeMethod.returnNativeMethod('{"type":"4"}');
                    } else {
                        $("#zjmobliestart").attr("href", "https://api.kdqugou.com/download-app.html");
                        window.location.href = "https://api.kdqugou.com/download-app.html";
                    }
                })
            })
        </script>
    </body>

</html>
