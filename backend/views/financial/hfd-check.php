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
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    .td24{
        width: 120px;
        font-weight: bold;
    }
    .td25{
        width: 120px;
        font-weight: bold;
    }
</style>
<?php echo $this->render('/public/hfd-financial-loan-info', [
    'review_log' => $review_log,
    'hfd_house' => $hfd_house,
    'loan_person' => $loan_person,
    'loan_hfd_order' => $loan_hfd_order,
    'shop' => $shop,
    'ywy_person' => $ywy_person,
    'picture' => $picture,
    'house_picture' => $house_picture,
    'hfd_financial_record'=>$hfd_financial_record,
    'is_child'=>$is_child,
    'loan_record_period'=>$loan_record_period,
]); ?>
<?php $form = ActiveForm::begin(); ?>
<table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '通过',
                    '2' => '不通过'
                ]); ?></td>
        </tr>
        <tr class="remark_show">
            <td class="td24">备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;']); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input type="submit" value="提交" name="submit_btn" class="btn">
            </td>
        </tr>
</table>
<?php ActiveForm::end(); ?>

