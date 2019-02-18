<?php
use common\helpers\Url;

?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
    .son td{border:grey solid 1px}
    .son {text-align: center}

    .report-container {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        overflow-y: auto;
    }

    .report-mask {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, .8);
        opacity: 0.7;
    }

    .msk-detail {
        position: absolute;
        top: 0;
        bottom: 0;
        opacity: 0;
        background-color: #000;
        display: none;
        z-index: 998;
        right: 480px;
        left: 109px;
    }

    .fl {
        float: left;
    }

    .fr {
        float: right;
    }

    table tr:hover {
        background-color: #f9fcff;
    }

    .container {
        position: absolute;
        top: 0;
        left: 50%;
        margin-left: -400px;
        background-color: #f9f9f9;
        padding: 0 24px;
        width: 892px;
        min-height: 100%;
    }

    .container .inner {
        width: 100%;
    }

    .report-a-close {
        position: fixed;
        background-color: #fff;
        width: 52px;
        height: 50px;
        font-size: 32px;
        line-height: 50px;
        text-align: center;
        margin-left: 916px;
        top: 0;
    }

    .report-a-close a {
        text-decoration: none;
        color: #ccc;
    }

    .header {
        font-size: 14px;
        overflow: hidden;
        padding: 25px 0;
        font-family: 'Microsoft Yahei';
    }

    .header-title {
        margin: 0 10px 0 0;
        vertical-align: top;
        font-size: 24px;
        display: inline-block;
        border-left: 4px solid #352bc1;
        height: 20px;
        padding-left: 10px;
        line-height: 20px;
        color: #333;
    }

    .header-report-application {
        display: inline-block;
        color: #666;
    }

    /*公共的border*/
    .module {
        margin-bottom: 8px;
        background: #fff;
    }

    .module > div {
        position: relative;
        z-index: 997;
        border: 1px solid #eee;
        background: transparent;
    }

    .module-title {
        padding: 20px 0 14px 0;
        border-bottom: 1px solid #eee;
    }

    .module-title h2 {
        font-size: 16px;
        height: 16px;
        line-height: 16px;
        color: #333;
        border-left: 4px solid #352bc1;
        margin-left: 24px;
        padding-left: 12px;
    }

    /*个人基本信息*/
    .rpi-subitem {
        overflow: hidden;
        padding: 20px 24px 0;
        font-size: 14px;
        color: #999;
    }

    .address-analysis .rpi-subitem .p-hidden {
        width: 400px;
        height: 18px;
        display: inline-block;
        line-height: 18px;
        overflow: hidden;
        margin-top: 0 !important;
        margin-bottom: 10px;
    }

    /*查看详情*/
    .a-detail {
        width: 480px;
        display: none;
        position: fixed;
        right: 0;
        top: 0;
        bottom: 0;
        background-color: #fff;
        box-shadow: 0 2px 16px rgba(0, 0, 0, .2);
        overflow-y: scroll;
        z-index: 9999;
    }

    .detail-a-close a {
        text-decoration: none;
        color: #999;
    }

    .detail-a-close {
        text-align: right;
        margin: 10px 10px 0 10px;
    }

    .detail-table {
        overflow-y: scroll;
    }

    .detail-table table {
        width: 96%;
        margin: 10px 2%;
        border-collapse: collapse;
        border: 1px solid #ccc;
    }

    .detail-table table td {
        border: 1px solid #ccc;
        height: 36px;
        font-size: 15px;
        line-height: 36px;
        color: #666;
    }

    .label-span {
        color: #666;
        word-break: break-all;
        display: inline-block;
    }

    .label {
        color: #999;
        font-size: 14px;
    }

    .jz, .table-href {
        color: #2ea5ff;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
    }

    .j-rpi-toggle-target {
        display: none;
    }

    .table-href:hover {
        opacity: .7;
        text-decoration: underline;
    }

    /*报告头部信息*/
    .risk-score {
        height: 178px;
        position: relative;
    }

    .result-score-wrap {
        width: 244px;
        height: 122px;
        position: absolute;
        top: 50%;
        left: 47px;
        margin-top: -61px;
    }

    .result-score-canvas-main {
        position: absolute;
    }

    .canvas-main {
        display: block;
        width: 244px;
        height: 122px;
    }

    .result-score-canvas-bg {
        position: absolute;
    }

    .result-score-text {
        top: 60%;
        left: 28px;
        width: 64px;
        margin-top: -42px;
        position: absolute;
    }

    .result-score {
        font-size: 32px;
        color: #333;
        text-align: center;
    }

    .result-cat {
        font-size: 14px;
        text-align: center;
        height: 23px;
        line-height: 23px;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
    }

    .result-text {
        margin-top: 60px;
        margin-left: 325px;
        float: left;
    }

    .result-text .result {
        font-size: 14px;
        margin-top: 12px;
    }

    .result-text p {
        line-height: 1;
    }

    .result-text a {
        display: inline-block;
        color: #2ea5ff;
        height: 18px;
        line-height: 18px;
        padding: 0 2px;
        border-bottom: 1px solid #2ea5ff;
        text-decoration: none;
    }

    .result-text .tip {
        font-size: 16px;
        font-weight: 700;
    }

    .result-cat.reject {
        color: #ff6c5c;
    }

    .result-cat.accept {
        color: #8cdb65;
    }

    .result-cat.review {
        color: #f8d436;
    }

    .col1 {
        width: 45%;
        text-align: right;
        padding-left: 10px;
        padding-right: 10px;
    }

    .col2 {
        padding-left: 10px;
        padding-right: 10px;
    }

    .dimension-section, .risk-detail-section {
        padding-top: 1px;
        color: #666;
    }

    .dimension-title, .risk-detail-title {
        display: inline-block;
        cursor: pointer;
        color: #2ea5ff !important;
    }

    .dimension-list, .risk-detail-list {
        display: none;
        list-style: none;
    }

    .risk-detail-list {
        list-style-type: disc;
    }

    .dimension-item {
        list-style-type: disc;
    }

    .header .report-application {
        margin-bottom: 2px;
    }

    .person-info .left, .person-info .right {
        display: inline-block;
        width: 300px;
        margin: 0 0 14px 0;
        height: 18px;
        line-height: 18px;
    }

    /*v4*/
    .description .rule-desc {
        margin-right: 10px;
        display: inline-block;
    }

    .description .rule-desc-detail {
        width: 400px;
        padding: 0;
        display: inline-block;
        vertical-align: top;
    }

    /* begin 侧面导航*/
    .left-nav {
        position: fixed;
        font-size: 14px;
        overflow-y: auto;
        width: 130px;
        z-index: 997;
        border-right: 1px solid #eee;
        height: 100%;
        background-color: #f9f9f9;
        margin-left: -155px;
    }

    .left-nav .nav-tab {
        color: #333;
        text-align: center;
        padding: 0 10px;
    }

    .left-nav .current-tab {
        color: white;
        background-color: #325bc1 !important;
    }

    .nav-tab span {
        display: block;
        border-bottom: 1px solid #eee;
        height: 100%;
        min-height: 50px;
    }

    .nav-tab span a {
        line-height: 29px;
        display: inline-block;
        vertical-align: middle;
        margin: 10px 0;
    }

    .nav-tab:hover:not(.current-tab) {
        background-color: #eee;
    }

    .current-tab span {
        border-bottom: none;
    }

    .current-tab:hover {
        opacity: 0.8
    }

    /*end 侧标导航*/
    /*设备信息*/
    .device-info {
        border-collapse: collapse;
        font-size: 14px;
        color: #666;
    }

    .device-info td {
        border: 1px solid #999;
        height: 40px;
        padding: 0 10px;
    }

    .device-info td.device-value {
        color: #333;
        width: 300px;
        word-break: break-all;
        word-wrap: break-word;
        line-height: 30px;
        text-align: left;
    }

    .device-info td.device-title {
        width: 130px;
        text-align: center;
    }

    /*最底层字段p的样式*/
    .module-subtitle {
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .module-subtitle h2 {
        font-size: 16px;
        margin: 0;
        height: 16px;
        color: #333;
        line-height: 16px;
    }

    /*通用*/
    .p-field {
        width: 418px;
        display: inline-block;
        margin: 0 0 20px 0;
        font-size: 14px;
    }

    /*三方数据*/
    .p-field-data {
        vertical-align: top;
        width: 45%;
        display: inline-block;
        margin: 0 0 20px 20px;
        font-size: 14px;
    }

    .p-field-title {
        color: #666;
    }

    .p-field-value {
        color: #333;
        word-break: break-all;
        word-wrap: break-word;
    }

    /*各模块报告的风险情况样式*/
    .table-wrap {
        padding: 10px;
    }

    .risk-table {
        border-collapse: collapse;
        font-size: 14px;
    }

    .risk-table th {
        line-height: 40px;
        color: #333;
        border: 1px solid #999;
        padding-left: 20px;
    }

    .risk-table .risk-col1 {
        text-align: center;
        width: 280px;
    }

    .risk-table .risk-col2 {
        text-align: left;
        width: 590px;
    }

    .risk-table td {
        border: 1px solid #999;
        color: #666;
        word-break: break-all;
        word-wrap: break-word;
    }

    td.risk-col1 {
        padding: 20px;
    }

    td.risk-col2 {
        padding: 20px 0 0 0;
    }

    .description .v3-table td {
        border: none;
    }

    .description {
        margin: 0 20px 10px 20px;
        font-size: 14px;
    }

    .description:first-child {
        display: inline-block;
    }

    .table-mark {
        color: #ff523f;
        margin-top: 0;
        margin-bottom: 20px;
        margin-left: 40px !important;
        padding: 0;
    }

    .name-list-ul {
        color: #ff523f;
        margin-top: 0;
        margin-bottom: 20px;
        margin-left: 40px !important;
        margin-right: 20px !important;
        padding: 0;
    }

    .overdue-ul li, .name-list-ul li {
        margin-bottom: 10px;
    }

    .name-list-ul .name-list-value {
        color: #666;
    }

    .dimension-sub-list li, .dimension-list li, .risk-detail-list li {
        margin-top: 10px;
    }

    .platform-ul li {
        margin-bottom: 10px;
    }

    /*法院详情*/
    .detail-table td {
        border: 1px solid #999;
        text-align: left;
    }

    hr.split-line {
        border: 0.5px dashed #999;
        margin-bottom: 20px;
    }
</style>
<script type="text/JavaScript">
    $.extend({
        showReport: function (e) {
            var i, c, n, r = {
                ANTIFRAUD: "贷前反欺诈",
                AUTHENTICATION: "信息核验",
                CREDIT: "授信定价",
                WITHDRAWALS: "提现",
                PREFILTER: "预筛",
                REGISTER: "注册",
                LOGIN: "登录",
                PRECREDIT: "预授信",
                LOANINGQUERY: "贷中检查",
                ADJUSTAMOUNT: "调额",
                CREDITTRADE: "信用交易",
                INFOANALYSIS: "信息解析",
                ENTPRELOAN: "企业贷前审核",
                RENT: "租赁",
                SURETY: "担保",
                ACCOUNT: "开户",
                INSURE: "投保",
                BACKGROUNDCHECK: "员工背调",
                MERCHANTPERMISSION: "商户准入",
                DEBTASSESS: "债权评估",
                ACTIVATE: "激活"
            }, s = {
                SUSPECTED_OF_WIPING_PHONE: "疑似刷机",
                SHORT_UPTIME: "开机时间过短",
                ABNORMAL_TIME: "本机时间异常",
                DEVICE_FIRST_SEEN: "设备首次出现",
                ABNORMAL_CARRIER_INFO: "运营商异常",
                ABNORMAL_NETWORK_CONNECTION: "网络信息异常",
                ILLEGAL_CLIENTID: "客户端ID异常",
                ANDROID_EMULATOR: "设备为模拟器",
                MULTIPLE_RUNNING: "多开",
                DEBUGGER_DETECTED: "检测到调试器",
                HOOK_TOOL_DETECTED: "检测到改机工具",
                DEVICE_INFO_TAMPERED: "设备参数被篡改",
                SUSPECTED_OF_FAKING_LOCATION: "疑似伪造基站定位",
                SUSPECTED_OF_FAKING_WIFI: "疑似伪造无线网络信息"
            }, t = {
                risk_type: "风险类型",
                evidence_time: "风险时间",
                overdue_amount: "逾期金额",
                overdue_counts: "逾期期数",
                overdue_days: "逾期天数",
                industry: "来源行业",
                subject_type: "主体类型",
                role: "角色",
                name: "被执行人姓名",
                sex: "性别",
                province: "省份",
                case_no: "案号",
                case_create_date: "立案时间",
                court_name: "执行法院",
                gist_id: "执行依据文号",
                judgment_duty: "生效法律文书确定的义务",
                gist_unit: "做出执行依据单位",
                disrupt_type_name: "失信被执行人行为具体情形",
                performance: "被执行人的履行情况",
                judgment_doc: "裁判文书",
                exec_amount: "执行标的",
                case_type: "案件类型",
                case_character: "案件性质",
                owe_tax_amount: "欠税金额",
                tax_type: "税费种",
                risk_reject_counts: "风险拒绝次数",
                related_risk_type: "相关风险类型",
                related_chain_score: "风险亲密度等级"
            }, a = (i = e, c = {}, n = [], $.each(i, function (e, i) {
                if (!c.id && i.id && (c.id = i.id), i.result_desc) for (var s in i.result_desc) if ("INFOANALYSIS" == s) $.extend(c, {INFOANALYSIS: i.result_desc.INFOANALYSIS}); else if (r[s] && i.result_desc[s].final_decision) {
                    var t = {};
                    if ("PASS" == i.result_desc[s].final_decision && (t.final_score = 0), t.report_name = s, t.report_display_name = r[s], $.extend(t, i.result_desc[s]), i.result_desc[s].output_fields) {
                        var a = [], l = i.result_desc[s].output_fields;
                        if (Array.isArray(l)) $.each(l, function (e, i) {
                            for (var s in i) a.push({field_name: vFiled, field_value: i[vFiled]})
                        }); else for (var d in l) a.push({field_name: d, field_value: l[d]});
                        $.extend(t, {output_fields: a})
                    }
                    n.push(t)
                }
            }), $.each(n, function (e, i) {
                var s = [], t = {};
                if (i.risk_items && i.risk_items[0] && "undefined" != i.risk_items[0] && undefined != i.risk_items[0].policy_name) {
                    for (var a in $.each(i.risk_items, function (e, i) {
                        t[i.policy_name] ? t[i.policy_name].push(i) : t[i.policy_name] = [i]
                    }), t) {
                        var l = {};
                        l.policy_decision = t[a][0].policy_decision, l.policy_name = a, l.policy_score = t[a][0].policy_score, l.policy_mode = t[a][0].policy_mode, l.risk_items = t[a], s.push(l)
                    }
                    $.extend(i, {policy_set: s})
                }
            }), $.extend(c, {module_report: n}), c);
            !function () {
                var s = !1;
                if ($.each($("link"), function (e, i) {
                    -1 < $(i).prop("href").indexOf("css/tdstyle.1.0.css") && (s = !0)
                }), !s) {
                    var t;
                    $.each($("script"), function (e, i) {
                        var s = $(i).prop("src").indexOf("tdreportv4");
                        -1 < s && (t = $(i).prop("src").substring(0, s) + "css/tdstyle.1.0.css?r=" + (new Date).getTime())
                    }), $("head").append('<link rel="stylesheet" href="' + t + '"/>')
                }
            }();
            template.helper("dealDisplay", function (e) {
                return "boolean" == typeof e ? e ? "是" : "否" : e || "-"
            }), template.helper("dealTime", function (e) {
                var i;
                if (e) {
                    var s;
                    s = 16 == (e = e.toString()).length ? e.substr(0, e.length - 3) : e;
                    var t = new Date(parseInt(s)), a = t.getFullYear(), l = t.getMonth() + 1, d = t.getDate(),
                        c = t.getHours(), n = t.getMinutes(), r = t.getSeconds();
                    i = a + "-" + (1 == l.toString().length ? "0" + l : l) + "-" + (1 == d.toString().length ? "0" + d : d) + " " + (1 == c.toString().length ? "0" + c : c) + ":" + (1 == n.toString().length ? "0" + n : n) + ":" + (1 == r.toString().length ? "0" + r : r)
                } else i = "-";
                return i
            }), template.helper("dealTimeLong", function (e) {
                var i;
                if (e) {
                    var s = e / 1e3 / 60 / 60 / 24, t = Math.floor(s), a = e / 1e3 / 60 / 60 - 24 * t, l = Math.floor(a),
                        d = e / 1e3 / 60 - 1440 * t - 60 * l, c = Math.floor(d),
                        n = e / 1e3 - 86400 * t - 3600 * l - 60 * c;
                    i = t + "天" + l + "时" + c + "分" + Math.floor(n) + "秒"
                } else i = "-";
                return i
            }), template.helper("dealMemory", function (e) {
                return function (e) {
                    {
                        if (e) {
                            if (!/^\d+$/.test(e)) return e;
                            for (var i = 0, s = e / 1; 1024 <= s;) s /= 1024, i++;
                            s = s.toFixed(2);
                            var t = "";
                            switch (i) {
                                case 0:
                                    t = " Bytes";
                                    break;
                                case 1:
                                    t = " KB";
                                    break;
                                case 2:
                                    t = " MB";
                                    break;
                                case 3:
                                    t = " GB";
                                    break;
                                case 4:
                                    t = " TB"
                            }
                            return s += t
                        }
                        return "-"
                    }
                }(e)
            }), template.helper("dealAbnormalTags", function (e) {
                var i = [];
                return e.forEach(function (e) {
                    i.push(s[e])
                }), i.join(",")
            }), template.helper("riskDetailDisplay", function (e) {
                if (e) {
                    var i = [];
                    for (var s in t) i.push(s);
                    return template.compile('{{if risk_details && risk_details.length>0}} {{each risk_details as risk_detail risk_detail_index}} {{if risk_detail.type=="discredit_count"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark overdue-ul"> {{if risk_detail.discredit_times}} <li>平台个数: {{risk_detail.platform_count}}</li><li>逾期次数: {{risk_detail.discredit_times}}</li> {{if risk_detail.overdue_details && risk_detail.overdue_details.length>0}} {{each risk_detail.overdue_details as overdue overdue_index}} <li>逾期金额: {{overdue.overdue_amount_range}} 逾期笔数: {{overdue.overdue_count}} 逾期天数: {{overdue.overdue_day_range}} 逾期入库时间: {{overdue.overdue_time}} </li> {{/each}} {{/if}} {{/if}} </ul> {{/if}} {{if risk_detail.type=="custom_list"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{if risk_detail.high_risk_areas && risk_detail.high_risk_areas.length>0}} <li>高风险较为集中地区:{{#risk_detail.high_risk_areas | dealArray}}</li> {{else if risk_detail.hit_list_datas && risk_detail.hit_list_datas.length>0}} <li>命中列表数据:{{#risk_detail.hit_list_datas | dealArray}}</li> {{/if}} </ul> {{/if}} {{if risk_detail.type=="platform_detail"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark platform-ul"> {{if risk_detail.platform_detail}} <li style="list-style: none; margin-left: -16px;"> 总个数：{{risk_detail.platform_count}}</li> {{each risk_detail.platform_detail as platform platform_index}} <li>{{platform.industry_display_name}}：{{platform.count}}</li> {{/each}} {{/if}} {{if risk_detail.platform_detail_dimension && risk_detail.platform_detail_dimension.length>0}} <div class="dimension-section"><span class="dimension-title">各维度多头详情</span><ul class="dimension-list"> {{each risk_detail.platform_detail_dimension as dimension dimension_index}} <li class="dimension-item"><span class="dimension-item-title">{{dimension.dimension}}：</span><ul class="dimension-sub-list"><li>总个数：{{dimension.count}}</li> {{each dimension.detail as item item_index}} <li class="dimension-sub-item"> {{item.industry_display_name}}：{{item.count}}</li> {{/each}} </ul></li> {{/each}} </ul></div> {{/if}} </ul> {{/if}} {{if risk_detail.type==\'frequency_detail\'}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{if risk_detail.frequency_detail_list && risk_detail.frequency_detail_list.length>0}} <div class="risk-detail-section"><span class="risk-detail-title">频度规则详情</span><ul class="risk-detail-list"> {{each risk_detail.frequency_detail_list as detail detail_index}} <li class="risk-detail-item"><span class="risk-detail-item-title"> {{detail.detail}} </span><ul class="risk-detail-sub-list"> {{each detail.data as data data_index}} <li class="risk-detail-sub-item">{{data}}</li> {{/each}} </ul></li> {{/each}} </ul></div> {{/if}} </ul> {{/if}} {{if risk_detail.type=="cross_frequency_detail"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{if risk_detail.cross_frequency_detail_list && risk_detail.cross_frequency_detail_list.length>0}} <div class="risk-detail-section"><span class="risk-detail-title">跨事件频度规则详情</span><ul class="risk-detail-list"> {{each risk_detail.cross_frequency_detail_list as detail defail_index}} <li class="risk-detail-item"><span class="risk-detail-item-title"> {{detail.detail}} </span></li><ul class="risk-detail-sub-list"> {{each detail.data as data data_index}} <li class="risk-detail-sub-item">{{data}}</li> {{/each}} </ul> {{/each}} </ul></div> {{/if}} </ul> {{/if}} {{if risk_detail.type=="suspected_team"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{if risk_detail.suspect_team_detail_list && risk_detail.suspect_team_detail_list.length>0}} <div class="dimension-section"><span class="dimension-title">复杂网络各维度详情</span> {{each risk_detail.suspect_team_detail_list as suspect_team_detail s_t_d_index}} <ul class="dimension-list"> {{if suspect_team_detail.dim_value}} <li class="dimension-item"><span><span class="dimension-item-title">匹配字段值:</span><span class="dimension-sub-item"> {{suspect_team_detail.dim_value}} </span></span></li> {{/if}} {{if suspect_team_detail.group_id}} <li class="dimension-item"><span class="dimension-item-title">疑似风险群体编号：</span><span class="dimension-sub-item"> {{suspect_team_detail.group_id}} </span></li> {{/if}} {{if suspect_team_detail.total_cnt}} <li class="dimension-item"><span class="dimension-item-title">疑似风险群体成员数：</span><span class="dimension-sub-item"> {{suspect_team_detail.total_cnt}} </span></li> {{/if}} {{if suspect_team_detail.node_dist}} <li class="dimension-item"><span class="dimension-item-title">成员分布:</span><span class="dimension-sub-item"> {{suspect_team_detail.node_dist}} </span></li> {{/if}} {{if suspect_team_detail.black_cnt}} <li class="dimension-item"><span class="dimension-item-title">风险名单成员数:</span><span class="dimension-sub-item"> {{suspect_team_detail.black_cnt}} </span></li> {{/if}} {{if suspect_team_detail.black_rat}} <li class="dimension-item"><span class="dimension-item-title">风险名单占比:</span><span class="dimension-sub-item"> {{suspect_team_detail.black_rat}} </span></li> {{/if}} {{if suspect_team_detail.fraud_dist}} <li class="dimension-item"><span class="dimension-item-title">风险名单分布:</span><span class="dimension-sub-item"> {{suspect_team_detail.fraud_dist}} </span></li> {{/if}} {{if suspect_team_detail.grey_cnt}} <li class="dimension-item"><span class="dimension-item-title">关注名单成员数:</span><span class="dimension-sub-item"> {{suspect_team_detail.grey_cnt}} </span></li> {{/if}} {{if suspect_team_detail.grey_rat}} <li class="dimension-item"><span class="dimension-item-title">关注名单占比:</span><span class="dimension-sub-item"> {{suspect_team_detail.grey_rat}} </span></li> {{/if}} {{if suspect_team_detail.degree}} <li class="dimension-item"><span class="dimension-item-title">一度关联节点个数:</span><span class="dimension-sub-item"> {{suspect_team_detail.degree}} </span></li> {{/if}} {{if suspect_team_detail.total_cnt_two}} <li class="dimension-item"><span class="dimension-item-title">二度关联节点个数:</span><span class="dimension-sub-item"> {{suspect_team_detail.total_cnt_two}} </span></li> {{/if}} {{if suspect_team_detail.black_cnt_one}} <li class="dimension-item"><span class="dimension-item-title">一度风险名单个数:</span><span class="dimension-sub-item"> {{suspect_team_detail.black_cnt_one}} </span></li> {{/if}} {{if suspect_team_detail.fraud_dist_one}} <li class="dimension-item"><span class="dimension-item-title">一度风险名单分布:</span><span class="dimension-sub-item"> {{suspect_team_detail.fraud_dist_one}} </span></li> {{/if}} {{if suspect_team_detail.black_cnt_two}} <li class="dimension-item"><span class="dimension-item-title">二度风险名单个数:</span><span class="dimension-sub-item"> {{suspect_team_detail.black_cnt_two}} </span></li> {{/if}} {{if suspect_team_detail.fraud_dist_two}} <li class="dimension-item"><span class="dimension-item-title">二度风险名单分布:</span><span class="dimension-sub-item"> {{suspect_team_detail.fraud_dist_two}} </span></li> {{/if}} {{if suspect_team_detail.black_dst}} <li class="dimension-item"><span class="dimension-item-title">风险节点距离:</span><span class="dimension-sub-item"> {{suspect_team_detail.black_dst}} </span></li> {{/if}} {{if suspect_team_detail.core_dst}} <li class="dimension-item"><span class="dimension-item-title">核心节点距离:</span><span class="dimension-sub-item"> {{suspect_team_detail.core_dst}} </span></li> {{/if}} {{if suspect_team_detail.node_score}} <li class="dimension-item"><span class="dimension-item-title">关联风险分:</span><span class="dimension-sub-item">{{suspect_team_detail.node_score}}</span></li> {{/if}} </ul><br/> {{/each}} </div> {{/if}} </ul> {{/if}} {{if risk_detail.type=="cross_event_detail"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{if risk_detail.cross_event_detail_list && risk_detail.cross_event_detail_list.length>0}} <div class="dimension-section"><span class="dimension-title">各维度详情</span><ul class="dimension-list"> {{each risk_detail.cross_event_detail_list as fieldDetail fieldDetail_index}} <li class="dimension-item"><span class="dimension-sub-item"> {{fieldDetail.detail}} </span></li> {{/each}} </ul></div> {{/if}} </ul> {{/if}} {{if risk_detail.type==\'grey_list\'}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="name-list-ul"> {{if risk_detail.fraud_type_display_name}} <li><span>风险类型：</span><span class="name-list-value">{{risk_detail.fraud_type_display_name}}</span></li> {{/if}} {{if risk_detail.hit_type_display_name}} <li><span class="name-detail-em">匹配字段：</span><span class="name-list-value">{{risk_detail.hit_type_display_name}}</span></li> {{/if}} </ul> {{/if}} {{if risk_detail.type==\'black_list\'}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="name-list-ul"> {{if risk_detail.fraud_type_display_name}} <li><span class="name-detail-em">风险类型：</span><span class="name-list-value">{{risk_detail.fraud_type_display_name}}</span></li> {{/if}} {{if risk_detail.hit_type_display_name}} <li><span class="name-detail-em">匹配字段：</span><span class="name-list-value">{{risk_detail.hit_type_display_name}}</span></li> {{/if}} {{if risk_detail.court_details && risk_detail.court_details.length>0}} <li style="color: #2ea5ff;"><a class=\'table-href\' href=\'javascript:void(0)\'> 法院详情(点击查看) </a><div class="a-detail"><div class="detail-a-close"><a href="javascript:void(0)" class="detail-close-x"> X </a></div><div class="detail-table"> {{each risk_detail.court_details as court_detail court_detail_index}} <table> {{if court_detail.executed_name}} <tr><td class="col1">被执行人姓名:</td><td class="col2"> {{#court_detail.executed_name | dealDisplay}}</td></tr> {{/if}} {{if court_detail.gender}} <tr><td class="col1">性别:</td><td class="col2"> {{#court_detail.gender | dealDisplay}}</td></tr> {{/if}} {{if court_detail.age}} <tr><td class="col1">年龄:</td><td class="col2"> {{#court_detail.age | dealDisplay}}</td></tr> {{/if}} {{if court_detail.fraud_type_display_name}} <tr><td class="col1">风险类型:</td><td class="col2"> {{#court_detail.fraud_type_display_name | dealDisplay}}</td></tr> {{/if}} {{if court_detail.value}} <tr><td class="col1">命中的属性值:</td><td class="col2"> {{#court_detail.value | dealDisplay}}</td></tr> {{/if}} {{if court_detail.execute_court}} <tr><td class="col1">执行法院:</td><td class="col2"> {{#court_detail.execute_court | dealDisplay}}</td></tr> {{/if}} {{if court_detail.province}} <tr><td class="col1">省份:</td><td class="col2"> {{#court_detail.province | dealDisplay}}</td></tr> {{/if}} {{if court_detail.execute_code}} <tr><td class="col1">执行依据文号:</td><td class="col2"> {{#court_detail.execute_code | dealDisplay}}</td></tr> {{/if}} {{if court_detail.case_date}} <tr><td class="col1">立案时间:</td><td class="col2"> {{#court_detail.case_date | dealDisplay}}</td></tr> {{/if}} {{if court_detail.case_code}} <tr><td class="col1">案号:</td><td class="col2"> {{#court_detail.case_code | dealDisplay}}</td></tr> {{/if}} {{if court_detail.execute_subject}} <tr><td class="col1">执行标的:</td><td class="col2"> {{#court_detail.execute_subject | dealDisplay}}</td></tr> {{/if}} {{if court_detail.execute_status}} <tr><td class="col1">执行状态:</td><td class="col2"> {{#court_detail.execute_status | dealDisplay}}</td></tr> {{/if}} {{if court_detail.evidence_court}} <tr><td class="col1">做出依据执行法院:</td><td class="col2"> {{#court_detail.evidence_court | dealDisplay}}</td></tr> {{/if}} {{if court_detail.term_duty}} <tr><td class="col1">生效法律文书确定的义务:</td><td class="col2"> {{#court_detail.term_duty | dealDisplay}}</td></tr> {{/if}} {{if court_detail.carry_out}} <tr><td class="col1">被执行人履行情况:</td><td class="col2"> {{#court_detail.carry_out | dealDisplay}}</td></tr> {{/if}} {{if court_detail.specific_circumstances}} <tr><td class="col1">信贷逾期被执行人行为具体情形:</td><td class="col2"> {{#court_detail.specific_circumstances | dealDisplay}}</td></tr> {{/if}} </table> {{/each}} </div></div></li> {{/if}} </ul> {{/if}} {{if risk_detail.type=="fuzzy_black_list"}} {{if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="name-list-ul"> {{if risk_detail.fuzzy_list_details && risk_detail.fuzzy_list_details.length>0}} {{each risk_detail.fuzzy_list_details as fuzzy_list fuzzy_list_index}} {{if fuzzy_list.fraud_type_display_name}} <li><span class="name-detail-em">风险类型：</span><span class="name-list-value"> {{fuzzy_list.fraud_type_display_name}} </span></li> {{/if}} {{if fuzzy_list.fuzzy_name}} <li><span class="name-detail-em">姓名：</span><span class="name-list-value"> {{fuzzy_list.fuzzy_name}} </span></li> {{/if}} {{if fuzzy_list.fuzzy_id_number}} <li><span class="name-detail-em">模糊身份证：</span><span class="name-list-value"> {{fuzzy_list.fuzzy_id_number}} </span></li> {{/if}} {{/each}} {{/if}} </ul> {{/if}} {{ if risk_detail.type=="device_status_abnormal"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.abnormal_tags && risk_detail.abnormal_tags.length>0 }} <li>设备状态异常集合: {{#risk_detail.abnormal_tags | dealAbnormalTags}} </li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="fp_exception"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.code}} <li>异常代码:{{#risk_detail.code | dealDisplay}}</li> {{/if}} {{ if risk_detail.code_display_name}} <li>异常代码显示名:{{#risk_detail.code_display_name | dealDisplay}}</li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="android_emulator"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.emulator_type}} <li>模拟器名称:{{#risk_detail.emulator_type | dealDisplay}}</li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="android_cheat_app"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.hook_method}} <li>作弊器内容1:{{#risk_detail.hook_method | dealDisplay}}</li> {{/if}} {{ if risk_detail.hook_inline}} <li>作弊器内容2:{{#risk_detail.hook_inline | dealDisplay}}</li> {{/if}} {{ if risk_detail.hook_address}} <li>作弊器内容3:{{#risk_detail.hook_address | dealDisplay}}</li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="ios_cheat_app"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.hook_inline}} <li>作弊器内容1:{{#risk_detail.hook_inline | dealDisplay}}</li> {{/if}} {{ if risk_detail.hook_i_m_p}} <li>作弊器内容2:{{#risk_detail.hook_i_m_p | dealDisplay}}</li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="function_kit"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.result}} <li>计算结果:{{#risk_detail.result | dealDisplay}}</li> {{/if}} </ul> {{/if}} {{ if risk_detail.type=="creditList_index_detail"}} {{ if risk_detail.description}} <div class="description"><span class="rule-desc">规则描述</span><span>{{risk_detail.description}}</span></div> {{/if}} <ul class="table-mark"> {{ if risk_detail.dim_type}} <li>匹配字段:{{#risk_detail.dim_type | dealDisplay}}</li> {{/if}} {{ if risk_detail.result}} <li>计算结果:{{#risk_detail.result | dealDisplay}}</li> {{/if}} {{ if !!(risk_detail.hits) && risk_detail.hits.length>0}} <div class="dimension-section"><span class="dimension-title"><i class="iconfont icon-list"></i>名单指标细则详情 </span> {{each risk_detail.hits as hit}} <ul class="dimension-list judgment-list"> {{each creditListIndexDetailHitKeys as item}} {{ if hit[item] }} <li class="dimension-item"><span><span class="dimension-item-title">{{creditListIndexDetailHitObj[item]}}:</span><span class="dimension-sub-item judgment"> {{ if item == "judgment_doc" || item == "judgment_duty"}} <a class="judgment-href" href="javascript:void(0)"> 详情(点击查看) </a><div class="a-detail"><div class="detail-a-close"><a href="javascript:void(0)" class="detail-close-x">X</a></div><div class="detail-table"> {{hit[item]}} </div></div> {{else}} {{hit[item]}} {{/if}} </span></span></li> {{/if}} {{/each}} </ul><br/> {{/each}} </div> {{/if}} </ul> {{/if}} {{if (risk_detail_index+1) < risk_details.length}} <hr class="split-line"></hr> {{/if}} {{/each}} {{/if}}')({
                        risk_details: e,
                        creditListIndexDetailHitObj: t,
                        creditListIndexDetailHitKeys: i
                    })
                }
                return ""
            }), template.helper("dealArray", function (e) {
                return e.join("，")
            });
            var l = template.compile('<div class="report-mask"></div><div class="msk-detail"></div><div class="report-container"><div class="container"><div class="left-nav"> {{if INFOANALYSIS}} <div class="nav-tab person-info-nav"><span data-report="person-info-div"><a>个人报告</a></span></div> {{/if}} {{each module_report as sub_report sub_report_nav_index}} <div class="nav-tab"><span data-report="{{sub_report.report_name}}"><a>{{sub_report.report_display_name}}报告</a></span></div> {{/each}} </div><div class="report-a-close" id="report-a-close"><a href="javascript:void(0)">X</a></div><div class="header"><h1 class="header-title"></h1><div class="header-report-application"><span>保镖ID:</span><span>{{id}}</span></div></div> {{if INFOANALYSIS}} <div class="inner person-info-div"> {{if INFOANALYSIS.address_detect}} <div class="module"><div class="address-analysis"><div class="module-title"><h2>归属地解析</h2></div><div class="rpi-subitem"> {{if INFOANALYSIS.address_detect.id_card_address}} <p class="p-hidden"><span class="label">身份证所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.id_card_address}} </span></span></p> {{/if}} {{if INFOANALYSIS.address_detect.mobile_address}} <p class="p-hidden"><span class="label">手机所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.mobile_address}} </span></span></p> {{/if}} {{if INFOANALYSIS.address_detect.true_ip_address}} <p class="p-hidden"><span class="label">ip所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.true_ip_address}} </span></span></p> {{/if}} {{if INFOANALYSIS.address_detect.wifi_address}} <p class="p-hidden"><span class="label">wifi所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.wifi_address}} </span></span></p> {{/if}} {{if INFOANALYSIS.address_detect.cell_address}} <p class="p-hidden"><span class="label">基站所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.cell_address}} </span></span></p> {{/if}} {{if INFOANALYSIS.address_detect.bank_card_address}} <p class="p-hidden"><span class="label">银行卡所属地: <span class="label-span"> {{INFOANALYSIS.address_detect.bank_card_address}} </span></span></p> {{/if}} </div></div></div> {{/if}} {{if INFOANALYSIS.geoip_info}} <div class="module"><div class="address-analysis"><div class="module-title"><h2>IP解析</h2></div><div class="rpi-subitem"> {{if INFOANALYSIS.geoip_info.position}} <p class="p-hidden"><span class="label">地址: <span class="label-span"> {{INFOANALYSIS.geoip_info.position}} </span></span></p> {{/if}} {{if INFOANALYSIS.geoip_info.longitude}} <p class="p-hidden"><span class="label">经度: <span class="label-span">{{INFOANALYSIS.geoip_info.longitude}}</span></span></p> {{/if}} {{if INFOANALYSIS.geoip_info.isp}} <p class="p-hidden"><span class="label">互联网提供商: <span class="label-span">{{INFOANALYSIS.geoip_info.isp}}</span></span></p> {{/if}} {{if INFOANALYSIS.geoip_info.latitude}} <p class="p-hidden"><span class="label">纬度: <span class="label-span">{{INFOANALYSIS.geoip_info.latitude}}</span></span></p> {{/if}} {{each INFOANALYSIS.geoip_info.proxy_info as proxyInfo proxyIndex}} {{if proxyInfo.port}} <p class="p-hidden"><span class="label">代理端口: <span class="label-span">{{proxyInfo.port}}</span></span></p> {{/if}} {{if proxyInfo.proxyProtocol}} <p class="p-hidden"><span class="label">代理协议: <span class="label-span">{{proxyInfo.proxyProtocol}}</span></span></p> {{/if}} {{if proxyInfo.proxyType}} <p class="p-hidden "><span class="label">代理类型: <span class="label-span">{{proxyInfo.proxyType}}</span></span></p> {{/if}} {{/each}} </div></div></div> {{/if}} {{if INFOANALYSIS.geotrueip_info}} <div class="module"><div class="address-analysis"><div class="module-title"><h2>真实IP解析</h2></div><div class="rpi-subitem"> {{if INFOANALYSIS.geotrueip_info.position}} <p class="p-hidden"><span class="label">地址: <span class="label-span"> {{INFOANALYSIS.geotrueip_info.position}}</span></span></p> {{/if}} {{if INFOANALYSIS.geotrueip_info.longitude}} <p class="p-hidden"><span class="label">经度: <span class="label-span"> {{INFOANALYSIS.geotrueip_info.longitude}}</span></span></p> {{/if}} {{if INFOANALYSIS.geotrueip_info.isp}} <p class="p-hidden"><span class="label">互联网提供商: <span class="label-span">{{INFOANALYSIS.geotrueip_info.isp}}</span></span></p> {{/if}} {{if INFOANALYSIS.geotrueip_info.latitude}} <p class="p-hidden"><span class="label">纬度: <span class="label-span"> {{INFOANALYSIS.geotrueip_info.latitude}}</span></span></p> {{/if}} </div></div></div> {{/if}} {{if INFOANALYSIS.device_info && !INFOANALYSIS.device_info.error}} <div class="module"><div id="rp-device-info" class="rp-item rp-device-info"><div class="module-title"><h2>设备信息</h2></div><div style="margin: 20px 0px 20px 24px;"><a href="javascript:void(0)" class="jz">详情 (点击查看)</a></div><div class="table-wrap j-rpi-toggle-target"><table class="device-info"> {{if INFOANALYSIS.device_info.appOs.toLowerCase() == \'web\'}} <tr><td class="device-title">启用Cookie</td><td class="device-value"> {{#INFOANALYSIS.device_info.cookieEnabled | dealDisplay }} </td><td class="device-title">操作系统</td><td class="device-value"> {{#INFOANALYSIS.device_info.os | dealDisplay}} </td></tr><tr><td class="device-title">真实IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.trueIp | dealDisplay}} </td><td class="device-title">集成sdk版本号</td><td class="device-value"> {{#INFOANALYSIS.device_info.fpVersion | dealDisplay}} </td></tr><tr><td class="device-title">tokenId</td><td class="device-value"> {{#INFOANALYSIS.device_info.tokenId | dealDisplay}}</td><td class="device-title">设备ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceId | dealDisplay}}</td></tr><tr><td class="device-title">浏览器</td><td class="device-value"> {{#INFOANALYSIS.device_info.userAgent | dealDisplay}}</td><td class="device-title">智能ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.smartId | dealDisplay}}</td></tr><tr><td class="device-title">帆布指纹</td><td class="device-value"> {{#INFOANALYSIS.device_info.canvas | dealDisplay}}</td><td class="device-title">计算机语言</td><td class="device-value"> {{#INFOANALYSIS.device_info.languageRes | dealDisplay}}</td></tr><tr><td class="device-title">应用类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.appOs | dealDisplay}}</td><td class="device-title">插件列表</td><td class="device-value"> {{#INFOANALYSIS.device_info.pluginListHash | dealDisplay}}</td></tr><tr><td class="device-title">启用Flash</td><td class="device-value"> {{#INFOANALYSIS.device_info.flashEnabled | dealDisplay}}</td><td class="device-title">请求来源</td><td class="device-value"> {{#INFOANALYSIS.device_info.referer | dealDisplay}}</td></tr><tr><td class="device-title">浏览器类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.browserType | dealDisplay}}</td><td class="device-title">TCP协议栈特征对应的操作系统</td><td class="device-value"> {{#INFOANALYSIS.device_info.tcpOs | dealDisplay}}</td></tr><tr><td class="device-title">浏览器版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.browserVersion | dealDisplay}}</td><td class="device-title">字体</td><td class="device-value"> {{#INFOANALYSIS.device_info.fontListHash | dealDisplay}}</td></tr><tr><td class="device-title">浏览器header</td><td class="device-value"> {{#INFOANALYSIS.device_info.accept | dealDisplay}}</td><td class="device-title">浏览器header编码类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.acceptEncoding | dealDisplay}}</td></tr><tr><td class="device-title">浏览器header语言</td><td class="device-value"> {{#INFOANALYSIS.device_info.acceptLanguage | dealDisplay}}</td><td class="device-title">时区</td><td class="device-value"> {{#INFOANALYSIS.device_info.timeZone | dealDisplay}}</td></tr><tr><td class="device-title">开启Debug模式</td><td class="device-value"> {{#INFOANALYSIS.device_info.webDebuggerStatus | dealDisplay}}</td><td class="device-title">启用js</td><td class="device-value"> {{#INFOANALYSIS.device_info.enabledJs | dealDisplay}}</td></tr><tr><td class="device-title">设备类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceType | dealDisplay}}</td><td class="device-title">浏览器</td><td class="device-value"> {{#INFOANALYSIS.device_info.browser | dealDisplay}}</td></tr><tr><td class="device-title">屏幕分辨率</td><td class="device-value"> {{#INFOANALYSIS.device_info.screen | dealDisplay}}</td><td class="device-title">设备类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.appOs | dealDisplay}}</td></tr><tr><td class="device-title">错误原因</td><td class="device-value"> {{#INFOANALYSIS.device_info.error | dealDisplay}}</td><td></td><td></td></tr> {{else if INFOANALYSIS.device_info.appOs.toLowerCase() == \'ios\'}} <tr><td class="device-title">集成sdk版本号</td><td class="device-value"> {{#INFOANALYSIS.device_info.fpVersion | dealDisplay}}</td><td class="device-title">tokenId</td><td class="device-value"> {{#INFOANALYSIS.device_info.tokenId | dealDisplay}}</td></tr><tr><td class="device-title">设备ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceId | dealDisplay}}</td><td class="device-title">操作系统</td><td class="device-value"> {{#INFOANALYSIS.device_info.os | dealDisplay}}</td></tr><tr><td class="device-title">iOS系统版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.osVersion | dealDisplay}}</td><td class="device-title">广告追踪标识</td><td class="device-value"> {{#INFOANALYSIS.device_info.idfa | dealDisplay}}</td></tr><tr><td class="device-title">厂商追踪标识</td><td class="device-value"> {{#INFOANALYSIS.device_info.idfv | dealDisplay}}</td><td class="device-title">生效客户端ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.uuid | dealDisplay}}</td></tr><tr><td class="device-title">开机时刻</td><td class="device-value"> {{#INFOANALYSIS.device_info.bootTime | dealTime}}</td><td class="device-title">当前时间戳</td><td class="device-value"> {{#INFOANALYSIS.device_info.currentTime | dealTime}}</td></tr><tr><td class="device-title">运行时间</td><td class="device-value"> {{#INFOANALYSIS.device_info.upTime | dealTimeLong}}</td><td class="device-title">存储空间</td><td class="device-value"> {{#INFOANALYSIS.device_info.totalSpace | dealMemory}}</td></tr><tr><td class="device-title">可用空间</td><td class="device-value"> {{#INFOANALYSIS.device_info.freeSpace | dealMemory}}</td><td class="device-title">内存大小</td><td class="device-value"> {{#INFOANALYSIS.device_info.memory | dealMemory}}</td></tr><tr><td class="device-title">蜂窝网络ip</td><td class="device-value"> {{#INFOANALYSIS.device_info.cellIp | dealDisplay}}</td><td class="device-title">wifi IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.wifiIp | dealDisplay}}</td></tr><tr><td class="device-title">wifi 子网掩码</td><td class="device-value"> {{#INFOANALYSIS.device_info.wifiNetmask | dealDisplay}}</td><td class="device-title">wifi mac</td><td class="device-value"> {{#INFOANALYSIS.device_info.mac | dealDisplay}}</td></tr><tr><td class="device-title">wifi</td><td class="device-value"> {{#INFOANALYSIS.device_info.ssid | dealDisplay}}</td><td class="device-title">wifi BSSID</td><td class="device-value"> {{#INFOANALYSIS.device_info.bssid | dealDisplay}}</td></tr><tr><td class="device-title">VPN IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.vpnIp | dealDisplay}}</td><td class="device-title">VPN 子网掩码</td><td class="device-value"> {{#INFOANALYSIS.device_info.vpnNetmask | dealDisplay}}</td></tr><tr><td class="device-title">网络类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.networkType | dealDisplay}}</td><td class="device-title">Wifi代理类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.proxyType | dealDisplay}}</td></tr><tr><td class="device-title">Wifi代理地址</td><td class="device-value"> {{#INFOANALYSIS.device_info.proxyUrl | dealDisplay}}</td><td class="device-title">设备型号</td><td class="device-value"> {{#INFOANALYSIS.device_info.platform | dealDisplay}}</td></tr><tr><td class="device-title">设备名称</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceName | dealDisplay}}</td><td class="device-title">屏幕亮度</td><td class="device-value"> {{#INFOANALYSIS.device_info.brightness | dealDisplay}}</td></tr><tr><td class="device-title">运营商</td><td class="device-value"> {{#INFOANALYSIS.device_info.carrier | dealDisplay}}</td><td class="device-title">ISO标准国家码</td><td class="device-value"> {{#INFOANALYSIS.device_info.countryIso | dealDisplay}}</td></tr><tr><td class="device-title">移动网络码</td><td class="device-value"> {{#INFOANALYSIS.device_info.mnc | dealDisplay}}</td><td class="device-title">移动国家码</td><td class="device-value"> {{#INFOANALYSIS.device_info.mcc | dealDisplay}}</td></tr><tr><td class="device-title">应用类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.appOs | dealDisplay}}</td><td class="device-title">应用的BundleId</td><td class="device-value"> {{#INFOANALYSIS.device_info.bundleId | dealDisplay}}</td></tr><tr><td class="device-title">应用的版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.appVersion | dealDisplay}}</td><td class="device-title">时区</td><td class="device-value"> {{#INFOANALYSIS.device_info.timeZone | dealDisplay}}</td></tr><tr><td class="device-title">_CodeSignature MD5</td><td class="device-value"> {{#INFOANALYSIS.device_info.signMd5 | dealDisplay}}</td><td class="device-title">错误原因</td><td class="device-value"> {{#INFOANALYSIS.device_info.error | dealDisplay}}</td></tr><tr><td class="device-title">语言列表</td><td class="device-value"> {{#INFOANALYSIS.device_info.languages | dealDisplay}}</td><td class="device-title">充电状态</td><td class="device-value"> {{#INFOANALYSIS.device_info.batteryStatus | dealDisplay}}</td></tr><tr><td class="device-title">电量</td><td class="device-value"> {{#INFOANALYSIS.device_info.batteryLevel | dealDisplay}}</td><td class="device-title">内核版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.kernelVersion | dealDisplay}}</td></tr><tr><td class="device-title">最近一次定位</td><td class="device-value"> {{#INFOANALYSIS.device_info.gpsLocation | dealDisplay}}</td><td class="device-title">GPS开关状态</td><td class="device-value"> {{#INFOANALYSIS.device_info.gpsSwitch | dealDisplay}}</td></tr><tr><td class="device-title">GPS授权状态</td><td class="device-value"> {{#INFOANALYSIS.device_info.gpsAuthStatus | dealDisplay}}</td><td class="device-title">通过环境变量注入的动态库</td><td class="device-value"> {{#INFOANALYSIS.device_info.env | dealDisplay}}</td></tr><tr><td class="device-title">越狱后注入进程的插件库</td><td class="device-value"> {{#INFOANALYSIS.device_info.attached | dealDisplay}} </td><td class="device-title">真实IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.trueIp | dealDisplay}}</td></tr> {{else if INFOANALYSIS.device_info.appOs.toLowerCase() == \'android\'}} <tr><td class="device-title">集成sdk版本号</td><td class="device-value"> {{#INFOANALYSIS.device_info.fpVersion | dealDisplay}}</td><td class="device-title">tokenId</td><td class="device-value"> {{#INFOANALYSIS.device_info.tokenId | dealDisplay}} </td></tr><tr><td class="device-title">设备ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceId | dealDisplay}}</td><td class="device-title">操作系统</td><td class="device-value"> {{#INFOANALYSIS.device_info.os | dealDisplay}} </td></tr><tr><td class="device-title">系统版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.sdkVersion | dealDisplay}}</td><td class="device-title">发行版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.releaseVersion | dealDisplay}}</td></tr><tr><td class="device-title">设备型号</td><td class="device-value"> {{#INFOANALYSIS.device_info.model | dealDisplay}}</td><td class="device-title">产品内部代码</td><td class="device-value"> {{#INFOANALYSIS.device_info.product | dealDisplay}}</td></tr><tr><td class="device-title">品牌</td><td class="device-value"> {{#INFOANALYSIS.device_info.brand | dealDisplay}}</td><td class="device-title">序列号</td><td class="device-value"> {{#INFOANALYSIS.device_info.serialNo | dealDisplay}}</td></tr><tr><td class="device-title">固件编号</td><td class="device-value"> {{#INFOANALYSIS.device_info.display | dealDisplay}}</td><td class="device-title">编译ROM的主机</td><td class="device-value"> {{#INFOANALYSIS.device_info.host | dealDisplay}}</td></tr><tr><td class="device-title">设备名称</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceName | dealDisplay}}</td><td class="device-title">硬件平台名称或者代号</td><td class="device-value"> {{#INFOANALYSIS.device_info.hardware | dealDisplay}}</td></tr><tr><td class="device-title">ROM标签</td><td class="device-value"> {{#INFOANALYSIS.device_info.tags | dealDisplay}}</td><td class="device-title">多个信息</td><td class="device-value"> {{#INFOANALYSIS.device_info.telephonyInfo | dealDisplay}}</td></tr><tr><td class="device-title">SVN号</td><td class="device-value"> {{#INFOANALYSIS.device_info.deviceSVN | dealDisplay}}</td><td class="device-title">wifi IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.wifiIp | dealDisplay}}</td></tr><tr><td class="device-title">wifi mac地址</td><td class="device-value"> {{#INFOANALYSIS.device_info.wifiMac | dealDisplay}}</td><td class="device-title">wifi</td><td class="device-value"> {{#INFOANALYSIS.device_info.ssid | dealDisplay}}</td></tr><tr><td class="device-title">wifi BSSID</td><td class="device-value"> {{#INFOANALYSIS.device_info.bssid | dealDisplay}}</td><td class="device-title">网关</td><td class="device-value"> {{#INFOANALYSIS.device_info.gateway | dealDisplay}}</td></tr><tr><td class="device-title">子网掩码</td><td class="device-value"> {{#INFOANALYSIS.device_info.wifiNetmask | dealDisplay}}</td><td class="device-title">HTTP代理IP和端口</td><td class="device-value"> {{#INFOANALYSIS.device_info.proxyInfo | dealDisplay}}</td></tr><tr><td class="device-title">DNS</td><td class="device-value"> {{#INFOANALYSIS.device_info.dnsAddress | dealDisplay}}</td><td class="device-title">VPN IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.vpnIp | dealDisplay}}</td></tr><tr><td class="device-title">VPN 子网掩码</td><td class="device-value"> {{#INFOANALYSIS.device_info.vpnNetmask | dealDisplay}}</td><td class="device-title">数据网络IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.cellIp | dealDisplay}}</td></tr><tr><td class="device-title">网络类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.networkType | dealDisplay}}</td><td class="device-title">当前时间</td><td class="device-value"> {{#INFOANALYSIS.device_info.currentTime | dealTime}}</td></tr><tr><td class="device-title">运行时间</td><td class="device-value"> {{#INFOANALYSIS.device_info.upTime | dealTimeLong}}</td><td class="device-title">开机时刻</td><td class="device-value"> {{#INFOANALYSIS.device_info.bootTime | dealTime}}</td></tr><tr><td class="device-title">是否ROOT</td><td class="device-value"> {{#INFOANALYSIS.device_info.root | dealDisplay}}</td><td class="device-title">应用包名</td><td class="device-value"> {{#INFOANALYSIS.device_info.packageName | dealDisplay}}</td></tr><tr><td class="device-title">应用版本号</td><td class="device-value"> {{#INFOANALYSIS.device_info.apkVersion | dealDisplay}}</td><td class="device-title">SDK core文件的md5</td><td class="device-value"> {{#INFOANALYSIS.device_info.sdkMd5 | dealDisplay}}</td></tr><tr><td class="device-title">APK签名文件的md5</td><td class="device-value"> {{#INFOANALYSIS.device_info.signMD5 | dealDisplay}}</td><td class="device-title">APK文件的md5</td><td class="device-value"> {{#INFOANALYSIS.device_info.apkMD5 | dealDisplay}}</td></tr><tr><td class="device-title">时区</td><td class="device-value"> {{#INFOANALYSIS.device_info.timeZone | dealDisplay}}</td><td class="device-title">语言</td><td class="device-value"> {{#INFOANALYSIS.device_info.language | dealDisplay}}</td></tr><tr><td class="device-title">屏幕亮度</td><td class="device-value"> {{#INFOANALYSIS.device_info.brightness | dealDisplay}}</td><td class="device-title">充电状态</td><td class="device-value"> {{#INFOANALYSIS.device_info.batteryStatus | dealDisplay}}</td></tr><tr><td class="device-title">电量</td><td class="device-value"> {{#INFOANALYSIS.device_info.batteryLevel | dealDisplay}}</td><td class="device-title">电池温度</td><td class="device-value"> {{#INFOANALYSIS.device_info.batteryTemp | dealDisplay}}</td></tr><tr><td class="device-title">屏幕分辨率</td><td class="device-value"> {{#INFOANALYSIS.device_info.screenRes | dealDisplay}}</td><td class="device-title">字体列表HASH</td><td class="device-value"> {{#INFOANALYSIS.device_info.fontHash | dealDisplay}}</td></tr><tr><td class="device-title">蓝牙MAC地址</td><td class="device-value"> {{#INFOANALYSIS.device_info.blueMac | dealDisplay}}</td><td class="device-title">系统初始化ID</td><td class="device-value"> {{#INFOANALYSIS.device_info.andriodId | dealDisplay}}</td></tr><tr><td class="device-title">CPU最大频率</td><td class="device-value"> {{#INFOANALYSIS.device_info.cpuFrequency | dealDisplay}}</td><td class="device-title">CPU硬件架构</td><td class="device-value"> {{#INFOANALYSIS.device_info.cpuHardware | dealDisplay}}</td></tr><tr><td class="device-title">CPU型号或者平台</td><td class="device-value"> {{#INFOANALYSIS.device_info.cpuType | dealDisplay}}</td><td class="device-title">内存大小</td><td class="device-value"> {{#INFOANALYSIS.device_info.totalMemory | dealMemory}}</td></tr><tr><td class="device-title">可用内存大小</td><td class="device-value"> {{#INFOANALYSIS.device_info.availableMemory | dealMemory}}</td><td class="device-title">基带版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.basebandVersion | dealDisplay}}</td></tr><tr><td class="device-title">底层Linux内核版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.kernelVersion | dealDisplay}}</td><td class="device-title">GPS坐标</td><td class="device-value"> {{#INFOANALYSIS.device_info.gpsLocation | dealDisplay}}</td></tr><tr><td class="device-title">启用位置模拟</td><td class="device-value"> {{#INFOANALYSIS.device_info.allowMockLocation | dealDisplay}}</td><td class="device-title">真实IP</td><td class="device-value"> {{#INFOANALYSIS.device_info.trueIp | dealDisplay}}</td></tr><tr><td class="device-title">设备类型</td><td class="device-value"> {{#INFOANALYSIS.device_info.appOs | dealDisplay}}</td><td class="device-title">错误原因</td><td class="device-value"> {{#INFOANALYSIS.device_info.error | dealDisplay}}</td></tr><tr><td class="device-title">设备指纹SDK的版本</td><td class="device-value"> {{#INFOANALYSIS.device_info.fmVersion | dealDisplay}}</td><td class="device-title">手机信息</td><td class="device-value"> {{#INFOANALYSIS.device_info.telephonyInfos | dealDisplay}}</td></tr><tr><td class="device-title">开机时间(不含休眠)</td><td class="device-value"> {{#INFOANALYSIS.device_info.activeTime | dealDisplay}}</td><td class="device-title">总容量</td><td class="device-value"> {{#INFOANALYSIS.device_info.totalStorage | dealDisplay}}</td></tr><tr><td class="device-title">可用容量</td><td class="device-value"> {{#INFOANALYSIS.device_info.availableStorage | dealDisplay}}</td><td class="device-title"></td><td class="device-value"></td></tr> {{/if}} </table></div></div></div> {{/if}} </div> {{/if}} {{each module_report as sub_report sub_report_index}} <div class="inner {{sub_report.report_name}}"><div class="module"><div class="risk-score"><div class="fl result-score-wrap"><div class="result-score-canvas-main"><canvas class="canvas-main"></canvas></div><div class="result-score-canvas-bg"><canvas class="canvas-bg"></canvas></div><div class="result-score-text"><div id="result-score" class="result-score"> {{sub_report.final_score}} </div><div class="result-cat"> {{if sub_report.final_decision =="PASS"}} 建议通过 {{else if sub_report.final_decision == "REJECT"}} 建议拒绝 {{else if sub_report.final_decision == "REVIEW"}} 建议审核 {{/if}} </div></div></div><div class="fl result-text"> {{if sub_report.final_decision == "PASS"}} <p class="tip">申请用户未检出高危风险，建议通过</p> {{else if sub_report.final_decision == "REJECT"}} <p class="tip">申请用户检测出高危风险，建议拒绝</p> {{else if sub_report.final_decision == "REVIEW"}} <p class="tip">申请用户存在较大风险，建议进行人工审核</p> {{/if}} <p class="result">共发现 <a class="risk-count" href="javascript:void(0)"> {{if sub_report.risk_items}} {{sub_report.risk_items.length}} {{else}}0{{/if}}</a>异常信息 </p></div></div></div> {{if sub_report.output_fields}} <div class="module"><div class="output-field"><div class="module-title"><h2>自定义指标</h2></div><div class="rpi-subitem"> {{each sub_report.output_fields as output_field output_field_index}} <p class="p-field"><span class="p-field-title">{{output_field.field_name}}：</span><span class="p-field-value">{{output_field.field_value}}</span></p> {{/each}} </div></div></div> {{/if}} {{if sub_report.policy_set}} {{each sub_report.policy_set as policy_item policy_item_index}} <div class="module"><div class="rp-item risk-items"><div class="module-title"><h2>{{policy_item.policy_name}}</h2></div> {{if policy_item.risk_items && policy_item.risk_items.length>0}} <div class="table-wrap"><table class="risk-table"><thead><tr><th class="risk-col1">规则名称</th><th class="risk-col1" style="width:100px;">规则得分</th><th class="risk-col2">规则详情</th></tr></thead><tbody> {{each policy_item.risk_items as risk_item risk_item_index}} {{if risk_item}} <tr class="risk-items-flag border-bottom-2"><td class="risk-col1">{{risk_item.risk_name}}</td><td class="risk-col1" style="width:100px;">{{risk_item.score}}</td><td class="risk-col2"> {{#risk_item.risk_detail | riskDetailDisplay}} </td></tr> {{/if}} {{/each}} </tbody></table></div> {{else}} <div class="table-wrap" style="padding-left: 20px;">无风险</div> {{/if}} </div></div> {{/each}} {{else}} <div class="module"><div class="rp-item risk-items"><div class="module-title"><h2>{{sub_report.report_display_name}}风险情况</h2></div> {{if sub_report.risk_items && sub_report.risk_items.length>0}} <div class="table-wrap"><table class="risk-table"><thead><tr><th class="risk-col1">规则名称</th><th class="risk-col2">规则详情</th></tr></thead><tbody> {{each sub_report.risk_items as risk_item risk_item_index}} {{if risk_item}} <tr class="risk-items-flag border-bottom-2"><td class="risk-col1">{{risk_item.risk_name}}</td><td class="risk-col2"> {{#risk_item.risk_detail | riskDetailDisplay}} </td></tr> {{/if}} {{/each}} </tbody></table></div> {{else}} <div class="table-wrap" style="padding-left: 20px;">无风险</div> {{/if}} </div></div> {{/if}} </div> {{/each}} </div></div>')(a);
            0 == $("body #tdReportContainer").length && $("body").append('<div id="tdReportContainer"></div>'), $("#tdReportContainer").html(l), function () {
                $(".nav-tab").on("click", function () {
                    $(".report-container").animate({scrollTop: "0"}, 0);
                    var e = $(this), i = e.siblings(".nav-tab"), s = e.children("span"), t = s.data("report");
                    if ($(".header-title").text(s.text()), i.removeClass("current-tab"), e.addClass("current-tab"), $("." + t).css("display", "inline-block"), $('.inner:not(".' + t + '")').css("display", "none"), 0 < $("." + t + " .canvas-main").length) {
                        var a = $("." + t + " .canvas-main")[0], l = a.getContext("2d"),
                            d = parseInt($("." + t + " .result-score").text(), 10), c = $("." + t + " .result-cat").text(),
                            n = 1;
                        0 < d && d < 100 && (n = (100 - d) / 100), 0 === d && (n = 1), l.rotate(-Math.PI / 2), l.lineWidth = 8, -1 < c.indexOf("拒绝") ? ($("." + t + " .result-cat").addClass("reject"), l.strokeStyle = "#ff6c5c") : -1 < c.indexOf("通过") ? ($("." + t + " .result-cat").addClass("accept"), l.strokeStyle = "#8cdb65") : -1 < c.indexOf("审核") && ($("." + t + " .result-cat").addClass("review"), l.strokeStyle = "#f8d436"), l.beginPath(), l.arc(-75, 75, 69, 0, Math.PI * (2 * n), !0), l.stroke();
                        var r = $("." + t + " .canvas-bg")[0], o = r.getContext("2d");
                        o.fillStyle = "#fafafa", o.beginPath(), o.arc(61, 61, 52, 0, 2 * Math.PI, !0), o.closePath(), o.fill()
                    }
                }), $(".left-nav").children(":first").addClass("current-tab");
                var e = $(".current-tab").children("span");
                $(".header-title").text(e.text());
                var i = e.data("report");
                $('.inner:not(".' + i + '")').css("display", "none"), $(".left-nav").children(".nav-tab:first").trigger("click"), $(".jz").on("click", function () {
                    $(".j-rpi-toggle-target").toggle(200)
                }), $(".risk-count").on("click", function () {
                    var e = $(this), i = e.parents(".inner"), s = i.find(".risk-items");
                    0 < s.length && ($(".report-container").animate({scrollTop: 0}, 0), $(".report-container").animate({scrollTop: s.offset().top}, 300))
                }), $("#report-a-close").on("click", function (e) {
                    e.preventDefault(), e.stopPropagation(), $("#tdReportContainer").hide(200)
                }), $(".report-container").on("click", function () {
                    $("#report-a-close").trigger("click")
                }), $(".container").on("click", function (e) {
                    e.preventDefault(), e.stopPropagation()
                }), $(".dimension-title").on("click", function () {
                    var e = $(this).next(".dimension-list");
                    e.length && e.slideToggle(200)
                }), $(".risk-table").on("click", ".table-href", function () {
                    $(".a-detail").hide(), $(this).parents("ul").find(".a-detail").show(100), $(".msk-detail").show()
                }), $(".judgment-list").on("click", ".judgment-href", function () {
                    $(".a-detail").hide(), $(this).parent(".judgment").find(".a-detail").show(100), $(".msk-detail").show()
                }), $(".risk-detail-title").on("click", function () {
                    var e = $(this).next(".risk-detail-list");
                    e.length && e.slideToggle(200);
                    var i = $(this).next(".risk-detail-list").find(".risk-detail-sub-list").children("li");
                    $.each(i, function (e, i) {
                        var s = $(i).text().replace(/[※]/g, "*");
                        $(i).text(s)
                    })
                }), $(".detail-close-x").on("click", function (e) {
                    $(this).parent().parent().hide(100), $(".msk-detail").hide()
                }), $(".msk-detail").on("click", function () {
                    $(".detail-close-x").trigger("click"), $(".msk-detail").hide()
                })
            }(), $("#tdReportContainer").show(200)
        }
    }), function () {
        function k(e) {
            return "'" + e.replace(/('|\\)/g, "\\$1").replace(/\r/g, "\\r").replace(/\n/g, "\\n") + "'"
        }

        function d(e, a) {
            function t(e) {
                return o += e.split(/\n/).length - 1, s && (e = e.replace(/\s+/g, " ").replace(/<!--[\w\W]*?-->/g, "")), e && (e = _[1] + k(e) + _[2] + "\n"), e
            }

            function l(e) {
                var i = o;
                if (n ? e = n(e, a) : d && (e = e.replace(/\n/g, function () {
                    return "$line=" + ++o + ";"
                })), 0 === e.indexOf("=")) {
                    var s = r && !/^=[=#]/.test(e);
                    if (e = e.replace(/^=[=#]?|[\s;]*$/g, ""), s) {
                        var t = e.replace(/\s*\([^\)]+\)/, "");
                        h[t] || /^(include|print)$/.test(t) || (e = "$escape(" + e + ")")
                    } else e = "$string(" + e + ")";
                    e = _[1] + e + _[2]
                }
                return d && (e = "$line=" + i + ";" + e), O(e.replace(L, "").replace(g, ",").replace(D, "").replace(F, "").replace(Y, "").split($), function (e) {
                    var i;
                    e && !p[e] && (i = "print" === e ? u : "include" === e ? m : h[e] ? "$utils." + e : b[e] ? "$helpers." + e : "$data." + e, I += e + "=" + i + ",", p[e] = !0)
                }), e + "\n"
            }

            var d = a.debug, i = a.openTag, c = a.closeTag, n = a.parser, s = a.compress, r = a.escape, o = 1,
                p = {$data: 1, $filename: 1, $utils: 1, $helpers: 1, $out: 1, $line: 1}, v = "".trim,
                _ = v ? ["$out='';", "$out+=", ";", "$out"] : ["$out=[];", "$out.push(", ");", "$out.join('')"],
                f = v ? "$out+=text;return $out;" : "$out.push(text);",
                u = "function(){var text=''.concat.apply('',arguments);" + f + "}",
                m = "function(filename,data){data=data||$data;var text=$utils.$include(filename,data,$filename);" + f + "}",
                I = "'use strict';var $utils=this,$helpers=$utils.$helpers," + (d ? "$line=0," : ""), S = _[0],
                A = "return new String(" + _[3] + ");";
            O(e.split(i), function (e) {
                var i = (e = e.split(c))[0], s = e[1];
                1 === e.length ? S += t(i) : (S += l(i), s && (S += t(s)))
            });
            var N = I + S + A;
            d && (N = "try{" + N + "}catch(e){throw {filename:$filename,name:'Render Error',message:e.message,line:$line,source:" + k(e) + ".split(/\\n/)[$line-1].replace(/^\\s+/,'')};}");
            try {
                var y = new Function("$data", "$filename", N);
                return y.prototype = h, y
            } catch (e) {
                throw e.temp = "function anonymous($data,$filename) {" + N + "}", e
            }
        }

        var u = function (e, i) {
            return "string" == typeof i ? o(i, {filename: e}) : s(e, i)
        };
        u.version = "3.0.0", u.config = function (e, i) {
            c[e] = i
        };
        var c = u.defaults = {openTag: "<%", closeTag: "%>", escape: !0, cache: !0, compress: !1, parser: null},
            n = u.cache = {};
        u.render = function (e, i) {
            return o(e, i)
        };
        var s = u.renderFile = function (e, i) {
            var s = u.get(e) || r({filename: e, name: "Render Error", message: "Template not found"});
            return i ? s(i) : s
        };
        u.get = function (e) {
            var i;
            if (n[e]) i = n[e]; else if ("object" == typeof document) {
                var s = document.getElementById(e);
                if (s) {
                    var t = (s.value || s.innerHTML).replace(/^\s*|\s*$/g, "");
                    i = o(t, {filename: e})
                }
            }
            return i
        };
        var t = function (e, i) {
            return "string" != typeof e && ("number" === (i = typeof e) ? e += "" : e = "function" === i ? t(e.call(e)) : ""), e
        }, i = {"<": "&#60;", ">": "&#62;", '"': "&#34;", "'": "&#39;", "&": "&#38;"}, a = function (e) {
            return i[e]
        }, l = Array.isArray || function (e) {
            return "[object Array]" === {}.toString.call(e)
        }, h = u.utils = {
            $helpers: {}, $include: s, $string: t, $escape: function (e) {
                return t(e).replace(/&(?![\w#]+;)|[<>"']/g, a)
            }, $each: function (e, i) {
                var s, t;
                if (l(e)) for (s = 0, t = e.length; s < t; s++) i.call(e, e[s], s, e); else for (s in e) i.call(e, e[s], s)
            }
        };
        u.helper = function (e, i) {
            b[e] = i
        };
        var b = u.helpers = h.$helpers;
        u.onerror = function (e) {
            var i = "Template Error\n\n";
            for (var s in e) i += "<" + s + ">\n" + e[s] + "\n\n";
            "object" == typeof console && console.error(i)
        };
        var r = function (e) {
                return u.onerror(e), function () {
                    return "{Template Error}"
                }
            }, o = u.compile = function (s, t) {
                function e(i) {
                    try {
                        return new l(i, a) + ""
                    } catch (e) {
                        return t.debug ? r(e)() : (t.debug = !0, o(s, t)(i))
                    }
                }

                for (var i in t = t || {}, c) void 0 === t[i] && (t[i] = c[i]);
                var a = t.filename;
                try {
                    var l = d(s, t)
                } catch (e) {
                    return e.filename = a || "anonymous", e.name = "Syntax Error", r(e)
                }
                return e.prototype = l.prototype, e.toString = function () {
                    return l.toString()
                }, a && t.cache && (n[a] = e), e
            }, O = h.$each,
            L = /\/\*[\w\W]*?\*\/|\/\/[^\n]*\n|\/\/[^\n]*$|"(?:[^"\\]|\\[\w\W])*"|'(?:[^'\\]|\\[\w\W])*'|\s*\.\s*[$\w\.]+/g,
            g = /[^\w$]+/g,
            D = new RegExp(["\\b" + "break,case,catch,continue,debugger,default,delete,do,else,false,finally,for,function,if,in,instanceof,new,null,return,switch,this,throw,true,try,typeof,var,void,while,with,abstract,boolean,byte,char,class,const,double,enum,export,extends,final,float,goto,implements,import,int,interface,long,native,package,private,protected,public,short,static,super,synchronized,throws,transient,volatile,arguments,let,yield,undefined".replace(/,/g, "\\b|\\b") + "\\b"].join("|"), "g"),
            F = /^\d[^,]*|,\d[^,]*/g, Y = /^,+|,+$/g, $ = /^$|,+/;
        c.openTag = "{{", c.closeTag = "}}";
        c.parser = function (e) {
            var i, s, t, a, l, d = (e = e.replace(/^\s/, "")).split(" "), c = d.shift(), n = d.join(" ");
            switch (c) {
                case"if":
                    e = "if(" + n + "){";
                    break;
                case"else":
                    e = "}else" + (d = "if" === d.shift() ? " if(" + d.join(" ") + ")" : "") + "{";
                    break;
                case"/if":
                    e = "}";
                    break;
                case"each":
                    var r = d[0] || "$data";
                    "as" !== (d[1] || "as") && (r = "[]"), e = "$each(" + r + ",function(" + ((d[2] || "$value") + "," + (d[3] || "$index")) + "){";
                    break;
                case"/each":
                    e = "});";
                    break;
                case"echo":
                    e = "print(" + n + ");";
                    break;
                case"print":
                case"include":
                    e = c + "(" + d.join(",") + ");";
                    break;
                default:
                    if (/^\s*\|\s*[\w\$]/.test(n)) {
                        var o = !0;
                        0 === e.indexOf("#") && (e = e.substr(1), o = !1);
                        for (var p = 0, v = e.split("|"), _ = v.length, f = v[p++]; p < _; p++) i = f, s = v[p], l = t = void 0, t = s.split(":"), a = t.shift(), (l = t.join(":") || "") && (l = ", " + l), f = "$helpers." + a + "(" + i + l + ")";
                        e = (o ? "=" : "=#") + f
                    } else e = u.helpers[c] ? "=#" + c + "(" + d.join(",") + ");" : "=" + e
            }
            return e
        }, "function" == typeof define ? define(function () {
            return u
        }) : "undefined" != typeof exports ? module.exports = u : this.template = u
    }();
</script>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的同盾信息</span>
        </th>
    </tr>
    <tr>
        <td>
            <a href="javascript:;" onclick="getReportId('<?php echo $loanPerson['id'];?>')">提交用户信息</a>
            <?php if(!empty($info['status']) && $info['status'] == 1): ?>
                <a style="color:red" onclick="getInfo(<?php echo $info['person_id'];?>)" href="javascript:;">点击获取所有信息</a>
            <?php endif;?>
        </td>
    </tr>
    <?php if(!empty($data)):?>
        <tr>
            <td width="500">报告信息</td>
            <td>
                <table class="son">
                    <tr>
                        <td>报表更新时间</td>
                        <td>风险评分（数字越大风险越高,0-19通过、20-79审核、80以上拒绝）</td>
                        <td>审核建议</td>
                    </tr>
                    <tr>
                        <td><?php echo isset($data['report_time'])?date('Y-m-d',floor($data['report_time']/1000)):'未知';?></td>
                        <td><?php echo $data['result_desc']['ANTIFRAUD']['final_score'];?></td>
                        <td><?php echo $data['result_desc']['ANTIFRAUD']['final_decision'];?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php foreach($data['result_desc']['ANTIFRAUD']['risk_items'] as $v):?>
            <tr>
                <td width="500"><?php echo $v['risk_name'];?></td>
                <td>
                    <table class="son">
                        <tr>
                            <?php if(isset($v['risk_detail'][0]['grey_list_details'][0]['risk_level'])):?>
                                <td>风险等级</td>
                            <?php endif;?>
                            <?php if(isset($v['risk_detail'][0]['description'])):?>
                                <td>风险名称</td>
                            <?php endif;?>
                            <?php if(isset($v['risk_detail']['score'])):?>
                                <td>风险评分</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_count'])):?>
                                <td>多平台借贷总数</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_detail'])):?>
                                <td>借贷详情</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['high_risk_areas'])):?>
                                <td>高风险区域</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['hit_list_datas'])):?>
                                <td>中介关键词</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['court_details'])):?>
                                <td>法院详情信息列表</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['fraud_type'])):?>
                                <td>风险类型</td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['frequency_detail_list'])):?>
                                <td>频度详情</td>
                            <?php endif;?>
                        </tr>
                        <tr>
                            <?php if(isset($v['risk_detail'][0]['grey_list_details'][0]['risk_level'])):?>
                                <td><?php echo $v['risk_detail'][0]['grey_list_details'][0]['risk_level'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['risk_detail'][0]['description'])):?>
                                <td><?php echo $v['risk_detail'][0]['description'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['risk_detail']['score'])):?>
                                <td><?php echo $v['risk_detail']['score'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_count'])):?>
                                <td><?php echo $v['item_detail']['platform_count'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['platform_detail'])):?>
                                <td><?php echo implode(',',$v['item_detail']['platform_detail']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['high_risk_areas'])):?>
                                <td><?php echo implode(',',$v['item_detail']['high_risk_areas']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['hit_list_datas'])):?>
                                <td><?php echo implode(',',$v['item_detail']['hit_list_datas']);?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['fraud_type'])):?>
                                <td><?php echo $v['item_detail']['fraud_type'];?></td>
                            <?php endif;?>
                            <?php if(isset($v['item_detail']['frequency_detail_list'])):?>
                                <td>
                                    <?php foreach($v['item_detail']['frequency_detail_list'] as $value):?>
                                        <?php echo $value['detail'];?></br>
                                    <?php endforeach;?>
                                </td>
                            <?php endif;?>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php endforeach;?>
    <?php endif;?>
    <?php if(empty($data)):?>
        <tr>不存在同盾数据</tr>
    <?php endif;?>
</table>
<?php if(!empty($data)):?>
    <button id="showtongdun" style="margin: 20px 0">查看同盾数据</button>
<?php endif;?>
<br><a href="<?php echo Url::toRoute(['td/old-user-view','id'=>$id]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">历史查询记录</a>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>
<script>
    var td_data = <?php echo json_encode($data)?>;
    if (td_data) {
        var td_data_list = [td_data]
        $('#showtongdun').on('click', function () {
            $.showReport(td_data_list);
        })
    }
    function getReportId(id){
        var url = '<?php echo Url::toRoute(['td/get-report-id']);?>';
        var params = {id:id};
        var ret = confirmMsg('确认提交');
        if(!ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('提交成功');
            }else{
                alert(data.message);
            }
            location.reload(true);
        },'json');
    }

    function getInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        var url = '<?php echo Url::toRoute(['td/get-info']);?>';
        //获取芝麻评分
        params = {
            id:id
        };
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('获取成功');
            }else{
                alert(data.messag);
            }
            location.reload(true);
        },'json');

    }


</script>


