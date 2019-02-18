<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 20:12
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanProject;
use common\models\LoanRecord;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['loan/loan-record-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:60px;" >&nbsp;
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    借款日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_start', ''); ?>"  name="created_at_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_end', ''); ?>"  name="created_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    借款类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), LoanProject::$type_list, array('prompt' => '-所有类型-')); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), LoanRecord::$status_list, array('prompt' => '-所有状态-')); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>是否公司员工</th>
            <th>手机号</th>
            <th>项目名称</th>
            <th>借款类型</th>
            <th>借款金额（元）</th>
            <th>借款期限（月）</th>
            <th>借款日期</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php foreach ($loan_record_list as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['user']['realname']; ?></td>
                <td style="text-align: center;"><?php
                        $staff = \common\models\Staff::findOne(['user_id' => $value['user_id']]);
                        echo empty($staff) ? "否" : "是"
                    ?>
                </td>
                <td><?php echo $value['user']['username']; ?></td>
                <td><?php echo $value['loanProject']['loan_project_name']; ?></td>
                <td><?php echo LoanProject::$type_list[$value['type']]; ?></td>
                <td><?php echo sprintf('%.2f', $value['amount'] / 100); ?></td>
                <td><?php echo $value['period']; ?></td>
                <td><?php echo date('Y-m-d H:i:s', $value['created_at']); ?></td>
                <td><?php echo LoanRecord::$status_list[$value['status']]; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['loan/loan-record-view', 'id' => $value['id']]); ?>">查看</a>
                    <a href="<?php echo Url::toRoute(['loan/loan-record-review', 'id' => $value['id']]); ?>">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_record_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
