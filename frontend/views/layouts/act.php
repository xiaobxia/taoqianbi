<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
// 是否分享              *isShare;
// 按钮文案              *shareBtnTitle;
// 分享title             *shareTitle;
// 分享有奖描述          *sharePageTitle;
// 分享描述              *shareContent;
// 分享链接              *shareUrl;
// 分享图片              *shareImg;
// 分享渠道              *sharePlatform;   ['wx','wechatf','qq','qqzone','sina','sms'] -> [微信、朋友圈、qq、qq空间、新浪微博、短信]
// 分享成功弹框文案      *shareSuccessAlert;
// 是否上报              *shareIsUp;
// 上报id                *shareUpId;
// 上报类型              *shareUpType;
// 上报url               *shareUpUrl;
$json_arr = [
    'isShare' => 1,
    'shareBtnTitle' => '按钮文案',
    'shareTitle' => '分享title',
    'sharePageTitle' => '分享有奖描述',
    'shareContent' => '分享描述',
    'shareUrl' => 'http://www.koudailc.com',
    'shareImg' => 'http://res.koudailc.com/article/20160506/3572c6e05464b6.png',
    'sharePlatform' => ['wx','wechatf','qq','qqzone','sina','sms'],
    'shareSuccessAlert' => '分享成功弹框文案',
    'shareIsUp' => 1,
    'shareUpId' => 11,
    'shareUpType' => 1,
    'shareUpUrl' => 'http://www.koudailc.com',
];
$json = json_encode($json_arr);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; ?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES;?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <script src="<?php echo $baseUrl;?>/js/flexable.js?v=2016042201"></script>
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <script src="<?php echo $baseUrl;?>/js/jquery.min.js"></script>
    <?php if($this->context->showDownload): ?>
    <script src="<?php echo $baseUrl;?>/js/download.js"></script>
    <?php endif; ?>
    <script>
        var _hmt = _hmt || [];
        (function() {
          var hm = document.createElement("script");
          hm.src = "//hm.baidu.com/hm.js?f98257d164a7690cce2cab7629ec3707";
          var s = document.getElementsByTagName("script")[0];
          s.parentNode.insertBefore(hm, s);
        })();

        kdlcJsApiShareBack();
        function kdlcJsApiShareBack(){
            if (typeof(kdlcJsApi) != 'undefined') {
                kdlcJsApi.pageAddShare('<?php echo $json;?>');
            };
            return 'kdlc_share_back';
        }
    </script>
</head>
<body>
    <img src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/logo_share.png" alt="" style="position:absolute;opacity: 0;left:-10000px;z-index: -1000;">
    <?php echo $content; ?>

</body>
</html>
