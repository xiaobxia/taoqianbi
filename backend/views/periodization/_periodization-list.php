<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanRecordPeriod;
use common\models\LoanPerson;
use common\models\User;
use common\models\IndianaOrder;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    借款人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    联系方式：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    借款人身份证：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id_number', ''); ?>" name="id_number" class="txt" style="width:120px;">&nbsp;
    审核状态：
    <select name="status" value="<?php echo Yii::$app->getRequest()->get('status', ''); ?>">
        <option value="0">--全部状态--</option>
        <?php foreach($fqsc_status as $k=>$v): ?>
            <?php if($k == Yii::$app->getRequest()->get('status')):?>
                <option value="<?php echo $k;?>" selected ><?php echo $v;?></option>
            <?php else:?>
                <option value="<?php echo $k;?>"><?php echo $v;?></option>
            <?php endif;?>
        <?php endforeach;?>
    </select>
    按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>借款人ID</th>
                <th>借款人姓名</th>
                <th>借款人类型</th>
                <th>借款人手机</th>
                <th>借款人身份证</th>
                <th>商品名称</th>
                <th>商品规格</th>
                <th>商品金额(元)</th>
                <th>分期数(月)</th>
                <th>状态</th>
                <th>寄送地址</th>
                <th>下单时间</th>
                <th>是否满足分期要求</th>
                <th>操作</th>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['loanPerson']['id']; ?></td>
                    <th><?php echo $value['loanPerson']['name']; ?></th>
                    <th><?php echo empty($value['user']['source']) ? '--' : User::$source_list[$value['user']['source']]; ?></th>
                    <th><?php echo $value['loanPerson']['phone']; ?></th>
                    <th><?php echo $value['loanPerson']['id_number']; ?></th>
                    <th><?php echo isset($value['loanPrecordPeriod']['product_type_name'])?$value['loanPrecordPeriod']['product_type_name']:''; ?></th>
                    <th><?php echo $value['installment_option']; ?></th>
                    <th><?php echo isset($value['loanPrecordPeriod']['amount']) ? number_format($value['loanPrecordPeriod']['amount']/100,2):'0.00'; ?></th>
                    <th><?php echo isset($value['loanPrecordPeriod']['period']) ? $value['loanPrecordPeriod']['period']:'0'; ?></th>
                    <th><?php echo isset(LoanRecordPeriod::$fqsc_status_msg[$value['loanPrecordPeriod']['status']]) ? LoanRecordPeriod::$fqsc_status_msg[$value['loanPrecordPeriod']['status']]:''; ?></th>
                    <th><?php echo $value['shipping_address']; ?></th>
                    <th><?php echo date("Y-m-d H:i:s" , $value['created_at']); ?></th>
                    <th><?php
                        $is_fee = $loanService->verifyReqChance($value['uid'], $value['loanPrecordPeriod']['amount']*IndianaOrder::RATE);
                        echo $is_fee ? '<span style="color:#fd5353">是</span>' : '否';
                     ?></th>
                    <th>
                        <a href="<?php echo Url::toRoute(['periodization/periodization-detail', 'id' => $value['id']]);?>">查看</a>
                        <?php if($value['loanPrecordPeriod']['status'] == LoanRecordPeriod::STATUS_APPLY_CAR_APPLYING):?>
                            <a href="<?php echo Url::toRoute(['periodization/periodization-review', 'id' => $value['id']]);?>">审核</a>
                        <?php endif;?>
                        <?php if($value['loanPrecordPeriod']['status'] == LoanRecordPeriod::STATUS_APPLY_MONEY_APPLY):?>
                            <a href="<?php echo Url::toRoute(['periodization/shipping', 'id'=>$value['id']]); ?>">发货</a>
                        <?php endif;?>
                        <?php if($value['loanPrecordPeriod']['status'] == LoanRecordPeriod::STATUS_APPLY_REVIEW_APPLY):?>
                            <a href="<?php echo Url::toRoute(['periodization/periodization-check', 'id' => $value['id']]);?>">复核</a>
                        <?php endif;?>
                        <?php if(in_array($value['loanPrecordPeriod']['status'],[LoanRecordPeriod::STATUS_APPLY_SIGN,LoanRecordPeriod::STATUS_APPLY_MONEY_APPLY,LoanRecordPeriod::STATUS_APPLY_REPAYING,LoanRecordPeriod::STATUS_APPLY_REPAY_SUCCESS,LoanRecordPeriod::STATUS_APPLY_MONEY_FALSE])):?>
                            <a target="_blank" href="<?php echo Url::toRoute(['periodization/contract-cat', 'id'=>$value['id']]); ?>">查看合同</a>
                        <?php endif;?>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>