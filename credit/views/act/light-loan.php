<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title><?php echo $this->title ? $this->title : APP_NAMES; ?></title>
    <meta name="format-detection" content="telephone=no" />
    <script src="<?php echo $this->absBaseUrl; ?>/credit/js/flexible.js"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js?v=2016012601"></script>
  </head>
  <body>
    <style type="text/css">
         [data-dpr="2"] .input-form input{font-size:24px}
         [data-dpr="3"] .input-form input{font-size:36px}                                             
        .light-wrapper{position:relative;overflow:hidden}
        .light-wrapper img{vertical-align:top}
        .light-wrapper .input-form{background:#F25451}
        .light-wrapper .input-form .inp span{display:inline-block;height:1rem;line-height:1rem;background:#fff;vertical-align:middle}
        .light-wrapper .input-form .inp span.left{width:14%;border-radius:0.13333333333333333rem 0 0 0.13333333333333333rem;text-align:center}
        .light-wrapper .input-form .inp span.left img{vertical-align:middle}
        .light-wrapper .input-form .inp span.right{width:86%;border-radius:0 0.13333333333333333rem 0.13333333333333333rem 0;margin-left:-1px;}
        .light-wrapper .input-form .inp.code-inp span.right{width:52%}
        .light-wrapper .input-form input{border:none;border-radius:0;width:90%;border-left:1px solid #999;padding-left:6%}
        .light-wrapper .input-form .code-inp input{width:85%;padding-left:9%}
        .light-wrapper .input-form button{display:inline-block;width:100%;padding:0.2rem 0;background:#FFD821;border:none;border-radius:10px}
        .light-wrapper .input-form button.btn-sub{width:32%;height:1rem;margin-left:2%;background:#E6E6E6;font-size:1em;vertical-align:middle}
        .light-wrapper .intru{background:#FCF8EF;padding-bottom:0.8rem}
        .light-wrapper .page2{padding:0.58rem 0.42rem;padding-top:0.2rem;position:absolute;top:30rem;left:0;background:#fff;color:#666}
        .light-wrapper .mask{background:rgba(0,0,0,0.6);width:100%;height:100%;position:absolute;top:0;left:0}
        .light-wrapper .mask .pop-box{margin:0 auto;margin-top:60%;background:#fff;width:50%;padding:10%;text-align:center}
        .light-wrapper .mask button{display:inline-block;width:100%;padding:0.2rem 0;background:#F25451;border:none;border-radius:10px}
        .div-pad{padding:0.58rem 0.42rem}
        .color1{color:#fff}
        .color2{color:#ffde01}
        .color3{color:#999999}
        .color4{color:#f25451}
        .mt1{margin-top:0.2rem}
        .mt2{margin-top:0.4rem}
        .mt3{margin-top:0.6rem}
        .size0{font-size:1em}
        .size1{font-size:1.4em}
        .size2{font-size:1.2em}
        .size3{font-size:2em}
        .align-r{text-align:right}
        .align-c{text-align:center}
        .weiruan{font-family:微软雅黑}
        .under-l{text-decoration:underline}
        .bold{font-weight:bold}
        .hide{display:none}
        .bounce{-webkit-animation:bounce 1.2s .2s ease both infinite;-moz-animation:bounce 1.2s .2s ease both infinite}
        @-webkit-keyframes bounce{0%,20%,50%,80%,100%{-webkit-transform:translateY(0)}
        40%{-webkit-transform:translateY(-20px)}
        60%{-webkit-transform:translateY(-10px)}
        }@-moz-keyframes bounce{0%,20%,50%,80%,100%{-moz-transform:translateY(0)}
        40%{-moz-transform:translateY(-20px)}
        60%{-moz-transform:translateY(-10px)}
        }
    </style>
    <div class="light-wrapper">
        <div class="page1">
            <div class="lib1">
                <div><img src="<?php echo $baseUrl;?>/image/act/light-loan/top.jpg" width="100%"></div>
                <div class="input-form div-pad">
                    <p class="inp"><span class="left"><img src="<?php echo $baseUrl;?>/image/act/light-loan/phone.png" width="32%"></span><span class="right"><input type="text" name="phone" class="phone" maxlength="11" placeholder="请输入您的手机号码"></span></p>
                    <p class="inp mt1"><span class="left"><img src="<?php echo $baseUrl;?>/image/act/light-loan/password.png" width="32%"></span><span class="right"><input type="password" name="password" class="password" placeholder="请设置登录密码"></span></p>
                    <p class="inp code-inp mt1"><span class="left"><img src="<?php echo $baseUrl;?>/image/act/light-loan/code.png" width="36%"></span><span class="right"><input type="text" name="code" class="code" placeholder="请输入验证码"></span><button class="weiruan btn-sub sendCode color3">获取验证码</button></p>
                    <p class="mt2"><button class="size1 weiruan register">立即注册拿钱</button></p>
                    <p class="mt2 color1 size2">已注册，直接 <span class="color2 under-l" onClick="downLoad()">下载</span> 登录。</p>
                </div>
                <div class="div-pad"><img src="<?php echo $baseUrl;?>/image/act/light-loan/progress.png" width="100%"></div>
            </div>
            <div class="intru div-pad lib2">
                <p class="size1 weiruan"><?php echo APP_NAMES;?>优势</p>
                <p class="mt2 color3">1. 纯信用借款，无抵押，无担保；</p>
                <p class="mt1 color3">2. <?php echo APP_NAMES;?>有工作既能借款，最快20分钟到账；</p>
                <p class="mt1 color3">&nbsp;&nbsp;小提示：首次申请时，绑定更多真实信息有助于成功借款。</p>
                <p class="mt1 color3">3. 安全保障：银行级信息管控，为您保驾护航。</p>
                <p class="mt2 align-c weiruan under-l size2"></p>
                <p class="mt1 align-c color3"><?php echo APP_NAMES;?>——科技让金融更简单</p>
            </div>
        </div>
        <div class="mask hide">
            <div class="pop-box">
                <div class="pop-con">
                    <p class="color3 size1"></p>
                    <button class="mt2 color1 size1" onclick="hideBox()">朕知道了</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.onload = function(){
            var u = navigator.userAgent;
            window.browser = {};
            window.browser.iPhone = u.indexOf('iPhone') > -1;
            window.browser.android = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1;//android or uc
            window.browser.ipad = u.indexOf('iPad') > -1;
            window.browser.isclient = u.indexOf('lyWb') > -1;
            window.browser.ios = u.match(/Mac OS/); //ios
            window.browser.width = window.innerWidth;
            window.browser.height = window.innerHeight;
            window.browser.wx = u.match(/MicroMessenger/);
    
            if (window.afterOnload) {
                window.afterOnload();
            }
        }
        var reg=/^1[3|4|5|7|8]\d{9}$/;
        $(function(){
            $('.sendCode').click(function() {
                var phone = $('.phone').val();
                var pwd = $('.password').val();
                var code = $('.code').val();
                if (!phone) {
                    showBox('请输入手机号码');
                    return false;
                }
                if (!reg.test(phone)) {
                    showBox('请输入正确的手机号码');
                    return false;
                }
                if (!pwd) {
                    showBox('请设置登录密码');
                    return false;
                }
                var params = 'phone='+phone;
                $.post("<?php echo Url::to(['credit-user/reg-get-code'], true); ?>", params, function(data){
                    if (data.code == 0) {
                        $('.sendCode').html('<i class="size1" id="second">60s</i>').attr('disabled', true);
                        countdown();
                    }else {
                        if (data.code == 1000) {
                            showBox('您已注册，请直接下载登录','立即下载','downLoad()');
                        } else {
                            showBox(data.message);
                        }
                    }
                });
            });
    
            $('.register').click(function() {
                var check = $('.register').attr("tip");
                if(check == 'timing'){
                    return false;
                }
                $('.register').attr("tip","timing");
                var phone = $('.phone').val();
                var pwd = $('.password').val();
                var code = $('.code').val();
                if (!phone) {
                    showBox('请输入手机号码');
                    return false;
                }
                if (!reg.test(phone)) {
                    showBox('请输入正确的手机号码');
                    return false;
                }
                if (!pwd) {
                    showBox('请设置登录密码');
                    return false;
                }
                var params = 'phone='+phone+'&code='+code+'&password='+pwd+'&invite_code=<?php echo $user_id; ?>';
                $.post("<?php echo Url::toRoute(['credit-user/register','clientType'=>'h5','appMarket'=>$tag], true); ?>", params, function (data) {
                    if (data.code == 0) {
                        showBox('<span class="bold color4">恭喜您注册成功</span><br><span class="mt1 size0">快来极速借款吧</span>','下载<?php echo APP_NAMES;?>','downLoad()');
                    }else {
                        showBox(data.message);
                        $('.register').removeAttr("tip");
                    }
                });
            });
        });
    
        function showBox(tips,btn,click){
            $('.pop-con p').html(tips);
            if(btn != ''){
                $('.pop-con button').html(btn);
            }
            if(click != ''){
                $('.pop-con button').attr('onClick',click);
            }
            $('.mask').show();
        }
        function hideBox(obj,func){
            $('.pop-con button').attr('onClick','hideBox()').html('朕知道了');
            $('.mask').hide();
        }
    
        function downLoad() {
            hideBox();
            window.location.href = "https://qbcredit.wzdai.com/download-app.html?source_tag=<?php echo $tag ?>";
        }
    
        function changePage(){
            $('.light-wrapper').css('overflow','auto');
            $('.page2').animate({top:$('.lib1').height()+$('.lib2').height()},500);
        }
        function countdown(){
            var time = 60;
            timing = setInterval(function() {
                time--;
                $('#second').html(time+'s');
                if (time < 0) {
                    clearInterval(timing);
                    time = 60;
                    $('.sendCode').removeAttr('disabled').html('获取验证码');
                }
            }, 1000);
        }
    </script>
  </body>
</html>
