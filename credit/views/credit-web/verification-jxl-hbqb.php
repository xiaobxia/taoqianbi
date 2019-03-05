<?php
use yii\helpers\Url;
?>
<style type="text/css">
/*新增 B*/
#zjmobliestart1{
    /*background: #1ec8e1*/
    background: url('<?= $this->absBaseUrl;?>/image/anniu@3x.png') no-repeat;
}
.phone-verify p span{ line-height: 0.72rem; }

.phone-verify p{ padding: 1.7333333333rem 0.4rem 0;}

.phone-verify a.button{margin:0.6666666667rem 0.4rem 0;}

.phone-verify p.other label a{text-decoration: none;color: #ff6462;}

.phone-verify p.other label i{width: 0.4rem; height: 0.4rem;border-radius:50%; position:absolute; left: 0; top: 50%; margin-top: -0.2rem; background:transparent url('<?= $this->absBaseUrl;?>/image/xuanze@2x.png') 0 0 no-repeat; background-size: 0.4rem 0.4rem; display: none; }

.phone-verify p.other { margin-left: 0.4rem;padding: 0.266666rem 0 0 0;}

.phone-verify p.other label:before {content: '';display: block; width: 0.4rem;height: 0.4rem; background: transparent;position: absolute;left: 0; top: 50%;margin-top: -0.2rem; border-radius: 0.0666666667rem;}

.phone-verify ul li input.verify {width: 6.9333333333rem;font-size: 0.4266666667rem;color: #212121;}
/*新增 E*/

/*.phone-verify p.other label a {
    text-decoration: none;
    color: #1ec8e1;
}*/
.phone-verify ul li a {
    display: inline-block;
    text-decoration: none;
    color: #ff6462;
}
</style>
<div data-url="<?= Url::to(['credit-info/post-service-code']); ?>" class="phone-verify" id="pwd">
    <ul>
        <li class="tel"><?= $phone; ?></li>
        <li class="query_pwd"><input class="verify" name="param" type="text" value="" placeholder="请输入手机服务密码" />
            <a href="<?= Url::to(['credit-web/forget-pwd']); ?>">忘记密码？</a>
        </li>
    </ul>
    <p style="display: none" class="err_msg error">输入不正确</p>
    <p class="tips"><span>温馨提示： </span><br/><span>1. 请输入正确的运营商（移动、联通、电信）服务密码，如若忘记可通过拨打运营商服务电话或者登录网上营业厅找回密码； </span><br/><span>2.运营商认证需要2~3分钟，请耐心等待；</span></p>
    <a class="button" href="#"  id='zjmobliestart1'>确认</a>
    <p class="clearfix other">
        <input id="checkbox" name="" type="checkbox" value="" checked="true" />
        <label for="checkbox">我已阅读并同意<i></i><a href="<?= Url::to(['credit-web/operator']); ?>">《运营商授权协议》</a></label>
    </p>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>

<div data-url="<?= Url::to(['credit-info/post-phone-captcha']); ?>" class="phone-verify" id="auth" style="display: none">
    <ul>
        <li class="tel"><?= $phone; ?></li>
        <li class="auth_code">
            <input class="verify" name="param" type="text" value="" placeholder="请输入短信校验码" />
            <a href="JaveScript:;" id="resend" class="send" style="background-color: gray">还需120秒</a>
        </li>
    </ul>
    <p style="display: none" class="err_msg error">输入不正确</p>
    <a class="button" href="#"  id='zjmobliestart2'>确认</a>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>

<div class="phone-verify" data-url="<?= Url::to(['credit-info/post-phone-query-pwd']); ?>" id="query" style="display: none">
    <ul>
        <li class="tel"><?= $phone; ?></li>
        <li class="query_pwd"><input name="param" class="verify" type="text" value="" placeholder="请输入查询密码" /></li>
    </ul>
    <p style="display: none" class="err_msg error">输入不正确</p>
    <a class="button" href="#" id='zjmobliestart3'>确认</a>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>

<div class="phone-verify" data-url="false" id="finish" style="display: none">
    <div style="text-align:center;background-color:#fff;padding-top:1rem;padding-bottom:0.8rem;">
        <p><img src="<?=$this->staticUrl('credit/img/'.$img); ?>" width="28%"></p>
        <p style="font-size:0.52rem;color:#ff6462;margin-top:0.4rem;">认证成功</p>
        <p style="font-size:0.4rem;color:#666;">您的手机运营商已认证成功</p>
    </div>
    <?php if (!empty($url) && isset($url)): ?>
        <a class="button" href="<?= $url?>">返回</a>
    <?php else: ?>
        <a class="button" href="#" style="background-color:#d9d9d9">已认证</a>
    <?php endif; ?>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>



<div class="popup-spin" id="loading" style="display: none;">
       <div class="overlay"></div>
       <div class="content">
            <p class="tips-msg">运营商认证需要2~3分钟…</p>
            <div class="spin" id="preview"></div>
       </div>
    </div>
<script>

    $(function () {
        var checkbox = $("input[type='checkbox']");
        checkbox.click(function (e) {
            var flag = checkbox.is(":checked");
            if (flag === true) {
                $('#zjmobliestart1').css('background-color','<?= $color;?>');
                $('#zjmobliestart1').attr("href", "#");
            } else {
                $("#zjmobliestart1").css('background-color', '#bbb');
                $("#zjmobliestart1").attr("href", "javascript:void(0);");
            }
        });


        var dpr = lib.flexible.dpr;

        var intval;
        var caption_count;
        var caption_intval;
        new Spinner({color: '#fff', width: 3 * dpr, radius: 11 * dpr, length: 8 * dpr}).spin(document.getElementById('preview'));
        //当前步骤
        var current_step = <?= $status; ?>;
        if ((current_step == -1) || (current_step == -2) || (current_step == 1)) {
            changeStepPage(1, false);
        } else if ((current_step == 2)) {
            changeStepPage(1, true);
            intval = window.setInterval(function () {
                getJxlStatus($('#pwd .err_msg'));
            }, 5000);

        } else if ((current_step == 3) || (current_step == 5) || (current_step == -4)) {
            changeStepPage(2, false);
        } else if (current_step == 4) {
            changeStepPage(2, true);
            intval = window.setInterval(function () {
                getJxlStatus($('#auth .err_msg'));
            }, 5000);
        } else if (current_step == 10) {
            changeStepPage(3, false);
        } else if (current_step == 11) {
            changeStepPage(3, true);
            intval = window.setInterval(function () {
                getJxlStatus($('#pwd .err_msg'));
            }, 5000);

        } else if (current_step == 6) {
            changeStepPage(4, false);
        }
        captionCountDown();

        $('.phone-verify .button').click(function () {
            var $p = $(this).parent();
            var url = $p.attr('data-url');
            var btnUrl = $(this).data('href');
            if (url == 'false' && btnUrl === '#') {
                return false;
            }
            var param = $('input[name=param]', $p).val();
            var $msg = $('.err_msg', $p);
            postQuery(url, param, $msg);
        });

        $('#resend').click(function (e) {
            if (caption_count > 1) {
                return false;
            }
            captionCountDown();
            var url = '<?= Url::to(['credit-info/resend-phone-captcha']); ?>';
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'json',
                success: function (data) {
                    if (data.code == 0) {
                        $('#auth .err_msg').html('发送成功').show();
                    } else {
                        $('#auth .err_msg').html(data.message).show();
                    }
                },
                fail: function () {

                }
            });
            return false;
        });
        //提交查询
        function postQuery(url, param, $message) {
            $message.hide();
            if (param.length <= 0 || param.length >= 20) {
                $message.html('不能为空').show();
                return false;
            }
            var params = {
                'p': param
            };
            $('.popup-spin').show();
            setTimeout(function() {
                returnNative(0);
            }, 10000); //60s
            $.ajax({
                url: url,
                type: 'post',
                data: params,
                dataType: 'json',
                success: function (data) {
                    if (data.code == 0) {
                        intval = window.setInterval(function () {
                            getJxlStatus($message)
                        }, 5000);
                    } else {
                        $('.popup-spin').hide();
                        $message.html(data.message).show();
                    }
                },
                fail: function () {

                }
            });
        }

        //获取聚信立状态
        function getJxlStatus($message) {
            var url = '<?= Url::to(['credit-info/get-jxl-status']); ?>';
            $message.hide();
            $.ajax({
                url: url,
                type: 'get',
                dataType: 'json',
                success: function (data) {
                    if (data.code == 0) {
                        switch (data.data) {
                            case -1:
                                window.clearInterval(intval);
                                changeStepPage(1, false);
                                $('#pwd .err_msg').html(data.message).show();
                                break;
                            case -2:
                                window.clearInterval(intval);
                                changeStepPage(1, false);
                                $('#pwd .err_msg').html(data.message).show();
                                break;
                            case 1:
                                window.clearInterval(intval);
                                changeStepPage(1, false);
                                break;
                            case 3:
                                window.clearInterval(intval);
                                changeStepPage(2, false);
                                break;
                            case -4:
                                window.clearInterval(intval);
                                changeStepPage(2, false);
                                $('#auth .err_msg').html(data.message).show();
                                break;
                            case 10:
                                window.clearInterval(intval);
                                changeStepPage(3, false);
                                break;
                            case 6:
                                window.clearInterval(intval);
<?php if (Yii::$app->controller->isFromXjk()): ?>
                                    changeStepPage(4, false);
<?php else: ?>
                                    returnNative(100);
<?php endif; ?>
                                break
                        }
                    } else {
                        $message.html(data.message).show();
                    }
                },
                fail: function () {

                }
            });
        }

        //切换页面
        function changeStepPage(step, popup) {
            var pwd_page = $('#pwd').hide();
            var auth_page = $('#auth').hide();
            var query_page = $('#query').hide();
            var finish = $('#finish').hide();
            if (step == 1) {
                pwd_page.show();
            } else if (step == 2) {
                auth_page.show();
            } else if (step == 3) {
                query_page.show();
            } else if (step == 4) {
                finish.show();
            }
            if (popup == true) {
                $('.popup-spin').show();
            } else {
                $('.popup-spin').hide();
            }
        }

        //验证码计时
        function captionCountDown() {
            caption_count = 120;
            caption_intval = window.setInterval(function () {
                if (caption_count > 1) {
                    caption_count -= 1;
                    $('#resend').html('还需' + caption_count + '秒');
                } else {
                    window.clearInterval(caption_intval);
                    $('#resend').html('重新发送').css('background-color', '<?= $color;?>');
                }
            }, 1000);
        }
    });


</script>
