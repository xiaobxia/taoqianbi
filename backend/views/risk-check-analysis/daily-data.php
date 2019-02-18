<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\LoanPerson;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$rate = Yii::$app->request->get('from_st','0') ? 1.1 : 1;
$search_date = Yii::$app->getRequest()->get('search_date', '1');
?>
<title>每日借款数据</title>
    <style>
        table th{text-align: center}
        table td{text-align: center}
    </style>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
    <script type="text/javascript">
        $(function(){
            $("#daochu_submit").click(function(){
                $.ajax({
                    url:"<?php echo Url::toRoute('risk-check-analysis/data-list'); ?>",
                    type:"get",
                    data:{add_start:"2017-03-26",add_end:"2017-05-16"},
                    success:function(data){
                        window.clearInterval(timer);
                        console.log("over..");
                    },
                    error:function(e){
                        alert("错误！！");
                        window.clearInterval(timer);
                    }
                })
            })
        })
    </script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action'=>Url::toRoute(['risk-check-analysis/data-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
    <?php echo Html::dropDownList('search_date', $search_date, array(1=>'借款日期',2=>'还款日期')) ?>
   <input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
&nbsp
    <input type="submit" name="search_submit" value="过滤" class="btn">
    <?php if (!empty($last_update_at)): ?>
        &nbsp;&nbsp;最后更新时间：<?php echo date("n-j H:i", $last_update_at);?>
    <?php endif; ?>
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th colspan="4">全部注册</th>
                <th colspan="5">全部申请</th>
                <th colspan="5">全部通过</th>
                <th colspan="6">总通过率</th>
            </tr>
            <tr class="header">
                <th style="border-right:1px solid #A9A9A9;">日期</th>
                <th>全部注册</th>
                <th>白名单注册</th>
                <th style="border-right:1px solid #A9A9A9;">非白名单注册</th>
                <th>全部申请</th>
                <th>白名单新客户申请</th>
                <th>白名单老客户申请</th>
                <th>非白名单新客户申请</th>
                <th style="border-right:1px solid #A9A9A9;">非白名单老客户申请</th>
                <th>全部通过</th>
                <th>白名单新客户通过</th>
                <th>白名单老客户通过</th>
                <th>非白名单新客户通过</th>
                <th style="border-right:1px solid #A9A9A9;">非白名单老客户通过</th>
                <th>总通过率</th>
                <th>新客户总通过率</th>
                <th>白名单新客户通过率</th>
                <th>非白名单新客户通过率</th>
                <th>老客户总通过率</th>
                <th>白名单老客户通过率</th>
                <th>非白名单老客户通过率</th>
            </tr>
            <?php foreach ($message as $value): ?>
                <tr class="hover">
                    <!-- 全部注册 -->
                    <td class="td25"><?php echo date('Y-m-d',$value['credit_at'])?></td>
                    <td class="td25"><?php echo $value['register_all']?></td>
                    <td class="td25"><?php echo $value['register_white']?></td>
                    <td class="td25"><?php echo $value['register_no_white']?></td>
                    <!-- 全部申请 -->
                    <td class="td25"><?php echo $value['with_new_all']?></td>
                    <td class="td25"><?php echo $value['with_new_order']?></td>
                    <td class="td25"><?php echo $value['with_old_order']?></td>
                    <td class="td25"><?php echo $value['no_with_new_order']?></td>
                    <td class="td25"><?php echo $value['no_with_old_order']?></td>

                    <!-- 全部通过 -->
                    <td class="td25"><?php echo $value['with_new_pass_all']?></td>
                    <td class="td25"><?php echo $value['with_new_pass_order']?></td>
                    <td class="td25"><?php echo $value['with_old_pass_order']?></td>
                    <td class="td25"><?php echo $value['no_with_new_pass_order']?></td>
                    <td class="td25"><?php echo $value['no_with_old_pass_order']?></td>

                    <!--总通过率-->
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_new_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_new_withe_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_new_no_withe_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_old_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_old_withe_pass_rate']/100)?></td>
                    <td class="td25"><?php echo sprintf("%0.2f",$value['all_old_no_withe_pass_rate']/100)?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </form>
