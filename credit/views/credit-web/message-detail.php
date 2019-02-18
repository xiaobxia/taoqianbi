<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title><?php echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; ?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES;?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <meta name="format-detection" content="telephone=no" />
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <link href="<?=$this->staticUrl('css/message.css?v=2016112602',2); ?>" rel="stylesheet"/>
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
    <?php echo $content;?>
  </body>
</html>
