<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<?php if(isset($page_js)){
    echo $page_js;
};?>
<?php if(isset($gdt)){
    echo $gdt;
};?>
<link rel="stylesheet" href="<?= $this->source_url();?>/themes/stream3/css/channel-valentine.css">
<div id="channel-valentine" class="valentinebg">
    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/valentinebg1.png" />
    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/valentinebg2.png" />
    <div class="input-form">
        <input class="_radiu_16px" type="tel" id="phone" value="" maxlength="11" placeholder="请输入手机号码" />
        <div class="code-input">
            <input class="_input_sp _radiu_16px" type="text" id="password" maxlength="6" placeholder="请输入短信验证码" />
            <button class="btn get_code _radiu_16px" id="autoGetCode">获取验证码</button>
        </div>
        <div class="btn _radiu_16px submit" id="autoRegister">赢免单</div>
    </div>
    <div class="about">
        <img class="about_img" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/about.png">
        <div class="about_con">
            <ul class="clear">
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon01.png" />
                    <h3>操作便捷</h3>
                    <p>只需3步, 搞定</p>
                    <p>贷款</p>
                </li>
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon02.png" />
                    <h3>认证简单</h3>
                    <p>5个维度, 无抵</p>
                    <p>押无担保</p>
                </li>
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon03.png" />
                    <h3>快速审核</h3>
                    <p>智能大数据审</p>
                    <p>核, 最快5分钟完成</p>
                </li>
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon04.png" />
                    <h3>快速房贷</h3>
                    <p>自由选择借款</p>
                    <p>金额, 快速5分钟到账</p>
                </li>
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon05.png" />
                    <h3>超低利息</h3>
                    <p>每天低于1元, 让</p>
                    <p>您借款更安心</p>
                </li>
                <li>
                    <img width="100%" src="<?php echo $this->absBaseUrl;?>/themes/stream3/image/valentine-x/icon06.png" />
                    <h3>额度更高</h3>
                    <p>还款记录良好, 尊</p>
                    <p>享更高额度</p>
                </li>
            </ul>
        </div>
    </div>
    <div class="p_footer">
        <p>@ 2011~2017 <?php echo COMPANY_NAME;?></p>
        <p><?php echo SITE_ICP; ?></p>
    </div>
</div>
<script type="text/javascript">
    // 初始化数据
    var get_code_url = '<?php echo ApiUrl::toRoute(["xqb-user/reg-get-code"], true);?>'; // 获取验证码链接
    var register_url = '<?php echo ApiUrl::toRoute(["xqb-user/register", "appMarket" => $this->source_tag], true); ?>';
    var reg_sms_key = '<?php echo $reg_sms_key;?>'; // 验证码防刷key
    var isfromweichat = <?php echo $this->isFromWeichat() ? 1 : 0;?>; // 是否在微信里
    var pop_params = {btn_bg_color: '#1ebdbd', btn_txt_color: '#fff',btn_txt_size: '0.4rem'}; // 弹框样式默认
    var source = {source_id:<?php echo $this->source_id;?>,source_tag:'<?php echo $this->source_tag;?>',source_app:'<?php echo $this->source_app?>'};


    $(function(){
        // 获取验证码
        $('#autoGetCode').click(function(){
            getCode($('#phone').val(),get_code_url,source,reg_sms_key,isfromweichat,pop_params)
        });
        $('#autoRegister').on('click',function(){
            var password = $("#password").length > 0 ? $("#password").val() : undefined;
            register($("#phone").val(),$('#code').val(),register_url,source,isfromweichat,pop_params);
        })
    });
    
</script>