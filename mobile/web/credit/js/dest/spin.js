/* 2016-09-26 */!function(a,b,c){function d(a,c){var d,e=b.createElement(a||"div");for(d in c)e[d]=c[d];return e}function e(a){for(var b=1,c=arguments.length;c>b;b++)a.appendChild(arguments[b]);return a}function f(a,b,c,d){var e=["opacity",b,~~(100*a),c,d].join("-"),f=.01+c/d*100,g=Math.max(1-(1-a)/b*(100-f),a),h=k.substring(0,k.indexOf("Animation")).toLowerCase(),i=h&&"-"+h+"-"||"";return m[e]||(n.insertRule("@"+i+"keyframes "+e+"{0%{opacity:"+g+"}"+f+"%{opacity:"+a+"}"+(f+.01)+"%{opacity:1}"+(f+b)%100+"%{opacity:"+a+"}100%{opacity:"+g+"}}",n.cssRules.length),m[e]=1),e}function g(a,b){var d,e,f=a.style;if(f[b]!==c)return b;for(b=b.charAt(0).toUpperCase()+b.slice(1),e=0;e<l.length;e++)if(d=l[e]+b,f[d]!==c)return d}function h(a,b){for(var c in b)a.style[g(a,c)||c]=b[c];return a}function i(a){for(var b=1;b<arguments.length;b++){var d=arguments[b];for(var e in d)a[e]===c&&(a[e]=d[e])}return a}function j(a){for(var b={x:a.offsetLeft,y:a.offsetTop};a=a.offsetParent;)b.x+=a.offsetLeft,b.y+=a.offsetTop;return b}var k,l=["webkit","Moz","ms","O"],m={},n=function(){var a=d("style",{type:"text/css"});return e(b.getElementsByTagName("head")[0],a),a.sheet||a.styleSheet}(),o={lines:12,length:7,width:5,radius:10,rotate:0,corners:1,color:"#000",speed:1,trail:100,opacity:.25,fps:20,zIndex:2e9,className:"spinner",top:"auto",left:"auto",position:"relative"},p=function q(a){return this.spin?void(this.opts=i(a||{},q.defaults,o)):new q(a)};p.defaults={},i(p.prototype,{spin:function(a){this.stop();var b,c,e=this,f=e.opts,g=e.el=h(d(0,{className:f.className}),{position:f.position,width:0,zIndex:f.zIndex}),i=f.radius+f.length+f.width;if(a&&(a.insertBefore(g,a.firstChild||null),c=j(a),b=j(g),h(g,{left:("auto"==f.left?c.x-b.x+(a.offsetWidth>>1):parseInt(f.left,10)+i)+"px",top:("auto"==f.top?c.y-b.y+(a.offsetHeight>>1):parseInt(f.top,10)+i)+"px"})),g.setAttribute("aria-role","progressbar"),e.lines(g,e.opts),!k){var l=0,m=f.fps,n=m/f.speed,o=(1-f.opacity)/(n*f.trail/100),p=n/f.lines;!function q(){l++;for(var a=f.lines;a;a--){var b=Math.max(1-(l+a*p)%n*o,f.opacity);e.opacity(g,f.lines-a,b,f)}e.timeout=e.el&&setTimeout(q,~~(1e3/m))}()}return e},stop:function(){var a=this.el;return a&&(clearTimeout(this.timeout),a.parentNode&&a.parentNode.removeChild(a),this.el=c),this},lines:function(a,b){function c(a,c){return h(d(),{position:"absolute",width:b.length+b.width+"px",height:b.width+"px",background:a,boxShadow:c,transformOrigin:"left",transform:"rotate("+~~(360/b.lines*i+b.rotate)+"deg) translate("+b.radius+"px,0)",borderRadius:(b.corners*b.width>>1)+"px"})}for(var g,i=0;i<b.lines;i++)g=h(d(),{position:"absolute",top:1+~(b.width/2)+"px",transform:b.hwaccel?"translate3d(0,0,0)":"",opacity:b.opacity,animation:k&&f(b.opacity,b.trail,i,b.lines)+" "+1/b.speed+"s linear infinite"}),b.shadow&&e(g,h(c("#000","0 0 4px #000"),{top:"2px"})),e(a,e(g,c(b.color,"0 0 1px rgba(0,0,0,.1)")));return a},opacity:function(a,b,c){b<a.childNodes.length&&(a.childNodes[b].style.opacity=c)}}),function(){function a(a,b){return d("<"+a+' xmlns="urn:schemas-microsoft.com:vml" class="spin-vml">',b)}var b=h(d("group"),{behavior:"url(#default#VML)"});!g(b,"transform")&&b.adj?(n.addRule(".spin-vml","behavior:url(#default#VML)"),p.prototype.lines=function(b,c){function d(){return h(a("group",{coordsize:j+" "+j,coordorigin:-i+" "+-i}),{width:j,height:j})}function f(b,f,g){e(l,e(h(d(),{rotation:360/c.lines*b+"deg",left:~~f}),e(h(a("roundrect",{arcsize:c.corners}),{width:i,height:c.width,left:c.radius,top:-c.width>>1,filter:g}),a("fill",{color:c.color,opacity:c.opacity}),a("stroke",{opacity:0}))))}var g,i=c.length+c.width,j=2*i,k=2*-(c.width+c.length)+"px",l=h(d(),{position:"absolute",top:k,left:k});if(c.shadow)for(g=1;g<=c.lines;g++)f(g,-2,"progid:DXImageTransform.Microsoft.Blur(pixelradius=2,makeshadow=1,shadowopacity=.3)");for(g=1;g<=c.lines;g++)f(g);return e(b,l)},p.prototype.opacity=function(a,b,c,d){var e=a.firstChild;d=d.shadow&&d.lines||0,e&&b+d<e.childNodes.length&&(e=e.childNodes[b+d],e=e&&e.firstChild,e=e&&e.firstChild,e&&(e.opacity=c))}):k=g(b,"animation")}(),"function"==typeof define&&define.amd?define(function(){return p}):a.Spinner=p}(window,document);