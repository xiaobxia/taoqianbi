<div id='content1' class='content1 full'>
    <div class='top'>
        <div class='top_left'>
            <a href=""><img src="<?= $this->absBaseUrl;?>/image/pc-site/logo.png" class='top_img'></a>
        </div>
        <div class='top_right'>
            <font face="微软雅黑">客服热线：<?php echo SITE_TEL; ?></font>
        </div>
    </div>
    <div class='mid'>
        <div class='mid_left'>
            <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_img_text.png" class='mid_left_img animated wobble'>
            <div class='mid_button'>
                <a href="http://itunes.apple.com/app/id1221186366?mt=8" class="mid_button_left animated bounceInDown"></a>
                <a href="http://qbres.wzdai.com/apk/xybt-<?= \yii::$app->request->get('source_tag', 'latest') ?>.apk" class="mid_button_right animated bounceInDown"></a>
            </div>
        </div>
        <div class='mid_right'>
            <div class="animated fadeInUp300 fadeInD500" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_img_personal.png" />
            </div>
            <div class="mid_right1 animated500ms fadeInR200 fadeInD1000" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_icon_Features02.png" />
            </div>
            <div class="mid_right2 animated500ms fadeInR200 fadeInD1100" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_icon_Features01.png" />
            </div>
            <div class="mid_right3 animated500ms fadeInR200 fadeInD1200" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_icon_Features05.png" />
            </div>
            <div class="mid_right4 animated500ms fadeInR200 fadeInD1300" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_icon_Features03.png" />
            </div>
            <div class="mid_right5 animated500ms fadeInR200 fadeInD1400" >
                <img src="<?= $this->absBaseUrl;?>/image/pc-site/home_icon_Features04.png" />
            </div>
        </div>
    </div>
</div>

<div id='content2' class='content1 full'>
    <div class='mid2'>
        <div class='mid'>
            <div class="mid2_txt">
                <div style="position: relative;">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_img_background.png" class="icon0 animated rotateIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon01.png" class="icon1 animated rollIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon02.png" class="icon2 animated rollIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon03.png" class="icon3 animated rollIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon04.png" class="icon4 animated rollIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon05.png" class="icon5 animated rollIn">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/cycle_icon06.png" class="icon6 animated rollIn">
                </div>
            </div>

            <div class="mid2_right">
                <p class="dzq">短周期</p>
                <p class="gjq">借款时间清晰明确<span style='color: #1782e0;font-size: 30px'> - 更准确</span></p>
                <p class="asd">14天</p>
            </div>

        </div>
    </div>
</div>

<div id='content3' class='content1 full'>
    <div class='mid'>
        <div class="mid3_left">
            <p class="dzq">小额度</p>
            <p class="gjq">自定义借款金额<span style='color: #1782e0;font-size: 30px'> - 更灵活</span></p>
            <p class="asd">500 - 5000元</p>
        </div>

        <div class='mid3_txt'>
            <img src="<?= $this->absBaseUrl;?>/image/pc-site/Quota_img_back.png" style='position: relative;float: right;' class="mid3_txt0 animated bounce" >
            <div class="mid3_txt_left animated fadeInLeft"><img src="<?= $this->absBaseUrl;?>/image/pc-site/Quota_icon_line01.png"></div>
            <div class="mid3_txt_right animated fadeInLeft"><img src="<?= $this->absBaseUrl;?>/image/pc-site/Quota_icon_line02.png"></div>
        </div>
    </div>
</div>
<div id='content4' class='content1 full'>
    <div class='mid4'>
        <div class='mid4s'>
            <div class="mid2_txt">
                <div style="position: relative;">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/fast_img_back.png" class='icon0 animated swing'>
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/fast_icon02.png"  class="icon1 animated tada">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/fast_icon04.png" class="mid4_icon2 animated tada">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/fast_icon03.png" class="mid4_icon3 animated tada">
                    <img src="<?= $this->absBaseUrl;?>/image/pc-site/fast_icon01.png" class="mid4_icon4 animated tada">
                </div>
            </div>

            <div class="mid2_right">
                <p class="dzq">放款快</p>
                <p class="gjq">快速审批，极速放款<span style='color: #1782e0;font-size: 30px'> - 更迅速</span></p>
                <p class="asd">最快30分钟</p>
            </div>
        </div>
    </div>
    <div id='bottom'>
        <?php
            if(Yii::$app->request->hostName == 'www.xybaitiao.com' || Yii::$app->request->hostName == 'xybaitiao.com'){
                echo "<div class='bottom'><?php echo COMPANY_NAME;?>版权所有 © 2011-2018 <?php echo APP_NAMES;?> All Right Reserved <a href=\"http://www.miitbeian.gov.cn\"><?php echo SITE_ICP; ?></a> <a href=\"http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=31011002000164\"><img src=\"$this->absBaseUrl/image/pc-site/ghs_b769e8d.png\" width=\"18\" style=\"vertical-align:bottom;\"></a>沪公网安备 31011002000164号</div>";
            }else{
                echo "<div class='bottom'><?php echo COMPANY_NAME;?>版权所有 © 2011-2018 <?php echo APP_NAMES;?> All Right Reserved <a href=\"http://www.miitbeian.gov.cn\"><?php echo SITE_ICP; ?></a> <a href=\"http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=31011002000164\"><img src=\"$this->absBaseUrl/image/pc-site/ghs_b769e8d.png\" width=\"18\" style=\"vertical-align:bottom;\"></a>沪公网安备 31011002000164号</div>";
            }
        ?>
    </div>
</div>

<script src="<?= $this->absBaseUrl;?>/js/jquery-1.8.3.min.js"></script>
<script src="<?= $this->absBaseUrl;?>/js/jquery.easing.1.3.js"></script>
<script src="<?= $this->absBaseUrl;?>/js/jquery.scrollify.min.js"></script>
<script type="text/javascript">
$(function() {
    $.scrollify({
    });
});
</script>
        section: '.content1',
