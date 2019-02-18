(function(b){b.fn.datePicker=function(a){return this.each(function(e){var V=b(this),ac=b(this).is("input"),W=new Date(),I=null,U=null,X=null,S=null,R=null,T=null,ae=null,Y=null,ab=null,ad=null,L=null,K=null;b.fn.datePicker.defaultOptions={beginyear:2010,endyear:2020,monthDay:[31,28,31,30,31,30,31,31,30,31,30,31],days:["周日","周一","周二","周三","周四","周五","周六"],beginhour:0,endhour:23,beginminute:0,endminute:59,curdate:true,nextDay:false,liH:60,theme:"date",mode:null,event:"click",show:true,title:"请选择日期",scrollOpt:{snap:"li",checkDOMChanges:true,vScrollbar:false},callBack:function(){}};var N=b.extend(true,{},b.fn.datePicker.defaultOptions,a);if(!N.show){V.off("click")}else{V.on(N.event,function(){H()})}function H(){K=V.val();if(!b("#datePlugin").size()){b("body").append('<div id="datePlugin"></div>')}document.getElementsByTagName("body")[0].addEventListener("touchmove",M,false);if(!N.curdate&&b.trim(K)!=""){var d=null,c=null;if(N.theme=="date"||N.theme=="datetime"){d=K.split(" ")[0];T=parseInt(d.split("-")[0]-parseInt(N.beginyear))+1,ae=parseInt(d.split("-")[1]),Y=parseInt(d.split("-")[2])}if(N.theme=="datetime"){c=K.split(" ")[1];ab=parseInt(c.split(":")[0])+1,ad=parseInt(c.split(":")[1])+1}if(N.theme=="time"){c=K;ab=parseInt(c.split(":")[0])+1,ad=parseInt(c.split(":")[1])+1}if(N.theme=="month"){d=K;T=parseInt(d.split("-")[0]-parseInt(N.beginyear))+1,ae=parseInt(d.split("-")[1])}}else{T=parseInt(W.getFullYear())-parseInt(N.beginyear)+1,ae=parseInt(W.getMonth())+1,Y=parseInt(W.getDate()),ab=parseInt(W.getHours())+1,ad=parseInt(W.getMinutes())+1,L=parseInt(W.getSeconds())}if(N.nextDay){Y=parseInt(W.getDate())+1}b("#datePlugin").show();af();G();b("#d-okBtn").on("click",function(i){af();document.getElementsByTagName("body")[0].removeEventListener("touchmove",M,false);var f=b("#yearScroll li").eq(T).data("num");var g=b("#monthScroll li").eq(ae).data("num");var h=b("#dayScroll li").eq(Y).data("num");var j=b("#hourScroll li").eq(ab).data("num");var k=b("#minuteScroll li").eq(ad).data("num");V.val(b(".d-return-info").html());b("#datePlugin").hide().html("");N.callBack({y:f,M:g,d:h,h:j,m:k})});b("#d-cancleBtn").on("click",function(f){af();b("#datePlugin").hide().html("");document.getElementsByTagName("body")[0].removeEventListener("touchmove",M,false)})}function M(c){c.preventDefault()}function P(c){if((c%4==0&&c%100!=0)||c%400==0){return true}else{return false}}function G(){var c=' <div class="d-date-box"><div class="d-date-title">'+N.title+'</div><p class="d-date-info"><span class="d-day-info"></span><span class="d-return-info"></span></p></div>';var h='<div class="d-date-btns"><button class="d-btn" id="d-okBtn">确定</button><button class="d-btn" id="d-cancleBtn">取消</button></div>';var f='<div class="d-date-wrap"><div class="op op1"></div><div class="op op2"></div>';f+='<div class="d-date-mark"></div>';f+='<div class="d-year-wrap d-date-cell" id="yearScroll"><ul></ul></div>';f+='<div class="d-month-wrap d-date-cell" id="monthScroll"><ul></ul></div>';f+='<div class="d-day-wrap d-date-cell" id="dayScroll"><ul></ul></div>';f+="</div>";var g='<div class="d-date-wrap d-time-wrap">';g+='<div class="d-date-mark"></div>';g+='<div class="d-hour-wrap d-date-cell" id="hourScroll"><ul></ul></div>';g+='<div class="d-minute-wrap d-date-cell" id="minuteScroll"><ul></ul></div>';g+="</div>";var d='<div class="d-date-wrap">';d+='<div class="d-date-mark"></div>';d+='<div class="d-year-wrap d-date-cell" style="width:50%" id="yearScroll"><ul></ul></div>';d+='<div class="d-month-wrap d-date-cell" style="width:50%" id="monthScroll"><ul></ul></div>';d+="</div>";b("#datePlugin").html(c);switch(N.theme){case"date":b(".d-date-box").append(f);F();aa();O(N.monthDay[ae-1]);break;case"datetime":b(".d-date-box").append(f);b(".d-date-box").append(g);F();aa();O(N.monthDay[ae-1]);J();Z();break;case"time":b(".d-date-box").append(g);J();Z();break;case"month":b(".d-date-box").append(d);F();aa();break;default:b(".d-date-box").append(f);F();aa();O(N.monthDay[ae-1]);break}b(".d-date-box").append(h);Q()}function Q(){var c=b("#yearScroll li").eq(T).data("num"),i=b("#monthScroll li").eq(ae).data("num"),d=b("#dayScroll li").eq(Y).data("num"),f=b("#hourScroll li").eq(ab).data("num"),g=b("#minuteScroll li").eq(ad).data("num"),h=new Date(c+"-"+i+"-"+d);switch(N.theme){case"date":b(".d-day-info").html(N.days[h.getDay()]+"&nbsp;");b(".d-return-info").html(c+"-"+i+"-"+d);break;case"datetime":b(".d-day-info").html(N.days[h.getDay()]+"&nbsp;");b(".d-return-info").html(c+"-"+i+"-"+d+" "+f+":"+g);break;case"time":b(".d-return-info").html(f+":"+g);break;case"month":b(".d-return-info").html(c+"-"+i);break;default:b(".d-day-info").html(N.days[h.getDay()]+"&nbsp;");b(".d-return-info").html(c+"-"+i+"-"+d);break}}function af(){var c=[I,U,X,S,R];c.forEach(function(d){if(d!=null){d.destroy();d=null}})}function F(){var g=b("#yearScroll"),d=N.endyear-N.beginyear,h="<li></li>";for(var f=0;f<=d;f++){h+="<li data-num="+(N.beginyear+f)+">"+(N.beginyear+f)+"年</li>"}g.find("ul").html(h).append("<li></li>");I=new iScroll("yearScroll",b.extend(true,{},N.scrollOpt,{onScrollEnd:function(){c(this)}}));I.scrollTo(0,-(T-1)*N.liH);function c(i){var j=Math.floor(-i.y/N.liH);T=j+1;if(P(parseInt(g.find("li").eq(T).data("num")))){N.monthDay[1]=29}else{N.monthDay[1]=28}if(ae==2&&N.theme!="month"){O(N.monthDay[ae-1])}Q()}}function aa(){var d=b("#monthScroll"),c="<li></li>";for(var f=1;f<=12;f++){if(f<10){c+='<li data-num="0'+f+'">0'+f+"月</li>"}else{c+='<li data-num="'+f+'">'+f+"月</li>"}}d.find("ul").html(c).append("<li></li>");U=new iScroll("monthScroll",b.extend(true,{},N.scrollOpt,{onScrollEnd:function(){g(this)}}));U.scrollTo(0,-(ae-1)*N.liH);function g(h){console.log("month");var j=Math.floor(-h.y/N.liH);var i=N.monthDay[j];ae=j+1;if(N.theme!="month"){O(i)}Q()}}function O(c){var g=b("#dayScroll"),h="<li></li>";for(var f=1;f<=c;f++){if(f<10){h+='<li data-num="0'+f+'">0'+f+"日</li>"}else{h+='<li data-num="'+f+'">'+f+"日</li>"}}g.find("ul").html(h).append("<li></li>");X=new iScroll("dayScroll",b.extend(true,{},N.scrollOpt,{onScrollEnd:function(){d(this)}}));if(Y>N.monthDay[ae-1]){Y=1}X.scrollTo(0,-(Y-1)*N.liH);function d(i){Y=Math.floor(-i.y/N.liH)+1;Q()}}function J(){var g=b("#hourScroll"),c="<li></li>";for(var f=N.beginhour;f<=N.endhour;f++){if(f<10){c+='<li data-num="0'+f+'">0'+f+"时</li>"}else{c+='<li data-num="'+f+'">'+f+"时</li>"}}g.find("ul").html(c).append("<li></li>");S=new iScroll("hourScroll",b.extend(true,{},N.scrollOpt,{onScrollEnd:function(){d(this)}}));S.scrollTo(0,-(ab-1)*N.liH);function d(h){ab=Math.floor(-h.y/N.liH)+1;Q()}}function Z(){var c=b("#minuteScroll"),d="<li></li>";for(var g=0;g<=59;g++){if(g<10){d+='<li data-num="0'+g+'">0'+g+"分</li>"}else{d+='<li data-num="'+g+'">'+g+"分</li>"}}c.find("ul").html(d).append("<li></li>");R=new iScroll("minuteScroll",b.extend(true,{},N.scrollOpt,{onScrollEnd:function(){f(this)}}));R.scrollTo(0,-(ad-1)*N.liH);function f(h){ad=Math.floor(-h.y/N.liH)+1;Q()}}})}})(typeof(Zepto)!="undefined"?Zepto:jQuery);