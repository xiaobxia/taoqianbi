<?php
use common\helpers\Util;
use common\models\LoanPerson;
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use yii\helpers\Url;
use common\helpers;
use mobile\components\ApiUrl;

?>
<style>

    body {
        background-color: #f2f2f2;
    }
    .repayment-detail.wrap .fixed-button .fb-btn {
        float: right;
        text-decoration: none;
        background-color: #1ec8e1
        color: #fff;
        width: 3.84rem;
    }
    .repayment-detail.wrap .content ul > li .iconfont {
        position: absolute;
        left: 0;
        top: 0;
        font-size: 0.77333rem;
        color: #1ec8e1
    }
    .repayment-detail.wrap .content ul > li:before {
        content: '';
        display: block;
        width: 0.05333rem;
        height: 0.77333rem;
        background: #1ec8e1
        position: absolute;
        top: -0.72rem;
        left: 0.38667rem;
        color: #000;
    }
    .repayment-detail.wrap .content ul > li:after {
        content: '';
        display: block;
        width: 0.05333rem;
        height: 100%;
        background: #1ec8e1
        position: absolute;
        top: 0.77333rem;
        left: 0.38667rem;
    }
    .more_chance_button_background{
        background: #1ec8e1
        height:1.173333rem;
        width: 7.6rem;
        line-height:1.173333rem;
        position: absolute;
        top: 1.5rem;
        left: -0.4rem;
        display:block;
        color:#fff;
        font-size:0.426667rem;
        text-decoration:none;
        border-radius:0.586667rem;
        text-align: center;
        box-shadow: 0 0 0.186667rem rgba(10,86,152,0.36);
    }
    .more_chance_button_background:link{
        color: #fff;
    }
    .more_chance_button_background:visited{
        color: #fff;
    }
    .more_chance_button_background i{
        display: block;
        width: 0.56rem;
        height: 0.533333rem;
        background: url("../image/loan/content_icon01.png") no-repeat center;
        background-size: 0.56rem 0.533333rem;
        position: absolute;
        left:0.8rem;
        top:0.33334rem;
        float: left;
    }
    .more_chance_button_background b{
        display: block;
        width: 0.266667rem;
        height: 0.266667rem;
        border-top:0.053333rem solid #fff;
        border-right:0.053333rem solid #fff;
        transform: rotate(45deg);
        margin-left: 1.066667rem;
        position: absolute;
        top: 0.42rem;
        right:0.8rem;
    }
    .repayment-detail.wrap .item ul li a.zonghe {
        display: block;
        width: 0.4rem;
        height: 0.4rem;
        background: url("../image/loan/zonghe_icon.png") no-repeat center/cover;
        float: right;
        margin: 0.213333rem 0 0.213333rem 0.32rem;
    }
    .repayment-detail.wrap .content ul > li h1 {
        font-size: 18px;
        padding-bottom: 0.13333rem;
        color: #1ec8e1 }
    .repayment-detail.wrap .content ul > li.sType3 .iconfont {
        color: rgb(244, 28, 28); }
    .repayment-detail.wrap .content ul > li.sType3 h1 {
        color: rgb(244, 28, 28); }
    .repayment-detail.wrap .content ul > li.sType3:before {
        background-color: rgb(244, 28, 28); }
    .repayment-detail.wrap .content ul > li.sType3 p > span {
        color: rgb(244, 28, 28); }
    .repayment-detail.wrap .item ul li a {
        color: #1ec8e1
        text-decoration: none;
        float: right; }
    .item .item-head span{
        color: #1ec8e1
        float: right;
        font-size: 0.4rem;
    }
    .item #selected{
        float: right;
        border: none;
        color: #333;
        background: #fff;
        appearance:none;
        -moz-appearance:none;
        -webkit-appearance:none;
        padding-top: 0.133333rem;
        outline: none;
        tap-highlight-color: transparent;
        -moz-tap-highlight-color: transparent;
        -webkit-tap-highlight-color: transparent;
    }
    .item .select span{
        font-size: 0.48rem;
        font-weight: bold;
        color: #666;
    }
    .repayment-detail.wrap .item ul li.hide{
        display: none;
    }
    .repayment-detail.wrap .item .select i{
        display: block;
        width: 0.366667rem;
        height: 0.366667rem;
        border-top: 0.036667rem solid #1ec8e1
        border-right: 0.036667rem solid #1ec8e1
        transform: rotate(45deg);
        float: right;
        margin-top: 0.293333rem;
    }
    .repayment-detail.wrap .tip{
        color: #999;
        font-size: 0.373333rem;
        margin-top: -0.48rem;
        background: transparent;
        padding-bottom: 0.4rem;
    }
    #loan-dialog {
         z-index: 1;
    }
    /* 综合费用 */
    .loan_zonghe {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3); }
    .loan_zonghe ._zonghe {
        width: 8.32rem;
        background: #fff;
        border-radius: 0.32rem;
        position: absolute;
        top: 3.64rem;
        left: 0.853333rem; }
    .loan_zonghe ._zonghe .title_zonghe {
        width: 100%;
        height: 1.333333rem;
        line-height: 1.333333rem;
        font-size: 0.48rem;
        color: #333;
        border-bottom: 1px solid #d6d7dc;
        text-align: center; }
    .loan_zonghe ._zonghe .content_zonghe {
        padding: 0.453333rem;
        font-size: 0.4rem;
        color: #666; }
    .loan_zonghe ._zonghe .content_zonghe ul > li {
        margin-bottom: 0.4rem;
        line-height: 0.4rem}
    .loan_zonghe ._zonghe .content_zonghe ul > li:after{
        content:".";
        display:block;
        height:0;
        overflow:hidden;
        clear:both;
        visibility:hidden;
    }
    .loan_zonghe ._zonghe .content_zonghe ul > li span {
        float: left; }
    .loan_zonghe ._zonghe .content_zonghe ul > li i {
        display: inline-block;
        color: #666;
        float: right;
        font-style: normal;
    }
    .loan_zonghe ._zonghe .content_zonghe ul > li:first-child span,.loan_zonghe ._zonghe .content_zonghe ul > li:first-child i {
        color: #333;
        font-weight: bold; }
    .loan_zonghe ._zonghe .content_zonghe p {
        font-size: 0.32rem;
        line-height: 0.426667rem; }
    .loan_zonghe ._zonghe .hide_zonghe {
        width: 100%;
        height: 1.333333rem;
        line-height: 1.333333rem;
        font-size: 0.48rem;
        color: #1ec8e1
        border-top: 1px solid #d6d7dc;
        text-align: center; }
    input.repayment {
        position: relative;
        display: inline-block;
        width: 0.694444rem;
        height: 0.694444rem;
        -webkit-appearance: none;
        background-color: transparent;
        border: 0;
        outline: 0 !important;
        line-height:0.694444rem;
        color: #d8d8d8;
    }

    input.repayment:before {
        content: "";
        display:block;
        width: 0.694444rem;
        height: 0.694444rem;
        border-radius: 50%;
        text-align:center;
        line-height:0.583333rem;
        color: #fff;
        border:0.0555555rem solid #ddd;
        background-color:#fff;
        box-sizing:border-box;
    }

    input.repayment:checked:after {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: auto;
        content: "";
        display: block;
        background-color:#1ec8e1;
        width: 0.25rem;
        height: 0.25rem;
        border-radius: 50%;
    }
    .icon-0 {
        content: '&#xe600;';
    }
    .icon-1 {
        content: '&#xe63d;';
    }
    .icon-2 {
        content: '&#xe641;';
    }
    .icon-3 {
        content: '&#xe735;';
    }
</style>
<?php if($is_xjbt == 1){?>
    <style>
        .repayment-detail.wrap .fixed-button .fb-btn {
            float: right;
            text-decoration: none;
            background-color: #ff7955;
            color: #fff;
            width: 3.84rem;
        }
        .repayment-detail.wrap .content ul > li .iconfont {
            position: absolute;
            left: 0;
            top: 0;
            font-size: 0.77333rem;
            color: #ff7955;
        }
        .repayment-detail.wrap .content ul > li:before {
            content: '';
            display: block;
            width: 0.05333rem;
            height: 0.77333rem;
            background: #ff7955;
            position: absolute;
            top: -0.72rem;
            left: 0.38667rem;
        }
        .repayment-detail.wrap .content ul > li:after {
            content: '';
            display: block;
            width: 0.05333rem;
            height: 100%;
            background: #ff7955;
            position: absolute;
            top: 0.77333rem;
            left: 0.38667rem;
        }
        .more_chance_button_background{
            background:#ff7955;
        }

    </style>
<?php }?>
<div class="wrap repayment-detail">
    <div style="display: none" id="show_type"><?=$show_type?></div>
    <div class="content">
        <?php if ($repayment && $page_action != "get_more") { ?>
            <div class="pay-status">
                <a class="pay-status-detail" href="javascript:;"></a>
                <h1>
                    <?php echo $head_show['new_title']; ?>
                </h1>
                <p><?php echo $head_show['new_desc']; ?></p>
            </div>
        <?php } else { ?>
            <!--除了主包之外都显示-->
            <ul>
                <?php foreach ($list as $value) { ?>
                    <li class="<?= isset($value['class']) ? $value['class'] : '' ?>">
                        <i class="iconfont"></i>
                        <h1 style="position:relative;">
                            <?= $value['title'] ?>
                        </h1>
                        <p><?= $value['body'] ?></p>
                    </li>
                <?php }?>
            </ul>
        <?php } ?>
    </div>

    <?php if ($page_action != "get_more") { ?>
    <div class="item">
        <p class="item-head">借款明细 <span class="open">展开</span></p>
        <ul>
            <li class="hide"><span>申请时间</span><i><?= date('Y-m-d', $order['order_time']) ?></i></li>
            <li><span>借款金额</span><i><?= sprintf("%0.2f", $order['money_amount'] / 100) ?></i></li>
            <li class="hide"><span>借款期限</span><i><?= $order['loan_term'] ?>天</i></li>
            <li class="hide"><span>到账金额</span><i><?= sprintf("%0.2f", $order['money_amount'] / 100) ?></i></li>
            <li class="hide"><span>综合利息</span><i><?= sprintf("%0.2f", $order['loan_interests'] / 100) ?></i></li>
            <li class="hide"><span>购买征信报告支付</span><i><?= sprintf("%0.2f", $order['counter_fee'] / 100) ?></i></li>
            <li class="hide"><span>购物后到账</span><i><?= sprintf("%0.2f", $order['true_money_amount'] / 100) ?></i></li>
            <li class="hide"><span>放款日期</span><i><?= $user_info_more['order_loan_time'] ?></i></li>
            <li class="hide"><span>合约还款日</span><i><?= $user_info_more['order_repayment_time'] ?></i></li>
            <li class="hide"><span>实际还款日</span><i><?= $user_info_more['order_true_repayment_time'] ?></i></li>
            <li class="hide"><span>应还金额</span><i><?= sprintf("%0.2f", $user_info_more['order_pay_all_money']) ?></i></li>
<!--            <li class="hide"><span>购物支付</span><i>¥--><?//= $user_info_more['loan_term'] ?><!--</i></li>-->
<!--            <li class="hide"><span>购物后到账</span><i>¥--><?//= $user_info_more['detail_buy_true_loan_money'] ?><!--</i></li>-->
<!--            <li class="hide"><span>优惠卷减免金额</span><i>¥--><?//= $user_info_more['detail_free_money'] ?><!--</i></li>-->
<!--            <li class="hide"><span>放款日期</span><i>--><?//= $user_info_more['order_loan_time'] ?><!--</i></li>-->
<!--            <li class="hide"><span>合约还款日</span><i>--><?//= $user_info_more['order_repayment_time'] ?><!--</i></li>-->
<!--            <li class="hide"><span>实际还款日</span><i>--><?//= $user_info_more['order_true_repayment_time'] ?><!--</i></li>-->
<!--            <li class="hide"><span>应还金额</span><i>¥--><?//= $user_info_more['order_pay_all_money'] ?><!--</i></li>-->
            <?php if (isset($order['bank_info'])) { ?>
                <li class="hide"><span>收款账号</span><i><?= $order['bank_info'] ?></i></li>
            <?php } ?>
            <?php if($flag_show == 1){ ?>
                <li style="margin-bottom: 0.8rem;">
                    <span>协议说明</span>
                    <a href="<?php echo $url_two;?>" >《平台服务协议》</a>
                    <a class="last" href="<?php echo $url_three;?>" style="margin-left: 0;" >《授权扣款委托书》</a>
                </li>
            <?php }?>

        </ul>
    </div>

    <?php if ($repayment) { ?>
        <div class="item">
            <p class="item-head">还款明细</p>
            <ul>
                <li>
                    <span>待还本金</span>
                    <i id="total_repay_money"><?php if($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){echo '0.00';}else{echo sprintf("%0.2f", $repayment['remain_money_amount'] / 100);}?></i>
                </li>

                <?php if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE && $repayment['coupon_money'] != 0 && $repayment['coupon_id'] > 0 ) { ?>
                    <li>
                        <span>我的优惠券</span>
                        <i id="coupon">-<?= sprintf("%0.2f", $repayment['coupon_money'] / 100) ?></i>
                    </li>
                <?php } ?>
                <?php if ($repayment['is_overdue'] && $repayment['overdue_day']) { ?>
                    <li>
                        <span>逾期费用</span>
                        <i style="color: #f41c1c"><?= sprintf("%0.2f", $repayment['late_fee'] / 100) ?></i>
                    </li>
                <?php } ?>
                <li>
                    <span>已还金额</span>
                    <i><?= sprintf("%0.2f", $repayment['true_total_money'] / 100) ?></i>
                </li>
                <li>
                    <span>最迟还款日期</span>
                    <i><?= date('Y-m-d', $repayment['plan_fee_time']) ?></i>
                </li>
                <?php if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
                    <li>
                        <span>最终结算日期</span>
                        <i><?= $repayment['true_repayment_time'] ? date('Y-m-d', $repayment['true_repayment_time']) : '- -' ?></i>
                    </li>
                <?php } ?>
                <?php if ($repayment['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
                    <!--
                    <li>
                      <span>剩余续期次数</span>
                      <i><?= $remainDelayTimes ?></i>
                    </li>
                    -->
                <?php } ?>
            </ul>
        </div>
        <?php if ($repayment['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { //还款未完成?>
            <?php if ($remainDelayTimes > 0 && !$repayment['is_overdue']) { //剩余续期次数大于0，未逾期?>
                <?php if ($useFy) { ?>
                    <a class="fixed-button" href="<?= Url::toRoute(['loan/loan-delay', 'id' => $order['id'], 'type' => UserCreditMoneyLog::TYPE_PLAY]) ?>">申请续期</a>
                <?php } ?>
                <?php if($is_repaymenting==0): ?>
                <div class="fixed-button c_f_btn" style="z-index:10000;background:#fff;">
                    <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <del class="fb-text-chooseCoupon"><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></del>
                    <!-- <?php if(!empty($coupon_list)) {?> -->
                    <!-- <span class="chooseCoupon">选择优惠</span> -->
                    <!-- <?php } ?> -->
                    <a class="fb-btn" id="repayment_btn" href="javascript:void(0);">立即还款</a>
                </div><?php endif; ?>
            <?php } else { //逾期?>
                <?php if($is_repaymenting==0): ?>
                <div class="fixed-button" style="z-index:10000;background:#fff;">
                    <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <a class="fb-btn" id="repayment_btn" href="javascript:void(0);">立即还款</a>
                </div>
                <?php endif; ?>
            <?php } ?>

        <?php } ?>
    <?php }else{ ?>
        <?php if ($show_emergency) { ?>
            <div class="button clearfix">
                <a class="the-one" href="<?php echo $emergency_url; ?>">江湖救急</a>
            </div>
        <?php } ?>
    <?php } ?>

    <?php
        //逾期7天以上的不能展期借款
        if ($repayment && $is_repaymenting==0 && $repayment['true_total_money']==0 && $repayment['overdue_day']<=7) {?>
             <div class="item">
                <ul style="padding-top: 0;">
                    <li class="select">
                        <span style="color:#1ec8e1">选择还款方式</span>
                    </li>
                    <li>
                        <span>全额还款</span>
                        <i><input type="radio" class="repayment" checked="checked" value="0" onclick="loanrepaymenttype(0);" name="loanrepaymenttype" /></i>
                    </li>
                    <li style="margin-top:5px;">
                        <span>借款展期</span>
                        <i><input type="radio" class="repayment" value="1" onclick="loanrepaymenttype(1);" name="loanrepaymenttype" /></i>
                    </li>
                </ul>
            </div>
        <?php } ?>

    <?php if ($repayment) { ?>
    <?php if ($repayment['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { //还款未完成?>

    <?php if ($repayment['is_overdue'] && $repayment['overdue_day'] && $order['status']==\common\models\UserLoanOrder::STATUS_LOAN_COMPLING && $part_repay_money) { ?>
    <div class="item">

    <ul style="padding-top: 0;">

        <li class="select" style="position:relative;">
                <span>选择还款金额</span>
                <i style="position: absolute;right:0;top:0.22rem;margin-top:0;"></i>
                <select id="selected" style="margin-right:0.1rem;" onclick="clickFn()">
                    <option>请选择</option>
                    <?php foreach ($part_repay_money as $k => $money) { ?>
                        <option><?= sprintf("%0.2f", $money / 100) ?></option>
                    <?php } ?>
                </select>
            </li>
        </ul>
    </div>
    <p class="tip">部分还款后, 逾期费用按剩余逾期金额计算</p>
<?php } ?>
<?php } ?>
<?php } ?>

<?php } else { ?>
    <div class="item">
        <ul>
            <li style="margin-bottom: 0.8rem;">
                <span>协议说明</span>
                <a href="<?php echo $url_two;?>" >《平台服务协议》</a>
                <a class="last" href="<?php echo $url_three;?>" style="margin-left: 0;" >《授权扣款委托书》</a>
            </li>
        </ul>
    </div>
<?php }  ?>

</div>


<!-- 留言成功 -->
<div id="message_span" class="toast" style="display:none;">
    <span>留言成功</span>
</div>

<!-- 我要留言操作 -->
<div id="comments" class="popup comments" style="display:none;">
    <div class="overlay"></div>
    <div class="dialog">
        <h2>添加留言</h2>
        <textarea placeholder="限30个字以内" cols="17" id="txt_content" rows="5" maxlength="30"></textarea>
        <p>*留言成功后无法修改</p>
        <p>
            <a id="btn_user_canel" href="javascript:void(0);">取消</a>
            <a id="btn_user_book" href="javascript:void(0);">留言</a>
        </p>
    </div>
</div>

<!-- 上传图片成功 -->
<?php if (count($user_materia) > 0 && is_array($user_materia)) { ?>
    <div id="pop-image" class="popup pop-image" style="display:none">
        <div class="overlay"></div>
        <div class="dialog">
            <img src="<?php echo $user_materia["img_url"]; ?>" />
        </div>
    </div>
<?php } ?>

<input type="hidden" id="coupon_id" name="coupon_id" value="">

<!--弹出框-->
<div id="loan-dialog">
    <?php if(YII_ENV_PROD):?>
        <div class="dialogbg"></div>
        <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT ):?>
            <div class="dialog wx" id="wx_dialog" style="display: block;">
                <div class="wx-head">
                    <p>
                        <i class="iconfont">&#xe63d;</i><br/>
                        借款申请提交成功
                    </p>
                </div>
                <div class="wx-prompt" style="display: none">
                    <p class="prompt-title">
                        友情提示
                    </p>
                    <div class="dashed-line">
                        审核成功后即有机会获得一张还款抵扣券，关注官方微信公众号并绑定，可第一时间获取审核结果！
                    </div>
                    <div class="wx-number">微信公众号：<span id="wx-number"><?php echo WEIXIN_GONGZHONGNHAO_SHORENAME; ?></span></div>
                </div>

                <?php if($wx_user):?>
                    <div class="wx-btn">
                        我知道了
                    </div>
                <?php else:?>
                    <div class="wx-btn wx-copy">
                        复制并关注
                    </div>
                <?php endif;?>
            </div>
        <?php endif;?>
    <?php endif;?>
    <!-- 优惠券 div start -->
    <?php if(!empty($coupon_list)) {?>
        <div class="dialog coupon">
            <div class="coupon-title">
                请选择优惠券
                <span class="close">关闭</span>
            </div>
            <div class="coupon-main">
                <ul class="coupons">
                    <?php foreach ($coupon_list as $k => $coupon) { ?>
                        <li class="coupon">
                            <div class="cp-left fl">
                                <input type="hidden" data-coupon-id="<?php echo $coupon['coupon_id'];?>">
                                <p>￥<span class="C_rmb"><?php echo (!empty($coupon["amount"])) ? sprintf("%.2f", $coupon["amount"]): ""; ?></span></p>
                                <p><?php echo (!empty($coupon["sub_title"])) ? $coupon["sub_title"]: ""; ?></p>
                            </div>
                            <div class="cp-right fl">
                                <p><?php echo $coupon['loan_amount'] ?? ''; ?></p>
                                <p><?php echo $coupon['loan_term'] ?? ''; ?></p>
                                <p><?php echo $coupon['time'] ?? '' ;?></p>
                            </div>
                            <span class="checkbox">
                    </span>
                        </li>
                    <?php } ?>
                </ul>
                <div class="coupon no-coupons">
                    不使用优惠券
                    <span class="checkbox checked">
                </span>
                </div>
            </div>
        </div>
    <?php } ?>
    <!--    优惠券 div end -->
</div>

<!-- 综合费用明细 start -->
<div class="loan_zonghe" style="display: none">
    <div class="_zonghe">
        <div class="title_zonghe">
            <h2>费用明细(元)</h2>
        </div>
        <div class="content_zonghe">
            <ul>
                <li>
                    <span>综合费用</span>
                    <i>55.00</i>
                </li>
                <li>
                    <span>审批费</span>
                    <i>16.80</i>
                </li>
                <li>
                    <span>管理费</span>
                    <i>14.00</i>
                </li>
                <li>
                    <span>互保准备金</span>
                    <i>28.00</i>
                </li>
            </ul>
            <p class="explain">说明：综合服务费将在放款成功后，从您的收款银行卡自动扣除，如果扣费失败将在还款时一并收取。</p>
        </div>
        <div class="hide_zonghe">
            <h2>我知道了</h2>
        </div>
    </div>
</div>
<!-- 综合费用明细 end -->

<?php
    //展期费计算
    $zhanqi_money=$order['counter_fee'];
    if($zhanqi_money<$order['money_amount']*ZHANQI_LOAN_LV){
        $zhanqi_money=$order['money_amount']*ZHANQI_LOAN_LV;
    }
?>
<script type="text/javascript">
    $(document).ready(function () {
        if($('input[name="loanrepaymenttype"]').length>0){
            var loanrepaymenttype=$('input[name="loanrepaymenttype"]:checked').val();
            if(loanrepaymenttype.toString()=='1'){
                var repaymentloan='<?= sprintf("%0.2f", (intval($zhanqi_money+$repayment["interests"]+$repayment["late_fee"]) / 100)) ?>';
                $('.fb-text').find('i').text(repaymentloan.toString());
                $('.fixed-button #repayment_btn').html('立即展期');
            }
        }
        setWebViewFlag();
        MobclickAgent.onEvent("applydetail","借款详情页面事件"); //页面进入打点

        <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT && $page_action != "get_more"):?>
        <?php if(YII_ENV_PROD):?>
        $('#loan-dialog').show();
        <?php endif;?>
        $(".dialog.coupon").hide();

        <?php endif;?>

        buttonShow();
        showCoupon();
        getShowType();
        showCollection();
        var $leaveMessage = $(".popup-message");
        $leaveMessage.parent().click(function () {
            var display = $leaveMessage.css("display");
            if (display == "none") {
                $leaveMessage.css("display", "block");
                $leaveMessage.css("opacity", 1);
            } else {
                $leaveMessage.css("opacity", 0);
                setTimeout(function () {
                    $leaveMessage.css("display", "none");
                }, 500);
            }
        });

    });
    //
    var rmb = $('.fixed-button.c_f_btn .fb-text').children('i').html(); //应还款总额
    // 优惠券选择框
    $('.dialog.coupon .coupon').click(function(){
        $('.dialog.coupon .coupon').find('.checkbox').removeClass('checked');
        $(this).find('.checkbox').addClass('checked');  // 选中的优惠券checkbox变蓝
        $('.dialog.coupon .coupon').find('input').removeAttr('id');
        $(this).find('input').attr('id','c_id');  //选中的优惠券添加id=c_id
        $('#coupon_id').val($('#c_id').attr('data-coupon-id'));  // 选中优惠券对应的data-coupon-id值赋给#coupon_id
        var Crmb = $(this).find(".C_rmb").html()
        if(Crmb){
            $('.fixed-button.c_f_btn .fb-text').children('i').html(rmb - Crmb);
            $('.fixed-button.c_f_btn .fb-text-chooseCoupon').show();
            $('.fixed-button.c_f_btn .fb-text').css({'line-height': '1.32em','padding-top': '0.16rem'});
            $('.item .to-repaid').html(Crmb+"元抵扣").css({'color':'#e27e08'});
            $('#coupon.coupon-on').html('-'+Crmb);
        }else {
            $('.fixed-button.c_f_btn .fb-text').children('i').html(rmb);
            $('.fixed-button.c_f_btn .fb-text-chooseCoupon').hide();
            $('.fixed-button.c_f_btn .fb-text').css({'line-height': 'inherit','padding-top': 'inherit'});
            $('.item .to-repaid').html("选择优惠").css({'color':'#999'});
            $('#coupon.coupon-on').html("未选择优惠券");
        }
        $("#loan-dialog").hide();
        $('.dialog.coupon').hide();
        return false;
    })
    $('.dialog.coupon .close').click(function(){  // 优惠券关闭按钮
        $("#loan-dialog").hide();
        $('.dialog.coupon').hide();
        return false;
    })
    // 点击选择优惠券
    $('.to-repaid').click(function(){
        $('.dialog.wx').hide();
        $("#loan-dialog").show();
        $('.dialog.coupon').show();
        return false;
    })

    <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT ):?>

    // 点击复制微信公众号
    $(".wx-btn").click(function(e){

        e.preventDefault();
        $.ajax({
            url: "<?php echo Url::to(['loan/first-notice'])?>",
            type: "post",
            data: {id : "<?php echo $order['id']?>"},
        })
        if($(this).hasClass('wx-copy')){
            copyText('<?php echo WEIXIN_GONGZHONGNHAO_SHORENAME; ?>');
            returnNative('14');
        }
        $('#loan-dialog').hide();
    })
    <?php endif;?>


    // 监听显示图片的方法
    $("#event_btn_img").on("click", function (e) {
        e.preventDefault();
        var code = $("#materia_code").val();
        var order_id = "<?php echo $id; ?>";
        if (code == 2) {
            // 显示图片
            $("#pop-image").show();
        } else {
            try {
                nativeMethod.returnNativeMethod('{"type":"9","object_id":"'+ order_id +'"}');
            } catch (e) {

            }
        }
    });

    // 取消按钮操作
    $("#btn_user_canel").on("click", function (e) {
        $("#comments").hide();
    })

    // 取消显示
    <?php if (count($user_materia) > 0 && is_array($user_materia)) { ?>
    $("#pop-image").on("click", function () {
        $("#pop-image").hide();
    });
    <?php } ?>

    // 监听我要留言
    $("div.content li a.btn_user_book").on("click", function (e) {
        e.preventDefault();
        $("#comments").show();
    });


    // 监听我要留言方法
    $("#btn_user_book").on("click", function (e) {
        e.preventDefault();
        var url = "<?php echo $book_url; ?>";
        var orders_id   = "<?php echo $id; ?>";
        var orders_text = $("#txt_content").val().replace(/<\/?[^>]*>/g, '');

        url = url + "?order_id=" + orders_id + "&order_text=" + orders_text;
        $.ajax({
            type: "get",
            url: url,
            dataType: "jsonp",
            data: "",
            jsonpCallback: "callback",
            success: function (data) {
                $("#comments").hide();
                $("#message_span").html("<span>" + data.message + "</span>");
                $("#message_span").show();
                window.location.reload();
            }
        });

    });

    //立即还款
    $("#repayment_btn").on("click", function () {
        var order_id = "<?php echo $id; ?>";
        var url = "<?php echo Url::toRoute(['loan/loan-repayment-type']); ?>";
        var coupon_id = $("#coupon_id").val();
        var loan_repaymenttype='';
        if($('input[name="loanrepaymenttype"]').length>0){
            if($('input[name="loanrepaymenttype"]:checked').length>0){
                loan_repaymenttype = $('input[name="loanrepaymenttype"]:checked').val();
            }
        }
        <?php if ($repayment['is_overdue'] && $repayment['overdue_day'] && $part_repay_money) { ?>
        var part_money = $(".fb-text i").text();
        var total_repay_money = $("#total_repay_money").text();
        if(part_money  == total_repay_money) {
            window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id;
        } else {
            if(loan_repaymenttype.toString()=='1') {
                window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id + "&loan_repaymenttype=" + loan_repaymenttype.toString();
            }else{
                window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id + "&part_money=" + part_money;
            }
        }
        <?php } else { ?>
        if(loan_repaymenttype.toString()=='1') {
            window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id + "&loan_repaymenttype=" + loan_repaymenttype.toString();
        }else{
            window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id;
        }
        <?php } ?>

        MobclickAgent.onEventWithLabel("applydetail_return", "借款详情-立即还款"); //打点
    });

    /**
     *  显示催收投诉
     **/
    function  showCollection(){
        <?php if ($show_collection): ?>
        var order_id = "<?php echo $id; ?>";
        try {
            nativeMethod.returnNativeMethod('{"type":"11","order_id":"'+ order_id +'"}');
        } catch (e) {

        }
        <?php endif ?>
    }

    /*
     * 显示红包
     */
    function showCoupon() {
        <?php if ($red_packet): ?>
        try {
            nativeMethod.returnNativeMethod('<?= $red_packet; ?>');
        } catch (e) {

        }
        <?php endif ?>
    }

    /*
     *  底部button不显示时，页面下面不留空白
     */
    function buttonShow(){
        if($('.fixed-button').length===0){
            $(".repayment-detail.wrap").css("paddingBottom","0");
        }else {
            $(".repayment-detail.wrap").css("paddingBottom","1.30667rem");
        }
    }

    // 借款流程的iconfont
    iconfont();
    function iconfont(){
        $('.repayment-detail.wrap .content ul').find("li").eq(0).find('.iconfont').html('&#xe600;');
        $('.repayment-detail.wrap .content ul').find("li").eq(1).find('.iconfont').html('&#xe63d;');
        $('.repayment-detail.wrap .content ul').find("li").eq(2).find('.iconfont').html('&#xe641;');
        $('.repayment-detail.wrap .content ul').find("li").eq(3).find('.iconfont').html('&#xe735;');
        // $('.repayment-detail.wrap .content ul').find("li").eq(0).find('.iconfont').addClass('icon-0');
        // $('.repayment-detail.wrap .content ul').find("li").eq(1).find('.iconfont').addClass('icon-1');
        // $('.repayment-detail.wrap .content ul').find("li").eq(2).find('.iconfont').addClass('icon-2');
        // $('.repayment-detail.wrap .content ul').find("li").eq(3).find('.iconfont').addClass('icon-3');
    }

    /*
     *  控制借款流程
     */
    function getShowType(){
        var showType = Number($("#show_type").text());
        switch (showType){
            case 1:
                $(".repayment-detail.wrap .content ul").find("li").eq(0).attr("class","sType0");//待审核1
                break;
            case 2:
                $(".repayment-detail.wrap .content ul").find("li").eq(1).attr("class","sType1");//打款中2

                break;
            case 5:
                $(".repayment-detail.wrap .content ul").find("li").eq(2).attr("class","sType2");//待还款5
                $(".repayment-detail.wrap .content .pay-status").find("h1").css("color","#1ec8e1");
                break;
            case 4:
                $(".repayment-detail.wrap .content ul").find("li").eq(3).attr("class","sType3");// 已逾期4
                $(".repayment-detail.wrap .content .pay-status").find("h1").css("color","#f41c1c");
                break;
            case 6:
                $(".repayment-detail.wrap .content ul").find("li").eq(1).attr("class","sType3");// 审核未通过6
                break;
            default:
                $(".repayment-detail.wrap .content .pay-status").find("h1").css("color","#666");
        }
    }

    /*
    * 借款明细的展开 收起
    *
    * */
    $(".open").on("click", function () {
        $(".open").toggleClass("close");
        if($(".open").text() ==='收起'){
            $(".hide").slideUp();
            $(".open").text("展开");
        }else {
            $(".hide").slideDown();
            $(".open").text("收起");
        }

    })

    /*
    * 还款金额选择下拉框
    *
    * */

    // 金额改变颜色变化
    $('#selected').change(function () {
        $('#selected').css('color','#333');
        $('.fb-text').find('i').text($('#selected').val());
        if($('input.repayment').size()>0){
            $('input.repayment:checked').attr('checked',false);
        }
    })

    function loanrepaymenttype(repaymenttype) {
        var repaymentall='<?= sprintf("%0.2f", ($repayment["remain_money_amount"]) / 100) ?>';
        var repaymentloan='<?= sprintf("%0.2f", ($zhanqi_money+$repayment["interests"]+$repayment["late_fee"]) / 100) ?>';
        if($('#selected').length>0){
            $('#selected').get(0).selectedIndex=0;
        }
        if(repaymenttype.toString()=='0'){
            $('.fb-text').find('i').text(repaymentall.toString());
            $('.fixed-button #repayment_btn').html('立即还款');
        }else{
            $('.fb-text').find('i').text(repaymentloan.toString());
            $('.fixed-button #repayment_btn').html('立即展期');
        }
    }

    function clickFn() {
        // 动画效果
//        setTimeout(function () {
//            $('body').animate({scrollTop:'-5rem'},500);
//        },500);
    }


    /*
    * 点击其他贷款平台打点
    *
    * */

    function goOther() {
        setWebViewFlag();
        try {
            MobclickAgent.onEventWithLabel("applydetail_more","借款详情-获取更多机会");
        } catch (e) {
            console.log(e);
        }
    }

    // 点击还款状态
    $('.pay-status').on('click', function () {
        window.location='<?= Url::toRoute(["loan/loan-detail?id={$id}&page_action=get_more"]); ?>';
    })


    /*
    *综合费用明细
     */
    loanZongHe();
    function loanZongHe(){
        $('.zonghe').on('click',function () {
            $('.loan_zonghe').css('display','block');
        })
        $('.hide_zonghe').on('click',function () {
            $('.loan_zonghe').css('display','none');
        })
    }
</script>
