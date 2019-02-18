/**
 *
 *  使用前先引用jquery文件
 *
 */
var u = navigator.userAgent;
window.browser = {};
window.browser.iPhone = u.indexOf('iPhone') > -1; //iPhone or QQHD
window.browser.android = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1;//android or uc
window.browser.ipad = u.indexOf('iPad') > -1;
window.browser.isclient = u.indexOf('lyWb') > -1;
window.browser.ios = u.match(/Mac OS/); //ios
window.browser.width = window.innerWidth;
window.browser.height = window.innerHeight;
window.browser.wx = u.match(/MicroMessenger/);
window.browser.pc = isPC();
window.source_tag = getQueryString('source_tag') ? getQueryString('source_tag') : 'wap';
window.urlList = {};
window.urlList.home_url = isIP(location.host) ? location.origin+'/kdkj/frontend/web/' : location.origin+'/';
if( window.localStorage.getItem("curUrl") != window.location.href ){
    window.localStorage.setItem("sourceUrl",window.localStorage.getItem("curUrl"));
}
window.localStorage.setItem("curUrl",window.location.href);
window.urlList.cur_url = window.localStorage.getItem("curUrl");
window.urlList.source_url = window.localStorage.getItem("sourceUrl");

$(document).ready(function(){
    Initialization();
});
$(window).resize(function(){
    Initialization();
});
function Initialization(){
    fontSize();
    isOneScreen();
}

/*
 *获取来源URL
*/
function getSourceUrl(){
    return document.referrer ? document.referrer : (window.urlList.source_url != 'null' ? window.urlList.source_url : window.urlList.home_url);
}
//百度统计
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "//hm.baidu.com/hm.js?3b60c1a2a4af28a2c4eff40da370fef9";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
//拨打电话
function callPhoneMehtod(phone){
    if (window.browser.android) {
        // window.JavaMethod.callPhoneMethod(phone);
        jumpTo("tel:" + phone);
    }else{
        jumpTo("tel:" + phone);
    }
}
//APP下载
function downLoad() {
    if (window.browser.iPhone || window.browser.ipad || window.browser.ios) {
        iosDownload();
    } else {
        androidDownload();
    }
}
function iosDownload() {
    if (!window.browser.wx){
        jumpTo("https://itunes.apple.com/cn/app/id953061503?mt=8");
    }else{
        jumpTo("http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai");
    }
}
function androidDownload() {
    if (!window.browser.wx){
        if(window.source_tag){
            jumpTo("http://www.koudailc.com/attachment/download/koudailicai_"+window.source_tag+".apk");
        }else{
            jumpTo("http://www.koudailc.com/attachment/download/koudailicai.apk");
        }
    }else{
        jumpTo("http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai");
    }
}
//三步一起
function toRealVerify(){
    hideExDialog();
    return jumpTo("koudaikj://app.launch/auth/userauth");
}
function toBindCard(){
    hideExDialog();
    return jumpTo("koudaikj://app.launch/auth/userauth");
}
function toSetPayPwd(){
    hideExDialog();
    return jumpTo("koudaikj://app.launch/auth/userauth");
}
function sharePacket() {
    showExDialog('<p class="lh_em_1_5 em__9">请使用微信的分享功能，分享页面给好友</p>','我知道了');
}
function qunJoin(){
    var qq = '271377528';
    var html = '';
    html += '<p class="lh_em_2_5 _000">加入夺宝群，分享夺宝心得</p>';
    html += '<p class="m_center lh_em_2_5"><span class="_999">QQ群号：<i class="fd5353">'+qq+'</i>&nbsp;&nbsp;</span></p>';
    html += '<img src="'+window.urlList.home_url+'image/indiana/QQqun.jpg?v=2016042701" alt="" width="70%">';
    html += '<p class="_999 lh_em_1_5">微信内长按以识别二维码</p>';
    if(!window.browser.android){
    html += '<p style="margin-bottom: -5%;"><a class="_00a0e9 em__9" href="http://jq.qq.com/?_wv=1027&k=2IYNedj">一键加群</a></p>';
    }
    showExDialog(html,'取消');
    $(".kdlc_mobile_wraper #exception_dialog").css({'top':'5%'});
}
function WeChatJoin(){
    var html = '';
    html += '<img src="'+window.urlList.home_url+'image/indiana/WeChat.jpg?v=2016042701" alt="" width="75%">';
    html += '<p class="_000 lh_em_2">微信内长按以识别二维码</p>';
    html += '<p class="_000 lh_em_1_5">也可以微信内手动搜索<br/>‘口袋趣购’</p>';
    showExDialog(html,'取消');
    $(".kdlc_mobile_wraper #exception_dialog").css({'top':'5%'});
}

/**
 *===================校对交易密码begin=============
*/
window.tradepwd = {};
window.tradepwd.maxLen = 6;
window.tradepwd.sign = '';
window.tradepwd.callBackFun = function(res){};
function toCheckTradePwd(content,title,guidetips){
    content = content ? content : '';
    title = title ? title : '请输入交易密码';
    guidetips = guidetips ? guidetips : '确认分期';
    hideExDialog();
    var html = '';
        html += '<p class="lh_em_2_5 em_1">'+title+'</p>';
        html += '<p class="_bdr_ededed"></p>';
        html += content;
        html += '<input class="o_hidden zero_opacity" id="trade_pwd_temp" style="position:absolute;top:-9999px;" type="password" maxlength="'+window.tradepwd.maxLen+'" oninput="actionTradePwdTemp(this);" value=""/>';
        html += '<div id="trade_pwd_wraper">';
        for (var i = 1; i <= window.tradepwd.maxLen; i++) {
            html += '<input onfocus="showKeyBord()" class="trade_pwd lh_em_1_2 em_2 a_center _box_sizing" type="password" maxlength="1" oninput="justInt(this);" value=""/>';
        };
        html += '</div>';
        html += '<p class="lh_em_3 em_1 fd5353">'+guidetips+'</p>';
    showExDialog(html);
    $(".kdlc_mobile_wraper #exception_dialog").prepend('<img onclick="hideExDialog();" id="close_mask" width="6.5%" style="position:absolute;right:5px;top:5px;" src="http://res.koudailc.com/article/20160311/856e2ba992482d.png">');
    $(".kdlc_mobile_wraper #exception_dialog_tips").css({'margin':'auto'});
    $(".kdlc_mobile_wraper #trade_pwd_wraper").css({'margin-top':'.5em','border':'1px solid #ededed'});
    $(".kdlc_mobile_wraper .trade_pwd").css({'width':'16.6%','border-left':'1px solid #ededed'});
    $(".kdlc_mobile_wraper .trade_pwd:first-child").css({'border-left':'0 none'});
    showKeyBord();
}
function showKeyBord(){
    $("input#trade_pwd_temp").trigger('focus');
}
function actionTradePwdTemp(obj){
    justInt(obj);
    var pwd = $(obj).val();
    for (var i = 1; i <= window.tradepwd.maxLen; i++) {
        $("#trade_pwd_wraper .trade_pwd:nth-child("+i+")").val(pwd.charAt(i-1));
    };
    if( pwd.length == window.tradepwd.maxLen ){
        $("input#trade_pwd_temp").attr("disabled","disabled");
        drawCircle();
        var url = window.urlList.home_url + 'installment-shop/check-trade-pwd-post';
        $.post(url,{'password':pwd},function(res){
            hideCircle();
            window.tradepwd.sign = res.sign;
            window.tradepwd.callBackFun(res);
        });
    }
}
/**
*===================校对交易密码end=============
*/
