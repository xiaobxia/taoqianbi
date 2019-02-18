/**
 * 后台渠道结算JS
 */
var bk_channel = {};
bk_channel = {
    //结算类型change事件
    calcTypeChange:function(){
        var _v = $(this).val();
        switch(_v) {
            case '0'://无
                $(".tr_calc_type_a, .tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
                break;
            case '1'://A
                $(".tr_calc_type_a").show();
                $(".tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
                break;
            case '2'://S
                $(".tr_calc_type_s").show();
                $(".tr_calc_type_a, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
                break;
            case '3'://A+S
                $(".tr_calc_type_as").show();
                $(".tr_calc_type_s, .tr_calc_type_a, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
                break;
            case '4'://A+S+复借
                $(".tr_calc_type_asfj").show();
                $(".tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_a, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
                break;
            case '5'://A阶梯
                $(".tr_calc_type_ajt").show();
                $(".tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_a, .tr_calc_type_sjt").hide();
                break;
            case '6'://S阶梯
                $(".tr_calc_type_sjt").show();
                $(".tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_a").hide();
                break;
            default:
                $(".tr_calc_type_a, .tr_calc_type_s, .tr_calc_type_as, .tr_calc_type_asfj, .tr_calc_type_ajt, .tr_calc_type_sjt").hide();
        }
    },

    //结算规则必填检测
    checkRule:function(calc_type) {
        if(!calc_type || calc_type == 0){
            return true;
        }

        //A、S、A+S、A+S+复借、A阶梯、S阶梯
        switch(calc_type) {
            case "1"://A
                var txt_type_a_xzzc = $("#txt_type_a_xzzc").val();
                var txt_type_a_scsq = $("#txt_type_a_scsq").val();
                var txt_type_a_scfk = $("#txt_type_a_scfk").val();
                if(!txt_type_a_xzzc){
                    alert("新增注册选项不能为空！");
                    $("#txt_type_a_xzzc").focus();
                    return false;
                }
                if(!txt_type_a_scsq){
                    alert("首次申请选项不能为空！");
                    $("#txt_type_a_scsq").focus();
                    return false;
                }
                if(!txt_type_a_scfk){
                    alert("首次放款选项不能为空！");
                    $("#txt_type_a_scfk").focus();
                    return false;
                }
                break;
            case "2"://S
                var txt_type_s_fkfc = $("#txt_type_s_fkfc").val();
                if(!txt_type_s_fkfc){
                    alert("首次放款分成选项不能为空！");
                    $("#txt_type_a_scfk").focus();
                    return false;
                }
                break;
            case "3"://A+S
                var txt_type_as_xzzc = $("#txt_type_as_xzzc").val();
                var txt_type_as_scsq = $("#txt_type_as_scsq").val();
                var txt_type_as_scfk = $("#txt_type_as_scfk").val();
                var txt_type_as_fkfc = $("#txt_type_as_fkfc").val();
                if(!txt_type_as_xzzc){
                    alert("新增注册选项不能为空！");
                    $("#txt_type_as_xzzc").focus();
                    return false;
                }
                if(!txt_type_as_scsq){
                    alert("首次申请选项不能为空！");
                    $("#txt_type_as_scsq").focus();
                    return false;
                }
                if(!txt_type_as_scfk){
                    alert("首次放款选项不能为空！");
                    $("#txt_type_as_scfk").focus();
                    return false;
                }
                if(!txt_type_as_fkfc){
                    alert("首次放款分成选项不能为空！");
                    $("#txt_type_as_fkfc").focus();
                    return false;
                }
                break;
            case "4"://A+S+复借
                var txt_type_asfj_xzzc = $("#txt_type_asfj_xzzc").val();
                var txt_type_asfj_scsq = $("#txt_type_asfj_scsq").val();
                var txt_type_asfj_scfk = $("#txt_type_asfj_scfk").val();
                var txt_type_asfj_fkfc = $("#txt_type_asfj_fkfc").val();
                var txt_type_asfj_fjfc = $("#txt_type_asfj_fjfc").val();
                if(!txt_type_asfj_xzzc){
                    alert("新增注册选项不能为空！");
                    $("#txt_type_asfj_xzzc").focus();
                    return false;
                }
                if(!txt_type_asfj_scsq){
                    alert("首次申请选项不能为空！");
                    $("#txt_type_asfj_scsq").focus();
                    return false;
                }
                if(!txt_type_asfj_scfk){
                    alert("首次放款选项不能为空！");
                    $("#txt_type_asfj_scfk").focus();
                    return false;
                }
                if(!txt_type_asfj_fkfc){
                    alert("首次放款分成选项不能为空！");
                    $("#txt_type_asfj_fkfc").focus();
                    return false;
                }
                if(!txt_type_asfj_fjfc){
                    alert("复借分成选项不能为空！");
                    $("#txt_type_asfj_fjfc").focus();
                    return false;
                }
                break;
            case "5"://A阶梯
                //新增注册
                for(var i = 1;i <= 7; i++) {
                    var txt_type_ajt_xzzc = $("#txt_type_ajt_xzzc"+i).val();
                    if(!txt_type_ajt_xzzc){
                        alert("新增注册选项不能为空！");
                        $("#txt_type_ajt_xzzc"+i).focus();
                        return false;
                    }
                }
                //首次申请
                for(var i = 1;i <= 7; i++) {
                    var txt_type_ajt_scsq = $("#txt_type_ajt_scsq"+i).val();
                    if(!txt_type_ajt_scsq){
                        alert("首次申请选项不能为空！");
                        $("#txt_type_ajt_scsq"+i).focus();
                        return false;
                    }
                }
                //首次放款
                for(var i = 1;i <= 7; i++) {
                    var txt_type_ajt_scfk = $("#txt_type_ajt_scfk"+i).val();
                    if(!txt_type_ajt_scfk){
                        alert("首次放款选项不能为空！");
                        $("#txt_type_ajt_scfk"+i).focus();
                        return false;
                    }
                }
                break;
            case "6"://S阶梯
                //首次放款分成
                for(var i = 1;i <= 11; i++) {
                    var txt_type_sjt_fkfc = $("#txt_type_sjt_fkfc"+i).val();
                    if(!txt_type_sjt_fkfc){
                        alert("首次放款分成选项不能为空！");
                        $("#txt_type_sjt_fkfc"+i).focus();
                        return false;
                    }
                }
                break;
            default://A
                var txt_type_a_xzzc = $("#txt_type_a_xzzc").val();
                var txt_type_a_scsq = $("#txt_type_a_scsq").val();
                var txt_type_a_scfk = $("#txt_type_a_scfk").val();
                if(!txt_type_a_xzzc){
                    alert("新增注册选项不能为空！");
                    $("#txt_type_a_xzzc").focus();
                    return false;
                }
                if(!txt_type_a_scsq){
                    alert("首次申请选项不能为空！");
                    $("#txt_type_a_scsq").focus();
                    return false;
                }
                if(!txt_type_a_scfk){
                    alert("首次放款选项不能为空！");
                    $("#txt_type_a_scfk").focus();
                    return false;
                }
        }
        return true;
    }
}