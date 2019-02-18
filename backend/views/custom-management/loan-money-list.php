<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\FinancialLoanRecord;
use common\models\BankConfig;
use common\models\UserLoanOrder;

$this->shownav('service', 'menu_user_pay_list');
$this->showsubmenu('打款列表');

?>

<table class="tb tb2 ">
	<tr><td class="tipsblock"><ul><li>提现审核通过，只是向第三方支付平台发起提现申请（用户端仍然显示为提现中），提现结果第三方支付平台会异步通知或发起主动查询</li></ul></td></tr>
</table>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
	打款ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('rid', ''); ?>" name="rid" class="txt" style="width:120px;"/>&nbsp;
	订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;"/>&nbsp;
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;"/>&nbsp;
	用户名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('username', ''); ?>" name="username" class="txt" style="width:120px;"/>&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;"/>&nbsp;
    借款期限：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_term', ''); ?>" name="loan_term" class="txt" style="width:120px;"/>&nbsp;
	<input type="submit" name="search_submit" value="搜索" class="btn"/>
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php ActiveForm::end(); ?>
<style>
    .header th{ text-align: center;}
</style>
<?php $ids = "";?>
<?php if (!empty($withdraws)):?>
    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header">
            <?php if ($view == 'review'): ?>
                <th>选择</th>
            <?php endif;?>
            <th>打款ID</th>
            <th>业务订单ID</th>
            <th>用户ID</th>
            <th style="width: 50px;">姓名</th>
            <th>借款期限</th>
            <th>申请金额</th>
            <th>手续费</th>
            <th>实际打款金额</th>
            <th style="width: 50px;">绑卡银行</th>
            <th>银行卡号</th>
            <th style="width: 50px;">业务类型</th>
            <th style="width: 50px;">打款渠道</th>
            <th style="width: 50px;">审核状态</th>
            <th style="width: 50px;">打款状态</th>
            <th style="width: 100px;">通知业务方结果</th>
            <th>审核人</th>
            <th>审核时间</th>
            <th>申请时间</th>
            <th>成功时间</th>
            <th width="80">操作</th>
        </tr>
        <?php foreach ($withdraws as $value): ?>
        <tr class="hover">
            <?php if ($view == 'review'): ?>
                <td><input type="checkbox" value="<?php echo $value['id']; ?>"  id="id_<?php echo $value['id'];?>" name="id_<?php echo $value['id']; ?>" onclick="add_id(this)"></td>
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
            <?php if($value['loan_method']==0){?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'天'?></td>
            <?php }elseif($value['loan_method']==1){?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'月'?></td>
            <?php }else{ ?>
            <td><?php echo empty($value['loan_term'])?'':$value['loan_term'].'年'?></td>
            <?php }?>
            <td><?php echo sprintf('%.2f', $value['money'] / 100); ?></td>
            <td><?php echo sprintf('%.2f', $value['counter_fee'] / 100); ?></td>
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
            <td><?php echo $value['review_username'] ? $value['review_username'] : '-'; ?></td>
            <td><?php echo $value['review_time'] ? date('Y-m-d H:i:s', $value['review_time']) : '-'; ?></td>
            <td><?php echo date('Y-m-d H:i', $value['created_at']); ?></td>
            <td><?php echo $value['success_time'] ? date('Y-m-d H:i:s', $value['success_time']) : '-'; ?></td>
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
            </td>
            <?php $ids .= $value['id'].",";?>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if(!empty($withdraws) && $view == 'review'):?>
        <table class="tb tb2 fixpadding">
            <tr>
                <?php
                $ids = substr($ids,0,strlen($ids)-1);
                //                    $ids = explode(",", $ids);
                ?>
                <input type="checkbox" value="<?php echo $ids?>" name="all_submit" onclick="add_ids(this.value, this.checked)" class="btn"> 全选 &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" value="直连打款批量审核通过" name="submit_btn_update" id="submit_btn_update"  onclick="update()" class="btn">
                &nbsp;&nbsp;&nbsp;
                <a class="btn" href="<?php echo Url::toRoute(['financial/all-withdraw-approve',
                    'type' =>  Yii::$app->getRequest()->get('type', ''),
                    'review_result' =>  Yii::$app->getRequest()->get('review_result', ''),
                    'status' =>  Yii::$app->getRequest()->get('status', ''),

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
</script>