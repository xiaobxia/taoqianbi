/**
 * 后台公用js
 */

// 原生方式通过id获取dom，代替discuz的$方法
function $id(id) {
	return !id ? null : document.getElementById(id);
}

// 弹出确认是否继续操作提示
function confirmMsg(msg) {
	if (confirm(msg)) {
		return true;
	} else {
		return false;
	}
}

// 跳转
function redirect(url) {
	window.location.replace(url);
}

// 切换导航锚点，控制选中显示，非选中隐藏
function showanchor(obj) {
	var navs = $id('submenu').getElementsByTagName('li');
	for(var i = 0; i < navs.length; i++) {
		if(navs[i].id.substr(0, 4) == 'nav_' && navs[i].id != obj.id) {
			if($id(navs[i].id.substr(4))) {
				navs[i].className = '';
				$id(navs[i].id.substr(4)).style.display = 'none';
				if($id(navs[i].id.substr(4) + '_tips')) $id(navs[i].id.substr(4) + '_tips').style.display = 'none';
			}
		}
	}
	obj.className = 'current';
	currentAnchor = obj.id.substr(4);
	$id(currentAnchor).style.display = '';
	if($id(currentAnchor + '_tips')) $id(currentAnchor + '_tips').style.display = '';
	if($id(currentAnchor + 'form')) {
		$id(currentAnchor + 'form').anchor.value = currentAnchor;
	} else if($id('cpform')) {
		$id('cpform').anchor.value = currentAnchor;
	}
}

function confirmRedirect(msg, url) {
	if(confirmMsg(msg)){
        window.location.href = url;
        return true;
    }else{
        return false;
    }
}


/**
 * 限制只能输入纯数字(不带小数)
 * @param {} event
 */
function onlyNumKeyUp(event, o) {
    if (event.keyCode >= 48 && event.keyCode <= 57 || event.keyCode >= 96 && event.keyCode <= 105 || event.keyCode == 8) {
        //合法输入
    }
    else if (event.keyCode == 37 || event.keyCode == 39) {
        // 左、右
    }
    else {
        o.value = o.value.replace(/\D/g, '');
        return;
    }
}


//限制文本框只能输入数字（带两位小数）
function clearNoNum(obj){
    //先把非数字的都替换掉，除了数字和.
    obj.value = obj.value.replace(/[^\d.]/g,"");
    //必须保证第一个为数字而不是.
    obj.value = obj.value.replace(/^\./g,"");
    //保证只有出现一个.而没有多个.
    obj.value = obj.value.replace(/\.{2,}/g,".");
    //保证.只出现一次，而不能出现两次以上
    obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");

    //把小数点后面超过两位的数字替换掉，也就是保留两位小数
    var strs = "";
    var midd = "";
    var count = 0;
    for(var i=0;i<obj.value.length;i++){
        if(obj.value.charAt(i) == "."){
            midd = "start";
        }
        if(midd == "start"){
            count++;
        }
        if(count == 4){
            break;
        }
        strs += obj.value.charAt(i);
    }
    obj.value = strs;
}