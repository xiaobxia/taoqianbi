<?php
use newh5\components\ApiUrl;
use common\models\LoanPerson;
?>

<style type="text/css">
<?php if ($source != LoanPerson::USER_AGENT_XYBT) : ?>
    .bg_61cae4 {
        background: #<?= $color?>;
    }
    #bank_card_action_wraper{min-height:100%;background:#f5f5f7;padding-bottom: 0.2rem;}
    #bank_card_action_wraper #title{padding-top:1.8em;padding-bottom:.5em;}
    #bank_card_action_wraper .column{border-top:1px solid #dcdbdf;border-bottom:1px solid #dcdbdf;}
    #bank_card_action_wraper .column > .padding{padding-right:0;}
    #bank_card_action_wraper ._table{border-collapse:collapse;}
    #bank_card_action_wraper ._table td{padding-top:.8em;padding-bottom:.8em;border-top:1px solid #dcdbdf;}
    #bank_card_action_wraper ._table td.padding{padding-left:0;}
    #bank_card_action_wraper ._table tr:first-child td{border-top:0 none;}
    #bank_card_action_wraper ._table td input{width:100%;height:25px;line-height:25px;}
    #bank_card_action_wraper .btn{width:87%;height: 1.2rem;line-height: 1.2rem; font-size: 1.35em; margin-top:2em;}
    #bank_card_action_wraper .hsm{width: 5rem;margin: 0.6rem auto;height: 0.613333rem;line-height: 0.613333rem;padding-left: 1rem;color: <?= $color;?>;box-sizing:border-box;font-size: 1.24em;background: url(<?= $this->absBaseUrl;?>/image/common/<?= $img;?>?V=01) no-repeat 0.133333rem 0/0.506667rem 0.533333rem;}
    #bank_card_action_wraper ._select{width:auto;margin-bottom:0;}
    #bank_card_action_wraper #code{width:60%;}
    #bank_card_action_wraper .get_code{display:block;width:20%;height:25px;line-height:25px;border:1px solid <?= $color?>;font-size: inherit; background-color: transparent;}
<?php elseif ($source == LoanPerson::USER_AGENT_XYBT) : ?>
    /*新增B 仅修改border颜色*/
    #bank_card_action_wraper .get_code {display: block; width: 20%; height: 25px;line-height: 25px;border: 1px solid #ff6462;font-size: inherit; background-color: transparent;}
    /*新增E*/

    .bg_61cae4 {
        background:url("<?= $this->absBaseUrl;?>/image/app-page/anniu@2x.png");
    }
    #bank_card_action_wraper{min-height:100%;background:#f5f5f7;}
    #bank_card_action_wraper #title{padding-top:1.8em;padding-bottom:.5em;}
    #bank_card_action_wraper .column{border-top:1px solid #dcdbdf;border-bottom:1px solid #dcdbdf;}
    #bank_card_action_wraper .column > .padding{padding-right:0;}
    #bank_card_action_wraper ._table{border-collapse:collapse;}
    #bank_card_action_wraper ._table td{padding-top:.8em;padding-bottom:.8em;border-top:1px solid #dcdbdf;}
    #bank_card_action_wraper ._table td.padding{padding-left:0;}
    #bank_card_action_wraper ._table tr:first-child td{border-top:0 none;}
    #bank_card_action_wraper ._table td input{width:100%;height:25px;line-height:25px;}
    #bank_card_action_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;}
    #bank_card_action_wraper ._select{width:auto;margin-bottom:0;}
    #bank_card_action_wraper #code{width:60%;}
    #bank_card_action_wraper .leftfill{padding-left:0.2rem;}
    #bank_card_action_wraper .rightfill{padding-right:0.2rem;}
<?php endif; ?>
<?php if(\common\helpers\Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){?>
#bank_card_action_wraper .hsm{width: 5rem;margin: 0.6rem auto;height: 0.613333rem;line-height: 0.613333rem;padding-left: 1.2rem;color: #<?= $color;?>;box-sizing:border-box;font-size: 1.24em;background: url(<?= $this->absBaseUrl;?>/image/common/<?= $img;?>) no-repeat 0.133333rem 0/0.506667rem 0.533333rem;}
<?php }?>
</style>

<div id="bank_card_action_wraper">
    <p class="padding adadad em__9" id="title" style="padding-left:0.3rem;">请填写银行卡信息</p>
    <div class="column bg_fff">
        <div class="padding">
            <table class="_table" width="100%">
                <tr>
                    <td class="lh_em_1_8 _666 leftfill" width="24%" style="padding-left:0.5rem;">持卡人</td>
                    <td class="lh_em_1_8 _8d8d8d rightfill">
                        <?= $name;?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666 leftfill" style="padding-left:0.5rem;">选择银行</td>
                    <td class="lh_em_1_8 _8d8d8d padding rightfill" style="padding-right:0.5rem;">
                        <div class="_select _b_radius">
                            <div>
                            <select class="_cursor" id="bank_id">
                                <?php foreach ($card_list as $val): ?>
                                <option value ="<?= $val['bank_id'];?>"><?= $val['bank_name'];?></option>
                                <?php endforeach;?>
                            </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666 leftfill" style="padding-left:0.5rem;">银行卡号</td>
                    <td class="lh_em_1_8 _8d8d8d rightfill">
                        <input class="em_1" id="card_no" maxlength="20" placeholder="请输入银行卡号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666 leftfill" style="padding-left:0.5rem;">手机号</td>
                    <td class="lh_em_1_8 _8d8d8d rightfill">
                        <input class="em_1" id="phone" maxlength="11" placeholder="请输入银行预留手机号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666 leftfill" style="padding-left:0.5rem;">验证码</td>
                    <td class="lh_em_1_8 _8d8d8d padding rightfill" style="padding-right:0.5rem;">
                        <input class="f_left em_1" id="code" maxlength="6" placeholder="<?php echo HELIPAYTIPS; ?>"/>
                        <button class="f_right a_center _61cae4 get_code _b_radius" style="color: <?= $color ?>" id="action" onclick="getCode();">获取</button>
                        <div class="clear"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius" style="background-color: <?=$color;?>">确认绑卡<a class="indie" href="javascript:save();"></a></div>
    <div class="hsm">银行级数据加密保护</div>
</div>

<script type="text/javascript">
var pop_params={btn_bg_color: '<?= $color?>'};

function getCode(){

    var card_no = $.trim( $("#card_no").val() ),
        bank_id = $.trim( $("#bank_id").val() ),
        phone = $("#phone").val();
    if (card_no == "" || bank_id == "" || phone == "") {
        return showExDialog("请先完善信息", '确定',"","","","","",pop_params);
    }

    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定',"","","","","",pop_params);
    }

    var url = "<?= ApiUrl::toRouteCredit(['credit-card/get-code'], true); ?>";
    var params = {
        card_no:card_no,
        bank_id:bank_id,
        phone:phone,
        type:1
    };
  /*  $.ajaxSetup({
        headers: {
            'appMarket': appMarket,
            'clientType':clientType
        }
    });*/
    $.post(url, params, function(data) {
        if (data && data.code == 0) {
            getCodeCountDown('获取', 'num秒','action');
        }
        else if (data.message) {
            showExDialog(data.message, '确定',"","","","","",pop_params);
        }
        else {
            showExDialog('绑卡获取验证码异常，请稍后重试', '确定',"","","","","",pop_params);
        }
    });
}
function save() {
    // 上报重新绑定银行卡
    nativeMethod.returnNativeMethod('{ "type": 20, "operationLogType": 4 }');

    var card_no = $("#card_no").val(),
        bank_id = $("#bank_id").val(),
        phone = $("#phone").val(),
        code = $("#code").val();
    if (card_no == "" || bank_id == "" || phone == "" || code == "") {
        return showExDialog("请先完善信息", '确定',"","","","","",pop_params);
    }
    else if(card_no == "6217000010021315588"||card_no == "6217007180002488002"||card_no == "6217007200005246718"||card_no == "6217002530001949559"||card_no == "6217002390020373046"){
        return showExDialog("请你更换卡绑定", '确定',"","","","","",pop_params);
    }

    drawCircle();

    var url = "<?= ApiUrl::toRouteMobile(['loan/do-bind-card'], true); ?>";
    var params = {
        card_no:card_no,
        bank_id:bank_id,
        phone:phone,
        code:code
    };
    $.post(url, params, function(data) {
        hideCircle();

        if (data.code == 0) {
            jumpTo(getSourceUrl());
        }
        else {
            showExDialog(data.message || '绑卡异常，请稍后重试', '确定',"","","","","",pop_params);
        }
    });

    $("._btn").css('background','#1782e0')
}
</script>
