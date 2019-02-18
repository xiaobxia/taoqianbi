<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use common\models\CreditJxlQueue;
use backend\components\widgets\ActiveForm;
use backend\components\widgets\LinkPager;

$this->showsubmenu('用户运营商认证状态');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
用户名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" >
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" >
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('uid', ''); ?>" name="uid" >
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <td>ID</td>
                <td>用户ID</td>
                <td>用户名</td>
                <td>手机号</td>
                <td>当前状态</td>
                <td>反馈信息</td>
                <td>第三方token</td>
                <td>更新时间</td>
            </tr>
            <?php if(!empty($info)):?>
            <?php foreach ($info as $v):?>
                <tr>
                    <td><?php echo $v['id'];?></td>
                    <td><?php echo $credit_jxl_data[$v['id']]['user_id'];?></td>
                    <td><?php echo $v['name'];?></td>
                    <td><?php echo $v['phone'];?></td>
                    <td><?php echo CreditJxlQueue::$current_status_list[$credit_jxl_data[$v['id']]['current_status']];?></td>
                    <td><?php echo $credit_jxl_data[$v['id']]['message'];?></td>
                    <td><?php echo $credit_jxl_data[$v['id']]['token'];?></td>
                    <td><?php echo date('Y-m-d H:i:s',$credit_jxl_data[$v['id']]['updated_at']);?></td>
                </tr>
            <?php endforeach;?>
            <?php else:?>
                <tr>
                    <td>暂无数据</td>
                </tr>
            <?php endif;?>
        </table>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

