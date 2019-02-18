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
use common\models\HfdOrder;
?>
    <style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;">&nbsp;
    房产证号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('house_num', ''); ?>" name="house_num" class="txt" style="width:120px;">&nbsp;
    业务员姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    业务员手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('mobile', ''); ?>" name="mobile" class="txt" style="width:120px;">&nbsp;
    机构代码：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_code', ''); ?>" name="shop_code" class="txt" style="width:120px;">&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), HfdOrder::$status,array('prompt' => '-所有状态-')); ?>&nbsp;
    下单时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">

    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>id</th>
            <th>订单号</th>
            <th>房产证号</th>
            <th>房产类型</th>
            <th>申请贷款金额(万元)</th>
            <th>授信金额(万元)</th>
            <th>借款期限</th>
            <th>业务员ID</th>
            <th>业务员姓名</th>
            <th>业务员手机</th>
            <th>业务员机构代码</th>
            <th>业务员机构</th>
            <th>订单状态</th>
            <th>订单创建时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($data as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <th><?php echo $value['order_id']; ?></th>
                <th><?php echo $value['house_num']; ?></th>
                <th><?php echo isset(HfdOrder::$property_ownership_certificate_type[$value['house_type']])?HfdOrder::$property_ownership_certificate_type[$value['house_type']]:"";?></th>
                <th><?php echo sprintf("%0.2f",$value['apply_money']/1000000); ?></th>
                <th><?php echo sprintf("%0.2f",$value['ture_money']/1000000); ?></th>
                <th><?php echo $value['loan_period'].'月'; ?></th>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['name']; ?></td>
                <th><?php echo $value['phone']; ?></th>
                <th><?php echo $value['shop_code']; ?></th>
                <th><?php echo isset($shop_data[$value['shop_code']])?$shop_data[$value['shop_code']]:""; ?></th>
                <th><?php echo isset(HfdOrder::$status[$value['status']])?HfdOrder::$status[$value['status']]:"" ; ?></th>
                <th><?php echo date('Y-m-d H:i:s',$value['created_at']); ; ?></th>
                <th>
                    <a href="<?php echo Url::toRoute(['hfd-bl/order-detail', 'id' => $value['id'],'order_id'=>$value['order_id']]); ?>">详情</a>

                </th>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($data)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>