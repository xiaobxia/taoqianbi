<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\helpers\Url;
use common\models\CreditBqs;
?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
</style>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的白骑士信息</span>
        </th>
    </tr>
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a onclick="getInfo('<?php echo $info['loanPerson']['id'];?>',<?php echo $info['type'];?>)" href="javascript:;">点击查询最新数据</a>
                    </td>
                </tr>
                <?php if(!is_null($info['report'])):?>
                <?php $data = json_decode($info['report']['data'],true);?>
                <tr>
                    <td>
                        数据更新于<?php echo date('Y-m-d H:i:s',$info['report']['updated_at']);?>
                    </td>
                </tr>
                <tr>
                    <td class="td31">决策建议</td>
                    <td><?php echo $data['finalDecision'];?></td>
                </tr>
                <tr>
                    <td>策略集明细</td>
                    <?php if(empty($data['strategySet'])):?>
                        <td>无</td>
                    <?php else:?>
                        <td>
                            <table>
                                <tr>
                                    <td>策略名称</td>
                                    <td>策略ID</td>
                                    <td>策略决策结果</td>
                                    <td>策略匹配模式</td>
                                    <td>策略风险系数</td>
                                    <td>策略风险类型</td>
                                    <td>规则内容明细</td>
                                </tr>
                                <?php foreach($data['strategySet'] as $v):?>
                                    <tr>
                                        <td><?php echo isset($v['strategyName']) ? $v['strategyName'] : '';?></td>
                                        <td><?php echo isset($v['strategyId']) ? $v['strategyId'] : '';?></td>
                                        <td><?php echo isset($v['strategyDecision']) ? $v['strategyDecision'] : '';?></td>
                                        <td><?php echo isset($v['strategyMode']) ? $v['strategyMode'] : '';?></td>
                                        <td><?php echo isset($v['strategyScore']) ? $v['strategyScore'] : '';?></td>
                                        <td><?php echo isset($v['riskType']) ? $v['riskType'] : '';?></td>
                                        <td>
                                            <?php if(empty($v['hitRules'])):?>
                                                无
                                            <?php else:?>
                                                <table>
                                                    <tr>
                                                        <td>规则名称</td>
                                                        <td>规则ID</td>
                                                        <td>规则风险系数</td>
                                                        <td>规则决策结果</td>
                                                        <td>备注</td>
                                                    </tr>
                                                    <?php foreach($v['hitRules'] as $j):?>
                                                        <tr>
                                                            <td><?php echo isset($j['ruleName']) ? $j['ruleName'] : '';?></td>
                                                            <td><?php echo isset($j['ruleId']) ? $j['ruleId'] : '';?></td>
                                                            <td><?php echo isset($j['score']) ? $j['score'] : '';?></td>
                                                            <td><?php echo isset($j['decision']) ? $j['decision'] : '';?></td>
                                                            <td><?php echo isset($j['memo']) ? $j['memo'] : '';?></td>
                                                        </tr>
                                                    <?php endforeach;?>
                                                </table>

                                            <?php endif;?>
                                        </td>

                                    </tr>
                                <?php endforeach;?>
                            </table>
                        </td>

                    <?php endif;?>
                </tr>
            </table>
        </td>
    </tr>

    <?php endif;?>

</table>
<br><a href="<?php echo Url::toRoute(['bqs/old-view','id'=>$info['id'],'product_id' => $info['product_id'],'order_id' => $info['order_id']]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">历史查询记录</a>

<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script>

    var reminder_count = 0;
    var reminder_str;

    function getInfo(id,type,reminder){
        if(!reminder){
            if(!confirmMsg('确认获取')){
                return false;
            }
        }

        var url = '<?php echo Url::toRoute(['bqs/get-info']);?>';
        var params = {
            id : id,
            type : type
        };
        $.get(url,params,function(data){
            if(data.code == 0){
                if(reminder){
                    insertToShow(data.message,true);
                }else{
                    alert('获取成功');
                    location.reload(true);
                }
            }else{
                if(reminder){
                    insertToShow(data.message,false);
                }else{
                    alert(data.message);
                }

            }
        },'json')
    }


    function showDiv() {
        document.body.style.overlfow='hidden';
        document.documentElement.style.overflow = 'hidden';
        document.getElementById("bg").style.display ="block";
        document.getElementById("show").style.display ="block";
    }
    function hideDiv() {
        document.body.style.overlfow='auto';
        document.documentElement.style.overflow = 'auto';
        document.getElementById("bg").style.display ="none";
        document.getElementById("show").style.display ="none";
        location.reload(true);
    }

    function insertToShow(message,code){
        if(code){
            var str = '<div style="color:darkgreen">'+message+'</div>';
        }else{
            var str = '<div style="color:red">'+message+'</div>';
        }

        $('#content').append(str);
    }


</script>

