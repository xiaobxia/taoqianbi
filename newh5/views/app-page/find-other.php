<?php
use common\services\DiscoverColleagueBannerService;
use yii\helpers\Url;
use common\helpers\StringHelper;
use common\models\LoanPerson;

?>
<!-- 补充css js-->
<link rel="stylesheet" href="<?= $this->source_url(); ?>/css/newh5/swiper.min.css">
<link rel="stylesheet" href="<?= $this->source_url(); ?>/css/newh5/find.css">
<script type="text/javascript" src="<?= $this->absBaseUrl; ?>/js/swiper.min.js"></script>
<!-- 补充css js-->
<div class="find_other">
    <div class="swiper-container banner swiper-container-horizontal">
        <ul class="swiper-wrapper clear">
            <?php foreach ($bannerList as $k => $v):?>
            <li class="fl swiper-slide">
                <a href="<?=$v['link']; ?>">
                    <img src="<?= $v['image']; ?>" alt="活动">
                </a>
            </li>
            <?php endforeach;?>
        </ul>
        <!-- 角标 -->
        <div class="icon swiper-pagination clear swiper-pagination-clickable swiper-pagination-bullets">
        </div>
    </div>
    <!-- tab 切换栏 -->
    <div class="tab">
        <ul class="clear">
            <li class="recommend active"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_1]?></li>
            <li class="selected"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_2]?></li>
            <li class="big"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_3]?></li>
        </ul>
    </div>
    <!-- 推荐APP -->
    <div class="other_app recommend_app">
        <ul>
            <?php foreach ($list as $key => $val):
            if($val['cate_id'] == DiscoverColleagueBannerService::C_ID_1){
            ?>
            <li>
                <a href="<?=$val['link'];?>">
                    <div class="app_top clear">
                        <img class="logo fl" src='<?=$val['icon'];?>'>
                        <div class="app_con fl">
                            <h1><?=$val['title'];?></h1>
                            <div class="daily_rate rate fl">
                                <p>日利率:
                                    <span><?=$val['day_rate'];?></span>
                                </p>
                            </div>
                            <div class="quota rate fl">
                                <p>额度:
                                    <span><?=$val['credit_limit'];?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="app_bottom clear">
                        <?php if(!empty($val['tags'])){
                            foreach($val['tags'] as $k=>$v){?>
                            <span><?=$v?></span>
                        <?php }}?>
                    </div>
                    <div class="tip"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_1]?></div>
                </a>
            </li>
            <?php }?>
            <?php endforeach;?>
        </ul>
    </div>
    <!-- 精选APP -->
    <div class="other_app selected_app" style="display: none;">
        <ul>
            <?php foreach ($list as $key => $val):
                if($val['cate_id'] == DiscoverColleagueBannerService::C_ID_2){
                    ?>
                    <li>
                        <a href="<?=$val['link'];?>">
                            <div class="app_top clear">
                                <img class="logo fl" src='<?=$val['icon'];?>'>
                                <div class="app_con fl">
                                    <h1><?=$val['title'];?></h1>
                                    <div class="daily_rate rate fl">
                                        <p>日利率:
                                            <span><?=$val['day_rate'];?></span>
                                        </p>
                                    </div>
                                    <div class="quota rate fl">
                                        <p>额度:
                                            <span><?=$val['credit_limit'];?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="app_bottom clear">
                                <?php if(!empty($val['tags'])){
                                    foreach($val['tags'] as $k=>$v){?>
                                        <span><?=$v?></span>
                                    <?php }}?>
                            </div>
                            <div class="tip"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_2]?></div>
                        </a>
                    </li>
                <?php }?>
            <?php endforeach;?>
        </ul>
    </div>
    <!-- 大额APP -->
    <div class="other_app big_app" style="display: none;">
        <ul>
            <?php foreach ($list as $key => $val):
                if($val['cate_id'] == DiscoverColleagueBannerService::C_ID_3){
                    ?>
                    <li>
                        <a href="<?=$val['link'];?>">
                            <div class="app_top clear">
                                <img class="logo fl" src='<?=$val['icon'];?>'>
                                <div class="app_con fl">
                                    <h1><?=$val['title'];?></h1>
                                    <div class="daily_rate rate fl">
                                        <p>日利率:
                                            <span><?=$val['day_rate'];?></span>
                                        </p>
                                    </div>
                                    <div class="quota rate fl">
                                        <p>额度:
                                            <span><?=$val['credit_limit'];?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="app_bottom clear">
                                <?php if(!empty($val['tags'])){
                                    foreach($val['tags'] as $k=>$v){?>
                                        <span><?=$v?></span>
                                    <?php }}?>
                            </div>
                            <div class="tip"><?=DiscoverColleagueBannerService::$categoryInfo[DiscoverColleagueBannerService::C_ID_3]?></div>
                        </a>
                    </li>
                <?php }?>
            <?php endforeach;?>
        </ul>
    </div>
</div>

<script>
    /*
     轮播图
     */
    var swiper = new Swiper('.swiper-container', {
        pagination: '.swiper-pagination',
        nextButton: '.swiper-button-next',
        prevButton: '.swiper-button-prev',
        paginationClickable: true,
        spaceBetween: 0,
        centeredSlides: true,
        autoplay: 2500,
        autoplayDisableOnInteraction: false,
        loop: true
    });

    /*
     Tab切换
     */
    function tab() {
        var $tab = $('.tab ul');
        //tab栏
        var tabLis = $tab.find('li');
        // 内容
        var conLis = $('.other_app');
        $tab.on('click', 'li', function () {

            //排他
            conLis.css('display','none');
            for (var i = 0; i < tabLis.length; i++) {
                tabLis[i].classList.remove('active');
            }
            //点击项样式 内容一致
            var appType = this.classList[0];
            $('.' + appType + '_app').css('display','block')
            this.classList.add('active')
        })
    }
    tab();
</script>


