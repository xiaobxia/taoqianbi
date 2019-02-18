<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/4/19
 * Time: 15:17
 */
use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRecord;
use common\models\Shop;

$this->shownav('building', 'menu_loan-first-trail');
$this->showsubmenu('借款初审');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['building/loan-first-trail-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
ID:<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:60px;">&nbsp;
产品名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('product_type_name', ''); ?>" name="product_type_name" class="txt" style="width:60px;">&nbsp;
借款人：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user', ''); ?>" name="user" class="txt" style="width:60px;">&nbsp;
门店名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_name', ''); ?>" name="shop_name" class="txt" style="width:60px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
借款人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_person_id', ''); ?>" name="loan_person_id" class="txt" style="width:60px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
申请日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_start', ''); ?>"  name="created_at_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('created_at_end', ''); ?>"  name="created_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">&nbsp;
还款方式：<?php echo Html::dropDownList('repay_type', Yii::$app->getRequest()->get('repay_type', ''), LoanRecordPeriod::$repay_type, array('prompt' => '-所有状态-')); ?>&nbsp;
来源：<?php echo Html::dropDownList('source', Yii::$app->getRequest()->get('source', ''), LoanRecordPeriod::$source, array('prompt' => '-所有状态-')); ?>&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>借款人ID</th>
            <th>借款人</th>
            <th>门店名称</th>
            <th>联系方式</th>
            <th>项目类型</th>
            <th>产品名称</th>
            <th>用户借款金额（元）</th>
            <th>平台放款金额（元）</th>
            <th>借款期限（月）</th>
            <th>借款利率</th>
            <th>还款类型</th>
            <th>来源</th>
            <th>借款日期</th>
            <th>状态</th>
            <th>操作</th>
        </tr>
        <?php foreach ($loan_record_list as $value): ?>
            <tr class="hover">
                <td class="td25"><?php echo $value['id']; ?></td>
                <td><a target='_blank' href="<?php echo Url::toRoute(['loan/loan-person-view', 'id' => $value['loan_person_id'], 'type' => '2']); ?>"><?php echo $value['loan_person_id']; ?></a></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php
                    $shop = Shop::find()->select(['shop_name'])->where(['id' => $value['shop_id']])->one();
                    echo empty($shop['shop_name']) ? "自营客户" : $shop['shop_name'];
                    ?>
                </td>
                <td><?php echo $value['loanPerson']['phone']; ?></td>
                <td><?php echo isset($projects[$value['loan_project_id']])?$projects[$value['loan_project_id']]:""; ?></td>
                <th><?php echo $value['product_type_name']; ?></th>
                <td><?php echo sprintf('%.2f', $value['amount'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['credit_amount'] / 100); ?></td>
                <td><?php echo $value['period']; ?></td>
                <td><?php echo $value['apr']; ?></td>
                <td><?php echo isset(LoanRecordPeriod::$repay_type[$value['repay_type']])?LoanRecordPeriod::$repay_type[$value['repay_type']]:""; ?></td>
                <td><?php echo isset(LoanRecordPeriod::$source[$value['source']])?LoanRecordPeriod::$source[$value['source']]:""; ?></td>
                <td><?php echo date('Y-m-d H:i', $value['apply_time']); ?></td>
                <td><?php echo isset(LoanRecordPeriod::$status[$value['status']])?LoanRecordPeriod::$status[$value['status']]:""; ?></td>
                <td>
                    <a href="<?php echo Url::toRoute(['building/loan-record-period-view', 'id' => $value['id']]); ?>">查看</a>
                    <a href="<?php echo Url::toRoute(['building/loan-backend-edit', 'id' => $value['id'],'type' => 'edit']); ?>">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_record_list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
