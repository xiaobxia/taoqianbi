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
.btn{width:87%;padding:.7em 0;margin-top:2em;}
/*-----------------新增*/
#bank_card_info_wraper #title {
    padding-top: 0.8em;
    padding-bottom: .5em;
}
#bank_card_info_wraper .column {
    border-top: none;
    border-bottom: none;
    background-color: #f5f5f7;
}
#bank_card_info_wraper .card{
    width: 98%;
    height:8rem;
    background-color: red;
    margin:0 auto;
    background:transparent url('<?= $this->absBaseUrl;?>/image/bank-card/<?php echo $card_info['bank_id']?>.png') no-repeat center center;
    background-size: 100%;
    border-radius: 0.625rem;
}
#bank_card_info_wraper .card .card_top{
    height:50%;
    width: 100%;
    display: flex;
}
#bank_card_info_wraper .card .card_top .card_top_l,#bank_card_info_wraper .card .card_top .card_top_r{
    width: 50%;
    height:100%;
    flex: 1;
}

#bank_card_info_wraper .card .card_top .card_top_l i{
    display: inline-block;
    width: 1.25rem;
    height: 1.25rem;
    background-color:coral;
    margin-top: 1.06rem;
    margin-left: 0.625rem;
    vertical-align: top;
}
#bank_card_info_wraper .card .card_top .card_top_l p{
    display: inline-block;
    margin-top: 0.8rem;
    margin-left: 0.625rem;
    color: #212121;
    font-size: 0.75rem;
}
#bank_card_info_wraper .card .card_top .card_top_l .bank_name{
    font-size: 0.9375rem;
}
#bank_card_info_wraper .card .card_top .card_top_r p{
    text-align: right;
    margin-top: 3rem;
    margin-right: 0.625rem;
}
#bank_card_info_wraper .card .card_bottom p{
    text-align: right;
    margin-right: 0.625rem;
}
#bank_card_info_wraper .card .card_bottom .card_num{
    font-weight: 700;
    font-size: 1.25rem;
/*    margin-top:1.25rem; */
}
#bank_card_info_wraper ._tips {
    margin: 1rem auto;
    font-size: 1.2em !important;
    color: #999;
}


.btn{
    background: url('<?= $this->absBaseUrl;?>/image/anniu2@2x.png') no-repeat center center;
}
.container .padding {
    padding: 0 0.9rem;
}

</style>
<div id="bank_card_info_wraper">
    <p class="padding adadad em__9" id="title"></p>
    <div class="column bg_fff">
        <!--<div class="padding">
            <table class="_table" width="100%">
                <tr>
                    <td class="lh_em_1_8 _666" width="24%">所属银行</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo $card_info['bank_name'];?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">银行卡号</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo StringHelper::blurCardNo($card_info['card_no'])?>
                    </td>
                </tr>
                <tr>
                    <td class="lh_em_1_8 _666">预留手机号</td>
                    <td class="lh_em_1_8 _8d8d8d a_right">
                        <?php echo StringHelper::blurPhone($card_info['phone'])?>
                    </td>
                </tr>
            </table>
        </div>-->
        <div class="card">
            <div class="card_top">
                <!-- <div class="card_top_l">
                    <i></i>
                    <p><span class="bank_name">工商银行</span><br><span>(储蓄卡)</span></p>
                </div>
 -->                <div class="card_top_r">
                    <p class="phone"><?php echo StringHelper::blurPhone($card_info['phone'])?></p>
                </div>
            </div>
            <div class="card_bottom">
                <p class="card_num"><?php echo StringHelper::blurCardNo($card_info['card_no'])?></p>
            </div>
        </div>
    </div>
    <div class="_tips padding lh_em_2 adadad em__9">
        备注<br/>
        1.借款通过申请后，我们将会将您的所借款项发放到该银行卡；<br/>
        2.若申请重新绑卡，则新卡将被激活为收款银行卡；<br/>
        3.未完成借款期间不允许更换银行卡。<br/>
    </div>
    <div class="btn p_relative bg_61cae4 fff m_center a_center _b_radius">重新绑卡<a class="indie" href="<?php echo Url::toRoute(['app-page/bank-card-action'],true);?>"></a></div>
</div>