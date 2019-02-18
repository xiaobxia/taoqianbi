<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\FinancialReconcillationRecord;
echo '<label><strong>'.$date.'</strong></label>';
echo '<hr>';
$this->showsubmenu('对账列表');
?>
<style>
.tb2 th{ font-size: 12px;}
</style>
<?php $form = ActiveForm::begin(['method' => "post",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th  width="25%"></th>
            <th  >网站侧</th>
            <th  >商户侧</th>
            <th  >差额（网站侧-商户侧）</th>
        </tr>
        <tr>
            <td ><strong>易宝支付总额</strong></td>
            <td id="web_yeepay_money"><?php echo sprintf("%0.2f",$info['yeepay_money']['web_yeepay_money']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="text" id="custom_yeepay_money" name="custom_yeepay_money" value="<?php echo empty($info['yeepay_money']['custom_yeepay_money'])?'':sprintf("%0.2f",$info['yeepay_money']['custom_yeepay_money']/100);?>"  onblur="get_balance('web_yeepay_money','custom_yeepay_money','yeepay_balance');"/>
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['yeepay_money']['custom_yeepay_money']/100);?>
                <?php endif;?>
            </td>
            <td id="yeepay_balance"><?php echo sprintf("%0.2f",$info['yeepay_money']['yeepay_balance']/100);?></td>
        </tr>
        <tr>
            <td><strong>易宝支付手续费</strong></td>
            <td id="web_yeepay_counter_fee"><?php echo sprintf("%0.2f",$info['yeepay_counter_fee']['web_yeepay_counter_fee']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="text" id="custom_yeepay_counter_fee" name="custom_yeepay_counter_fee" value="<?php echo empty($info['yeepay_counter_fee']['custom_yeepay_counter_fee'])?'':sprintf("%0.2f",$info['yeepay_counter_fee']['custom_yeepay_counter_fee']/100);?>" onblur="get_balance('web_yeepay_counter_fee','custom_yeepay_counter_fee','yeepay_counter_balance');" />
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['yeepay_counter_fee']['custom_yeepay_counter_fee']/100);?>
                <?php endif;?>
            </td>
            <td id="yeepay_counter_balance"><?php echo sprintf("%0.2f",$info['yeepay_counter_fee']['yeepay_counter_balance']/100); ?> </td>
        </tr>
        <tr>
            <td><strong>联动优势总额</strong></td>
            <td id="web_unionpay_money"><?php echo sprintf("%0.2f",$info['unionpay_money']['web_unionpay_money']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="text" id="custom_unionpay_money" name="custom_unionpay_money" value="<?php echo empty($info['unionpay_money']['custom_unionpay_money'])?'':sprintf("%0.2f",$info['unionpay_money']['custom_unionpay_money']/100);?>" onblur="get_balance('web_unionpay_money','custom_unionpay_money','unionpay_balance');" />
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['unionpay_money']['custom_unionpay_money']/100);?>
                <?php endif;?>
            </td>
            <td  id="unionpay_balance"><?php echo sprintf("%0.2f",$info['unionpay_money']['unionpay_balance']/100);?></td>
        </tr>
        <tr>
            <td><strong>联动优势手续费</strong></td>
            <td id="web_unionpay_counter_fee"><?php echo sprintf("%0.2f",$info['unionpay_counter_fee']['web_unionpay_counter_fee']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input  type="text" id="custom_unionpay_counter_fee" name="custom_unionpay_counter_fee" value="<?php echo empty($info['unionpay_counter_fee']['custom_unionpay_counter_money'])?'':sprintf("%0.2f",$info['unionpay_counter_fee']['custom_unionpay_counter_money']/100);?>"  onblur="get_balance('web_unionpay_counter_fee','custom_unionpay_counter_fee','unionpay_counter_balance');"/>
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['unionpay_counter_fee']['custom_unionpay_counter_money']/100);?>
                <?php endif;?>
            </td>
            <td id="unionpay_counter_balance" ><?php echo sprintf("%0.2f",$info['unionpay_counter_fee']['unionpay_counter_balance']/100);?></td>
        </tr>
        <tr>
            <td style="width: 10px;"><strong>支付宝总额</strong></td>
            <td id="web_alipay_money"><?php echo sprintf("%0.2f",$info['alipay_money']['web_alipay_money']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="text" id="custom_alipay_money" name="custom_alipay_money" value="<?php echo empty($info['alipay_money']['custom_alipay_money'])?'':sprintf("%0.2f",$info['alipay_money']['custom_alipay_money']/100);?>"  onblur="get_balance('web_alipay_money','custom_alipay_money','alipay_balance');"/>
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['alipay_money']['custom_alipay_money']/100);?>
                <?php endif;?>
            </td>
            <td  id="alipay_balance"><?php echo sprintf("%0.2f",$info['alipay_money']['alipay_balance']/100);?></td>
        </tr>
        <!--
        <tr>
            <td><strong>支付宝手续费</strong></td>
            <td id="web_alipay_counter_fee"></td>
            <td><input id="custom_alipay_counter_fee" /></td>
            <td id="alipay_counter_balance"></td>
        </tr>
        -->
         <tr>
            <td><strong>银行卡总额</strong></td>
            <td id="web_bank_money"><?php echo sprintf("%0.2f",$info['bank_trans_money']['web_bank_trans_money']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="text" id="custom_bank_money" name="custom_bank_money" value="<?php echo empty($info['bank_trans_money']['custom_bank_trans_money'])?'':sprintf("%0.2f",$info['bank_trans_money']['custom_bank_trans_money']/100);?>" onblur="get_balance('web_bank_money','custom_bank_money','bank_balance');" />
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['bank_trans_money']['custom_bank_trans_money']/100);?>
                <?php endif;?>
            </td>
            <td id="bank_balance"><?php echo sprintf("%0.2f",$info['bank_trans_money']['bank_trans_balance']/100);?></td>
        </tr>
        <!--
        <tr>
            <td><strong>银行卡手续费</strong></td>
            <td id="web_bank_counter_fee"></td>
            <td><input id="custom_bank_counter_fee" /></td>
            <td id="bank_counter_balance"></td>
        </tr>
        -->
        <tr>
            <td><strong>总额</strong></td>
            <td id="web_total"><?php echo sprintf("%0.2f",$info['total_money']['web_total_money']/100);?></td>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input  type="text" id="custom_total" name="custom_total" value="<?php echo empty($info['total_money']['custom_total_money'])?'':sprintf("%0.2f",$info['total_money']['custom_total_money']/100);?>" onblur="get_balance('web_total','custom_total','total_balance');"/>
                <?php else:?>
                    <?php echo sprintf("%0.2f",$info['total_money']['custom_total_money']/100);?>
                <?php endif;?>
            </td>
            <td id="total_balance"><?php echo sprintf("%0.2f",$info['total_money']['total_balance']/100);?></td>
        </tr>
        <tr>
            <td>
                <strong>备注</strong>
            </td>
        </tr>
        <tr class="remark">
            <td>
                <?php if($view_type=='input_view'):?>
                    <?php echo Html::textarea('remark',$info['remark']['remark_info'], ['style' => 'background-color:white', 'rows' => 6, 'cols' => 50])?>
                <?php else:?>
                    <?php echo $info['remark']['remark_info'];?>
                <?php endif;?>
            </td>
        </tr>
        <tr>
            <td>
                <?php if($view_type=='input_view'):?>
                    <input type="submit" value="提交" name="save"  />
                <?php endif;?>
            <td>
        </tr>
    </table>
<?php $form = ActiveForm::end(); ?>
<script>
    /**
     *获取差额
    **/
    function get_balance(web_id,custom_id,balance_id){
      var web_value = $.trim($('#'+web_id).html());
      var custom_value = $.trim($('#'+custom_id).val());
      var balance_value = web_value - custom_value;
      balance_value = balance_value.toFixed(2);//四舍五入保留两位小数
      $('#'+balance_id).html(balance_value);
    }
    /**
     *清空
    $(function() {
      $('input[type=text]').focus(function(){
        $(this).val('');
      });
      $('textarea[name=remark]').focus(function(){
        $(this).val('');
      });
    });
    **/
</script>

