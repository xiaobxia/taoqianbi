var u = navigator.userAgent;
window.browser = {};
window.browser.iPhone = u.indexOf('iPhone') > -1; //iPhone or QQHD
window.browser.android = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1; //android or uc
window.browser.ipad = u.indexOf('iPad') > -1;
window.browser.isclient = u.indexOf('lyWb') > -1;
window.browser.ios = u.match(/Mac OS/); //ios
window.browser.wx = u.match(/MicroMessenger/);
window.browser.qq = u.match(/QQBrowser/);
window.browser.pc = isPC();
window.browser.app = u.indexOf('xqb') > -1;
window.urlList = {};
window.urlList.homeUrl = location.origin + (isIP(location.host) ? '/xqb' : '') + '/newh5/web';
getQueryString('institution_id') && window.localStorage.setItem("source_tag", getQueryString('institution_id'));
getQueryString('source_tag') && window.localStorage.setItem("source_tag", getQueryString('source_tag'));
window.source_tag = localStorage.source_tag ? localStorage.source_tag : '';
Initialization();
function Initialization() {
    // fontSize();
    // baiduVisitStat("//hm.baidu.com/hm.js?2d7ead83d6647b772a8a6c0661d68240");
}

//页面访问统计
function webVisitStat(remark) {
    remark = remark || '';
    var url = window.urlList.homeUrl + '/page/visit-stat';
    var params = {
        source_url: getSourceUrl(),
        current_url: location.href,
        source_tag: window.source_tag,
        remark: remark
    };
    $.post(url, params, function (data) {
        if (data.code == 0) {
            console.log(data.message);
        } else {
            console.log(data.message || '请求失败');
        }
    });
}

//百度统计
function baiduClickStat(obj, eventType) {
    eventType = eventType || 'onclick';
    optLabel = obj ? (obj.innerHTML ? obj.innerHTML : obj.title) : '';
    var params = ['_trackEvent', location.href, eventType, optLabel];
    window._hmt && window._hmt.push(params);
}

//百度统计
function baiduVisitStat(src) {
    var _hmt = _hmt || [];
    (function () {
        var hm = document.createElement("script");
        hm.src = src;
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(hm, s);
    })();
}

//APP下载
function downLoad(obj, tag) {
    if (window.browser.app) {
        return;
    }
    if (window.browser.iPhone || window.browser.ipad || window.browser.ios) {
        iosDownload(obj);
    } else {
        androidDownload(obj, tag);
    }
}

function iosDownload(obj) {
    var download_url = "";
    if (obj == 'xybt') {
        download_url = "http://itunes.apple.com/app/id1235438496?mt=8"; // 极速荷包 TODO clark id1221186366
    } else if (obj == 'hbqb') {
        download_url = "http://itunes.apple.com/app/id1235438496?mt=8"; // 汇邦钱包 TODO clark id1221186366
    } else if (obj == 'wzdai_loan') {
        download_url = "http://itunes.apple.com/app/id1239756949"; // 温州贷借款
    } else if (obj == 'xybt_fund') {
        download_url = "http://itunes.apple.com/app/id1248726833?mt=8"; // 极速荷包公积金版
    } else if (obj == 'xybt_fuli') {
        download_url = "http://itunes.apple.com/app/id1235438496?mt=8"; // 极速荷包福利版
    } else if (obj == 'sxdai') {
        download_url = "http://itunes.apple.com/app/id1251292028?mt=8"; // 随心贷
    }
    if (window.browser.wx) {
        wxDownload(obj);
    } else {
        jumpTo(download_url);
    }
}

function androidDownload(obj, tag) {
    if (window.browser.wx) {
        return wxDownload(obj);
    }
    tag = tag || window.source_tag;

    var dft_apk = "";
    var tag_apk = "";
    if (obj == 'xybt') { // 极速荷包
        dft_apk = "http://qbres.wzdai.com/apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/apk/" + obj + "-" + tag + ".apk";
    } else if (obj == 'hbqb') { // 汇邦钱包
        dft_apk = "http://qbres.wzdai.com/hbqb_apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/hbqb_apk/" + obj + "-" + tag + ".apk";
    } else if (obj == 'wzdai_loan') { // 温州贷借款
        dft_apk = "http://qbres.wzdai.com/wzdai_apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/wzdai_apk/" + obj + "-" + tag + ".apk";
    } else if (obj == 'xybt_fund') { // 极速荷包公积金版
        dft_apk = "http://qbres.wzdai.com/xybt_fund_apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/xybt_fund_apk/" + obj + "-" + tag + ".apk";
    } else if (obj == 'xybt_fuli') { // 极速荷包福利版
        dft_apk = "http://qbres.wzdai.com/xybt_fuli_apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/xybt_fuli_apk/" + obj + "-" + tag + ".apk";
    } else if (obj == 'sxdai') { // 随心贷
        dft_apk = "http://qbres.wzdai.com/sxdai_apk/" + obj + "-latest.apk";
        tag_apk = "http://qbres.wzdai.com/sxdai_apk/" + obj + "-" + tag + ".apk";
    }
    $.ajax({
        url: tag_apk,
        type: 'HEAD',
        error: function () {
            return jumpTo(dft_apk);
        },
        success: function () {
            return jumpTo(tag_apk);
        }
    });
}
// 微信内下载引导到浏览器
function showWxDown(obj) {
    hideExDialog();
    wxDownload(obj);
}

function wxDownload(obj) {
    hideExDialog('wxDownload');
    showMask('wxDownload', '#000', '60');
    var src = window.urlList.homeUrl + '/image/page/wx_download.png?v=2017032001';
    $(".wxDownload").attr("onclick", "hideExDialog('wxDownload');");
    $(".wxDownload").css({
        'background': '#000 url(' + src + ') no-repeat top center',
        'background-size': '100%'
    });
    // --lyw xybt微信界面直接前往应用宝市场下载 安卓ios通用--
   var a = document.createElement('a')
    switch(obj){
        case '':
            a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
            break;
         case 'xybt':
            a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
            break;
         case 'sxdai':
            a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.xybt.sxdai";
            break;
        default:
            a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
            break;
    }

   a.click();
}

function showSoft() {
    hideExDialog('showSoft');
    showMask('showSoft', '#000', '100');
    var src = window.urlList.homeUrl + '/image/common/Soft.jpg?v=2017032001';
    $(".showSoft").attr("onclick", "hideExDialog('showSoft');");
    $(".showSoft").css({
        'background': '#000 url(' + src + ') no-repeat center center',
        'background-size': '100%'
    });
}

/*---------- 注册流程封装 -----------------------------------------------------------------*/

/**
 * 获取验证码倒计时
 * @params btnTips1 默认 获取
 * @params btnTips2 默认 num秒
 * @params eleId 默认 action
 * @params fun 默认 getCode
 */
function getCodeCountDown(btnTips1, btnTips2, eleId, fun) {
    btnTips1 = btnTips1 || '获取';
    btnTips2 = btnTips2 || 'num秒';
    eleId = eleId || 'autoGetCode';
    fun = fun || 'getCode();';
    var obj = ID(eleId);
    var _div = document.createElement("div");
    var second = document.createElement("i");
    second.id = 'second';
    second.innerHTML = 60;
    _div.appendChild(second);
    obj.innerHTML = strReplace(btnTips2, 'num', _div.innerHTML);
    obj.setAttribute('disabled', 'true'); // 倒计时标签需使用button
    countdown();

    function countdown() {
        var obj1 = ID('second');
        obj1.innerHTML = intval(obj1.innerHTML) - 1;
        obj.style.cursor = 'not-allowed';
        //倒计时结束
        if (obj1.innerHTML <= 0) {
            window.clearInterval(timing);
            obj.innerHTML = btnTips1;
            obj.removeAttribute('disabled'); // 倒计时标签需使用button
            obj.style.cursor = '';
        }
    }
    var timing = window.setInterval(countdown, 1000);
}

/**
 * 获取验证码
 * @params phone 注册手机号
 * @params url post请求地址
 * @params keyvalue post请求参数key的value值
 * @params source 注册信息
 * @params isfromweichat 判断是否来自微信
 * @params pop_params 弹框样式修改
 * @params obj wx页面下载app的名称
 * @params countDownBtnTips2 getCodeCountDown第二个参数btnTips2
 */
function getCode(phone, url, source, keyvalue, isfromweichat, pop_params,obj,countDownBtnTips2) {
    var countDownBtnTips2 = countDownBtnTips2 || '倒计时num秒';
    console.log(phone);
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    $.post(url, {
        phone: phone,
        source_id: source.source_id,
        source_tag: source.source_tag,
        key: keyvalue
    }, function (data) {
        if (data && data.code == 0) {
            getCodeCountDown('获取验证码', countDownBtnTips2);
        } else if (data && data.code == 1000) {
            if (isfromweichat) {
                return showExDialog('您已注册，立即申请借款吧', '打开APP', "showWxDown('"+obj+"')", '', '', '', '', pop_params);
            } else {
                return showExDialog('注册成功，立即申请借款吧', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "')", '', '', '', '', pop_params);
            }
        } else if (data.message) {
            return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
        }
    }, 'json');
}
/**
 * 注册申请
 * @params phone 注册手机号
 * @params code 验证码
 * @params url post请求地址
 * @params source 注册信息
 * @params isfromweichat 判断是否来自微信
 * @params pop_params 弹框样式修改
 * @params obj wx页面下载app的名称
 */
function register(phone, code, url, source, isfromweichat, pop_params,obj,openid) {
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    if (!code) {
        return showExDialog('请输入验证码', '确定', '', '', '', '', '', pop_params);
    }
    $
    url = url.replace('NoneAppMarket', source.source_tag);
    $.post(url, {
        phone: phone,
        code: code,
        source_id: source.source_id,
        source_tag: source.source_tag,
        openid:openid
    }, function (data) {
        if (data && data.code == 0) {
            var isAndroid =  navigator.userAgent.indexOf("Android") > 0
            if (isfromweichat && isAndroid) {
                return showExDialog('注册成功，您的初始登录密码稍后将以短信形式发送给您', '下载APP', "showWxDown('"+obj+"')", '', '', '', '', pop_params);
            } else {
                return showExDialog('注册成功，您的初始登录密码稍后将以短信形式发送给您', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "')", '', '', '', '', pop_params);
            }
        } else if (data.message) {
            return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
        }
    }, 'json');
}
//图片验证码刷新
function refreshCode(refreshurl) {
    $.ajax({
        url: refreshurl,
        dataType: 'json',
        cache: false,
        success: function (data) {
            $("#imgCode").attr('src', data['url']);
            $('#imgCodeIn').val('');
        }
    });
}

/*---------------------落地页轮播信息封装-------------------------*/
// 轮播数据
    /*
    * @params rollbox 轮播图的父盒子
    * @params liMoveNum 每次轮播li的数量
    * @params url 请求li内容地址
    * @params rollParentBox 数据请求异常隐藏的轮播盒子
    */
    function rollShowData(rollbox, liMoveNum, url, rollParentBox){
        var $ul = $("<ul></ul>");
        $(rollbox).append($ul);
        $.get(url,function(data){
            if(data && data.code == 0){
                for(var i = 0; i < data.message.length; i++){
                    var $li = $('<li></li>');
                    $li.text(data.message[i]);
                    $ul.append($li);
                }
                $ul.append($ul.children().clone(true));
                setInterval(function(){
                    autoRoll($ul, liMoveNum);
                }, 2500);
            }
            if(data.code == -1){
                // 设置隐藏
                $(rollParentBox).hide();
            }
        });
    }
    // 轮播动画
    /*
    * @params ul 轮播ul
    * @params liMoveNum 每次轮播li的数量
    */
    function autoRoll(ul, liMoveNum){
        var liHeight = ul.children("li:first").outerHeight();
        ul.animate({
            marginTop: "-"+liHeight*liMoveNum+"px"
        },
        500,
        "swing",
        function() {
            $(this).css({
                marginTop: "0"
            }).children("li:lt("+liMoveNum+")").appendTo(this);
        });
    }
    /**
     * 显示提示弹窗
     * @params content 弹窗内容
     * @params contentParams content样式对象
     * @params btnParams btn样式对象
     * @params btnl 左按钮内容 默认"稍后操作"，关闭功能
     * @params funl 左按钮事件
     * @params btnr 右按钮内容 默认"极速借款"
     * @params funr 右按钮事件
     * @params type 弹窗类型(对象) {"btn":"oneclose", //  "oneclose"-无抽奖机会
     *                            "title":"prize",          //"prize"-中奖
     *                            "voucherClass":"yuan2" }   //"voucherClass"-不同奖品的类名
    */
    function turntableDialog(content,contentParams,btnParams,btnl,funl,btnr,funr,type){
        contentParams = contentParams || {'text-align':'center'};
        btnParams = btnParams || {'background-color':'#fff'};
        btnl = btnl || '稍后操作';
        funl = funl || 'hideExDialog("turntable-dialog")';
        btnr = btnr || '极速借款';
        type = type || {};

        showMask('turntable-dialog','#000','50'); //显示遮罩层

        var $turntableDialog = $("<div></div>");
        $turntableDialog.addClass('turntable-dialog').attr("id","dialog-wraper");

        var $title = $("<div></div>");
        var $content = $("<div></div>");
        if(type.title && type.title == 'prize'){
            $title.addClass('title').attr("id","prize-title").appendTo($turntableDialog);

            $content.addClass('content').css(contentParams).html(content+"<div class='voucher "+type.voucherClass+"'></div><p style='color:#497082'>前往 “我的-我的优惠”查看</p>");
            $turntableDialog.css("top",'0');
        }else{
            $title.addClass('title').attr("id","turntable-title").appendTo($turntableDialog);
            $content.addClass('content').css(contentParams).html(content);
        }
        $content.appendTo($turntableDialog);

        var $btn = $("<div></div>");
        if(type.btn && type.btn == "oneclose"){
            var $noChance = $("<div></div>");
            $noChance.addClass('noChance').attr("onclick",funl);
            $noChance.appendTo($btn);
            $content.css("border-radius","10px")
        }else {
            var $buttonl = $("<button></button>");
            $buttonl.addClass('fl').css("background-color","#e9d03e").attr("onclick",funl).html(btnl);
            var $buttonr = $("<button></button>");
            $buttonr.addClass('fr').css("background-color","#ff7978").attr("onclick",funr).html(btnr);
            $buttonl.appendTo($btn);
            $buttonr.appendTo($btn);
            $btn.css(btnParams);
        }
        $btn.addClass('btn').appendTo($turntableDialog);

        $turntableDialog.appendTo('body');
    }