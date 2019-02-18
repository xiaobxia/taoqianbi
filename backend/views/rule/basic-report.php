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

<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>

<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">风控模型结果</th></tr>
    <tr>
        <th width="110px;" class="person">特征结果列表</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table id="data3" style="margin-bottom: 0px" class="table">

            </table>
        </td>
    </tr>
</table>


<script>

	var id = "<?php echo $id ?>";

	$(function() {
		getBasicReports();
	});

	function getBasicReports(){
		$.getJSON(
			"<?php echo Url::toRoute('rule-json/basic-reports-mongo'); ?>",
			{
				id : id
 			},
 			function(ret){
 				var html = "";
 				for(var i in ret.data){
 					html += "<tr><th>" + ret.data[i]['name'] + "</th>";
 					html += "<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\">" + ret.data[i]['value'] + "</td>";
 					html += "<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\">" + ret.data[i]['result']['detail'] + "</td>";
 					html += "</tr>";
 				}

 				if (html == "") {
 					html = "<tr><th onClick='checkPerson(" + id + ", getBasicReports" + ")'>获取属性特征</th></tr>";
 				}
 				$("#data3").html(html);
 			}

		)
	}

	function checkPerson(id, callback){
		$.post(
			"<?php echo Url::toRoute('rule-json/check-person'); ?>",
			{
				id : id
			},
			function(ret){
				if (ret.code == 0) {
					callback && callback();
				}
			}
		)
	}

</script>
