
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>依赖关系</title>
    <link type="text/css" rel="stylesheet" href="js/jsmind/style/jsmind.css" />
    <style type="text/css">
        #jsmind_container{
            width : 100%;
            height : 800px;
            border : solid 1px #ccc;
            /*background:#f4f4f4;*/
            background:#f4f4f4;
        }
        #jsmind_nav li{float: left;margin: 5px 10px;}
        .clear{clear: both};
        .td24{
            width: 200px;
            font-weight: bold;
        }
    </style>
</head>
<body >
<table class="tb tb2 fixpadding" id="order_report">
    <tr><th class="partition" colspan="10">授信决策详情页</th></tr>
    <tr>
        <th class="td24">用户ID：</th>
        <td><?php echo $list['_id']; ?></td>
        <th class="td24">新增时间：</th>
        <td><?php echo date("Y-m-d H:i:s",$list['created_at']); ?></td>
        <th class="td24">更新时间：</th>
        <td ><?php echo date("Y-m-d H:i:s",$list['updated_at']); ?></td>
    </tr>
</table>
<div id="layout">
    <div id="jsmind_container"></div>
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

