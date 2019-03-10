<?php
use yii\helpers\Url;

use common\models\LoanPerson;
?>
<style type="text/css">
#zjmobliestart1{
    background: <?= $color;?>;
}
.phone-verify p.other label a {
    text-decoration: none;
    color: <?= $color;?>;
}
.phone-verify ul li a {
    display: inline-block;
    text-decoration: none;
    color: <?= $color;?>;
}
#check_id {
    background:url(<?= $this->absBaseUrl;?>/image/<?=$check_img;?>);
    background-size: 0.4rem 0.4rem;
}
</style>


<div data-url="<?= Url::to(['credit-info/post-service-code']); ?>" class="phone-verify" id="pwd">
    <ul>
        <li class="tel"><?= $phone; ?></li>
        <li class="query_pwd"><input class="verify" name="param" type="text" value="" placeholder="请输入手机服务密码" />
            <a href="<?= Url::to(['credit-web/forget-pwd']); ?>"  id="forget">忘记密码？</a>
        </li>
    </ul>
    <p style="display: none" class="err_msg error">输入不正确</p>
    <p class="tips">温馨提示： <br/>1. 请输入正确的运营商（移动、联通、电信）服务密码，如若忘记可通过拨打运营商服务电话或者登录网上营业厅找回密码； <br/>2.运营商认证需要2~3分钟，请耐心等待；</p>
    <a class="button" href="#"  id='zjmobliestart1' >确认</a>
    <p class="clearfix other">
        <input id="checkbox" name="" type="checkbox" value="" checked="true" />
        <label for="checkbox">我已阅读并同意<i id="check_id"></i><a href="<?= Url::to(['credit-web/operator']); ?>">《运营商授权协议》</a></label>
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
    <a class="button" href="#" style="background-color: <?=$color;?>" id='zjmobliestart2'>确认</a>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>

<div class="phone-verify" data-url="<?= Url::to(['credit-info/post-phone-query-pwd']); ?>" id="query" style="display: none">
    <ul>
        <li class="tel"><?= $phone; ?></li>
        <li class="query_pwd"><input name="param" class="verify" type="text" value="" placeholder="请输入查询密码" /></li>
    </ul>
    <p style="display: none" class="err_msg error">输入不正确</p>
    <a class="button" href="#" style="background-color: <?=$color;?>" id='zjmobliestart3'>确认</a>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>

<div class="phone-verify" data-url="false" id="finish" style="display: none">
    <div style="text-align:center;background-color:#fff;padding-top:1rem;padding-bottom:0.8rem;">

        <p><img src="<?=$this->staticUrl('credit/img/'.$img); ?>" width="28%"></p>
        <p style="font-size:0.52rem;color:<?= $color?>;margin-top:0.4rem;">认证成功</p>
        <p style="font-size:0.4rem;color:#666;">您的手机运营商已认证成功</p>
    </div>
    <?php if (!empty($url) && isset($url)): ?>
        <a class="button" href="<?= $url?>">返回</a>
        <div class="button"id="nextjmup" style="display: none;position:relative;background-color:<?=$color;?>;color:#fff;font-size:0.4266rem;height:1.2rem;line-height:1.2rem;width:93.3%;margin:0 auto;text-align:center;border-radius:0.24rem;margin-top:1.3333rem;">
            下一步
        </div>
    <?php else: ?>
        <a class="button" href="#" id ="back" style="background-color:#d9d9d9">已认证</a>
        <div class="button" id="nextjmup" style="display: none;position:relative;background-color:<?=$color;?>;color:#fff;font-size:0.4266rem;height:1.2rem;line-height:1.2rem;width:93.3%;margin:0 auto;text-align:center;border-radius:0.24rem;margin-top:1.3333rem;">
            下一步
        </div>
    <?php endif; ?>
    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>
<div class="popup-spin" id="loading" style="display: none">
    <?php if($appmarket == 'xybt_professional'):?>
        <div class="overlay" style="background-color:#f5f5f5;"></div>
        <div style="position:relative;text-align:center;background-color:#fff;padding-top:0.53rem;padding-bottom:0.48rem;">
            <div style="position:relative;height:2.6rem;">
                <span style="position:absolute;top:0.25rem;left:50%;margin-left:-1.085rem;font-size:0.48rem;color:#fff;background-color:<?php echo $color;?>;line-height:2.17rem;width:2.17rem;height:2.17rem;text-align:center;border-radius:50%;">认证中</span>
                <div id="preview" style="position:absolute;top:1.31rem;left:50%;"></div>
            </div>
            <p style="font-size:0.4rem;color:#666;margin-top:0.4rem;">运营商认证需要2-3分钟</p>
            <p style="font-size:0.4rem;color:#666;">您可以跳过等待去认证芝麻授信</p>
        </div>
        <a class="button" href="#" id="next" style="display: none;position:relative;background-color:<?=$color;?>;color:#fff;font-size:0.4266rem;height:1.2rem;line-height:1.2rem;width:93.3%;display:block;margin:0 auto;text-align:center;border-radius:0.24rem;margin-top:1.3333rem;">
            跳过等待，下一步
        </a>
    <?php else:?>
        <div class="overlay"></div>
        <div class="content">
            <p class="tips-msg">运营商认证需要2~3分钟…</p>
            <div class="spin" id="preview" style="top:59%;left:65%;"></div>
        </div>
    <?php endif;?>

    <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
</div>
<script>
    $(function () {

        setWebViewFlag();
        MobclickAgent.onEvent("tel","手机运营商页面事件"); //页面进入打点

        <?php if ($jump == true) : ?>
        res = nativeMethod.isFirstCertificationNext();
        if(res == 0){
            $("#nextjmup").hide();
            $("#back").show();
        }else{
            $("#nextjmup").show();
            $("#back").hide();
        }
        <?php endif; ?>

        try {
            setWebViewFlag();
        } catch(e) {
            console.log(e);
        }
        var checkbox = $('input[type="checkbox"]');
        var flag = checkbox.is(":checked");
        if (!flag) {
            $("#zjmobliestart1").css('background-color', '#bbb');
            $("#zjmobliestart1").attr("href", "javascript:void(0);");
        }
        checkbox.click(function (e) {
            flag = checkbox.is(":checked");
            console.log(flag)
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
        <?php if($appmarket == LoanPerson::APPMARKET_XJBT_PRO):?>
            new Spinner({color: '<?= $color;?>', lines: 40, width: 3 * dpr, radius: 44 * dpr, length: 0 * dpr}).spin(document.getElementById('preview'));
        <?php else:?>
            new Spinner({color: '#fff', width: 3 * dpr, radius: 11 * dpr, length: 8 * dpr}).spin(document.getElementById('preview'));
        <?php endif;?>

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
            // 上报手机运营商
            //nativeMethod.returnNativeMethod('{ "type": 20, "operationLogType": 5 }');

            var $p = $(this).parent();
            var url = $p.attr('data-url');
            var btnUrl = $(this).data('href');
            var _href = $(this).attr('href')
            if (url == 'false' && btnUrl === '#') {
                return false;
            }
            if(_href==='javascript:void(0);'){
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
            getType();
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
            getType();
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
                                //注册成功打点为 ‘1’
                                eventSubmitResult("1");
                                window.clearInterval(intval);
<?php if (in_array($source, [
            LoanPerson::PERSON_SOURCE_MOBILE_CREDIT
    ]) ) : ?>
                                changeStepPage(4, false);
                            <?php if ($jump != true) : ?>
                                setTimeout(function() {
                                    returnNative(0);
                                }, 10000); //60s
                            <?php endif; ?>
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
            getType();
            if (step == 1) {
                //页面开始时打点
                //MobclickAgent.onEvent('infor_tel')
                pwd_page.show();
            } else if (step == 2) {
                getType();
                auth_page.show();
            } else if (step == 3) {
                getType();
                query_page.show();
            } else if (step == 4) {
                finish.show();
                // 运营商认证成功打点
                MobclickAgent.onEvent('tel_sucess','手机运营商认证成功页面事件');
                getType();
            }
            if (popup == true) {
                $('.popup-spin').show();
            } else {
                $('.popup-spin').hide();
            }
        }
        function getType() {
            <?php if($jump == true){?>
            res = nativeMethod.isFirstCertificationNext();
            if(res == 0){
                $("#next").hide();
                $("#back").show();
            }else{
                $("#next").show();
                $("#back").hide();
            }
            <?php }?>
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

        // 认证中点击按钮跳转芝麻授信
        $("#loading .button").click(function(){
            nativeMethod.returnNativeMethod('{"type":"1008","verify_info":{"real_verify_status":"1"}}');
            nativeMethod.returnNativeMethod('{"type" : "4002"}');
            return false;
        });


        // 认证中点击按钮跳转芝麻授信
        $(document).on("click","#nextjmup",function () {
            nativeMethod.returnNativeMethod('{"type":"1008","verify_info":{"real_verify_status":"1"}}');
            nativeMethod.returnNativeMethod('{"type" : "4002"}');
            return false;
        })

        //友盟点击打点
        $('#zjmobliestart1').on("click",function () {
            try {
                MobclickAgent.onEventWithLabel("tel_sure", "手机运营商-确认"); 
            } catch (e) {
                console.log(e);
            }
        })
        $("#forget").on("click",function () {
            //忘记密码打点
            try {
                MobclickAgent.onEventWithLabel("tel_forget", "手机运营商-忘记密码");
            } catch (e) {
                console.log(e);
            }
        })
        //打点 确认返回结果
        function eventSubmitResult(resultValue) {
           try {
               var eventId = "tel_sure1"; //事件ID
               var eventData = {
                   '手机运营商-确认结果': resultValue
               }
               MobclickAgent.onEventWithParameters(eventId, eventData);
           } catch(e) {
               console.log(e);
           }
        }

    });


</script>
