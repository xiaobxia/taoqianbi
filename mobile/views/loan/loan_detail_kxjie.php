<?php
use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use yii\helpers\Url;
use mobile\components\ApiUrl;

?>
<style>
    .more_chance_button_background{
        background:#1683E1;
    }
    body {
        background-color: #f2f2f2;
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
    <?php if ($user_coupon["status"]): ?>
       <!-- <div class="head"><h3>申请进度</h3></div>-->
        <div class="prompt">
            <div>
                <?php if ($user_coupon["status"] == 1) : ?>
                    <a class="slow" href="<?= $link_url; ?>">本单享受慢就赔服务</a>
                <?php elseif ($user_coupon["status"] == 2) : ?>
                    <h3>本单处理耗时<?php echo $user_coupon["time"]; ?></h3>
                <?php elseif ($user_coupon["status"] == 4) : ?>
                    <a class="slow" href="<?= $slow_url; ?>">本单享受拒就赔服务</a>
                <?php elseif ($user_coupon["status"] == 3) : ?>
                    <span class="time" href=""><i class="time_up" id="timeShow"><?php echo $user_coupon["time"]; ?></i>本单参与慢就赔活动</span>
                <input type="hidden" id="hour" value="<?php echo $user_coupon['hour']; ?>" />
                <input type="hidden" id="min" value="<?php echo $user_coupon['min']; ?>" />
                <input type="hidden" id="sec" value="<?php echo $user_coupon['sec']; ?>" />
                    <!-- 当前状态为4才执行 -->
                    <script language="javascript">
                        var t = null;
                        t = setTimeout(time, 1000);//开始执行
                        function time()
                        {
                            clearTimeout(t);//清除定时器
                            dt = new Date();
                            var hour = document.getElementById('hour').value;
                            var min = document.getElementById('min').value;
                            var sec = document.getElementById('sec').value;
                            if (sec <= 59) {
                                sec++;
                                document.getElementById("sec").value = sec;
                                if (sec == 60) {
                                    sec = 0;
                                    document.getElementById("sec").value = sec;
                                    min++;
                                    document.getElementById("min").value = min;
                                    if (min == 60) {
                                        min = 0;
                                        document.getElementById("min").value = min;
                                        hour++;
                                        document.getElementById("hour").value = hour;
                                        if (hour == 2) {
                                            myrefresh();
                                        }
                                    }
                                }
                            }
                            document.getElementById("timeShow").innerHTML = pad(hour, 2) + ":" + pad(min, 2) + ":" + pad(sec, 2);

                            t = setTimeout(time, 1000); //设定定时器，循环执行
                        }
                        function pad(num, n) {
                            var len = num.toString().length;
                            while (len < n) {
                                num = "0" + num;
                                len++;
                            }
                            return num;
                        }
                        function myrefresh()
                        {
                            window.location.reload();
                        }
                        setTimeout('myrefresh()', 600000); //指定600秒刷新一次
                    </script>
                <?php endif ?>
            </div>
        </div>
    <?php else: ?>
        <!--<div class="head"><h3>借款详情</h3></div>  -->
    <?php endif ?>
    <div style="display: none" id="show_type"><?=$show_type?></div>
    <div class="content">
        <?php if ($repayment && $page_action != "get_more") { ?>
            <div class="pay-status">
                <a class="pay-status-detail" href="<?php echo Url::toRoute(["loan/loan-detail?id={$id}&page_action=get_more"]); ?>"></a>
                <h1>
                    <?php echo $head_show['new_title']; ?>
                </h1>
                <p><?php echo $head_show['new_desc']; ?></p>
            </div>
        <?php } else { ?>
            <ul>
                <?php foreach ($list as $value) { ?>
                    <li class="<?= isset($value['class']) ? $value['class'] : '' ?>">
                        <i class="iconfont"></i>
                        <h1 style="position:relative;">
                            <?= $value['title'] ?>
                            <?php if(isset($value['btn_url']) && $value['btn_url'] && $is_show == 1){ ?>
                                <a class="more_chance_button_background" style=" position:absolute;right:0;display:inline-block;padding:0.072rem 0.2rem;color:#fff;font-size:0.3466666667rem;text-decoration:none;border-radius:8px;" href="<?= ApiUrl::toNewh5(['discover/more'])?>">获取更多机会</a>
                            <?php } ?>
                        </h1>
                        <p><?= $value['body'] ?></p>
                    </li>
                <?php } ?>
            </ul>

        <?php } ?>
    </div>

    <?php if ($page_action != "get_more") { ?>
    <div class="item">
        <p class="item-head">借款明细</p>
        <ul>
            <li><span>借款金额</span><i><?= sprintf("%0.2f", $order['money_amount'] / 100) ?></i></li>
            <li><span>实际到账</span><i><?= sprintf("%0.2f", $order['true_money_amount'] / 100) ?></i></li>
            <li><span>综合费用</span><i><?= sprintf("%0.2f", $order['counter_fee'] / 100) ?></i></li>
            <li><span>借款期限</span><i><?= $order['loan_term'] ?>天<?= $daly_tip ?></i></li>
            <li><span>借款时间</span><i><?= date('Y-m-d', $order['order_time']) ?></i></li>
            <?php if (isset($order['bank_info'])) { ?>
                <li><span>收款账号</span><i><?= $order['bank_info'] ?></i></li>
            <?php } ?>
            <?php if($flag_show == 1){ ?>
                <li style="margin-bottom: 0.8rem;">
                    <span>协议说明</span>
                    <a href="<?php echo $url_one;?>" >《借款协议》</a><br>
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
                    <i><?php if($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE){echo '0.00';}else{echo sprintf("%0.2f", $repayment['remain_money_amount'] / 100);}?></i>
                </li>

                <li>
                    <span>已还本金</span>
                    <i><?= sprintf("%0.2f", $repayment['true_total_money'] / 100) ?></i>
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
                <div class="fixed-button c_f_btn" >
                    <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <del class="fb-text-chooseCoupon"><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></del>
                    <!-- <?php if(!empty($coupon_list)) {?> -->
                    <!-- <span class="chooseCoupon">选择优惠</span> -->
                    <!-- <?php } ?> -->
                    <a class="fb-btn" id="repayment_btn" href="javascript:void(0);">立即还款</a>
                </div>
            <?php } else { //逾期?>
                <div class="fixed-button" >
                    <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <a class="fb-btn" id="repayment_btn" href="javascript:void(0);">立即还款</a>
                </div>
            <?php } ?>

        <?php } ?>
    <?php }else{ ?>
        <?php if ($show_emergency) { ?>
            <div class="button clearfix">
                <a class="the-one" href="<?php echo $emergency_url; ?>">江湖救急</a>
            </div>
        <?php } ?>
    <?php } ?>

    <?php if ($repayment) { ?>
            <?php if ($repayment['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { //还款未完成?>
    <div class="item">
        <ul style="padding-top: 0;">
            <li>
                <span>我的优惠券</span>
                <?php if($coupon_count > 0) { ?>
                    <i class = "to-repaid" ><?php echo $coupon_count; ?>张可用</i>
                <?php } else { ?>
                    <i class = "overdue">不可用</i>
                <?php } ?>
            </li>
        </ul>
    </div>
            <?php } ?>
    <?php } ?>

    <?php } else { ?>
        <div class="item">
            <ul>
                <li style="margin-bottom: 0.8rem;">
                    <span>协议说明</span>
                    <a href="<?php echo $url_one;?>" >《借款协议》</a><br>
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
    <div class="dialogbg"></div>
    <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT):?>
    <div class="dialog wx" id="wx_dialog" style="display: block;">
        <div class="wx-head">
            <p>
                <i class="iconfont">&#xe63d;</i><br/>
                借款申请提交成功
            </p>
        </div>
        <div class="wx-prompt">
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

<script type="text/javascript">
    $(document).ready(function () {
        setWebViewFlag();
        MobclickAgent.onEvent("apply_detail"); //页面进入打点

        <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT && $page_action != "get_more"):?>
        $('#loan-dialog').show();
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

    <?php if($weixin_show == true && $source == \common\models\LoanPerson::PERSON_SOURCE_MOBILE_CREDIT):?>
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
        window.location.href = url + "?id=" + order_id + "&coupon_id=" + coupon_id;
        MobclickAgent.onEventWithLabel("apply_detail", "立即还款按钮"); //打点
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
                $(".repayment-detail.wrap .content .pay-status").find("h1").css("color","#6a4dfc");
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
</script>
