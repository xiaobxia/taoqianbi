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
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的葫芦信息</span>
        </th>
    </tr>
    <tr>
        <td>
            <a href="javascript:;" onclick="getReportId('<?php echo $loanPerson['id'];?>')">提交用户信息</a>
            <?php if(!empty($info['status']) && $info['status'] == 1): ?>
                <a style="color:red" onclick="getInfo(<?php echo $info['person_id'];?>)" href="javascript:;">点击获取所有信息</a>
            <?php endif;?>
        </td>
    </tr>
    <?php if(!empty($data)):?>
        <tr>
            <td width="500">基本信息</td>
            <td>
                <table class="son">
                    <tr>
                        <td>姓名</td>
                        <td>性别</td>
                        <td>年龄</td>
                        <td>出生日期</td>
                        <td>身份证是否有效</td>
                        <td>身份证户籍省份</td>
                        <td>身份证户籍城市</td>
                        <td>身份证户籍地区</td>
                        <td>手机运营商</td>
                        <td>手机归属地省份</td>
                        <td>手机归属地城市</td>
                        <td>身份证号记录天数</td>
                        <td>手机号记录天数</td>
                        <td>身份证最近出现时间</td>
                        <td>手机号最近出现时间</td>
                        <td>关联身份证数量</td>
                        <td>关联手机号数量</td>
                    </tr>
                    <tr>
                        <td><?php echo $data['user_name'];?></td>
                        <td><?php echo $data['user_basic']['gender'];?></td>
                        <td><?php echo $data['user_basic']['age'];?></td>
                        <td><?php echo $data['user_basic']['birthday'];?></td>
                        <td><?php echo $data['user_basic']['idcard_validate'] == 1 ? "是" : "否";?></td>
                        <td><?php echo $data['user_basic']['idcard_province'];?></td>
                        <td><?php echo $data['user_basic']['idcard_city'];?></td>
                        <td><?php echo $data['user_basic']['idcard_region'];?></td>
                        <td><?php echo $data['user_basic']['phone_operator'];?></td>
                        <td><?php echo $data['user_basic']['phone_province'];?></td>
                        <td><?php echo $data['user_basic']['phone_city'];?></td>
                        <td><?php echo $data['user_basic']['record_idcard_days'];?></td>
                        <td><?php echo $data['user_basic']['record_phone_days'];?></td>
                        <td><?php echo $data['user_basic']['last_appear_idcard'];?></td>
                        <td><?php echo $data['user_basic']['last_appear_phone'];?></td>
                        <td><?php echo $data['user_basic']['used_idcards_cnt'];?></td>
                        <td><?php echo $data['user_basic']['used_phones_cnt'];?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td width="500">黑名单</td>
            <td>
                <table class="son">
                    <tr>
                        <td>身份证是否命中黑名单</td>
                        <td>手机号是否命中黑名单</td>
                        <td>是否命中法院黑名单</td>
                        <td>是否命中网贷黑名单</td>
                        <td>是否命中银行黑名单</td>
                        <td>最近该手机号出现在黑名单中时间</td>
                        <td>最近该身份证出现在黑名单中时间</td>
                    </tr>
                    <tr>
                        <td><?php echo $data['risk_blacklist']['idcard_in_blacklist'] ? "是" : "否";?></td>
                        <td><?php echo $data['risk_blacklist']['phone_in_blacklist'] ? "是" : "否";?></td>
                        <td><?php echo $data['risk_blacklist']['in_court_blacklist'] ? "是" : "否";?></td>
                        <td><?php echo $data['risk_blacklist']['in_p2p_blacklist'] ? "是" : "否";?></td>
                        <td><?php echo $data['risk_blacklist']['in_bank_blacklist'] ? "是" : "否";?></td>
                        <td><?php echo $data['risk_blacklist']['last_appear_phone_in_blacklist'];?></td>
                        <td><?php echo $data['risk_blacklist']['last_appear_idcard_in_blacklist'];?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td width="500">社交风险点</td>
            <td>
                <table class="son">
                    <tr>
                        <td>葫芦分</td>
                        <td>直接联系人</td>
                        <td>直接联系人在黑名单中数量(直接黑人)</td>
                        <td>间接联系人在黑名单中数量(间接黑人)</td>
                        <td>认识间接黑人的直接联系人个数</td>
                        <td>认识间接黑人的直接联系人比例</td>
                    </tr>
                    <tr>
                        <td><?php echo $data['risk_social_network']['sn_score'];?></td>
                        <td><?php echo $data['risk_social_network']['sn_order1_contacts_cnt'];?></td>
                        <td><?php echo $data['risk_social_network']['sn_order1_blacklist_contacts_cnt'];?></td>
                        <td><?php echo $data['risk_social_network']['sn_order2_blacklist_contacts_cnt'];?></td>
                        <td><?php echo $data['risk_social_network']['sn_order2_blacklist_routers_cnt'];?></td>
                        <td><?php echo $data['risk_social_network']['sn_order2_blacklist_routers_pct'];?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td width="500">查询历史</td>
            <td>
                <table class="son">
                    <tr>
                        <td>历史查询总次数</td>
                        <td>历史查询总机构数</td>
                        <td>最近7天查询次数</td>
                        <td>最近7天查询机构数</td>
                        <td>最近14天查询次数</td>
                        <td>最近14天查询机构数</td>
                        <td>最近30天查询次数</td>
                        <td>最近30天查询机构数</td>
                        <td>最近60天查询次数</td>
                        <td>最近60天查询机构数</td>
                        <td>最近90天查询次数</td>
                        <td>最近90天查询机构数</td>
                        <td>最近180天查询次数</td>
                        <td>最近180天查询机构数</td>
                    </tr>
                    <tr>
                        <td><?php echo $data['history_search']['search_cnt'];?></td>
                        <td><?php echo $data['history_search']['org_cnt'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_7_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_7_days'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_14_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_14_days'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_30_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_30_days'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_60_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_60_days'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_90_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_90_days'];?></td>
                        <td><?php echo $data['history_search']['search_cnt_recent_180_days'];?></td>
                        <td><?php echo $data['history_search']['org_cnt_recent_180_days'];?></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td width="500">手机可疑身份</td>
            <td>
                <table class="son">
                    <tr>
                        <td>其他手机号码</td>
                        <td>运营商</td>
                        <td>归属地</td>
                        <td>归属地城市</td>
                        <td>此号码绑定其他姓名个数</td>
                        <td>查询此手机号的机构数</td>
                        <td>最近此手机号出现时间</td>
                    </tr>
                    <?php if(!empty($data['binding_phones'])):?>
                        <?php foreach($data['binding_phones'] as $value):?>
                            <tr>
                                <td><?php echo $value['other_phone'];?></td>
                                <td><?php echo $value['phone_operator'];?></td>
                                <td><?php echo $value['phone_province'];?></td>
                                <td><?php echo $value['phone_city'];?></td>
                                <td><?php echo $value['other_names_cnt'];?></td>
                                <td><?php echo $value['search_orgs_cnt'];?></td>
                                <td><?php echo $value['last_appear_phone'];?></td>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>

                </table>
            </td>
        </tr>

        <tr>
            <td width="500">身份证可疑身份</td>
            <td>
                <table class="son">
                    <tr>
                        <td>绑定的其他身份证</td>
                        <td>身份证是否是有效身份证</td>
                        <td>身份证户籍省份</td>
                        <td>身份证户籍城市</td>
                        <td>身份证户籍地区</td>
                        <td>年龄</td>
                        <td>性别</td>
                        <td>此号码绑定其他姓名个数</td>
                        <td>查询此身份证的机构数</td>
                        <td>最近此身份证出现时间</td>
                    </tr>
                    <?php if(!empty($data['binding_idcards'])):?>
                        <?php foreach($data['binding_idcards'] as $value):?>
                            <tr>
                                <td><?php echo $value['other_idcard'];?></td>
                                <td><?php echo $value['idcard_validate'] ? "是" : "否";?></td>
                                <td><?php echo $value['idcard_province'];?></td>
                                <td><?php echo $value['idcard_region'];?></td>
                                <td><?php echo $value['idcard_city'];?></td>
                                <td><?php echo $value['idcard_age'];?></td>
                                <td><?php echo $value['idcard_gender'];?></td>
                                <td><?php echo $value['other_names_cnt'];?></td>
                                <td><?php echo $value['search_orgs_cnt'];?></td>
                                <td><?php echo $value['last_appear_idcard'];?></td>
                            </tr>
                        <?php endforeach;?>
                    <?php endif;?>

                </table>
            </td>
        </tr>

        <tr>
            <td width="500">历史类型</td>
            <td>
                <table class="son">
                    <tr>
                        <td>线上消费分期出现次数</td>
                        <td>线下消费分期出现次数</td>
                        <td>信用卡代换出现次数</td>
                        <td>小额快速贷出现次数</td>
                        <td>线上现金贷出现次数</td>
                        <td>线下现金贷出现次数</td>
                        <td>其他</td>
                    </tr>
                    <tr>
                        <td><?php echo $data['history_org']['online_installment_cnt'];?></td>
                        <td><?php echo $data['history_org']['offline_installment_cnt'];?></td>
                        <td><?php echo $data['history_org']['credit_card_repayment_cnt'];?></td>
                        <td><?php echo $data['history_org']['payday_loan_cnt'];?></td>
                        <td><?php echo $data['history_org']['online_cash_loan_cnt'];?></td>
                        <td><?php echo $data['history_org']['offline_cash_loan_cnt'];?></td>
                        <td><?php echo $data['history_org']['others_cnt'];?></td>
                    </tr>
                </table>
            </td>
        </tr>

    <?php endif;?>
</table>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>
<script>

    function getReportId(id){
        var url = '<?php echo Url::toRoute(['hulu/get-report-id']);?>';
        var params = {id:id};
        var ret = confirmMsg('确认提交');
        if(!ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('提交成功');
            }else{
                alert(data.message);
            }
            location.reload(true);
        },'json');
    }

    function getInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        var url = '<?php echo Url::toRoute(['hulu/get-report-id']);?>';
        //获取芝麻评分
        params = {
            id:id
        };
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('获取成功');
            }else{
                alert(data.messag);
            }
            location.reload(true);
        },'json');

    }


</script>


