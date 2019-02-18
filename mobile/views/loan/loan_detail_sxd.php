<?php

use common\models\UserLoanOrderRepayment;
use common\models\UserCreditMoneyLog;
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<div class="wrap repayment-detail sxdai">
    <?php if ($user_coupon["status"]): ?>
        <div class="head"><h3>申请进度</h3></div>
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
        <ul>
            <?php foreach ($list as $value) { ?>
                <li class="<?= isset($value['class']) ? $value['class'] : '' ?>">
                    <!--<span class="status-icon"></span>-->
                    <i class="iconfont"></i>
                    <h1 style="position:relative;">
                        <?= $value['title'] ?>
                        <?php if(isset($value['btn_url']) && $value['btn_url']){ ?>
                        <a style="position:absolute;right:0;display:inline-block;padding:0.072rem 0.2rem;color:#fff;font-size:0.3466666667rem;text-decoration:none;border-radius:8px;" href="<?= ApiUrl::toNewh5(['discover/more'])?>">获取更多机会</a>
                        <?php } ?>
                    </h1>
                    <p><?= $value['body'] ?></p>
                </li>
            <?php } ?>
        </ul>
    </div>
    

    <?php if (is_array($user_materia) && count($user_materia) > 0) { ?>
     <!--   <input id="materia_code" type="hidden" value="<?php /*echo $user_materia["code"]; */?>" />
        <p>我已还款，但状态未更新？<a id="event_btn_img" href="javascript:void(0);"><?php /*echo $user_materia["title"] */?></a></p>-->
    <?php } ?>

    <div class="item">
        <p class="item-head">借款明细</p>
        <ul>
            <li><span>借款金额</span><i><?= sprintf("%0.2f", $order['money_amount'] / 100) ?></i></li>
            <li><span>实际到账</span><i><?= sprintf("%0.2f", $order['true_money_amount'] / 100) ?></i></li>
            <li><span>综合费用</span><i><?= sprintf("%0.2f", $order['counter_fee'] / 100) ?></i></li>
            <?php if ($repayment) { ?>
                <?php if (!empty($repayment['coupon_money'])) { ?>
                    <li><span>抵扣金额</span><i><?= sprintf("%0.2f", $repayment['coupon_money'] / 100) ?></i></li>
                <?php } ?>
            <?php } ?>
            <li><span>借款期限</span><i><?= $order['loan_term'] ?>天<?= $daly_tip ?></i></li>
            <li><span>申请日期</span><i><?= date('Y-m-d', $order['order_time']) ?></i></li>
            <?php if (isset($order['bank_info'])) { ?>
                <li><span>收款银行</span><i><?= $order['bank_info'] ?></i></li>
            <?php } ?>
            <?php if($flag_show == 1){ ?>
               <li style="margin-bottom: 0.65rem;">
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
            <p class="item-head">还款信息</p>
            <ul>
                <li>
                    <span>待还金额</span>
                    <i><?= sprintf("%0.2f", $repayment['remain_money_amount'] / 100) ?></i>
                </li>
                <li>
                    <span>优惠金额</span>
                    <?php if($order['coupon_id'] == 0){?>
                        <i id="coupon">无可用优惠券</i>
                    <?php }else if($repayment['is_overdue']){?>
                        <i id="coupon">逾期不可用</i>
                    <?php }else if($repayment['coupon_money'] != 0 && $repayment['coupon_id'] > 0){?>
                        <i id="coupon">-<?= sprintf("%0.2f", $repayment['coupon_money'] / 100) ?></i>
                    <?php }else{?>
                        <i id="coupon">- -</i>
                    <?php }?>
                </li>
                <li>
                    <span>已还金额</span>
                    <i><?= sprintf("%0.2f", $repayment['true_total_money'] / 100) ?></i>
                </li>
                <li>
                    <span>最迟还款日期</span>
                    <i><?= date('Y-m-d', $repayment['plan_fee_time']) ?></i>
                </li>
                <li>
                    <span>实际还款日期</span>
                    <i><?= $repayment['true_repayment_time'] ? date('Y-m-d', $repayment['true_repayment_time']) : '- -' ?></i>
                </li>
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
        <?php if ($repayment['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { ?>
            <?php if ($remainDelayTimes > 0 && !$repayment['is_overdue']) { ?>

                <?php if ($useFy) { ?>
                    <a class="fixed-button" href="<?= Url::toRoute(['loan/loan-delay', 'id' => $order['id'], 'type' => UserCreditMoneyLog::TYPE_PLAY]) ?>">申请续期</a>
                <?php } ?>
                
                <div class="fixed-button" >
                    <?php if($repayment['is_overdue']){?>
                        <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <?php }else{?>
                        <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <?php }?>
                    <a class="fb-btn" href="<?= Url::toRoute(['loan/loan-repayment-type', 'id' => $order['id']]) ?>">立即还款</a>
                </div>
            <?php } else { ?>
                <div class="fixed-button" >
                    <?php if($repayment['is_overdue']){?>
                        <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <?php }else{?>
                        <span class="fb-text">共<i><?= sprintf("%0.2f", ($repayment['remain_money_amount']) / 100) ?></i>元</span>
                    <?php }?>
                    <a class="chooseCoupon" href="" style="display: none;">选择抵扣券</a>
                    <a class="fb-btn" href="<?= Url::toRoute(['loan/loan-repayment-type', 'id' => $order['id']]) ?>">立即还款</a>
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

<!--弹出框-->
<!--<div class="dialogbg"></div>
<div class="dialog">
	<div class="dl-title">请选择优惠券</div>
	<span class="close">关闭</span>
	<div class="dl-main">
		<div class="coupons">
			<div class="coupon">
				<div class="cp-left">
					<p>￥15</p>
					<p>满500元可用</p>
				</div>
				<div class="cp-right">
					<p>仅适用于还款抵扣</p>
					<p>仅限银行卡还款使用</p>
					<p>有效期：2017.6.5-2017.6.20</p>
				</div>
				<span class="checkbox">
					<p>仅适用于还款抵扣</p>
					<p>仅限银行卡还款使用</p>
					<p>有效期：2017.6.5-2017.6.20</p>
				</span>
			</div>
			<div class="coupon">
				<div class="cp-left">
					<p>￥15</p>
					<p>满500元可用</p>
				</div>
				<div class="cp-right"></div>
				<span class="checkbox"></span>
			</div>
		</div>
		
		<div class="no-coupons">
			不使用优惠券
			<span class="checkbox">
				
			</span>
		</div>
	</div>
</div>-->

<script type="text/javascript">
    $(document).ready(function () {
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
                $(".repayment-detail.wrap .content ul").find("li").eq(0).attr("class","sType0"); //待审核1
                break;
            case 2: 
                $(".repayment-detail.wrap .content ul").find("li").eq(1).attr("class","sType1"); //打款中2
                break;
            case 5:
                $(".repayment-detail.wrap .content ul").find("li").eq(2).attr("class","sType2"); //待还款5
                break;
            case 4:
                $(".repayment-detail.wrap .content ul").find("li").eq(3).attr("class","sType3"); // 已逾期4
                break;
            case 6:
                $(".repayment-detail.wrap .content ul").find("li").eq(1).attr("class","sType3"); // 审核未通过6
                break;
        }
        
    }
</script>
