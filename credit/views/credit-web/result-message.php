<?php
use common\models\LoanPerson;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
$activity_int = 0;
$banner_int = 0;
?>
<style type="text/css">
    body{background: #f5f5f5;}
    .msg-box{
        text-align:center;
        color:#666;
        padding-top:1.6rem;
    }
    .msg{
        height:0.666667rem;
        line-height:0.666667rem;
    }
    .activity{
        display: none;
    }
    .message-center div.tab {
        text-align: center;
        margin-top: 0;
        font-size: 0;
        background: #FFF;
        height:1.306667rem;
        line-height:1.306667rem;
        padding-bottom: 6px;
    }
    .message-center div.tab a.current {
        background: #fff;
        color: #6a4dfc;
        border-bottom: 6px solid #6a4dfc;
    }
    .message-center div.tab a {
        display: inline-block;
        text-decoration: none;
        font-size: 0.426667rem;
        width: 40%;
        height:1.306667rem;
        color: #999;
        background: #fff;
        border: none;
        margin: 0 0.333333rem;
        padding: 0 0.8rem;
    }
    .message-center div.tab a:first-child{
        border-right: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .message-center div.tab a:last-child {
        border-left: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .result-wait .content {
        position: absolute;
        top: 30%;
    }
    .result-wait .content img {
        display: block;
        width: 5.133333rem;
        height: 5.133333rem;
        margin: 0 auto 0.4rem;
    }
</style>
<?php if (count($data_list) > 0): ?>
    <div id="message-center" class="message-center">
        <!-- 添加切换 -->
        <div class="tab">
            <a class="current">公告</a>
            <a>活动</a>
        </div>
        <input id="redgiftNextPage" type="hidden" currentpage="2"/>
        <?php foreach ($data_list as $data): ?>
            <?php if($data["use_case"] == 1): ?>
                <div class="activity">
                    <h5></h5>
                    <?php $activity_int++; ?>
                    <?php if (isset($data["link"]) && !empty($data["link"])): ?>
                            <a href="<?= $data["link"]; ?>" class="activity-a">
                                <h1><?= $data["title"]; ?></h1>
                                <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                                <div class="content">
                                    <img src="<?= $data["banner"] ?>" width="100%" alt=""/>
                                </div>
                            </a>
                    <?php elseif (isset($data["banner"]) && !empty($data["banner"])): ?>
                            <a href="<?= $baseUrl; ?>/credit-web/message-detail?id=<?= $data["id"]; ?>" class="activity-a">
                                <h1><?= $data["title"]; ?></h1>
                                <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                                <div class="content">
                                    <img src="<?= $data["banner"] ?>" width="100%" alt=""/>
                                </div>
                            </a>
                    <?php else: ?>
                            <h1><?= $data["title"]; ?></h1>
                            <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                            <p><?= $data["content"]; ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="banner">
                    <h5></h5>
                    <?php $banner_int++; ?>
                    <?php if (isset($data["link"]) && !empty($data["link"])): ?>
                            <a href="<?= $data["link"]; ?>" class="activity-a">
                                <h1><?= $data["title"]; ?></h1>
                                <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                                <div class="content">
                                    <img src="<?= $data["banner"] ?>" width="100%" height="250" alt=""/>
                                </div>
                            </a>
                    <?php elseif (isset($data["banner"]) && !empty($data["banner"])): ?>
                            <a href="<?= $baseUrl; ?>/credit-web/message-detail?id=<?= $data["id"]; ?>" class="activity-a">
                                <h1><?= $data["title"]; ?></h1>
                                <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                                <div class="content">
                                    <img src="<?= $data["banner"] ?>" width="100%" height="250" alt=""/>
                                </div>
                            </a>
                    <?php else: ?>
                            <h1><?= $data["title"]; ?></h1>
                            <h6><?= date('Y-m-d H:i', $data["created_at"]); ?></h6>
                            <p><?= $data["content"]; ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <!-- 无公告 -->
    <?php if($banner_int == 0){ ?>
        <div id="banner_show" class="result-wait message-nonews banner" style="display: none;">
            <div class="content">
                <img alt="" src="<?= $this->staticUrl('image/card/content_icon_no_date.png', 1); ?>"/>
                <p>暂无公告哦~</p>
            </div>
        </div>
    <?php } ?>
    <!-- 无活动 -->
    <?php if($activity_int == 0){ ?>
        <div id='activity_show' class="result-wait message-nonews activity" style="display: none;">
            <div class="content">
                <img alt="" src="<?= $this->staticUrl('image/card/content_icon_no_date.png', 1); ?>"/>
                <p>暂无活动哦~</p>
            </div>
        </div>
    <?php } ?>
<?php else: ?>
    <div class="msg-box">
        <div>
        <?php if ($source == LoanPerson::PERSON_SOURCE_HBJB) { ?>
        <img src="<?= $this->staticUrl('image/card/hbqb_icon_norecord.png', 1); ?>" width="60%" />
        <?php }elseif ($source == LoanPerson::PERSON_SOURCE_WZD_LOAN){ ?>
        <img src="<?= $this->staticUrl('image/card/content_icon_prompt.png', 1); ?>" width="60%" />
        <?php }elseif (\common\helpers\Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){ ?>
        <img src="<?= $this->staticUrl('image/card/content_icon_prompt_pro.png', 1); ?>" width="60%" />
        <?php }elseif ($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){ ?>
        <img src="<?= $this->staticUrl('image/card/icon-1-03.png', 1); ?>" width="60%" />
        <?php } ?>
        </div>
        <div class="msg">您还没有任何的信息记录</div>
    </div>
<?php endif; ?>

<script type='text/javascript'>
$(function() {
    $('div.tab a').click(function(e) {
        $('div.tab a').removeClass('current');
        $('div.lists').hide();
        $(this).addClass('current');
        var sel_index = $(this).index();
        $('div.lists').eq(sel_index).show();
        if (sel_index == 0) {
            $(".banner").show();
            $("#banner_show").show();
            $(".activity").hide();
            $("#activity_show").hide();

        }else{
            $(".banner").hide();
            $("#banner_show").hide();
            $("#activity_show").show();
            $(".activity").show();
        }
        return false;
    })
})
</script>
