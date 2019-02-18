<?php
use newh5\components\ApiUrl;
?>
<style type="text/css">
#more_user_info_wraper{min-height:100%;background:#f5f5f7;}
#more_user_info_wraper #title{padding-top:1.8em;padding-bottom:.5em;}
#more_user_info_wraper .column{border-top:1px solid #dcdbdf;border-bottom:1px solid #dcdbdf;}
#more_user_info_wraper .column .padding{padding-right:0;}
#more_user_info_wraper ._table{border-collapse:collapse;}
#more_user_info_wraper ._table td{padding:.8em 0;border-top:1px solid #dcdbdf;}
#more_user_info_wraper ._table tr:first-child td{border-top:0 none;}
#more_user_info_wraper ._table td input{width:100%;height:21px;line-height:21px;}
#more_user_info_wraper .btn{width:87%;padding:.7em 0;margin-top:2em;}
#save{
    background-color: #1782e0;
}
</style>
<div id="more_user_info_wraper">
    <p class="padding adadad em__9" id="title">为保证借款申请顺利通过，请务必填写真实信息！</p>
    <div class="column bg_fff">
        <div class="padding">
            <table class="_table" width="100%">
                <tr>
                    <td class="lh_em_1_8 _666"> QQ账号</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="qq" value="<?php echo $data['qq'];?>" maxlength="20" placeholder="请输入QQ账号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666"> 微信账号</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="wx" value="<?php echo $data['wx'];?>" maxlength="20" placeholder="请输入微信账号"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666"> 常用邮箱</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="mail" value="<?php echo $data['mail'];?>" maxlength="20" placeholder="请输入邮箱"/>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666" width="24%"> 淘宝账号</td>
                    <td class="lh_em_1_8 _8d8d8d">
                        <input class="em_1" id="taobao" value="<?php echo $data['taobao'];?>" maxlength="20" placeholder="请输入淘宝账号"/>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius" style="background-color:<?php echo $color?>;color: #ffffff;">保存<a class="indie" href="javascript:save();"></a></div>
</div>
<script type="text/javascript">
    setWebViewFlag();
    MobclickAgent.onEvent("more","更多信息页面事件"); //页面进入打点

    var pop_params={btn_bg_color: '<?= $color?>'};//showExDialog btn背景色
    function save(){

        // 上报更多信息
        nativeMethod.returnNativeMethod('{ "type": 20, "operationLogType": 7 }');

        // 点击保存按钮打点
        try{
            MobclickAgent.onEventWithLabel("more_keep","更多信息页-保存");
        }catch (e){
            console.log(e);
        }

        var taobao = $("#taobao").val(),
            mail = $("#mail").val(),
            qq = $("#qq").val(),
            wx = $("#wx").val();
        if(taobao == "" && mail == "" && qq == "" && wx == "") {
            return showExDialog("不能保存空信息",'确定',"","","","","",pop_params);
        }
        if(taobao.indexOf(" ") == 0 || mail.indexOf(" ") == 0 || qq.indexOf(" ") == 0 || wx.indexOf(" ") == 0){
            return showExDialog("不能保存空信息",'确定',"","","","","",pop_params);
        }
        var url = "<?php echo ApiUrl::toRouteCredit(['credit-card/save-more-info'], true); ?>";
        var params ={
            taobao:taobao,
            mail:mail,
            qq:qq,
            wx:wx
        };
        drawCircle();
        $.post(url,params,function(data){
            hideCircle();
            if(data.code == 0){
                <?php if(\Yii::$app->controller->isFromApp()):?>
                //返回认证中心
                nativeMethod.returnNativeMethod('{"type":"0"}');
            <?php else:?>
                showExDialog(data.message,'确定',"","","","","",pop_params);
            <?php endif;?>
                // 保存成功打点为 ‘1’
                eventSubmitResult("1"); 
            }else{
                showExDialog(data.message || '保存失败','确定',"","","","","",pop_params);
                // 保存失败打点为 ‘0’
                eventSubmitResult("0");
            }
        });

        //打点 确认返回结果
        function eventSubmitResult(resultValue) {
            try {
                var eventId = "more_keep1"; //事件ID
                var eventData = {
                    '更多信息页-保存结果': resultValue
                }
                MobclickAgent.onEventWithParameters(eventId, eventData);
            } catch(e) {
                console.log(e);
            }
        }
    }
</script>