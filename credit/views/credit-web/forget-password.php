<div class="forget-password">
  <style>
    body {
      background: #f2f2f2
    }
    .forget-password .piece .head i {
        font-style: normal;
        color: <?php echo $color?>;
        display: inline-block;
        margin-left: 0.1333333333rem;
    }
    .forget-password .piece button {
        padding: 0 0.4rem;
        height: 0.8rem;
        line-height: 0.8rem;
        font-size: 0.4266666667rem;
        color: #fff;
        background: <?php echo $color?>;
        border: none;
        border-radius: 0.4rem;
    }
    .forget-password .piece .content a {
        text-decoration: none;
        color:<?php echo $color?>;
    }
    .forget-password .tab li.action {
        color: #fff;
        background: <?php echo $color?>;
    }
    .forget-password .tab li {
        float: left;
        width: 3.0666666667rem;
        height: 0.9333333333rem;
        font-size: 0.4266666667rem;
        border: 0.0266666667rem solid <?php echo $color?>;
        border-right: none;
        color: <?php echo $color?>;
        background: #fff;
    }
    .forget-password .tab li:last-child {
        border-top-right-radius: 0.1333333333rem;
        border-bottom-right-radius: 0.1333333333rem;
        border-right: 0.0266666667rem solid <?php echo $color;?>;
    }
  </style>
  <ul class="tab clearfix">
    <li class="action">移动</li>
    <li>联通</li>
    <li>电信</li>
  </ul>
  <ul class="item">
    <li>
      <div class="piece">
        <div class="head">
          <h2>方案一 <i>(推荐)</i></h2>
          <button><a href="tel:10086">拨打电话</a></button>
        </div>
        <div class="content yd">
          <p>1.使用本机拨打10086客服电话热线，按照语音提示进行密码重置；</p>
          <br>
          <p>2.操作步骤：电话10086→按1→按4（需待语音提示结束时操作）→按1→输入身份证号码并以#号键结束→输入发送到手机的短信随机码，并以#键结束→设置新服务密码</p>
        </div>
      </div>

      <div class="piece">
        <div class="head">
          <h2>方案二</h2>
          <button><a href="http://touch.10086.cn">进入官网</a></button>
        </div>
        <div class="content yd">
          <p>进入移动官网找回密码：<a href="http://touch.10086.cn">http://touch.10086.cn</a></p>
          <p>1. 选择手机号归属地省份</p>
          <p>2. 点击【登录】，选择【登录网上营业厅】</p>
          <p>3. 点击【忘记密码】进入相应页面找回密码</p>
        </div>
      </div>
    </li>

    <li style="display:none">
      <div class="piece">
        <div class="head">
          <h2>方案一 <i>(推荐)</i></h2>
          <button><a href="tel:10010">拨打电话</a></button>
        </div>
        <div class="content">
          <p>1.使用本机拨打10010客服电话热线，按照语音提示进行密码重置；</p>
          <br>
          <p>2.操作步骤：电话10010→按3→按1→输入身份证并以#号键结束→联通发送新密码至手机号；</p>
        </div>
      </div>

      <div class="piece">
        <div class="head">
          <h2>方案二</h2>
          <button><a href="http://m.10010.com">进入官网</a></button>
        </div>
        <div class="content">
          <p>进入联通官网找回密码：<a href="http://m.10010.com">http://m.10010.com</a></p>
          <p>1. 点击【登录】</p>
          <p>2. 点击【忘记密码】进入相应页面找回密码</p>
        </div>
      </div>
    </li>

    <li style="display:none">
      <div class="piece">
        <div class="head">
          <h2>方案一 <i>(推荐)</i></h2>
          <button><a href="tel:10000">拨打电话</a></button>
        </div>
        <div class="content">
          <p>1.使用本机拨打10000客服电话热线，按照语音提示进行密码重置；</p>
          <br>
          <p>2.操作步骤：电话10000→按1→按1→按5→按1→按1→电信发送新密码至手机号；</p>
        </div>
      </div>

      <div class="piece">
        <div class="head">
          <h2>方案二</h2>
          <button><a href="http://m.sh.189.cn">进入官网</a></button>
        </div>
        <div class="content">
          <p>进入电信官网找回密码：<a href="http://m.sh.189.cn">http://m.sh.189.cn</a></p>
          <p>1. 选择手机号归属地省份</p>
          <p>2. 点击【登录】</p>
          <p>3. 点击【忘记密码】进入相应页面找回密码</p>
        </div>
      </div>
    </li>
  </ul>
</div>
<script>
  $(function() {
    // 进入页面打点
    setWebViewFlag();
    MobclickAgent.onEvent('tel_forget_find','手机服务密码找回页'); 

    $('ul.tab li').click(function(event) {
      $('ul.tab li').removeClass('action');
      $(this).addClass('action');
      var i = $(this).index();
      $('ul.item li').hide().eq(i).show();
    })
    $('li>div.piece:first-child .head button a').click(function(e) {
      var tel = $(this).attr('href').replace('tel:', '');
      if ((typeof nativeMethod !== 'undefined') && (typeof nativeMethod.callPhoneMethod !== 'undefined')) {
        nativeMethod.callPhoneMethod(tel);
        return false;
      }
    })
  })
</script>
