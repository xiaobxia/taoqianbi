<?php
use newh5\components\ApiUrl;
?>
<style type="text/css">
/*新增B 仅修改border颜色*/
    #bank_card_action_wraper .get_code {display: block; width: 20%; height: 25px;line-height: 25px;border: 1px solid #ff6462;background-color: #fff;}
/*新增E*/

.bg_61cae4 {
    /*background: #1782e0;*/
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
/*#bank_card_action_wraper .get_code{display:block;width:20%;height:25px;line-height:25px;border:1px solid #1782e0;}*/
</style>
<div id="bank_card_action_wraper">
    <p class="padding adadad em__9" id="title">请填写银行卡信息</p>
    <div class="column bg_fff">
        <div class="padding">
            <table class="_table" width="100%">
                <tr>
                    <td class="lh_em_1_8 _666" width="24%">持卡人</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <?php echo $name;?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">选择银行</td>
                    <td class="lh_em_1_8 _8d8d8d padding">
                        <div class="_select _b_radius">
                            <div>
                            <select class="_cursor" id="bank_id">
                                <?php foreach ($card_list as $val): ?>
                                <option value ="<?php echo $val['bank_id'];?>"><?php echo $val['bank_name'];?></option>
                                <?php endforeach;?>
                            </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">银行卡号</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="card_no" maxlength="20" placeholder="请输入银行卡号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">手机号</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="phone" maxlength="11" placeholder="请输入银行预留手机号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">验证码</td>
                    <td class="lh_em_1_8 _8d8d8d padding">
                        <input class="f_left em_1" id="code" maxlength="6" placeholder="请输入验证码"/>
                        <button class="f_right a_center _61cae4 get_code _b_radius" style="color: #ff6462" id="action" onclick="getCode();">获取</button>
                        <div class="clear"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius">确认绑卡<a class="indie" href="javascript:save();"></a></div>
</div>
<script type="text/javascript">
function getCode(){
    var phone = $("#phone").val();
    if(!isPhone(phone)){
        return showExDialog('手机号码格式不正确','确定');
    }
    var url = "<?php echo ApiUrl::toRouteCredit(['credit-card/get-code'], true); ?>";
    $.post(url, {phone:phone,type:1}, function(data){
        if(data && data.code == 0){
            getCodeCountDown();
        }else if(data.message){
            showExDialog(data.message,'确定');
        }
    });
}
function save(){
    var card_no = $("#card_no").val(),
        bank_id = $("#bank_id").val(),
        phone = $("#phone").val(),
        code = $("#code").val();
    if(card_no == "" || bank_id == "" || phone == "" || code == "") {
        return showExDialog("请先完善信息",'确定');
    }
    var url = "<?php echo ApiUrl::toRouteMobile(['loan/do-bind-card'], true); ?>";
    var params ={
        card_no:card_no,
        bank_id:bank_id,
        phone:phone,
        code:code
    };
    drawCircle();
    $.post(url,params,function(data){
        hideCircle();
        if(data.code == 0){
            jumpTo(getSourceUrl());
        }else{
            showExDialog(data.message || '保存失败','确定');
        }
    });
    $("._btn").css('background','#1782e0')
}
</script>