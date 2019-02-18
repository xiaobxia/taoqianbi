<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="house_css">
    <header>
        <ul>
            <li style="color:#333;">填写<br/>借款人资料</li>
            <li>填写<br/>房产信息</li>
            <li>审核通过</li>
        </ul>
        <div class="house"></div>
    </header>
    <div class="form-group">
        <label class="select_show" for="type">房产类型</label>
        <select id="type"></select>
        <span class="right_css"></span>
    </div>
    <div class="form-group1">
        <label for="number">房产证号码</label>
        <label class="house_title" id="house_floor_label">沪房地</label><input type="text" maxlength="1" class="shuru" id="house_floor" required="required"><label class="house_title" id="house_title_word">字 (</label><input type="number" maxlength="4" class="shuru" id="house_word" required="required"><label id="house_end_word">)</label>
        <label class="house_end" id="house_title_tip">第</label><input type="number" class="shuru" maxlength="6" id="house_number" required="required"><label class="house_end" id="house_end_tip">号</label>
    </div>
    <div class="form-group">
        <label class="select_show" for="level">抵押顺位</label>
        <select id="level" onchange="show_price()"></select>
        <span class="right_css"></span>
    </div>
    <div class="form-group hidden level_one">
        <label for="price">一抵价格</label>
        <input type="number" id="price" required="required" placeholder="请输入一抵价格"><span class="danwei">(万元)</span>
    </div>
    <div class="form-group is_house">
        <label style="position:relative;">有备用房</label>
        <a href="javascript:void(0);" onclick="have_house()">
            <span class="have" >
                <span class="yes_css">&#8226</span>
            </span>
            <span>是</span>
        </a>
        <a href="javascript:void(0);" onclick="no_house()">
            <span class="have" >
                <span class="no_css">&#8226</span>
            </span>
            <span>否</span>
        </a>
    </div>
    <div class="form-group">
        <label for="no">户口人数</label>
        <input type="number" id="no" required="required" placeholder="请输入户口人数">
    </div>
    <footer>
        <button id="btn" onclick="submit()"><span id="word">下一步</span></button>
    </footer>
</div>
<script type="text/javascript">
    // 房产类型 抵押顺位
    window.onload=function(){
        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/get-house-info','clientType'=>'h5'], true); ?>","",function(data){
            if(data.code == 0) {
                var is_house ;
                var data1 = data.data.item.house_type_text;
                data2 = data.data.item.level_type_text;
                html1 = null;
                html2 = null;
                for (var i = 0; i < data1.length; i ++) {
                    html1 += "<option value= "+ [i+1] +" > "+ data1[i].name + "</option>";
                }
                $("#type").append(html1);
                for (var i = 0; i < data2.length; i ++) {
                    html2 += "<option value= "+ [i+1] +" > "+ data2[i].name + "</option>";
                }
                $("#level").append(html2);

                // 获取页面数据
                if (data.data&&data.data.item) {
                    if (data.data.item.house_num) {
                        $("#number").val(data.data.item.house_num);
                    }
                    // 房产类型
                    if (data.data.item.house_type==1) {
                        $("#type  option[value='1'] ").attr("selected",true);
                    }else if(data.data.item.house_type==2){
                        $("#type  option[value='2'] ").attr("selected",true);
                    }else{
                        $("#type  option[value='1'] ").attr("selected",true);
                    }

                    // 抵押顺位
                    if (data.data.item.level_type==1) {
                        $("#level  option[value='1'] ").attr("selected",true);
                    }else {
                        $("#level  option[value='2'] ").attr("selected",true);
                    }

                    if (data.data.item.level_one_money) {
                        $("#price").val(data.data.item.level_one_money);
                    }
                    if (!data.data.item.is_spare_house) {
                        no_house();
                    }
                    if (data.data.item.is_spare_house==1) {
                        have_house();
                    }
                    if (data.data.item.house_person_num) {
                        $("#no").val(data.data.item.house_person_num);
                    }
                }
                show_price();
            }
        })
    }
    function show_price () {
        // 判断抵押顺位
        var level = $("#level").val();
        if (level == 2) {
            $(".level_one").show();
        }else {
            $(".level_one").hide();
        }
    }
    function submit(){
        var type = $("#type").val(),
            house_floor = $("#house_floor").val(),
            house_word = $("#house_word").val(),
            house_number = $("#house_number").val(),
            level = $("#level").val(),
            price = $("#price").val(),
            no = $("#no").val(),

            reg =  /[\u4e00-\u9fa5]/;
            number_reg =/^(([0-9]+\.[0-9]*[1-9][0-9]*)|([0-9]*[1-9][0-9]*\.[0-9]+)|([0-9]*[1-9][0-9]*))$/;

        if(!type) {
            dialog("房产类型不能为空");
            return false;
        }
        if(house_floor == "" || house_word == "" || house_number == "") {
            dialog("房产证号不能为空");
            return false;
        }

        if(!reg.test(house_floor)){
            dialog("房产证号地字填写中文");
            return false;
        }
        if(!level) {
            dialog("抵押顺位不能为空");
            return false;
        } 
        if (level == 2) {
            if(price == "") {
                dialog("一抵价格不能为空");
                return false;
            } 
            if ( !number_reg.test(price) ){
                dialog("一抵价格请输入正整数");
                return false;
            } 
        }


        var params = {
            house_type       : type,
            house_num        : "沪房地"+house_floor+"字("+house_word+")第"+house_number+"号",
            level_type       : level,
            level_one_money  : price,
            is_spare_house   : is_house,
            house_person_num : no
        };

        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/save-house-info','clientType'=>'h5'], true); ?>",params,function(data){
           if(data.code == 0) {               
                window.location.href = "<?php echo Url::toRoute(['building/upload-images'],true);?>";                                           
            } else {
                dialog(data.message);
            }   
        })        
    }

    function have_house() {
        $(".yes_css").css({color:"#fa5558"});
        $(".no_css").css({color:"#fff"});
        is_house=1;
    }

    function no_house() {
        $(".yes_css").css({color:"#fff"});
        $(".no_css").css({color:"#fa5558"});
        is_house=0;
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