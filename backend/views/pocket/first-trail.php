<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/16
 * Time: 11:29
 */
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrder;
use common\models\LoanPersonBadInfo;
use common\models\UserOrderLoanCheckLog;
/**
 * @var backend\components\View $this
 */
$this->shownav('staff', 'menu_ygb_zc_lqd_lb');
?>
<?php echo $this->render('pocket-view', [
    'information' => $information
]); ?>
<?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核此项目</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '初审通过',
                    '2' => UserLoanOrder::$status[UserLoanOrder::STATUS_CANCEL]
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">审核码：</td>
            <td class="pass"><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''), $pass_tmp); ?></td>
            <td class="reject" style="display: none"><?php echo Html::dropDownList('nocode', Yii::$app->getRequest()->get('code', ''), $reject_tmp); ?></td>
        </tr>
        <tr class="loan_cation" style="display: none">
            <td>拒绝操作：</td>
            <td>
                <?php echo Html::dropDownList('loan_action', UserOrderLoanCheckLog::CAN_LOAN, UserOrderLoanCheckLog::$can_loan_type); ?>
            </td>
        </tr>
        <tr>
            <td class="td24">备注：</td>
            <td style="width: 15%;display: none" class="reject_mark">
                <select name="reject_mark">
                    <option></option>
                    <option>未抓取到公积金单位</option>
                    <option>公积金单位信息不一致</option>
                    <option>无人接</option>
                    <option>拒接</option>
                    <option>空错停关</option>
                    <option>未填写单位</option>
                    <option>本人与身份证不符</option>
                    <option>客户取消申请</option>
                    <option>欺诈分高</option>
                    <option>无本人照片</option>
                    <option>本人照片不清晰</option>
                    <option>身份证不够清晰</option>
                    <option>身份证有效期过期</option>
                    <option>黑中介分数高</option>
                    <option>未上传身份证</option>
                    <option>短信显示多方逾期</option>
                    <option>核实申请资料错误</option>
                </select>
            </td>
            <td>
                <?php echo Html::textarea('remark', '', ['style' => 'width:300px;', 'id' => "review-remark"]); ?>
            </td>

        </tr>
        <tr>
            <td colspan="15">
                <input id="submit_btn" value="提交" name="submit_btn" class="btn" style="align-items: flex-start;text-align: center;">
            </td>
        </tr>
    </table>
<?php ActiveForm::end(); ?>
<script>
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').show();
            $('.pass select').attr('name','code');
            $('.loan_cation').hide();
            $('.reject').hide();
            $('.reject_mark').hide();
            $('.reject select').attr('name','nocode');
        }else{
            $('.pass').hide();
            $('.pass select').attr('name','nocode');
            $('.reject').show();
            $('.loan_cation').show();
            $('.reject_mark').show();
            $('.reject select').attr('name','code');
        }
        });

    $('select[name=reject_mark]').change(function () {
        $('#review-remark').val($('select[name=reject_mark]').val())
    })

    $("#submit_btn").click(function(){
        var code = $(":radio:checked").val();
        var text = "";
        if(code == 1){
            text = $('.pass select  option:selected').text();
        }else{
            text = $('.reject select  option:selected').text();
        }

        if (text.indexOf("须备注原因") != -1 && $("#review-remark").val() == "") {
            alert("该选项必须备注原因");
            return;
        }

        $("#review-form").submit();
    })
</script>
