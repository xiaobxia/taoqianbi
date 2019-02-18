<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>

<link rel="stylesheet" href="<?php echo Url::toStatic('/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo Url::toStatic('/bootstrap/datetimepicker/css/bootstrap-datetimepicker.min.css'); ?>">
<style type="text/css">
    .hidden {
        display: none;
    }
    .head {
        width: 1100px;
        margin-top: 40px;
    }
    .content {
        width: 1100px;
    }
    .filter {
        width: 1100px;
    }
    .filter .input-group {
        margin-top: 20px;
        margin-right: 20px;
        width: 48%;
        display: inline-table;
        float: left;
    }
    .filter .input-group button {
        margin: 0;
        width: 100%;
    }
    .input-group .block-left {
        width: 10%;
    }
    .input-group .block-left button {
        min-width: 90px;
    }
    .input-group .block-middle {
        position: absolute;
        margin: 0;
        z-index: 100;
        width: 74%;
        height: 100%;
        border: 1px solid #ccc;
        border-left: 0;
        border-right: 0;
        border-radius: 0;
    }
    .input-group .block-right {
        width: 5%;
    }
    .input-group .block-left ul {
        min-width: 106px;
    }
    .input-group .block-middle span {
        float: left;
        height: 80%;
        margin-left: 10px;
        margin-top: 3px;
        line-height: 20px;
    }
    .input-group .block-middle img {
        margin-left: 6px;
        width: 12px;
        height: 12px;
        cursor: pointer;
    }
    .input-group .span-left {
        width: 10%;
        min-width: 90px;
        color: #333;
        background-color: white;
    }
    .bottom {
        margin-top: 40px;
        width: 1100px;
    }
    .bottom button {
        width: 275px;
        height: 50px;
        margin-top: 20px;
        margin-left: 420px;
    }
    .nav-default{
        position: inherit;
    }
    .input-group .from {
        width: 46.5%;
    }
    .input-group .to {
        width: 46.5%;
        float: right;
    }
    .input-group .seperate {
        display: inline-block;
        text-align: center;
        width: 7%;
    }
    .bottom .input-group {
        width: 600px;
        height: 34px;
        padding-top: 20px;
        margin-left: 250px;
    }
</style>
<?php
    //Test
?>
<div class="head">
    <ul class="nav nav-tabs nav-justified nav-default">
        <?php
            foreach ($data as $key => $value) {
                echo "<li data-index='$key' data-key='".$value['key']."'><a href='javascript:;'>".$value['name']."</a></li>";
            }
        ?>
    </ul>
</div>

<div class="content">

    <?php
        foreach ($data as $key => $value) {
            echo "<div id='".$value['key']."' class='btn-group btn-group-lg' style='display:none;'>";

                $temp = $value['data'];

                foreach ($temp as $index => $val) {
                    echo "<button type='button' id='".$val['key']."' class='btn btn-default' data-index='$index' data-key='".$val['key']."' data-option='".$val['option']."' data-value='".json_encode((object)$val['value'])."'>".$val['name']."</button>";
                }

            echo "</div>";
        }
    ?>
</div>

<div class="filter"></div>

<div class="bottom">

    <div class="input-group">
        <span class="input-group-addon span-left">任务名称</span>
        <input id="task-name" type="text" class="form-control">
    </div>
    <div class="input-group">
        <span class="input-group-addon span-left">任务描述</span>
        <input id="task-remark" type="text" class="form-control">
    </div>

    <button type="button" class="btn btn-info">提交任务</button>
</div>

<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script src="<?php echo Url::toStatic('/bootstrap/js/bootstrap.min.js'); ?>"></script>
<script src="<?php echo Url::toStatic('/bootstrap/datetimepicker/js/bootstrap-datetimepicker.min.js'); ?>"></script>
<script src="<?php echo Url::toStatic('/bootstrap/datetimepicker/js/locales/bootstrap-datetimepicker.zh-CN.js'); ?>"></script>

<script type="text/javascript">

    var filters = {}; //全局存储筛选条件

    $(function(){
        $('#datetimepicker').datetimepicker();
        $('.head .nav li:eq(0)').addClass('active');

        var widgetWidth = $('.head .nav li').css('width');
        $('.content button').css('width', widgetWidth);

        $('.content .btn-group:eq(0)').show();

        $('.head .nav li').click(function(){
            $('.head .nav li').removeClass('active');
            $(this).addClass('active');
            var key = $(this).data('key');
            $('.content .btn-group').hide();
            $('#'+key).show();
        });

        $('.content button').click(function(){
            if(!$(this).hasClass('active')){
                $(this).addClass('active');
                var name = $(this).html();
                var key = $(this).data('key');
                var option = $(this).data('option');
                var value = eval($(this).data('value'));
                // console.log(name);
                // console.log(key);
                // console.log(option);
                // console.log(value);

                switch(option){
                    case 'string':
                        appendString(name, key, value, option);
                        break;
                    case 'list':
                        appendList(name, key, value, option);
                        break;
                    case 'int_between':
                        appendInt(name, key, value, option);
                        break;
                    case 'float_between':
                        appendFloat(name, key, value, option);
                        break;
                    case 'date_between':
                        appendDate(name, key, value, option);
                        break;
                    default:
                        alert('未识别选项, 请联系开发人员!')
                }
            }
        });

        $('.bottom button').click(function(){
            // console.log('submit');
            //遍历filter获取搜索条件
            var taskName = $('#task-name').val();
            var taskRemark = $('#task-remark').val();
            if(taskName == "" || taskName == null || taskName == "undefined"){
                alert('任务名称为空')
            }else{
                var filters = getFilters();
                if(!$.isEmptyObject(filters)){
                    filters = JSON.stringify(filters);
                    $(this).attr('disabled', 'disabled');
                    $.ajax({
                        url : "<?php echo Url::toRoute('task/loan-info-list'); ?>",
                        type : 'POST',
                        data : {
                            'title': taskName,
                            'remark': taskRemark,
                            'data': filters,
                        },
                        error : function(result){
                            alert('数据请求失败，请尝试刷新页面');
                            $('.bottom button').removeAttr('disabled');
                        },
                        success : function(result){
                            alert(result.message);
                            $('.bottom button').removeAttr('disabled');
                        }
                    });
                }else{
                    alert('筛选条件为空');
                }
            }
        });
    });

    function getFilters(){
        var result = {};
        var list = $('.filter .input-group');
        var length = list.length;
        for (var i = 0; i < length; i++) {
            var temp = {};
            var obj = list.eq(i);
            var key = obj.data('key');
            var option = obj.data('option');
            temp.option = option;
            switch(option){
                case 'string':
                    var value = obj.find('input').val();
                    if(value != "" && value != null && value != "undefined"){
                        temp.value = value;
                        // console.log(temp);
                        result[key] = temp;
                    }
                    break;
                case 'list':
                    var tempList = obj.find('.block-middle span');
                    var len = tempList.length;
                    if(len != 0){
                        var value = new Array();
                        for (var j = 0; j < len; j++) {
                            value.push(tempList.eq(j).data('index'));
                        }
                        temp.value = value;
                        // console.log(temp);
                        result[key] = temp;
                    }
                    break;
                case 'int_between':
                    var from = obj.find('input.from').val();
                    var to = obj.find('input.to').val();
                    if(from != "" || to != ""){
                        var value = new Array();
                        from = parseInt(from);
                        to = parseInt(to);
                        if(!isNaN(from)){
                            value[0] = from;
                        } else{
                            value[0] = 0;
                        }
                        if(!isNaN(to)){
                            value[1] = to;
                        }else{
                            value[1] = 0;
                        }
                        temp.value = value;
                        // console.log(temp);
                        result[key] = temp;
                    }
                    break;
                case 'float_between':
                    var from = obj.find('input.from').val();
                    var to = obj.find('input.to').val();
                    if(from != "" || to != ""){
                        var value = new Array();
                        from = parseFloat(from);
                        to = parseFloat(to);
                        if(!isNaN(from)){
                            value[0] = from;
                        } else{
                            value[0] = 0;
                        }
                        if(!isNaN(to)){
                            value[1] = to;
                        }else{
                            value[1] = 0;
                        }
                        temp.value = value;
                        // console.log(temp);
                        result[key] = temp;
                    }
                    break;
                case 'date_between':
                    var from = obj.find('input.from').val();
                    var to = obj.find('input.to').val();
                    if(from != "" || to != ""){
                        var value = new Array();
                        if(from != "" ){
                            from = Date.parse(new Date(from)) / 1000;
                            value[0] = from;
                        }else{
                            value[0] = 0;
                        }
                        if(to != "" ){
                            to = Date.parse(new Date(to)) / 1000;
                            value[1] = to;
                        }else{
                            value[1] = 0;
                        }
                        temp.value = value;
                        // console.log(temp);
                        result[key] = temp;
                    }
                    break;
                default:
                    alert('未识别选项, 请联系开发人员!')
            }
        }
        return result;
    }

    function appendString(name, key, value, option){
        var html =  '<div class="input-group" data-key="'+key+'" data-option="'+option+'">'+
                        '<span class="input-group-addon span-left">'+name+'</span>'+
                        '<input type="text" class="form-control">'+
                        '<span class="input-group-btn block-right">'+
                            '<button class="btn btn-default" type="button" onclick="removeFilter(this)">删除</button>'+
                        '</span>'+
                    '</div>';

        $('.filter').append(html);
    }

    function appendFloat(name, key, value, option){
        var html =  '<div class="input-group" data-key="'+key+'" data-option="'+option+'">'+
                        '<span class="input-group-addon span-left">'+name+'</span>'+
                        '<input type="text" class="form-control from" onkeyup="isFloat(event, this)" style="ime-mode:Disabled">'+
                        '<div class="seperate">~</div>'+
                        '<input type="text" class="form-control to" onkeyup="isFloat(event, this)" style="ime-mode:Disabled">'+
                        '<span class="input-group-btn block-right">'+
                            '<button class="btn btn-default" type="button" onclick="removeFilter(this)">删除</button>'+
                        '</span>'+
                    '</div>';

        $('.filter').append(html);
    }

    function appendInt(name, key, value, option){
        var html =  '<div class="input-group" data-key="'+key+'" data-option="'+option+'">'+
                        '<span class="input-group-addon span-left">'+name+'</span>'+
                        '<input type="text" class="form-control from" onkeydown="isInt(event)">'+
                        '<div class="seperate">~</div>'+
                        '<input type="text" class="form-control to" onkeydown="isInt(event)">'+
                        '<span class="input-group-btn block-right">'+
                            '<button class="btn btn-default" type="button" onclick="removeFilter(this)">删除</button>'+
                        '</span>'+
                    '</div>';

        $('.filter').append(html);
    }

    function appendDate(name, key, value, option){
        var timestamp = new Date().getTime();
        var id0 = 'datetimepicker'+timestamp;
        var id1 = 'datetimepicker'+timestamp+1;
        var html =  '<div class="input-group" data-key="'+key+'" data-option="'+option+'">'+
                        '<span class="input-group-addon span-left">'+name+'</span>'+
                        '<input type="text" id="'+id0+'" class="form-control from" data-date-format="yyyy-mm-dd hh:ii">'+
                        '<div class="seperate">~</div>'+
                        '<input type="text" id="'+id1+'" class="form-control to" data-date-format="yyyy-mm-dd hh:ii">'+
                        '<span class="input-group-btn block-right">'+
                            '<button class="btn btn-default" type="button" onclick="removeFilter(this)">删除</button>'+
                        '</span>'+
                    '</div>';

        $('.filter').append(html);

        $('#'+id0).datetimepicker({
                language: 'zh-CN',
            });
        $('#'+id1).datetimepicker({
                language: 'zh-CN',
            });
    }

    function appendList(name, key, value, option){
        var html =  '<div class="input-group" data-key="'+key+'" data-option="'+option+'">'+
                        '<div class="input-group-btn block-left">'+
                            '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'+name+'<span class="caret"></span></button>'+
                            '<ul class="dropdown-menu">';
                                for(var temp in value){
                                    html += '<li><a data-index="'+temp+'" href="javascript:;" onclick="addTag(this)">'+value[temp]+'</a></li>';
                                }
            html +=         '</ul>'+
                        '</div>'+
                        '<div class="block-middle">'+
                        '</div>'+
                        '<span class="input-group-btn block-right">'+
                            '<button class="btn btn-default" type="button" onclick="removeFilter(this)">删除</button>'+
                        '</span>'+
                    '</div>';

        $('.filter').append(html);
    }

    function addTag(obj){
        var index = $(obj).data('index');
        var name = $(obj).html();
        var option = $(obj).closest('.input-group').data('option');
        var key = $(obj).closest('.input-group').data('key');
        // console.log(index);
        // console.log(name);
        // console.log(option);
        // console.log(key);

        var html = '<span class="label label-info" data-index="'+index+'">'+name+'<img src="<?php echo Url::toStatic('/image/close.gif') ?>" onclick="removeTag(this)"></span>';

        $(obj).closest('.block-left').next().append(html);

        if(key in filters){
            filters[key]['value'].push(index);
        }else{
            filters[key] = {};
            filters[key]['option'] = option;
            filters[key]['value'] = [];
            filters[key]['value'].push(index);
        }
        // console.log(filters);
    }

    function removeTag(obj){
        var key = $(obj).closest('.input-group').data('key');
        var index = filters[key]['value'].indexOf($(obj).parent().data('index'));
        filters[key]['value'].splice(index, 1);
        $(obj).parent().remove();
        // console.log(filters);
    }

    function removeFilter(obj){
        $(obj).parent().parent().remove();
        var id = $(obj).closest('.input-group').data('key');
        $('#'+id).removeClass('active');
    }

    function isInt(e) {
        var k = window.event ? e.keyCode : e.which;
        if (((k >= 48) && (k <= 57)) || k == 8 || k == 0) {
        } else {
            if (window.event) {
                window.event.returnValue = false;
            }
            else {
                e.preventDefault(); //for firefox
            }
        }
    }

    function isFloat(e, obj){

        $(obj).val($(obj).val().replace(/[^\d.]/g, "").replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3'));
    }

</script>

<?php
    //var_dump($data);
?>