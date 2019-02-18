<?php

use yii\helpers\Url;
use common\models\LoanPerson;
switch ($source_type){
    case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        $color = "#6a4dfc";
        break;
    case LoanPerson::PERSON_SOURCE_HBJB;
        $color = "#ff6462";
        break;
    case LoanPerson::PERSON_SOURCE_WZD_LOAN;
        $color = "#d74a55";
        break;
}
?>
<style type="text/css">
    body{
        background:#f5f5f7;
    }
    .tied-card{
        width: 100%;
    }
    .tips{
        color: #adadad;
    }
    .tied-card ul {
        padding: 0;
    }
    .tied-card ul li{
        padding: 0 0.4rem;
    }
    .common-list li:first-child {
        /*border-top: 1px solid #dcdbdf;*/
    }
    .common-list li {
        height: 1.4666666667rem;
        line-height: 1.4666666667rem;
        border-bottom: 1px solid #dcdbdf;
        padding: 0 0.4rem;
        font-size: 0.4266666667rem;
        color: #333;
    }
    #error{
        line-height: 1em;
    }
    .tied-card ul li label{
        font-weight: normal;
    }
    ._btn{
        background: <?=$color;?>;
        text-align: center;
        margin: 0 auto;
        width: 87%;
        color: #fff;
        text-decoration: none;
        padding: .7em 0;
        margin-top: 1.5em;
        border-radius: 5px;
        -moz-border-radius: 5px 5px 5px 5px;
        -webkit-border-radius: 5px;
    }
    .tied-card ul li.code a {
        color: <?=$color;?>;
        text-decoration: none;
        font-size: 0.4rem;
        text-align: right;
        border-left: 1px solid #e6e6e6;
        height: 0.9333333333rem;
        display: inline-block;
        line-height: 0.9333333333rem;
        padding-left: 0.4rem;
    }
</style>
<div class="tied-card">
    <div>
        <p class="tips">请填写银行卡信息</p>
        <ul class="common-list">
            <li>
                <label>持卡人</label>
                <span><?= $name ?></span>
            </li>
            <li>
                <label>选择银行</label>
                <select name="bank_id" id="bank_id" >
                    <option value ="0">请选择银行</option>
                    <?php foreach ($card_list as $val) { ?>
                        <option value ="<?= $val['bank_id'] ?>"><?= $val['bank_name'] ?></option>
                    <?php } ?>
                </select>
            </li>
            <li>
                <label>银行卡号</label>
                <span><input placeholder="请输入银行卡号" id="card_no" name="card_no" type="number" value="" pattern="\d*"/></span>
            </li>
            <li>
                <label>手机号</label>
                <span><input placeholder="请输入银行预留手机号" id="phone" name="phone" type="number" value="" pattern="\d*"/></span>
            </li>
            <li class="code">
                <label>验证码</label>
                <span><input placeholder="请输入验证码" id="code" name="code" type="text" value=""/><a id="resend" href="javascript:getCode()">点击获取</a></span>
            </li>

        </ul>
        <p class="error" id="error">&nbsp;&nbsp;</p>
        <!--<p class="info" style="display:none;" id="bank_error">由于邮政储蓄银行不支持还款代扣，建议优先选择其他银行卡。</p>-->
        <div id="sumbit" class="_btn" onclick="bindCard()"><?=$source == 'add-card' ? '完成绑卡' : '确定绑卡'?></div>
        <!-- <a id="sumbit" href="javascript:bindCard()"></a> -->
        <!--        <p class="clearfix other">
                    <input id="checkbox" name="" type="checkbox" value="" checked="true" />
                    <label for="checkbox">我已阅读并同意<i></i><a href="">《极速荷包还款代扣协议》</a></label>
                </p>-->
        <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
    </div>
</div>

<script>
    var caption_count;
    var caption_intval;
    function bindCard() {
        var checkbox = $("input[type='checkbox']");
        checkbox.click(function (e) {
            var flag = checkbox.is(":checked");
            console.log(flag);
            if (flag === true) {
                $('.sumbit-button').css('background-color', '#6a4dfc');
                $('.sumbit-button').attr("href", "#");
            } else {
                $(".sumbit-button").css('background-color', '#eee');
                $(".sumbit-button").attr("href", "javascript:void(0);");
            }
        });

        if ($('#sumbit').hasClass('disabled-button')) {
            return false;
        }
        if (!checkParam(1)) {
            return false;
        }
        var params = {
            phone: $('#phone').val(),
            code: $('#code').val(),
            card_no: $('#card_no').val(),
            bank_id: $('#bank_id').val()
        };
        <?php if($source == 'add-card'){ ?>
        var bind_url = '<?= Url::toRoute(['loan/do-add-card']) ?>';
        <?php }else{?>
        var bind_url = '<?= Url::toRoute(['loan/do-bind-card']) ?>';
        <?php } ?>
        $.post(bind_url, params, function (data) {
            if (data.code != 0 && data.message) {
                showError(data.message);
                return false;
            } else {
                <?php if ($source == 'xjk-shandai') { ?>
                jumpTo('<?= Url::toRoute(['shandai/update?source_order_id=' . $source_id]) ?>');
                <?php }else if ($source == 'add-card') { ?>
                jumpTo("<?= Url::toRoute(['loan/loan-repayment-type','id'=> $source_id,'type'=> 1],true) ?>");
                <?php } else { ?>
                jumpTo('<?= Url::toRoute(['loan/card-list']) ?>');
                <?php } ?>
            }
        });
    }

    function getCode() {
        if (caption_intval) {
            return false;
        }
        if (!checkParam()) {
            return false;
        }
        captionCountDown();
        var card_nos = $('#card_no').val();
        var bank_ids =  $('#bank_id').val();
        $.post('<?= Url::toRoute(['loan/get-code']) ?>', {phone: $('#phone').val(), source: '<?= $source == 'xjk-shandai' ? 'xjk' : '' ?>',card_no:card_nos,bank_id:bank_ids}, function (data) {
            if (data.code != 0 && data.message) {
                showError(data.message);
                return false;
            }
            $('#sumbit').removeClass('disabled-button');
        });
    }
    function isCardNoScoped(num){
        var reg = /^(\d{16,})$/;
        return reg.test(num);
    }
    function checkParam(type) {
        if (!isCardNoScoped($('#card_no').val())) {
            showError('银行卡号只能15位以上数字');
            return false;
        }
        if (!isPhone($('#phone').val())) {
            showError('手机号只能11位数字');
            return false;
        }
        if ($('#bank_id').val() <= 0) {
            showError('请选项银行');
            return false;
        }
        if (type) {
            if (!$('#code').val()) {
                showError('请输入验证码');
                return false;
            }
        }
        showError('');
        return true;
    }
    function showError(msg) {
        var $p = $('#error').html(msg);
        (!!msg) ? $p.show() : $p.hide();
    }
    //验证码计时
    function captionCountDown() {
        caption_count = 60;
        caption_intval = window.setInterval(function () {
            if (caption_count > 1) {
                caption_count -= 1;
                $('#resend').html('还需' + caption_count + '秒');
            } else {
                window.clearInterval(caption_intval);
                caption_intval = null;
                $('#resend').html('重新发送');
            }
        }, 1000);
    }

    $('#bank_id').change(function (e) {
        var bank_value = document.getElementById("bank_id");
        var index = bank_value.selectedIndex;
        if (bank_value.options[index].value == 4) {
//            document.getElementById("bank_error").style.display = 'block';
            alert("由于邮政储蓄银行不支持还款代扣，建议优先选择其他银行卡。");
        } else {
//            document.getElementById("bank_error").style.display = 'none';
        }
    })
</script>
