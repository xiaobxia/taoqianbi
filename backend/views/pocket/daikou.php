<?php
use yii\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
$this->shownav('financial', 'menu_daikou_list');
$this->showsubmenu('发起代扣');
?>
<script language="javascript" type="text/javascript" src="<?php echo $this->baseUrl ?>/js/My97DatePicker/WdatePicker.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $this->baseUrl ?>/js/jquery.modal.min.js"></script>
<link rel="stylesheet" href="<?php echo $this->baseUrl ?>/css/jquery.modal.min.css" type="text/css" media="screen" />
<style>
    input.txt {width:120px;}
    .header th{text-align: center;}
    body > .modal { display: none;}
</style>
<!--<p style="color:red">说明：本代扣只是提交到畅捷代扣系统，具体是否成功，不做记录，需要到畅捷后台去查询</p>-->
<?php $form = ActiveForm::begin(['id' => 'form', 'method' => "post", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
<p style="display: block;width:100%;padding:5px 0;">借款人姓名：<input type="text" readonly value="<?=$loanPerson->name?>"  name="name" class="txt" style="width:200px;"></p>
<p style="display: block;width:100%;padding:5px 0;">借款人身份证：<input type="text" readonly value="<?=$loanPerson->id_number?>" name="id_card_no" class="txt" style="width:200px;"></p>
<p style="display: block;width:100%;padding:5px 0;">应还金额：<strong style="color:red;"><?=sprintf("%0.2f", $preayment_amount/100)?></strong>元</p>
<p style="display: block;width:100%;padding:5px 0;">
    扣款银行卡：
        <select name="bank_id">
            <?php foreach($cardinfo as $k=>$v):?>
                <?php if($v->main_card==\common\models\CardInfo::MAIN_CARD):?>
            <option selected="selected" value="<?=$v->id?>" ><?=$v->bank_name.'(卡号：'.$v->card_no.'，预留手机号：'.$v->phone.'，主卡)'?></option>
                    <?php else:?>
                    <option value="<?=$v->id?>" ><?=$v->bank_name.'(卡号：'.$v->card_no.'，预留手机号：'.$v->phone.'，副卡)'?></option>
            <?php endif;?>
            <?php endforeach;?>
        </select></p>
<p style="display: block;width:100%;padding:5px 0;">扣款金额：<input type="text" value="<?=$preayment_amount/100?>" name="amount" class="txt"  style="width:200px;"><font color="red">元</font></p>
    <input type="submit" name="submit"value="确认发起扣款" class="btn" />
    <input type="hidden" name="orderid" value="<?=$orderid?>" />
<?php ActiveForm::end(); ?>
<script type="text/javascript">
    $(function(){
        $('#form').submit(function(){
            //应还金额
            var payamount='<?=$preayment_amount/100?>';
            var amount=$.trim($('input[name="amount"]').val());
            if(parseFloat(amount.toString(),10)>parseFloat(payamount.toString(),10)){
                alert('输入的扣款金额不能大于'+payamount.toString()+'元！');
                return false;
            }
            return true;
        });
    });
</script>