<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\LoanBlackList;
use common\models\UserOperateApplication;
use common\models\AccumulationFund;
use common\models\UserContact;

$this->shownav('service', 'menu_user_list');
$this->showsubmenu('用户管理');
?>
<style>
    .tb2 th{ font-size: 12px;}
    table {
        width: 100%;
    }
    table tr {
        height: 40px;
        line-height: 40px;
    }
</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;"/>&nbsp;
    用户姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;"/>&nbsp;
    身份证信息：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id_number', ''); ?>" name="id_number" class="txt" style="width:120px;"/>&nbsp;
    联系方式：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;"/>&nbsp;
    <input type="submit" name="search_submit" value="搜索" class="btn"/>
<?php $form = ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
        <tr class="header">
            <th>用户ID</th>
            <th>姓名</th>
            <th>公司名称</th>
            <th>联系方式</th>
            <th>生日</th>
            <th>性别</th>
            <th>类型</th>
            <th>来源</th>
            <th>来源详情</th>
            <th>状态</th>
            <th>可再借时间</th>
            <th>是否黑名单</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php if(!empty($loan_person)):?>
            <?php foreach ($loan_person as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <th><?php echo $value['name']; ?></th>
                    <th><?php echo isset($details[ $value['id'] ]['company_name'])?$details[ $value['id'] ]['company_name']:'' ?></th>
                    <th><?php echo $value['phone']; ?></th>
                    <th><?php echo date("Y-m-d" , $value['birthday']); ?></th>
                    <th><?php echo $value['property']; ?></th>
                    <th><?php echo empty($value['type'])?'--':LoanPerson::$person_type[$value['type']]; ?></th>
                    <th><?php echo @LoanPerson::$person_source[$value['source_id']]; ?></th>
                    <th><?php echo isset($details[ $value['id'] ]['reg_app_market_detail'])?$details[ $value['id'] ]['reg_app_market_detail']:''; ?></th>
                    <th><?php echo isset(LoanPerson::$status[$value['status']])? LoanPerson::$status[$value['status']]:""; ?></th>

                    <th><?php echo empty($value['can_loan_time']) ? '随时可借' : date("Y-m-d H:i", $value['can_loan_time']); ?></th>
                    <th><?php echo isset($value['black_status']) ? LoanBlackList::$status_list[$value['black_status']] : '否'  ?></th>
                    <th><?php echo date("Y-m-d H:i",$value['created_at']); ?></th>
                    <td>
                        <?php if(isset($value['black_status']) && $value['black_status'] == 1):?>
                            <a href="JavaScript:;" onclick="delBlackList(<?php echo $value['id'];?>)">取消黑名单</a>
                        <?php else:?>
                            <a href="JavaScript:;" onclick="addBlackList(<?php echo $value['id'];?>)">加入黑名单</a>
                        <?php endif;?>
                        |
                        <a href="<?php echo Url::toRoute(['loan/loan-person-view', 'id' => $value['id'],'type' => $value['type']]); ?>">查看</a>
                        |
                        <a href="<?php echo Url::toRoute(['loan/loan-person-edit', 'id' => $value['id']]); ?>">编辑</a>
                        |
                        <?php if(isset($value['status']) && $value['status'] == -2):?>
                            <a href="JavaScript:;" onclick="cancelDelete(<?php echo $value['id'];?>)">取消注销</a>
                        <?php else:?>
                            <a  onclick="confirmRedirect('确定要删除吗？', '<?php echo Url::toRoute(['custom-management/loan-person-log-out-del', 'id' => $value['id']]); ?>')" href="javascript:void(0);">注销用户资料</a>
                        <?php endif;?>
                        <!--<a onclick="confirmRedirect('确定要删除吗？', '<?php /*echo Url::toRoute(['custom-management/loan-person-log-out-del', 'id' => $value['id']]); */?>')" href="javascript:void(0);">注销用户资料</a>-->
                        |
                        <!--
                        <a onclick="confirmRedirect('确定要注销/删除资料账户吗？', '<?php echo Url::toRoute(['custom-management/operate-application', 'id' => $value['id'],'type'=>UserOperateApplication::OPERATE_DEL_PERSON_LOGOUT]); ?>')" href="javascript:void(0);">注销/删除资料账户</a>
                        <a onclick="confirmRedirect('确定要重新绑定银行卡吗？', '<?php echo Url::toRoute(['custom-management/operate-application', 'id' => $value['id'],'type'=>UserOperateApplication::OPERATE_PERSON_BIND_BANK]); ?>')" href="javascript:void(0);">重新绑定银行卡</a>
                        <a href="<?php echo Url::toRoute(['custom-management/operate-loan-person-update-phone', 'id' => $value['id']]); ?>">新旧号码更改</a>
                        <a href="<?php echo Url::toRoute(['custom-management/loan-person-proof-delete', 'id' => $value['id'],'type'=>UserOperateApplication::OPERATE_DEL_PERSON_PROOF]); ?>">删除用户照片</a>
                        <a onclick="confirmRedirect('确定重置聚信力吗？', '<?php echo Url::toRoute(['custom-management/operate-application', 'id' => $value['id'],'type'=>UserOperateApplication::OPERATE_REFRESH_JXL]); ?>')" href="javascript:void(0);">重置聚信力</a>
                        -->
                        <a href="<?php echo Url::toRoute(['custom-management/operate-type', 'id' => $value['id']]); ?>">申请</a>
                   </td>

                </tr>
                <tr>
                    <td colspan="14" style="text-align: center"><h5 style="color: #3325ff;font-size: 22px;">其他信息</h5></td>
                </tr>
                <tr>
                    <td colspan="4" style="vertical-align: top">
                        <table style="border:1px solid #ccc;width: 100%:">
                            <thead>
                                <tr>
                                    <th colspan="2"><h3>用户五步认证情况</h3></th>
                                </tr>
                            </thead>
                            <tr>
                                <td>基本信息填写是否完整：<br></td>
                                <td><?php echo $value['base_info'] ?></td>
                            </tr>
                            <tr>
                                <td>
                                    紧急联系人<a href="<?php echo Url::toRoute(['mobile-contacts/mobile-contacts-list','user_id'=>$value['id']]);?>">&nbsp;&nbsp;查看全部联系人</a><br><br>
                                    <ul>
                                        <?php foreach ($value['information'] as $_info) { ?>
                                        <li>关系-<?php echo UserContact::$relation_types[$_info['relation']]."：" .$_info['mobile'] ?></li>
                                        <li>关系-<?php echo UserContact::$relation_types[$_info['relation_spare']]."：" .$_info['mobile_spare'] ?></li>
                                        <?php } ?>
                                    </ul>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>是否上传通讯录：</td>
                                <td><?php echo $value['contacts'] ?></td>
                            </tr>
                            <tr>
                                <td>是否绑定银行卡：</td>
                                <td><?php echo empty($value['real_bind_bank_card_status']) ? "否" : "是"; ?><br></td>
                            </tr>
                            <tr>
                                <td>是否进行了芝麻信用认证：</td>
                                <td> <?php echo empty($value['real_zmxy_status']) ? "否" : "是"; ?><br></td>
                            </tr>
                            <tr>
                                <td>运营商认证：</td>
                                <td><?php echo $value['yys_status'] ?></td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="3" style="vertical-align: top">
                        <table style="border:1px solid #ccc;width: 100%;">
                            <thead>
                                <tr>
                                    <th colspan="2"><h3>用户额外信息填写情况（公积金等）</h3></th>
                                </tr>
                            </thead>

                            <tr>
                                <td>公积金认证情况：</td>
                                <td><?php echo isset($value['gjj_status']) ? AccumulationFund::$status[$value['gjj_status']] : "--"; ?></td>
                            </tr>
                            <tr>
                                <td>认证备注：</td>
                                <td><?php echo $value['gjj_remark'] ?></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                        </table>

                    </td>
                    <td colspan="3" style="vertical-align: top">
                        <table style="border:1px solid #ccc;width: 100%;">
                            <thead>
                                <tr>
                                    <th colspan="2"><h3>用户的授信情况</h3></th>
                                </tr>
                            </thead>
                            <tr>
                                <td>授信状态：</td>
                                <td><?php echo $value['credit_status'] ?></td>
                            </tr>
                            <tr>
                                <td>授信总额：</td>
                                <td><?php echo $value['credit_amount'] / 100 . '元' ?></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="2" style="vertical-align: top">
                        <table style="border:1px solid #ccc;width: 100%;">
                            <thead>
                                <tr>
                                    <th colspan="2"><h3>下单逾期情况</h3></th>
                                </tr>
                            </thead>
                            <tr>
                                <td>下单总数：</td>
                                <td>共<?php echo $value['order_num'] ?>单</td>
                            </tr>
                            <tr>
                                <td>逾期情况：</td>
                                <td>逾期<?php echo $value['overdue_num'] ?>次</td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                        </table>

                    </td>
                    <td colspan="4" style="vertical-align: top">
                        <table style="border: 1px solid #ccc;font-weight: 200">
                            <thead>
                                <tr>
                                    <td colspan="5"><h3>登录情况</h3></td>
                                </tr>
                            </thead>
                            <tr>
                                <td>用户id</td>
                                <td>用户姓名</td>
                                <td>登录设备</td>
                                <td>登录时间</td>
                                <td>具体地址</td>
                            </tr>
                            <?php
                            if ($value['user_login_upload_log']) {
                                foreach ($value['user_login_upload_log'] as $_login) {
                                    ?>
                                    <tr>
                                        <td><?php echo $_login['user_id'] ?></td>
                                        <td><?php echo $_login['loanPerson']['name'] ?></td>
                                        <td><?php echo $_login['clientType'] ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', $_login['created_at']); ?></td>
                                        <td><?php echo $_login['address'] ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">暂无记录</td>
                            </tr>
                            <?php } ?>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif;?>
    </table>
    <?php if (empty($loan_person)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>

<script>
    function addBlackList(id){
        var name = prompt("必须填写备注！"); //在页面上弹出提示对话框，

        if (name!=null && name!="") {
            var url = '<?php echo Url::toRoute(["loan-black-list/add"]);?>';
            var params = {id:id, mark: name};
            $.get(url,params,function(data){
                if(data.code == 0){
                    alert('添加成功');
                    window.location.reload(true);
                }else{
                    alert(data.message);
                }
            })
        } else if(name === "") {
            alert("必须填写备注");
        }
        console.log(name);
        return;
        /*if(!confirmMsg('确认加入黑名单')){
         return false;
         }*/

    }
    function cancelDelete (id) {

        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/custom-management/loan-person-cancel-out-del']); ?>",
            data: {_csrf: "<?php echo Yii::$app->request->csrfToken ?>", user_id: id},
            dataType: "json",
            success: function (o) {
                console.log(o)
                if (o.code == 0) {
                    alert('恢复成功');
                    location.reload()
                } else {
                    alert(o.message);
                }
            }
        })
    }
    function delBlackList(id){
        if(!confirmMsg('确认取消黑名单')){
            return false;
        }
        var url = '<?php echo Url::toRoute(["loan-black-list/del"]);?>';
        var params = {id:id};
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('取消成功');
                window.location.reload(true);
            }else{
                alert(data.message);
            }
        })
    }
</script>
