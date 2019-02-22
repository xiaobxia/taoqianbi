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
    .pay-result .container img {
        height:4rem;
        width: 2.9rem;
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
<?php
$company = (isset($company_name)&&$company_name) ? $company_name : ALIPAY_NAME;

?>
<div class="pay-result">
    <div class="container">
        <h2>转账到微信账户进行还款，操作流程如下:</h2>
        <p style="margin-bottom:10px;">1、截图并保存以下微信付款图片到手机相册。</p>
        <p style="text-align:center;height:auto;line-height:normal;padding:0;margin:0;"><img src="<?= $this->absBaseUrl;?>/image/apply_img/Wechat.jpeg" class="copytext" style="padding:0;margin:0;width:6rem;height:7.5rem;" /></p>
        <p style="margin-top:6px;">2、进入微信，点击右上角➕【扫一扫】，选择右上角【相册】，选择收款码图片，点击右上角【完成】</p>
        <p>3、输入付款金额，并添加备注 <i><?php echo $data['name']; ?> <?php echo $data['phone']; ?> <?php echo '还款';?></i><span><a href="javascript:copyText('<?php echo $data['name']; ?> <?php echo $data['phone'] ; ?> <?php echo '还款';?>')" > <span>复制备注</span></a>。点击【付款】</span></p>
        <p>4、点击【确认支付】，输入支付密码即可完成还款</p>
        <p>温馨提示<br/>微信转账金额须与还款页面提示的还款金额一致，否则将无法成功还款。转账成功后，请及时关注还款状态。</p>
    </div>
<!--    <a class="button" href="--><?//=ApiUrl::toNewh5(['app-page/show-pay'])?><!--">查看图文说明</a>-->
</div>
