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
function downLoad(obj, tag, phone) {
    if (window.browser.app) {
        return;
    }
    if (window.browser.iPhone || window.browser.ipad || window.browser.ios) {
        iosDownload(obj, phone);
    } else {
        androidDownload(obj, tag, phone);
    }
}
//信息流结算下载
function xxl_downLoad(obj, tag, phone) {
    if (window.browser.app) {
        return;
    }
    if (window.browser.iPhone || window.browser.ipad || window.browser.ios) {
        if(typeof _tt_show_type != 'undefined'){
            if (_tt_show_type == 1) {
                _taq.push({ convert_id: _tt_convert_id, event_type: 'form' });
            }
        }
        iosDownload(obj, phone);

    } else {
        if(typeof _tt_show_type != 'undefined'){
            if (_tt_show_type == 1) {
                _taq.push({ convert_id: _tt_convert_id, event_type: 'form' });
            }
        }
        androidDownload(obj, tag, phone);
    }
}

function iosDownload(obj, phone) {
    //统计下载
    tjDownApp('ios','init',phone);
    window.location.href = 'https://fir.im/7l4v';
}

function androidDownload(obj, tag, phone) {
    if (window.browser.wx) {
        return wxDownload(obj, phone);
    }

    //统计下载
    tjDownApp('android','init',phone);

    tag = tag || window.source_tag;

    var dft_apk = "";
    var tag_apk = "";
    dft_apk = "https://fir.im/7l4v";
    // tag_apk = "https://sdhb-pro.oss-cn-shanghai.aliyuncs.com/sdhb/apk/sdhb0815_release.apk";
    tag_apk = "https://fir.im/7l4v";
    window.location.href = dft_apk;
}
// 微信内下载引导到浏览器
function showWxDown(obj, phone) {
    hideExDialog();
    wxDownload(obj, phone);
}

function wxDownload(obj, phone) {
    //统计下载
    tjDownApp('weixin','init',phone);
    hideExDialog('wxDownload');
    showMask('wxDownload', '#000', '60');
    var src = window.urlList.homeUrl + '/image/page/wx_download.png?v=2017032001';
    //$(".wxDownload").attr("onclick", "hideExDialog('wxDownload');");
    $(".wxDownload").css({
        'background': '#000 url(' + src + ') no-repeat top center',
        'background-size': '100%'
    });
    // --lyw xybt微信界面直接前往应用宝市场下载 安卓ios通用--
    // var a = document.createElement('a')
    // switch (obj) {
    //     case 'xybt':
    //         a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
    //         break;
    //     case 'sxdai':
    //         a.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.xybt.sxdai";
    //         break;
    // }

    // a.click();
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

//统计下载
function tjDownApp(type,status,phone) {
    if(phone==undefined){
        phone='';
    }
    var url = '/frontend/web/xqb-user/tj-app-down';
    var params = {
        type: type.toString(),
        status: status.toString(),
        phone: phone.toString()
    };
    $.post(url, params, function (data) {});
}

/*---------- 注册流程封装 -----------------------------------------------------------------*/

/**
 * 获取验证码倒计时
 * @params btnTips1 默认 获取
 * @params btnTips2 默认 num秒
 * @params eleId 默认 action
 * @params fun 默认 getCode
 * @params Tcolor 倒计时文字颜色
 */
function getCodeCountDown(btnTips1, btnTips2, eleId, fun, Tcolor) {
    btnTips1 = btnTips1 || '获取';
    btnTips2 = btnTips2 || 'num秒';
    eleId = eleId || 'autoGetCode';
    fun = fun || 'getCode();';
    // Tcolor = Tcolor || 'inherit';
    var obj = ID(eleId);
    var _div = document.createElement("div");
    var second = document.createElement("i");
    second.id = 'second';
    second.innerHTML = 60;
    _div.appendChild(second);
    // obj.style.color = Tcolor;
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
function getCode(phone, url, source, keyvalue, isfromweichat, pop_params, obj, countDownBtnTips2) {
    var countDownBtnTips2 = countDownBtnTips2 || '倒计时num秒';
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    $.ajax({
        type: 'post',
        url: url,
        data: {
            phone: phone,
            source_id: source.source_id,
            source_tag: source.source_tag,
            key: keyvalue
        },
        dataType: 'json',
        success: function (data) {
            if (data && data.code == 0) {
                getCodeCountDown('获取验证码', countDownBtnTips2);
            } else if (data && data.code == 1000) {
                if (isfromweichat == 1) {
                    if (obj && obj == 'xybt_xjbtfuli') {
                        return showWxExDialog('您已注册，立即申请借款吧', '../image/xjbt/xjbt-code.png', '', '', pop_params);
                    } else {
                        return showExDialog('您已注册，立即申请借款吧', '打开APP', "showWxDown('" + obj + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                    }
                } else {
                    return showExDialog('注册成功，立即申请借款吧', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                }
            } else if (data.message) {
                return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
            }
        },
        error: function (xhr, msg) {
            //错误信息发送给后台接口
            console.log('接口请求不通，source_tag：' + source.source_tag + '\n' + 'xhr:' + xhr.readState + '\n' + 'msg:' + msg)
        }

    })
}

/**
    * 获取带图片验证码弹框
    * @params phone 注册手机号
    * @params url post请求地址
    * @params keyvalue post请求参数key的value值
    * @params source 注册信息
    * @params isfromweichat 判断是否来自微信
    * @params pop_params 弹框样式修改
    * @params abnFun 当条件为data.message时 执行的函数
    * @params obj wx页面下载app的名称
    * @params countDownBtnTips2 getCodeCountDown第二个参数btnTips2
    */
function getImgCode(phone, source, keyvalue, isfromweichat, pop_params, imgUrl, checkUrl, obj, countDownBtnTips2) {
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    imgCheck(phone, source, keyvalue, isfromweichat, pop_params, imgUrl, checkUrl, obj, countDownBtnTips2);
}

/**
   * 校验图片获取验证码
   * @params phone 注册手机号
   * @params keyvalue post请求参数key的value值
   * @params source 注册信息
   * @params isfromweichat 判断是否来自微信
   * @params pop_params 弹框样式修改
   * @params obj wx页面下载app的名称
   * @params countDownBtnTips2 getCodeCountDown第二个参数btnTips2
   */
function imgCheck(phone, source, keyvalue, isfromweichat, pop_params, imgUrl, checkUrl, obj, countDownBtnTips2) {
    var create = document.createElement.bind(document);
    var tip = create('p');
    tip.style.cssText = 'color:red;font-size:0.3rem;display:block;';
    $.ajax({
        type: "post",
        url: checkUrl,
        data: {
            code: '',
            phone: phone,
            source_id: source.source_id,
            source_tag: source.source_tag,
            key: keyvalue
        },
        dataType: 'json',
        success: function (data) {
            if (data && data.code == 0) {
                return showExDialog('恭喜您，获取验证码成功，请查收短信！', '确定', '', '', '', '', '', pop_params);
            } else if (data && data.code == 1000) {
                // document.body.removeChild(pop);
                var isAndroid =  navigator.userAgent.indexOf("Android") > 0
                if (isfromweichat == 1 && isAndroid) {
                    if (obj && obj == 'xybt_xjbtfuli') {
                        return showWxExDialog('您已注册，立即申请借款吧', '../image/xjbt/xjbt-code.png', '', '', pop_params);
                    } else {
                        return showExDialog('您已注册，立即申请借款吧', '打开APP', "showWxDown('" + obj + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                    }
                } else {
                    return showExDialog('注册成功，立即申请借款吧', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                }
            } else if (data && data.message) {
                if(data.message.toString()=='手机号已注册'){
                    return showExDialog('您已注册，立即申请借款吧', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                }
                return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
            } else {
                return showExDialog('抱歉，获取短信验证码失败！', '确定', '', '', '', '', '', pop_params);
            }
        },
        error: function () {
            return showExDialog('抱歉，获取短信验证码失败！', '确定', '', '', '', '', '', pop_params);
        }
    });
}

/** 2落地页 图片验证码刷新
 * @params imgUrl 图片请求地址
 */
function refreshImg(imgUrl) {
    $('.img-input img').attr('src', imgUrl + "?" + Math.random())
}

/**
* 含有图片验证码情况获取验证码
* @params phone 注册手机号
* @params keyvalue post请求参数key的value值
* @params source 注册信息
* @params isfromweichat 判断是否来自微信
* @params pop_params 弹框样式修改
* @params imgUrl 图片请求地址
* @params checkUrl 检验图片验证码请求地址
* @params obj wx页面下载app的名称
* @params countDownBtnTips2 getCodeCountDown第二个参数btnTips2
*/
function getImgCode2(phone, source, keyvalue, isfromweichat, pop_params, imgUrl, checkUrl, obj, countDownBtnTips2) {
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    var val = $('#imgCode').val();
    if (!val) {
        return showExDialog('请输入图片验证码', '确定', '', '', '', '', '', pop_params);
    }

    $.ajax({
        type: "post",
        url: checkUrl,
        data: {
            code: val,
            phone: phone,
            source_id: source.source_id,
            source_tag: source.source_tag,
            key: keyvalue
        },
        dataType: 'json',
        success: function (data) {
            if (data && data.code == 0) {
                getCodeCountDown('获取验证码', countDownBtnTips2);
            } else if (data && data.code == 1000) {
                if (isfromweichat == 1) {
                    if (obj && obj == 'xybt_xjbtfuli') {
                        return showWxExDialog('您已注册，立即申请借款吧', '../image/xjbt/xjbt-code.png', '', '', pop_params);
                    } else {
                        return showExDialog('您已注册，立即申请借款吧', '打开APP', "showWxDown('" + obj + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                    }
                } else {
                    return showExDialog('注册成功，立即申请借款吧', '下载APP', "downLoad('" + source.source_app + "','" + source.source_tag + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                }
            } else if (data && data.message) {
                return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
            } else {
                showExDialog('图片验证码不正确', '确认', '', '', '', '', '', pop_params);
                $('.img-input img').click();
            }
        },
        error: function () {
            console.log('检验图片验证码接口错误')
        }
    })
}
/**
 *
 * 注册申请
 * @params phone 注册手机号
 * @params code 验证码
 * @params url post请求地址
 * @params source 注册信息
 * @params isfromweichat 判断是否来自微信
 * @params pop_params 弹框样式修改
 * @params password 设置的新密码
 * @params obj wx页面下载app的名称
 */
function register(phone, code, url, source, isfromweichat, pop_params, password, obj, openid) {
    if (!isPhone(phone)) {
        return showExDialog('手机号码格式不正确', '确定', '', '', '', '', '', pop_params);
    }
    if (!code) {
        return showExDialog('请输入验证码', '确定', '', '', '', '', '', pop_params);
    }
    if (document.querySelector('#password')) {
        if (!password || password.length < 6) {
            return showExDialog('请输入6~12位密码', '确定', '', '', '', '', '', pop_params);
        }
    }
    url = url.replace('NoneAppMarket', source.source_tag);
    $.ajax({
        type: 'post',
        dataType: 'json',
        url: url,
        data: {
            phone: phone,
            code: code,
            source_id: source.source_id,
            source_tag: source.source_tag,
            openid: openid,
            password: password
        },
        success: function (data, textStatus) {
            if (data && data.code == 0) {
                var isAndroid =  navigator.userAgent.indexOf("Android") > 0
                if (isfromweichat == 1 && isAndroid) { //请注意返回值的类型 js 不会把0转为false
                    console.log(isfromweichat);
                    if (obj && obj == 'xybt_xjbtfuli') {
                        return showWxExDialog('恭喜您，注册成功，请下载APP后登录！', '../image/xjbt/xjbt-code.png', '', '', pop_params);
                    } else {
                        return showExDialog('恭喜您，注册成功，请下载APP后登录！', '下载APP', "showWxDown('" + obj + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                    }
                } else {
                    if (typeof _taq != 'undefined' && typeof _tt_convert_id != 'undefined' && _tt_show_type == 0) { //今日头条
                        _taq.push({ convert_id: _tt_convert_id, event_type: 'form' });
                    }
                    return showExDialog('恭喜您，注册成功，请下载APP后登录！', '下载APP', "xxl_downLoad('" + source.source_app + "','" + source.source_tag + "','"+ phone.toString() +"')", '', '', '', '', pop_params);
                }
            } else if (data.message) {
                return showExDialog(data.message, '确定', '', '', '', '', '', pop_params);
            }
        },
        error: function (xhr, msg) { //错误信息发送给后台接口
            console.log('接口请求不通，source_tag：' + source.source_tag + '\n' + 'xhr:' + xhr.readState + '\n' + 'msg:' + msg)
        }
    })
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
* @params liHeight li的高度
*/
function rollShowData(rollbox, liMoveNum, url, rollParentBox, liHeight) {
    liHeight = liHeight || '1.2em';
    var $ul = $("<ul></ul>");
    $ul.css({ 'overflow': 'hidden' });
    $(rollbox).append($ul);
    $.ajax({
        type: 'get',
        url: url,
        dataType: 'json',
        success: function (data) {
            if (data && data.code == 0) {
                for (var i = 0; i < data.message.length; i++) {
                    var $li = $('<li></li>');
                    $li.css({
                        'overflow': 'hidden',
                        'white-space': 'nowrap',
                        'text-overflow': 'ellipsis',
                        'height': liHeight
                    }).text(data.message[i]);
                    $ul.append($li);
                }
                $ul.append($ul.children().clone(true));
                setInterval(function () {
                    autoRoll($ul, liMoveNum);
                }, 2500);
            }
            if (data.code == -1) {
                // 设置隐藏
                $(rollParentBox).hide();
            }
        },
        error: function (xhr, msg) {
            //错误信息发送给后台接口
            console.log('文字轮播接口不通')
        }
    })
}
// 轮播动画
/*
* @params ul 轮播ul
* @params liMoveNum 每次轮播li的数量
*/
function autoRoll(ul, liMoveNum) {
    var liHeight = ul.children("li:first").outerHeight();
    ul.animate({
        marginTop: "-" + liHeight * liMoveNum + "px"
    },
        500,
        "swing",
        function () {
            $(this).css({
                marginTop: "0"
            }).children("li:lt(" + liMoveNum + ")").appendTo(this);
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
 *                            "imgUrl":url }   //"imgUrl"-不同奖品图片的链接
*/
function turntableDialog(content, contentParams, btnParams, btnl, funl, btnr, funr, type) {
    contentParams = contentParams || { 'text-align': 'center' };
    btnParams = btnParams || { 'background-color': '#fff' };
    btnl = btnl || '稍后操作';
    funl = funl || 'hideExDialog("turntable-dialog")';
    funr = funr.indexOf('(') > 0 ? funr + ';' : funr + '();';
    btnr = btnr || '极速借款';
    type = type || {};

    showMask('turntable-dialog', '#000', '50'); //显示遮罩层

    var $turntableDialog = $("<div></div>");
    $turntableDialog.addClass('turntable-dialog').attr("id", "dialog-wraper");

    var $title = $("<div></div>");
    var $content = $("<div></div>");
    if (type.title && type.title == 'prize') {
        $title.addClass('title').attr("id", "prize-title").appendTo($turntableDialog);

        $content.addClass('content').css(contentParams).html(content + "<div class='voucher' style='background:url(" + type.imgUrl + ") no-repeat center top/5.453333rem 2.266667rem'></div><p style='color:#497082'></p>");
        $turntableDialog.css("top", '0');
    } else {
        $title.addClass('title').attr("id", "turntable-title").appendTo($turntableDialog);
        $content.addClass('content').css(contentParams).html(content);
    }
    $content.appendTo($turntableDialog);

    var $btn = $("<div></div>");
    if (type.btn && type.btn == "oneclose") {
        var $noChance = $("<div></div>");
        $noChance.addClass('noChance').attr("onclick", funl);
        $noChance.appendTo($btn);
        $content.css("border-radius", "10px")
    } else {
        var $buttonl = $("<button></button>");
        $buttonl.addClass('fl').css("background-color", "#e9d03e").attr("onclick", funl).html(btnl);
        var $buttonr = $("<button></button>");
        $buttonr.addClass('fr').css("background-color", "#ff7978").attr("onclick", funr).html(btnr);
        $buttonl.appendTo($btn);
        $buttonr.appendTo($btn);
        $btn.css(btnParams);
    }
    $btn.addClass('btn').appendTo($turntableDialog);

    $turntableDialog.appendTo('body');
}


/**
 * JS脚本执行报错检测
 */
(function (window) {
    window.onerror = function (msg, url, line) {
        //向后台发送错误信息
        console.log('错误：' + msg + '\n' + 'URL:' + url + '\n' + 'at line:' + line);
    }
})(window)

/**
 * 广点通js
 */

