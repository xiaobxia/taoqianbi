<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use common\models\Shop;
use backend\components\widgets\ActiveForm;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\helpers\Url;
use common\models\PeriodizationSigned;
?>
<div class="itemtitle"><h3>生成合同</h3></div>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style type="text/css">
    .tb2 .txt, .tb2 .txtnobd {
        width: 150px;
        margin-right: 10px;
    }
    .td27{ width: 100px;}
    tr{ height: 30px;}
</style>
<?php echo $content;?>
<?php $form = ActiveForm::begin(['action'=>['periodization/contract-create'],'method'=>'post']);?>
<input type="hidden" value=<?php echo $form_list['remark'];?> name="remark"/>
<input type="hidden" value="<?php echo $form_list['loan_record_period_id'];?>" name="loan_record_period_id"/>
<input type="hidden" value="<?php echo $form_list['indiana_order_id'];?>" name="indiana_order_id"/>
<input type="hidden" value="<?php echo $form_list['type'];?>" name="content[type]"/>
<input type="hidden" value="<?php echo $form_list['template_type'];?>" name="template_type"/>
<input type="hidden" value="<?php echo $form_list['amount'];?>" name="content[amount]"/>
<input type="hidden" value="<?php echo $form_list['use'];?>" name="content[use]"/>
<input type="hidden" value="<?php echo $form_list['color'];?>" name="content[color]"/>
<input type="hidden" value="<?php echo $form_list['address'];?>" name="content[address]"/>
<input type="hidden" value="<?php echo $form_list['fee'];?>" name="content[fee]"/>
<input type="hidden" value="<?php echo $form_list['repayment_type'];?>" name="content[repayment_type]"/>
<input type="hidden" value="<?php echo $form_list['period'];?>" name="content[period]"/>
<input type="hidden" value="<?php echo $form_list['first_repay_date'];?>" name="content[first_repay_date]"/>
<input type="hidden" value="<?php echo $form_list['repay_date'];?>" name="content[repay_date]"/>
<input type="hidden" value="<?php echo $form_list['period_repay_money'];?>" name="content[period_repay_money]"/>
<input type="hidden" value="<?php echo $form_list['company_name'];?>" name="content[company_name]"/>
<input type="hidden" value="<?php echo $form_list['customer_realname'];?>" name="content[customer_realname]"/>
<input type="hidden" value="<?php echo $form_list['idcard'];?>" name="content[idcard]"/>
<input type="hidden" value="<?php echo $form_list['username'];?>" name="content[username]"/>
<input type="submit" value="签约通过" name="submit_btn" class="btn" onclick="return confirm('确认通过')">
<?php ActiveForm::end(); ?>


