<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<div id="channel-page-one" class="register">
    <img width="100%" src="<?php echo $this->absBaseUrl;?>/image/page/banner-register.png" />
    <div class="p_relative">
        <div class="_radiu_16px" id="rollshowbox">
            <div class="rollline">
                <div class="r_l_sqr"></div>
                <div class="r_l_line"><span></span></div>
                <div class="r_l_sqr"></div>
                <div class="r_l_line"><span></span></div>
                <div class="r_l_sqr"></div>
            </div>
            <div id="rollshow">
                
            </div>
        </div>
        <div class="input-form">
            <input class="_radiu_16px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
            <div class="code-input">
                <input class="_input_sp _radiu_16px" type="text" id="code" maxlength="6" placeholder="请输入短信验证码" />
                <button class="btn get_code _radiu_16px" id="autoGetCode">获取验证码</button>
            </div>
            <div class="btn _radiu_16px" id="autoRegister">闪电借款</div>
        </div>
        <div class="intro _radiu_16px">
            <h3><?php echo APP_NAMES;?>比其他平台更便捷</h3>
            <div class="intro-sec">
                <span class="intro-icon fl"></span>
                <div class="intro-text fl">
                    <h4>无抵押</h4>
                    <p>纯信用贷款，最高5000元</p>
                </div>
            </div>
            <div class="intro-sec">
                <span class="intro-icon fl"></span>
                <div class="intro-text fl">
                    <h4>速度高</h4>
                    <p>3分钟获得额度，5分钟极速借款</p>
                </div>
            </div>
            <div class="intro-sec">
                <span class="intro-icon fl"></span>
                <div class="intro-text fl">
                    <h4>放款快</h4>
                    <p>高效审批，快速放款，闪电到账</p>
                </div>
            </div>
        </div>
    </div>
    <div class="p_footer">
        <p><?php echo $company_name?></p>
<!--        <p>浙ICP备18010618号-1</p>-->
    </div>
</div>

<script type="text/javascript">
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#f18d00', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};

    var roll_url = "<?= ApiUrl::toRouteCredit(['credit-app/user-multi-message']);?>"; // 获取轮播数据地址
    var openid = "<?php echo $openid?>";
    $(function(){
        // 获取轮播数据且进行滚动轮播
        rollShowData("#rollshow", 1, roll_url, "#rollshowbox");

        // 获取验证码
        $('#autoGetCode').click(function(){
            getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
        });
        $('#autoRegister').click(function(){
            register($('#phone').val(),$('#code').val(),register_url,source,isfromweichat,pop_params,'',openid);
        });
    });
    
</script>