<?php
use common\helpers\ToolsUtil;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\LoanBlackList;
use common\models\UserVerification;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>.tb2 th{ font-size: 12px;}</style>

<?php $form = ActiveForm::begin([
    'method' => "get",
    'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'],
]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->request->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->request->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->request->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    身份证：<input type="text" value="<?php echo Yii::$app->request->get('id_number', ''); ?>" name="id_number" class="txt" style="width:120px;">&nbsp;

    注册时间：<input type="text" value="<?php echo Yii::$app->request->get('begintime', ''); ?>" name="begintime"
                onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
    至<input type="text" value="<?php echo Yii::$app->request->get('endtime', ''); ?>" name="endtime"
            onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})" />
    <?php if ($type!=1) : ?>
        <br><br>
        用户类型：<?php echo Html::dropDownList('type', Yii::$app->request->get('type', ''), LoanPerson::$person_type, ['prompt' => '-所有类型-']); ?>&nbsp;
        用户来源：<?php echo Html::dropDownList('source_id', Yii::$app->request->get('source_id', ''), LoanPerson::$current_loan_source, ['prompt' => '-所有类型-']); ?>&nbsp;
        是否黑名单：<?php echo Html::dropDownList('black_status', Yii::$app->request->get('black_status', ''), LoanBlackList::$status_list, ['prompt' => '-所有类型-']); ?>&nbsp;
        是否认证：<?php echo Html::dropDownList('verify_status', Yii::$app->request->get('verify_status', ''), UserVerification::$verification_status, ['prompt' => '全部']); ?>
        <label><input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存</label>
    <?php endif; ?>

    <input type="submit" name="search_submit" value="过滤" class="btn" />
<?php $form = ActiveForm::end(); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>用户ID</th>
        <th>姓名</th>
        <?php if($type!=1){?>
        <th>公司名称</th>
        <?php }?>

        <th>手机号</th>
        <th>生日</th>
        <th>性别</th>
        <th>类型</th>
        <th>来源</th>
        <th>来源详情</th>
        <th>渠道</th>
        <th>设备</th>
        <th>os版本</th>
        <th>系统</th>
        <th>状态</th>

        <th>可再借时间</th>
        <?php if($type!=1){?>
        <th>是否黑名单</th>
        <?php }?>
        <th>创建时间</th>
        <?php if($type!=1){?>
        <th>操作</th>
        <?php }?>
    </tr>
    <?php foreach ($loan_person as $value): ?>
        <tr class="hover">
            <td><?php echo $value['id']; ?></td>
            <th><?php echo $value['name']; ?></th>
            <?php if($type!=1){?>
            <th><?php echo $details[ $value['id'] ]['company_name'] ?? ''; ?></th>
            <?php }?>
            <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
            <th><?php echo date("Y-m-d" , $value['birthday'] != '0' ? $value['birthday'] : ToolsUtil::idCard_to_birthday($value['id_number']) ); ?></th>
            <th><?php echo $value['property']; ?></th>
            <th><?php echo empty($value['type'])?'--':LoanPerson::$person_type[$value['type']]; ?></th>
            <th><?php echo LoanPerson::$person_source[$value['source_id']] ?? ''; ?></th>
            <th><?php echo isset($details[ $value['id'] ]['reg_app_market_detail']) ? $details[ $value['id'] ]['reg_app_market_detail'] : ''; ?></th>
            <th><?php echo isset($details[ $value['id'] ]['reg_app_market']) ? $details[ $value['id'] ]['reg_app_market'] : ''; ?></th>
            <th><?php echo isset($details[ $value['id'] ]['reg_device_name']) ? $details[ $value['id'] ]['reg_device_name'] : ''; ?></th>
            <th><?php echo isset($details[ $value['id'] ]['reg_os_version']) ? $details[ $value['id'] ]['reg_os_version'] : ''; ?></th>
            <th><?php echo isset($details[ $value['id'] ]['reg_client_type']) ? $details[ $value['id'] ]['reg_client_type'] : ''; ?></th>
            <th><?php echo isset(LoanPerson::$status[$value['status']])? LoanPerson::$status[$value['status']]:""; ?></th>

            <th>
            <?php if(empty($value['can_loan_time'])):?>
                随时可借
            <?php elseif($value['can_loan_time']==4294967295):?>
                永不再借
            <?php else:?>
                <?php echo  date("Y-m-d H:i", $value['can_loan_time']); ?>
            <?php endif;?>
            </th>
            <?php if($type!=1){?>
            <th><?php echo isset($value['black_status']) ? LoanBlackList::$status_list[$value['black_status']]: '否'  ?></th>
            <?php }?>
            <th><?php echo date("Y-m-d H:i",$value['created_at']); ?></th>
            <?php if($type!=1){?>
            <td>
                <?php if(isset($value['black_status']) && $value['black_status'] == 1):?>
                    <a href="JavaScript:;" onclick="delBlackList(<?php echo $value['id'];?>)">取消黑名单</a>
                <?php else:?>
                    <a href="JavaScript:;" onclick="addBlackList(<?php echo $value['id'];?>)">加入黑名单</a>
                <?php endif;?>
                <a href="<?php echo Url::toRoute(['loan/loan-person-view', 'id' => $value['id'],'type' => $value['type']]); ?>">查看</a>
<!--                <a href="--><?php //echo Url::toRoute(['loan/loan-person-edit', 'id' => $value['id']]); ?><!--">编辑</a>-->
                <!--<a onclick="confirmRedirect('确定要删除吗？', '<?php //echo Url::toRoute(['loan/loan-person-del', 'id' => $value['id']]); ?>')" href="javascript:void(0);">删除</a> -->
                <a onclick="confirmRedirect('确定要注销/删除资料账户吗？', '<?php echo Url::toRoute(['loan/loan-person-log-out-del', 'id' => $value['id']]); ?>')" href="javascript:void(0);">注销/删除资料账户</a>
                <a onclick="confirmRedirect('确定要重新绑定银行卡吗？', '<?php echo Url::toRoute(['loan/loan-person-afresh-bind', 'id' => $value['id']]); ?>')" href="javascript:void(0);">重新绑定银行卡</a>
                <br />
                <a href="<?php echo Url::toRoute(['loan/loan-person-update-phone', 'id' => $value['id']]); ?>">新旧号码更改</a>
                <a href="<?php echo Url::toRoute(['loan/loan-person-proof-delete', 'id' => $value['id']]); ?>">删除用户照片</a>
                <a onclick="confirmRedirect('确定重置聚信力吗？', '<?php echo Url::toRoute(['custom-management/refresh-jxl-loan-person', 'id' => $value['id']]); ?>')" href="javascript:void(0);">重置聚信力</a>
                <a href="<?php echo Url::toRoute(['loan/can-loan-time-update', 'id' => $value['id']]); ?>">重置可再借时间</a>
                <a href="<?php echo Url::toRoute(['loan/user-change-card','id'=>$value['id']])?>">重新绑定用户的银行卡</a>
            </td>
            <?php }?>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($loan_person)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

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

/**
 * 电话显示*，点击后正常显示
 */
(function initClickPhoneCol() {
    $('.click-phone').each(function () {
        var $item = $(this);
        var phone = $item.attr('data-phoneraw');
        if (phone && phone.length>5) {
            var phoneshow = phone.substr(0, 3) + '****' + phone.substr(phone.length - 2, 2);
            $item.attr('data-phoneshow', phoneshow);
            $item.text(phoneshow);
        } else {
            $item.attr('data-phoneshow', phone);
            $item.text(phone);
        }
    });
    $('.click-phone').one('click', function () {
        $(this).text($(this).attr('data-phoneraw'));
    })
})();
</script>
