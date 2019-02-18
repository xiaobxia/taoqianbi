<?php
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title><?php echo $bank_name?></title>
    <meta name="format-detection" content="telephone=no">
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery-1.7.2.min.js'); ?>"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/style.css'); ?>?v=2016121214"/>

</head>


<style>
    .bank_list {
        list-style: none;
        border-bottom: 1px solid #E6E6E6;
        height: 9vh;
        width: 100%;
        line-height: 9vh;
        background-color: #fff;
    }

    .bank_list img {
        list-style: none;
        margin-left: 2vh;
        margin-right: 2vh;
        margin-bottom: 1.2vh;
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

        margin-bottom: 0.5vh;
        width: 18em;
    }

    .button {
        width: 80%;
        height: 6vh;
        margin: 0 auto;
        background-color: #1782e0;
        color: #fff;
        line-height: 6vh;
        border-radius: 3vh;
        font-size: 2.5vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 2vh
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
        width: 20%;
        margin-left: 40%;
        text-align: center;
        font-size: 2.3vh;
        color: #fff;
        position: absolute;
        top: 40%;
        display: none;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;

    }

    .proccess img {
        margin-bottom: 1.5vh;
    }

    #maskLayer {
        width: 100%;
        height: 750px;
        background-color: #000000;
        opacity: 0.5;
        -moz-opacity: 0.5;
        display: none;
        filter: alpha(opacity=50);
        position: absolute
    }
    #alert.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    #alert1.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert1.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert1.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    #alert2.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert2.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert2.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    #alert3.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert3.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert3.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    #alert4.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert4.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert4.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    #alert5.popup .error { -webkit-transform: translate3d(0, 0, 0);
        background: #f2f2f2;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        width: 10.4666666667rem;
        height: 4rem;
        position: fixed; top: 50%;
        left: 50%;
        margin: -3.08rem -4.2333333333rem;
        padding: 0.4rem 0;
        line-height: 3em;
        text-align: center;
        border-radius: 0.08rem; }

    #alert5.popup .error h2 {
        font-size: 2vh;
        color: #333;
        height: 1.6rem;
        padding: 0 0.4rem; }

    #alert.popup .error a {
        display: inline-block;
        width: 49%;
        text-decoration: none;
        font-size: 0.4266666667rem;
        color: #1782e0; }
    .popup .overlay { width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); position: fixed; top: 0; }

    .popup .overlay .tips-msg { color: #fff; font-size: 0.3466666667rem; width: 5.3333333333rem; position: absolute; top: 50%; left: 50%; text-align: center; margin: 1.3333333333rem 0 0 -2.6666666667rem; }

    .popup .spin { position: fixed; left: 50%; top: 50%; color: #fff; float: left; width: 2.9333333333rem; height: 2.9333333333rem; margin-left: -1.4666666667rem; margin-top: -1.4666666667rem; -webkit-border-radius: 10px; -moz-border-radius: 10px; border-radius: 10px; -webkit-transform: translate3d(0, 0, 0); }

    .popup .dialog { -webkit-transform: translate3d(0, 0, 0); background: #f2f2f2; width: 8.4666666667rem; height: 6.16rem; position: fixed; top: 50%; left: 50%; margin: -3.08rem -4.2333333333rem; padding: 0.4rem 0; line-height: 3em; text-align: center; border-radius: 0.08rem; }

    .popup .dialog input { position: absolute; top: -26.6666666667rem; }

    .popup .dialog span.close { display: inline-block; position: absolute; right: 0.2666666667rem; top: 0.5333333333rem; width: 0.6666666667rem; height: 0.6666666667rem; cursor: pointer; }

    .popup .dialog span.close:before, .popup .dialog span.close:after { content: ''; position: absolute; height: 2px; width: 100%; top: 50%; left: 0; margin-top: -1px; background: #666; }

    .popup .dialog span.close:before { -webkit-transform: rotate(45deg); -moz-transform: rotate(45deg); -ms-transform: rotate(45deg); -o-transform: rotate(45deg); transform: rotate(45deg); }

    .popup .dialog span.close:after { -webkit-transform: rotate(-45deg); -moz-transform: rotate(-45deg); -ms-transform: rotate(-45deg); -o-transform: rotate(-45deg); transform: rotate(-45deg); }

    .popup .dialog h1 { font-size: 0.6666666667rem; color: #333; padding-bottom: 0.2666666667rem; }

    .popup .dialog h2 { font-size: 0.48rem; }

    .popup .dialog p { border-radius: 0.1333333333rem; padding: 0 0.5333333333rem; }

    .popup .dialog p i { display: block; float: left; width: 1.2266666667rem; height: 1.28rem; background: #fff; border-top: 1px solid #e6e6e6; border-bottom: 1px solid #e6e6e6; border-right: 1px solid #e6e6e6; position: relative; }

    .popup .dialog p i:first-child { border-left: 1px solid #e6e6e6; border-top-left-radius: 0.1333333333rem; border-bottom-left-radius: 0.1333333333rem; }

    .popup .dialog p i:last-child { border-top-right-radius: 0.1333333333rem; border-bottom-right-radius: 0.1333333333rem; }

    .popup .dialog p i.point:after { content: ''; background: #333; display: block; width: 0.2666666667rem; height: 0.2666666667rem; border-radius: 0.1333333333rem; position: absolute; top: 0.5066666667rem; left: 0.4666666667rem; }

    .popup .dialog .error-tips { font-size: 0.3733333333rem; line-height: 1em; padding-top: 0.2666666667rem; color: #ff8003; }

    .popup .dialog a { font-size: 0.3466666667rem; color: #1782e0; text-decoration: none; display: block; width: 100%; }

    .popup .pay { height: 5.2rem; margin-top: -2.6rem; }

    .popup .pay h2 { border-bottom: 1px solid #e6e6e6; line-height: 2em; margin-bottom: 0.5333333333rem; }

    .popup .error { -webkit-transform: translate3d(0, 0, 0); background: #f2f2f2; width: 8.4666666667rem; height: 5.3333333333rem; position: fixed; top: 0; bottom: 0; left: 0; right: 0; margin: auto; text-align: center; border-radius: 0.08rem; padding-top: 1rem; }

    .popup .error img { display: block; width: 1rem; height: 1.0266666667rem; margin: 0 auto 0.4666666667rem; }

    .popup .error h2 { font-size: 0.6266666667rem; color: #333; height: 1.6rem; padding: 0 0.4rem; }

    .popup .error p { border-top: 1px solid #e6e6e6;  position: absolute; bottom: 0; left: 0; width: 100%; }

    .popup .error p a { display: inline-block; width: 49%; text-decoration: none; font-size: 0.4266666667rem; color: #1782e0; }

    .popup .error p a:nth-child(2) { border-left: 1px solid #e6e6e6; }

    .popup .alert { height: 4rem; }

</style>
<div id="maskLayer"></div>
<div class="proccess" id="loading">
    <div class="spin" id="preview"></div>
    <p style="color: #fff; position: relative; z-index: 22;">校验中...</p>
</div>
<body style="background-color:#F2F2F2">
<div class="flag"></div>
<form action="<?php echo Url::toRoute(['payroll-card/request']) ?>" id="form1" method="post">
    <ul class="card_verify">

        <li class="bank_list">
            <div class="type_head">卡类型：储蓄卡</div>
        </li>

        <li class="bank_list" id="user_type" >
            <input style="position: absolute;z-index:11;background-color: #fff;width: 70%;height: 6vh;margin-left: 14vh;border: none;font-size: 2.5vh;margin-top: 1.5vh;padding-right: 2vh;text-align: left; border-radius: 0; -webkit-appearance: none; " type="button" onclick="typeShow1();" id="heihei" value="请选择">
            <div class="type_head">账号类型：&nbsp;&nbsp;&nbsp;
        </li>
        <?php foreach($types as $type):?>
            <li class="bank_list" id="user_name<?php echo $type['entry_id']?>" style="display: none">
                <span class="type_head"><?php echo $type['user_label']?></span>：&nbsp;&nbsp;&nbsp;<input class="select user_name_select" type="text" value="" placeholder="<?php echo $type['user_desc']?>">
            </li>
        <?php endforeach ?>
        <?php foreach($types as $type):?>
            <li class="bank_list" style="display: none" id="bank_list<?php echo $type['entry_id']?>">
                <div class="type_head"><?php echo $type['label']?>：&nbsp;&nbsp;&nbsp;<input class="select password_select"  type="password"  value="" placeholder="<?php echo $type['desc'];?>"></div>
            </li>
        <?php endforeach ?>

        <li class="bank_list" id="verify_pic" style="display: none">
            <div class="type_head">验证码：&nbsp;&nbsp;&nbsp;<input class="select" type="text" id="verify_Pic"
                                                                style="width: 60%" value=""><img id="images" src="">
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
        <label for="checkbox">我已阅读并同意<i></i><a href="<?php echo Url::toRoute(['payroll-card/authority']) ?>">《授权书》</a></label>
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
<input type="hidden" name="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">
<div class="card_type" style="display: none">
    <li style="border-bottom: 1px solid #FAFAFA;" onclick="change(1);">储蓄卡</li>
    <li onclick="change(2);">信用卡</li>
</div>
<div class="user_name_type" style="display: none">
    <?php foreach ($types as $type): ?>
        <li id="login_type" style="border-bottom: 1px solid #FAFAFA;"
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
    });

    function typeShow() {
        $('.card_type').show();
    }
    function typeShow1() {
        $('.user_name_type').show();
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
    function submitRequest() {
        if(!$('#button').hasClass('button')) {
            return false;
        }

        var bank_type = $('#bank_type').val();
        var user_id = $('#user_id').val();
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
                url: "<?php echo Url::toRoute(['payroll-card/request']) ?>",
                data: {
                    user_id: user_id,
                    bank_type: bank_type,
                    bank_id: bank_id,
                    bank_name: bank_name,
                    type: type,
                    user_name: user_name,
                    password: password,
                    entry_id: entry_id,
                    _csrf: "<?php echo Yii::$app->request->csrfToken ?>"
                },
                dataType: "json",
                success: function (o) {

                    if (o.open_id) {
                        $('#open_id').val(o.open_id);
                    }
                    if(o.status == 50 || o.status == 200){
                        $('#error').html(o.msg);
                        $('#error').show();
                        window._hmt && window._hmt.push(['_trackEvent', o.msg, 'click', 'step1Error']);
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::toRoute(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: o.msg, type: '1'},
                            dataType: "json",
                            success: function () {}
                        });
                        return false;
                    }else if("undefined" == typeof o.data){
                        $('#error').html('用户名或密码错误');
                        $('#error').show();
                        window._hmt && window._hmt.push(['_trackEvent', '用户名或密码错误', 'click', 'step1Error']);
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::toRoute(['payroll-card/get-process-message']) ?>",
                            data: {user_id: user_id, message: '用户名或密码错误', type: '1'},
                            dataType: "json",
                            success: function () {}
                        });
                        return false;
                    }else {
                        window._hmt && window._hmt.push(['_trackEvent', '认证成功', 'click', 'step1Success']);
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::toRoute(['payroll-card/get-process-message']) ?>",
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
                            window.location.href = '<?php echo Url::toRoute(['payroll-card/my-card']) ?>';
                        }
                    }
                }
            })
        }


    }


    function changeType(id) {
        $('#entry_id').val(id);
        $('.user_name_type').hide();
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
            async: true,
            url: "<?php echo Url::toRoute(['payroll-card/send-code']) ?>",
            data: {code: code, user_id: user_id,open_id:open_id, _csrf: "<?php echo Yii::$app->request->csrfToken?>"},
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
            beforeSend: function () {
                $('#loading').show();
                $('#maskLayer').show();
            },
            complete: function () {
                $('#loading').hide();
                $('#maskLayer').hide();
            },
            url: "<?php echo Url::toRoute(['payroll-card/get-state']) ?>",
            data: {user_id: user_id,open_id:open_id ,_csrf: "<?php echo Yii::$app->request->csrfToken?>"},
            dataType: "json",
            success: function (o) {
                if (o.status == 10) {
                    window._hmt && window._hmt.push(['_trackEvent', '认证成功', 'click', 'step2Success']);
                    $.ajax({
                        type: "POST",
                        async: true,
                        url: "<?php echo Url::toRoute(['payroll-card/get-process-message']) ?>",
                        data: {user_id: user_id, message: '认证成功', type: '2'},
                        dataType: "json",
                        success: function () {}
                    });
                    $('#alert3').show();
                    setTimeout(function(){
                        $('#alert3').hide();
                        window.location.href = '<?php echo Url::toRoute(['payroll-card/my-card']) ?>';
                    },1200);
                } else {
                    if(o.status==100){
                        $('#error').html(o.msg);
                        $('#error').show();
                        window._hmt && window._hmt.push(['_trackEvent', o.msg, 'click', 'step2Error']);
                        $.ajax({
                            type: "POST",
                            async: true,
                            url: "<?php echo Url::toRoute(['payroll-card/get-process-message']) ?>",
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