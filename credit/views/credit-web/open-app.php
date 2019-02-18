<body>
	<div class="forward-app">
		<a id="openapp" href="xianjincard://com.kdlc.mcc/openapp" style="text-decoration:none;">点此打开</a>
	</div>
</body>

<script type="text/javascript">
	$(function(){
		// main();

		function main(){
			var browser={
			    versions:function(){
			            var u = navigator.userAgent, app = navigator.appVersion;
			            return {         //移动终端浏览器版本信息
			                trident: u.indexOf('Trident') > -1, //IE内核
			                presto: u.indexOf('Presto') > -1, //opera内核
			                webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
			                gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
			                mobile: !!u.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
			                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
			                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
			                iPhone: u.indexOf('iPhone') > -1 , //是否为iPhone或者QQHD浏览器
			                iPad: u.indexOf('iPad') > -1, //是否iPad
			                webApp: u.indexOf('Safari') == -1 //是否web应该程序，没有头部与底部
			            };
			         }(),
			         language:(navigator.browserLanguage || navigator.language).toLowerCase()
			}
			var clickedAt = +new Date;
			
			if (browser.versions.ios == true) {
				// $("#openapp").attr("href", "xjbk915164674://");
		        // setTimeout(function () {
		        //     !window.document.webkitHidden && setTimeout(function () {
		        //         if (+new Date - clickedAt < 1000) {
		        //             window.location.href = "xianjincard://com.kdlc.mcc/openapp";
		        //         }
		        //     }, 500);
		        // }, 500);
		        window.location = "https://api.kdqugou.com/download-app.html";
			}else if(browser.versions.android == true){
				alert("1111");
				$("#openapp").attr("href", "xianjincard://com.kdlc.mcc/openapp");
				// setTimeout(function () {
		            // !window.document.webkitHidden && setTimeout(function () {
		            // 	if (+new Date - clickedAt < 1000) {
		            // 		alert("测试地址");
		            //         window.location.href = "xianjincard://com.kdlc.mcc/openapp";
		            //     }
		            // }, 500);
		            // alert("xianjincard://com.kdlc.mcc/openapp");
		            // if (+new Date - clickedAt < 1000) {
		            	window.location.href = "xianjincard://com.kdlc.mcc/openapp";
		            // }
		        // }, 500);
			}else{
				// setTimeout(function () {
				// 	if (+new Date - clickedAt < 1000) {
	   //                  window.location.href = "https://api.kdqugou.com/download-app.html";
	   //              }
				// },500);
			}

			
		}

		$("#openapp").on('click',function(e){
				e.preventDefault();
				// var clickedAt = +new Date;
				// $("#openapp").attr("href", "xianjincard://com.kdlc.mcc/openapp");
				// // alert("xianjincard://com.kdlc.mcc/openapp");
				// // if (browser.versions.android == true) {

				// if (+new Date - clickedAt < 500) {
				// 	alert(+new Date - clickedAt);
				// 	alert("xianjincard://com.kdlc.mcc/openapp");
				// 	window.location.href = "xianjincard://com.kdlc.mcc/openapp";
				// }
				// // }else{
				// // 	window.location.href = "https://api.kdqugou.com/download-app.html";
				// // }
				// // return false;

				window.location.href = 'xianjincard://com.kdlc.mcc/openapp';
			    t = Date.now();
			    setTimeout(function(){
			        if (Date.now() - t < 1200) {
			            location.href = 'xianjincard://com.kdlc.mcc/openapp';
			        }
			    }, 1000);
			    return false;

				// var ifr = document.createElement('iframe');
				// ifr.src = 'xianjincard://com.kdlc.mcc/openapp';
				// ifr.style.display = 'none';
				// document.body.appendChild(ifr);
				// var openTime = +new Date();
				// window.setTimeout(function(){
				//     document.body.removeChild(ifr);
				//     if( (+new Date()) - openTime > 2500 ){
				//         window.location = 'https://api.kdqugou.com/download-app.html';
				//     }
				// },2000);
			});
	})	

</script>
