<?php
 use yii\helpers\Url;
 use common\models\LoanPerson;
?>
<style>
    body {
        background-color: #f5f5f7;
    }
    .help-center h3::after{
        border-top: 1px solid #ffffff;
        border-right: 1px solid #ffffff;
    }
    .help-center h3{
        color: #1ec8e1;
    },
    .help-center .consultation .online-consultation h4,
    .help-center .consultation .phone-consultation h4{
        color: #1ec8e1;
    }
    .help-center .consultation{
        background: #fff;
        text-align: center;
    }
    .help-center . ul li{
        color:#666666;
    }
    .help-center {
        background:#f5f5f5
    }
    .help-center .consultation .online-consultation h4, .help-center .consultation .phone-consultation h4 {
        font-size: 0.4rem;
        color: #1ec8e1;
        text-align: center;
        position: relative;
        margin-top: 1.6rem;
    }
    /*新增 更换图标-------------------*/
        .help-center .consultation .phone-consultation {
            display: none;
            width: 4.9866666667rem;
            height: 2.5333333333rem;
            margin-left: 0.0266666667rem;
        }
        .help-center .consultation .online-consultation h4:before{
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn_qq.png") 0 0 no-repeat;
            background-size: 1.1866666667rem 1.1866666667rem;
        }
        .help-center .consultation .phone-consultation h4:before{
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn_phone.png") 0 0 no-repeat;
            background-size: 1.1866666667rem 1.1866666667rem;
        }

        .help-center .auth h3 {
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn01.png") 40% 35% no-repeat;
            background-size: 50%;
        }
        .help-center .loan h3 {
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn02.png") 40% 35% no-repeat;
            background-size: 50%;
        }
        .help-center .repayment h3 {
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn03.png") 40% 35% no-repeat;
            background-size: 50%;
        }
        .help-center .cost h3 {
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn04.png") 40% 35% no-repeat;
            background-size: 50%;
        }
        .help-center .other h3 {
            background: transparent url("<?= $this->absBaseUrl;?>/credit/img/sxd/content_list_btn05.png") 40% 35% no-repeat;
            background-size: 50%;
        }
        .help-center h3 {
            color: #16D9D6;
        }

</style>
<body>
<div id="dialog-wraper"></div>
<div class="help-center kdlc_mobile_wraper">
    <?php if ($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT): ?>
        <div class="consultation">
            <a class="online-consultation" id="online" >
                <h4>在线咨询</h4>
            </a>
            <a class="phone-consultation" id="callphone" href="javascript:callPhoneMehtod('<?= $call;?>')">
               <h4 id="msg">电话咨询</h4>
           </a>
        </div>
    <?php endif; ?>
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
            <!-- <li id="36">逾期还能申请续期服务吗？</li>-->
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
        <?php if ($title == false): ?>
        Initialization();
        <?php endif; ?>
        <?php if($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){?>
        function Initialization() {
            try {
                nativeMethod.returnNativeMethod('{"type":"5"}');
            } catch (e) {
            }
        }
        <?php }?>
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
