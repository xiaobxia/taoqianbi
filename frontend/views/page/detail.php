<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#7CD88E">
	<title><?php echo $this->title ? $this->title : ''; ?></title>
	<script src="https://api.koudailc.com/js/mobile.js?v=20160082301"></script>
    <script src="https://api.koudailc.com/js/wx-activity/zepto.min.js?v=2015061001"></script>
	<link rel="stylesheet" type="text/css" href="https://api.koudailc.com/css/general.css?v=20160090801">
<style type="text/css">
body {
    font-size: 16px;
}
html, body, div, span, h1, h2, h3, p, em, img, dl, dt, dd, ol, ul, li, table, tr,th,td, form,input,select {
    margin:0;
    padding:0;
}
body {
    min-width:320px;
    max-width:480px;
    min-height:100%;
    margin:0 auto;
}
.bg{
    display: none;
    /*position: absolute;*/
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background-color: rgba(0,0,0,.7);
}
.bg img{
    display: block;
    position: absolute;
    width: 65%;
    top:5px;
    height: auto;
    float: left;
    left: 25%;
}
</style>
</head>
<body>
    <div class="container">
    <?php echo $html; ?>
    </div>
</body>
</html>


