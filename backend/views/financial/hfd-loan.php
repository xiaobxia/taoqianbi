<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/7/26
 * Time: 15:32
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\HfdOrder;
use common\models\HfdHouse;
use common\models\HfdNotarization;
use common\models\LoanRecordPeriod;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.js'); ?>"></script>
<style>
    .td24{
        width: 120px;
        font-weight: bold;
    }
</style>
<?php $form = ActiveForm::begin(); ?>
<?php echo $this->render('/public/hfd-financial-loan-info', [
    'review_log' => $review_log,
    'hfd_house' => $hfd_house,
    'loan_person' => $loan_person,
    'loan_hfd_order' => $loan_hfd_order,
    'shop' => $shop,
    'ywy_person' => $ywy_person,
    'picture' => $picture,
    'house_picture' => $house_picture,
    'hfd_financial_record' => $hfd_financial_record,
    'is_child'=>$is_child,
    'loan_record_period' => $loan_record_period,
]); ?>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">审核</th></tr>
    <tr>
        <td class="td24">操作</td>
        <td><?php echo Html::radioList('operation', 1, [
                '1' => '通过',
                '2' => '不通过'
            ]); ?></td>
    </tr>
    <tr class="true_money_show">
        <td class="td24">实际放款金额：</td>
        <td><?php echo Html::textInput('true_loan_money', '', ['style' => 'width:100px;'])."万元"; ?></td>
    </tr>
    <tr class="true_money_show">
        <td class="td24">实际放款时间：</td>
        <td><?php echo $form->field($hfd_financial_record, 'true_pay_money_time')->textInput(array("onfocus"=>"WdatePicker({startDate:'%y-%M-%d ',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true});")); ?></td>
    </tr>
    <tr class="remark_show">
        <td class="td24">备注：</td>
        <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
    </tr>
    <tr>
        <td class="td24">付款凭证:</td>
        <td>
            <?php echo $form->field($hfd_financial_record, 'file')->textarea(); ?><div><a target="_blank" href="<?php echo Url::toRoute(['hfd-attachment/add']);?>">上传附件</a></div>
            <span style="float:left">材料地址：以(;)隔开</span>
        </td>
    </tr>
    <tr>
        <td colspan="15">
            <input type="submit" value="放款" name="submit_btn" class="btn">
        </td>
    </tr>

<?php ActiveForm::end(); ?>
    <script>
        $('.true_money_show').show();
        $('img').click(function(){
            $.openPhotoGallery(this);
        });
        $(window).resize(function(){
            $('#J_pg').height($(window).height());
            $('#J_pg').width($(window).width());
        });
        $(':radio').click(function(){
            var code = $(this).val();
            if(code == 1){
                $('.true_money_show').show();
            }else{
                $('.true_money_show').hide();
            }
        });
    </script>
