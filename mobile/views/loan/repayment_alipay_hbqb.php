<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<style type="text/css">
    body{
        background: #f5f5f7;
    }
    .pay-result .container p .copytext {
        margin: .3rem 0;
    }
    /*.pay-result .container p a {
        color: #ff6462;
        text-decoration: none;
        border: 1px solid #ff6462;
        padding: .15rem .4rem;
        border-radius: .15rem;
    }*/
    /*.pay-result a.button {
        background: #fff;
        display: block;
        text-decoration: none;
        color: #ff6462;
        font-size: 0.4rem;
        text-align: center;
        height: 1.3333333333rem;
        width: 10rem;
        line-height: 1.3333333333rem;
        position: fixed;
        bottom: 0;
        left: 0;
        border-top: 1px solid #e6e6e6;
    }*/

    /*新增css-----------------*/
    .pay-result .container {
        color: #666;
        font-size: 0.3733333333rem;
        padding-bottom:0;
        border-top: 0.2933333333rem solid #f2f2f2;
        padding-top: 0;
    }
    .pay-result .container h2 {
        padding: 0 0.4rem;
        font-size: 0.426667rem;
        color: #333;
        border-bottom: 1px solid #d9d9d9;
        height: 1.333333rem;
        line-height: 1.333333rem;
    }
    .pay-result .container p {
        margin-bottom: 0.6rem;
        font-size: 0.4rem;
        color: #666;
    }
    .pay-result p i {
        font-style: normal;
        color: #ff8003;
        font-size: 0.4rem;
    }
    .pay-result .container p span a {
        text-decoration: none;
        padding: 0.333333rem 0.3rem 0.333333rem 0.2rem;
        border-radius: .15rem;
        color: #1ec8e1
        text-decoration: none;
        -webkit-tap-highlight-color:transparent;
    }
    .pay-result .container p  span a span{
        padding: 0.133333rem 0.333333rem;
        border: 1px solid #1ec8e1
        color: #1ec8e1
        border-radius: 0.133333rem;
    }

    .pay-result a.button {
        background: #fff;
        display: block;
        text-decoration: none;
        color: #1ec8e1
        font-size: 0.4rem;
        text-align: center;
        height: 1.3333333333rem;
        width: 10rem;
        line-height: 1.3333333333rem;
        position: fixed;
        bottom: 0;
        left: 0;
        border-top: 1px solid #e6e6e6;
    }
    .pay-result .container p span {
        color: #666;
    }
</style>
<div class="pay-result">
    <div class="container">
        <h2>转账到支付宝账户进行还款，操作流程如下:</h2>
        <p>1、进入支付宝首页，点击【转账】，选择【转到支付宝账户】，输入支付宝账户 <i>hk@xxxxx.com</i><span><a href="javascript:copyText('hk@xybaitiao.com')" class="copytext"><span>复制账号</span></a>可通过账户全名“<?php echo COMPANY_NAME;?>”进行校验</span>
            <!-- <div class="copytext">
                <a href="javascript:copyText('hk@xybaitiao.com')" > 复制账户</a>
            </div> -->
        </p>
        <p>2、点击【下一步】，输入转账金额，并添加备注 <i><?php echo $data['name']; ?> <?php echo $data['phone']; ?> <?php echo '还款';?></i><span><a href="javascript:copyText('<?php echo $data['name']; ?> <?php echo $data['phone'] ; ?> <?php echo '还款';?>')" > <span>复制备注</span></a></span></p>
        <p>3、点击【确认转账】，输入支付密码即可完成还款</p>
        <p>温馨提示<br/>支付宝转账金额须与还款页面提示的还款金额一致，否则将无法成功还款。转账成功后，请及时关注还款状态。</p>
    </div>
    <a class="button" href="<?=ApiUrl::toRouteCredit(['credit-web/alipay-process'])?>">查看图文说明</a>
</div>

<!-- 指定返回 这里有个坑 https -->
<script type="text/javascript">
    $(function(){
        function pushHistory() {
            var main_url = "<?=Url::toRoute(['loan/loan-repayment-type','id'=>$order['id']])?>";
            var state = {
                title: "index",
                url: main_url
            };
            window.history.pushState(state, "index", location.href);
            state = {
                title: "index",
                url: ""
            };
            window.history.pushState(state, "index", "");
        }

        setTimeout(function () {
            pushHistory();

            window.addEventListener("popstate", function (e) {

                if (window.history.state != null && window.history.state.url != "") {
                    location.href = window.history.state.url
                }
            });
        }, 300);

    });
</script>
