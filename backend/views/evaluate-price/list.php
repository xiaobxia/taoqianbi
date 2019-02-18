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
    订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:80px;">&nbsp;
    业务员姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_name', ''); ?>" name="user_name" class="txt" style="width:80px;">&nbsp;
    业务业务员手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_mobile', ''); ?>" name="user_mobile" class="txt" style="width:120px;">&nbsp;
    机构名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_name', ''); ?>" name="shop_name" class="txt" style="width:120px;">&nbsp;
    房产具体地址：<input type="text" value="<?php echo Yii::$app->getRequest()->get('address', ''); ?>" name="address" class="txt" style="width:120px;">&nbsp;
    授信类型：<?php echo Html::dropDownList('evaluate_type', Yii::$app->getRequest()->get('evaluate_type', ''), HfdOrder::$evaluate_type); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th rowspan="2">ID</th>
            <th rowspan="2">订单号</th>
            <th rowspan="2">房产地址</th>
            <th rowspan="2">面积</th>
            <th rowspan="2">年限</th>
            <th colspan="4" style="text-align:center;">授信额度</th>
            <th rowspan="2">授信类型</th>
            <th rowspan="2">业务员姓名</th>
            <th rowspan="2">业务员手机号</th>
            <th rowspan="2">机构名称</th>
        </tr>
        <tr class="header">
            <th>城市授信</th>
            <th>世联授信</th>
            <th>快借授信</th>
            <th>最终授信</th>
        </tr>
        <?php foreach ($list as $value): ?>

            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['order_id']; ?></td>
                <td><?php echo empty($value['address'])?"--":$value['address']; ?></td>
                <td><?php echo empty($value['area'])?"--":$value['area'].'平方米'; ?></td>
                <td><?php echo empty($value['year'])?"--":$value['year'].'年'; ?></td>
                <td><?php echo isset($hfd_price[$value['order_id']]['cl_price'])?$hfd_price[$value['order_id']]['cl_price'].'元':"--"; ?></td>
                <td><?php echo isset($hfd_price[$value['order_id']]['sl_price'])?$hfd_price[$value['sl_price']]['cl_price'].'元':"--"; ?></td>
                <td><?php echo isset($hfd_price[$value['order_id']]['kj_price'])?$hfd_price[$value['kj_price']]['cl_price'].'元':"--"; ?></td>
                <td><?php echo isset($hfd_price[$value['order_id']]['price'])?$hfd_price[$value['price']]['cl_price'].'元':"--"; ?></td>
                <td><?php echo isset(HfdOrder::$evaluate_type[$value['evaluate_type']])?HfdOrder::$evaluate_type[$value['evaluate_type']]:"--"; ?></td>
                <td><?php echo isset($user_info[$value['user_id']]['name'])?$user_info[$value['user_id']]['name']:"--"; ?></td>
                <td><?php echo isset($user_info[$value['user_id']]['phone'])?$user_info[$value['user_id']]['phone']:"--"; ?></td>
                <td><?php echo isset($shop_info[$value['shop_code']]['shop_name'])?$shop_info[$value['shop_code']]['shop_name']:"--"; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>