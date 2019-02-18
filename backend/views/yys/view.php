    <?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\loanPerson;
use common\helpers\Url;
use common\models\CreditZmop;

?>
<style>
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table td{
        border:1px solid darkgray;

    }
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的运营商基本报告</span>
            <span style="color:red">数据更新时间：<?php echo empty($creditYys['updated_at'])?'未获取报表':date('Y-m-d H:i:s',$creditYys['updated_at']);?></span>
        </th>
    </tr>
    <!--
    <tr>
        <td>
            <a style="color:red" onclick="getJxlInfo(<?php echo $loanPerson['id'];?>)" href="JavaScript:;">点击获取报表</a>
        </td>
    </tr>
    -->
</table>
<?php if(empty($data)):?>
    暂无报告
<?php else:?>

    <?php if($type == 1):?>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">数据来源</span>
            </th>
        </tr>
        <?php if(!empty($data['data_source'])):?>
            <tr>
                <th>数据源标识</th>
                <th>数据源名称</th>
                <th>账号名称</th>
                <th>数据类型</th>
                <th>数据类型名称</th>
                <th>数据有效性</th>
                <th>数据可靠性</th>
                <th>绑定时间</th>
            </tr>
            <?php foreach($data['data_source'] as $item):?>
                <tr>
                    <td><?php echo $item['key'];?></td>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo $item['account'];?></td>
                    <td><?php echo $item['category_name'];?></td>
                    <td><?php echo $item['category_value'];?></td>
                    <td><?php echo $item['status'];?></td>
                    <td><?php echo $item['reliability'];?></td>
                    <td><?php echo $item['binding_time'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">行为检测</span>
            </th>
        </tr>
        <?php if(!empty($data['behavior_check'])):?>
            <tr>
                <th>分析点</th>
                <th>检查结果</th>
                <th>证据</th>
                <th>标记</th>
            </tr>
            <?php foreach($data['behavior_check'] as $item):?>
                <tr>
                    <td><?php echo $item['check_point'];?></td>
                    <td><?php echo $item['result'];?></td>
                    <td><?php echo $item['evidence'];?></td>
                    <td>
                        <?php if($item['score'] == 0):?>
                            无数据
                        <?php elseif($item['score'] == 1): ?>
                            通过
                        <?php elseif($item['score'] == 2): ?>
                            不通过
                        <?php endif;?>
                    </td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">联系人信息</span>
            </th>
        </tr>
        <?php if(!empty($data['collection_contact'])):?>
            <tr>
                <th>联系人姓名</th>
                <th>最早出现时间</th>
                <th>最晚出现时间</th>
                <th>电商送货总数</th>
                <th>电商送货总金额</th>
                <th>电话号码</th>
                <th>号码归属地</th>
                <th>呼叫次数</th>
                <th>呼叫时长（分钟）</th>
                <th>呼出次数</th>
                <th>呼入次数</th>
                <th>短信条数</th>
                <th>最早沟通时间</th>
                <th>最晚沟通时间</th>
            </tr>
            <?php foreach($data['collection_contact'] as $item):?>
                <tr>
                    <td><?php echo $item['contact_name'];?></td>
                    <td><?php echo $item['begin_date'];?></td>
                    <td><?php echo $item['end_date'];?></td>
                    <td><?php echo $item['total_count'];?></td>
                    <td><?php echo $item['total_amount'];?></td>
                    <td><?php echo $item['contact_details'][0]['phone_num'];?></td>
                    <td><?php echo $item['contact_details'][0]['phone_num_loc'];?></td>
                    <td><?php echo $item['contact_details'][0]['call_cnt'];?></td>
                    <td><?php echo $item['contact_details'][0]['call_len'];?></td>
                    <td><?php echo $item['contact_details'][0]['call_out_cnt'];?></td>
                    <td><?php echo $item['contact_details'][0]['call_in_cnt'];?></td>
                    <td><?php echo $item['contact_details'][0]['sms_cnt'];?></td>
                    <td><?php echo $item['contact_details'][0]['trans_start'];?></td>
                    <td><?php echo $item['contact_details'][0]['trans_end'];?></td>

                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">出行数据分析</span>
            </th>
        </tr>
        <?php if(!empty($data['trip_consume'])):?>
            <tr>
                <th>下单时间</th>
                <th>机票消费</th>
                <th>火车消费</th>
                <th>酒店消费</th>
                <th>月度消费频次(/人次)</th>
            </tr>
            <?php foreach($data['trip_consume'] as $item):?>
                <tr>
                    <td><?php echo $item['order_date'];?></td>
                    <td><?php echo $item['flight_spend'];?></td>
                    <td><?php echo $item['train_spend'];?></td>
                    <td><?php echo $item['hotel_spend'];?></td>
                    <td><?php echo $item['count'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">电商消费</span>
            </th>
        </tr>
        <?php if(!empty($data['ebusiness_expense'])):?>
            <tr>
                <th>月份</th>
                <th>全部消费金额(元)</th>
                <th>全部消费次数</th>
            </tr>
            <?php foreach($data['ebusiness_expense'] as $item):?>
                <tr>
                    <td><?php echo $item['trans_mth'];?></td>
                    <td><?php echo $item['all_amount'];?></td>
                    <td><?php echo $item['all_count'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">个人信息</span>
            </th>
        </tr>
        <?php if(!empty($data['person'])):?>
            <tr>
                <th>真实姓名</th>
                <th>身份证号码</th>
                <th>性别</th>
                <th>星座</th>
                <th>年龄</th>
                <th>出生省份</th>
                <th>出生城市</th>
                <th>出生县</th>
                <th>籍贯省份</th>
            </tr>
            <tr>
                <td><?php echo $data['person']['real_name'];?></td>
                <td><?php echo $data['person']['id_card_num'];?></td>
                <td><?php echo $data['person']['gender'];?></td>
                <td><?php echo $data['person']['sign'];?></td>
                <td><?php echo $data['person']['age'];?></td>
                <td><?php echo $data['person']['province'];?></td>
                <td><?php echo $data['person']['city'];?></td>
                <td><?php echo $data['person']['region'];?></td>
                <td><?php echo $data['person']['state'];?></td>
            </tr>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">常用服务</span>
            </th>
        </tr>
        <?php if(!empty($data['main_service'])):?>
            <tr>
                <th>企业名称</th>
                <th>服务企业类型</th>
                <th>总互动次数</th>
                <th>月互动次数</th>
            </tr>
            <?php foreach($data['main_service'] as $item):?>
                <tr>
                    <td><?php echo $item['company_name'];?></td>
                    <td><?php echo $item['company_type'];?></td>
                    <td><?php echo $item['total_service_cnt'];?></td>
                    <td>
                        <?php foreach($item['service_details'] as $val):?>
                            <span style="padding:2px;background-color: #d9edf7;color: #0283c2;"><?php echo $val['interact_mth'];?>:<?php echo $val['interact_cnt'];?>次</span>
                        <?php endforeach;?>
                    </td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">联系人所在地区分析</span>
            </th>
        </tr>
        <?php if(!empty($data['contact_region'])):?>
            <tr>
                <th>地区名称</th>
                <th>号码数量</th>
                <th>电话呼入次数</th>
                <th>电话呼出次数</th>
                <th>电话呼入时间(分钟)</th>
                <th>电话呼出时间(分钟)</th>
                <th>平均电话呼入时间(分钟)</th>
                <th>平均电话呼出时间(分钟)</th>
                <th>电话呼入次数百分比</th>
                <th>电话呼出次数百分比</th>
                <th>电话呼入时间百分比</th>
                <th>电话呼出时间百分比</th>
            </tr>
            <?php foreach($data['contact_region'] as $item):?>
                <tr>
                    <td><?php echo $item['region_loc'];?></td>
                    <td><?php echo $item['region_uniq_num_cnt'];?></td>
                    <td><?php echo $item['region_call_in_cnt'];?></td>
                    <td><?php echo $item['region_call_out_cnt'];?></td>
                    <td><?php echo round($item['region_call_in_time']);?></td>
                    <td><?php echo round($item['region_call_out_time']);?></td>
                    <td><?php echo round($item['region_avg_call_in_time']);?></td>
                    <td><?php echo round($item['region_avg_call_out_time']);?></td>
                    <td><?php echo round($item['region_call_in_cnt_pct']*100,2);?>%</td>
                    <td><?php echo round($item['region_call_out_cnt_pct']*100,2);?>%</td>
                    <td><?php echo round($item['region_call_in_time_pct']*100,2);?>%</td>
                    <td><?php echo round($item['region_call_out_time_pct']*100,2);?>%</td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">信息验真</span>
            </th>
        </tr>
        <?php if(!empty($data['application_check'])):?>
            <tr>
                <th>分析点</th>
                <th>检查结果</th>
                <th>证据</th>
                <th>标记</th>
            </tr>
            <?php foreach($data['application_check'] as $item):?>
                <tr>
                    <td><?php echo $item['check_point'];?></td>
                    <td><?php echo $item['result'];?></td>
                    <td><?php echo $item['evidence'];?></td>
                    <td>
                        <?php if($item['score'] == 0):?>
                            无数据
                        <?php elseif($item['score'] == 1): ?>
                            通过
                        <?php elseif($item['score'] == 2): ?>
                            不通过
                        <?php endif;?>
                    </td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">收货地址</span>
            </th>
        </tr>
        <?php if(!empty($data['deliver_address'])):?>
            <tr>
                <th>收货地址</th>
                <th>经度</th>
                <th>纬度</th>
                <th>地址类型</th>
                <th>开始送货时间</th>
                <th>结束送货时间</th>
                <th>总送货金额(元)</th>
                <th>总送货次数</th>
                <th>收货人/手机号/金额/次数</th>
            </tr>
            <?php foreach($data['deliver_address'] as $item):?>
                <tr>
                    <td><?php echo $item['address'];?></td>
                    <td><?php echo round($item['lng'],2);?></td>
                    <td><?php echo round($item['lat'],2);?></td>
                    <td><?php echo $item['predict_addr_type'];?></td>
                    <td><?php echo $item['begin_date'];?></td>
                    <td><?php echo $item['end_date'];?></td>
                    <td><?php echo $item['total_amount'];?></td>
                    <td><?php echo $item['total_count'];?></td>
                    <td>
                        <?php foreach($item['receiver'] as $val):?>
                            <span style="padding:2px;background-color: #d9edf7;color: #0283c2;">
                                <?php echo $val['name'];?>
                                /<?php echo implode(',',$val['phone_num_list']);?>
                                /<?php echo $val['amount'];?>
                                /<?php echo $val['count'];?>
                            </span>
                        <?php endforeach;?>
                    </td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">出行数据分析</span>
            </th>
        </tr>
        <?php if(!empty($data['trip_info'])):?>
            <tr>
                <th>出发地</th>
                <th>目的地</th>
                <th>出行工具</th>
                <th>同行人</th>
                <th>出行时间类型</th>
                <th>出行开始时间</th>
                <th>出行结束时间</th>
                <th>数据来源</th>
            </tr>
            <?php foreach($data['trip_info'] as $item):?>
                <tr>
                    <td><?php echo $item['trip_leave'];?></td>
                    <td><?php echo $item['trip_dest'];?></td>
                    <td><?php echo implode(',',$item['trip_transportation']);?></td>
                    <td><?php echo implode(',',$item['trip_person']);?></td>
                    <td><?php echo $item['trip_type'];?></td>
                    <td><?php echo $item['trip_start_time'];?></td>
                    <td><?php echo $item['trip_end_time'];?></td>
                    <td><?php echo implode(',',$item['trip_data_source']);?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">运营商数据整理</span>
            </th>
        </tr>
        <?php if(!empty($data['cell_behavior'])):?>
            <tr>
                <th>运营商</th>
                <th>运营商（中文）</th>
                <th>号码</th>
                <th>归属地</th>
                <th>月份</th>
                <th>呼叫次数</th>
                <th>主叫次数</th>
                <th>主叫时间（分）</th>
                <th>被叫次数</th>
                <th>被叫时间（分）</th>
                <th>流量</th>
                <th>短信数目</th>
                <th>话费消费(元)</th>
            </tr>
            <?php foreach($data['cell_behavior'] as $v):?>
                <?php foreach($v['behavior'] as $item):?>
                    <tr>
                        <td><?php echo $item['cell_operator'];?></td>
                        <td><?php echo $item['cell_operator_zh'];?></td>
                        <td><?php echo $item['cell_phone_num'];?></td>
                        <td><?php echo $item['cell_loc'];?></td>
                        <td><?php echo $item['cell_mth'];?></td>
                        <td><?php echo $item['call_cnt'];?></td>
                        <td><?php echo $item['call_out_cnt'];?></td>
                        <td><?php echo round($item['call_out_time']);?></td>
                        <td><?php echo $item['call_in_cnt'];?></td>
                        <td><?php echo round($item['call_in_time']);?></td>
                        <td><?php echo $item['net_flow'];?></td>
                        <td><?php echo $item['sms_cnt'];?></td>
                        <td><?php echo $item['total_amount'];?></td>

                    </tr>
                <?php endforeach;?>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>
<?php elseif($type == 2):?>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">通话记录</span>
            </th>
        </tr>
        <?php if(!empty($data['contact_list'])):?>
            <tr>
                <th>号码</th>
                <th>号码归属地</th>
                <th>号码标注</th>
                <th>需求类别</th>
                <th>通话次数</th>
                <th>通话时长(分)</th>
                <th>呼出次数</th>
                <th>呼出时间(分)</th>
                <th>呼入次数</th>
                <th>呼入时间(分)</th>
                <th>关系推测</th>
                <th>最近一周联系次数</th>
                <th>最近一月联系次数</th>
                <th>最近三月联系次数</th>
                <th>三个月以上联系次数</th>
                <th>凌晨联系次数</th>
                <th>上午联系次数</th>
                <th>中午联系次数</th>
                <th>下午联系次数</th>
                <th>晚上联系次数</th>
                <th>是否全天联系</th>
                <th>周中联系次数</th>
                <th>周末联系次数</th>
                <th>节假日联系次数</th>
            </tr>
            <?php foreach($data['contact_list'] as $item):?>
                <tr>
                    <td><?php echo $item['phone_num'];?></td>
                    <td><?php echo $item['phone_num_loc'];?></td>
                    <td><?php echo $item['contact_name'];?></td>
                    <td><?php echo $item['needs_type'];?></td>
                    <td><?php echo $item['call_cnt'];?></td>
                    <td><?php echo round($item['call_len']);?></td>
                    <td><?php echo $item['call_out_cnt'];?></td>
                    <td><?php echo round($item['call_out_len']);?></td>
                    <td><?php echo $item['call_in_cnt'];?></td>
                    <td><?php echo round($item['call_in_len']);?></td>
                    <td><?php echo $item['p_relation'];?></td>
                    <td><?php echo $item['contact_1w'];?></td>
                    <td><?php echo $item['contact_1m'];?></td>
                    <td><?php echo $item['contact_3m'];?></td>
                    <td><?php echo $item['contact_3m_plus'];?></td>
                    <td><?php echo $item['contact_early_morning'];?></td>
                    <td><?php echo $item['contact_morning'];?></td>
                    <td><?php echo $item['contact_noon'];?></td>
                    <td><?php echo $item['contact_afternoon'];?></td>
                    <td><?php echo $item['contact_night'];?></td>
                    <td><?php echo $item['contact_all_day'];?></td>
                    <td><?php echo $item['contact_weekday'];?></td>
                    <td><?php echo $item['contact_weekend'];?></td>
                    <td><?php echo $item['contact_holiday'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>
        <?php endif;?>
<?php endif;?>


<script>
    function getMgInfo(id){

        var url = '<?php echo Url::toRoute(['jxl/get-miguan-info']);?>';
        var params = {
          id : id
        };
        var ret = confirmMsg('确认获取');
        if(! ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert(data.message);
                location.reload(true);
            }else{
                alert(data.message);
            }
        },'json')
    }

</script>


