<?php
use yii\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title>工资卡认证</title>
    <meta name="format-detection" content="telephone=no">
    <script type="text/javascript" src="<?=$this->staticUrl('js/jquery-1.7.2.min.js'); ?>"></script>
    <script type="text/javascript" src="<?=$this->staticUrl('js/spin.js'); ?>"></script>
    <meta name="viewport" content="initial-scale=1.0, minimum-scale=1.0, maximum-scale=2.0, user-scalable=no, width=device-width">
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


<style>
    @keyframes disappear {
        0% {
            opacity: 1;
        }
        100% {
            opacity: 0;
        }
    }

    @-webkit-keyframes disappear {
        0% {
            opacity: 1;
        }
        100% {
            opacity: 0;
        }
    }

    @keyframes popupIn {
        0% {
            transform: translateY(100%);
        }
        100% {
        transform: translateY(0%);
      }
    }

    @-webkit-keyframes popupIn {
      0% {
        -webkit-transform: translateY(100%);
      }
      100% {
        -webkit-transform: translateY(0%);
      }
    }

    .popup-in {
        -webkit-animation: popupIn 0.2s ease-out forwards;
        animation: popupIn 0.2s ease-out forwards;
    }

    .bank_list {
        list-style: none;
        border-bottom: 1px solid #E6E6E6;
        height: 9vh;
        width: 100%;
        line-height: 9vh;
        background-color: #fff;
        position: relative;
    }

    .bank_list img {
        float: right;
        list-style: none;
        margin-right: 2vh;
        margin-top: 2vh;
    }

    .flag {
        height: 2vh;
        background-color: #F2F2F2;
    }

    .card_type {
        background-color: #fff;
        width: 100%;
        height: 18vh;
        position: absolute;
        bottom: 0px;
    }

    .type_head {
        margin-left: 2vh;
        font-size: 2.5vh;
        color: #3A3A3A;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    }

    .card_type li {
        list-style: none;
        height: 9vh;
        width: 100%;
        text-align: center;
        line-height: 9vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        font-size: 2.5vh;

    }

    .user_name_type {
        background-color: #fff;
        width: 100%;
        position: absolute;
        bottom: 0px;
        z-index: 30;
    }

    .user_name_type li {
        list-style: none;
        height: 9vh;
        width: 100%;
        text-align: center;
        line-height: 9vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        font-size: 2.5vh;

    }

    .container {
        padding: 0 0 0;

    }

    .select {
        border: none;
        background-color: #fff;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        font-size: 2.3vh;
        width: 60%;
        margin-bottom: 0.5vh;
    }

    a.a-button {
        cursor: default;
    }

    .button {
        width: 90%;
        height: 6vh;
        margin: 0 auto;
        background-color: #1ec8e1
        color: #fff;
        line-height: 6vh;
        border-radius: 2vh;
        font-size: 2.7vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 2vh;
    }
    div.disabled {
        width: 90%;
        height: 6vh;
        margin: 0 auto;
        background-color: #bbbbbb;
        color: #fff;
        line-height: 6vh;
        border-radius: 2vh;
        font-size: 2.7vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 2vh;
        cursor: default;
    }

    .agreement {
        width: 50%;
        margin-left: 25%;
        font-size: 2vh;
        color: #666;
        margin-top: 2vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    }

    .tip {
        height: 6vh;
        margin: 0 auto;

        line-height: 6vh;

        font-size: 2.5vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 2vh
    }

    #verify {
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        font-size: 2vh;
        width: 12vh;
        height: 4vh;
    }

    .proccess {
        width: 53%;
        background: rgba(0, 0, 0, 0.7);
        left: 50%;
        border-radius: 1vh;
        margin-left: -26.5%;
        margin-top: -18%;
        padding-top: 23%;
        text-align: center;
        font-size: 2.3vh;
        color: #fff;
        position: fixed;
        top: 50%;
        display: none;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        z-index: 20;
    }

    .proccess img {
        margin-bottom: 1.5vh;
    }

    .clearfix:after {
        content: " ";
        display: block;
        clear: both;
    }

    p.other {
        margin-left: 5%;
        padding: 3% 0 0 0;
        font-size: 2vh;
    }

    p.other input {
        display: none;
    }

    p.other input[type='checkbox']:checked + label i {
        display: block;
    }

    p.other label {
        display: block;
        float: left;
        padding-left: 5%;
        position: relative;
    }

    p.other label:before {
        content: '';
        display: block;
        width: 2vh;
        height: 2vh;
        background: #d9dbdb;
        position: absolute;
        left: 0;
        top: 50%;
        margin-top: -1vh;
        border-radius: 0.2vh;
    }

    p.other label i {
        width: 2vh;
        height: 2vh;
        position: absolute;
        left: 0; top: 50%;
        margin-top: -1vh;
        background: url(../credit/img/safe-icon-yes.png) 0 0 no-repeat;
        background-size: 2vh 2vh;
        display: none;
    }

    p.other label a {
        text-decoration: none;
        color: #1ec8e1
    }

    p.error {
        color: #ff8003;
        font-size: 2vh;
        margin-left: 5%;
        padding: 2% 0;
    }

    #maskLayer {
        width: 100%;
        height: 750px;
        display: none;
        position: absolute;
        z-index: 12;
    }

    .popup {
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        position: absolute;
        bottom: 10%;
        color: #fff;
        background: rgba(51, 51, 51, 0.7);
        font-size: 2.4vh;
        width: 28vh;
        height: 5vh;
        line-height: 5vh;
        border-radius: 2.6vh;
        text-align: center;
        left: 50%;
        margin-left: -14vh;
        letter-spacing: 2px;
        animation: disappear 1s linear 1s forwards;
        -webkit-animation: disappear 1s linear 1s forwards;
        z-index: 13;
    }

    .spin {
        position: fixed;
        left: 50%;
        top: 47%;
        color: #fff;
        float: left;
        width: 16vh;
        height: 16vh;
        -webkit-border-radius: 10px;
        -moz-border-radius: 10px;
        border-radius: 10px;
        -webkit-transform: translate3d(0, 0, 0);
        z-index: 25;
    }

    #user_type:before {
        content: '';
        display: block;
        position: absolute;
        top: 3.6vh;
        right: 2.4vh;
        width: 1.8vh;
        height: 1.8vh;
        border-top: 1px solid #999;
        border-right: 1px solid #999;
        -webkit-transform: rotate(45deg);
        transform: rotate(45deg);
        z-index: 10;
    }

</style>
<div id="maskLayer"></div>
<div class="proccess" id="loading">
    <div class="spin" id="preview"></div>
    <p id="loading-text" style="color: #fff; position: relative; padding-bottom: 2%; z-index: 22;">校验中,请耐心等待...</p>
</div>
<body style="background-color:#F2F2F2">
<div class="flag"></div>
<form action="<?php echo Url::to(['payroll-card/request']) ?>" id="form1" method="post">
    <ul class="card_verify">

        <li class="bank_list">
            <div class="type_head">所属银行：<?php echo $bank_name ?></div>
        </li>

        <li class="bank_list">
            <div class="type_head">卡类型：储蓄卡</div>
        </li>

        <li class="bank_list" id="user_type" onclick="typeShow1();">
            <input style="position: absolute;z-index:11;background-color: #fff;width: 60%;height: 6vh;margin-left: 14vh;border: none;font-size: 2.5vh;margin-top: 1.5vh;padding-right: 2vh;text-align: left; border-radius: 0; -webkit-appearance: none; " type="button" id="heihei" value="请选择">
            <div class="type_head">账号类型：&nbsp;&nbsp;&nbsp;
        </li>
        <?php foreach($types as $k=> $type):?>
        <li class="bank_list" id="user_name<?php echo $type['entry_id']?>" style="display: none">
            <span class="type_head"><?php echo $type['user_label']?></span>：&nbsp;&nbsp;&nbsp;<input class="select user_name_select" type="text" value="" placeholder="<?php echo $type['user_desc']?>">
            <input type="hidden" name="user_name_select_hidden" value="<?php echo $type['login_valid'] ?>" id="user_name_select_hidden<?php echo $type['entry_id']?>">
        </li>
        <?php endforeach ?>
        <?php foreach($types as $type):?>
            <li class="bank_list" style="display: none" id="bank_list<?php echo $type['entry_id']?>">
                <div class="type_head"><?php echo $type['label']?>：&nbsp;&nbsp;&nbsp;<input class="select password_select"  type="password"  value="" placeholder="<?php echo $type['desc'];?>"></div>
                <input type="hidden" name="password_select_hidden" value="<?php echo $type['password_valid'] ?>" id="password_select_hidden<?php echo $type['entry_id']?>">
            </li>
        <?php endforeach ?>

        <li class="bank_list" id="verify_pic" style="display: none">
            <div class="type_head clearfix">验证码：&nbsp;&nbsp;&nbsp;<input class="select" type="text" id="verify_Pic"
                                                                style="width: 25%" value=""><img id="images" src="">
            </div>
        </li>
    </ul>

    <p class="error" id="error" style="display: none;"></p>

    <a href="javascript:void(0)" class="a-button">
        <div class="button" id="button" onclick="submitRequest()">
            下一步
        </div>
    </a>

    <p class="clearfix other">
        <input id="checkbox" name="" type="checkbox" value="" checked="true">
        <label for="checkbox">我已阅读并同意<i></i><a href="<?php echo Url::to(['payroll-card/authority']) ?>">《授权书》</a></label>
    </p>
</form>
<div id="alert" class="popup" style="display:none;">
    <span>请选择卡片类型</span>
</div>
<div id="alert1" class="popup" style="display:none;">
    <span>请选择账号类型</span>
</div>
<div id="alert2" class="popup" style="display:none;">
    <span>密码不能为空</span>
</div>
<div id="alert3" class="popup" style="display:none;">
    <span>认证成功</span>
</div>
<div id="alert4" class="popup" style="display:none;">
    <span>用户名不能为空</span>
</div>
<div id="alert5" class="popup" style="display:none;">
    <span>采集成功</span>
</div>
<div class="popup" id="alert6" style="display: none">
    <span>请阅读并同意授权书</span>
</div>
<div class="popup" id="alert7" style="display: none">
    <span>请输入验证码</span>
</div>
<input type="hidden" name="entry_id" value="" id="entry_id">
<input type="hidden" name="bank_type" value="<?php echo $flag ?>" id="bank_type">
<input type="hidden" name="user_id" value="<?php echo $user_id ?>" id="user_id">
<input type="hidden" name="open_id" value="" id="open_id">
<input type="hidden" name="bank_id" value="<?php echo $bank_id ?>" id="bank_id">
<input type="hidden" name="bank_name" value="<?php echo $bank_name ?>" id="bank_name">
<input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
<div class="card_type" style="display: none">
    <li style="border-bottom: 1px solid #FAFAFA;" onclick="change(1);">储蓄卡</li>
    <li onclick="change(2);">信用卡</li>
</div>
<div class="user_name_type" style="display: none">
    <?php foreach ($types as $type): ?>
        <li id="login_type" style="border-bottom: 1px solid #E6E6E6;"
            onclick="changeType(<?php echo $type['entry_id'] ?>);"><?php echo $type['name'] ?></li>
    <?php endforeach ?>
</div>


</body>
<script type="text/javascript">

    $(function() {
        new Spinner({color:'#fff',width:3,radius:11,length:8}).spin(document.getElementById('preview'));

        var checkbox = $("input[type='checkbox']");
        checkbox.click(function() {
            var flag = checkbox.is(":checked");
            if (!flag) {
                $('#button').removeClass("button").addClass("disabled");
            } else {
                $('#button').removeClass("disabled").addClass("button");
            }
        });

        $('#maskLayer').click(function() {
            $('#maskLayer').hide();
            $('.user_name_type').hide();
            $('.user_name_type').removeClass('popup-in');
        });
    });

    function typeShow() {
        $('.card_type').show();
    }
    function typeShow1() {
        $('#maskLayer').show();
        $('.user_name_type').show();
        $('.user_name_type').addClass('popup-in');
        if(!$('#login_type').html()){
            $('#error').html('该银行暂不可用');
            $('#error').show();
            return false;
        }
    }
    function change(type) {
        if (type == 1) {
            $('.card_type').hide();
            $('#select').val('储蓄卡');

        } else if (type == 2) {
            $('.card_type').hide();
            $('#select').val('信用卡');
        }
    }

    function setTime(m, l) {
        setTimeout(function() {
            var text = "请在刷新页面后重新操作<br>页面将在" + m + "秒后自动刷新";
            $('#loading-text').html(text);
            if(m) {
                setTime(--m, 1000);
            } else {
                window.location.reload();
            }
        }, l);
    }

    function submitRequest() {
        if(!$('#button').hasClass('button')) {
            return false;
        }

        $('#error').hide();

        var bank_type = $('#bank_type').val();
        var user_id = $('#user_id').val();
        var login_valid = new RegExp($('#user_name_select_hidden' + $('#entry_id').val()).val());
        var password_valid = new RegExp($('#password_select_hidden'+ $('#entry_id').val()).val());
        if (bank_type) {
            if($('#select').val() == '储蓄卡'){
                var type = 1;
            }else if($('#select').val() == '信用卡'){
                var type = 2;
            }
        }
        var temp = $('#open_id').val();
        var entry_id= $('#entry_id').val();
        if(!entry_id){
            $('#alert1').show();
            setTimeout(function(){
                $('#alert1').hide();
            },2000);
            return false;
        }
        if (temp) {
            sendCode(temp);
        } else {
            var entry_id = $('#entry_id').val();
            var user_name = $('#user_name' + entry_id + " .user_name_select").val();
            if (!user_name) {
                $('#alert4').show();
                setTimeout(function(){
                    $('#alert4').hide();
                },2000);
                return false;
            }

            if(!(eval(login_valid).test(user_name))) {
                $('#error').html("用户名格式不正确");
                $('#error').show();
                return false;
            }

            var bank_id = $('#bank_id').val();
            var bank_name = $('#bank_name').val();
            var password = $('#bank_list' + entry_id + " .password_select").val();
            if (!password) {
                $('#alert2').show();
                setTimeout(function(){
                    $('#alert2').hide();
                },2000);
                return false;
            }

            if(!(eval(password_valid).test(password))) {
                $('#error').html("密码格式不正确");
                $('#error').show();
                return false;
            }

            $.ajax({
                type: "POST",
                async: true,
                beforeSend: function () {
                    $('#loading').show();
                    $('#maskLayer').show();
                },
                complete: function () {
                    $('#loading').hide();
                    $('#maskLayer').hide();
                },
                url: "<?php echo Url::to(['payroll-card/request']) ?>",
                data: {
                    user_id: user_id,
                    bank_type: bank_type,
                    bank_id: bank_id,
                    bank_name: bank_name,
                    type: type,
                    user_name: user_name,
                    password: password,
                    entry_id: entry_id,
                    _csrf: "<?= Yii::$app->request->csrfToken ?>"
                },
                dataType: "json",
                success: function (o) {
                    if (o.open_id) {
                        $('#open_id').val(o.open_id);
                    }
                    if(o.status == 50 || o.status == 200){
                        $('#error').html(o.msg);
                        $('#error').show();
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: o.msg, type: '1'},
                            dataType: "json",
                            success: function () {}
                        });
                        return false;
                    }else if("undefined" == typeof o.data){
                        $('#error').html(o.msg);
                        $('#error').show();
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: o.msg, type: '1'},
                            dataType: "json",
                            success: function () {}
                        });
                        return false;
                    }else {
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: '认证成功', type: '1'},
                            dataType: "json",
                            success: function () {}
                        });
                        if (o.data.state == 2) {
                            $('#verify_pic').show();
                            $("#button").html("确认提交");
                        } else if (o.data.state == 4) {
                            $('#verify_pic').show();
                            $('#images').attr('src', 'data:image/png;base64,' + o.data.pic_captcha);
                            $("#button").html("确认提交");
                        } else if (o.data.state == 6) {
                            $('#verify_pic').show();
                            $("#button").html("确认提交");
                        } else if(o.data.state == 10) {
                            $('#alert3').show();
                            setTimeout(function(){
                                $('#alert3').hide();
                            },1200);
                            window.location.href = '<?php echo Url::to(['payroll-card/my-card']) ?>';
                        }
                    }
                }
            })
        }


    }


    function changeType(id) {
        $('#entry_id').val(id);
        $('#maskLayer').hide();
        $('.user_name_type').hide();
        $('.user_name_type').removeClass('popup-in');
        var user_name = "user_name" + id;
        var text = $('#'+user_name).find(".type_head").text();
        $("#user_type").find("input").attr("value", text);
        $("li[id^='bank_list']").hide();
        $("li[id^='user_name']").hide();
        $("#bank_list"+id).show();
        $("#user_name"+id).show();
        $("#button").html("提&nbsp&nbsp&nbsp交");
    }
    function sendCode(temp) {
        var open_id = temp;

        if (!$('#images').attr('src')) {
            $('#images').hide();
        }
        if(!$('#verify_Pic').val()){
            $('#alert7').show();
            setTimeout(function(){
                $('#alert7').hide();
            },2000);
            return false;
        }
        var user_id = $('#user_id').val();
        var code = $('#verify_Pic').val();
        $.ajax({
            type: "POST",
            beforeSend: function () {
                $('#loading').show();
                $('#maskLayer').show();
            },
            async: true,
            url: "<?php echo Url::to(['payroll-card/send-code']) ?>",
            data: {code: code, user_id: user_id,open_id:open_id, _csrf: "<?= Yii::$app->request->csrfToken?>"},
            dataType: "json",
            success: function (o) {
                getState(open_id);
            }
        })
    }

    function getState(open_id) {
        var user_id = $('#user_id').val();
        $.ajax({
            type: 'POST',
            url: "<?php echo Url::to(['payroll-card/get-state']) ?>",
            data: {user_id: user_id,open_id:open_id ,_csrf: "<?= Yii::$app->request->csrfToken?>"},
            dataType: "json",
            success: function (o) {
                $('#loading').hide();
                $('#maskLayer').hide();
                if (o.status == 10) {
                    $.ajax({
                        type: "POST",
                        async: true,
                        url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
                        data: {user_id: user_id, message: '认证成功', type: '2'},
                        dataType: "json",
                        success: function () {}
                    });
                    $('#alert3').show();
                    setTimeout(function(){
                        $('#alert3').hide();
                        window.location.href = '<?php echo Url::to(['payroll-card/my-card']) ?>';
                    },1200);
                } else {
                    if(o.status==100){
                        $('#error').html(o.msg);
                        $('#error').show();
                        setTimeout(function() {
                            $('#maskLayer').show();
                            $('#loading').show();
                            setTime(3);
                        },100);
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::to(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: o.msg, type: '2'},
                            dataType: "json",
                            success: function () {}
                        });
                        return false;
                    }else {
                        setTimeout(function () {
                            getState(open_id);
                        }, 3000);
                    }
                }

            }
        });
    }
</script>
