<?php
/**
 *

 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<style>

    .person {
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .table td{
        border:1px solid darkgray;
    }
    .tb2 th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .tb2 td{
        border:1px solid darkgray;
    }
    .tb2 {
        border:1px solid darkgray;
    }
    .mark {
        font-weight: bold;
        /*background-color:indianred;*/
        color:red;
    }
</style>

<script type="text/javascript" src="<?php echo Url::toStatic('/js/jstree.min.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo Url::toStatic('/css/jstree.css'); ?>" />


<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">风控模型结果</th></tr>
    <tr>
    	<th width="110px;" class="person">特征结果列表</th>
    	<td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;color:#ff5951;cursor: pointer" id="b1">
    		点击查看
    	</td>
    </tr>
    <tr>
        <th id="data" width="110px;" class="person">信用评估</th>
    </tr>
    <tr>
        <th id="data2" width="110px;" class="person">反欺诈</th>
    </tr>
    <tr>
        <th id="data3" width="110px;" class="person">禁止项</th>
    </tr>
    <tr>
        <th width="110px;" class="person">操作</th>
        <td>
<!--        <span onClick="scorePerson(this);" style="border: 1px solid;padding: 5px;cursor: pointer">更新评分</span>   -->
        <a href="<?php echo Url::toRoute(['rule/get-yys-report','id'=>$id]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">查看运营商数据</a>&nbsp;
        <a href="<?php echo Url::toRoute(['rule/get-taobao-report','id'=>$id]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">查看淘宝报告</a>&nbsp;
        </td>
    </tr>
</table>

<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">风控新模型结果</th></tr>
    <tr>
        <th id="new" width="110px;" class="person">信用评估</th>
    </tr>
    <tr>
        <th id="new2" width="110px;" class="person">反欺诈</th>
    </tr>
    <tr>
        <th id="new3" width="110px;" class="person">禁止项</th>
    </tr>
</table>


<script>

	var id = "<?php echo $id ?>";
	$(function() {

		getTree('信用评估', $("#data"));
        getTree('反欺诈', $("#data2"));
		getTree('禁止项', $("#data3"));

        getTree1('414', $("#new"));
        getTree1('404', $("#new2"));
        getTree1('399', $("#new3"));

		$("#b1").on("click", function(){
			location.href = <?php echo '"' . urldecode(Url::toRoute(['rule/basic-report', 'id' => '" + id'])); ?>
		});
	});

	function getTree(tree_name, dom){
		$.getJSON(
			//"<?php //echo Url::toRoute('rule-json/reports-mongo'); ?>//",
			{
				node_name : tree_name,
				id : id
 			},
 			function(ret){
 				$node = $("<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\"></td>")
 				$node.jstree({
					'core' : {
						'data' : [simplyTree(ret.data)]
					}
				});
				dom.after($node);
 			}
		)
	}

    function getTree1(tree_id, dom){
        $.getJSON(
            //"<?php //echo Url::toRoute('rule-json/new-report-value'); ?>//",
            {
                node_id : tree_id,
                id : id
            },
            function(ret){
                $node = $("<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\"></td>")
                $node.jstree({
                    'core' : {
                        'data' : [simplyTree(ret.data)]
                    }
                });
                dom.after($node);
                console.log(ret.data['text']);
            }
        )
    }




	function scorePerson(ob){
        $(ob).css("background", "#ccc").text("计算中");
		$.post(
			"<?php echo Url::toRoute('rule-json/score-person'); ?>",
			{
				id : id
			},
			function(ret){
				if (ret.code == 0) {
					location.reload();
				}else{
                    alert(ret.message);
                    $(ob).text("重新计算");
                }
			},
            'json'
		)
	}

    function simplyTree(tree){
        var children = [];

        function getChildren (tree) {
            if (!tree.children) {
                children.push(tree);
                return;
            }
            for(var i in tree.children){
                getChildren(tree.children[i]);
            }
        }
        getChildren (tree);
        tree.children = children;
        return tree;
    }
</script>
