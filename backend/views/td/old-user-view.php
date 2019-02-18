<?php
use common\helpers\Url;

?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
    .son td{border:grey solid 1px}
    .son {text-align: center}
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" >
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的同盾信息历史查询记录</span>
    </th>
    <th>请选择历史查询时间:
            <select name="" class="selectShow">
                <?php foreach ($creditTd as $key=>$val): ?>
                    <option value="<?php echo $key ?>"><?php echo date('Y-m-d H:i:s',$val['created_at']) ?></option>
                <?php endforeach; ?>
            </select>
        </th>
    </tr>
</table>
    <?php if(empty($creditTd)):?>
        <table class="tb tb2 fixpadding">
            <tr>
                <td>无历史查询记录</td>
            </tr>
        </table>
    <?php else:?>
        <?php
        $i = 0;
        foreach($creditTd as $val):
        $data = json_decode($val['data'],true);
        ?>
<div class="selectShow" id="tb_<?php echo $i++; ?>" >
<table class="tb tb2 fixpadding">
        <tr><td style="color:red">报告历史查询时间：<?php echo date('Y-m-d H:i:s',$val['created_at'])?></td></tr>
        <tr>

            <td width="500">报告信息</td>
            <td>
                <table class="son">
                    <tr>
                        <td>报表更新时间</td>
                        <td>风险评分（数字越大风险越高,0-19通过、20-79审核、80以上拒绝）</td>
                        <td>审核建议</td>
                    </tr>
                    <tr>
                        <td><?php echo date('Y-m-d',floor($data['report_time']/1000));?></td>
                        <td><?php echo $data['final_score'];?></td>
                        <td><?php echo $data['final_decision'];?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php foreach($data['risk_items'] as $v):?>
            <tr>
                <td width="500"><?php echo $v['item_name'];?></td>
                <td>
                    <table class="son">
                        <tr>
                            <td>风险等级</td>
                            <td>风险分类</td>
                            <td>风险名称</td>
                            <?php if(isset($v['item_detail']['discredit_times'])):?>
                                <td>失信次数</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['overdue_details'])):?>
                                <td>逾期详情</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_count'])):?>
                                <td>多平台借贷总数</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_detail'])):?>
                                <td>借贷详情</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['high_risk_areas'])):?>
                                <td>高风险区域</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['hit_list_datas'])):?>
                                <td>中介关键词</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['court_details'])):?>
                                <td>法院详情信息列表</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['fraud_type'])):?>
                                <td>风险类型</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['frequency_detail_list'])):?>
                                <td>频度详情</td>
                            <?php endif;?>
                        </tr>
                        <tr>
                            <td><?php echo $v['risk_level'];?></td>
                            <td><?php echo $v['group'];?></td>
                            <td><?php echo $v['item_name'];?></td>
                            <?php if(isset($v['item_detail']['discredit_times'])):?>
                                <td><?php echo $v['item_detail']['discredit_times'];?></td>
                            <?php endif;?>

                            <?php if(isset($v['item_detail']['overdue_details']) ):?>
                            <td>
                               <?php foreach($v['item_detail']['overdue_details'] as $val):?>
                                    <p>逾期天数：<?php echo isset($val['overdue_day'])?$val['overdue_day']:'';?>,逾期金额(元):<?php echo isset($val['overdue_amount']) ?$val['overdue_amount']:'';?></p>
                                <?php endforeach;?>
                            </td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_count'])):?>
                                <td><?php echo $v['item_detail']['platform_count'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_detail'])):?>
                                <td><?php echo implode(',',$v['item_detail']['platform_detail']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['high_risk_areas'])):?>
                                <td><?php echo implode(',',$v['item_detail']['high_risk_areas']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['hit_list_datas'])):?>
                                <td><?php echo implode(',',$v['item_detail']['hit_list_datas']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['court_details'])):?>
                                <td>
                                    <table>
                                        <tr>
                                            <td>性别</td>
                                            <td>欺诈类型</td>
                                            <td>归档时间</td>
                                            <td>执行号</td>
                                            <td>详情</td>
                                            <td>省</td>
                                            <td>执行状态</td>
                                            <td>姓名</td>
                                            <td>职务</td>
                                            <td>案例号</td>
                                            <td>法院名</td>
                                            <td>年龄</td>
                                        </tr>
                                        <?php foreach($v['item_detail']['court_details'] as $v):?>
                                            <tr>
                                                <td><?php echo $v['gender'];?></td>
                                                <td><?php echo $v['fraud_type'];?></td>
                                                <td><?php echo $v['filing_time'];?></td>
                                                <td><?php echo $v['execution_number'];?></td>
                                                <td><?php echo $v['discredit_detail'];?></td>
                                                <td><?php echo $v['province'];?></td>
                                                <td><?php echo $v['execution_status'];?></td>
                                                <td><?php echo $v['name'];?></td>
                                                <td><?php echo $v['duty'];?></td>
                                                <td><?php echo $v['case_number'];?></td>
                                                <td><?php echo $v['court_name'];?></td>
                                                <td><?php echo $v['age'];?></td>
                                            </tr>
                                        <?php endforeach;?>
                                    </table>

                                </td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['fraud_type'])):?>
                                <td><?php echo $v['item_detail']['fraud_type'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['frequency_detail_list'])):?>
                                <td>
                                    <?php foreach($v['item_detail']['frequency_detail_list'] as $value):?>
                                        <?php echo $value['detail'];?></br>
                                    <?php endforeach;?>
                                </td>
                            <?php endif;?>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php endforeach;?>
        </table>
    </div>
<?php endforeach;?>

 <?php endif;?>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>


<script>
    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto();
    });

    $(".selectShow").change(function(){
          checkauto();
    })

    function checkauto(){
         var show_id = $(".selectShow option:selected").val();
          console.log(show_id);
          $(".selectShow option").each(function(){
              if($(this).val() == show_id) {
                 $("#tb_"+show_id).show();   //当前的显示
              } else {
                 $("#tb_"+$(this).val()).hide();
              }
          })
    }
</script>