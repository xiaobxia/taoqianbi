<?php

use yii\helpers\Url;
use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8"/>
        <title><?php echo isset($this->title) ? $this->title : '双12活动页'; ?></title>
        <script src="<?php echo $this->absBaseUrl; ?>/js/flexible-invite.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/js/jqueryrotate2.2.js"></script>
        <link href="<?php echo $this->absBaseUrl; ?>/css/act/double-twelve.css?=2016120701" rel="stylesheet" />
        
        <script>
          var _hmt = _hmt || [];
          (function() {
            var hm = document.createElement("script");
            hm.src = "https://hm.baidu.com/hm.js?3ac5a6a835b4ee96a11d699ee4f6b39a";
            var s = document.getElementsByTagName("script")[0]; 
            s.parentNode.insertBefore(hm, s);
          })();
      </script>
    </head>

    <body>
        <style>
            body {
                background: #ffc21c;
            }
        </style>
        <div class="double-twelve-body">
            <header>
                <h2>活动时间：2016年12月12日-12月14日</h2>
            </header>
            <div class="roll">
                <div class="t_news">
                    <ul class="news_li">
                        <li>恭喜131****7536获得7天免息奖</li>
                        <li>恭喜138****7186获得10元抵扣券</li>
                        <li>恭喜186****6526获得20元抵扣券</li>
                        <li>恭喜187****1436获得1元抵扣券</li>
                        <li>恭喜189****1936获得1元抵扣券</li>
                        <li>恭喜133****1236获得10元抵扣券</li>
                        <li>恭喜187****6589获得5元抵扣券</li>
                        <li>恭喜155****1336获得1元抵扣券</li>
                        <?php if ($message) : ?>
                            <?php foreach ($message as $v): ?>
                                <li><?= $v ?> </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="content">
                <div class="turntable">
                    <div class="rotate-bg">
                        <img id="rotate" src="<?php echo $this->absBaseUrl; ?>/css/img/twelve-icon-04.png" alt="" />
                    </div>
                    <div class="rotate-point" id="button_div"></div>
                </div>
            </div>
            <div class="rule">
                <h3>活动规则</h3>
                <p>1.活动有效期：2016年12月12日0:00-12月14日23:59</p>
                <p>2.活动期间申请成功借款≥1000元即可获得抽奖机会，每人仅限一次。所有奖励可至 “<span>我的</span>” -> “<span>我的优惠</span>” 中查看，有效期10天，不与其他抵扣券同享；</p>
                <p>3.本活动最终解释权归<?php echo APP_NAMES;?>平台所有，客服电话：4006812016。</p>
                <p><span><b>※</b>其中7天免息奖为一张还款抵扣券，该券根据个人借款金额、时间设定。</span></p>
                <p>例如：小明双12成功借款1000元7天，获得抽奖机会，抽到7天免息奖，那么他的还款抵扣券即为98元。</p>
            </div>
            <p class="description">本活动最终解释权归本公司所有，与苹果无关</p>
            <div class="popup" style="display: none;">
                <div class="overlay"></div>
                <div class="dialog">
                    <div class="close"><span></span></div>
                    <h2><span style="display: none;">您的抽奖机会已用完，请关注平台其他活动</span><span>恭喜您获得了<br><b>10元抵扣券</b>一张</span></h2>
                    <img src="<?php echo $this->absBaseUrl; ?>/css/img/twelve-icon-07.png" alt="" style="display: none;" />
                    <p><a href="javascript:void(0);" id="button_a">朕知道了</a></p>
                </div>
            </div>
            <script>
                $(function () {
                    var browser = {
                        versions: function () {
                            var u = navigator.userAgent, app = navigator.appVersion;
                            return {//移动终端浏览器版本信息
                                trident: u.indexOf('Trident') > -1, //IE内核
                                presto: u.indexOf('Presto') > -1, //opera内核
                                webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
                                gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
                                mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
                                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
                                iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器
                                iPad: u.indexOf('iPad') > -1, //是否iPad
                                webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
                                wx: u.indexOf(/MicroMessenger/) > -1 //是否wx应用程序
                            };
                        }(),
                        language: (navigator.browserLanguage || navigator.language).toLowerCase()
                    }
                    var $ul = $('.news_li');
                    var $li = $('.news_li li').eq(0);
                    var h = $li.height();
                    var l = $('li', $ul).length;
                    $ul.append($('li', $ul).eq(0).clone());
                    var i = 0;
                    var buttonFlag = 0;
                    setInterval(function () {
                        i++;
                        if (i > l) {
                            i = 1;
                            $ul.css('top', 0);
                        }
                        $ul.animate({
                            top: -h * i
                        })
                    }, 3000)

                    var rotateFunc = function (awards, angle, text) { //awards:奖项，angle:奖项对应的角度，text:提示文字
                        $("#rotate").stopRotate();
                        $("#rotate").rotate({
                            angle: 0,
                            duration: 8000,
                            animateTo: angle + 3600,
                            callback: function () {
                                $(".popup").show();
                                var $dialog = $(".dialog");
                                var $content = $("h2", $dialog);
                                var html = "<br>请至 <b>“我的” - “我的优惠”</b> 中查收";
                                $dialog.addClass("success");
                                $("img", $dialog).show();
                                $("p", $dialog).hide();
                                switch (awards) {
                                    case 1:
                                        $content.html("<span>恭喜您获得了<b>1元抵扣券</b>一张" + html + "</span>");
                                        break;
                                    case 2:
                                        $content.html("<span>恭喜您获得了<b>5元抵扣券</b>一张" + html + "</span>");
                                        break;
                                    case 3:
                                        $content.html("<span>恭喜您获得了<b>10元抵扣券</b>一张" + html + "</span>");
                                        break;
                                    case 4:
                                        $content.html("<span>恭喜您获得了<b>20元抵扣券</b>一张" + html + "</span>");
                                        break;
                                    case 5:
                                        $content.html("<span>恭喜您获得了<b>7天免息奖</b>" + html + "</span>");
                                        break;
                                }
                            }
                        })
                    }

                    $(".rotate-point").rotate({
                        bind: {
                            click: function () {
                                if (buttonFlag === 0) {
                                    buttonFlag = 1;
                                    var url = "<?php echo $this->absBaseUrl; ?>/credit-info/get-free-coupon";
                                    // ajax请求
                                    $.ajax({
                                        type: "post",
                                        url: url,
                                        dataType: "json",
                                        success: function (data) {



                                            console.log(data);




                                            if (data.code == 0) {
                                                var item = data.data.item;
                                                switch (item) {
                                                    case 1 :
                                                        rotateFunc(1, 288, '1元抵扣卷');
                                                        break;
                                                    case 2 :
                                                        rotateFunc(2, 216, '5元抵扣卷');
                                                        break;
                                                    case 3 :
                                                        rotateFunc(3, 144, '10元抵扣卷');
                                                        break;
                                                    case 4 :
                                                        rotateFunc(4, 72, '20元抵扣券');
                                                        break;
                                                    case 5 :
                                                        rotateFunc(5, 360, '7天免息奖');
                                                        break;
                                                    default:
                                                        break;
                                                }

                                            } else if (data.code === -2) {
                                                // 提示信息并且跳转
                                                buttonFlag = 0;
                                                if (data.data.is_app === 1) {
                                                    // 用户版本号为最新
                                                    $(".popup").show();
                                                    var $dialog = $(".dialog");
                                                    var $content = $("h2", $dialog);
                                                    $dialog.removeClass("success");
                                                    $("img", $dialog).hide();
                                                    $content.html("<span>快去登录，马上来抽奖</span>");
                                                    // 处理操作
                                                    $("#button_a").click(function (e) {
                                                        var flag_ver = data.data.is_ver;
                                                        if (flag_ver === 1) {
                                                            $("#button_a").click(function (e) {
                                                                e.preventDefault();
                                                                nativeMethod.returnNativeMethod('{"type":"4"}');
                                                            });
                                                        } else {
                                                            if (browser.versions.ios === true) {
                                                                $("#button_a").attr("href", "<?= $down_url ?>");
                                                                window.location.href = "<?= $down_url ?>";
                                                            } else if (browser.versions.android === true) {
                                                                $("#button_a").attr("href", "<?= $down_url ?>");
                                                            } else {
                                                                $("#button_a").attr("href", "<?= $down_url ?>");
                                                                window.location.href = "<?= $down_url ?>";
                                                            }
                                                        }
                                                    })
                                                } else {
                                                    // 其他登录
                                                    $(".popup").show();
                                                    var $dialog = $(".dialog");
                                                    var $content = $("h2", $dialog);
                                                    $dialog.removeClass("success");
                                                    $("img", $dialog).hide();
                                                    $content.html("<span>快去登录，马上来抽奖</span>");
                                                    // 处理操作
                                                    $("#button_a").click(function (e) {
                                                        if (browser.versions.ios === true) {
                                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                                            window.location.href = "<?= $down_url ?>";
                                                        } else if (browser.versions.android === true) {
                                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                                        } else {
                                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                                            window.location.href = "<?= $down_url ?>";
                                                        }
                                                    });
                                                }
                                            } else {
                                                buttonFlag = 0;
                                                // 提示信息
                                                $(".popup").show();
                                                var $dialog = $(".dialog");
                                                var $content = $("h2", $dialog);
                                                $dialog.removeClass("success");
                                                $("img", $dialog).hide();
                                                $content.html("<span>" + data.message + "</span>");
                                                $("p a", $dialog).text("点击申请");
                                                $("#button_a").click(function (e) {
                                                    e.preventDefault();
                                                    nativeMethod.returnNativeMethod('{"type":"4"}');
                                                });
                                            }

                                        }
                                    });

                                    //模拟数据
                                    //
                                    // var data = [1, 2, 3, 4, 5];
                                    // data = data[Math.floor(Math.random() * data.length)];

                                    // if (data == 1) {
                                    //   rotateFunc(1, 288, '1元抵扣卷')
                                    // }
                                    // if (data == 2) {
                                    //   rotateFunc(2, 216, '5元抵扣卷')
                                    // }
                                    // if (data == 3) {
                                    //   rotateFunc(3, 144, '10元抵扣卷')
                                    // }
                                    // if (data == 4) {
                                    //   rotateFunc(4, 72, '20元抵扣券')
                                    // }
                                    // if (data == 5) {
                                    //   rotateFunc(5, 360, '7天免息奖')
                                    // }
                                }
                            }
                        }
                    })

                    $(".close").click(function () {
                        $(".popup").hide();
                    });
                })
            </script>
        </div>
    </body>

</html>