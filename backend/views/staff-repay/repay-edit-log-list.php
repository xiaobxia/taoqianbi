<?php
/**
 * Created by PhpDesigner.
 * User: user
 * Date: 2017-1-12
 * Time: 12:00
 */

use common\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
$this->shownav('loan', 'menu_ygb_zc_lqd_repay_edit_log');
$this->showsubmenu('修改实际还款金额日志列表');
?>

<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
订单ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('order_id', ''); ?>" name="order_id" class="txt" style="width:120px;"/>&nbsp;
修改时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%ss',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})"/>

<input type="submit" name="search_submit" value="过滤" class="btn"/>
<table class="tb tb2 fixpadding">
            <tr class="header">
                <th>订单ID</th>
                <th>修改前金额</th>
                <th>修改后金额</th>
                <th>修改原因</th>
                <th>修改人</th>
                <th>实际还款时间</th>
                <th>修改时间</th>
            </tr>
             <?php foreach ($repay as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['order_id']; ?></td>
                    <td><?php echo sprintf("%0.2f",$value['true_total_money']/100); ?></td>
                    <td><?php echo sprintf("%0.2f",$value['true_repay_money']/100); ?></td>
                    <td><?php echo $value['remark']; ?></td>
                    <td><?php echo $value['operator_id']; ?></td>
                    <td><?php echo date('Y-m-d H:i:s',$value['true_repayment_time']); ?></td>
                    <td><?php echo date('Y-m-d H:i:s',$value['updated_at']); ?></td>

                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($repay)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php $form = ActiveForm::end(); ?>