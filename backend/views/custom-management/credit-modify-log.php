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
use common\models\UserCreditReviewLog;


$this->shownav('service', 'menu_user_credit_log');
$this->showsubmenu('信用额度调整流水');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:80px;"/>&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;"/>&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;"/>&nbsp;
<input type="submit" name="search_submit" value="搜索" class="btn"/>
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>手机号</th>
            <th>类型</th>
            <th>调整前额度（元）</th>
            <th>调整数额（元）</th>
            <th>调整后额度（元）</th>
            <th>创建人</th>
            <th>审核人</th>
            <th>创建时间</th>
            <th>备注</th>
        </tr>
        <?php foreach ($list as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['name']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo !empty($value['type']) ? UserCreditReviewLog::$type[$value['type']] : "--"; ?></td>
                <?php if($value['type'] == UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT) : ?>
                    <td><?php echo ($value['before_number'] / 100); ?></td>
                    <td><?php echo ($value['operate_number'] / 100); ?></td>
                    <td><?php echo ($value['after_number'] / 100); ?></td>
                <?php elseif($value['type'] == UserCreditReviewLog::TYPE_POCKET_APR || $value['type'] == UserCreditReviewLog::TYPE_POCKET_REGISTER_APR) : ?>
                    <td><?php echo sprintf("%.2f",$value['before_number'])." (万分之)"; ?></td>
                    <td><?php echo sprintf("%.2f",$value['operate_number'])." (万分之)"; ?></td>
                    <td><?php echo sprintf("%.2f",$value['after_number'])." (万分之)"; ?></td>
                <?php elseif($value['type'] == UserCreditReviewLog::TYPE_HOUSE_APR || $value['type'] == UserCreditReviewLog::TYPE_HOUSE_REGISTER_APR) :?>
                    <td><?php echo sprintf("%.2f",$value['before_number'])." (百分之)"; ?></td>
                    <td><?php echo sprintf("%.2f",$value['operate_number'])." (百分之)"; ?></td>
                    <td><?php echo sprintf("%.2f",$value['after_number'])." (百分之)"; ?></td>
                <?php endif; ?>
                <td><?php echo $value['creater_name']; ?></td>
                <td><?php echo empty($value['operator_name']) ? "--" : $value['operator_name']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                <td><?php echo $value['remark']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php if(!empty($pages)):?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php endif;?>