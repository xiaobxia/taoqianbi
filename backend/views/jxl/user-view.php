<?php
use common\helpers\Url;
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
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的蜜罐信息</span>

                <span style="color:red">数据更新时间：<?php echo $update_time;?></span>

        </th>
    </tr>
    <tr>
        <td>
                <a style="color:red" onclick="getMgInfo(<?php echo $loanPerson['id'];?>)" href="JavaScript:;">点击获取所有信息</a>
        </td>
    </tr>
</table>
    <?php if(!empty($update_time)): ?>
        <table class="tb tb2 fixpadding table" style="margin-top:20px">
            <tr>
                <th width="110px;">基本信息</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px;">姓名</th>
                            <td ng-bind="reportData.result.user_basic.user_name" class="ng-binding"><?php echo $user_basic['user_name'];?></td>
                        </tr>
                        <tr>
                            <th>身份证号码有效</th>
                            <td ng-switch="reportData.result.user_basic.user_idcard_valid">
                                <label class="label label-success ng-scope" ng-switch-when="true"><?php echo $user_basic['user_idcard_valid'] ? '是' : '否';?></label>
                            </td>
                        </tr>
                        <tr>
                            <th>身份证归属地</th>
                            <td>
                                <span ng-bind="reportData.result.user_basic.user_province" class="label label-primary ng-binding"><?php echo $user_basic['user_province'];?></span>/
                                <span ng-bind="reportData.result.user_basic.user_city" class="label label-primary ng-binding"><?php echo $user_basic['user_city'];?></span>/
                                <span ng-bind="reportData.result.user_basic.user_region" class="label label-primary ng-binding"><?php echo $user_basic['user_region'];?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>手机号码归属地</th>
                            <td>
                                <span ng-bind="reportData.result.user_basic.user_phone_province" class="label label-primary ng-binding"><?php echo $user_basic['user_phone_province'];?></span>/
                                <span ng-bind="reportData.result.user_basic.user_phone_city" class="label label-primary ng-binding"><?php echo $user_basic['user_phone_city'];?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>手机所属运营商</th>
                            <td ng-bind="reportData.result.user_basic.user_phone_operator" class="ng-binding"><?php echo $user_basic['user_phone_operator'];?></td>
                        </tr>
                        <tr>
                            <th>身份证号码</th>
                            <td ng-bind="reportData.result.user_basic.user_idcard" class="ng-binding"><?php echo $user_basic['user_idcard'];?></td>
                        </tr>
                        <tr>
                            <th>手机号码</th>
                            <td ng-bind="reportData.result.user_basic.user_phone" class="ng-binding"><?php echo $user_basic['user_phone'];?></td>
                        </tr>
                        <tr>
                            <th>年龄</th>
                            <td ng-bind="reportData.result.user_basic.user_age" class="ng-binding"><?php echo $user_basic['user_age'];?></td>
                        </tr>
                        <tr>
                            <th>性别</th>
                            <td ng-bind="reportData.result.user_basic.user_gender" class="ng-binding"><?php echo $user_basic['user_gender'];?></td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr width="110px;">
                <th>注册信息</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px">注册机构数量</th>
                            <td class="ng-binding"><?php echo $user_register_orgs['register_cnt'];?></td>
                        </tr>
                        <tr>
                            <th>注册机构类型</th>
                            <td>
                                <span class="text-warning"><?php echo empty($user_register_orgs['register_orgs'])?'无':implode(',',$user_register_orgs['register_orgs']);?></span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr width="110px;">
                <th>查询统计信息</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px">机构查询统计</th>
                            <td>
                                <span style="display: inline-block;width: 50px">
                                    <span><?php echo $user_searched_statistic['searched_org_cnt'];?></span>
                                </span>
                                <span>(被多少机构查询过-已去重)</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <th>黑名单信息</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr ng-class="{'danger':reportData.result.user_gray.phone_gray_score&gt;0&amp;&amp;reportData.result.user_gray.phone_gray_score&lt;59}">
                            <th width="110px;">黑中介分数</th>
                            <td>
                                <span style="display: inline-block;width: 50px">
                                    <span class="label ng-binding label-warning">
                                        <?php echo is_null($user_gray['phone_gray_score']) ? '无数据':$user_gray['phone_gray_score'];?>
                                    </span>
                                </span>
                                <span>(分数区间为0~100，10分以下为高危人群)</span>
                            </td>
                        </tr>
                        <tr class="">
                            <th>直接联系人在黑名单数量</th>
                            <td>
                                <span style="display: inline-block;width: 50px" class="ng-binding"><?php echo is_null($user_gray['contacts_class1_blacklist_cnt']) ? '无数据':$user_gray['contacts_class1_blacklist_cnt'];?></span>
                                (直接联系人：和被查询号码有通话记录)
                            </td>
                        </tr>
                        <tr>
                            <th>直接联系人总数</th>
                            <td>
                                <span style="display: inline-block;width: 50px" class="ng-binding"><?php echo is_null($user_gray['contacts_class1_cnt']) ? '无数据':$user_gray['contacts_class1_cnt'];?></span>
                                (直接联系人：和被查询号码有通话记录)
                            </td>
                        </tr>
                        <tr class="">
                            <th>间接联系人在黑名单数量</th>
                            <td>
                                <span style="display: inline-block;width: 50px" class="ng-binding">
                                    <?php echo is_null($user_gray['contacts_class2_blacklist_cnt']) ? '无数据':$user_gray['contacts_class2_blacklist_cnt'];?>
                                </span>
                                (间接联系人：和被查询号码的直接联系人有通话记录)
                            </td>
                        </tr>
                        <tr>
                            <th>引起黑名单的直接联系人数量
                            <td>
                                <span  style="display: inline-block;width: 50px" class="ng-binding">
                                    <?php echo is_null($user_gray['contacts_router_cnt']) ? '无数据':$user_gray['contacts_router_cnt'];?>
                                </span>
                                (直接联系人有和黑名单用户的通讯记录的号码数量)
                            </td>
                        </tr>
                        <tr>
                            <th>引起黑名单的直接联系人占比</th>
                            <td>
                                <span style="display: inline-block">
                                    <span class="ng-binding">
                                        <?php echo $user_gray['contacts_router_ratio'];?>
                                    </span>%
                                </span>
                                (直接联系人有和黑名单用户的通讯记录的号码数量在直接联系人数量中的百分比)
                            </td>
                        </tr>
                        <tr>
                            <th>被标记的黑名单分类</th>
                            <td>
                                <span class="label label-success">
                                    <?php echo empty($user_blacklist['blacklist_category']) ? '无' : implode(',',$user_blacklist['blacklist_category']);?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>手机和姓名是否在黑名单</th>
                            <td>
                                <label>
                                    <?php echo $user_blacklist['blacklist_name_with_phone'] ? '是' : '否';?>
                                </label>
                           </td>
                        </tr>
                        <tr>
                            <th width="200px;">手机和姓名黑名单更新信息</th>
                            <td class="ng-binding">
                                <?php echo $user_blacklist['blacklist_update_time_name_phone'];?>
                            </td>
                        </tr>

                        <tr>
                            <th width="200px;">身份证和姓名是否在黑名单</th>
                            <td>
                             <label class="label label-success ng-scope">
                                 <?php echo $user_blacklist['blacklist_name_with_idcard'] ? '是' : '否';?>
                             </label>
                           </td>
                        </tr>
                        <tr>
                            <th width="200px;">身份证和姓名黑名单更新时间</th>
                            <td class="ng-binding">
                                <?php echo $user_blacklist['blacklist_update_time_name_idcard'];?>
                            </td>
                        </tr>
                        <tr>
                            <th>黑名单详细信息</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">

                                        <?php if(empty($user_blacklist['blacklist_details'])):?>
                                    <tr>
                                            <span>无</span>
                                    </tr>
                                        <?php else:?>
                                            <?php foreach($user_blacklist['blacklist_details'] as $v):?>
                                                <tr>
                                                    <th width="180px"><?php echo $v['details_key'];?></th>
                                                    <th><?php echo $v['details_value'];?></th>
                                                </tr>

                                            <?php endforeach;?>
                                        <?php endif;?>

                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <th>机构查询历史</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px">查询日期</th>
                            <th width="183px">机构类型</th>
                            <th>是否是本机构查询</th>
                        </tr>

                            <?php if(!empty($user_searched_history_by_orgs)):?>
                                <?php foreach($user_searched_history_by_orgs as $v):?>
                                    <tr class="ng-scope">
                                        <td class="ng-binding"><?php echo $v['searched_date'];?></td>
                                        <td class="ng-binding"><?php echo $v['searched_org'];?></td>
                                        <td>
                                            <span class="label label-primary ng-scope">
                                                <?php echo $v['org_self'] ? '是' : '否';?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach;?>
                            <?php endif;?>
                    </table>
                </td>
            </tr>

            <tr>
                <th>手机存疑</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px;">使用过此手机的其他姓名</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr>
                                        <th width="180px">最后使用时间</th>
                                        <th>姓名</th>
                                    </tr>
                                    <?php if(!empty($user_phone_suspicion['phone_with_other_names'])):?>
                                        <?php foreach($user_phone_suspicion['phone_with_other_names'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo isset($v['susp_updt'])?$v['susp_updt']:'';?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo isset($v['susp_name'])?$v['susp_name']:'';?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td colspan="2">
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>使用过此手机的其他身份证</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr>
                                        <th width="180px">最后使用时间</th>
                                        <th>身份证号码</th>
                                    </tr>
                                    <?php if(!empty($user_phone_suspicion['phone_with_other_idcards'])):?>
                                        <?php foreach($user_phone_suspicion['phone_with_other_idcards'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_updt'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo empty($v['susp_name']) ? "--" : $v['susp_name'];?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td colspan="2">
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>提供数据的机构类型</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr>
                                        <th width="180px">最后使用时间</th>
                                        <th>机构类型</th>
                                    </tr>
                                    <?php if(!empty($user_phone_suspicion['phone_applied_in_orgs'])):?>
                                        <?php foreach($user_phone_suspicion['phone_applied_in_orgs'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_updt'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_org_type'];?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td colspan="2">
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <th>身份证存疑</th>
                <td style="padding: 2px;margin-bottom: 1px">
                    <table style="margin-bottom: 0px" class="table">
                        <tr>
                            <th width="200px;">使用过此身份证的其他姓名</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr>
                                        <th width="180px">最后使用时间</th>
                                        <th>用户名称</th>
                                    </tr>
                                    <?php if(!empty($user_idcard_suspicion['idcard_with_other_names'])):?>
                                        <?php foreach($user_idcard_suspicion['idcard_with_other_names'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_updt'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_name'];?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td colspan="2">
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>使用过此身份证的其他手机</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr>
                                        <th width="180px">最后使用时间</th>
                                        <th width="180px">手机号码</th>
                                        <th width="180px">运营商名称</th>
                                        <th>归属地</th>
                                    </tr>
                                    <?php if(!empty($user_idcard_suspicion['idcard_with_other_phones'])):?>
                                        <?php foreach($user_idcard_suspicion['idcard_with_other_phones'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_updt'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_phone'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_phone_operator'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_phone_city'];?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td colspan="4">
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <th>提供数据的机构类型</th>
                            <td style="padding: 2px;margin-bottom: 1px">
                                <table style="margin-bottom: 0px" class="table">
                                    <tr> <th width="180px">最后使用时间</th>
                                        <th>机构类型</th>
                                    </tr>
                                    <?php if(!empty($user_idcard_suspicion['idcard_applied_in_orgs'])):?>
                                        <?php foreach($user_idcard_suspicion['idcard_applied_in_orgs'] as $v):?>
                                            <tr class="ng-scope">
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_updt'];?></span>
                                                </td>
                                                <td>
                                                    <span class="label label-success"><?php echo $v['susp_org_type'];?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <tr class="ng-scope">
                                            <td>
                                                <span class="label label-success">无</span>
                                            </td>
                                        </tr>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    <?php endif;?>
    <br><a href="<?php echo Url::toRoute(['jxl/old-user-view','id'=>$id]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">历史查询记录</a>

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


