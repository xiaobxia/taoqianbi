<?php
use yii\helpers\Url;
use yii\helpers\Html;
use credit\components\ApiUrl;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>

<div class="invite">
  <div class="head">
    <p><i><?php echo $inviteInfo['count']; ?></i>位好友为我赚取了</p>
    <h1><i>￥</i><?php echo sprintf("%.2f", $inviteInfo['total_money']/100); ?></h1>
    <p>邀请一位好友放款最高可得50元奖励，多邀多得</p>

        <?php if(YII_ENV_PROD): ?>
    <a href="//h5.kdqugou.com/activity/inviteThree/index.html#/home">查看详细规则></a>
        <?php else: ?>
    <a href="//42.96.204.114/koudai/kdkj/h5/mobile/web/activity/inviteThree/index.html#/home">查看详细规则></a>
        <?php endif; ?>
  </div>
  <div class="content">
    <ul>
        <li><span>直接邀请</span><a id="sendCoupon" href="javascript:void(0);">立即邀请</a></li>
      <li><span>我的奖金</span><a href="<?php echo $baseUrl . '/credit-invite/invite-rebates-apply-cash'; ?>">点击查看</a></li>
    </ul>
  </div>
</div>

<script>

    $("#sendCoupon").click(function(e){
        e.preventDefault();
        <?php if(YII_ENV_PROD): ?>
        var url = "//h5.kdqugou.com/activity/inviteThree/index.html?invite_code=<?php echo $invite_code ?>";
        <?php else: ?>
        var url = "//42.96.204.114/koudai/kdkj/h5/mobile/web/activity/inviteThree/index.html?invite_code=<?php echo $invite_code ?>";
        <?php endif; ?>
        var image = "//h5.kdqugou.com/activity/inviteThree/img/share-icon1.png";

        nativeShare("有人@你 你有一个红包未领取","手气红包，看看你能领多少钱？",url,image,0,'','','',0);
    });
</script>

