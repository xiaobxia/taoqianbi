<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use backend\components\widgets\ActiveForm;
use common\helpers\Url;
use yii\helpers\Html;
use common\models\UserLoanOrderForzenRecord;

?>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >冻结记录</th></tr>
    <tr>
        <?php if (empty($user_forzen_record)): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style="padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th>用户名称：</th>
                        <th>冻结时间：</th>
                        <th>冻结天数：</th>
                        <th>冻结次数：</th>
                        <th>冻结后还款时间：</th>
                        <th>当前状态：</th>
                        <th>操作人：  </th>
                        <th>备注：    </th>
                        <th>操作      </th>
                    </tr>
                    <?php foreach ($user_forzen_record as $item): ?>
                        <tr>
                            <td><?php echo $common['loanPerson']['name'];?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['created_at']);?></td>
                            <td><?php echo $item['forzen_day'];?></td>
                            <td><?php echo $item['forzen_times'];?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['forzen_repayment_time']);?></td>
                            <td><?php echo isset(UserLoanOrderForzenRecord::$status_forzen_list[$item['status']]) ? UserLoanOrderForzenRecord::$status_forzen_list[$item['status']] : "-";?></td>
                            <td><?php echo $item['opeartor_at'] ;?></td>
                            <td><?php echo $item['remark'] ;?></td>
                            <td> 已处理 </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >冻结日志</th></tr>
    <tr>
        <?php if (empty($user_forzen_log)): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style="padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th>排序ID</th>
                        <th>操作方式</th>
                        <th>记录时间</th>
                        <th>冻结天数</th>
                        <th>初始还款时间</th>
                        <th>上次还款时间</th>
                        <th>冻结后还款时间</th>
                        <th>备注消息</th>
                        <th>操作人</th>
                    </tr>
                    <?php foreach ($user_forzen_log as $item): ?>
                        <tr>
                            <td><?php echo $item['id'];?></td>
                            <td><?php echo $item['opeartor'];?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['created_at']);?></td>
                            <td><?php echo $item['forzen_day'];?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['true_repayment_time']);?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['plan_repayment_time']);?></td>
                            <td><?php echo date("Y-m-d H:i:s",$item['forzen_repayment_time']);?></td>
                            <td><?php echo $item['remark'] ;?></td>
                            <td><?php echo $item['opeartor_at'] ;?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>

<?php $form = ActiveForm::begin(["id"=>"red-packet-form"]); ?>
<table class="tb tb2">
    <tr><td class="td27" colspan="2">冻结天数</td></tr>
    <tr class="noborder">
        <td class="vtop rowform" ><?php echo Html::dropDownList('free_day',"1",$forzen_list) ?></td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>



<?php
echo $this->render('/public/repayment-common-view', array(
    'common'=>$common,
));
?>
