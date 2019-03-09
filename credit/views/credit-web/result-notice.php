<?php
use common\helpers\Util;
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\NoticeSms;
use common\models\LoanPerson;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<style type="text/css">
    body{
        background: #f5f5f5;
    }
    .notice-sms{
        color: #666;
        font-size: 0.42rem;
        padding-bottom: 0.8rem;
    }
    .notice-sms .have-notice{
        padding: 0 0.4rem;
    }
    .notice-sms .have-notice .notice-title{
        height: 1.3rem;
        line-height: 1.3rem;
    }
    .notice-sms .have-notice .notice-title span.left{
        float: left;
    }
    .notice-sms .have-notice .notice-title span.right{
        font-size: 0.34rem;
        float: right;
    }
    .notice-sms .have-notice .notice-content{
        font-size: 0.34rem;
        border-bottom: 1px solid #A7A7A7;
        padding-bottom: 0.1rem;
        line-height: 1.8;
    }
    .notice-sms .none-notice{
        text-align: center;
        padding-top: 120px;
    }
    .mask{
        display: none;
        background: #000;
        opacity: 0.5;
        width: 100%;
        height: 100%;
        position: fixed;
        top: 0;
        left: 0;
    }
    .pop-tip{
        display: none;
        background: #fff;
        width: 80%;
        padding: 0.6rem 5%;
        position: fixed;
        top: 16%;
        left: 10%;
        color: #666;
        font-size: 0.42rem;
        line-height: 2;
        text-align: center;
    }
    .pop-tip .tip-time{
        font-size: 0.34rem;
    }
    .pop-tip .tip-content{
        text-align: left;
        font-size: 0.34rem;
        border-top: 1px solid #A7A7A7;
        border-bottom: 1px solid #A7A7A7;
        margin: 0.2rem 0;
        padding: 0.2rem 0;
    }
    .pop-tip .pop-btn{
        margin-top: 0.4rem;
        padding: 0.14rem 0;
        background: #1ec8e1;
        color: #fff;
    }
    .pop-tip .pop-btn.xybt{
        background: #1ec8e1;
    }
    .pop-tip .pop-btn.hbqb{
        background: #ff6462;
    }
    .pop-tip .pop-btn.wzdai_loan{
        background: #d74a55;
    }
</style>
<div class="notice-sms">
    <?php if($data_list){?>
        <div class="have-notice">
            <?php foreach ($data_list as $notice) :?>
                <div class="notice-detail" onclick="popTip('<?php echo NoticeSms::$types[$notice->type];?>','<?php echo date('Y-m-d H:i:s',$notice->created_at);?>','<?php echo $notice->content;?>')">
                    <div class="notice-title">
                        <span class="left"><?php echo NoticeSms::$types[$notice->type];?></span>
                        <span class="right"><?php echo date('Y-m-d H:i:s',$notice->created_at);?></span>
                    </div>
                    <div class="notice-content">
                        <?php echo mb_substr($notice->content,0,48,'utf-8').'...';?>
                    </div>
                </div>
            <?php endforeach;?>
        </div>
    <?php }else{?>
        <div class="none-notice">
            <?php if ($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT) {?>
                <img src="<?= $this->staticUrl('image/card/icon-1-03.png', 1); ?>" width="60%" />
            <?php } ?>
            <div>您还没有任何的消息记录~~</div>
        </div>
    <?php }?>
</div>
<div class="mask"></div>
<div class="pop-tip">
    <div class="tip-title"></div>
    <div class="tip-time"></div>
    <div class="tip-content"></div>
    <div class="pop-btn <?php echo $source_color;?>" onclick="popHide()">知道了</div>
</div>
<script type="text/javascript">
    function popTip(title,time,content){
        $('.pop-tip .tip-title').html(title);
        $('.pop-tip .tip-time').html(time);
        $('.pop-tip .tip-content').html(content);
        $('.pop-tip').show();
        $('.mask').show();
    }
    function popHide(){
        $('.mask').hide();
        $('.pop-tip').hide();
        $('.pop-tip .tip-title').html('');
        $('.pop-tip .tip-time').html('');
        $('.pop-tip .tip-content').html('');
    }
</script>
