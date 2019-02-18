//口袋宝交易记录
function KdbTradeRecord(url,page){
    if(!page) page = 1;
    var myDate = new Date();
    var date = myDate.getFullYear() + '-' + (myDate.getMonth()+1) + '-01';
    var html = '';
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        data : {page:page},
        success:function(data){
            if( data.code == 0){
                $.each(data.data,function(index,value){
                    html +='<a href="'+kdb_url_redirect+'"><div class="padding">';
                    html +='<div class="f_left a_left">';
                    html +='<p class="_000 em_1">口袋宝</p>';
                    html +='<p class="_666 em__8">'+value.time+'</p></div>';
                    html +='<div class="f_right a_right">';
                    html +='<p class="_000 em_1">'+(value.invest_money/100).toFixed(2)+'</p>';
                    html +='<p class="_666 em__8">'+value.status+'</p></div>';
                    html +='<div class="clear"></div></div></a>';
                });
                if (data.data.length == 0 && page == 1){
                    $('#trade_record_wraper #kdb').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">暂无数据</p></div>');
                }else{
                    if( page == 1 ){
                        $('#trade_record_wraper #kdb').html(html);
                    }else{
                        $('#trade_record_wraper #kdb').append(html);
                    }
                }
            }else{
                $('#trade_record_wraper #kdb').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">数据加载失败 . . .</p></div>');
            }
        }
    });
}
//项目交易记录
function projectTradeRecord(url,page){
    if(!page) page = 1;
    var myDate = new Date();
    var date = myDate.getFullYear() + '-' + (myDate.getMonth()+1) + '-01';
    var html = '';
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        data : {page:page},
        success:function(data){
            if( data.code == 0){
                $.each(data.data,function(index,value){
                    html +='<a href="'+pro_url_redirect+'?project_id='+value.project_id+'"><div class="padding">';
                    html +='<div class="f_left a_left">';
                    html +='<p class="_000 em_1">'+value.name+'</p>';
                    html +='<p class="_666 em__8">'+value.time+'</p></div>';
                    html +='<div class="f_right a_right">';
                    html +='<p class="_000 em_1">'+(value.invest_money/100).toFixed(2)+'</p>';
                    html +='<p class="_666 em__8">'+value.status+'</p></div>';
                    html +='<div class="clear"></div></div></a>';
                });
                if (data.data.length == 0 && page == 1){
                    $('#trade_record_wraper #project').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">暂无数据</p></div>');
                }else{
                    if( page == 1 ){
                        $('#trade_record_wraper #project').html(html);
                    }else{
                        $('#trade_record_wraper #project').append(html);
                    }
                }
            }else{
                $('#trade_record_wraper #project').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">数据加载失败 . . .</p></div>');
            }
        }
    });
}
//累计收益详情
function cumulativeGain(url,page){
    if(!page) page = 1;
    var html = '';
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        data : {page:page},
        success:function(data){
            if( data.code == 0){
                $.each(data.profits,function(index,value){
                    html +='<div class="em__8 padding">';
                    html +='<div class="f_left a_left">';
                    html +='<p class="_000 em_1_2">'+value.project_name+'</p></div>';
                    html +='<div class="f_right a_right">';
                    html +='<p class="_666 em_1_2">'+(value.profits / 100).toFixed(2)+'</p></div>';
                    html +=' <div class="clear"></div></div>';
                });
                if (data.profits.length == 0 && page == 1){
                    $('#cumulative_gain_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">暂无数据</p></div>');
                }else{
                    if( page == 1 ){
                        $('#cumulative_gain_wraper #title').html((data.data.total_profits / 100).toFixed(2));
                        $('#cumulative_gain_wraper #list').html(html);
                    }else{
                        $('#cumulative_gain_wraper #list').append(html);
                    }
                }
            }else{
                $('#cumulative_gain_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">数据加载失败 . . .</p></div>');
            }
        }
    });
}
//用户资金主页
function getAccountHome(url){
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0){
                $("#balance_wraper #title").html((data.data.remain_money / 100 ).toFixed(2) +"<span class=\"unit\">元</span>");
                $("#balance_wraper #withdrawing_money").html((data.data.withdrawing_money / 100).toFixed(2));
                $("#balance_wraper #usable_money").html((data.data.usable_money / 100).toFixed(2));
                $("#kd_wraper #total_money").html((data.data.total_money / 100).toFixed(2));
                $("#kd_wraper #remain_money").html((data.data.remain_money / 100).toFixed(2));
                $("#kd_wraper #hold_money").html((data.data.hold_money / 100).toFixed(2));
                $("#kd_wraper #title").html((data.data.lastday_profits / 100).toFixed(2));
            }
        }
    });
}
// 转出
function rollOut(url,url_redirect){
    var money = $('#money').val();
    var pay_password = $('#pay_password').val();
    if( !money ){
        $('#msg').html("请输入金额");
        return false;
    }
    if( !pay_password ){
        $('#msg').html("请输入交易密码");
        return false;
    }
    $('#btn').css("background","#fd5353");
    $('#msg').html("&nbsp;&nbsp;");
    var params = {
        money : money,
        pay_password : pay_password,
    };
    KD.util.post(url, params, function(data){
        if (data.code == 0){
            var params = "?type=roll-out&money="+data.result.money+"&counter_fee="+0+"&created_at="+data.result.created_at;
            window.location.href = url_redirect + params;
        }else{
            if( money == 0 ){
                $('#msg').html("你还没有转出金额");
                return false;
            }else{
                $("#msg").html(data.message);
                return false;
            }
        }
    });
}
//实名认证
function userRealNameVertify(url,realname,id_card,url_redirect){
    var params = {
        realname : realname,
        id_card : id_card
    };
    KD.util.post(url, params, function(data){
        if(data.code == 0){
            window.localStorage.setItem("real_verify_status",1);
            window.location.href = url_redirect;
        }else{
            $("#msg").html(data.message);
        }
    });
}
//修改登录密码,修改交易密码
function changePwd(url){
    if( !$('#new_pwd').val() || !$('#old_pwd').val() || !$('#rep_new_pwd').val() ){
        $('#msg').html("密码不能为空");
        return false;
    }
    if( $('#new_pwd').val() != $('#rep_new_pwd').val() ){
        $('#msg').html("两次密码不一致");
        return false;
    }
    $('#msg').html("&nbsp;&nbsp;");
    $('#btn').css("background","#fd5353");
    var params = {
        'old_pwd' : $('#old_pwd').val(),
        'new_pwd' : $('#new_pwd').val(),
    };
    KD.util.post(url, params, function(data){
        if (data.code == 0){
            showExDialog('密码修改成功',"确认","urlRedirect");
        }else{
            $('#msg').html(data.message);
        }
    });
}

//口袋宝详情
function kdbInfo(url){
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0){
                $("#kdb_info_wraper #apr").html(data.info.apr);
                $("#kdb_info_wraper #title").html(data.info.title);
                $("#kdb_info_wraper #min_invest_money").html(data.info.min_invest_money/100);
                $("#kdb_info_wraper #summary").html(data.info.summary);
                var invest_kdb_profits = $("#invest_kdb_money").attr("placeholder") * data.info.apr * $("#invest_kdb_days").attr("placeholder") / 36500;
                invest_kdb_profits = invest_kdb_profits.toFixed(2);
                $("#invest_kdb_profits").val(invest_kdb_profits);
            }
        }
    });
}
//项目详情
function newProjectDetail(url){
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0){
                $("#project_detail_wraper #apr").html(data.project.apr);
                $("#project_detail_wraper #invest_project_days").attr("placeholder",data.project.period).next().html(data.project.is_day ? '天' : '月');
                $("#project_detail_wraper #period").html(data.project.period + (data.project.is_day ? '天' : '月'));
                $("#project_detail_wraper #title").html(data.project.name);
                if( data.project.is_novice == 1 ){
                    $("#project_detail_wraper #is_novice").html("新手专享：新注册用户第一次购买本产品，平台会额外贴息1%，只能购买一次。");
                }
                $("#project_detail_wraper #min_invest_money").html(data.project.min_invest_money/100);
                $("#project_detail_wraper #summary").html(data.project.summary);
                $("#project_detail_wraper #early_invest_word").html(data.project.early_invest_word);
                $("#project_detail_wraper #max_invest_word").html(data.project.max_invest_word);
                $("#project_detail_wraper #last_invest_word").html(data.project.last_invest_word);
                var invest_project_profits = $("#invest_project_money").attr("placeholder") * data.project.apr * $("#invest_project_days").attr("placeholder") / 36500;
                invest_project_profits = invest_project_profits.toFixed(2);
                $("#invest_project_profits").val(invest_project_profits);
            }
        }
    });
}
//投资记录列表
function projectInvestList(url,page){
    if(!page) page = 1;
    var html = '';
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        data : {page:page},
        success:function(data){
            if( data.code == 0){
                $.each(data.invests,function(index,value){
                    html +='<div class="em__8 padding">';
                    html +='<div class="f_left a_left">';
                    html +='<p class="_000 em_1_2">'+value.username+'</p>';
                    html +='<p class="_666 em_1">'+UnixToDate(value.created_at,true)+'</p></div>';
                    html +='<div class="f_right a_right">';
                    html +='<p class="_666 em_1_2">'+(value.invest_money / 100).toFixed(2)+'</p></div>';
                    html +=' <div class="clear"></div></div>';
                });
                if ( !data.pages.totalCount ){
                    $('#project_invest_list_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">暂无数据</p></div>');
                }else{
                    if( page == 1 ){
                        $('#project_invest_list_wraper #title').html('已购'+data.pages.totalCount + '人');
                        $('#invest_num').html(data.pages.totalCount);
                        $('#project_invest_list_wraper #list').html(html);
                    }else{
                        $('#project_invest_list_wraper #list').append(html);
                    }
                }
            }else{
                $('#project_invest_list_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">数据加载失败 . . .</p></div>');
            }
        }
    });
}
//拉优惠券列表
function projectVoucherList(url,pid,money){
    var html = '';
    $.ajax({
        url : url,
        type: 'GET',
        data : {
            project_id : pid,
            money : money
        },
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0)
            {
                $("#voucher_name").html("--请选择--");
                for(var i in data.data){
                    if(i==0){
                        html += '<li class="lh_em_2" data="0">&nbsp;&nbsp;不使用</li>';
                    }
                    html += '<li class="lh_em_2" data="'+data.data[i].id+'">&nbsp;&nbsp;'+data.data[i].deduction_info+'</li>';
                };
            } else if(data.code == -1){
                $("#voucher_name").html(data.message);
            }else {
                $("#msg").html(data.message);
            }
            $("#voucher_list").html(html).css("margin-top","-1px");
            $("#voucher_list li").toggleClass("_hidden");
        }
    });
}

//拉取银行列表
function getBankList(url,base_url){
    var bank_list = {
        1 : "gongshang",
        2 : "nongye",
        3 : "guangda",
        4 : "youzheng",
        5 : "xingye",
        6 : "shenfa",
        7 : "jianshe",
        8 : "zhaoshang",
        9 : "zhongguo",
       10 : "pufa",
       11 : "pingan",
       12 : "huaxia",
       13 : "zhongxin",
       14 : "jiaotong",
       15 : "minsheng",
       16 : "guangfa"
    };
    var bank_list_name = {
        "1" : "工商银行",
        "2" : "农业银行",
        "3" : "光大银行",
        "4" : "邮政储蓄银行",
        "5" : "兴业银行",
        "6" : "深圳发展银行",
        "7" : "建设银行",
        "8" : "招商银行",
        "9" : "中国银行",
        "10" : "浦发银行",
        "11" : "平安银行",
        "12" : "华夏银行",
        "13" : "中信银行",
        "14" : "交通银行",
        "15" : "民生银行",
        "16" : "广发银行"
    };
    var params = {};
    var html = '';
    KD.util.post(url, params, function(data){
        if(data.code == 0){
            for(var i in data.banks){
                html += '<div class="padding input_wrap _cursor list" bankName="'+bank_list_name[data.banks[i].code]+'" bankId="'+data.banks[i].code+'">';
                html += '<span class="_inline_block lh_em_1_5 em__9 _000 v_center"><img src="'+base_url+'/image/account/bank_logo/logo_'+bank_list[data.banks[i].code]+'@2x.png" width="80%"></span>';
                html += '<span class="_inline_block lh_em_1_5 em__9 _000 v_center">'+data.banks[i].name+'<br/>'+data.banks[i].restrict_desc+'</span></div>';
            }
            $("#bank_list").html(html);
            $("#bank_list .list").click(function(){
                $(".content").toggleClass("_hidden");
                chose_bank_id = $(this).attr("bankId");
                $(".choose_bank").html($(this).attr("bankName"));
            });
        }else{
            showExDialog(data.message,"确认");
        }
    });
}
//绑定银行卡
function bindCard(url,bank_id,card_no,pay_amount,source_action,url_post_confirm){
    var params = {
        'bank_id' : bank_id,
        'card_no' : card_no
    };

    $.post(url, params, function(data){
        if(data.code == 0){
            window.localStorage.setItem("set_paypwd_status",1);
            rechargeConfirm(url_post_confirm,pay_amount,bank_id,card_no,source_action);
        }else{
            $("#msg").html("&nbsp;&nbsp;");
            showExDialog(data.message,"确认");
        }
    }, 'json');
}
//用户查看口袋券列表
function showVouchers(url,page){
    if(!page) page = 1;
    var html = '';
    var html1 = '';
    $.ajax({
        url : url,
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'callback',
        data : {page:page},
        success:function(data){
            if (data.code == 0){
                $.each(data.data,function(index,value){
                    if( value.status != 0 ){
                        //灰色
                        html1 += '<div class="column_wraper">';
                        html1 += '<div class="column to_void_voucher">';
                        html1 += '<span class="_inline_block f_left fff em_1 left a_center">'+value.voucher_tip+'</span>';
                        html1 += '<span class="_inline_block f_left a_center center">';

                        if(value.type == 1){
                            //带￥
                            html1 += '<p class="_999"><span class="em__1">￥</span><span class="em_3">'+value.deduction+'</span></p>';
                        }else if( value.type == 2 ){
                            //百分号 eg:1%
                            html1 += '<p class="_999 em_3">'+value.deduction+'</p>';
                        }else if( value.type == 3 ){
                            html1 += '<p class="_999"><span class="em__1">￥</span><span class="em_3">'+value.deduction+'</span></p>';
                        }
                        
                        html1 += '<p class="a7a7a7 em__8 a_left"><span>'+value.desc+'</span><br/><span class="clear em__8">'+value.title+'</span><span class="f_right em__8">'+value.expire_info+'</span></p>';
                        html1 += '</span>';
                        html1 += '<span class="_inline_block f_left fff em_2 right a_center">'+value.voucher_name+'</span>';
                        html1 += '<div class="clear"></div></div></div>';
                    }else{
                        if(value.type == 1){
                            //抵扣券
                            html += '<div class="column_wraper">';
                            html += '<div class="column useable_cash_voucher">';
                            html += '<span class="_inline_block f_left fff em_1 left a_center">'+value.voucher_tip+'</span>';
                            html += '<span class="_inline_block f_left a_center center">';
                            html += '<p class="fff"><span class="em__1">￥</span><span class="em_3">'+value.deduction+'</span></p>';
                            html += '<p class="fff3e3 em__8 a_left"><span>'+value.desc+'</span><br/><span class="clear em__8">'+value.title+'</span><span class="f_right em__8">'+value.expire_info+'</span></p>';
                            html += '</span>';
                            html += '<span class="_inline_block f_left fff em_2 right a_center">'+value.voucher_name+'</span>';
                            html += '<div class="clear"></div></div></div>';
                        }else if( value.type == 2 ){
                            //增益券
                            html += '<div class="column_wraper">';
                            html += '<div class="column useable_gain_voucher">';
                            html += '<span class="_inline_block f_left fff em_1 left a_center">'+value.voucher_tip+'</span>';
                            html += '<span class="_inline_block f_left a_center center">';
                            html += '<p class="fff em_3">'+value.deduction+'</p>';
                            html += '<p class="fedcdc em__8 a_left"><span>'+value.desc+'</span><br/><span class="clear em__8">'+value.title+'</span><span class="f_right em__8">'+value.expire_info+'</span></p>';
                            html += '</span>';
                            html += '<span class="_inline_block f_left fff em_2 right a_center">'+value.voucher_name+'</span>';
                            html += '<div class="clear"></div></div></div>';
                        }else if( value.type == 3 ){
                            //返现券
                            html += '<div class="column_wraper">';
                            html += '<div class="column return_cash_voucher">';
                            html += '<span class="_inline_block f_left fff em_1 left a_center">'+value.voucher_tip+'</span>';
                            html += '<span class="_inline_block f_left a_center center">';
                            html += '<p class="fff"><span class="em__1">￥</span><span class="em_3">'+value.deduction+'</span></p>';
                            html += '<p class="ffbd9d em__8 a_left"><span>'+value.desc+'</span><br/><span class="clear em__8">'+value.title+'</span><span class="f_right em__8">'+value.expire_info+'</span></p>';
                            html += '</span>';
                            html += '<span class="_inline_block f_left fff em_2 right a_center">'+value.voucher_name+'</span>';
                            html += '<div class="clear"></div></div></div>';
                        }
                    }
                });
                if (data.data.length == 0 && page == 1){
                        $('#voucher_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">暂无数据</p></div>');
                }else{
                    if( page == 1 ){
                        $('#voucher_wraper #list').html(html);
                        $('#voucher_wraper #list').append(html1);
                    }else{
                        $('#voucher_wraper #list').append(html);
                        $('#voucher_wraper #list').append(html1);
                    }
                    $("#voucher_wraper .column .left").css({"line-height": ($("#voucher_wraper .column").outerHeight()*0.77)/$("#voucher_wraper .column .left").html().length + 'px' });
                    $("#voucher_wraper .column .right").css({"line-height": ($("#voucher_wraper .column").outerHeight()*0.77)/$("#voucher_wraper .column .right").html().length + 'px'});
                    $("#voucher_wraper .column_wraper .left,#voucher_wraper .column_wraper .center,#voucher_wraper .column_wraper .right").height($(document.body).width() * 0.25).css({
                        "padding-top":"2%",
                        "padding-bottom":"2%"
                    });
                }
            }else{
                $('#voucher_wraper #list').html('<div class="padding" style="border:0 none;"><p class="_666 em__8 a_center">数据加载失败 . . .</p></div>');
            }
        }
    });
}
//用户设置支付密码
function userSetPayPassword(url_post,pay_pwd,url_redirect){
    var params = {
        'password' : pay_pwd
    };
    KD.util.post(url_post, params, function(data){
        if(data.code == 0){
            window.localStorage.setItem("set_paypwd_status",1);
            window.location.href = url_redirect;
        }else{
            showExDialog(data.message,"确认",'toRelname');
        }
    });
}
//口袋宝投资下单
function kdbInvestOrder(url_post,kdb_buy){
    var params = {
        'money' : kdb_buy
    };
    KD.util.post(url_post, params, function(data){
        if(data.code == 0){
            order_id = data.order_id;
        }else{
            showExDialog(data.message,"确认");
        }
    });
}
//口袋宝投资
function kdbInvest(url_post,use_remain,money,pay_password,order_id,sign,url_redirect){
    var params = {
        'use_remain' : use_remain,
        'money' : money,
        'pay_password' : pay_password,
        'order_id' : order_id,
        'sign' : sign
    };
    KD.util.post(url_post, params, function(data){
        if(data.code == 0){
            var params = "?type=kdb&project_name="+data.investInfo['invest']['project_name']+"&project_apr="+data.investInfo['invest']['apr']+"&project_money=" +
                data.investInfo['invest']['invest_money']+"&start_date="+data.investInfo['start']['date']+"&start_desc="+data.investInfo['start']['desc']+
                "&end_date="+data.investInfo['end']['date']+"&end_desc="+data.investInfo['end']['desc'];
            window.location.href = url_redirect + params;
        }else{
            showExDialog(data.message,"确认");
        }
    });
}
//项目详情 projectDetail
function projectDetail(url,project_id){
    $.ajax({
        url : url,
        type: 'GET',
        data : {
            id : project_id
        },
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0)
            {
                var p_i_i_money = data.project.increment_invest_money;
                var p_m_i_money = data.project.max_invest_money;
                $("#project_name").html(data.project.name);
                $("#project_profit").html("预期年化&nbsp; : &nbsp;" + data.project.apr + "%");
                if( p_m_i_money > 0 ){
                    $("#project_min_invest").html("单笔限购&nbsp; : &nbsp;" + p_m_i_money / 100 + "元");
                }else{
                    $("#project_min_invest").html("单笔限购&nbsp; : &nbsp; 不限购");
                }
                $("#project_remain").html("剩余金额&nbsp; : &nbsp;" + (data.project.total_money-data.project.success_money) / 100 + "元");
                $("#project_period").html("理财期限&nbsp; : &nbsp;" + data.project.period + "天");
                if( p_i_i_money > 0 ){
                    $("#project_buy_value").attr("placeholder",data.project.min_invest_money / 100+"起投  "+p_i_i_money / 100+"的整数倍");
                }else{
                    $("#project_buy_value").attr("placeholder",data.project.min_invest_money / 100+"起投  "+data.project.min_invest_money / 100+"的整数倍");
                }
                project = data.project;
            }
            else
            {
                showExDialog(data.message,"确认","back");
            }
        }
    });
}
//定期项目投资下单
function projectInvestOrder(url,voucher_id,money){
    var params = {
        'money' : money,
        'voucher_id' : voucher_id
    };
    KD.util.post(url, params, function(data){
        if(data.code == 0){
            project_order_id = data.order_id;
        }else{
            showExDialog(data.message,"确认");
        }
    });
}
//定期项目投资付费
function projectInvest(url,id,is_kdb_pay,money,voucher_id,pay_password,order_id,sign,redirect_url){
    var params = {
        'id': id,
        'is_kdb_pay' : is_kdb_pay,
        'money' : money,
        'voucher_id' : voucher_id,
        'pay_password' : pay_password,
        'order_id' : order_id,
        'sign' : sign
    };
    KD.util.post(url, params, function(data){
        if(data.code == 0){
            var project_params = "?type=project&project_name="+data.investInfo['invest']['project_name']+"&project_apr="+data.investInfo['invest']['apr']+"&project_money=" +
                data.investInfo['invest']['invest_money']+"&start_date="+data.investInfo['start']['date']+"&start_desc="+data.investInfo['start']['desc']+
                "&end_date="+data.investInfo['end']['date']+"&end_desc="+data.investInfo['end']['desc'];
            window.location.href = redirect_url + project_params;
        }else{
            showExDialog(data.message,"确认");
        }
    });
}
//点击充值验证输入
function chargeVerify(url_post,pay_amount,bank_id,card_no,url_post_confirm){
    var params = {
        'bank_id' : bank_id,
        'card_no' : card_no
    };

    $.post(url_post, params, function(data){
        if(data.code == 0){
            rechargeConfirm(url_post_confirm,pay_amount,bank_id,card_no);
        }else{
            showExDialog(data.message,"确认");
        }
    }, 'json');
    // KD.util.post(url_post, params, function(data){
    //     if(data.code == 0){
    //         rechargeConfirm(url_post_confirm,pay_amount,pay_password,bank_id,card_no);
    //     }else{
    //         alert(data.message);return false;
    //         showExDialog(data.message,"确认");
    //     }
    // });
}
//连连充值提交
function rechargeConfirm(url_post_confirm,pay_amount,bank_id,card_no,source_action){
    var source_action = source_action ? source_action : '';
    var params = {
        pay_amount : pay_amount,
        bank_id : bank_id,
        card_no : card_no,
        source_action : source_action,
    };
    //KD.util.post(url_post_confirm, params, function(data){
    //    if(data.code == 0){
    //    }else{
    //        showExDialog(data.message,"确认");
    //    }
    //});
    post(url_post_confirm,params);
}
function post(URL, PARAMS) {
    var temp = document.createElement("form");
    temp.action = URL;
    temp.method = "post";
    temp.style.display = "none";
    for (var x in PARAMS) {
        var opt = document.createElement("textarea");
        opt.name = x;
        opt.value = PARAMS[x];
        temp.appendChild(opt);
    }
    document.body.appendChild(temp);
    temp.submit();
    return temp;
}
function getUserUsableMoney(url){
    var params = {};
    KD.util.post(url, params, function(data){
        if(data.code == 0){
            user_usable_money = data.usable_money / 100;
        }else{
            showExDialog(data.message,"确认");
        }
    });
}

function yydbInvestRecode(url,indiana_more_id,page){
    if($('#recode_info').data('end')){
        $('#searchMore').html('<a class="fd5353" href="javascript:;">没有更多啦</a>');
    }
    $.ajax({
        url : url,
        type: 'GET',
        data : {
            'page' : page,
            'pageSize' : 10,
            'indiana_more_id' : indiana_more_id
        },
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            if (data.code == 0){
                if(data.list.length <= 0) {
                    $('#recode_info').data('end',1);
                    $('#searchMore').html('<a class="fd5353" href="javascript:;">没有更多啦</a>');
                }
                var html = '';
                // $('#invest_count').html('共'+data.pages.totalCount+'次');
                html += '<table width="100%" cellspacing="0" cellpadding="0">';
                $.each(data.list,function(index,value){
                    html += '<tr>';
                    html += '<td class="a_left"><p><span class="_000 em__8">'+value.username+'</span><br/><span class="_999 em__8">'+value.date+'</span></p></td>';
                    html += '<td class="a_right"><p class="lh_em_2_5"><span class="_999 em__9">参与'+value.count+'次</span></p></td>';
                    html += '</tr>';
                }); 
                html += '</table>';

                $('#recode_info').append(html);
            }
            else{
                showExDialog(data.message,"确认");
            }
        }
    });
}
function yydbFreeInvestPay(url,data,fn){
    $.ajax({
        url : url,
        type: 'POST',
        data : data,
        dataType: 'jsonp',
        jsonp: 'callback',
        success:function(data){
            typeof fn == 'function' && fn(data);
        }
    });
}