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
    <title><?php echo APP_NAMES;?></title>
    <meta name="format-detection" content="telephone=no">
    <link href="<?=$this->staticUrl('credit/css/style1.css?v=20161214'); ?>" rel="stylesheet" />

</head>
<style>
    .container{
        padding: 0 0 0;
        text-align: center;
    }
</style>
<body>

<a href="<?php echo Url::to(['accredit/login','id'=>1,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['sy_status']) && $info['sy_status'] == 1):?>

                <img src="<?=$this->staticUrl('credit/img/shanyin-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>
            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/shanyin-2.png'); ?>">
            <?php endif?>
            <p> 闪银</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>2,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['xyqb_status']) && $info['xyqb_status'] == 1):?>
                <img src="<?=$this->staticUrl('credit/img/xyqb-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/xyqb-2.png'); ?>">
            <?php endif?>
            <p> 信用钱包</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>3,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['ppd_status']) && $info['ppd_status'] == 1):?>

                <img src="<?=$this->staticUrl('credit/img/ppdai-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/ppdai-2.png'); ?>">
            <?php endif?>
            <p> 拍拍贷</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>4,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['yqb_status']) && $info['yqb_status'] == 1):?>
                <img src="<?=$this->staticUrl('credit/img/yqb-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/yqb-2.png'); ?>">
            <?php endif?>
            <p> 用钱宝</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>5,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['sjd_status']) && $info['sjd_status'] == 1):?>
                <img src="<?=$this->staticUrl('credit/img/sjdai-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/sjdai-2.png'); ?>">
            <?php endif?>
            <p> 手机贷</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>6,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['xjbs_status']) && $info['xjbs_status'] == 1):?>
                <img src="<?=$this->staticUrl('credit/img/xjbs-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/xjbs-2.png'); ?>">
            <?php endif?>
            <p> 现金巴士</p>
        </div>


    </li></a>
<a href="<?php echo Url::to(['accredit/login','id'=>7,'user_id'=>$user_id]) ; ?>"> <li class="containe">
        <div>
            <?php if(isset($info['dkw_status']) && $info['dkw_status'] == 1):?>
                <img src="<?=$this->staticUrl('credit/img/2345-1.png'); ?>">
                <div class="success"><img src="<?=$this->staticUrl('credit/img/success.png'); ?>"></div>

            <?php else:?>
                <img src="<?=$this->staticUrl('credit/img/2345-2.png'); ?>">
            <?php endif?>
            <p> 2345贷款王</p>
        </div>


    </li></a>


</body>