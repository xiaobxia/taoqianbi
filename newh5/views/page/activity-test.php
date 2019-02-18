<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8"/>
    <meta name="viewport" content="initial-scale=1, maximum-scale=3, minimum-scale=1, user-scalable=no">
    <title>H5跳原生测试</title>
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="cache-control" content="private">
    <script>
		function skipType() {
            try {
				var text = document.getElementById('typedata').value;
				var jsonStr='{"type":"'+text+'"}';
                msg(jsonStr);
				nativeMethod.returnNativeMethod(jsonStr);
            } catch (e) {
            }
        }
		function skipJson() {
            try {
				var text = document.getElementById('jsondata').value;
				msg(text);
                nativeMethod.returnNativeMethod(text);
            } catch (e) {
            }
        }
		function msg(data) {
            document.getElementById('msg').value=data+"\n"+document.getElementById('msg').value;
        }
	</script>
	<style>
	input[type="text"]{
		box-sizing: border-box;
		text-align:center;
		font-size:1.4em;
		height:2.7em;
		border-radius:4px;
		border:1px solid #c8cccf;
		color:#6a6f77;
		-web-kit-appearance:none;
		-moz-appearance: none;
		display:block;
		outline:none;
		padding:0 1em;
		text-decoration:none;
		width:100%;
	}
	textarea{
		box-sizing: border-box;
		font-size:1.4em;
		border-radius:4px;
		border:1px solid #c8cccf;
		color:#6a6f77;
		-web-kit-appearance:none;
		-moz-appearance: none;
		display:block;
		outline:0;
		padding:2px;
		text-decoration:none;
		width:100%;
	}
	button{
		width: 100%;
		line-height: 38px;
		text-align: center;
		font-weight: bold;
		color: #fff;
		background:#149cf1;
		border-radius: 5px;
		position: relative;
		overflow: hidden;
	}
    .ab{
        width: 100%;
        height: 50px;
        text-align: center;
        font-weight: bold;
        color: #fff;
        background:#149cf1;
        border-radius: 5px;
    }
	</style>
</head>
<body>
<a href="koudaikj://app.launch/login/applogin"><div class="ab">登录测试</div></a>
<br>
<a href="xybt://com.xybaitiao/openapp"><div class="ab">唤醒测试BT</div></a>
<br>
<a href="wzdailoan://com.wzdai/openapp"><div class="ab">唤醒测试WZD</div></a>
<br>
<a href="hbqb://com.hbqb/openapp"><div class="ab">唤醒测试HBQB</div></a>
<br>
<a href="market://details?id=com.wzdai.xybt"><div class="ab">唤醒测试市场</div></a>
<br>
<a href="mhk://com.xybt.creditcard/openapp"><div class="ab">唤醒秒还卡</div></a>
<br>
<a href="mhk://com.xybt.creditcard/openapp?pushdata={type:1}"><div class="ab">唤醒秒还卡1</div></a>
<br>
<a href="mhk://com.xybt.creditcard/openapp?pushdata={type:10,url=https://www.baidu.com}"><div class="ab">唤醒秒还卡10</div></a>
<H5>Type跳转</H5>
<input type="text"  id="typedata" />
<button type="button" onclick="skipType()">跳转</button>
<H5>JSON跳转</H5>
<textarea id="jsondata" cols=40 rows=4 ></textarea>
<button type="button" onclick="skipJson()">跳转 </button>
<H5>记录</H5>
<textarea id="msg" cols=40 rows=20 ></textarea>
</body>
</html>
