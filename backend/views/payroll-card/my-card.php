<?php
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title><?php echo APP_NAMES;?></title>
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/style.css'); ?>?v=2016121214" />

</head>
<style>
    .bank_list{list-style: none;
        border-bottom: 1px solid #E6E6E6;
        height: 9vh;
        width: 100%;
        line-height: 9vh;
        background-color: #fff;
    }
    .bank_list img{list-style: none;
        margin-left: 2vh;
        margin-right: 2vh;
        margin-bottom: 1.2vh;
    }
    .bank_list span{list-style: none;
        font-size: 2.5vh;
        color: #3A3A3A;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    }
    .flag{height: 2vh;
        background-color:#F2F2F2 ;
    }
    .container{
        padding: 0 0 0;

    }
    .last_num{
        float: right;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        margin-right: 3vh;
        font-size: 2.5vh;

    }
    .button{width: 80%;
        height: 6vh;
        margin: 0 auto;
        background-color: #1782e0;
        color: #fff;
        line-height: 6vh;
        border-radius: 3vh;
        font-size: 2.5vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 5vh}
</style>

<body style="background-color: #F2F2F2">
<div class="flag"></div>
<ul>
    <?php foreach($data as $value):?>
    <li class="bank_list">
        <img style=" width: 4vh;" src="<?php echo Url::toStatic('/image/bank/bank' . $value['bank_id'] . '.png'); ?>"><span><?php echo $value['bank_name'];?></span>
        <div class="last_num">已添加</div>
    </li>
    <?php endforeach?>

</ul>
<div class="button">
    <a style="color: #fff" href="<?php echo Url::toRoute(['payroll-card/index'])?>">添加</a>
</div>


</body>