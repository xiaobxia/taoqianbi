
<?php
    use common\helpers\Url;
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>查看依赖调试</title>
<!--    <link type="text/css" rel="stylesheet" href="--><?php //echo Url::toRoute('js/jsmind/style/jsmind.css'); ?><!--" />-->
    <style type="text/css">
        #jsmind_container{
            width : 100%;
            height : 700px;
            border : solid 1px #ccc;
            /*background:#f4f4f4;*/
            background:#f4f4f4;
        }
        #jsmind_nav li{float: left;margin: 5px 10px;}
        .clear{clear: both};

        input.form-control{
            display: block;
            width: 200px;
            height: 20px;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            margin: 3px 0;
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: normal;
            line-height: 1.42857143;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .btn-success{
            color: #fff;
            background-color: #5cb85c;
            border-color: #4cae4c;
        }
    </style>
</head>
<body >
<div id="layout">
    <div id="jsmind_container"></div>
    <div style="width: 100%;height: 15px;"></div>
    <div class="test-form">
        <label class="control-label">用户id</label>
        <input type="text" id="test-user_id" style="width: 200px;height: 20px;padding: 6px 12px;font-size: 14px;line-height: 1;color: #555;background-color: #fff;background-image: none;border: 1px solid #ccc;border-radius: 4px;">
        <a class="btn btn-success" href="javascript:testrule()">提交</a>
        <div id="test-result"></div>
    </div>
    <div id="jsmind_nav">
<!--            <li><button onclick="change_container();">resize container</button>-->
<!--                <button onclick="resize_jsmind();">adusting</button></li>-->
            <li class="clear">expand/collapse</li>
            <ol>
                <li><button class="sub" onclick="expand();">expand node</button></li>
                <li><button class="sub" onclick="collapse();">collapse node</button></li>
                <li><button class="sub" onclick="toggle();">toggle node</button></li>
                <li><button class="sub" onclick="expand_to_level2();">expand to level 2</button></li>
                <li><button class="sub" onclick="expand_to_level3();">expand to level 3</button></li>
                <li><button class="sub" onclick="expand_all();">expand all</button></li>
                <li><button class="sub" onclick="collapse_all();">collapse all</button></li>
            </ol>
            <li class="clear">zoom</li>
            <ol>
                <li><button id="zoom-in-button" style="width:50px" onclick="zoomIn();" class="clear">In</button></li>
                <li><button id="zoom-out-button" style="width:50px" onclick="zoomOut();" class="clear">Out</button></li>
            </ol>
    </div>
</div>
<script type="text/javascript" src="js/jsmind/js/jsmind.js"></script>
<script type="text/javascript" src="js/jsmind/js/jsmind.draggable.js"></script>
<script type="text/javascript" src="js/jsmind/js/jsmind.screenshot.js"></script>
<script type="text/javascript">
    function testrule(){
        var user_id = parseInt($("#test-user_id").val());
        if(!user_id){
            $("#test-result").text('请先输入用户名');
            return;
        }
        $("#test-result").text('');
        var rule_id = '<?php echo $id;?>';
        $.get(
            '<?php echo Url::toRoute('decision-tree/test-dependence-rule'); ?>',
            {'rule_id':rule_id,'user_id':user_id},
            function(ret){
                if(ret['code'] === 0){
                    var msg = ret['msg'];
                    _jm.enable_edit();
                    $.each(msg,function(node_id,value){
                        if(typeof(value) == 'object'){
                            var val = JSON.stringify(value);
                            $('.jsmind-inner jmnode[nodeid='+node_id+']').attr('title',val);
                            console.log('id:'+node_id+'json'+val);
                        }
                    });
                    _jm.disable_edit();
                }else{
                    // TODO
                }
            },
            'json'
        );
    }
</script>
<script type="text/javascript">
    var _jm = null;
    function load_jsmind(){
        var mind = {
            "meta":{
                "name":"dtree",
                "author":"dolphy@koudailc.com",
                "version":"0.2"
            },
            "format":"node_array",
            "data":<?php echo json_encode($tree);?>
        };

        var options = {
            container:'jsmind_container',
            editable : false,
            theme:'primary',
            mode :'full',           // 显示模式
            support_html : true,    // 是否支持节点里的HTML元素
            view:{
                hmargin:50,        // 思维导图距容器外框的最小水平距离
                vmargin:100,         // 思维导图距容器外框的最小垂直距离
                line_width:2,       // 思维导图线条的粗细
                line_color:'#555'   // 思维导图线条的颜色
            },
            layout:{
                hspace:30,          // 节点之间的水平间距
                vspace:20,          // 节点之间的垂直间距
                pspace:13           // 节点收缩/展开控制器的尺寸
            },
            shortcut:{
                enable:true,        // 是否启用快捷键
                handles:{},         // 命名的快捷键事件处理器
                mapping:{           // 快捷键映射
                    toggle     : 32,    // <Space>
                    left       : 37,    // <Left>
                    up         : 38,    // <Up>
                    zoomIn      : 39,    // <Right>
                    zoomOut       : 40    // <Down>
                }
            }
        };

        _jm = new jsMind(options);
        // 让 jm 显示这个 mind 即可
        _jm.show(mind);




    }

    var zoomInButton = document.getElementById("zoom-in-button");
    var zoomOutButton = document.getElementById("zoom-out-button");

    function zoomIn() {
        if (_jm.view.zoomIn()) {
            zoomOutButton.disabled = false;
        } else {
            zoomInButton.disabled = true;
        }
    }

    function zoomOut() {
        if (_jm.view.zoomOut()) {
            zoomInButton.disabled = false;
        } else {
            zoomOutButton.disabled = true;
        }
    }

    function expand(){
        var selected_id = get_selected_nodeid();
        if(!selected_id){prompt_info('please select a node first.');return;}

        _jm.expand_node(selected_id);
    }

    function collapse(){
        var selected_id = get_selected_nodeid();
        if(!selected_id){prompt_info('please select a node first.');return;}

        _jm.collapse_node(selected_id);
    }

    function toggle(){
        var selected_id = get_selected_nodeid();
        if(!selected_id){prompt_info('please select a node first.');return;}

        _jm.toggle_node(selected_id);
    }

    function expand_all(){
        _jm.expand_all();
    }

    function expand_to_level2(){
        _jm.expand_to_depth(2);
    }

    function expand_to_level3(){
        _jm.expand_to_depth(3);
    }

    function collapse_all(){
        _jm.collapse_all();
    }


    function get_selected_nodeid(){
        var selected_node = _jm.get_selected_node();
        if(!!selected_node){
            return selected_node.id;
        }else{
            return null;
        }
    }
    load_jsmind();
</script>
</body>
</html>