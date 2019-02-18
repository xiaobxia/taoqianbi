<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/8/23
 * Time: 20:25
 */
use yii\helpers\Url;
use yii\helpers\Html;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>
<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/associate.css">
<div class="house_associate">
    <div id="header">
        <input type="text" id="cell_address" required="required" value="" autofocus="autofocus" placeholder="请输入小区名称或地址" oninput="listen()">
        <button onclick="search()">确认</button>
    </div>
    <div id="img"></div>
    <div id="loading"></div>
    <div id="content" style="max-height: 4rem;">
        <ul id="cell_associate"></ul>
    </div>
    <div id="footer"></div>
</div>


<script type="text/javascript">
    window.onload=function(){
        $("#cell_address").focus();
    };
    function listen() {
        var cell_address = $("#cell_address").val();
        <?php if($city_code == 310100 || $city_code == 310200) :?>
        var params = {address: cell_address};
        var reg = /[a-zA-Z]/;
        if (cell_address != "") {
            $("#content").css("display","");
            if (!reg.test(cell_address)) {
                $.ajax({
                    url: "<?php echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-info-by-address'], true); ?>",
                    dataType: 'jsonp',
                    data: params,
                    success: function (data) {
                        if (data.code == 0 && data.data.item != "") {
                            $("#content").css({"max-height":"4rem","margin-top":"1.26rem"});
                            if(data.data.item.length > 3){
                                $("#footer").css("display","block");
                            } else {
                                $("#footer").css("display","none");
                            }
                            var cell_associate = "";
                            for (var cell_name in data.data.item) {
                                cell_associate = cell_associate + '<li projectid=' + data.data.item[cell_name].Id + ' project_name=' + data.data.item[cell_name].ProjectName + ' address=' + data.data.item[cell_name].Address + ' >' + data.data.item[cell_name].ProjectName + '('+ data.data.item[cell_name].Address +')'  + '</li>';
                            }
                            if($("#cell_associate").length == 0){
                                $("#content").html('<ul id="cell_associate"></ul>');
                            }
                            $("#cell_associate").html(cell_associate);
                            if($("#cell_address").val() == ""){
                                $("#footer").css("display","none");
                            }
                            $("#cell_associate li").last().addClass('margin-b0');
                            $("#cell_associate li").click(function () {
                                var chuan = $(this).attr("projectid");
                                var chuan1 = $(this).attr("project_name");
                                $("#cell_address").attr("projectid", chuan);
                                $("#cell_address").attr("project_name", chuan1);
                                $("#cell_address").val($(this).attr("address"));
                                $("#associate").css("display", "none");
                                window.location.href = "<?php echo Url::toRoute(['building/house-assess','city_area_code'=>$city_area_code,'city'=>$city,'city_code'=>$city_code],true);?>" + '&address=' + $("#cell_address").val() + '&projectid=' + chuan + '&project_name=' + chuan1;
                            });
                        }else {
                            var bad =  '<ul id="reword_back"></ul>' +
                                '<ul class="reword" id="reword_head">阿偶，没有房产信息</ul>' +
                                ' <div><button onclick="jump()">返回上一页</button></div>';
                            $("#footer").removeAttr("style");
                            $("#content").html(bad);
                            $("#content").removeAttr("style",{"max-height":"4rem"});
                            $("#content").css({"margin-top":"2.25rem"});
                        }
                    }
                });
            }
        } else {
            $("#content").css("display","none");
            $("#footer").css("display","none");
        }
        <?php else : ?>
        <?php endif; ?>
    }
    function search(){
        window.location.href = "<?php echo Url::toRoute(['building/house-assess','city_area_code'=>$city_area_code,'city'=>$city,'city_code'=>$city_code],true);?>" + '&address=' + $("#cell_address").val();
    }

    function jump(){
        <?php if(empty($city)) :?>
        window.location.href = "<?php echo Url::toRoute(['building/house-assess'],true);?>" + '?address=' + $("#cell_address").val();
        <?php else :?>
        window.location.href = "<?php echo Url::toRoute(['building/house-assess','city_area_code'=>$city_area_code,'city'=>$city,'city_code'=>$city_code],true);?>" + '&address=' + $("#cell_address").val();
        <?php endif; ?>
    }
</script>