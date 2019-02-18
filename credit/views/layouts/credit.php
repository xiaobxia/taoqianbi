<?php
use common\helpers\Util;
if(Util::getMarket()==\common\models\LoanPerson::APPMARKET_XJBT){
    $this->title = \common\models\LoanPerson::$source_app[\common\models\LoanPerson::APPMARKET_XJBT].'-快速借钱，贷款神器';
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title><?php echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; ?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES; ?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <meta name="format-detection" content="telephone=no" />
      <meta name="appVersion" content="<?php $data = $this->actionSetHeaderUrl();if(isset($data['appVersion'])){echo $data['appVersion'];}?>">
    <meta http-equiv="cache-control" content="private">
    <script src="<?=$this->staticUrl('js/flexible.js?v=2016110901',2); ?>"></script>
    <link href="<?=$this->staticUrl('css/style.css?v=2016122202',2); ?>" rel="stylesheet"/>
	<?php if($h5_theme_style = Util::t('h5_theme_style')){ ?>
	   <link href="<?=$this->staticUrl('css/'.$h5_theme_style,2); ?>" rel="stylesheet"/>
	<?php } ?>
    <script src="<?=$this->staticUrl('js/jquery.js',2); ?>"></script>
    <script type="text/javascript" src="<?=$this->staticUrl('js/spin.js',2); ?>"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/common.js?v=2016112901"></script>
    <script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/Umeng.js?v=2017080401"></script>
    <script>
      var _hmt = _hmt || [];
      (function() {
        var hm = document.createElement("script");
        hm.src = "https://hm.baidu.com/hm.js?985acfc678db5c774efb3ed1a2235b53";
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(hm, s);
      })();
  </script>


  </head>
  <body>
    <?php echo $content; ?>
  </body>
</html>
