<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrderCount;
use yii\helpers\Html;
use common\models\UserLoanOrder;
/**
 * @var backend\components\View $this
 */
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
用户类型：<?php echo Html::dropDownList('user_type', Yii::$app->request->get('user_type', ''), UserLoanOrderCount::$user_type); ?>&nbsp;
审核结果：<?php echo Html::dropDownList('status', Yii::$app->request->get('status', ''), UserLoanOrderCount::$result_status); ?>&nbsp;
额度区间：<?php echo Html::dropDownList('betweens', Yii::$app->request->get('betweens', ''), UserLoanOrderCount::$between); ?>&nbsp;
决策树类型：<?php echo Html::dropDownList('tree', Yii::$app->request->get('tree', ''), UserLoanOrderCount::getTreeList()); ?>&nbsp;
<?php if(empty($channel)){?>
业务类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->request->get('sub_order_type', ''), UserLoanOrder::$sub_order_type); ?>&nbsp;
<?php }else{?>
业务类型：<?php echo Html::dropDownList('sub_order_type', Yii::$app->request->get('sub_order_type', ''), array('prompt'=>UserLoanOrder::$sub_order_type[$sub_order_type])); ?>&nbsp;
<?php }?>
    <input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;

<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">

            <tr class="header">
                <th>日期</th>
                <th>用户类型</th>
                <th>审核结果</th>
                <th>额度</th>
                <th>次数</th>
                <th>决策树类型</th>
                <th>百分比</th>
                <th>金额</th>
                <th>百分比</th>
                <th >更新时间</th>


            </tr>

<?php foreach($data as $k=> $item):?>
                <tr class="hover">
                    <td style="width: 10%" class="td25"><?php echo $k?></td>
                    <td style="width: 10%" class="td25"><?php echo UserLoanOrderCount::$user_type[$user_type]?></td>
                    <td style="width: 10%" class="td25"><?php echo UserLoanOrderCount::$result_status[$status]?></td>
                    <td style="width: 10%" class="td25"><?php echo UserLoanOrderCount::$between[$between]?></td>
                    <td style="width: 10%" class="td25"><?php echo $item['sum']?></td>
                    <td style="width: 10%" class="td25">
                        <?php
                            if(empty($tree) || $tree == "all"){
                                echo "全部";
                            }else{
                                echo $item['tree'];
                            }
                        ?>
                    </td>
                    <td style="width: 10%" class="td25"><?php echo $item['rate']."%"?></td>
                    <td style="width: 10%" class="td25"><?php echo sprintf("%0.2f",$item['sum_money']/100)?></td>
                    <td style="width: 10%" class="td25"><?php echo $item['money_rate']."%"?></td>
                    <td style="width: 10%" class="td25"><?php echo $item['updated_at']?></td>
                </tr>
<?php endforeach?>

        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>