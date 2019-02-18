<?php
use yii\helpers\Url;
use newh5\components\ApiUrl;
?>
<style type="text/css">
.container{background:#63228F;}
#app_reg_wraper {background:#63228F;}

#app_reg_wraper ._input{padding: 2.8px 0;background:#7B44A0;width: 90%;margin-bottom: 11.2px; position: relative;}

#app_reg_wraper ._input1{width: 90%;margin-bottom: 7px;background:#63228F; }

#app_reg_wraper .input2{padding: 1.4px;margin-bottom: 11.2px;width: 53%;-webkit-border-radius: 5px;background:#7B44A0;float: left}

#app_reg_wraper .input2 input{font-family: "Microsoft YaHei";}

#app_reg_wraper ._input + ._input{margin-bottom:22.4px;}

#app_reg_wraper ._btn{padding:9.8px 0;background:#ff613c;width:90%;border-radius: 8px;}

#app_reg_wraper ._input input{margin-left:14px;background:#ffffff;height:21px;line-height:21px;padding:7px 0;color:#999999;font-family: "Microsoft YaHei";}

#app_reg_wraper .input2 input{margin-left:14px;background:#ffffff;height:21px;line-height:21px;padding:7px 0;color:#999999;font-family: "Microsoft YaHei";}

#app_reg_wraper #phone{width:80%;}

#app_reg_wraper #code{width:80%;}

.input3{border: 0px solid red;width: 40%;background:#ff613c;padding:9.8px 0;-webkit-border-radius: 5px;float: right;color: #ffffff;text-align: center}

#app_reg_wraper  .in-mask{width: 94%;padding-bottom: 21px;margin: 0 auto;border-radius: 5px;}

#app_reg_wraper .info{position: relative;margin: 12.6px auto;padding: 15px 0; width: 100%;height: 100px;overflow: hidden;background-color: rgba(255,255,255,0.16);border-radius: 7px;top: 23.8px;}
#app_reg_wraper .info p{margin-left:0;width: 97%;position: relative;color: #fff;font-size: 14px;padding-bottom: 17px;white-space: nowrap;overflow:hidden;text-overflow: ellipsis;}

#app_reg_wraper .info p span{ margin-left: 10px;}

._input .close{display：none;top: 50%;right: 10.5px;margin-top: -8.4px;width: 15.4px;height: 15.4px;z-index: 99;background: url("<?= $this->absBaseUrl;?>/image/page/content_icon_delect@2x.png") no-repeat center center;background-size:100%;}
#app_reg_wraper .p_relative input{background:#7B44A0;}

#app_reg_wraper .info .sq-line{background:#7B44A0;position: absolute;left: 12px;top:8px;z-index: 99;}

#app_reg_wraper .info .sq-line .sq1,#app_reg_wraper .info .sq-line .sq2,#app_reg_wraper .info .sq-line .sq3{width: 5px;height: 5px;position: relative;top: 0px;border-radius: 50%;background-color: yellow;margin: 0 auto;}

#app_reg_wraper .info .sq-line .line1,#app_reg_wraper .info .sq-line .line2{width: 1px; height: 32px;position: relative;background-color: yellow;margin: 0 auto;}

#app_reg_wraper .info .screen{position: absolute;left: 8px;width: 100%;overflow: hidden;padding-left: 10px;}

#app_reg_wraper .input4{background-color: #63228F;}

#app_reg_wraper .input4 img{float: left;margin-left: 23.8px;}

#app_reg_wraper .input4 input {float: left;width: 50%;background: #7B44A0;height: 21px;line-height: 21px;padding: 7px 0 7px 14px;color: #999999;font-family: "Microsoft YaHei";border-radius: 5px;}

#app_reg_wraper .footer{padding:33px 0;text-align:center;color:rgba(255,255,255,.5);font-size: 12px;}

.hide{display:none;}
/*新增-------------*/
#app_reg_wraper .visib{width: 97%;height: 100px;overflow: hidden;position: relative;}

#app_reg_wraper .deal{color:#fff;padding-left: 20px;font-size: 12px;background: url('<?= $this->absBaseUrl;?>/image/page/select_v2.png') no-repeat 0 2px/15px 15px;}

#app_reg_wraper .deal a{color: #F77A29;}

</style>
<div id="app_reg_wraper">
    <img class="bg" width="100%" src="<?= $this->absBaseUrl;?>/image/page/banner@2x.png">
    <div class="in-mask">
            <div class="info">
                    <div class="visib">
                        <div class="sq-line">
                            <div class="sq1"></div>
                            <div class="line1"></div>
                            <div class="sq2"></div>
                            <div class="line2"></div>
                            <div class="sq3"></div>
                        </div>
                        <div class="screen">
                           <div class="one"></div>
                           <div class="two"></div>
                        </div>
                    </div> 
            </div>
    </div>
    <div class="p_relative">
        <div class="_input m_center _b_radius_round">
            <input class="em_1" type="text" id="phone" value="<?=$source_token?>" maxlength="11" placeholder="请输入手机号码" onfocus="getFocusNum()"/>
            <i class="close"></i>
        </div>
        <div class="_input1 m_center _b_radius_round">
            <div class="input2 ">
                <input class="em_1" type="text" id="code" maxlength="6" placeholder="请输入验证码" onfocus="getFocusReg()"/>
            </div>
            <div class="input3">
                <span class="a_right get_code" id="action" onclick="getCode();">获取验证码</span>
            </div>
            <div class="clear"></div>
            <div class="input4 hide">
                <input class="em_1" type="text"  maxlength="6" placeholder="请输入图片验证码" onfocus="getFocusReg2()"/>
                <img onclick="refreshCaptcha();" title="点击刷新验证码" src="<?php echo Url::toRoute(['page/captcha', 'v' => uniqid()]); ?>" id="loginform-verifycode-image">
                <div class="clear"></div>
            </div>
            <div class="deal">注册即表示同意<a href="http://qbcredit.wzdai.com/credit-web/safe-login-txt?id=122">《<?php echo APP_NAMES; ?>用户使用协议》</a></div>
        </div>
        <div class="_btn m_center a_center fff _b_radius_rounds" onclick="register();">闪电兑换</div>
    </div>
    <div class="footer">
        <p>@ 2011-2017 <?php echo COMPANY_NAME;?></p>
        <p><?php echo SITE_ICP;?></p>
    </div>
</div>
<script type="text/javascript">
// $(".input4.hide").removeClass("hide");

//获取数据 渲染info内容
$.ajax({
    url:"<?= ApiUrl::toRouteCredit(["credit-app/user-multi-message"]); ?>",
    type:"get",
    success:function(data){
        if(data&&data.code==0){
              var render="";
                $.each(data.message,function(i,v){
                    render +='<p><span>'+v+'</span></p>';  
                })
                $(".info .screen .one").html(render); 
                $(".info .screen .two").html(render); 
            }
    }
})

webVisitStat();
function getCode(){
    //手机正则匹配
    var reg = /^1[3|4|5|7|8][0-9]{9}$/; //验证规则
    var phone= $("#phone").val();//手机号码
    var flag = reg.test(phone); //true
    if(!flag){
        return showExDialog('手机号码格式不正确','确定');
    }
    if(!isPhone(phone)){
        return showExDialog('手机号码格式不正确','确定');
    }
    var url = "<?= ApiUrl::toRoute(['xqb-user/reg-get-code'],true);?>";
    $.post(url, {phone:phone,source:window.source_tag}, function(data){
        console.log(data);
        if(data && data.code == 0){
            getCodeCountDown('获取验证码','倒计时num秒');
        }else if(data && data.code == 1000){
            <?php if($this->isFromWeichat()):?>
            jumpTo(createUrl('<?= Url::toRoute(["page/download-app"],true); ?>',{source:window.source_tag}));
            <?php else:?>
            showExDialog('预约成功','下载APP','downLoad(this)');
            <?php endif;?>
        }else if(data.message){
            showExDialog(data.message,'确定');
        }
    },'json');
}

function register() {
    var phone = $("#phone").val();
    var code = $.trim($('#code').val());
    
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确','确定');
    }
    if (!code) {
        return showExDialog('请输入验证码','确定');
    }
    var openod = $("#openid").val();
    var url = '<?= ApiUrl::toRoute([ 'xqb-user/register', 'appMarket' => \yii::$app->request->get('source_tag', 'NoneAppMarket') ], true); ?>';
    url = url.replace('NoneAppMarket', window.source_tag);
    $.post(url, {
        phone: phone,
        code: code,
        source: window.source_tag

    }, function(data) {
        if (data && data.code == 0) {
            <?php if ($this->isFromWeichat() ):?>
            jumpTo(createUrl('<?= Url::toRoute(["page/download-app"], true); ?>', {source : window.source_tag}));
            <?php else : ?>
            showExDialog('预约成功','下载APP','downLoad(this)');
            <?php endif;?>
        }
        else if (data.message) {
            showExDialog(data.message, '确定');
        }
    }, 'json');
}
// 获取焦点后，外边框出现
function getFocusNum() {
    $("._input").css("border","1px solid #FF8020");
    $(".input2").css("border","none");
    $(".input4 input").css("border","none");
}
function getFocusReg() {
    $(".input2").css("border","1px solid #FF8020");
    $("._input").css("border","none");
    $(".input4 input").css("border","none");
}
function getFocusReg2() {
    $(".input4 input").css("border","1px solid #FF8020");
    $("._input").css("border","none");
    $(".input2").css("border","none");
}
//点击关闭按钮，清除phone栏
$(".close").on("click",function () {
    $("#phone").val("");
    $(".close").css("display","none");
})
$("#phone").on("keyup",function () {
    if($("#phone").val()){
        $(".close").css({"position":"absolute","display":"block"});
    }
   
})

//新闻向上无缝滚动
setInterval('wordSlideshow(".screen",".screen .one")',2000);//定时器调用wordSlideshow

/**
 *文字无缝轮播
 *param moveObj  为需要移动的对象
 *param child    为moveObj下的任意一个子元素
*/
function wordSlideshow(moveObj) {
        var scrH=$(moveObj).height();//moveObj的高度
        var step=-(scrH/2)/$(".screen").children("div:first").children().length;//速度step为每行文字行高
        var scrT=document.getElementsByClassName('screen')[0].offsetTop;
        if(-scrH/2 >= scrT){//当moveObj坐标的绝对值 大于等于 moveObj高度的一半时，设置moveObj坐标为0
            $(moveObj).css('top','0px')
            scrT=0
        }
            scrT=scrT+step;//moveObj坐标每次移动scrT
            $(moveObj).animate({top:scrT+'px'}) 
    }

//验证码
function refreshCaptcha() {
    $.ajax({
        url: '<?= Url::toRoute(['page/captcha', 'refresh' => 1]); ?>',
        dataType: 'json',
        success: function(data) {
            $('#loginform-verifycode-image').attr('src', data.url);
        }
    });
}
/**
 * 显示遮罩层
 * @params content 
 * @params btn1 
 * @params func1 默认 hideExDialog 
 * @params btn2
 * @params func2 默认 hideExDialog
 * @params pTop 顶部距离 默认 15%
 * @params cName 默认 ex_dialog
*/
function showExDialog(content,btn1,func1,btn2,func2,pTop,cName){
    func1 = func1 || 'hideExDialog';
    func2 = func2 || 'hideExDialog';
    func1 = func1.indexOf('(') > 0 ? func1+';' : func1+'();';
    func2 = func2.indexOf('(') > 0 ? func2+';' : func2+'();';
    pTop = pTop || '15%';
    cName = cName || 'ex_dialog';
    hideExDialog(cName);
    showMask(cName);
    var dialogWraper = document.createElement('div');
    dialogWraper.className = cName;
    dialogWraper.id = 'dialog-wraper';
    dialogWraper.style.cssText = "width: 80%;max-width: 380px;margin: auto;";
    document.body.appendChild(dialogWraper);
    var str = '';
    str += '<div style="position: fixed;z-index:2;top:'+pTop+';width:80%;max-width:380px;background-color:#fff;border-radius:12px;-moz-border-radius:12px 12px 12px 12px;-webkit-border-radius:12px;">';
    str += '<div class="_head" style="text-align: center;font-size: 18px;font-family:PingFang-SC-Medium;color:#999;padding: 17px 0 11px;border-radius: 12px 12px 0 0;-webkit-border-radius:12px 12px 0 0;border-bottom: 1px solid #ccc;">温馨提示</div><div class="_content" style="text-align:center;letter-spacing:1px;padding: 22px 0;font-size: 18px;font-family:PingFang-SC-Medium;color:#333;">'+content+'</div>';
    if(arguments[1]) str += '<div class="_btn" style="display:inline-block;width: '+((arguments[1] && arguments[3]) ? '35%' : '100%')+';padding: 2.5% 0;background-color: #FF613C;color: #fff;text-align: center;border-radius: 0px 0px 12px 12px;-moz-border-radius: 0px 0px 12px 12px;-webkit-border-radius: 0px 0px 12px 12px;font-size: 20px;font-family:PingFang-SC-Medium;color: #fff;" onclick="'+func1+'">'+btn1+'</div>';
    if(arguments[3]) str += '<div class="_btn" style="display:inline-block;width: 35%;padding: 2.5% 0;margin-left: 10%;margin-bottom: 4%;background-color: #ccc;color: #fff;text-align: center;border-radius: 4px;-moz-border-radius: 4px 4px 4px 4px;-webkit-border-radius: 4px;" onclick="'+func2+'">'+btn2+'</div>';
    str += '</div>';
    ID('dialog-wraper').innerHTML = str;
}
</script>