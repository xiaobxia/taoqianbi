<?php

use yii\helpers;
?>
<style>
body {
    background-color: #f5f5f5;
}
</style>
<div class="application-success">
    <div class="application-title">
        <h1>借款申请已提交</h1>
    </div>
    <div class="loan_status">
        <ul>
            <li>
                <i class="iconfont">&#xe614;</i>
                <p>申请借款</p>
            </li>
            <li>
                <i class="iconfont">&#xe614;</i>
                <p>审核通过</p>
            </li>
            <li>
                <i class="iconfont">&#xe614;</i>
                <p>打款成功</p>
            </li>
            <li>
                <i class="iconfont">&#xe614;</i>
                <p>还款成功</p>
            </li>
        </ul>
    </div>
    <?php if (\common\helpers\Util::getMarket() != \common\models\LoanPerson::APPMARKET_KXJIE):?>
    <div class="wx-wrap">
        <div class="wx-tdCode">
            
        </div>
        <p>关注并绑定<?php echo WEIXIN_GONGZHONGNHAO ?>微信公众号尊享更多福利</p>
        <p style="display: none">关注并绑定微信公众号尊享更多福利(公众号正在审核中)</p>
        <div class="wx-bindBtn">
            <div class="bindBtn-head">
            • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • • •
                <span class="">1</span>
                <span class="">2</span>
            </div>
            <div class="bindBtn-title">
               <p class="fl">微信搜索“<?php echo WEIXIN_GONGZHONGNHAO_SHORENAME; ?>”</p>
               <p class="fr">关注并完成绑定</p> 
            </div>
            <div class="bindBtn">
                <?php if(@$wx_info):?>
                <button class="fl" type="disabled">公众号已绑定</button>
                <?php else:?>
                    <button class="fl" id="copy_wx" type="">复制去关注</button>
                <?php endif;?>

                <a href="<?php if(isset($order_id)):?><?php echo helpers\Url::toRoute(['loan/loan-detail', 'id' => $order_id])?><?php endif;?>" class="fr" >查看借款详情</a>
            </div>
        </div>
        <div class="wx-wrap-bottom">
            
        </div>
    </div>
    <?php endif;?>

<script>
    try {
        setWebViewFlag();
        //页面进入时打点
        MobclickAgent.onEvent('apply_sucess','申请成功页面事件') 
    } catch(e) {
        console.log(e);
    }
    $("#copy_wx").click(function(){
        copyText('<?php echo WEIXIN_GONGZHONGNHAO_SHORENAME; ?>');
        returnNative('14');
    })
</script>