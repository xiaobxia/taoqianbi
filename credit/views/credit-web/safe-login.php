<?php

use yii\helpers\Url;
?>
<div class="safe-login">
    <div class="header">
        <h3>前程数据</h3>
        <h4>为您提供安全可信赖的数据加密服务</h4>
    </div>
    <h3 class="h3">使用前程数据账号登录并授权</h3>
    <form action="">
        <div class="get-message">
            <label>获得您的个人信息</label>
            <label>获得您的紧急联系人信息</label>
            <label>获得您的收款银行卡信息</label>
            <label>获得您的芝麻信用信息</label>
            <label>获得您的手机运营商信息</label>
        </div>
        <a href="javascript:void(0);" class="button" id="zjmobliestart" target="_blank">登录并授权</a>
        <p class="clearfix other">
            <input id="checkbox" name="" type="checkbox" value="" checked="false"/>
            <label for="checkbox">我已阅读并同意<i></i><a href="<?php echo Url::to(['credit-web/safe-login-txt']); ?>">《信息授权及使用协议》</a></label>
        </p>
    </form>
</div>
<script>

    $(function () {
        var checkbox = $("input[type='checkbox']");
        checkbox.click(function () {
            var flag = checkbox.is(":checked");

            if (flag === true) {
                $('#zjmobliestart').css('background-color', '#1ec8e1');
                $('#zjmobliestart').attr("href", "javascript:void(0);").attr("target", "_blank");
            } else {
                $("#zjmobliestart").css('background-color', '#eee');
                $("#zjmobliestart").attr("href", "#").attr("target", "");
            }
        });

        $("#zjmobliestart").on("click",function(e){
            e.stopPropagation();
            var flag = checkbox.is(":checked");
            if (flag === true) {
                nativeMethod.returnNativeMethod('<?= $data ?>');
            }
        })

    })
</script>
