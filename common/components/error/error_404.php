<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 Error</title>
    <style type="text/css">
        #error{
            width: 747px;
            height: 747px;
            text-align: center;
            overflow: hidden;
            margin: 0 auto;
            background: url(<?php echo $this->hostInfo.$this->baseUrl.'/image/404.png'; ?>) no-repeat center center/cover;
        }
        .error_type{
            color:#30B4D8;
            margin-top: 0px;
            font-size: 224px;
            line-height: 450px;
        }
        .error_type:first-letter{
            color:#096B8E;
        }
        .tips{
            padding-top:120px;
            font-size: 20px;
        }
        .btn{
            display: block;
            text-decoration: none;
            width:108px;
            height:34px;
            line-height: 34px;
            background:rgba(153,108,51,1);
            border-radius: 5px;
            font-size: 15px;
            color: #fff;
            margin:0 auto;
        }
    </style>
</head>

<body>
<div id="error">
    <p class="error_type">404</p>
    <p class="tips">糟糕！页面丢失了......</p>
    <a href="#" class="btn">返回首页</a>
</div>
</body>
</html>