<?php
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<!--标准mui-->
<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.picker.css" rel="stylesheet" />
<link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.poppicker.css" rel="stylesheet" />
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.min.js"></script>
<!--App自定义的js-->
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.picker.js"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/mui/mui.poppicker.js"></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
<div class="house_assess">
    <div class="form-group" id="city_info">
        <label class="assess_header">城市信息</label>
    </div>
    <div class="form-group">
        <label for="city">城市</label>
<!--        <input type="text" id="city" required="required" value="" placeholder="请选择城市">-->
        <span id="city">请输入城市</span>
        <span class="right_css"></span>
    </div>
    <div class="form-group">
        <label for="cell_address">小区地址</label>
        <input type="text" id="cell_address" required="required" value="" placeholder="请输入小区名称或地址" readonly="readonly" onclick="jump()">
        <div id="jump" onclick="jump()"><span class="right_css"></span></div>
    </div>
    <div id="associate">
        <ul id="cell_associate">
        </ul>
    </div>
    <div class="form-group">
        <label class="assess_header">房子信息</label>
    </div>
    <div class="form-group2">
        <label for="house_lou">楼栋号</label>
        <label style="position: inherit;padding-left: 3rem;"></label>
        <input type="tel" maxlength="4" class="house_shuru" id="house_lou" required="required" onkeyup="GetLou()">
        <label class="house_title_label" id="house_lou_label">栋/号</label>
        <input type="tel" maxlength="4" class="house_shuru" id="house_dong" required="required">
        <label class="house_title_label" id="house_dong_label">层</label>
        <input type="tel" maxlength="4" class="house_shuru" id="house_hao" required="required">
        <label class="house_title_label" id="house_hao_label">室</label>
    </div>
    <div id="associate_lou">
        <ul id="associate_house">
        </ul>
    </div>
    <div class="form-group">
        <label for="total_floor">总层高</label>
        <input type="tel" id="total_floor" required="required" value="" placeholder="请输入总层高"><span class="danwei">(层)</span>
    </div>
    <div class="form-group">
        <label class="select_show" for="type">房产类型</label>
        <select id="type"></select>
        <span class="right_css"></span>
    </div>
    <div class="form-group">
        <label for="house_size">房子面积</label>
        <input type="number" id="house_size" required="required" value="" placeholder="请输入房子总面积"><span class="danwei">(平方米)</span>
    </div>
    <div class="form-group">
        <label for="under_size">其他面积</label>
        <input type="number" id="under_size" required="required" value="" placeholder="请输入车库/地下室面积"><span class="danwei">(平方米)</span>
    </div>
    <div class="form-group">
        <label class="select_show" for="toward">朝向</label>
        <select id="toward">
            <option value="1">东</option>
            <option value="2">南</option>
            <option value="3">西</option>
            <option value="4">北</option>
        </select>
        <span class="right_css"></span>
    </div>
    <div class="form-group">
        <label for="finish_year">竣工年限</label>
        <input type="tel" maxlength="4" id="finish_year"  required="required" value="" placeholder="请输入年份(如2016)"><span class="danwei">(年)</span>
    </div>
    <footer>
        <button id="btn" onclick="submit()"><span id="word">上传房证</span></button>
    </footer>
    <p>评价结果不理想? <a href="<?php echo Url::toRoute(["building/house-info"],true); ?>">我想马上人工评房</a></p>
</div>
<script type="text/javascript">
    $('input').val('');
    var city_code,
    city_area_code,
    project_id,
    project_name,
    interval,
    towards,
    totalfloor,
    unitid,
    housetype,
    str_unitno,
    year,
    order_id;
    <?php if(!empty($city)): ?>
        city_code = <?php echo $city_code ?>;
        city_area_code = <?php echo $city_area_code ?>;
        $("#city").text("<?php echo $city ?>");
        $("#city").css("color","black");
    <?php endif; ?>
    <?php if(!empty($address)): ?>
        $("#cell_address").val("<?php echo $address ?>");
        project_id = "<?php echo $projectid ?>";
        project_name = "<?php echo $project_name ?>";
    <?php endif; ?>
    window.onload=function(){
        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-city-and-area-list'], true); ?>","",function(data) {
            (function($mui, doc) {
                $mui.init();
                $mui.ready(function() {
                    var cityPicker3 = new $mui.PopPicker({layer: 2});
                    cityPicker3.setData(data.data.item);
                    var showCityPickerButton = doc.getElementById('city');
                    showCityPickerButton.addEventListener('tap', function(event) {
                        cityPicker3.show(function(items) {
                            showCityPickerButton.innerText = (items[0] || {}).text + " " + (items[1] || {}).text;
                            city_code = items[0].value;
                            city_area_code = items[1].value;
                            $("#city").css("color","black");
                        });
                    }, false);
                });
            })(mui, document);
        });
        // 房产类型
        KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/get-house-info'], true); ?>","",function(data){
            if(data.code == 0) {
                var data1 = data.data.item.house_type_text;
                data2 = data.data.item.level_type_text;
                html1 = null;
                for (var i = 0; i < data1.length; i ++) {
                    html1 += "<option value= "+ [i+1] +" > "+ data1[i].name + "</option>";
                }
                $("#type").append(html1);
                if (data.data&&data.data.item) {
                    if (data.data.item.house_type==1) {
                        $("#type  option[value='1'] ").attr("selected",true);
                    }else if(data.data.item.house_type==2){
                        $("#type  option[value='2'] ").attr("selected",true);
                    }else{
                        $("#type  option[value='1'] ").attr("selected",true);
                    }
                }
            }
        })
    };

    function jump(){
        console.log(city_area_code);
        console.log(city_code);
        if(city_area_code == undefined && city_code == undefined){
            dialog("请先选择城市");
            return false;
        }
        window.location.href = "<?php echo Url::toRoute(['building/house-associate'],true);?>"+'?address='+$("#city").text()+'&city_code='+city_code+'&city_area_code='+city_area_code;
    }

    //获取楼栋号
    function GetLou(){
        var houselou = $("#house_lou").val(),
            params = {projectid:project_id,unitno:houselou};
        $.ajax({
            url: "<?php echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-info-by-building-no'], true); ?>",
            dataType: 'jsonp',
            data: params,
            success: function (data) {
                if (data.code == 0) {
                    var associate_lou = "";
                    for (var louhao in data.data.item) {
                        associate_lou = associate_lou + '<li unitid=' + data.data.item[louhao].Id + ' totalfloor=' + data.data.item[louhao].TotalFloor + '   towards=' + data.data.item[louhao].Towards + ' unitno=' + data.data.item[louhao].UnitNo + ' housetype=' + data.data.item[louhao].HouseType + '  str_unitno=' + data.data.item[louhao].str_unitno + ' year=' + data.data.item[louhao].Year + '  unitnum=' + data.data.item[louhao].UnitNum + ' >' + data.data.item[louhao].UnitNo + '</li>';
                    }
                    $("#associate_house").html(associate_lou);
                    $("#associate_lou").css("display", "block");
                    $("#associate_lou li").last().addClass('margin-b1');
                    if(data.data.item == "") {
                        $("#associate_lou").css({"display": "none"});
                    }
                    $("#associate_lou li").click(function () {
                        var chuan = $(this).attr("unitno");
                        towards = $(this).attr("towards");
                        totalfloor = $(this).attr("totalfloor");
                        unitid = $(this).attr("unitid");
                        housetype = $(this).attr("housetype");
                        str_unitno = $(this).attr("str_unitno");
                        year = $(this).attr("year");
                        $("#associate_house").attr("unitno", chuan);
                        $("#house_lou").val($(this).attr("unitnum"));
                        $("#associate_lou").css("display", "none");
                        $("#total_floor").val(totalfloor);
                        $("#toward").val(towards);
                        $("#finish_year").val(year);
                    });
                }
            }
        });
    }
    //一键评估
    function submit(){
        var cell_address = $("#cell_address").val(),
            project_id = $("#cell_address").attr("projectid"),
            project_name = $("#cell_address").attr("project_name"),
            house_floor = $("#house_floor").val(),
            house_word = $("#house_word").val(),
            house_number = $("#house_number").val(),
            house_lou = $("#house_lou").val(),
            house_dong = $("#house_dong").val(),
            house_hao = $("#house_hao").val(),
            type = $("#type").val(),
            house_size = parseFloat($("#house_size").val()),
            finish_year = $("#finish_year").val(),
            total_floor = $("#total_floor").val(),
            under_size = parseFloat($("#under_size").val()),
            toward = $("#toward").val(),
            city_name = $("#city").html();
        <?php if(!empty($projectid) && !empty($project_name)): ?>
        project_id = <?php echo $projectid; ?>;
        project_name = "<?php echo $project_name; ?>";
        <?php endif; ?>
        if(project_id == undefined){
            project_id = 0;
        }
        if(cell_address == "" || city_name == "" || house_lou == "" || house_dong == "" || finish_year == "" || total_floor == ""){
            dialog("请填写完整信息");
            return false;
        }
        if(isNaN(under_size)){
            under_size = 0;
        }
        if(isNaN(house_size)){
            house_size = 0;
        }
        if(house_size < under_size){
            dialog("其他面积不能大于房子面积");
            return false;
        }
        var reg1 = /^\d{4}$/;
        if(!reg1.test(finish_year)){
            dialog("请填写正确的年限");
            return false;
        }
        if(typeof str_unitno == 'undefined'){
            var houselou = $("#house_lou").val(),
            params = {projectid:project_id,unitno:houselou};
            $.ajax({
                url: "<?php echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-info-by-building-no'], true); ?>",
                dataType: 'jsonp',
                data: params,
                success: function (data) {
                    if(data.code == 0){
                        if (data.data.item.length != 0) {
                            var lou_dong_hao = "";
                            for (var relouhao in data.data.item) {
                                if(data.data.item[relouhao].UnitNum == houselou){
                                    str_unitno = data.data.item[relouhao].str_unitno;
                                    housetype = data.data.item[relouhao].HouseType;
                                    unitid = data.data.item[relouhao].Id;
                                    total_floor = data.data.item[relouhao].TotalFloor;
                                    toward = data.data.item[relouhao].Towards;
                                }
                            }
                        }
                    } else {
                        str_unitno = "号";
                        unitid = 0;
                        housetype = 1;
                    }
                    window.location.href = "<?php echo Url::toRoute(['building/upload-images'],true);?>"+'?distinct='+city_name+'&address='+cell_address+'&house_address='+house_lou+str_unitno+house_dong+"层"+house_hao+"室"+'&area='+house_size+'&other_area='+under_size+'&year='+finish_year+'&house_type='+type+'&projectid='+project_id+'&project_name='+project_name+'&unitid='+unitid+'&unitno='+house_lou+'&roomno='+house_hao+'&floor='+house_dong+'&totalfloor='+total_floor+'&towards='+toward+'&city_code='+city_code+'&city_area_code='+city_area_code+'&cl_house_type='+housetype
                }
            });
        }  else {
            window.location.href = "<?php echo Url::toRoute(['building/upload-images'],true);?>"+'?distinct='+city_name+'&address='+cell_address+'&house_address='+house_lou+str_unitno+house_dong+"层"+house_hao+"室"+'&area='+house_size+'&other_area='+under_size+'&year='+finish_year+'&house_type='+type+'&projectid='+project_id+'&project_name='+project_name+'&unitid='+unitid+'&unitno='+house_lou+'&roomno='+house_hao+'&floor='+house_dong+'&totalfloor='+total_floor+'&towards='+toward+'&city_code='+city_code+'&city_area_code='+city_area_code+'&cl_house_type='+housetype
        }
//        var parms = {distinct:city_name,address:cell_address,house_address:house_lou+str_unitno+house_dong+"层"+house_hao+"室",area:house_size,other_area:under_size,year:finish_year,house_type:type};
//        $.get("<?php //echo Url::toRoute(['building/create-hfd-order'], true); ?>//",parms,function(data) {
//            if(data.code == 0){
//                  order_id = data.order_id;
//                  var param = {
//                      address:cell_address,
//                      projectid:project_id,
//                      project_name:project_name,
//                      unitid:unitid,
//                      unitno:house_lou,
//                      roomno:house_hao,
//                      year:finish_year,
//                      floor:house_dong,
//                      totalfloor:total_floor,
//                      towards:toward,
//                      area:house_size,
//                      other_area:under_size,
//                      city_code:city_code,
//                      city_area_code:city_area_code,
//                      house_type:type,
//                      cl_house_type:housetype,
//                      order_id:order_id,
//                      unitno_str:str_unitno
//                  };
//                var $e=$('<div id="loading"></div>');
//                $e.appendTo("body");
//                KD.util.post("<?php //echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-all-evaluate'], true); ?>//",param,function(data){
//                    $e.remove();
//                    if(data.code == 0){
//                      var param1={order_id:order_id,type:1};
//                      $.get("<?php //echo Url::toRoute(['building/update-type'], true); ?>//",param1,function(data) {});
//                      dialog(data.message,function(){
//                          window.location.href = "<?php //echo Url::toRoute(['building/machine-trial'],true);?>//" +'?price='+data.data.item.price+'&order_id='+order_id;
//                      });
//                    } else if(data.code == 100){
//                        var param2= {order_id:order_id,type:2};
//                        $.get("<?php //echo Url::toRoute(['building/update-type'], true); ?>//",param2,function(data) {});
//                        dialog(data.message,function(){
//                            window.location.href = "<?php //echo Url::toRoute(['building/person-trial'],true);?>//"+'?order_id='+order_id;
//                        });
//                    } else if(data.code == 200) {
//                        var param3={order_id:order_id,type:3};
//                        $.get("<?php //echo Url::toRoute(['building/update-type'], true); ?>//",param3,function(data) {});
//                        dialog(data.message,function(){
//                            window.location.href = "<?php //echo Url::toRoute(['building/machine-trial'],true);?>//" +'?price='+data.data.item.price+'&order_id='+order_id;
//                        });
//                    } else {
//                        dialog(data.message);
//                        $("#btn").attr("onclick","submit()");
//                    }
//              });
//            } else {
//                dialog(data.message);
//                $("#btn").attr("onclick","submit()");
//            }
//        });
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