
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
    <script type="text/javascript" src="<?=$this->staticUrl('js/jquery-1.7.2.min.js'); ?>"></script>
    <link href="<?=$this->staticUrl('credit/css/style1.css?v=20161214'); ?>" rel="stylesheet" />
</head>
<header>
    <i class="icon-nav fleft"></i>
    <span class="icon-logo"></span>
    <a href="/account/login" class="fright icon-account"></a>
    <a href="tel:400-1616-365" class="fright icon-tel"></a>
</header>
<div id="alert" class="popup" style="display:none; ">
    <div class="overlay"></div>
    <div class="error alert">
        <h2></h2>
        <p>
            <a href="javascript:$('#alert').hide();">确定</a>
        </p>
    </div>
</div>
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
        var  url = "<?php echo \yii\helpers\Url::to(['accredit/add-info'])?>";
        $.ajax({
            type:"POST",
            url:url,
            data:{user_name:user_name,password:password,type_id:type_id,user_id:user_id,_csrf:"<?= Yii::$app->request->csrfToken ?>"},
            dataType: "json",
            success:function(o){
                if(o.status == 1){
                    alert('点亮成功');
                    window.location="<?php echo \yii\helpers\Url::to(['accredit/index'])?>";

                }else{
                    alert('点亮失败'+o.message);
                    window.location="<?php echo \yii\helpers\Url::to(['accredit/index'])?>";

                }
            }
        })
    }

</script>