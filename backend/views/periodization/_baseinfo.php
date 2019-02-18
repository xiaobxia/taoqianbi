<?php
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use yii\helpers\Html;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use backend\models\PhoneReviewLog;
use common\models\CreditZmop;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">基本信息</th></tr>
    <tr>
        <td class="td22">ID：</td>
        <td width="200"><?php echo $user['id']; ?></td>
        <td class="td22">姓名：</td>
        <td width="200"><?php echo $user['realname']; ?></td>
        <td class="td22">性别：</td>
        <td width="200"><?php echo $user['sex']; ?></td>
        <td class="td22">手机号：</td>
        <td width="200"><?php echo $user['phone']; ?></td>
    </tr>
    <tr>
        <td class="td22">身份证：</td>
        <td width="200"><?php echo $user['id_card']; ?></td>
        <td class="td22">注册时间：</td>
        <td><?php echo $user['created_at']; ?></td>
        <td class="td22">注册设备：</td>
        <td><?php echo $user['reg_os']; ?></td>
        <td class="td22">注册来源：</td>
        <td><?php echo $user['source']; ?></td>
    </tr>
</table>
<?php if(!empty($loanContract)):?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">合同信息</th></tr>
        <tr>
            <td class="td22">合同ID：</td>
            <td><?php echo $loanContract['id']; ?></td>
            <td class="td22">合同详情：</td>
            <td>
                <?php if(!empty($loanContract['contract_url'])): ?>
                    <a target="_blank" href="http://res.koudailc.com/kdfq_shop/<?php echo $loanContract['contract_url']; ?>"><?php echo $loanContract['contract_url']; ?></a>
                <?php else: ?>
                    暂未签约
                <?php endif;?>
            </td>
        </tr>
    </table>
<?php endif;?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">订单信息</th></tr>
    <tr>
        <td class="td22">商品名称：</td>
        <td width="200"><?php echo $indiana_order['indiana']['title']; ?></td>
        <td class="td22">商品规格：</td>
        <td width="200"><?php echo $indiana_order['installment_option']; ?></td>
        <td class="td22">商品价格：</td>
        <td width="200"><?php echo $indiana_order['indiana']['installment_price']; ?> 元</td>
    </tr>
    <tr>

        <td class="td22">分期期数：</td>
        <td width="200"><?php echo $indiana_order['installment_month']; ?> 期</td>
        <td class="td22">收获地址：</td>
        <td width="200"><?php echo empty(!$indiana_order['shipping_address'])?$indiana_order['shipping_address']:'---';?></td>
        <td class="td22">申请时间：</td>
        <td width="200"><?php echo $indiana_order['created_at']; ?></td>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">账户信息</th></tr>
    <tr>
        <td class="td22">总资产：</td>
        <td width="200"><?php echo $user_account['total_money']; ?> 元</td>
        <td class="td22">可用余额：</td>
        <td width="200"><?php echo $user_account['usable_money']; ?> 元</td>
        <td class="td22">净资产：</td>
        <td width="200"><?php echo $user_account['net_assets']; ?> 元</td>

    </tr>
    <tr>
        <td class="td22">待收本金：</td>
        <td width="200"><?php echo $user_account['duein_capital']; ?> 元</td>
        <td class="td22">申购中金额：</td>
        <td width="200"><?php echo $user_account['investing_money']; ?> 元</td>
        <td class="td22">口袋宝总金额：</td>
        <td width="200"><?php echo $user_account['kdb_total_money']; ?> 元</td>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">资产核对</th></tr>
    <tr>
        <td class="td22"">净资产：</td>
        <td width="200""><?php echo $user_account['net_assets']; ?> 元</td>
        <td class="td22"">待还总额：</td>
        <td width="200""><?php echo $user_account['duein_repay']; ?> 元</td>
        <td class="td22"">商品价格：</td>
        <td width="200""><?php echo $indiana_order['indiana']['installment_price']; ?> 元</td>
    </tr>
    <tr>
        <td class="td22" style="color:red">剩余资产：</td>
        <td width="200" ><strong style="color:red"><?php echo $user_account['net_assets'] - $user_account['duein_repay'] - $indiana_order['indiana']['installment_price']; ?> 元&nbsp（净资产-待还总额-商品价格）&nbsp</strong></td>
        <td class="td22"></td>
        <td width="200"></td>
        <td class="td22"></td>
        <td width="200"></td>
    </tr>
</table>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span>查询芝麻信用记录</span>
            <?php if(!empty($loan_person['creditZmop']['status'])): ?>
                &nbsp;
                <a style="color:red" onclick="return confirm('确认获取')" href="<?php echo Url::toRoute(['zmop/get-all-info','person_id'=>$loan_person['id']]);?>">点击获取芝麻信用所有信息</a>
            <?php endif;?>
        </th>

    </tr>
    <?php if(!empty($loan_person['creditZmop']['open_id'])): ?>
        <tr>
            <td class="td24">
                芝麻信用评分</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-score', 'id' => $loan_person['id']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if (! empty($loan_person['creditZmop']['zm_score'])): ?>
                    信用评分：<?php echo $loan_person['creditZmop']['zm_score'] ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得手机RAIN分</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-mobile-rain', 'id' => $loan_person['id']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['rain_info'])): ?>
                    RAIN分(取值为0-100。得分越高，风险越高)：<?php echo $loan_person['creditZmop']['rain_score'] ?> </br>
                    <?php foreach (json_decode($loan_person['creditZmop']['rain_info']) as $val): ?>
                        <?php echo $val->name ?>: <?php echo $val->description ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得行业关注名单</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-watch-list', 'id' => $loan_person['id']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if (! empty($loan_person['creditZmop']['watch_info'])): ?>
                    <?php foreach(json_decode($loan_person['creditZmop']['watch_info']) as $val ): ?>
                        风险信息行业：<?php echo CreditZmop::$iwatch_type[$val->biz_code] ?> <br/>
                        风险类型：<?php echo CreditZmop::$risk_type[$val->type] ?> <br/>
                        风险说明：<?php echo CreditZmop::$risk_code[$val->code] ?> <br/>
                        负面信息或者风险信息：<?php echo $val->level ?> (取值：1=有负面信息，2=有风险信息)<br/>
                        数据刷新时间：<?php echo $val->refresh_time ?> <br/>
                        <?php if(!empty($val->extend_info)) :?>
                            <?php foreach($val->extend_info as $v): ?>
                                芝麻信用申诉id: <?php echo $v->value;?><br/>
                            <?php endforeach ?>
                        <?php endif ?>
                        <br/>
                    <?php endforeach ?>
                <?php elseif($loan_person['creditZmop']['watch_matched'] == 0): ?>
                    行业关注未匹配
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        <tr>
            <td class="td24">
                获得IVS信息验证信息</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-ivs-detail', 'id' => $loan_person['id']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['ivs_info'])): ?>
                    IVS评分(取值区间为0-100。分数越高，表示可信程度越高。0表示无对应数据)：<?php echo $loan_person['creditZmop']['ivs_score'] ?> </br>
                    <?php foreach(json_decode($loan_person['creditZmop']['ivs_info']) as $val): ?>
                        <?php echo $val->description ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得DAS认证信息</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-zmop-credit-das', 'id' => $loan_person['id']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['das_info'])): ?>
                    <?php foreach(json_decode($loan_person['creditZmop']['das_info']) as $v): ?>
                        <?php echo CreditZmop::$das_keys[$v->key] . '：' . $v->value ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>
    <?php else: ?>
        <tr>
            <td class="td24">暂时没有通过芝麻信用</td>
        </tr>
        <tr>
            <td class="td24"><a href="<?php echo Url::toRoute(['zmop/batch-feedback', 'id' => $loan_person['id'], 'type' => $loan_person['type'] ])?>">点击发送短信授权</a></td>
        </tr>
    <?php endif;?>
</table>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition">联系记录</th>
        <th class="partition" colspan="15">
            <span style="cursor:pointer;" class="addtr" onclick="addTr()">新增联系</span>
        </th>
    </tr>
    <?php if(!empty($phoneReviewLog)): ?>
			<tr>
				<th class="td22">联系人</th>
				<th class="td22">类型</th>
				<th class="td22">联系内容</th>
				<th class="td22">联系时间</th>
			</tr>
        <?php foreach($phoneReviewLog as $value):?>
            <tr>
                <td><?php echo $value['auditor'];?></td>
                <td><?php echo empty(PhoneReviewLog::$type_list[$value['type']]) ? "--" : PhoneReviewLog::$type_list[$value['type']];?></td>
                <td><?php echo $value['content'];?></td>
				<td><?php echo $value['time'];?></td>
            </tr>
        <?php endforeach;?>
    <?php else:?>
        <tr>
            <td colspan="16">暂无联系记录</td>
        </tr>
    <?php endif;?>
</table>

<table style="display: none" id="phone-review">
    <?php $form = ActiveForm::begin(['action'=>['periodization/phone-review-log-add','id'=>$indiana_order['id']],'id' => 'searchform', 'method' => "post",'options' => ['style' => 'margin-bottom:5px;']]); ?>
    <tr>
        <td class="td27" colspan="2">联系类型:</td>
        <td><?php echo Html::DropDownList('type', Yii::$app->getRequest()->get('type', ''), PhoneReviewLog::$type_list, ['prompt' => '-请选择类型-']); ?></td>
        <td width="60px">联系时间：</td>
        <td width="200px">
            <input type="text" name="phone-review-time" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})"/>
        </td>
        <td>
            <span width="60px">联系内容：</span>
        </td>
        <td style="width:450px">
            <textarea name="phone-review-log" style="width:400px" row="4"></textarea>
        </td>
        <td>
            <input type="submit" value="添加记录" class="btn"/>
        </td>
    </tr>
    <?php ActiveForm::end(); ?>
</table>

<?php if('review' == $action):?>
    <table style="margin-top:20px;margin-bottom: 20px" class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="15">备注</th>
        </tr>
        <tr>
            <td><textarea id="remark"  style="width:600px"></textarea></td>
        </tr>
        <tr>
            <th colspan="15">
                <button class="btn" onclick="reviewPass()">审核通过</button>
                <button class="btn" onclick="reviewNoPass()">拒绝</button>
            </th>
        </tr>
    </table>
<?php elseif('check' == $action):?>
    <table style="margin-top:20px;margin-bottom: 20px" class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="15">备注</th>
        </tr>
        <tr>
            <td><textarea id="remark"  style="width:600px"></textarea></td>
        </tr>
        <tr>
            <th colspan="15">
                <button class="btn" onclick="checkPass()">复核通过</button>
                <button class="btn" onclick="checkNoPass()">复核拒绝</button>
            </th>
        </tr>
    </table>
<?php else:?>
    <?php if(!empty($loanRecordPeriod)): ?>
        <table class="tb tb2 fixpadding">
            <tr>
                <th class="partition" colspan="15">备注</th>
            </tr>
            <tr>
                <td><?php echo $loanRecordPeriod['remark'];?></td>
            </tr>
        </table>
    <?php endif;?>

<?php endif;?>
<script>
    function reviewNoPass(){
        if(!confirm('确认拒绝')) return false;
        var remark = document.getElementById("remark").value;
        if(!remark){
            alert('请填写备注');
            return false;
        }

        var params = {
            'id' : <?php echo $indiana_order['id']; ?>,
            'loan_record_period_id' : <?php echo $indiana_order['loan_record_period_id']; ?>,
            'remark' : remark,
            '<?php echo Yii::$app->getRequest()->csrfParam; ?>': '<?php echo Yii::$app->getRequest()->getCsrfToken(); ?>'
        };
        $.ajax({
            type: 'post',
            url: '<?php echo Url::toRoute(['periodization/review-reject']) ; ?>',
            data : params,
            success: function(data) {
                if(0 == data.code) {
                    alert('成功!');
                    window.location.href = '<?php echo Url::toRoute(['periodization/periodization-detail','id'=>Yii::$app->request->get('id')]);?>';
                } else if(-1 == data.code){
                    alert(data.message)
                }else{
                    alert('内部错误')
                }
            },
            error: function($ok){
                alert('内部错误')
            }
        });
    }
    function reviewPass(){
        if(!confirm('确认通过')) return false;
        var remark = document.getElementById("remark").value;
        if(!remark){
            alert('请填写备注');
            return false;
        }
        window.location.href = '<?php echo Url::toRoute([
                'periodization/contract-fill',
                'id'=>$indiana_order['id'],
                'loan_record_period_id'=>$indiana_order['loan_record_period_id']
            ]);?>'+'&remark='+remark;

    }

    function checkPass(){
        if(!confirm('确认通过')) return false;
        var remark = document.getElementById("remark").value;
        if(!remark){
            alert('请填写备注');
            return false;
        }
        var params = {
            'id' : <?php echo $indiana_order['id']; ?>,
            'loan_record_period_id' : <?php echo $indiana_order['loan_record_period_id']; ?>,
            'remark' : remark,
            '<?php echo Yii::$app->getRequest()->csrfParam; ?>': '<?php echo Yii::$app->getRequest()->getCsrfToken(); ?>'
        };
        $.ajax({
            type: 'post',
            url: '<?php echo Url::toRoute(['periodization/check-pass']) ; ?>',
            data : params,
            success: function(data) {
                if(0 == data.code) {
                    alert('复核成功!');
                    window.location.href = '<?php echo Url::toRoute(['periodization/periodization-list']);?>';
                } else {
                    alert(data.message);
                }
            },
            error: function($ok){
                alert('内部错误')
            }
        });
    }

    function checkNoPass(){
        if(!confirm('确认拒绝')) return false;
        var remark = document.getElementById("remark").value;
        if(!remark){
            alert('请填写备注');
            return false;
        }

        var params = {
            'id' : <?php echo $indiana_order['id']; ?>,
            'loan_record_period_id' : <?php echo $indiana_order['loan_record_period_id']; ?>,
            'remark' : remark,
            '<?php echo Yii::$app->getRequest()->csrfParam; ?>': '<?php echo Yii::$app->getRequest()->getCsrfToken(); ?>'
        };
        $.ajax({
            type: 'post',
            url: '<?php echo Url::toRoute(['periodization/check-reject']) ; ?>',
            data : params,
            success: function(data) {
                if(0 == data.code) {
                    alert('成功!');
                    window.location.href = '<?php echo Url::toRoute(['periodization/periodization-list']);?>';
                } else if(-1 == data.code){
                    alert(data.message)
                }else{
                    alert('内部错误')
                }
            },
            error: function($ok){
                alert('内部错误')
            }
        });
    }

    function addTr(){
        var phone_review = document.getElementById("phone-review");
        if(phone_review.style.display == 'block'){
            phone_review.style.display = 'none';
        }else{
            phone_review.style.display = 'block';
        }

    }

</script>