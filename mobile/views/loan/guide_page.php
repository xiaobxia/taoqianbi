<?php
use \mobile\components\ApiUrl;
?>
<!-- 补充css-->
<link rel="stylesheet" href="<?= $this->source_url();?>/css/guide.css">
<div class="guide_page">
    <div class="banner">
        <a href="<?=ApiUrl::toNewh5(['app-page/find-other'])?>">
            <span>戳我去热门贷款吧>></span>
        </a>
    </div>
    <div class="other_app">
        <ul>
            <?php foreach($find_list as $k=>$v):?>
            <li>
                <a href="<?= $v['link']?>">
                    <div class="app_top clear">
                        <img class="logo fl" src='<?= $v['icon']?>'>
                        <div class="app_con fl">
                            <h1><?= $v['colleague_name']?></h1>
                            <div class="daily_rate rate fl">
                                <p>日利率:
                                    <span><?= $v['day_rate']?></span>
                                </p>
                            </div>
                            <div class="quota rate fl">
                                <p>额度:
                                    <span><?= $v['credit_limit']?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="line"></div>
                    <div class="app_bottom clear">
                        <?php foreach ($v['tag_list'] as $lists):?>
                            <span><?= $lists?></span>
                        <?php endforeach;?>
                        <div class="click">点击拿钱<i></i></div>
                    </div>
                    <div class="tip">精选</div>
                </a>
            </li>
            <?php endforeach;?>
        </ul>
    </div>
</div>
<script>
    $(function(){
            return nativeMethod.returnNativeMethod('{"type":"19","data":{"btn_text":"借款明细","click":{"type":"2103","url":"<?= $loan_jump_url?>"}}}');
    });
    function jump() {
        return nativeMethod.returnNativeMethod('{"type":"15"}');
    }
</script>

