<?php
use yii\helpers\Url;
?>
<link href="<?=$this->staticUrl('css/style-hbqb.css?v=2016122202',2); ?>" rel="stylesheet"/>
<style>
    /*新增B*/
    .help #online h2 {background: transparent url(<?= $this->absBaseUrl;?>/image/QQkefu.png) 40% 15% no-repeat;background-size:50%;padding-top:1.8rem;border-right:none;}

    .help .auth h3 {background: url(<?= $this->absBaseUrl;?>/image/renzheng.png) 40% 35% no-repeat;background-size:50%;}

    .help .loan h3 { background:url(<?= $this->absBaseUrl;?>/image/jiekuan.png) 40% 35% no-repeat; background-size:50%; }

    .help .repayment h3 { background:url(<?= $this->absBaseUrl;?>/image/huankuan.png) 40% 35% no-repeat; background-size:50%; }

    .help .cost h3 { background:url(<?= $this->absBaseUrl;?>/image/feiyong.png) 40% 35% no-repeat; background-size:50%; }

    .help .other h3 { background:  url(<?= $this->absBaseUrl;?>/image/xiangguan.png) 40% 35% no-repeat; background-size:50%; }

     h3 {color: #ff6462;}

     /*> div { background: #fff;margin: 0.3333333333rem auto;font-size: 0rem;width:9.2rem;}*/
     .auth,.loan,.cost,.repayment,.other{background: #fff;margin: 0.3333333333rem auto;font-size: 0rem;width:9.2rem;}

     ul {display: inline-block;}

     ul li {font-size: 0.4rem; color: #666;line-height: 1.4rem;padding-left: 0.2rem;border-bottom: 1px solid #e6e6e6;box-sizing: border-box;}
    /*新增E*/

    body {
        /*background-color: #f5f5f7;*/
        background:#f5f5f5;
    }
     h3::after{
        border-top: 0px solid #ffffff;
        border-right: 0px solid #ffffff;
    }
     h3{
        color: #ff6462;
    },
     .consultation .online-consultation h4,
     .consultation .phone-consultation h4{
        color: #ff6462;
    }
     .consultation{
        background: #fff;
        text-align: center;
    }
     ul li{
        color:#666666;
    }
     {
        background:#f5f5f5
    }
     .consultation .online-consultation h4,  .consultation .phone-consultation h4 {
        font-size: 0.4rem;
        color: #ff6462;
        text-align: center;
        position: relative;
        margin-top: 1.6rem;
    }

/*-----------------------------------------*/

.help h3 {
    display: inline-block;
    width: 2.4666666667rem;
    height: 4.2133333333rem;
    padding-top: 2.3333333333rem;
    font-size: 0.4rem;
    color: #ff6462;
    border-right: 1px solid #e6e6e6;
    vertical-align: top;
    text-align: center;
    box-sizing: border-box;
}
    .help h2 {
        display: inline-block;
        width: 2.4666666667rem;
        height: 4.2133333333rem;
        padding-top: 2.3333333333rem;
        font-size: 0.4rem;
        color: #ff6462;
        border-right: 1px solid #e6e6e6;
        vertical-align: top;
        text-align: center;
        box-sizing: border-box;
    }
.help ul {
    display: inline-block;
    width: 6.7rem;
    padding:0 0.15rem;
}

.help ul li {
    font-size: 0.4rem;
    color: #333;
    line-height: 1.4rem;
    padding-left: 0rem;
    border-bottom: 1px solid #e6e6e6;
    box-sizing: border-box;
}
.help .consultation .online-consultation {
    display: inline-block;
    width: 9.2rem;
    height: 2.5333333333rem;
}
.help h3::after {
    border-top: 0px solid #ffffff;
    border-right: 0px solid #ffffff;
}
/*------------------------*/
.help-center >div{
    background:#f5f5f7;
}
</style>
<body>
<div id="dialog-wraper"></div>
    <div class="help-center kdlc_mobile_wraper">
       <div class="help">


            <!--<div class="consultation">
                <a class="online-consultation" id="online">
                    <h2>在线咨询</h2>
                </a>-->
                 <!--<a class="phone-consultation" id="callphone"></a>-->
                 <!--<a class="phone-consultation" id="callphone" href="javascript:callPhoneMehtod('')">
                     <div></div>
                    <h4 id="msg">电话咨询</h4>
                </a>
            </div>-->



        <div id="1" class="auth">
            <h3>认证相关</h3>
            <ul>
                <li id="4">为什么会读取联系人失败？</li>
                <li id="7">如何更换收款银行卡？</li>
                <li id="12">手机运营商认证失败的原因有哪些？</li>
            </ul>
        </div>
        <div id="2" class="loan">
            <h3>借款相关</h3>
            <ul>
                <li id="18">审核通过后多久打款？</li>
                <li id="19">审核被拒绝的原因一般有哪些？</li>
                <li id="24">如何提升信用额度？</li>
            </ul>
        </div>
        <div id="3" class="repayment">
            <h3>还款相关</h3>
            <ul>
                <li id="26">每种方式需要多久更新还款状态？</li>
                <li id="31">如何进行支付宝还款？</li>
                <li id="36">逾期还能申请续期服务吗？</li>
            </ul>
        </div>
        <div id="4" class="cost">
            <h3>费用相关</h3>
            <ul>
                <li id="39">借款费用如何收取？</li>
                <li id="40">逾期费用如何收取？</li>
            </ul>
        </div>
        <div id="5" class="other">
            <h3>其他问题</h3>
            <ul>
                <li id="42">收不到验证码怎么办？</li>
                <li id="43">如何更改手机号？</li>
                <li id="44">是否可以注销账户？</li>
            </ul>
        </div>

        </div>
    </div>
    <script>

        //获取版本号
        $("#4.cost h3").css("background-size",'1.3333333333rem 1.2rem');
        $("#5.other h3").css("background-size",'1.3333333333rem 1.2rem');
        $(function () {

            $("li").click(function () {
                var faTag = $(this).parent().parent().attr("id");
                var tag = $(this).attr("id");
                window.location.href = "<?php echo Url::to(["/credit-web/help-description"]) ?>" + "?fatag=" + faTag + "&tag=" + tag;
            });
            $("h3").click(function () {
                var faTag = $(this).parent().attr("id");
                window.location.href = "<?php echo $this->baseUrl; ?>/credit-web/help-description?fatag=" + faTag;
            });
            $("#online").click(function () {
                if(type == 1){//IOS
                    window.location.href="http://wpa.b.qq.com/cgi/wpa.php?ln=2&uin=";
                }else{
                    return nativeMethod.copyTextMethod('{"text":"'+10001+'","tip":"复制客服QQ成功!"}');
                }
                /*try {
                 nativeMethod.returnNativeMethod('{"type":"12","is_help":"1"}');
                 } catch (e) {
                 console.log(e);
                 }*/
            });

        <?php if ($title == false): ?>

                Initialization();

        <?php endif; ?>


            function Initialization() {
                try {
                    nativeMethod.returnNativeMethod('{"type":"5"}');
                } catch (e) {
                }
            }
        });
        $("#msg").click(function () {
            $("#msg").css("color","gray");
           /* try {
                return showExDialog("不能保存空信息",'确定');
            }catch (e){
                console.log(e);
            }*/
        })
        <?php
            if($type == 'ios'){
                 echo 'var type = 1;';
            }else{
                 echo 'var type = 2;';
            }
            ?>


        function callPhoneMehtod(phone) {
            if (browser.versions.ios !== true) {
                window.nativeMethod.callPhoneMethod(phone);
            } else {
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
</body>
