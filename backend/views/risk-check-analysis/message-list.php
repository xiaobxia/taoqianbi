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
    <h3 style="color: #3325ff;font-size: 14px">错误信息</h3>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['risk-check-analysis/message-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->getRequest()->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">

<?php ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding" style="border: 1px solid">
        <?php if (empty($count)){ ?><div class="no-result">暂无记录</div>
        <?php }else{ $num=sprintf("%.2f",($success_count/$count)*100);?>
        <tr class="header">
            <th style="border: 1px solid;text-align: center">数量</th>
            <th style="border: 1px solid;text-align: center">成功数量</th>
            <th style="border: 1px solid;text-align: center">失败数量</th>
            <th style="border: 1px solid;text-align: center">成功率</th>
        </tr>
            <tr class="hover">
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $count;?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $success_count;?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><a href="<?php echo Url::toRoute(['risk-check-analysis/message-lista','add_start'=>$add_start , 'add_end'=>$add_end , 'error_source'=>$source]) ?>" ><?php  echo $fail_count;?></a></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $num."%";?></td>
            </tr>
        <?php }?>
    </table>
