<?php
use yii\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title>工资卡认证</title>
    <meta name="format-detection" content="telephone=no">

    <link href="<?=$this->staticUrl('credit/css/bank_info.css?v=20170116'); ?>" rel="stylesheet" />
</head>
<style>
    .last_num{
        float: right;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        margin-right: 3vh;
        font-size: 2.5vh;

    }
    .button{
        display: block;
        width: 90%;
        height: 6vh;
        margin: 0 auto;
        background-color: #1ec8e1
        color: #fff;
        line-height: 6vh;
        border-radius: 3vh;
        font-size: 2.5vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 5vh;
    }
</style>

<body style="background-color: #F2F2F2">
<div class="flag"></div>
<ul>
    <?php foreach($data as $value):?>
    <li class="bank_list" style="background-color: #fff">
        <img style="width: 4vh;" src="<?=$this->staticUrl('image/bank1/bank'.$value['bank_id'].'.png'); ?>"><span><?php echo $value['bank_name'];?></span>
        <div class="last_num">已认证</div>
    </li>
    <?php endforeach?>
</ul>
<a class="button" style="color: #fff" href="<?php echo Url::to(['payroll-card/index'])?>">添加</a>


</body>
