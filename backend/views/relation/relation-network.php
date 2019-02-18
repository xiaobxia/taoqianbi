<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

?>

<link rel="stylesheet" href="<?php echo Url::toStatic('/bootstrap/css/bootstrap.min.css'); ?>">
<style type="text/css">
    .input-group .span-left {
        width: 10%;
        min-width: 90px;
        color: #333;
        background-color: white;
    }
    .bottom {
        margin-top: 40px;
        width: 1100px;
    }
    .bottom button {
        width: 275px;
        height: 50px;
        margin-top: 20px;
        margin-left: 420px;
    }

    .bottom .input-group {
        width: 600px;
        height: 34px;
        padding-top: 20px;
        margin-left: 250px;
    }
</style>

<div class="bottom">
    <div class="input-group">
        <span class="input-group-addon span-left">用户id</span>
        <input id="user_id" type="text" class="form-control">
    </div>
    <div class="input-group">
        <span class="input-group-addon span-left">关系权重</span>
        <input id="weight" type="text" class="form-control">
    </div>

    <button type="button" class="btn btn-info">查看用户关系网</button>
</div>




<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script src="<?php echo Url::toStatic('/bootstrap/js/bootstrap.min.js'); ?>"></script>

<script type="text/javascript">

    $(function(){
        $('.bottom button').click(function(){
            check();
        });
    });

    function check(){
        var user_id = $('#user_id').val();
        var weight = $('#weight').val();
        if(user_id == "" || user_id == null || isNaN(user_id)){
            alert('用户id为空，请输入用户id')
        } else if(weight == "" || weight == null || isNaN(weight)){
            alert('关系权重为空，请输入关系权重')
        }else{
            location.href = <?php echo '"' . urldecode(Url::toRoute(['relation/check', 'user_id' => '" + user_id + "', 'weight' => '" + weight'])); ?>;
        }
    }

</script>