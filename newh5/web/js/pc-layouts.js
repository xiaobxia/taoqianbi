Initialization();
function Initialization(){
    baiduVisitStat("//hm.baidu.com/hm.js?2d7ead83d6647b772a8a6c0661d68240");
}
//百度统计
function baiduVisitStat(src) {
    var _hmt = _hmt || [];
    (function() {
        var hm = document.createElement("script");
        hm.src = src;
        var s = document.getElementsByTagName("script")[0]; 
        s.parentNode.insertBefore(hm, s);
    })();
}