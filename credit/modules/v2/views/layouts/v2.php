<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $this->title ? $this->title : APP_NAMES.'-纯信用小额借钱极速放贷'; ?></title>
    <meta name="keywords" content="贷款,小额借钱,借贷,贷款app,急用钱,短期快速放贷,极速借款借钱,小额贷款">
    <meta name="description" content="<?php echo APP_NAMES; ?>专注于为个人提供正规小额贷款、无抵押贷款、个人贷款、闪电借钱等服务">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
    <link href="<?=$this->staticUrl('css/style.css?v=2016122202',2); ?>" rel="stylesheet"/>
</head>

<body>
    <div class="container">
        <?php echo $content;?>
    </div>
</body>
</html>
