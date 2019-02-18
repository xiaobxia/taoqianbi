
<!doctype html>
<html>
<head>
    <style>
        .container{
            padding: 0 0 0;
            text-align: center;
        }
    </style>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title><?php echo APP_NAMES;?></title>
    <meta name="format-detection" content="telephone=no">
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery-1.7.2.min.js'); ?>"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/style.css'); ?>?v=2016121214" />
</head>
<header>
    <i class="icon-nav fleft"></i>
    <span class="icon-logo"></span>
    <a href="/account/login" class="fright icon-account"></a>
    <a href="tel:400-1616-365" class="fright icon-tel"></a>
</header>

<div id="body">
    <div class="section-login">
        <span class="title">用户登录</span>
        <input type="hidden" id="redirect_url" >
        <input type="hidden" id="user_id"  value="<?php echo $user_id;?>">

        <div class="mobileno">
            <span>账号：</span><input type="number" name="user_name" id="user_name" placeholder="请输入账号">

        </div>
        <div class="verifycode">
            <span>密码：</span><input type="text" name="password" id="password" placeholder="请输入密码" >
        </div>
        <button id="btnLogin" onclick="callback()"  class="disabled"><span>登录</span></button>
    </div>
</div>
<script type="text/javascript">
    function callback(){
        var type_id = "<?php echo $id ;?>";
        var user_name = $('#user_name').val();
        var user_id = $('#user_id').val();
        var password = $('#password').val();
        var  url = "<?php echo \yii\helpers\Url::toRoute(['accredit/add-info'])?>";
        $.ajax({
            type:"POST",
            url:url,
            data:{user_name:user_name,password:password,type_id:type_id,user_id:user_id,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"},
            dataType: "json",
            success:function(o){
                if(o.status == 1){
                    alert('点亮成功');
                    window.location="<?php echo \yii\helpers\Url::toRoute(['accredit/index'])?>";

                }else{
                    alert('点亮失败'+o.message);
                    window.location="<?php echo \yii\helpers\Url::toRoute(['accredit/index'])?>";

                }
            }
        })
    }

</script>