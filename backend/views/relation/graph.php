<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/2/13
 * Time: 15:32
 */
use backend\components\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;

?>
<?php ActiveForm::begin(); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.js'); ?>"></script>
<style>
    .td24{
        width: 120px;
        font-weight: bold;
    }
</style>


<div id="main" style="width: 1000px; height:800px"></div>
    <div id="ss" ></div>
    <script src="<?php echo Url::toStatic('/echarts2/build/dist/echarts.js'); ?>"></script>
    <script type="text/javascript">

    	var data = <?php echo $data; ?>;
        require.config({
            paths : {
                echarts : "<?php echo Url::toStatic('/echarts2/build/dist'); ?>"
            }
        });
        drawRelationPic(data);



        function drawRelationPic(data){
        	var nodes = [];
        	var links = [];


        	function formatData(data){
	        	if (!data || !data.relation || data.relation.length == 0) {
	        		return;
	        	}
	        	var source = data.user_id;
	        	nodes.push({category: 0, name: source, draggable:true});
	        	for(var i in data.relation){
	        		data.relation[i]
	        		nodes.push({category: 1, name: data.relation[i]['value']});
	        		links.push({source : source, target : data.relation[i]['value'], weight : 1, name: data.relation[i]['relation_id']});
	        	}

	        	for(var i in data.friend){
	        		formatData(data.friend[i]);
	        	}
	        }

	        formatData(data);

        	require([ "echarts", "echarts/chart/force"], function(ec) {
	            var myChart = ec.init(document.getElementById('main'), 'macarons');
	            var option = {
				    title : {
				        text: '人物关系：' + data.user_id,
				        subtext: '数据来自'<?php echo APP_NAMES;?>,
				        x:'right',
				        y:'bottom'
				    },
				    tooltip : {
				        trigger: 'item',
				        formatter: '{a} : {b}'

				    },
				    toolbox: {
				        show : true,
				        feature : {
				            restore : {show: true},
				            saveAsImage : {show: true}
				        }
				    },
				    legend: {
				        x: 'left',
				        data:['人物','属性']
				    },
				    series : [
				        {
				            type:'force',
				            name : "人物关系",
				            ribbonType: false,
				            categories : [
				                {
				                    name: '人物'
				                },
				                {
				                    name: '属性',
				                    // symbol: 'diamond'
				                }
				            ],
				            itemStyle: {
				                normal: {
				                    label: {
				                        show: true,
				                        textStyle: {
				                            color: '#333'
				                        }
				                    },
				                    nodeStyle : {
				                        brushType : 'both',
				                        borderColor : 'rgba(255,215,0,0.4)',
				                        borderWidth : 1
				                    }
				                },
				                emphasis: {
				                    label: {
				                        show: false
				                        // textStyle: null      // 默认使用全局文本样式，详见TEXTSTYLE
				                    },
				                    nodeStyle : {
				                        //r: 30
				                    },
				                    linkStyle : {}
				                }
				            },
				            minRadius : 15,
				            maxRadius : 25,
				            gravity: 1.1,
				            scaling: 1.2,
				            draggable: false,
				            // linkSymbol: 'arrow',
				            steps: 10,
				            coolDown: 0.9,
				            //preventOverlap: true,
				            nodes:nodes,
				            links : links
				        }
				    ]
				};
	            myChart.setOption(option);

	            var ecConfig = require('echarts/config');
				function focus(param) {
				    var data = param.data;
				    var links = option.series[0].links;
				    var nodes = option.series[0].nodes;
				    if (
				        data.source != null
				        && data.target != null
				    ) { //点击的是边
				        var sourceNode = nodes.filter(function (n) {return n.name == data.source})[0];
				        var targetNode = nodes.filter(function (n) {return n.name == data.target})[0];
				        console.log("选中了边 " + sourceNode.name + ' -> ' + targetNode.name + ' (' + data.weight + ')');
				    } else { // 点击的是点
				        console.log("选中了" + data.name + '(' + data.value + ')');
				    }
				}
				myChart.on(ecConfig.EVENT.CLICK, focus)

				myChart.on(ecConfig.EVENT.FORCE_LAYOUT_END, function () {
				    console.log(myChart.chart.force.getPosition());
				});

	        });
        }

    </script>

<?php ActiveForm::end(); ?>