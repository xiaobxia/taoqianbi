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
use common\models\UserLoanOrder;
$this->showsubmenu('初审列表');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('oid', ''); ?>" name="oid" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('uid', ''); ?>" name="uid" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
拒绝理由：<input type="text" value="<?php echo Yii::$app->getRequest()->get('reject_reason', ''); ?>" name="reject_reason" class="txt" style="width:120px;">&nbsp;
拒绝时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>用户ID</th>
                <th>姓名</th>
                <th>手机号</th>
                <th>订单号</th>
                <th>拒绝理由</th>
                <th>拒绝时间</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['name']; ?></td>
                    <td><?php echo $value['phone']; ?></td>
                    <td><?php echo $value['order_id']; ?></td>
                    <td><?php echo $value['reject_reason']; ?></td>
                    <td><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
