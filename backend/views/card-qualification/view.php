<?php

use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

use common\models\CardInfo;
use common\models\UserContact;
use common\models\UserLoanOrder;
use common\models\UserProofMateria;
use common\models\UserQuotaWorkInfo;
use common\models\LoanPersonBadInfo;
use common\models\UserQuotaPersonInfo;
use common\models\UserOrderLoanCheckLog;
?>
<style>
    .person {
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .table td{
        border:1px solid darkgray;
    }
    .tb2 th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .tb2 td{
        border:1px solid darkgray;
    }
    .tb2 {
        border:1px solid darkgray;
    }
    .mark {
        font-weight: bold;
        /*background-color:indianred;*/
        color:red;
    }

    .hide {
        display: none;
    }
</style>

<?php if(!empty($information['user_credit_data'])):?>
<table class="tb tb2 fixpadding" id="creditreport">
    <tr><th class="partition" colspan="10">零钱贷借款规则验证结果</th></tr>
    <tr>
        <td>禁止命中项</td>
        <td><?php echo $information['user_credit_data']['forbid_detail'];?></td>
    </tr>
    <tr>
        <td>过滤命中项</td>
        <td><?php echo $information['user_credit_data']['filter_detail'];?></td>
    </tr>
    <tr>
        <td>高风险命中项</td>
        <td><?php echo $information['user_credit_data']['high_detail'];?></td>
    </tr>
    <tr>
        <td>中风险命中项</td>
        <td><?php echo $information['user_credit_data']['middle_detail'];?></td>
    </tr>
    <tr>
        <td>低风险命中项</td>
        <td><?php echo $information['user_credit_data']['low_detail'];?></td>
    </tr>
    <tr>
        <td>白户信息</td>
        <td><?php echo $information['user_credit_data']['is_white_detail'];?></td>
    </tr>
    <tr>
        <td>自动通过规则</td>
        <td><?php echo $information['user_credit_data']['pass_auto_detail'];?></td>
    </tr>
</table>
<?php endif;?>
<?php echo $this->render('/card-qualification/info', [
    'information' => $information
]); ?>
<script>
    $('.more_info').click(function(){
        if($(this).html() == '点击查看更多'){
            $(this).html('点击隐藏非高风险项');
            $('.hide').show();
        }else{
            $(this).html('点击查看更多');
            $('.hide').hide();
        }
    });

</script>


<?php if($type == 'check'){ ?>
<?php $form = ActiveForm::begin(['id' => 'review-form', 'action' => ['card-qualification/manual-check'],]); ?>
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
            <td class="pass"><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''), $information['pass_tmp']); ?></td>
            <td class="reject" style="display: none"><?php echo Html::dropDownList('nocode', Yii::$app->getRequest()->get('code', ''), $information['reject_tmp']); ?></td>
        </tr>
        <tr>
            <td class="td24">备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;', 'id' => "review-remark"]); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input id="submit_btn" value="提交" name="submit_btn" class="btn" style="align-items: flex-start;text-align: center;">
            </td>
        </tr>
    </table>
    <input type="hidden" name="id" value="<?php echo $information['info']->id?>">
<?php ActiveForm::end(); ?>
<?php } ?>
<script>
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').show();
            $('.pass select').attr('name','code');
            $('.reject').hide();
            $('.reject select').attr('name','nocode');
        }else{
            $('.pass').hide();
            $('.pass select').attr('name','nocode');
            $('.reject').show();
            $('.reject select').attr('name','code');
        }
        });

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

        $.ajax({
            url : '<?php echo Url::toRoute('card-qualification/manual-check'); ?>',
            type : 'POST',
            data : $('#review-form').serialize(),
            error : function(result){
                $.showMessage('数据请求失败，请尝试刷新页面');
            },
            success : function(result){
                alert(result);
                location.href = "<?php echo Url::toRoute(['card-qualification/view', 'id' => $information['info']->id]); ?>"
            }
        });

        // $("#review-form").submit();
    })
</script>