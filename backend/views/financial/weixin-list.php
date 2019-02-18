<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\WeixinRepaymentLog;

$this->shownav('financial', 'weixin_list');
$this->showsubmenu('微信还款列表');

?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
微信订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('weixin_order_id', ''); ?>" name="weixin_order_id" class="txt" style="width:280px;">&nbsp;
微信账号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('weixin_account', ''); ?>" name="weixin_account" class="txt" style="width:120px;">&nbsp;
用户姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('weixin_name', ''); ?>" name="weixin_name" class="txt" style="width:120px;">&nbsp;
最后操作人：<input type="text" value="<?php echo Yii::$app->getRequest()->get('operator_user', ''); ?>" name="operator_user" class="txt" style="width:120px;">&nbsp;
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), WeixinRepaymentLog::$status, ['prompt' => '所有状态']); ?>&nbsp;
来源：<?php echo Html::dropDownList('source', Yii::$app->getRequest()->get('source', ''), WeixinRepaymentLog::$source_list, ['prompt' => '所有状态']); ?>&nbsp;
公司账号：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), WeixinRepaymentLog::$types, ['prompt' => '所有状态']); ?>&nbsp;
<br/>
转账时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_at_begin', ''); ?>" name="pay_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%ss',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('pay_at_end', ''); ?>"  name="pay_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%ss',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:true})">&nbsp;
创建时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
更新时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('updated_at_begin', ''); ?>" name="updated_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至 <input type="text" value="<?php echo Yii::$app->getRequest()->get('updated_at_end', ''); ?>"  name="updated_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php ActiveForm::end(); ?>
<style>td { text-align: center;} th { text-align: center;}</style>
<?php if (!empty($info)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>ID</th>
            <th>匹配状态</th>
            <th>微信订单号</th>
            <th>微信账号</th>
            <th>姓名</th>
            <th>支付金额</th>
            <th>实际金额</th>
            <th>溢缴款</th>
            <th>溢缴款备注</th>
            <th>状态</th>
            <th>来源</th>
            <th>支付时间</th>
            <th>更新时间</th>
            <th>备注</th>
            <th>最后操作人</th>
            <th>管理员备注</th>
            <th style="width: 150px; text-align: center;">操作</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value->id; ?></td>
                <td><?php echo isset(WeixinRepaymentLog::$types[$value->type]) ? WeixinRepaymentLog::$types[$value->type] : $value->type ; ?></td>
                <td><?php echo $value->weixin_order_id; ?></td>
                <td class="click-phone" data-phoneraw="<?php echo $value->weixin_account; ?>">--</td>
                <td><?php echo $value->weixin_name; ?></td>
                <td><?php echo sprintf('%.2f', $value->money / 100); ?></td>
                <td><?php echo sprintf('%.2f', ($value->money-$value->overflow_payment) / 100); ?></td>
                <td onclick="switch_display(this)">
                    <span><?php echo sprintf('%.2f', $value->overflow_payment / 100); ?></span>
                    <input style="display: none" type="text" name="overflow_payment" onblur="modify_overflow_payment(this)" aid="<?php echo $value->id; ?>" old_value="<?php echo sprintf('%.2f', $value->overflow_payment / 100); ?>" value="<?php echo sprintf('%.2f', $value->overflow_payment / 100); ?>"/>
                </td>
                <td onclick="switch_display(this)">
                    <span><?php echo $value->remark2; ?></span>
                    <input style="display: none" type="text" name="remark2" onblur="modify_mark(this)" aid="<?php echo $value->id; ?>" old_value="<?php echo $value->remark2; ?>" value="<?php echo $value->remark2; ?>"/>
                </td>
                <td>
                    <?php
                    echo isset(WeixinRepaymentLog::$status[$value->status]) ? WeixinRepaymentLog::$status[$value->status] : "---";
                    ?>
                </td>
                <td><?php echo isset(WeixinRepaymentLog::$source_list[$value->source]) ? WeixinRepaymentLog::$source_list[$value->source] : '未知'; ?></td>
                <td><?php echo $value->pay_date; ?></td>
                <td><?php echo date('Y-m-d H:i',$value->updated_at); ?></td>
                <td><?php echo $value->remark; ?></td>
                <td><?php echo $value->operator_user; ?></td>
                <td><?php echo $value->remark_admin; ?></td>
                <td >
                    <?php if (($value['status'] != WeixinRepaymentLog::STATUS_FINISH && $value['status'] != WeixinRepaymentLog::STATUS_BACK)): ?>
<!--                        <a onclick="return confirmMsg('确认置为已处理')" href="--><?php //echo Url::toRoute(['financial/finish-weixin-log', 'id' => $value->id]); ?><!--">置为已处理</a>-->
<!--                        <a onclick="return confirmMsg('确认置为退回')"  href="--><?php //echo Url::toRoute(['financial/finish-weixin-log', 'id' => $value->id,'type'=>1]); ?><!--">置为退回</a>-->
                    <?php else: ;?>
<!--                        <a onclick="return confirmMsg('确认置为需人工')" href="--><?php //echo Url::toRoute(['financial/manual-weixin-log', 'id' => $value->id]); ?><!--">置为需人工</a>-->
<!--                        <a onclick="return confirmMsg('确认置为未处理')" href="--><?php //echo Url::toRoute(['financial/reset-weixin-log', 'id' => $value->id]); ?><!--">置为未处理</a>-->
                    <?php endif; ?>
                    <a href="#" onclick="setRemarkTwo(<?php echo $value->id;?>)">添加备注</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
    <?php if(isset($dataSt) && !empty($dataSt)): ?>
        <table frame="above" align="right">
            <tr>
                <td align="center" style="color: red;">总支付金额：</td>
                <td align="center" style="color: red;">已处理金额：</td>
                <td align="center" style="color: red;">已退回金额：</td>
            </tr>
            <tr>
                <td style="color: red;"><?php echo sprintf("%.2f",$dataSt['money'] / 100) ?></td>
                <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['success_money'] / 100) ?></td>
                <td align="right" style="color: red;"><?php echo sprintf("%.2f",($dataSt['reject_money']) / 100) ?></td>
            </tr>
        </table>
    <?php endif; ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>
<?php $form = ActiveForm::begin(['id' => 'set_admin_remark','enableAjaxValidation'=>true]); ?>
<input type="hidden" value="" name="id">
<input type="hidden" value="" name="remark_admin">
<?php ActiveForm::end(); ?>
<script>
    function setRemarkTwo(id){
        var remark_two = prompt("请输入备注","");
        if(remark_two == ""){
            alert("未输入备注");
        }else if(remark_two == null){
            return false;
        }else{
            $("#set_admin_remark input[name=id]").val(id);
            $("#set_admin_remark input[name=remark_admin]").val(remark_two);
            $.ajax({
                url : "<?php echo Url::toRoute('financial/set-weixin-admin-remark')?>",
                type : 'json',
                method : 'post',
                data : $("#set_admin_remark").serialize(),
                success : function(res) {
                    alert(res.message);
                    window.location.reload();
                },
                error : function (res) {
                    alert('网络请求错误!');
                    window.location.reload();
                }
            });

        }
    }
</script>
<script>

    function modify_overflow_payment(obj)
    {
        var $obj = $(obj);
        var value = $obj.val();
        var old_value = $obj.attr('old_value');
        var id = $obj.attr('aid');
        if(value != old_value){
            if(!confirmMsg('确认修改')){
                obj.value = old_value;
                $obj.hide();
                $obj.parent().children('span').show();
                return false;
            }
            $.post(
                "<?php echo Url::toRoute('financial/modify-overflow-payment');?>",
                {
                    id : id,
                    overflow_payment : value,
                    type : 'overflow_payment',
                    _csrf : '<?php echo  Yii::$app->request->csrfToken ;?>'
                },
                function(data){
                    if(data.code == 0){
                        location.reload(true);
                    } else{
                        alert(data.msg);
                    }
                });
        }else{
            $obj.hide();
            $obj.parent().children('span').show();
        }

    }

    function modify_mark(obj)
    {
        var $obj = $(obj);
        var value = $obj.val();
        var old_value = $obj.attr('old_value');
        var id = $obj.attr('aid');
        if(value != old_value){
            if(!confirmMsg('确认修改')){
                obj.value = old_value;
                $obj.hide();
                $obj.parent().children('span').show();
                return false;
            }
            $.post(
                "<?php echo Url::toRoute('financial/modify-overflow-payment');?>",
                {
                    id : id,
                    type : 'remark',
                    remark : value,
                    _csrf : '<?php echo  Yii::$app->request->csrfToken ;?>'
                },
                function(data){
                    if(data.code == 0){
                        location.reload(true);
                    } else{
                        alert(data.msg);
                    }
                });
        }else{
            $obj.hide();
            $obj.parent().children('span').show();
        }

    }

    function switch_display(obj)
    {
        var $obj = $(obj);
        if($obj.children('input').css('display') == 'none'){
            $obj.children('input').show();
            $obj.children('span').hide();
        }
    }
</script>
<script>
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
