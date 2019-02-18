<?php
use yii\helpers\Url;
use common\helpers\StringHelper;
use common\models\LoanPerson;
?>
<style type="text/css">
    .bg_61cae4 {
        <?php if($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){?>
            background: #1782e0;
        <?php }elseif($source == LoanPerson::PERSON_SOURCE_HBJB){?>
            background: #ff6462;
        <?php }elseif($source == LoanPerson::PERSON_SOURCE_WZD_LOAN){?>
            background: #d74a55;
        <?php }?>
    }
#bank_card_info_wraper{min-height:100%;background:#f5f5f7;}
#bank_card_info_wraper #title{padding-top:1.8em;padding-bottom:.5em;}
#bank_card_info_wraper .column{border-top:1px solid #dcdbdf;border-bottom:1px solid #dcdbdf;}
#bank_card_info_wraper .column > .padding{padding-right:0;}
#bank_card_info_wraper ._table{border-collapse:collapse;}
#bank_card_info_wraper ._table td{padding:.8em 0;border-top:1px solid #dcdbdf;}
#bank_card_info_wraper ._table td + td{padding-right:6.25%;}
#bank_card_info_wraper ._table tr:first-child td{border-top:0 none;}
#bank_card_info_wraper ._tips{margin-top:1.2em;}

.btn{width:87%;height: 1.2rem;line-height: 1.2rem;margin-top:2em;font-size: 1.35em;}
.hsm{width: 5rem;margin: 0.6rem auto;height: 0.613333rem;line-height: 0.613333rem;padding-left: 1.2rem;color: <?= $color;?>;box-sizing:border-box;font-size: 1.24em;background: url(<?= $this->absBaseUrl;?>/image/common/<?= $img;?>?V=001) no-repeat 0.133333rem 0/0.506667rem 0.533333rem;}
</style>
<div id="bank_card_info_wraper" style="padding-bottom:0.5rem;">
    <p class="padding adadad em__9" id="title"></p>
    <div class="column bg_fff">
        <div class="padding">
            <table class="_table" width="100%">
                <tr>
                    <td class="lh_em_1_8 _666" width="24%" style="padding-left:0.5rem;">所属银行</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo $card_info['bank_name'];?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666" style="padding-left:0.5rem;">银行卡号</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo StringHelper::blurCardNo($card_info['card_no'])?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666" style="padding-left:0.5rem;">预留手机号</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo StringHelper::blurPhone($card_info['phone'])?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="_tips padding lh_em_2 adadad em__9" style="padding-left:0.5rem;">
        备注<br/>
        1.借款通过申请后，我们将会将您的所借款项发放到该银行卡；<br/>
        2.若申请重新绑卡，则新卡将被激活为收款银行卡；<br/>
        3.未完成借款期间不允许更换银行卡。<br/>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius" style="background-color: <?=$color?>">重新绑卡<a class="indie" href="<?php echo Url::toRoute(['app-page/bank-card-action'],true);?>"></a></div>
    <div class="hsm">银行级数据加密保护</div>
</div>