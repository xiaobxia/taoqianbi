/**
 *
 *  使用前先引用jquery文件
 *
 */
$(document).ready(function(){
    var u = navigator.userAgent;
    window.browser = {};
    window.browser.iPhone = u.indexOf('iPhone') > -1;
    window.browser.android = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1;//android or uc
    window.browser.ipad = u.indexOf('iPad') > -1;
    window.browser.isclient = u.indexOf('lyWb') > -1;
    window.browser.ios = u.match(/Mac OS/); //ios
    window.browser.width = window.innerWidth;
    window.browser.height = window.innerHeight;
    window.browser.wx = u.match(/MicroMessenger/);
    window.source_tag = getQueryString('source_tag') ? getQueryString('source_tag') : 'wap';
});
//获取url参数的值
function getQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
}
//客服电话
function callPhoneMehtod(phone){
    if (window.browser.android) {
        // window.JavaMethod.callPhoneMethod(phone);
        window.location = "tel:" + phone;
    }else{
        window.location = "tel:" + phone;
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
        window.location.href = "https://itunes.apple.com/cn/app/id953061503?mt=8";
        // window.location.href = "itms-services://?action=download-manifest&url=https://app.irongbao.com/iosdown/koudai/koudai.plist";
    }else{
        //alert('请点击右上角按钮选择在Safari浏览器中打开并下载！');
        //window.location.href = "http://mp.weixin.qq.com/mp/redirect?url=https%3A%2F%2Fitunes.apple.com%2Fcn%2Fapp%2Fid953061503%3Fmt%3D8";
        window.location.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai";
    }
}
function androidDownload() {
    if (!window.browser.wx){
        if(window.source_tag){
            window.location.href = "https://www.xianjincard.com/attachment/download/koudailicai_"+window.source_tag+".apk";
        }else{
            window.location.href = "https://www.xianjincard.com/attachment/download/koudailicai.apk";
        }
    }else{
        // 后面换成应用宝地址
        // alert('请点击右上角按钮选择在浏览器中打开并下载！');
        // window.location.href = "http://dd.myapp.com/16891/DFF6B3C86CBA8866735935E325F8789B.apk?mkey=54b79534f6aac181&f=178a&fsname=com.kdkj.koudailicai1.1.02.apk&asr=8eff&p=.apk";
        window.location.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai";
    }
}
//根据屏幕调整大小
function fontSize(){
    $(document.body).css("font-size",$(document.body).width() / 480*120 + '%');
}
//小于一屏以一屏显示
function isOneScreen(){
    if( $(document.body).outerHeight(true) < $(window).innerHeight() ){
        $(document.body).height($(window).innerHeight() + 'px');
    }
}
//显示遮罩层
function showExDialog(tips,btn1,func1,btn2,func2){
    if( arguments[2] ){
        func1 = arguments[2];
    }else{
        func1 = 'hideExDialog';
    }
    if( arguments[4] ){
        func2 = arguments[4];
    }else{
        func2 = 'hideExDialog';
    }
    var str = '';
    str += '<div id="mask"></div>';
    str += '<div id="exception_dialog">';
    str += '<div class="a_center" id="exception_dialog_tips">'+tips+'</div>';
    if(arguments[1]) {
        str += '<div class="_inline_block exception_dialog_btn" onclick="'+func1+'();">'+btn1+'</div>';
    }
    if(arguments[3]) {
        str += '<div class="_inline_block exception_dialog_btn" onclick="'+func2+'();">'+btn2+'</div>';
    }
    str += '</div></div>';
    $(".kdlc_mobile_wraper > div").append(str);
    if( !arguments[3] && !arguments[4] ){
        $(".exception_dialog_btn").width("80%");
    }
}
//隐藏遮罩层
function hideExDialog(){
    $("#mask,#exception_dialog").remove();
}

//判断客户端是否为 PC 还是手持设备
function IsPC() {
    var userAgentInfo = navigator.userAgent;
    var Agents = ["Android", "iPhone",
                "SymbianOS", "Windows Phone",
                "iPad", "iPod"];
    var flag = true;
    for (var v = 0; v < Agents.length; v++) {
        if (userAgentInfo.indexOf(Agents[v]) > 0) {
            flag = false;
            break;
        }
    }
    return flag;
}
/**
 * 调用原生客户端-复制信息
 * @param text
 * @returns
 */
function copyText(text){
    return nativeMethod.copyTextMethod('{"text":"'+text+'","tip":"复制成功!"}');
}
/**
 * 调用原生客户端-返回原生页
 * @returns
 * 0:返回原生
1.忘记密码
2.忘记交易密码
3.认证页面
4.借款首页
100.聚信立下一步原生(小钱包)
 */
function returnNative(type){
    type = type || 0;
    return nativeMethod.returnNativeMethod('{"type":"'+type+'"}');
}
/**
 * 调用原生分享
 * @param title 分享标题
 * @param body 分享内容
 * @param url 分享url
 * @param logo 分享logo
 * @param type 0|1，0直接分享，1右上角出现分享按钮
 * @returns
 */
function nativeShare(title,body,url,logo,type){
    var type = type == 1 ? 1 : 0;
    return nativeMethod.shareMethod('{"share_title":"'+title+'","share_body":"'+body+'","share_url":"'+url+'","share_logo":"'+logo+'","type":"'+type+'"}');
}
//复制网页文字
function copyToClipboard(maintext){
    if (window.clipboardData){
        window.clipboardData.setData("Text", maintext);
    }else if (window.netscape){
        try{
            netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
        }catch(e){
            alert("该浏览器不支持一键复制！\n请手工复制文本框链接地址～");
        }
        var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
        if (!clip) return;
        var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
        if (!trans) return;
        trans.addDataFlavor('text/unicode');
        var str = new Object();
        var len = new Object();
        var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
        var copytext=maintext;
        str.data=copytext;
        trans.setTransferData("text/unicode",str,copytext.length*2);
        var clipid=Components.interfaces.nsIClipboard;
        if (!clip) return false;
            clip.setData(trans,null,clipid.kGlobalClipboard);
    }
    alert("以下内容已经复制到剪贴板\r\n" + maintext);
}

/**
 * 不足补零
 * @param obj
 * @returns {*}
 * @constructor
 */
function Appendzero (obj) {
    if (obj < 10) return "0" + obj; else return obj;
}

/**
 * 时间戳转换日期
 * @param <int> unixTime    待时间戳(秒)
 * @param <bool> isFull    返回完整时间(Y-m-d 或者 Y-m-d H:i:s)
 * @param <int>  timeZone   时区
 */
function UnixToDate(unixTime, isFull, isSeconds, timeZone) {
    if (typeof (timeZone) == 'number')
    {
        unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
    }
    var time = new Date(unixTime * 1000);
    var ymdhis = "";
    ymdhis += time.getFullYear() + "-";
    ymdhis += Appendzero( (time.getMonth()+1) ) + "-";
    ymdhis += Appendzero( time.getDate() );
    if (isFull === true)
    {
        ymdhis += " " + time.getHours() + ":";
        ymdhis += Appendzero(time.getMinutes());
        if (isSeconds === false){
            return ymdhis;
        }
        ymdhis += ":" + Appendzero(time.getSeconds());

    }
    return ymdhis;
}

//只能输入数字 eg: onkeyup事件
function JustInt(e)
{
    if(e.value.length==1){
        e.value=e.value.replace(/[^\d]/g,'');
    }else{
        e.value=e.value.replace(/\D/g,'');
    }
}

//只能输入数字 eg: onkeyup事件
function JustFloat(e)
{
    e.value=e.value.replace(/[^\d+(\.\d+)?$]/g,'');
}

var KD = {};
KD.util = {};
KD.util.post = function(url, data, okfn, onfn) {
    KD.util.post.pIndex = (KD.util.post.pIndex || 0) + 1;
    var iframe = $('<iframe name="pIframe_'+KD.util.post.pIndex+'" src="about:blank" style="display:none" width="0" height="0" scrolling="no" allowtransparency="true" frameborder="0"></iframe>').appendTo($(document.body));
    var isExe = false;
    var ipts = [];
    $.each(data, function(k, v){
        ipts.push('<input type="hidden" name="'+k+'" value="" />');
    });
    
    if(!/(\?|&(amp;)?)fmt=[^0 &]+/.test(url)) url += (url.indexOf('?') > 0 ? '&' : '?') + 'fmt=1';

    var form = $('<form action="'+url+'" method="post" target="pIframe_'+KD.util.post.pIndex+'">'+ipts.join('')+'</form>').appendTo($(document.body));

    $.each(data, function(k, v){
        form.children('[name='+k+']').val(v);
    });

    iframe[0].onload = function(){
        if(!isExe){
            if(typeof onfn == 'function') onfn();
        }
        $(this).src = 'about:blank';
        $(this).remove();
        form.remove();
        iframe = form = null;
    };

    iframe[0].callback = function(o){
        if(typeof okfn == 'function') okfn(o);
        isExe = true;
    };
    if(false && $.browser.msie && $.browser.version == 6.0){ // 暂不考虑ie6，且$.browser还不行
        iframe[0].pIndex = KD.util.post.pIndex;
        iframe[0].ie6callback = function(){
            form.target = 'pIframe_' + this.pIndex;
            form.submit();
        };
        iframe[0].src = location.protocol + '//m.kdqugou.com/html/ie6post.html';
    } else {
        form.submit();
    }
};

//获取ID
function ID(id) {
    return !id ? null : document.getElementById(id);
}

/**
*@param string eventType 事件类型
*@param string trackId 对象ID
*@param string activityId 活动页标识
*
*/
function activePageTracks(eventType, trackId, activityId) {
    trackId = ID(''+trackId+'');
    if (trackId) {
        trackId[eventType] = function(){
            var params = {
                event_type : eventType,
                current_url : window.location.href,
                source_url : document.referrer,
                source_tag : window.source_tag,
                avtivity_id : activityId
            };
            KD.util.post(TRACKS_TARGET_URL, params, function(data){});
        }
    }
}

function jumpTo(url){
    window.location.href = url;
}

/**
*===================倒计时=============
*/
function display(stamp){
    stamp = stamp*1000;
    rtime = etime-ctime+stamp;
    if (rtime>60000){
        m = parseInt(rtime/60000);
    }else{
        m = 0;
    }
    if (rtime>1000){
        s = parseInt(rtime/1000-m*60);
    }else{
        s = 0;
    }
    ms = parseInt((rtime-m*60*1000-s*1000)/10);
    if( ms < 0 ){
        ms = -1;
       return false;
    }
    return m+":"+((s<10) ? "0"+s : s)+"."+((ms<10) ? "0"+ms : ms);
}

function settimes(stamp){
    var time = new Date();
    hours = time.getHours() == 0 ? 24 : time.getHours();
    mins = time.getMinutes();
    secs = time.getSeconds();
    milsecs = time.getMilliseconds();
    etime = (hours*3600+mins*60+secs)*1000+milsecs;
    etime += stamp*1000;//时间段设计
    checktime();
}

function checktime(){
    var time = new Date();
    hours = time.getHours() == 0 ? 24 : time.getHours();
    mins = time.getMinutes();
    secs = time.getSeconds();
    milsecs = time.getMilliseconds();
    ctime = (hours*3600+mins*60+secs)*1000+milsecs;
    if( countDown() ){
        window.timer = window.setTimeout("checktime()",10);
    }
}
//使用
// settimes(0);
function countDown(){
    return true;
}
/*
下载悬浮
自定义图片路径 imgSrc
 */
$(function() {
    var u = navigator.userAgent;
    if (u.indexOf('KDLC') > -1) {
        return;
    }

    var url = (function() {
        
        var isAndroid = u.indexOf("Android") > -1 || u.indexOf("Adr") > -1,
            isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/),
            isWeixin = (/micromessenger/i).test(u),
            isQQ = (/QQBrowser/i).test(u),
            sourceTag = getQueryString('source_tag');

        if (isAndroid) {
            if (!isWeixin) {
                if (sourceTag) {
                    return 'http://www.kdqugou.com/attachment/download/koudailicai_' + sourceTag + '.apk';
                } else {
                    return "http://www.kdqugou.com/attachment/download/koudailicai.apk";
                }
            } else {
                return "http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai";
            }
        }
        if (isiOS) {
            if (isWeixin) {
                return "http://a.app.qq.com/o/simple.jsp?pkgname=com.kdkj.koudailicai";
            } else {
                return "https://itunes.apple.com/cn/app/id953061503?mt=8";
            }
        }
    }())


    var download = function(e) {
        e.preventDefault()
        if (url) {
            window.location.href = url;
        }
    }

    $.fn.extend({
        downloadApp: function(u) {
            if (url) {
                $(this).on('click', function(e) {
                    e.preventDefault();
                    window.location.href = u;
                })
            } else {
                $(this).on('click', download);
            }
        }
    })

    var src = $('#download-show').attr('data-src') || false;

    if (src) {
        var $tmp = $('<div class="shareDownload" style="box-sizing:border-box;position:fixed;left:0;bottom:0;width:100%;padding:0 15px;background-color:rgba(0,0,0,.75);z-index:999;font-size:20px;"><a href="javascript:void(0);" class="downloadImg"><img src="' + src + '" style="display:block;padding:0.75em 0;max-width:80%;height:auto;"></a><a href="javascript:void(0)" style="display:block;position: absolute;top:0.5em;right:0.5em;width:0.4em;height:0.4em;background:url(../image/page/close_small.png) no-repeat;background-size:cover;" class="downloadClose"></a></div>');
        $tmp.find('.downloadImg').on('click', download)
        $tmp.find('.downloadClose').on('click', function(e) {
            e.preventDefault()
            $tmp.hide();
        });
        $tmp.appendTo('body');
    } else {
        $('.download-button').downloadApp();
    }
});

/**
 * 表单提交
 * @param URL
 * @param PARAMS
 * @param METHOD
 */
function formPost(URL, PARAMS, METHOD){
    var temp = document.createElement("form");
    temp.action = URL;
    temp.method = METHOD || 'POST';
    temp.style.display = "none";
    for (var x in PARAMS){
        var opt = document.createElement("textarea");
        opt.name = x;
        opt.value = PARAMS[x];
        temp.appendChild(opt);
    }
    document.body.appendChild(temp);
    temp.submit();
    return temp;
}
/*
 *判断是否是手机号码
*/
function isPhone(num){
    var reg = /^[1]\d{10}$/;
    return reg.test(num);
}
/*
 *判断是否是银行卡
*/
function isCardNo(num){
    var reg = /^(\d{16})$|^(\d{19})$/;
    return reg.test(num);
}