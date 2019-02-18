<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="personal_css">
    <header>
        <ul>
            <li>填写<br/>借款人信息</li>
            <li>填写<br/>房产信息</li>
            <li>审核通过</li>
        </ul>
        <div class="process"></div>
    </header>
    <div class="form-group">
        <label for="money">借款金额</label>
        <input type="text" id="money" required="required" value="" placeholder="请输入借款金额"><span class="danwei">(万元)</span>
    </div>
    <div class="form-group">
        <label for="period">借款期限</label>
        <input type="text" id="period" required="required" value="" placeholder="请输入借款期限"><span class="danwei">(个月)</span>
    </div>
    <div class="form-group shop_code1">
        <label for="code_one">渠道代码</label>
        <input type="text" id="code_one" required="required"  value="" placeholder="请输入渠道代码">
    </div>
    <div class="form-group shop_code2">
        <label for="code_two">渠道代码</label>
        <select id="code_two"></select>
        <span class="right_css"></span>
    </div>
    <footer>
        <button id="btn" onclick="submit()"><span id="word">下一步</span></button>
    </footer>   
</div>
<script type="text/javascript">
    var can_choose,
        shop_code;
    window.onload=function(){
        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/get-loan-person-info','clientType'=>'h5'], true); ?>","",function(data){
            if(data.code == 0) {               
                if (data.data&&data.data.item) {
                    can_choose = data.data.item.can_choose;                  
                    if(data.data.item.can_choose==1){
                        $(".shop_code1").hide();
                        var list = data.data.item.shop_code_list,
                            html = "",
                            i;
                        for (i=0; i<list.length; i++) {
                            html += "<option value= "+ [list[i].id] +" > "+ list[i].name + "</option>";
                        }
                        $("#code_two").append(html); 
                    }else {
                        shop_code = data.data.item.shop_code;
                        $(".shop_code2").hide();
                        if (data.data.item.shop_name) {
                            $("#code_one").val(data.data.item.shop_name);
                            $("#code_one").attr("readonly","readonly");
                        }
                    }
                    if (data.data.item.money) {
                        var money = parseInt(data.data.item.money);
                        $("#money").val(money);
                    }
                    if (data.data.item.period) {
                         $("#period").val(data.data.item.period);
                    }
                }              
                
            }    
        })            
    }
    function submit(){
        var money = $("#money").val(),
            period = $("#period").val(),
            num_reg = /^[1-9]+[0-9]*]*$/,
            code_reg = /^[A-Za-z0-9]+$/,
            code;

        if (can_choose==1){
            code = $("#code_two").val();
        }else {
            if (shop_code) {
                code = shop_code;
            }else {
                code = $("#code_one").val();
            }           
        }

        if(money == "") {
            dialog("借款金额不能为空");
            return false;
        } 
        if ( !num_reg.test(money) ){
            dialog("借款金额请输入整数");
            return false;
        }
        if(period == "") {
            dialog("借款期限不能为空");
            return false;
        } 
        if ( !num_reg.test(period) ){
            dialog("借款期限请输入整数");
            return false;
        } 
        if(code == "") {
            dialog("渠道代码不能为空");
            return false;
        } 
        if ( !code_reg.test(code) ){
            dialog("渠道代码不合法");
            return false;
        }
        var type = "<?php echo Yii::$app->request->get('type') == null ?  '' :  Html::encode( Yii::$app->request->get('type') );?>";
        var params = {
            type     : type,
            money    : money,
            period   : period,
            shop_code: code
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/save-loan-person-info','clientType'=>'h5'], true); ?>",params,function(data){
           if(data.code == 0) {                
                window.location.href = "<?php echo Url::toRoute(['building/house-assess'],true);?>";
            } else {
                dialog(data.message);
            }   
        })        
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