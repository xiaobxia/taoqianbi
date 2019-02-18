<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $cardInfo \common\models\CardInfo */
/* @var $order \common\models\UserLoanOrder */
$this->title = '确认银行卡';
?>
<style>
    body {
        background: #f2f2f2;
    }
</style>
<div class="confirm-bankcard">
    <div class="head">
        <h3>银行卡待确认</h3>
        <h4>请在一小时内确认银行卡信息，超时系统将自动确认</h4>
    </div>
    <div class="content">
        <h3>姓名：<?= $order->loanPerson->name ?></h3>
        <h3 class="right"><?= $cardInfo->bank_name ?>：<?= substr($cardInfo->card_no, 0, 3) ?> **** **** <?= substr($cardInfo->card_no, 11) ?></h3>
        <p>放款成功还差一步！请填写 <?= substr($cardInfo->phone, 0, 3) ?>****<?= substr($cardInfo->phone, 7) ?>收到的验证码</p>
    </div>
    <form>
        <div class="code">
            <input name="code" type="text" value="" id="code" placeholder="请输入短信验证码" />
            <button type="button">获取验证码</button>
            <p style="display: none;" id="code_tips">
                <span>请输入正确的验证码</span>
                <a href="#" class="right btn-skip-confirm" style="display: none">跳过验证</a>
            </p>
        </div>
        <a class="button" href="javascript:;" id="submit">确认</a>
    </form>
</div>

<script>
    var MsgControl = {
        errCount:0,
        showSimpleSuccess:function(msg) {
            $('#code_tips').css({'display':'block'}).find('span').css({'color':'green'}).html(msg);
        },
        showSimpleError:function(msg) {
            this.errCount++;
            $('#code_tips').css({'display':'block'}).find('span').html(msg).css({'color':'red'});
            if(this.errCount>=4) {
                 $('#code_tips').find('a').show();
            }
        },
        hideSimple:function() {
            $('#code_tips').css({'display':'none'}).find('span').html('');
        }
    };
    var timeoutEvent,seriNo,code;
    var seriNo = <?=$seri_no?'"'.$seri_no.'"':'null'?>;
    function signSuccess() {
        window.location.href= '<?=(YII_ENV==='prod'?'//m'.APP_DOMAIN : '').Url::to(['fund-sign-status','key'=>$key])?>';
    }
    
    $(function () {
        var codeButton = $("input[name='code']+button");
        var submitBtn = $('#submit');
        var skipBtn = $('#btn_skip_confirm');
        
        var setTime = function (m, l) {
            timeoutEvent = setTimeout(function () {
                if (m) {
                    codeButton.attr("disabled", "disabled");
                    codeButton.html(m + "s后重新发送");
                    setTime(--m, 1000);
                } else {
                    codeButton.html("重新获取");
                    codeButton.removeAttr("disabled");
                }
            }, l);
        }

        codeButton.click(function () {
            MsgControl.hideSimple();
            $.ajax({
                url: '<?=YII_ENV==='prod'?'//api'.APP_DOMAIN.'/loan/fund-pre-sign': str_replace('/mobile/','/frontend/',Url::to(['loan/fund-pre-sign']))?>',
                data:{
                    key:'<?=$key?>',
                },
                success:function(res) {
                    if(res.code===0) {
                        if(res.data.sign) {
                            //已经签约过了 跳到签约成功的页面
                            signSuccess();
                        } else {
                            MsgControl.showSimpleSuccess('发送成功');
                            seriNo = res.data.serialNo;
                            submitBtn.removeClass('disabled');
                        }
                    } else {
                        MsgControl.showSimpleError(res.message);
                        clearTimeout(timeoutEvent);
                        setTime(0);
                    }
                },
                error:function() {
                    MsgControl.showSimpleError('抱歉，发送失败，请重试');
                    clearTimeout(timeoutEvent);
                    setTime(0);
                },
                dataType:'json',
                type:'GET'
            });
            setTime(120);
        });
        
        submitBtn.click(function() {
            code = $('#code').val();
            if(!code) {
                MsgControl.showSimpleError('请先输入验证码');
                return false;
            } else if(submitBtn.hasClass('disabled')) {
                return false;
            }
            
            submitBtn.html('正在提交').addClass('disabled');
            $.ajax({
                url: '<?=YII_ENV==='prod'?'//api'.APP_DOMAIN.'/loan/fund-sign-confirm': str_replace('/mobile/','/frontend/',Url::to(['loan/fund-sign-confirm']))?>',
                data:{
                    key:'<?=$key?>',
                    seri_no:seriNo,
                    captcha:code
                },
                success:function(res) {
                    if(res.code===0 || res.code===<?=\common\base\ErrCode::ORDER_FUND_ACCOUNT_SIGNED?>) {
                        signSuccess();
                    } else {
                        MsgControl.showSimpleError(res.message);
                    }
                    submitBtn.html('提交').removeClass('disabled');
                },
                error:function() {
                    MsgControl.showSimpleError('抱歉，提交失败，请重试');
                    submitBtn.html('提交').removeClass('disabled');
                },
                dataType:'json',
                type:'GET'
            });
        });
        
        $('#code_tips').on('click', '.btn-skip-confirm', function(e){
            var btnHtml = $(this).html();
            var skipMsg = '跳过验证';
            var loadingMsg = '正在跳过验证...';
            var that = $(this);
            if(btnHtml!==loadingMsg) {
                $(this).html(loadingMsg);
                $.ajax({
                    url:'<?=YII_ENV==='prod'?'//api'.APP_DOMAIN.'/loan/skip-fund-sign': str_replace('/mobile/','/frontend/',Url::to(['loan/skip-fund-sign']))?>',
                    data:{
                        key:'<?=$key?>'
                    },
                    dataType:'json',
                    type:'GET',
                    success:function(r) {
                        if(r.code===0) {
                            //跳过成功 刷新页面
                            window.location.reload();
                        } else {
                            MsgControl.showSimpleError(r.message);
                            that.html('跳过验证');
                        }
                    },
                    error:function() {
                        MsgControl.showSimpleError('网络请求出错了,请重试');
                        that.html(skipMsg);
                    }
                });
            };
        });
        
        if(!seriNo) {
            codeButton.click();
        } else {
            MsgControl.hideSimple();
            setTime(120);
        }
    });
//    $(function () {
//    var dpr = lib.flexible.dpr;
//            new Spinner({color:'#fff', width:3 * dpr, radius:11 * dpr, length:8 * dpr}).spin(document.getElementById('preview'));
//            document.getElementById('loading').style.display = 'none';
//            var caption_count;
//            var caption_intval;
//            captionCountDown();
//            //验证码计时
//                    function captionCountDown(){
//                    caption_count = 150;
//                            caption_intval = window.setInterval(function(){
//                            if (caption_count > 1){
//                            caption_count -= 1;
//                                    $('#resend').html('还需' + caption_count + '秒');
//                            } else{
//                            window.clearInterval(caption_intval);
//                                    $('#resend').html('重新发送').css('background-color', '#61cae4');
//                            }
//                            }, 1000);
//                    }
//            $('#resend').click(function(e){
//            if (caption_count > 1){
//            return false;
//            }
//            window.location.reload();
//            });
//                    $('#submit').click(function(){
//            var code = $.trim($('#code').val());
//            console.log(code);
//            if(!code){
//                $('#error').html('请输入验证码');
//                return false;
//            }
//            var url = '<?php echo Url::to(['loan/confirm-charge']); ?>';
//            $.ajax({
//                url : url,
//                type : 'get',
//                data : {"id":"<?= $order->id; ?>","code":code},
//                dataType : 'json',
//                success : function(data){
//                    if(data.code == 0){
//                        $('#loading').show();
//                        jumpTo('<?php echo $result_url; ?>');
//                    }else if(data.message){
//                        $('#error').html(data.message);
//                    }
//                },
//                fail:function(){
//                }
//            });
//            return false;
//        });
//    });


</script>