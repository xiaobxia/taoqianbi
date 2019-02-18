<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<div id="channel-page-one">
    <img width="100%" src="<?php echo $this->absBaseUrl;?>/image/page/bannerv1.png" />
    <div class="p_relative">
        <div class="rollshow _radiu_16px" id="rollshowbox">
            <div class="rollline">
                <div class="r_l_sqr"></div>
                <div class="r_l_line"><span></span></div>
                <div class="r_l_sqr"></div>
                <div class="r_l_line"><span></span></div>
                <div class="r_l_sqr"></div>
            </div>
            <div id="rollshow">
                <ul id="rollshow1">  </ul>
            </div>
        </div>
        <div class="input-form">
            <input class="_radiu_16px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
            <div class="code-input">
                <input class="_input_sp _radiu_16px" type="text" id="code" maxlength="6" placeholder="请输入短信验证码" />
                <button class="btn get_code _radiu_16px" id="autoGetCode">获取验证码</button>
            </div>
            <div class="clear"></div>
            <div class="checktext">
                <i class="iconfont">&#xe62d;</i>
                注册即代表同意 
                <a href="http://qbcredit.wzdai.com/credit-web/safe-login-txt?id=122" class="agreement">《<?php echo $this->title ? $this->title : APP_NAMES?>用户使用协议》</a>
            </div>
            <div class="btn _radiu_16px" id="autoRegister">开始使用</div>
        </div>
    </div>
    <div class="p_footer">
        <p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
        <p><?php echo SITE_ICP;?></p>
    </div>
</div>
<script type="text/javascript">
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#6637C5', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};
    $(function(){
        // 获取轮播数据且进行滚动轮播
        rollShowData();

        // 获取验证码
        $('#autoGetCode').click(function(){
            getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
        });
        $('#autoRegister').click(function(){
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
        });
    });
    
    // 轮播数据
    function rollShowData(){
        var url = "<?= ApiUrl::toRouteCredit(['credit-app/user-multi-message']);?>";
        $.get(url,function(data){
            if(data && data.code == 0){
                for(var i = 0; i < data.message.length; i++){
                    var $li = $('<li></li>');
                    $li.text(data.message[i]);
                    $('#rollshow1').append($li);
                }
                $("#rollshow1").append($("#rollshow1").children().clone(true));
                setInterval('AutoScroll("#rollshow", 1)', 2500); 
            }
            if(data.code == -1){
                // 设置隐藏
                $('#rollshowbox').hide();
            }
        });
    }
    // 轮播
    function AutoScroll(obj, n) {
        var $liHeight = $(obj).children("ul:first").children("li:first").outerHeight();
        $(obj).children("ul:first").animate({
            marginTop: "-"+$liHeight*n+"px"
        },
        500,
        "swing",
        function() {
            $(this).css({
                marginTop: "0"
            }).children("li:lt("+n+")").appendTo(this);
        });     
    }
</script>