<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>

<!-- 补充css -->
<link rel="stylesheet" href="<?= $this->source_url();?>/css/extra-css/jshb-reg-1.css?V=20181986">
<div id="stream" class="streambg">
    <div class="count-down">
        <div class="Parolantoj"></div>
        <div class="time" style="display: none;">
            <span class="minute-ten">1</span>
            <span class="minute-bit">9</span>
            <i></i>
            <span class="second-ten">0</span>
            <span class="second-bit">0</span>
        </div>
    </div>
    <div class="con">
        <div class="input-form">
            <div class="user-input">
                <i class="iconUser"></i>
                <input class="_radiu_16px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" style="color: #666;" />
            </div>
            <div class="code-input">
                <i class="iconMsg"></i>
                <input class="_input_sp _radiu_16px" type="text" id="code" maxlength="6" placeholder="请输入验证码" style="color: #666;" />
                <button class="btn get_code _radiu_16px" id="autoGetCode">获取验证码</button>
            </div>
            <div class="password">
                <input class="_radiu_16px" type="password" id="password" value="" placeholder="请设置6~12位登陆密码" style="color: #666;" />
            </div>
            <div class="submit btn _radiu_16px" id="autoRegister">立即注册</div>
            <div class="checktext">
                <!-- <img class="ture" src="<?= $this->source_url();?>/themes/stream11/image/ture.png" alt="">
                注册即代表同意
                <a href="http://qbcredit.wzdai.com/credit-web/safe-login-txt?id=124" class="agreement">《<?php echo $this->title ? $this->title : APP_NAMES ?>用户使用协议》</a> -->
            </div>
        </div>
    </div>
</div>
<script src="https://s22.cnzz.com/z_stat.php?id=1274363233&web_id=1274363233" language="JavaScript"></script>
<script>
    $(function(){
        $('.container [title="站长统计"]').text('');
        //页面统计
        tjDownApp('visit','init','');
    });
    // 初始化数据
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#fb3c19', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};

    var roll_url = "<?= ApiUrl::toRouteCredit(['credit-app/user-multi-message']);?>"; // 获取轮播数据地址
    var verify_code_url = '<?php echo  ApiUrl::toRoute(["xqb-user/verify"]); ?>'; //生成验证码url
    var check_verify_code_url = '<?php echo  ApiUrl::toRoute(["xqb-user/check-verify"]); ?>';//检查验证码url
    $(function(){
        var isAndroid =  navigator.userAgent.indexOf("Android") > 0
        if (window.browser.wx && isAndroid) {
            return wxDownload();
        }
        // 获取验证码
        $('#autoGetCode').click(function(){
            getImgCode($("#phone").val(),source,reg_sms_key,isfromweichat,pop_params,verify_code_url,check_verify_code_url);
        });
        $('#autoRegister').click(function(){
            var password = $("#password").length > 0 ? $("#password").val() : undefined;
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params,$('#password').val());
        });
    });
    //倒计时函数
    var mt = $('.minute-ten').text();//分钟的十位
    var mb = $('.minute-bit').text();//分钟的个位
    var st = $('.second-ten').text();//秒的十位
    var sb = $('.second-bit').text();//秒的个位

    secondBitCount();
    function minuteTenCount () {
        if (mb < 0) {
            mb = 5;
            mt--;
        }
        $('.minute-bit').text(mb);
        $('.minute-ten').text(mt)
    }
    function minuteBitCount () {
        if (st < 0) {
            st = 5;
            mb--;
        }
        $('.second-ten').text(st);
        minuteTenCount();
    }
    function secondTenCount() {
        if (sb < 0) {
            sb = 9;
            st--;
        }
        $('.second-bit').text(sb)
        minuteBitCount()
    }
    function secondBitCount() {
        var timer = setInterval(function(){
            if (mt < 0) {//结束
                $('.second-ten').text(0);
                $('.second-bit').text(0);
                $('.minute-ten').text(0);
                $('.minute-bit').text(0);
                clearInterval(timer)
                return;
            }
            sb--;
            secondTenCount();
        },1000)
    }
</script>