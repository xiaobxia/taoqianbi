
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
    <!--    <tr>-->
    <!--        <th class="partition" colspan="15">-->
    <!--            <span style="font-size:20px">--><?php //echo $info['loanPerson']['name'];?><!--的宜信至诚信息</span>-->
    <!--        </th>-->
    <!--    </tr>-->
    <tr>
        <td>
            借款信息
        </td>
        <td>
            <table>
                <?php if (!is_null($info)): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo date('Y-m-d H:i:s',$date);?> </span>
                        </td>
                    </tr>
                    <?php if(empty($info['data'])):?>
                        <tr>
                            <td>未查到相关信息</td>
                        </tr>
                    <?php else:?>

                        <tr>
                            <td>逾期信息：</td>
                            <?php if(!empty($info['data']['overdueCounts'])):?>
                                <td>
                                    180天以上逾期次数:<?php echo  isset($info['data']['overdueCounts']['overdueM6']) ? $info['data']['overdueCounts']['overdueM6'] : "";?> </br>
                                    90天以上逾期次数:<?php echo  isset($info['data']['overdueCounts']['overdueM3']) ? $info['data']['overdueCounts']['overdueM3']:"";?> </br>
                                    借款逾期次数:<?php echo isset( $info['data']['overdueCounts']['overdueTotal']) ?$info['data']['overdueCounts']['overdueTotal'] : "";?>
                                </td>
                            <?php else:?>
                                <td>无信息</td>
                            <?php endif;?>
                        </tr>
                        <tr>
                            <td>其他信息：</td>
                            <?php if(!empty($info['data']['queryStatistics'])):?>
                                <td>
                                    其他机构查询次数:<?php echo  isset($info['data']['queryStatistics']['timesByOtherOrg'])?$info['data']['queryStatistics']['timesByOtherOrg']:"暂无数据";?> </br>
                                    其他查询机构数:<?php echo  isset($info['data']['queryStatistics']['otherOrgCount'])?$info['data']['queryStatistics']['otherOrgCount']:"暂无数据";?> </br>
                                    本机构查询次数:<?php echo  isset($info['data']['queryStatistics']['timesByCurrentOrg']) ? $info['data']['queryStatistics']['timesByCurrentOrg']:"暂无数据";?></br>
                                    违约概率 <?php echo  isset($info['data']['contractBreakRate'])? $info['data']['contractBreakRate'] :"暂无数据";?></br>
                                    机构类型:<?php echo isset($info['data']['queryHistory']['orgType']) ? $info['data']['queryHistory']['orgType'] : "未知"; ?> </br>
                                    查询时间:<?php echo isset($info['data']['queryHistory']['time']) ? $info['data']['queryHistory']['time'] : "未知"; ?> </br>
                                    机构代号:<?php echo isset($info['data']['queryHistory']['orgName']) ? $info['data']['queryHistory']['orgName'] : "未知"; ?></br>
                                    致诚评分 <?php echo  isset($info['data']['zcCreditScore']) ? $info['data']['zcCreditScore']: "暂无数据";?>
                                </td>
                            <?php endif;?>


                        </tr>
                        <tr>
                            <td>借款信息：</td>
                            <?php if(!empty($info['data']['loanRecords'])):?>
                                <td>
                                    <table>
                                        <tr>
                                            <td>借款时间</td>
                                            <td>借款金额</td>
                                            <td>审批结果</td>
                                            <td>还款状态</td>
                                            <td>借款类型</td>
                                            <td>逾期金额</td>
                                            <td>逾期情况</td>
                                        </tr>
                                        <?php foreach($info['data']['loanRecords'] as $v):?>
                                            <tr>
                                                <td><?php echo isset($v['loanDate'])?$v['loanDate']:"";?></td>
                                                <td><?php echo isset($v['loanAmount'])?$v['loanAmount']:"";?></td>
                                                <td><?php  if(isset($v['approvalStatusCode']) && $v['approvalStatusCode'] == '201'){echo '审核中';}elseif($v['approvalStatusCode'] == '202'){echo "批贷已放款";}elseif($v['approvalStatusCode'] == '203'){echo '拒贷';}elseif($v['approvalStatusCode']=='204'){echo '客户放弃';}else{echo '未知';} ;?></td>
                                                <td><?php if(isset($v['loanStatusCode']) && $v['loanStatusCode'] == '301'){echo '正常';}elseif($v['loanStatusCode']=='302'){echo '逾期';}elseif($v['loanStatusCode']=='303'){echo '结清';}else{echo '未知';};?></td>
                                                <td><?php if(isset($v['loanTypeCode']) && $v['loanTypeCode'] =='21'){echo '信用';}elseif($v['loanTypeCode'] == '22'){echo '抵押';}elseif($v['loanTypeCode'] == '23'){echo '担保';}else{echo '未知';} ?></td>
                                                <td><?php echo isset($v['overdueAmount'])?$v['overdueAmount'] :"0";?></td>
                                                <td><?php echo $v['overdueStatus'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                    </table>
                                </td>

                            <?php else:?>
                                <td>无信息</td>
                            <?php endif;?>
                        </tr>
                        <tr>
                            <td>风险名单：</td>
                            <?php if(!empty($info['data']['riskResults'])):?>
                                <td>
                                    <table>
                                        <tr>
                                            <td>命中内容</td>
                                            <td>命中码项</td>
                                            <td>风险类别</td>
                                            <td>风险明细</td>
                                            <td>风险最近时间</td>

                                        </tr>
                                        <?php foreach($info['data']['riskResults'] as $v):?>
                                            <tr>
                                                <td><?php  if($v['riskItemTypeCode'] == '101010'){echo'证件号';};?></td>
                                                <td><?php echo $v['riskItemValue'];?></td>
                                                <td><?php echo $v['riskTypeCode'];?></td>
                                                <td><?php echo $v['riskDetail'];?></td>
                                                <td><?php echo $v['riskTime'];?></td>

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
                            <a onclick="getLoanInfo(<?php echo $order_id;?>);" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                <?php endif ?>

            </table>
        </td>
    </tr>

<!--    <tr>-->
<!--        <td>-->
<!--            风险名单-->
<!--        </td>-->
<!--        <td>-->
<!--            <table>-->
<!--                --><?php //if (! is_null($info['data']['risk_list'])): ?>
<!--                    <tr>-->
<!--                        <td>-->
<!--                            <span style="color:black;">数据更新于 --><?php //echo date('Y-m-d H:i:s',$info['risk_list']['updated_at']);?><!-- </span>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                    <tr>-->
<!--                        <td>命中项</td>-->
<!--                        <td>命中内容</td>-->
<!--                        <td>风险类别</td>-->
<!--                        <td>风险来源</td>-->
<!--                        <td>风险发生时间(最近)</td>-->
<!--                    </tr>-->
<!--                    --><?php //if(empty($info['risk_list']['data'])):?>
<!--                        <tr>-->
<!--                            <td colspan="5">无信息</td>-->
<!--                        </tr>-->
<!--                    --><?php //else:?>
<!--                        --><?php //$risk = json_decode($info['risk_list']['data'],true);?>
<!--                        --><?php //if(empty($risk['riskItems'])):?>
<!--                            <tr>-->
<!--                                <td colspan="5">无信息</td>-->
<!--                            </tr>-->
<!--                        --><?php //else:?>
<!--                            --><?php //foreach($risk['riskItems'] as $v):?>
<!--                                <tr>-->
<!--                                    <td>--><?php //echo $v['riskItemType'];?><!--</td>-->
<!--                                    <td>--><?php //echo $v['riskItemValue'];?><!--</td>-->
<!--                                    <td>--><?php //echo $v['riskType'];?><!--</td>-->
<!--                                    <td>--><?php //echo $v['source'];?><!--</td>-->
<!--                                    <td>--><?php //echo $v['riskTime'];?><!--</td>-->
<!--                                </tr>-->
<!--                            --><?php //endforeach;?>
<!--                        --><?php //endif;?>
<!---->
<!--                    --><?php //endif;?>
<!---->
<!---->
<!---->
<!--                --><?php //endif ?>
<!---->
<!--            </table>-->
<!--        </td>-->
<!--    </tr>-->




            <div id="bg"></div>
            <div id="show">
                <div id="content"></div>
                <div id="close" onclick="hideDiv()">关闭</div>
            </div>

            <script>


                function getLoanInfo(order_id){
                if(!confirmMsg('确认获取')){
                    return false;
                }
                    var url = '<?php echo Url::toRoute(['yxzc/get-info-new']);?>';
                    var params = {
                        order_id : order_id
                    };
                    $.get(url,params,function(data){
                        if(data.code == 0){
//
                            alert('获取成功');
                            location.reload(true);
                            //  }
                        }else{

                            alert(data.message);


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

