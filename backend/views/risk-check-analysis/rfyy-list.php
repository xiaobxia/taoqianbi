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
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['risk-check-analysis/rfyy-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn" style="margin-right:20px;" />命中总次数：<strong><?php echo $count; ?></strong>次（不包含0点到6点的订单全部拒绝次数）

<?php ActiveForm::end(); ?>
<table class="tb tb2 fixpadding" style="border: 1px solid">
        <tr class="header">
            <th style="border: 1px solid;text-align: center">拒绝原因</th>
            <th style="border: 1px solid;text-align: center">命中次数</th>
            <th style="border: 1px solid;text-align: center">占比</th>
        </tr>
    <?php
    $zero=0;
    foreach ($info as $value):
          if($value['remark']!='0点到6点的订单全部拒绝'):
        ?>
        <tr class="hover">
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $value['remark'];?></td>
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $value['id'];?></td>
            <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $value['remark']!='0点到6点的订单全部拒绝'?(number_format(($value['id']/$count)*100,2).'%'):'--'; ?></td>
        </tr>
    <?php
    else:
        $zero=$value['id'];
    endif;
    endforeach; ?>
</table>
<script>
    var zero='<?php echo $zero; ?>';
    if(zero.toString()!='0'){
        var html='<tr class="hover">';
        html+='<td style="width: 12%;border: 1px solid;text-align: center" class="td25">0点到6点的订单全部拒绝</td>';
        html+='<td style="width: 12%;border: 1px solid;text-align: center" class="td25">'+zero.toString()+'</td>';
        html+='<td style="width: 12%;border: 1px solid;text-align: center" class="td25">--</td>';
        html+='</tr>';
        $(html).insertBefore($('.fixpadding .hover:eq(0)'));
    }
</script>
