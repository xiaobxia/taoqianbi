<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\FinancialLoanRecord;
use common\models\BankConfig;
use common\models\UserLoanOrder;
use common\models\fund\LoanFund;
if($view=='review'){
    $this->shownav('financial', 'menu_loan_dksh_list');
    $this->showsubmenu('打款审核列表');
}elseif($view=='system'){
    $this->shownav('financial', 'menu_loan_xtdk_list');
    $this->showsubmenu('系统打款列表');
}elseif($view=='direct_defeated'){
    $this->shownav('financial', 'menu_loan_zlsb_list');
    $this->showsubmenu('直连打款失败列表');
}else{
    $this->shownav('financial', 'menu_loan_list');
    $this->showsubmenu('打款列表');
}
$fund_koudai = LoanFund::findOne(LoanFund::ID_KOUDAI);
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.modal.min.js'); ?>"></script>
<link rel="stylesheet" href="<?php echo Url::toStatic('/css/jquery.modal.min.css'); ?>" type="text/css" media="screen" />
<style>
    input.txt {width:120px;}
    .header th{text-align: center;}
    body > .modal { display: none;}
</style>
<p style="color:red">提现审核通过，只是向第三方支付平台发起提现申请（用户端仍然显示为提现中），提现结果第三方支付平台会异步通知或发起主动查询</p>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
    打款ID：<input type="text" value="<?php echo \yii::$app->request->get('rid', ''); ?>" name="rid" class="txt">&nbsp;
    订单ID：<input type="text" value="<?php echo \yii::$app->request->get('order_id', ''); ?>" name="order_id" class="txt">&nbsp;
    <?php if (empty($isother)) : ?>
    <?php $all_funds = \common\models\fund\LoanFund::getAllFundArray(); ?>
        资方：
        <select name="fund_id">
            <option value="-1" <?php if('all' === \yii::$app->request->get('fund_id', '-1')) { echo "selected";} ?>>全部</option>
            <?php foreach($all_funds as $fund_id=>$fund_name):?>
            <option value="<?php echo $fund_id?>" <?php if($fund_id == \yii::$app->request->get('fund_id', 'all')) { echo "selected";} ?>><?php echo $fund_name?></option>
            <?php endforeach;?>
        </select>&nbsp;
    <?php endif;?>
    银行支付订单：<input type="text" value="<?php echo \yii::$app->request->get('pay_order_id', ''); ?>" name="pay_order_id" class="txt" />
    借款期限：<input type="text" value="<?php echo \yii::$app->request->get('loan_term', ''); ?>" name="loan_term" class="txt" />
    借款区间：
        <input type="text" value="<?php echo \yii::$app->request->get('loan_amount_min', ''); ?>" name="loan_amount_min" class="txt" placeholder="最小借款" />
        &nbsp;-&nbsp;
        <input type="text" value="<?php echo \yii::$app->request->get('loan_amount_max', ''); ?>" name="loan_amount_max" class="txt" placeholder="最大借款" />
    <br />

    用户ID：<input type="text" value="<?php echo \yii::$app->request->get('user_id', ''); ?>" name="user_id" class="txt">&nbsp;
    用户名：<input type="text" value="<?php echo \yii::$app->request->get('username', ''); ?>" name="username" class="txt">&nbsp;
    手机号：<input type="text" value="<?php echo \yii::$app->request->get('phone', ''); ?>" name="phone" class="txt">&nbsp;
    打款渠道：<?php echo Html::dropDownList('payment_type', Yii::$app->request->get('payment_type', ''), FinancialLoanRecord::$payment_types, ['prompt' => '所有打款类型']); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->request->get('status', ''), FinancialLoanRecord::$ump_pay_status, ['prompt' => '所有状态']); ?>&nbsp;
    业务类型：<?php echo Html::dropDownList('type', Yii::$app->request->get('type', ''), [1=>APP_NAMES], ['prompt' => '所有提现类型']); ?>&nbsp;
    用户来源：<?php echo Html::dropDownList('source_id', Yii::$app->request->get('source_id', ''),\common\models\LoanPerson::$current_loan_source , ['prompt' => '所有来源']); ?>&nbsp;
    审核状态：<?php echo Html::dropDownList('review_result', Yii::$app->request->get('review_result', ''), FinancialLoanRecord::$review_status, ['prompt' => '所有状态']); ?>&nbsp;
    <br />

    回调状态：<?php echo Html::dropDownList('callback_result', Yii::$app->request->get('callback_result', ''), FinancialLoanRecord::$notify, ['prompt' => '所有状态']); ?>&nbsp;
    申请时间：<input type="text" value="<?php echo \yii::$app->request->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo \yii::$app->request->get('endtime', ''); ?>" name="endtime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    成功时间：<input type="text" value="<?php echo \yii::$app->request->get('updated_at_begin', ''); ?>" name="updated_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo \yii::$app->request->get('updated_at_end', ''); ?>" name="updated_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    <input type="submit" name="search_submit"value="过滤" class="btn" />&nbsp;
    <?php if(isset($export) && $export) : ?>
        <input style="display: none" type="submit" name="submitcsv" value="异步导出csv" onclick="$(this).val('exportcsv');return true;" class="btn" />
        &nbsp;
        <input style="display: none" type="submit" name="submitcsv" value="直接导出csv" onclick="$(this).val('export_direct');return true;" class="btn" />
        <?php if ($view == 'list' && Yii::$app->request->get('status') == 7) : ?>
            <a href="#ex7">批量处理</a>
        <?php endif;?>
        <label><input type="checkbox" name="is_summary" value="1" <?php if(Yii::$app->request->get('is_summary', '0')==1):?> checked <?php endif; ?> /> 显示汇总(勾选后，查询变慢)</label>
    <?php endif; ?>
<?php ActiveForm::end(); ?>

<?php $ids = "";?>
<?php if (!empty($withdraws)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header">
            <?php if ($view == 'review'): ?>
                <th>选择</th>
            <?php endif;?>
            <?php if ($view == 'list' && Yii::$app->request->get('status') == 7): ?>
                <th>
                    <input type="checkbox" name="allItem" value="1">
                </th>
            <?php endif;?>
            <th>打款ID</th>
            <th>业务订单ID</th>
            <th>用户ID</th>
            <th style="width: 50px;">姓名</th>
            <?php if (empty($isother)): ?>
                <th>资方</th>
            <?php endif;?>
            <th>借款期限</th>
            <th>申请金额</th>
            <th>抵扣券</th>
            <th>手续费</th>
            <th>利息</th>
            <th>实际打款金额</th>
            <th style="width: 50px;">绑卡银行</th>
            <th>银行卡号</th>
            <th style="width: 50px;">业务类型</th>
            <th style="width: 50px;">打款渠道</th>
            <th style="width: 50px;">审核状态</th>
            <th style="width: 50px;">打款状态</th>
            <th style="width: 100px;">通知业务方结果</th>
            <th style="width: 100px;">第三方状态码</th>
            <th>审核人</th>
            <th>审核时间</th>
            <th>申请时间</th>
            <th>成功时间</th>
            <th>来源</th>
            <th>渠道</th>
            <th width="80">操作</th>
        </tr>
        <?php foreach ($withdraws as $value): ?>
        <tr class="hover">
            <?php if ($view == 'review'): ?>
                <td><input type="checkbox" value="<?php echo $value['id']; ?>"  id="id_<?php echo $value['id'];?>" name="id_<?php echo $value['id']; ?>" onclick="add_id(this)"></td>
            <?php endif;?>
            <?php if ($view == 'list' && Yii::$app->request->get('status') == 7): ?>
                <td>
                    <input type="checkbox" name="item" value="<?php echo $value['id']; ?>">
                </td>
            <?php endif;?>
            <td><?php echo $value['rid']; ?></td>
            <td>
            <?php if(in_array($value['type'], FinancialLoanRecord::$other_platform_type)){ ?>
            <a href="<?php echo Url::toRoute(['asset/orders-detail', 'id' => $value['business_id']]);?>">
            <?php echo $value['business_id']; ?>
            </a>
            <?php }else{?>
            <?php echo $value['business_id']; ?>
            <?php }?>
            </td>
            <td><?php echo $value['user_id']; ?></td>
            <td><?php echo empty($value['name']) ? "" : $value['name']; ?></td>
            <?php if (empty($isother)): ?>
                <td><?php echo !empty($all_funds[$value['fund_id']]) ? $all_funds[$value['fund_id']] :$fund_koudai->name; ?></td>
            <?php endif;?>
            <?php if($value['loan_method']==0){?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'天'?></td>
            <?php }elseif($value['loan_method']==1){?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'月'?></td>
            <?php }else{ ?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'年'?></td>
            <?php }?>
            <td><?php echo sprintf('%.2f', $value['money'] / 100); ?></td>
            <td><?php echo (!empty($value['coupon_money'])) ? sprintf('%.2f', $value['coupon_money'] / 100) : '-'; ?></td>
            <td><?php echo sprintf('%.2f', $value['counter_fee'] / 100); ?></td>
            <td><?php echo sprintf('%.2f',  (array_key_exists('interests', $value) ? $value['interests']: 0) / 100); ?></td>
            <td><?php echo sprintf('%.2f',  ($value['money'] - $value['counter_fee']) / 100); ?></td>
            <td><?php echo $value['bank_name']; ?></td>
            <td>
            <a href="<?php echo Url::toRoute(['financial/update-card-info', 'id' => $value['id']]); ?>">
            <?php echo $value['card_no'];?>
            </a>
            </td>
            <td>
                <?php
                    echo isset(FinancialLoanRecord::$types[$value['type']]) ? FinancialLoanRecord::$types[$value['type']] : "---";
                ?>
            </td>
            <td>
                <?php
                echo isset(FinancialLoanRecord::$payment_types[$value['payment_type']]) ? FinancialLoanRecord::$payment_types[ $value['payment_type']] : "-----";
                ?>
            </td>
            <td><?php echo FinancialLoanRecord::$review_status[$value['review_result']]; ?></td>
            <td><?php echo empty($value['status']) ? "---" : FinancialLoanRecord::$ump_pay_status[$value['status']]; ?></td>
            <td><?php

                    $notify =  json_decode($value['callback_result'], true);
                    echo empty($notify) ?  FinancialLoanRecord::$notify[FinancialLoanRecord::NOTIFY_WAITING] : FinancialLoanRecord::$notify[$notify['is_notify']];
                ?>
            </td>
            <td><?php echo is_null($value['remit_status_code']) ? '-' : $value['remit_status_code']; ?></td>
            <td><?php echo $value['review_username'] ? $value['review_username'] : '-'; ?></td>
            <td><?php echo $value['review_time'] ? date('Y-m-d H:i:s', $value['review_time']) : '-'; ?></td>
            <td><?php echo date('Y-m-d H:i', $value['created_at']); ?></td>
            <td><?php echo $value['success_time'] ? date('Y-m-d H:i:s', $value['success_time']) : '-'; ?></td>
            <td><?php echo isset($value['source_id']) ? \common\models\LoanPerson::$person_source[$value['source_id']] : '-'; ?></td>
            <td><?php echo isset($value['sub_order_type']) ? \common\models\UserLoanOrder::$sub_order_type[$value['sub_order_type']] : '-'; ?></td>
            <td>
                <?php if (($value['review_result'] == FinancialLoanRecord::REVIEW_STATUS_NO) || ($value['review_result'] == FinancialLoanRecord::REVIEW_STATUS_CMB_FAILED)): ?>
                    <a href="<?php echo Url::toRoute(['financial/withdraw-detail', 'id' => $value['id'], 'user_id' => $value['user_id']]); ?>">审核</a>
                <?php else: ?>
                    <a href="<?php echo Url::toRoute(['financial/withdraw-detail', 'id' => $value['id'], 'user_id' => $value['user_id']]); ?>">详情</a>
                <?php endif; ?>
                <?php if ($value['payment_type'] == FinancialLoanRecord::PAYMENT_TYPE_MANUAL && $value['status'] == FinancialLoanRecord::UMP_PAYING ): ?>
                    <a href="<?php echo Url::toRoute(['financial/withdraw-result', 'id' => $value['id'], 'order_id' => $value['order_id']]); ?>">操作</a>
                <?php else: ?>
                    <a href="<?php echo Url::toRoute(['financial/withdraw-result', 'id' => $value['id'], 'order_id' => $value['order_id']]); ?>">付款查询</a>
                <?php endif; ?>
                <?php if ($notify && $notify['is_notify'] == FinancialLoanRecord::NOTIFY_FALSE ): ?>
                    <a href="<?php echo Url::toRoute(['financial/set-callback-success', 'id' => $value['id']]); ?>">置为回调成功</a>
                <?php endif; ?>
                <?php if ($value['status'] == FinancialLoanRecord::UMP_PAY_DOUBLE_FAILED):?>
                    <a href="<?php echo Url::toRoute(['financial/set-new-card', 'id' => $value['id'] , 'user_id' => $value['user_id']]); ?>">更换银行卡</a>
                <?php endif; ?>
                <?php if ($value['status'] == FinancialLoanRecord::UMP_PAY_HANDLE_FAILED):?>
                    <a onclick="getRemitStatus(<?php echo $value['id'];?>)">获取第三方状态码</a>
                <?php endif; ?>
            </td>
            <?php $ids .= $value['id'].",";?>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if(!empty($withdraws) && $view == 'review') : ?>
        <table class="tb tb2 fixpadding">
            <tr>
                <?php
                $ids = substr($ids,0,strlen($ids)-1);
                ?>
                <input type="checkbox" value="<?php echo $ids?>" name="all_submit" onclick="add_ids(this.value, this.checked)" class="btn" /> 全选 &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" value="直连打款批量审核通过" name="submit_btn_update" id="submit_btn_update"  onclick="update()" class="btn" />
                &nbsp;&nbsp;&nbsp;
                <a class="btn" href="<?php echo Url::toRoute(['financial/all-withdraw-approve',
                    'type' =>  Yii::$app->request->get('type', ''),
                    'review_result' =>  Yii::$app->request->get('review_result', ''),
                    'status' =>  Yii::$app->request->get('status', ''),

                ])?>" >直连打款全部审核通过</a>
            </tr>
        </table>
    <?php endif;?>

    <?php
    $page = ceil($pages->totalCount / $pages->pageSize);
    ?>
    <?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
        <?php if(isset($dataSt) && !empty($dataSt)): ?>
            <table frame="above" align="right">
                <tr>
                    <td align="center" style="color: red;">申请金额总计：</td>
                    <td align="center" style="color: red;">手续费金总计：</td>
                    <td align="center" style="color: red;">实际打款金额总计：</td>
                </tr>
                <tr>
                    <td style="color: red;"><?php echo sprintf("%.2f",$dataSt['money'] / 100) ?></td>
                    <td align="right" style="color: red;"><?php echo sprintf("%.2f",$dataSt['counter_fee'] / 100) ?></td>
                    <td align="right" style="color: red;"><?php echo sprintf("%.2f",($dataSt['money']-$dataSt['counter_fee']) / 100) ?></td>
                </tr>
            </table>
        <?php endif; ?>
<?php else: ?>
    抱歉，暂时没有符合条件的记录！
<?php endif;?>
<div id="ex7" class="modal" style="display: none;">
    <table class="tb tb2 fixpadding">
        <?php $form = ActiveForm::begin(['id' => 'review-form','action'=>Url::toRoute('financial/batch-handle')]); ?>
        <input type="hidden" name="financialRecordArr" value="">
        <tr>
            <td class="td24">审核结果：</td>
            <td>
                <label><input type="radio" name="FinancialLoanRecord[review_result]" value="1"> 审核通过</label>
                <label><input type="radio" name="FinancialLoanRecord[review_result]" value="2"> 审核驳回</label>
            </td>
        </tr>
        <tr>
            <td class="td24">打款类型：</td>
            <td colspan="15">
                <div class="form-group field-financialloanrecord-payment_type">
                    <select id="financialloanrecord-payment_type" class="form-control" name="FinancialLoanRecord[payment_type]">
                        <option value="3">人工打款</option>
                        <option value="4" selected="">直连打款</option>
                    </select>

                    <div class="help-block"></div>
                </div>
            </td>
        </tr>

        <tr>
            <td class="td24">审核备注：</td>
            <td>
                <textarea id="financialloanrecord-review_remark" class="form-control" rows="10" cols="30" name="FinancialLoanRecord[review_remark]"></textarea>
            </td>
            <td class="td24"></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
        <?php ActiveForm::end(); ?>
    </table>
</div>
<script>

    var ids = [];

    function add_id(obj){
        var id_value = obj.value;
        if(obj.checked == true){
            ids.push(id_value);
        }else{
            ids  = remove(ids, id_value);
        }
    }

    function add_ids(keys, result){
        var cars = keys.split(",");
        if(result == true) {
            for (var i=0;i<cars.length;i++)
            {
                ids.push(cars[i]);
                $("#id_"+cars[i]).prop("checked", "checked");
            }
        }else {
            for (var j=0;j<cars.length;j++)
            {
                ids  = remove(ids, cars[j]);
                $("#id_"+cars[j]).prop("checked", false);
            }
        }

    }

    function update(){
        if(ids.length == 0){
            alert("请先选择要更改的数据记录！");
            return;
        }
        $.ajax({
            type: 'get',
            url: '<?php echo Url::toRoute(['financial/batch-withdraw-approve']); ?>&ids='+ids.join(),
            async: false,//同步刷新
            success: function(data) {
                var json = eval(data);
                var data_false = json['false_ids'];
                if(json['code'] == 0){
                    alert("全部更新成功");
                }else{
                    alert("更新失败的提现ID："+data_false.join());
                }
                window.location.reload();
            },
            error: function(){
                //请求出错处理
            }
        });
    }

    function remove(array, id_value){
        for(var i=0; i<array.length; i++)
        {
            if(array[i] == id_value)
            {
                array = removeElement(i, array);//删除方法
            }
        }
        return array;
    }

    function removeElement(index,array)
    {
        if(index>=0 && index < array.length)
        {
            for(var i=index; i<array.length; i++)
            {
                array[i] = array[i+1];
            }

            array.length = array.length-1;
        }
        return array;
    }

    function getRemitStatus(id)
    {
        if(!confirmMsg('确认获取')){
            return false;
        }
        $.ajax({
            type: 'get',
            url: '<?php echo Url::toRoute(['financial/get-remit-status']); ?>',
            data : {id:id}, success: function(data) {
                var json = eval(data);
                if(json['code'] == 0){
                    alert(data.msg);
                    window.location.reload();
                }else{
                    alert(data.msg);
                }

            },
            error: function(){
                //请求出错处理
            }
        });
    }

</script>
<script type="text/javascript">
    $(function(){
        $("input[name='allItem']").click(function()
        {
            if(this.checked)$("input[name='item']").each(function(i,e){$(e).prop("checked",true)});
            else $("input[name='item']").each(function(i,e){$(e).prop("checked",false)});
        });
        $("input[name='item']").each(function(i,e){
            $(e).click(function(){
                if ($("input[name='item']").length == $("input[name='item']:checked").length){
                    $("input[name='allItem']").prop("checked",true)
                } else {
                    $("input[name='allItem']").prop("checked",false)
                }
            });
        });
    })

    $(function () {
        $('.click').click(function () {
            $('.bg').css({'display': 'block'});
            $('.content').css({'display':'block'});});
            $('.bg').click(function () {
                $('.bg').css({'display': 'none'});
                $('.content').css({'display': 'none'});
            });
        });

</script>
<script type="text/javascript">
    $("#ex7").on($.modal.BEFORE_BLOCK,function(event,modal){
        var financialRecordIds = [];
        $("table tr input[name=item]:checked").each(function(i,e){
            financialRecordIds[i] = $(e).val();
        });
        $("input[name=financialRecordArr]").val(financialRecordIds.join(','))
    });
    $('a[href="#ex7"]').click(function(event) {
        event.preventDefault();
        $(this).modal({fadeDuration: 250});
    });
</script>
