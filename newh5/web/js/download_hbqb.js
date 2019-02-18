$(function() {
    var u = navigator.userAgent;
    // if (u.indexOf('KDLC') > -1) {
    //     return;
    // }
    try{
        if (!(getQueryString&&typeof(getQueryString)=='function')) {
            var getQueryString=function(name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
                var r = window.location.search.substr(1).match(reg);
                if (r != null) return unescape(r[2]);
                return null;
            };
        }
    }catch(e){}

    var url = (function() {
        var isAndroid = u.indexOf("Android") > -1 || u.indexOf("Adr") > -1,
            isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/),
            isWeixin = (/micromessenger/i).test(u),
            isQQ = (/QQBrowser/i).test(u),
            sourceTag = getQueryString('source_tag');

        if (isAndroid) {
            if (!isWeixin) {
                var dft_apk = "http://qbres.wzdai.com/apk/xybt-latest.apk";
                var tag_apk = "http://qbres.wzdai.com/apk/xybt-" + sourceTag + ".apk";
                $.ajax({
                    url : tag_apk,
                    type : 'HEAD',
                    error: function() {
                        return jumpTo( dft_apk );
                    },
                    success: function() {
                        return jumpTo( tag_apk );
                    }
                });
            } else {
                return "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
            }
        }
        if (isiOS) {
            if (isWeixin) {
                return "http://a.app.qq.com/o/simple.jsp?pkgname=com.wzdai.xybt";
            } else {
                return "http://itunes.apple.com/app/id1221186366?mt=8";
            }
        }
    }());

    var download = function(e) {
        e.preventDefault();
        if (url) {
            window.location.href = url;
        }
    };

    $.fn.extend({
        downloadApp: function(u) {
            if (url) {
                $(this).on('click', function(e) {
                    e.preventDefault();
                    window.location.href = u;
                });
            } else {
                $(this).on('click', download);
            }
        }
    });

    var src = $('#download-show').attr('data-src') || '//api.koudailc.com/image/page/download_default2.png';
    if (src) {
        // 由于活动页面写法不一样，有的是用最先的fixfontsize写法，有的是用rem写法，有的用flexable写法，故在此做个兼容处理，如果有html标签上有data-dpr属性，采用rem，如果没有，那就使用em。
        if ($('html').attr('data-dpr')) {
            var fb = lib.flexible;
            var width = window.document.documentElement.getBoundingClientRect().width;
            var ratio = (width === fb.rem*(750/24)) ? 75/24 : 1;
            var $tmp = $('<div class="shareDownload" style="box-sizing:border-box;position:fixed;left:0;bottom:0;padding:0 '+0.75*ratio+'rem;width:100%;background-color:rgba(0,0,0,0.75);z-index:999;"><a href="javascript:void(0);" class="downloadImg"><img src="' + src + '" style="display:block;max-width:auto;height:'+1.3*ratio+'rem;"></a><a href="javascript:void(0)" style="display:block;position: absolute;top:'+0.2*ratio+'rem;right:'+0.2*ratio+'rem;width:'+0.4*ratio+'rem;height:'+0.4*ratio+'rem;background:url(//api.koudailc.com/image/page/close_small.png) no-repeat;background-size:cover;" class="downloadClose"></a></div>');
        }else{
            var $tmp = $('<div class="shareDownload" style="box-sizing:border-box;position:fixed;left:0;bottom:0;padding:0 2em 0 2em;width:100%;background-color:rgba(0,0,0,0.75);z-index:999;font-size:20px;"><a href="javascript:void(0);" class="downloadImg"><img src="' + src + '" style="display:block;max-width:100%;height:auto;"></a><a href="javascript:void(0)" style="display:block;position: absolute;top:0.5em;right:0.5em;width:0.4em;height:0.4em;background:url(//api.koudailc.com/image/page/close_small.png) no-repeat;background-size:cover;" class="downloadClose"></a></div>');
        }
        $tmp.find('.downloadImg').on('click', download);
        $tmp.find('.downloadClose').on('click', function(e) {
            e.preventDefault();
            $tmp.hide();
        });
        $tmp.appendTo('body');
    } else {
        $('.download-button').downloadApp();
    }
});
