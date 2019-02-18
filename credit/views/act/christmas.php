<?php

use yii\helpers\Url;
use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
//echo \common\widgets\WxShare::widget([
//    'title' => '测试啊',
//    'desc' => '测试啊测试啊测试啊',
//    'link' => Url::toRoute(['/act/christmas-coupon'], true),
//    'imgUrl' => $this->staticUrl('/css/img/christmas-icon-06.png'),
//]);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <title><?php echo isset($this->title) ? $this->title : '圣诞活动页'; ?></title>
    <script src="<?php  echo $baseUrl; ?>/js/flexible-invite.js"></script>
    <script src="<?php echo $baseUrl; ?>/js/jquery.min.js"></script>
    <link href="<?php echo $baseUrl; ?>/css/act/christmas.css" rel="stylesheet" />
    <script>
        var _hmt = _hmt || [];
        (function() {
        var hm = document.createElement("script");
        hm.src = "https://hm.baidu.com/hm.js?3ac5a6a835b4ee96a11d699ee4f6b39a";
        var s = document.getElementsByTagName("script")[0]; 
        s.parentNode.insertBefore(hm, s);
        })();
    </script>
</head>
<body>
	<div class="christmas-body">
		<header>
			<h2>活动时间:12月20日-12月25日</h2>
		</header>
		<div class="roll">
      <div class="t_news">
        <ul class="news_li">
          <li>恭喜13******180获得免息7天</li>
          <li>恭喜15******179获得免息7天</li>
          <li>恭喜18******165获得免息7天</li>
          <li>恭喜17******183获得免息7天</li>
          <li>恭喜13******145获得免息7天</li>
          <?php if ($message) : ?>
                <?php foreach ($message as $v): ?>
                    <li><?= $v ?> </li>
                <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="content">
    	<h3>圣诞狂欢</h3>
    	<h4>活动期间成功借款，即可打开圣诞礼盒 (限开一次)</h4>
    	<div class="christmas-tree">
    		<img id="image_gift" class="shake" src="<?php  echo $baseUrl; ?>/css/img/christmas-icon-06.png"/>
    	</div>
    	<a id="draw"><span>打开你的圣诞豪礼<span></a>
    	<p id="show_draw_detail">暂未获任何奖励</p>
    </div>
    <div class="rule">
    	<h3>- 活动规则 -</h3>
    	<p>1、活动时间：2016年12月20日00:00——12月25日23:59</p>
    	<p>2、抽奖资格：活动期间申请借款并审核通过的用户，每位用户活动期间仅可参与一次抽奖</p>
    	<p>3、活动奖励：</p>
					<p>永久提额：用户在活动期间成功借款，且该笔借款正常还款（未逾期），则将在还款后获得额外永久提额，额度将自动添加到个人信用额度中</p>
					<p>7天免息奖励：用户将获得在活动期间首笔借款的7天免息特权，奖励以现金抵扣券形式发放，可至“我的”-“我的优惠”中查看
                        例：小明在活动期间成功借款1000元，期限为7天，那么他将获得98元的现金抵扣券奖励
                    </p>
					<p>抵扣券奖励：用户获得后将立即发放到账户，可在“我的”-“我的优惠”中查看，借款时可直接抵扣现金</p>
    	<p>4、永久提额的额度将永久有效，抵扣券有效期均为20天</p>
    	<p>5、本活动最终解释权<?php echo APP_NAMES;?>所有，客服热线：4006812016</p>
    </div>
    <div class="popup" style="display: none;">
      <div class="overlay"></div>
      <div class="dialog">
        <div class="close"><span></span></div>
        <h2 ><span>马上借款<br/>打开你的圣诞礼盒</span></h2>
        <a id="button_a" href="javascript:void(0);">马上申请</a>
      </div>
    </div>
	</div>
    <?php   $type_info = isset($pre_info['type'])?$pre_info['type']:'';
            $title_info = isset($pre_info['title'])?$pre_info['title']:'';
    ?>
	<script>
		$(function(){
            //判断是否已经抽过奖了，若抽过则显示出获得奖项
            var type_info ="<?php echo  $type_info?>";
            var title_info="<?php echo  $title_info?>";
            var buttonFlag = 0;
            if(type_info.length>0){
                var title = title_info;
                var type = type_info;
                if(type =='free')
                    var show_html = '恭喜您获得了<b>'+title+'</b>一张';
                else
                    var show_html = title;
                $('#show_draw_detail').html(show_html); 
            }
            var browser = {
                    versions: function () {
                        var u = navigator.userAgent, app = navigator.appVersion;
                        return {//移动终端浏览器版本信息
                            trident: u.indexOf('Trident') > -1, //IE内核
                            presto: u.indexOf('Presto') > -1, //opera内核
                            webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
                            gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
                            mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
                            ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                            android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
                            iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器
                            iPad: u.indexOf('iPad') > -1, //是否iPad
                            webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
                            wx: u.indexOf(/MicroMessenger/) > -1 //是否wx应用程序
                        };
                    }(),
                    language: (navigator.browserLanguage || navigator.language).toLowerCase()
                }
            var $ul = $('.news_li');
            var $li = $('.news_li li').eq(0);
            var h = $li.height();
            var l = $('li', $ul).length;
            $ul.append($('li', $ul).eq(0).clone());
            var i = 0;
            setInterval(function() {
                i++;
                if (i > l) {
                i = 1;
                $ul.css('top', 0);
                }
                $ul.animate({
                top: -h * i
                })
            }, 3000)
            $("#draw,#image_gift").click(function(){
            if(buttonFlag == 0) {
                buttonFlag = 1;
                $("img").removeClass("shake");
                $("img").addClass("scale");
                var url = "<?php echo $this->absBaseUrl; ?>/credit-info/get-christmas-coupon";
                // ajax请求
                $.ajax({
                    type: "post",
                    url: url,
                    dataType: "json",
                    success: function (data) {
                        buttonFlag = 0;
                       if (data.code == 0) {
                            var item = data.data.item;
                            var message = data.message;
                            var $dialog = $(".dialog");
                            var $content = $("h2",$dialog);
                            var $a_content = $('#button_a');
                            $a_content.html('朕知道了');
                            var html = "<br>请至 <b>“我的” - “我的优惠”</b> 中查收";
                            //$dialog.addClass("success");
                            switch (item) {
                                case 1:
                                    $content.html("<span>恭喜您获得了<b>"+message+"</b>一张" + html + "</span>");
                                    var show_html = '恭喜您获得了<b>'+message+'</b>一张';
                                    break;
                                case 2:
                                    $content.html("<span>恭喜您获得了<b>"+message+"</b>一张" + html + "</span>");
                                    var show_html = '恭喜您获得了<b>'+message+'</b>一张';
                                    break;
                                case 3:
                                    $content.html("<span>恭喜您获得了<b>"+message+"</b>一张" + html + "</span>");
                                    var show_html = '恭喜您获得了<b>'+message+'</b>一张';
                                    break;
                                case 6:
                                    $content.html("<span>"+message+"<br>请至 <b>“我的” - “总额度”</b> 中查收</span>");
                                    var show_html = message;
                                    break;
                                case 5:
                                    $content.html("<span>恭喜您获得了<b>"+message+"</b>" + html + "</span>");
                                    var show_html = '恭喜您获得了<b>'+message+'</b>一张';
                                    break;
                                default:
                                    break;
                            }
                            $(".popup").css("display","block");
                            $(".overlay, .close").click(function(){
                              	$(".popup").css("display","none");
                                $('#show_draw_detail').html(show_html);  
                             });
                            $("#button_a").click(function () {
                                $(".popup").css("display","none"); 
                                $('#show_draw_detail').html(show_html);   
                            });
                        } else if (data.code === -2) {
                            // 提示信息并且跳转
                            //buttonFlag = 0;
                            if (data.data.is_app === 1) {
                                // 用户版本号为最新
                                $(".popup").show();
                                var $dialog = $(".dialog");
                                var $content = $("h2", $dialog);
                                var $a_content = $('#button_a');
                                //$dialog.removeClass("success");
                                //$("img", $dialog).hide();
                                $content.html("<span>快去登录，马上来抽奖</span>");
                                $a_content.html('马上登录');
                                // 处理操作
                                $("#button_a").click(function (e) {
                                    var flag_ver = data.data.is_ver;
                                    if (flag_ver === 1) {
                                        //$("#button_a").click(function (e) {
                                            e.preventDefault();
                                            nativeMethod.returnNativeMethod('{"type":"4"}');
                                       // });
                                    } else {
                                        if (browser.versions.ios === true) {
                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                            window.location.href = "<?= $down_url ?>";
                                        } else if (browser.versions.android === true) {
                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                        } else {
                                            $("#button_a").attr("href", "<?= $down_url ?>");
                                            window.location.href = "<?= $down_url ?>";
                                        }
                                    }
                                })
                            } else {
                                // 其他登录
                                $(".popup").show();
                                var $dialog = $(".dialog");
                                var $content = $("h2", $dialog);
                                var $a_content = $('#button_a');
                                //$dialog.removeClass("success");
                                //$("img", $dialog).hide();
                                $content.html("<span>快去登录，马上来抽奖</span>");
                                $a_content.html('马上登录');
                                // 处理操作
                                $("#button_a").click(function (e) {
                                    if (browser.versions.ios === true) {
                                        $("#button_a").attr("href", "<?= $down_url ?>");
                                        window.location.href = "<?= $down_url ?>";
                                    } else if (browser.versions.android === true) {
                                        $("#button_a").attr("href", "<?= $down_url ?>");
                                    } else {
                                        $("#button_a").attr("href", "<?= $down_url ?>");
                                        window.location.href = "<?= $down_url ?>";
                                    }
                                });
                            }
                        }else if(data.code==-3){//未借款成功，跳转首页进行借款
                            $(".popup").css("display","block");
                            $("#button_a").click(function (e) {
                                e.preventDefault();
                                nativeMethod.returnNativeMethod('{"type":"4"}');
                            }); 
                        }else {
                            // 提示信息
                            $(".popup").show();
                            var $dialog = $(".dialog");
                            var $content = $("h2",$dialog);
                            $content.html("<span>" + data.message + "</span>");
                            var $a_content = $('#button_a');
                            $a_content.html('朕知道了');
                            $("#button_a").click(function (e) {
                                $(".popup").css("display","none");
                            });
                        }
                    }
                });
            }
          });
          $(".overlay, .close").click(function(){
          	$(".popup").css("display","none");
          });
	   });
	</script>
</body>

</html>
