<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<style>
    #save{
        color: #1ec8e1
    }
</style>
<div class="more">
    <div class="head">
        <p>为保证借款申请顺利通过，请务必填写真实信息！</p>
    </div>
    <div class="content">
        <ul>
            <li>
                <label>淘宝账号</label><input name="taobao" id="taobao" type="text" value="" placeholder="请输入个人淘宝账号" />
            </li>
            <li>
                <label>常用邮箱</label><input name="mail" id="mail" type="text" placeholder="请输入邮箱" />
            </li>
            <li>
                <label>QQ账号</label><input name="qq" id="qq" type="tel" placeholder="请输入QQ账号">
            </li>
            <li>
                <label>微信账号</label><input name="wx" id="wx" type="text" placeholder="请输入微信账号">
            </li>
        </ul>
    </div>
    <div class="footer">
        <a onclick="Save()" id="save">保存</a>
        <!-- <p id="bank-verify-note">银行级数据加密防护</p> -->
    </div>
</div>
<script type="text/javascript">
    $(function(){
        $.post("<?php echo Url::toRoute(['credit-card/get-more-info','clientType'=>'h5'], true); ?>","",function(data) {
            $("#taobao").val(data.data.taobao);
            $("#mail").val(data.data.mail);
            $("#qq").val(data.data.qq);
            $("#wx").val(data.data.wx);
        });
    });
    function Save(){
        var taobao = $("#taobao").val(),
            mail = $("#mail").val(),
            qq = $("#qq").val(),
            wx = $("#wx").val();

        if(taobao == "" && mail == "" && qq == "" && wx == "") {
            dialog("不能保存空信息");
            return false;
        }
        var params ={
            taobao:taobao,
            mail:mail,
            qq:qq,
            wx:wx
        };

        $.post("<?php echo Url::toRoute(['credit-card/save-more-info','clientType'=>'h5'], true); ?>",params,function(data){
            if(data.code == 0){
                dialog(data.message,function(){
                    returnNative(0);
                });
            }else{
                dialog(data.message);
            }
        });
    }

    // 消息弹窗
    function dialog(g,f){
        var $e=$('<div class="pop-box"><div class="pop-con"><p>'+g+"</p><button>确认</button></div></div>");
        $e.appendTo("body");
        $e.find("button").on("click",function(a){
            a.preventDefault();
            $e.remove();
            if(typeof (f) == "function"){
                f();
            }
        });

    }
</script>
