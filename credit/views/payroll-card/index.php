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
    <script type="text/javascript" src="<?=$this->staticUrl('js/jquery-1.7.2.min.js'); ?>"></script>
    <link href="<?=$this->staticUrl('credit/css/bank_info.css?v=20170116'); ?>" rel="stylesheet" />
    <script>
        var _hmt = _hmt || [];
        (function() {
            var hm = document.createElement("script");
            hm.src = "https://hm.baidu.com/hm.js?3ac5a6a835b4ee96a11d699ee4f6b39a";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(hm, s);
        })();
    </script>
</head>

<body>
<div class="flag"></div>
<ul>
    <?php if($bank_info):?>
    <?php foreach($bank_info as $k=> $value):?>
            <a onclick="Statistics('<?php echo $value['bank_name'];?>', <?php echo $user_id;?>)" href="<?php echo Url::to(['payroll-card/apply','item'=>$value,'user_id'=>$user_id,'bank_id'=>$k,'bank_name'=>$value['bank_name']]);?>">
        <li class="bank_list">

            <img src="<?=$this->staticUrl('image/bank1/bank_'.$k.'.png'); ?>"><span><?php echo $value['bank_name'];?></span>
        </li></a>
        <?php endforeach ?>
    <?php endif;?>
</ul>

<script>
    var Statistics = function(bank, user_id) {
        $.ajax({
            type: "POST",
            async: true,
            url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
            data: {user_id: user_id, message: bank, type: '3'},
            dataType: "json",
            success: function () {}
        });
    }
</script>
</body>