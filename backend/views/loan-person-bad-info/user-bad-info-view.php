<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\loanPerson;
use common\helpers\Url;
use common\models\CreditZmop;

?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
    td{border:grey solid 1px}
    th{border:grey solid 1px}
</style>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px">用户不良信息命中结果</span>
            <a href="javascript:;" onclick="getCreditInfo(<?php echo $loanPerson['id'];?>)" style="margin-left:20px">点击更新征信信息</a>
            <a href="javascript:;" onclick="getBadInfo(<?php echo $loanPerson['id'];?>)" style="margin-left:20px">点击查询不良信息</a>
        </th>
    </tr>
    <tr>
        <th width="200">
            <span style="font-size:20px">黑名单命中结果</span>
        </th>
        <td>
            <table>
                <?php if(!empty($black_list)):?>
                    <tr>
                        <td>查询时间</td>
                        <td>数据来源</td>
                        <td>命中字段</td>
                        <td>命中值</td>
                        <td>描述</td>
                    </tr>
                    <?php foreach($black_list as $v):?>
                        <tr>
                            <td><?php echo $v['create_time'];?></td>
                            <td><?php echo $v['source'];?></td>
                            <td><?php echo $v['rule_type'];?></td>
                            <td><?php echo $v['value'];?></td>
                            <td><?php echo $v['desc'];?></td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr>
                        <td>黑名单未命中</td>
                    </tr>
                <?php endif;?>
            </table>
        </td>
    </tr>
    <tr>
        <th width="200">
            <span style="font-size:20px">灰名单命中结果</span>
        </th>
        <th>
            <table>
                <?php if(!empty($gray_list)):?>
                    <tr>
                        <td width="200">查询时间</td>
                        <td width="100">数据来源</td>
                        <td width="300">命中字段</td>
                        <td width="100">命中值</td>
                        <td>描述</td>
                    </tr>
                    <?php foreach($gray_list as $v):?>
                        <tr>
                            <td><?php echo $v['create_time'];?></td>
                            <td><?php echo $v['source'];?></td>
                            <td><?php echo $v['rule_type'];?></td>
                            <td><?php echo $v['value'];?></td>
                            <td><?php echo $v['desc'];?></td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr>
                        <td>黑名单未命中</td>
                    </tr>
                <?php endif;?>
            </table>
        </th>
    </tr>

</table>


<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>
<script>

    var reminder_count = 0;
    var reminder_str;

    function getCreditInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();

        $.ajaxSetup({
            async : false
        });

        var url = '<?php echo Url::toRoute(['zmop/get-zmop-info']);?>';
        //获取芝麻评分
        params = {
            id:id,
            type:<?php echo CreditZmop::ZM_TYPE_SCORE;?>
        };
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('芝麻信用评分：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //获取手机rain分
        params.type = <?php echo CreditZmop::ZM_TYPE_RAIN;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('手机RAIN评分：获取成功',true);
            }else{
                insertToShow(data.message,false);
        }
        },'json');

        //获取行业关注名单
        params.type = <?php echo CreditZmop::ZM_TYPE_WATCH;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('行业关注名单：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //获取IVS信息
        params.type = <?php echo CreditZmop::ZM_TYPE_IVS;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('IVS信息：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //获取DAS信息
        params.type = <?php echo CreditZmop::ZM_TYPE_DAS;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('DAS信息：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //获取蜜罐信息
        url = '<?php echo Url::toRoute(['jxl/get-miguan-info']);?>';
        params = {
            id : id
        };

        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('蜜罐：信息获取成功  ',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //提交同盾信息
        url = '<?php echo Url::toRoute(['td/get-report-id']);?>';
        var params = {
            id:id
        };

        $.get(url,params,function(data){
            if(data.code == 0){
                insertToShow('同盾：报表ID获取成功  ',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        //获取同盾信息
        url = '<?php echo Url::toRoute(['td/get-info']);?>';
        //获取芝麻评分
        params = {
            id:id
        };
        $.get(url,params,function(data){
            if(data.code == 0){
                insertToShow('同盾：报表信息获取成功  ',true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');

        document.getElementById("close").style.display ="block";
    }

    function getBadInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();
        url = '<?php echo Url::toRoute(['loan-person-bad-info/check-bad-info']);?>';
        params = {
            id : id
        };
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow(data.message,true);
            }else{
                insertToShow(data.message,false);
            }
        },'json');
        document.getElementById("close").style.display ="block";
    }


    function showDiv() {
        document.body.style.overlfow='hidden';
        document.documentElement.style.overflow = 'hidden';
        document.getElementById("bg").style.display ="block";
        document.getElementById("show").style.display ="block";
    }
    function hideDiv() {
        document.body.style.overlfow='auto';
        document.documentElement.style.overflow = 'auto';
        document.getElementById("bg").style.display ="none";
        document.getElementById("show").style.display ="none";
        location.reload(true);
    }

    function insertToShow(message,code){
        if(code){
            var str = '<div style="color:darkgreen">'+message+'</div>';
        }else{
            var str = '<div style="color:red">'+message+'</div>';
        }

        $('#content').append(str);
    }


</script>


