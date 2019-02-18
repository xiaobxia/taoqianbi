<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 11:41
 */
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\UserOrderLoanCheckLog;
use common\models\UserLoanOrder;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" xmlns="http://www.w3.org/1999/html"></script>
<link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<h3 style="color: #3325ff;font-size: 14px">查询条件</h3>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['risk-check-analysis/register-loan-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">

<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding" style="border: 1px solid">
        <tr class="header">
            <th style="border: 1px solid;text-align: center">统计日期</th>
            <th style="border: 1px solid;text-align: center">全部注册</th>
            <th style="border: 1px solid;text-align: center">全部申请</th>
            <th style="border: 1px solid;text-align: center">白名单注册</th>
            <th style="border: 1px solid;text-align: center">白名单申请</th>
            <th style="border: 1px solid;text-align: center">白名单通过</th>
            <th style="border: 1px solid;text-align: center">白名单通过率</th>
        </tr>
    <?php foreach ($info as $value): ?>
      <tr>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['date'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['register'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['loan'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['register_white'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['loan_white'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['payment_white'];?></td>
          <td style="width: 14%;border: 1px solid;text-align: center" class="td25"><?php echo $value['loan_white']?(number_format($value['payment_white']*100/$value['loan_white'],2).'%'):0;?></td>
      </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
