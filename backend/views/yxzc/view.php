<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\helpers\Url;
use common\models\CreditYxzc;
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
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的宜信至诚信息</span>
        </th>
    </tr>
    <tr>
        <td>
            借款信息
        </td>
        <td>
            <table>
                <?php if (! is_null($info['loan_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo date('Y-m-d H:i:s',$info['loan_info']['updated_at']);?> </span>
                        </td>
                    </tr>
                    <?php if(empty($info['loan_info']['data'])):?>
                        <tr>
                            <td>未查到学历信息</td>
                        </tr>
                    <?php else:?>
                    <?php $loan_info = json_decode($info['loan_info']['data'],true);?>
                        <tr>
                            <td>逾期信息：</td>
                            <?php if(!empty($loan_info['overdue'])):?>
                                <td>
                                    180天以上逾期次数:<?php echo  $loan_info['overdue']['180overdueTimes'];?> </br>
                                    90天以上逾期次数:<?php echo  $loan_info['overdue']['90overdueTimes'];?> </br>
                                    借款逾期次数:<?php echo  $loan_info['overdue']['overdueTimes'];?>
                                </td>
                            <?php else:?>
                            <td>无信息</td>
                        <?php endif;?>
                        </tr>
                        <tr>
                            <td>借款信息：</td>
                            <?php if(!empty($loan_info['loanRecords'])):?>
                                <td>
                                    <table>
                                        <tr>
                                            <td>借款时间</td>
                                            <td>借款金额</td>
                                            <td>审批结果</td>
                                            <td>目前状态</td>
                                        </tr>
                                        <?php foreach($loan_info['loanRecords'] as $v):?>
                                        <tr>
                                            <td><?php echo $v['loanTime'];?></td>
                                            <td><?php echo $v['amount'];?></td>
                                            <td><?php echo $v['approveStatus'];?></td>
                                            <td><?php echo $v['currentStatus'];?></td>
                                        </tr>
                                        <?php endforeach;?>
                                    </table>
                                </td>

                            <?php else:?>
                                <td>无信息</td>
                            <?php endif;?>
                        </tr>

                    <?php endif;?>

                <?php else: ?>
                    <tr>
                        <td>
                            <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYxzc::TYPE_LOAN_INFO;?>)" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                <?php endif ?>

            </table>
        </td>
    </tr>

    <tr>
        <td>
            风险名单
        </td>
        <td>
            <table>
                <?php if (! is_null($info['risk_list'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo date('Y-m-d H:i:s',$info['risk_list']['updated_at']);?> </span>
                        </td>
                    </tr>
                    <tr>
                        <td>命中项</td>
                        <td>命中内容</td>
                        <td>风险类别</td>
                        <td>风险来源</td>
                        <td>风险发生时间(最近)</td>
                    </tr>
                    <?php if(empty($info['risk_list']['data'])):?>
                        <tr>
                            <td colspan="5">无信息</td>
                        </tr>
                    <?php else:?>
                        <?php $risk = json_decode($info['risk_list']['data'],true);?>
                        <?php if(empty($risk['riskItems'])):?>
                            <tr>
                                <td colspan="5">无信息</td>
                            </tr>
                        <?php else:?>
                            <?php foreach($risk['riskItems'] as $v):?>
                                <tr>
                                    <td><?php echo $v['riskItemType'];?></td>
                                    <td><?php echo $v['riskItemValue'];?></td>
                                    <td><?php echo $v['riskType'];?></td>
                                    <td><?php echo $v['source'];?></td>
                                    <td><?php echo $v['riskTime'];?></td>
                                </tr>
                            <?php endforeach;?>
                        <?php endif;?>

                    <?php endif;?>


                <?php else: ?>
                    <tr>
                        <td>
                            <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYxzc::TYPE_RISK_LIST;?>)" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                <?php endif ?>

            </table>
        </td>
    </tr>

    <tr>
        <td>
            致诚分
        </td>
        <td>
            <table>
                <?php if (! is_null($info['zc_score'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo date('Y-m-d H:i:s',$info['zc_score']['updated_at']);?> </span>
                        </td>
                    </tr>
                    <?php $score = json_decode($info['zc_score']['data'],true);?>
                    <tr>
                        <td>致诚分:</td>
                        <td>
                            <?php if(empty($info['zc_score']['data'])):?>
                            无信息
                            <?php else:?>
                                <?php echo $score['creditScore'];?>
                            <?php endif;?>
                        </td>
                    </tr>
                    <tr>
                        <td>违约概率:</td>
                        <td>
                            <?php if(empty($info['zc_score']['data'])):?>
                                无信息
                            <?php else:?>
                                <?php echo $score['rate'];?>
                            <?php endif;?>
                        </td>
                    </tr>
                    <tr>

                    </tr>
                <?php else: ?>
                    <tr>
                        <td>
                            <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYxzc::TYPE_ZC_SCORE;?>)" href="javascript:;">点击查询最新数据</a>

                        </td>
                    </tr>
                <?php endif ?>

            </table>
        </td>
    </tr>

    <tr>
        <td>
            被查询情况
        </td>
        <td>
            <table>
                <?php if (! is_null($info['query_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo date('Y-m-d H:i:s',$info['query_info']['updated_at']);?> </span>
                        </td>
                    </tr>
                    <?php if(empty($info['query_info']['data'])):?>
                        <tr>
                            <td>查询统计：</td>
                            <td>无信息</td>
                        </tr>
                        <tr>
                            <td>查询记录：</td>
                            <td>无信息</td>
                        </tr>
                    <?php else:?>
                        <?php $query_info = json_decode($info['query_info']['data'],true);?>
                        <tr>
                            <td>查询统计：</td>
                            <td>
                                <table>
                                    <tr>
                                        <td>其他机构查询次数:</td>
                                        <td><?php echo $query_info['queryStastics']['timesByOtherOrg'];?></td>
                                    </tr>
                                    <tr>
                                        <td>其它机构数:</td>
                                        <td><?php echo $query_info['queryStastics']['otherOrgCount'];?></td>
                                    </tr>
                                    <tr>
                                        <td>本机构查询次数:</td>
                                        <td><?php echo $query_info['queryStastics']['timesByCurrentOrg'];?></td>
                                    </tr>
                                    <tr>
                                        <td>其它机构类别及类别对应机构数量:</td>
                                        <td>
                                            <?php if(empty($query_info['queryStastics']['eachOrgTypeCount'])):?>
                                                无信息
                                            <?php else:?>
                                                <?php foreach($query_info['queryStastics']['eachOrgTypeCount'] as $v):?>
                                                    机构类别:<?php echo $v['orgType'];?> </br>
                                                    对应类别的机构数:<?php echo $v['orgCount'];?>
                                                <?php endforeach;?>
                                            <?php endif;?>
                                        </td>


                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endif;?>


                <?php else: ?>
                    <tr>
                        <td>
                            <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYxzc::TYPE_QUERY_INFO;?>)" href="javascript:;">点击查询最新数据</a>

                        </td>
                    </tr>
                <?php endif ?>
            </table>
        </td>
    </tr>

</table>

<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script>

    var reminder_count = 0;
    var reminder_str;

    function getInfo(product_id,order_id,type,reminder){
        if(!reminder){
            if(!confirmMsg('确认获取')){
                return false;
            }
        }

        var url = '<?php echo Url::toRoute(['yxzc/get-info']);?>';
        var params = {
            type : type,
            product_id : product_id,
            order_id : order_id
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



    function getAllInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();
        $.ajaxSetup({
            async: false
        });
        var type = [1,2,3,4];
        for (var i in type){
            getInfo(id,type[i],true);
        }
        document.getElementById("close").style.display ="block";
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

