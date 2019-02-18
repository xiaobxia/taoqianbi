/**
 * @Author myron
 * @Version 2014111701
 * @Update date 2017031401
 * 说明：可以使用，不要随意更改原有的代码；若有添加且依赖其他文件的函数，在函数边上注释说明（如：依赖jquery，方法默认都是源生js）
*/
console.log(dateByTimestamp());
getSourceUrl();
/***********************************begin******************************************/
/*
 *性能测试专用
 * @param fun 测试函数
*/
function testRuntime(fun){
    console.time("Runtime");
    console.log(fun);
    console.timeEnd("Runtime");
}

/*
 *url跳转
*/
function jumpTo(url){
    window.location.href = url;
}

/*
 *获取ID
*/
function ID(id){
    return !id ? null : document.getElementById(id);
}

/**
 * 获取class
 * @param sName 必需。规定查找的类名
 */
 function getClass(sName){
    if(document.getElementsByClassName){
        return document.getElementsByClassName(sName);
    }else{       
        var aTmp = document.getElementsByTagName('*'), aRes = [], arr = [], aTmpLen = aTmp.length;
        for(var i=0;i<aTmpLen;i++){   
            arr = aTmp[i].className.split(' ');
            var arrLen = arr.length;
            for (var j=0;j<arrLen;j++){
                if(arr[j] == sName) aRes.push(aTmp[i]);
            }
        }
        return aRes;
    }
}

/**
 * 通过类名隐藏元素
 * @params cName
*/
function hideElementByClass(cName){
    if(window.jQuery){
        $('.'+cName).css('display','none');
    }else{
        var class_arr = getClass(cName), len = class_arr.length;
        for (var i = 0; i < len; i++) {
            var obj = class_arr[i];
            obj.style.display = 'none';
        }
    }
}

/**
 * 通过类名显示元素
 * @params cName
*/
function showElementByClass(cName){
    if(window.jQuery){
        $('.'+cName).css('display','');
    }else{
        var class_arr = getClass(cName), len = class_arr.length;
        for (var i = 0; i < len; i++) {
            var obj = class_arr[i];
            obj.style.display = '';
        }
    }
}

/**
 * 通过类名移除元素
 * @params cName
*/
function removeElementByClass(cName){
    if(window.jQuery){
        $('.'+cName).remove();
    }else{
        var class_arr = getClass(cName), len = class_arr.length;
        for (var i = len - 1; i >= 0; i--) { //
            var obj = class_arr[i];
            obj.parentNode.removeChild(obj);
        }
    }
}

/**
 * 设置cookies
 * @params name 
 * @params value 
 * @params expire 可选
*/
function setCookie(name, value, expire){
    var time = intval(expire.substring(1,expire.length));
    var unit = expire.substring(0,1);
    if (unit=="s") time *= 1000;
    if (unit=="h") time *= 60*60*1000;
    if (unit=="d") time *= 24*60*60*1000;
    var exp = new Date();
    exp.setTime(exp.getTime() + time);
    document.cookie = name + "="+ escape (value) + ";path=/;expires=" + exp.toGMTString();
}

/**
 * 删除cookies
 * @params name 
*/
function delCookie(name){
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if(cval != null) document.cookie = name + "="+cval+";path=/;expires="+exp.toGMTString();
}

/**
 * 读取cookies
 * @params name 
*/
function getCookie(name){
    var arr, reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if( arr = document.cookie.match(reg) ) return (arr[2]);
    return null;
}

/*
 *获取url参数的值
*/
function getQueryString(name){
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]);
    return null;
}

/**
 * 判断对象具体类型 辅助函数
 * @param obj
*/
function getTypeOf(obj){
    if(typeof obj == 'undefined') return '[object Undefined]';
    return Object.prototype.toString.call(obj);
}

/*
 *获取来源URL
*/
function getSourceUrl(){
    if(document.referrer) return document.referrer;
    if(!window.localStorage) return '/';
    var curUrl = window.localStorage.getItem("curUrl");
    if( curUrl != window.location.href ){
        window.localStorage.setItem("sourceUrl",curUrl);
        window.localStorage.setItem("curUrl",window.location.href);
    }
    return window.localStorage.getItem("sourceUrl");
}

/*
 *拨打电话
*/
function callPhoneMehtod(phone){
    jumpTo("tel:" + phone);
}

/*
 *根据屏幕调整字体大小
*/
function fontSize(){
    !function(x){
        var dom = x.document;
        function handleBody(){
            var cssTxt = 'body{font-size:100% !important;}';
            var _style = dom.createElement('style');
            _style.type = 'text/css';
            dom.getElementsByTagName("head")[0].appendChild(_style);
            try {
                _style.innerHTML = cssTxt;
            } catch (c) {
                _style.innerText = cssTxt;
            }
        }
        function handleHtml(){
            var el = dom.documentElement;
            el.style.fontSize = Math.min(100,el.clientWidth / 480*120 ) + '%';
        }
        if(!!x.addEventListener){
            x.addEventListener("resize", function() { handleHtml(); });
            x.addEventListener("load", function() { handleBody(); });
        }else{
            x.attachEvent("onresize", function() { handleHtml(); });
            x.attachEvent("onload", function() { handleBody(); });
        }
        handleHtml();
    }(window);
}

/**
 * 不足补零
 * @param num 必需。规定被补零的数字
 * @param n 可选。 规定补零后的长度，默认两位
 */
function appendZero(num, n){
    n = n || 2;
    var remainLen = n-(''+num).length > 0 ? n-(''+num).length : 0;
    return Array(remainLen+1).join(0) + num;  
}

/**
 * url参数拼接
 * @param url
 * @param params 支持类型[object String] [object Object]
*/
function createUrl(url,params){
    if(url) url += (url.indexOf("?") == -1 ? '?' : '&');
    if( getTypeOf(params) == '[object String]' ) url += params;
    if( getTypeOf(params) == '[object Object]' ){
        var params_temp = '';
        for(var key in params){ params_temp += "&"+key+"="+params[key] };
        url += strReplace(params_temp,'&','','both');
    }
    console.log(url);return url;
}

/**
 * 字符串替换 默认去除所有空格
 * @param string  必需。规定被搜索的字符串。
 * @param search 可选。规定要查找的值。
 * @param replace 可选。规定替换 search 中的值的值。
 * @param place 可选。规定替换 string 的位置：左右 左 右 所有，默认所有。
 * @param range 可选。默认：/g  (/i:忽略大小写; /g:全文查找出现的所有匹配字符; /m:多行查找; /g:全文查找、忽略大小写; /i:全文查找、忽略大小写)
*/
function strReplace(string,search,replace,place,range){
    var tempPlace = function(search,replace){
        search = search + '';
        var placeList = {
            "left" : "^" + search,
            "right" : search + "$",
            "both" : "^" + search + "|" + search + "$",
            "all" : search
        };
        placeList[place] = placeList[place] || placeList['all'];
        var reg =new RegExp(placeList[place],range); // reg为 /[变量]/g
        return string.replace(reg,replace);
    };
    search = search || ' ';
    replace = replace || '';
    place = place || 'all';
    range = range || 'g';
    var searchList = [], replaceList = [], searchType = getTypeOf(search), replaceType = getTypeOf(replace);
    if( searchType == '[object Array]' ){
        searchList = arrayArgToIE(search);
        var len = searchList.length;
        if( replaceType == '[object Array]' ){
            replaceList = arrayArgToIE(replace);
            len = Math.min(searchList.length,replaceList.length);
        }
        for (var i=0; i<len; i++){
            search = searchList[i];
            replace = replaceList[i] || replace;
            string = tempPlace(search,replace);
        }
        return string;
    }else{
        return tempPlace(search,replace);
    }
}

/**
 * 科学计数法转换成浮点数
 * @param value 科学计数法字符串
 * @return 字符串
 */
function eNotationToFloat(value){
    var str = value + '', retStr = '';
    var reg = new RegExp("[+-]?[0-9]([\\.][0-9])?(e[+-]){1}[0-9]", "i");
    if( regTemp = str.match(reg) ){
        var power = str.substr(regTemp['index']+2);
        var floatStr = str.substr(0,regTemp['index']+1);
        if( power <= 20 && power >= -6 ) return floatStr*Math.pow(10,power)+'';
        //特殊处理：大于20或小于-6时自动转化科学计数法
        if( power > 20 ){
            retStr = floatStr*Math.pow(10,20);
            for (var i = 0; i < power - 20; i++) {
                retStr += '0';
            };
        }else{
            retStr = floatStr*Math.pow(10,-6);
            for (var i = 0; i < -6 - power; i++) {
                retStr = '0' + retStr;
            };
            retStr = strReplace(retStr,'.','');
            retStr = '0' + strReplace(retStr,'0','.','left');
        }
        return retStr;
    }
    return str;
}

/*
 *判断客户端是否为PC还是手持设备
*/
function isPC(){
    var userAgentInfo = navigator.userAgent, Agents = ["Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod"], flag = true;
    var len = Agents.length;
    for (var v = 0; v < len; v++){
        if (userAgentInfo.indexOf(Agents[v]) > 0) { flag = false; break; }  
    }
    return flag;
}

/*
 *判断是否是IE(v)浏览器
 * @param v 可选 IE版本
*/
function isIE(v){
    v = v || '';
    var userAgent = navigator.userAgent.toLowerCase();
    if( userAgent.indexOf('msie '+v) != -1 ) return true;
    if( userAgent.indexOf('trident') != -1 && userAgent.indexOf('rv:'+v) != -1 ) return true;
    return false;
}

/*
 *验证18位或15身份证
 * @str
 * @isStandard 可选 是否18位标准身份证
*/
function isIdCard(str,isStandard){
    var reg =/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/;
    if ( isStandard === false) var reg =/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/;
    return reg.test(str);
}

/*
 *验证交易密码
 * @str
*/
function isPayPwd(str){
    var reg =/^[0-9]\d{5}$/;
    return reg.test(str);
}

/*
 *判断是否是手机号码
*/
function isPhone(num){
    var reg = /^[1]\d{10}$/;
    return reg.test(num);
}

/*
 *判断是否是虚拟号段，该号段不支持充值
*/
function isVirtualPhone(num){
    if(num == '17711682160'){//白名单用户
        return false;
    }
    var reg = /^17/;
    return reg.test(num);
}

/*
 *判断是否是IP地址
*/
function isIP(str){
    var reg = /((([1-9]?|1\d)\d|2([0-4]\d|5[0-5]))\.){3}(([1-9]?|1\d)\d|2([0-4]\d|5[0-5]))/;
    return reg.test(str);
}

/**
* 模糊手机号 XXX****XXXX
*/
function blurPhone(phone){
    phone = strReplace(phone);
    return phone.substring( 0, 3) + '****' + phone.substring(7);
}

/**
 * 格式化手机号 XXX XXXX XXXX
 */
function formatPhone(phone){
    phone = strReplace(phone);
    return phone.substring( 0, 3) + ' ' + phone.substring( 3 , 7) + ' ' + phone.substring(7);
}

/*
 *限制只能输入数字
 * 含value属性的标签
 * eg: onkeyup事件
*/
function justInt(e){
    e.value = e.value.replace(/[^\d]/g,'');
}

/*
 *限制只能输入浮点数
 * 含value属性的标签
 * eg: onkeyup事件
*/
function justFloat(e){
    e.value = e.value.replace(/[^\d+(\.\d+)?$]/g,'');
}

/*
 * 转换成整型
*/
function intval(num){
    var ret = parseInt(eNotationToFloat(num));
    return isNaN(ret) ?  0 : ret;
}

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

/**
 * 时间戳转换日期
 * @param <bool> format 格式
 * @param <int> timestamp 时间戳(秒)
 * 格式对应匹配 ：y->年,m->月,d->日,h->时,i->分,s->秒（ 小写不补零）
 *                Y->年,M->月,D->日,H->时,I->分,S->秒
 */
function dateByTimestamp(format,timestamp){
    format = format || 'Y-M-D H:I:S';
    timestamp = timestamp || new Date().getTime()/1000;
    var time = new Date(timestamp * 1000), year = '', month = '', day = '', hour = '', minute = '',second = '';
    year = time.getFullYear();
    month = time.getMonth() + 1;
    day = time.getDate();
    hour = time.getHours();
    minute = time.getMinutes();
    second = time.getSeconds();
    var searchArr = ['y','Y','m','M','d','D','h','H','i','I','s','S'];
    var replaceArr = [(year+'').substr(2),year,month,appendZero(month),day,appendZero(day),hour,appendZero(hour),minute,appendZero(minute),second,appendZero(second)];
    return strReplace(format,searchArr,replaceArr);
}


/**
 * 时间单位转换成字符 格式:d天H:i:s.ms(ms=ms/10)
 * @param intDiff 单位：秒
 * @param isMillisecond 是否显示毫秒 默认true
 */
function timeToStr(intDiff,isMillisecond){
    var str = '', day=0, hour=0, minute=0, second=0, Millisecond=0;//时间默认值
    if(intDiff > 0){
        intDiff = intval(intDiff * 1000);//倒计时总毫秒数量
        day = intval(intDiff / 86400000);
        hour = intval(intDiff % 86400000 / 3600000);
        minute = intval(intDiff % 86400000 % 3600000 / 60000);
        second = intval(intDiff % 86400000 % 3600000 % 60000 / 1000);
        Millisecond = intval(intDiff % 1000 / 10); // 毫秒/10
        if( day > 0 ) str += appendZero(day)+"天";
        if( hour > 0 ) str += appendZero(hour)+":";
    }
    str += appendZero(minute)+":"+appendZero(second);
    if (isMillisecond === false) return str;
    str += "."+appendZero(Millisecond);
    return str;
}

/**
 * 倒计时
 * @param noRefresh 是否页面刷新  默认true
 * @param cName 类名 默认值：alltime
 * @param attrName 自定义属性名  默认值：time
 * eg:<span class="alltime" time="816406"><!--倒计时--></span>
 */
function countDown(noRefresh,cName,attrName){
    cName = cName || 'alltime';
    attrName = attrName || 'time';
    var etime = new Date().getTime();
    var timing = window.setInterval(function(){
        ctime = new Date().getTime();
        if(window.jQuery){
            $.each($('.' + cName),function(){
                var interval = intval($(this).attr(attrName)) * 1000;
                var intDiff = etime - ctime + interval;
                $(this).html(timeToStr(intDiff/1000));
                if(intDiff < 0 ) window.clearInterval(timing);
                if(intDiff < 0 && noRefresh !== false) window.location.reload();
            });
        }else{
            var class_arr = getClass(cName), len = class_arr.length;
            for (var i = 0; i < len; i++) {
                var obj = class_arr[i];
                var interval = intval(obj.getAttribute(attrName)) * 1000;
                var intDiff = etime - ctime + interval;
                obj.innerHTML = timeToStr(intDiff/1000);
                if(intDiff < 0 ) window.clearInterval(timing);
                if(intDiff < 0 && noRefresh !== false) window.location.reload();
            }
        }
    }, 10);
}

/**
 * 关闭遮罩层
*/
function hideMask(){
    var obj = ID('mask');
    obj && obj.parentNode.removeChild(obj);
    document.body.style.overflow = 'auto';
    if( isIE(7) ){
        // 以下针对ie7特殊处理
        document.body.style.height = '';
        document.body.style.marginTop = '';
        document.body.style.marginRight = '';
        document.body.style.marginBottom = '';
        document.body.style.marginLeft = '';
        document.documentElement.style.height = '';
    }
}

/**
 * 显示遮罩层
 * @params cName 默认 mask
 * @params bgColor 默认 #000
 * @params opacityVal 默认 45
*/
function showMask(cName, bgColor, opacityVal){
    cName = cName || 'mask';
    bgColor = bgColor || '#000';
    opacityVal = opacityVal || 45;
    document.body.style.overflow = 'hidden';
    if( isIE(7) ){
        document.body.style.height = '100%';
        document.body.style.marginTop = '0';
        document.body.style.marginRight = 'auto';
        document.body.style.marginBottom = '0';
        document.body.style.marginLeft = 'auto';
        document.documentElement.style.height = '100%';
    }
    var layer = document.createElement('div');
    layer.className = cName;
    layer.id = 'mask';
    layer.style.cssText = "position:fixed; z-index:1; top:0; "+( isIE(7) ? 'left:0;' : '')+" width:100%; height:100%; background-color:"+bgColor+"; opacity:"+(opacityVal/100)+"; filter:alpha(opacity="+opacityVal+"); -moz-opacity:"+(opacityVal/100)+"; -khtml-opacity:"+(opacityVal/100)+";";
    document.body.appendChild(layer);
}

/**
 * 关闭进度条
 * @params cName 默认 circle
*/
function hideCircle(cName){
    hideMask();
    cName = cName || 'circle';
    hideElementByClass(cName);
}

/**
 * 显示进度条 依赖 sonic.js
 * @params fColor 默认 #fd5353
 * @params cName 默认 circle
 * @params pTop 默认 30%
*/
function drawCircle(fColor, pTop, cName){
    fColor = fColor || '#fd5353';
    cName = cName || 'circle';
    pTop = pTop || '30%';
    hideCircle(cName); //初始化
    showMask(cName);
    var idName = 'circle-wraper';
    if( ID(idName) ) return showElementByClass(cName);
    var circleWraper = document.createElement('div');
    circleWraper.className = cName;
    circleWraper.id = idName;
    circleWraper.style.cssText = "position:fixed; z-index:502; top:"+pTop+"; width:100%; margin:0 auto; text-align:center;";
    document.body.appendChild(circleWraper);
    if( isIE(7) || isIE(8) ){
        return ID(idName).innerHTML = '<span style="color:#666;">加载中...</span>';
    }
    var circle = new Sonic({
        width: 100,
        height: 100,
        stepsPerFrame: 1,
        trailLength: 1,
        pointDistance: .02,
        fps: 30,
        fillColor: fColor,
        step: function (point, index){
            this._.beginPath();
            this._.moveTo(point.x, point.y);
            this._.arc(point.x, point.y, index * 7, 0, Math.PI * 2, false);
            this._.closePath();
            this._.fill();
        },
        path: [
            ['arc', 50, 50, 30, 0, 360]
        ]
    });
    circle.play();
    if(window.jQuery){
        $('#'+idName).html(circle.canvas);
    }else{
        ID(idName).appendChild(circle.canvas);
    }
}

/**
 * 关闭遮罩层
 * @params cName 默认 ex_dialog
*/
function hideExDialog(cName){
    hideMask();
    cName = cName || 'ex_dialog';
    removeElementByClass(cName);
}

/**
 * 显示遮罩层
 * @params content 
 * @params btn1 
 * @params func1 默认 hideExDialog 
 * @params btn2
 * @params func2 默认 hideExDialog
 * @params pTop 顶部距离 默认 15%
 * @params cName 默认 ex_dialog
*/
function showExDialog(content,btn1,func1,btn2,func2,pTop,cName){
    func1 = func1 || 'hideExDialog';
    func2 = func2 || 'hideExDialog';
    func1 = func1.indexOf('(') > 0 ? func1+';' : func1+'();';
    func2 = func2.indexOf('(') > 0 ? func2+';' : func2+'();';
    pTop = pTop || '15%';
    cName = cName || 'ex_dialog';
    hideExDialog(cName);
    showMask(cName);
    var dialogWraper = document.createElement('div');
    dialogWraper.className = cName;
    dialogWraper.id = 'dialog-wraper';
    dialogWraper.style.cssText = "width: 80%;max-width: 380px;margin: auto;";
    document.body.appendChild(dialogWraper);
    var str = '';
    str += '<div style="position: fixed;z-index:2;top:'+pTop+';width:80%;max-width:380px;background-color:#fff;border-radius:5px;-moz-border-radius:5px 5px 5px 5px;-webkit-border-radius:5px;">';
    str += '<div class="_content" style="text-align:center;margin:12% auto;padding:0 10%;letter-spacing:1px;">'+content+'</div>';
    if(arguments[1]) str += '<div class="_btn" style="display:inline-block;width: '+((arguments[1] && arguments[3]) ? '35%' : '80%')+';padding: 2.5% 0;margin-left: 10%;margin-bottom: 4%;background-color: #d74a55;color: #fff;text-align: center;border-radius: 4px;-moz-border-radius: 5px 5px 5px 5px;-webkit-border-radius: 4px;" onclick="'+func1+'">'+btn1+'</div>';
    if(arguments[3]) str += '<div class="_btn" style="display:inline-block;width: 35%;padding: 2.5% 0;margin-left: 10%;margin-bottom: 4%;background-color: #ccc;color: #fff;text-align: center;border-radius: 4px;-moz-border-radius: 4px 4px 4px 4px;-webkit-border-radius: 4px;" onclick="'+func2+'">'+btn2+'</div>';
    str += '</div>';
    ID('dialog-wraper').innerHTML = str;
}

/**
 * 显示自定义遮罩层
 * @params content 
 * @params pTop 顶部距离 默认 15%
 * @params boxWidth 盒子宽度 默认 380px
 * @params cName 默认 ex_dialog
*/
function showCustomExDialog(content,pTop,boxWidth,cName){
    pTop = pTop || '15%';
    cName = cName || 'ex_dialog';
    boxWidth = boxWidth || '380px';
    hideExDialog(cName);
    showMask(cName);
    var dialogWraper = document.createElement('div');
    dialogWraper.className = cName;
    dialogWraper.id = 'dialog-wraper';
    dialogWraper.style.cssText = 'width:'+boxWidth+';margin:auto;';
    document.body.appendChild(dialogWraper);
    var str = '';
    str += '<div style="position: fixed;z-index:2;top:'+pTop+';width:'+boxWidth+';background-color:#fff;">';
    str += '<div class="_content">'+content+'</div>';
    str += '</div>';
    ID('dialog-wraper').innerHTML = str;
}

/**
 * 文字滚动
 * @params idName 
 * @params content 
 * @params cHeight 默认 1.2em
 * @params speedVal 默认 100
*/
function initMarquee(idName,content,cHeight,speedVal){
    if( !ID(idName) ) return;
    cHeight = cHeight || '1.2em';
    speed = speedVal || 100;
    var html = '';
    startMarquee = function(){
        var marqueeBox = ID("marqueeBox");
        var Box1 = ID("Box1");
        var Box2 = ID("Box2");
        Box2.innerHTML = ID("Box1").innerHTML;
        if( marqueeBox.scrollLeft - Box2.offsetWidth >= 0 ){
            marqueeBox.scrollLeft -= Box1.offsetWidth;  
        }else{
            marqueeBox.scrollLeft = marqueeBox.scrollLeft + 3;
        }
    }
    if( isPC() ){
        html += '<div id="marqueeBox" style="width:100%;overflow:hidden;height:'+cHeight+';line-height:'+cHeight+';" onmouseover="clearInterval(timing);" onmouseout="timing=setInterval(startMarquee,speed);">';
    }else{
        html += '<div id="marqueeBox" style="width:100%;overflow:hidden;height:'+cHeight+';line-height:'+cHeight+';" ontouchstart="clearInterval(timing);" ontouchend="timing=setInterval(startMarquee,speed);">';
    }
    html += '<div style="max-width:none;width:1000%;overflow:hidden;">';
    html += '<div id="Box1" style="max-width:none;float:left;word-break:keep-all;word-wrap:normal;">'+content+'&nbsp;</div>';
    html += '<div id="Box2" style="max-width:none;float:left;word-break:keep-all;word-wrap:normal;"></div>';
    html += '</div></div>';
    ID(idName).innerHTML = html;
    timing = setInterval(startMarquee,speed);
}

/**
 * 图片异步加载
 * @params imgList 资源列表 
*/
function imgReady(imgList){
    var loadImg = function(src){
            imgObj = new Image();
            imgObj.src = src;
    };
    var type = getTypeOf(imgList);
    if( type == '[object String]' ){
        loadImg(imgList);
    }else if( type == '[object Object]' ){
        for( key in imgList){
            var list = arrayArgToIE(imgList[key]), len = list.length;
            for (var i=0; i<len; i++){
                loadImg(list[i]);
            }
        }
    }else if( type == '[object Array]' ){
        var list = arrayArgToIE(imgList), len = list.length;
        for (var i=0; i<len; i++){
            loadImg(list[i]);
        }
    }
}

/**
 * 兼容IE7、IE8参数为数组的情况：eg:[xx,xx,] 。正常 length = 2，ie8、ie7下length = 3
 * @params name
*/
function arrayArgToIE(arr){
    if( getTypeOf(arr) == '[object Array]' && (isIE(7) || isIE(8)) && arr.length > 0 ){
        var ret = arr.pop();
        if( getTypeOf(ret) != '[object Undefined]' ) arr.push(ret);
    }
    return arr;
}

/**
 * 下拉刷新
 * @params bgColor 默认 transparent
 * @params tipsColor 默认 #999
*/
function pullDownRefresh(bgColor,tipsColor){
    if(isPC()) return;
    bgColor = bgColor || 'transparent';
    tipsColor = tipsColor || '#999';
    var bodyHtml = document.createElement("div");
    var outerScroller = document.createElement("div");
    var tips = document.createElement("div");
    var scroll = document.createElement("div");
    var cssTxt = '';
    var _style = document.createElement('style');
    cssTxt += '#outerScroller{position:absolute;top:0;bottom:0;width:100%;left:0;background-color:'+bgColor+';overflow:hidden;}';
    cssTxt += '#scroll{position:absolute;top:0;left:0;width:100%;margin-top:0;padding:0;}';
    _style.type = 'text/css';
    _style.innerHTML = cssTxt;
    outerScroller.id = 'outerScroller';
    tips.style = 'width:100%;line-height:2em;text-align:center;color:'+tipsColor+';';
    tips.innerHTML = '下拉刷新...';
    scroll.id = 'scroll';
    outerScroller.appendChild(tips);
    outerScroller.appendChild(scroll);
    bodyHtml.appendChild(_style);
    bodyHtml.appendChild(outerScroller);
    scroll.innerHTML = document.body.innerHTML;
    document.body.innerHTML = bodyHtml.innerHTML;
    var scroll = ID('scroll');
    var outerScroller = ID('outerScroller');
    var touchStart = 0;
    var touchDis = 0;
    outerScroller.addEventListener('touchstart', function(event){
        // 把元素放在手指所在的位置
        touchStart = event.targetTouches[0].pageY; 
    }, false);
    outerScroller.addEventListener('touchmove', function(event){
        var touch = event.targetTouches[0];
        scroll.style.top = scroll.offsetTop + touch.pageY - touchStart + 'px';
        if(scroll.offsetHeight - window.innerHeight + scroll.offsetTop <= 0){
            scroll.style.top = -(scroll.offsetHeight - window.innerHeight) + 'px';
        }
        touchStart = touch.pageY;
        touchDis = touch.pageY - touchStart;
    }, false);
    outerScroller.addEventListener('touchend', function(event){
        touchStart = 0;
        var top = scroll.offsetTop;
        if(top > 70) location.href = window.location.href;
        if(top > 0){
            var timimg = setInterval(function(){
                scroll.style.top = scroll.offsetTop - 2 + 'px';
                if(scroll.offsetTop<=0) clearInterval(timimg);
            },1)
        }
    }, false);
}
/**
 * 获取验证码倒计时
 * @params btnTips1 默认 获取
 * @params btnTips2 默认 num秒
 * @params eleId 默认 action
 * @params fun 默认 getCode
*/
function getCodeCountDown(btnTips1,btnTips2,eleId,fun){
    btnTips1 = btnTips1 || '获取';
    btnTips2 = btnTips2 || 'num秒';
    eleId = eleId || 'action';
    fun = fun || 'getCode();';
    var obj = ID(eleId);
    var _div = document.createElement("div");
    var second = document.createElement("i");
    second.id = 'second';
    second.innerHTML = 60;
    _div.appendChild(second);
    obj.innerHTML = strReplace(btnTips2,'num',_div.innerHTML);
    obj.setAttribute('disabled','true');
    countdown();
    function countdown(){
        var obj1 = ID('second');
        obj1.innerHTML = intval(obj1.innerHTML) - 1;
        obj.style.cursor = 'not-allowed';
        //倒计时结束
        if( obj1.innerHTML <= 0 ){
            window.clearInterval(timing);
            obj.innerHTML = btnTips1;
            obj.removeAttribute('disabled');
            obj.style.cursor = '';
        }
    }
    var timing = window.setInterval(countdown,1000);
}
 /***********************************end******************************************/
