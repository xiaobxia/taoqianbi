<?php
use yii\helpers;
?>

<div class="application-success">
    <div class="application-title">
         <h1><?php  if($_GET['code']==0){echo '恭喜您，本次还款成功！';}elseif($_GET['code']==-2){echo '您的还款申请已经提交！';}elseif ($_GET['step']=='one'&&$_GET['code']==-1){echo '还款申请提交失败！';}elseif ($_GET['step']=='two'&&$_GET['code']==-1){echo '很遗憾，本次还款失败！';}elseif ($_GET['code']==-1){echo '很遗憾，本次还款失败！';} ?></h1>
        <!-- 非还款成功 显示对应的文案信息 -->
        <?php if(isset($_GET['title']) && $_GET['code']!=0){ ?>
         <p><?php echo $_GET['title'];?></p>
        <?php } ?>
    </div>
    <div class="wx-wrap">
        <div class="wx-tdCode">

        </div>
        <p style="display: none">关注并绑定<?php echo WEIXIN_GONGZHONGNHAO ?>微信公众号尊享更多福利</p>
        <p style="display: none">关注并绑定微信公众号尊享更多福利(公众号正在审核中)</p>
        <p>首次还款添加微信lxw17788598965得50元红包，非首次还款可以的得小红包！具体详情看app公告</p>
        <div class="wx-bindBtn">
            <div class="bindBtn-head">
            • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • •
                <span class="">1</span>
                <span class="">2</span>
            </div>
            <div class="bindBtn-title">
               <p class="fl">微信搜索“lxw17788598965”</p>
               <p class="fr">获得红包</p>
            </div>
            <div class="bindBtn">
                <?php if(isset($wx_info)):?>
                <button class="fl" type="disabled" style="display: none">公众号已绑定</button>
                <?php else:?>
                    <button class="fl" id="copy_wx" type="">复制去关注</button>
                <?php endif;?>
                <a href="<?php if(isset($order_id)):?><?php echo helpers\Url::toRoute(['loan/loan-detail', 'id' => $order_id])?><?php endif;?>" class="fr" >查看借款详情</a>
<!--                <a href="#" class="fr" >查看借款详情</a>-->
            </div>
        </div>
       <!-- <div class="wx-wrap-bottom">

        </div>-->
    </div>

   <!-- <div class="activity">
        <div class="activity-head">

        </div>
        <div class="events">
            <p class="prize">送您一次拿<span>iphone</span>的机会</p>

             <p class="obtain">借款申请通过后可参与</p>

            <a href="#" class="activity-detail" >查看详情</a>
        </div>-->
        <!-- <div class="Coupon">
            <p class="prize">送您一张<span>15元</span>的优惠券</p>

            <p class="obtain">还款申请通过后即刻发放</p>
            <p class="obtain">借款申请通过后即刻发放</p>

            <p class="activity-detail">关注微信公众号，福利不错过！</p>
        </div> -->
<!--    </div>-->
<!--</div>-->

<script>
    try {
        setWebViewFlag();
        //页面进入时打点
        MobclickAgent.onEvent('returnresult','还款结果页面事件')
    } catch(e) {
        console.log(e);
    }
    $("#copy_wx").click(function(){
        copyText('lxw17788598965');
        returnNative('14');
    })// 打点
</script>
