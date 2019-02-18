<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\loanPerson;
use common\helpers\Url;
use common\models\CreditFkb;

?>
<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的face++信息</span>
        </th>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr>
        <td>
            <a onclick="getFaceInfo(<?php echo $info['loanPerson']['id'];?>)" href="JavaScript:;">点击获取信息</a>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <?php if(!empty($info['res'])):?>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">confidence</span>
                        </td>
                        <td>
                            <span class="label label-success"><?php echo $info['res']['confidence']; ?></span>
                        </td>
                    </tr>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">1e-3</span>
                        </td>
                        <td>
                            <span class="label label-success"><?php echo $info['res']['1e-3']; ?></span>
                        </td>
                    </tr>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">1e-4</span>
                        </td>
                        <td>
                            <span class="label label-success"><?php echo $info['res']['1e-4']; ?></span>
                        </td>
                    </tr>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">1e-5</span>
                        </td>
                        <td>
                            <span class="label label-success"><?php echo $info['res']['1e-5']; ?></span>
                        </td>
                    </tr>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">1e-6</span>
                        </td>
                        <td>
                            <span class="label label-success"><?php echo $info['res']['1e-6']; ?></span>
                        </td>
                    </tr>
                <?php else:?>
                    <tr class="ng-scope">
                        <td>
                            <span class="label label-success">暂无记录</span>
                        </td>
                    </tr>
                <?php endif;?>
            </table>
        </td>
    </tr>
</table>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>
<script>
    function getFaceInfo(id,type){
        var url = '<?php echo Url::toRoute(['fkb/get-info-face']);?>';
        var params = {
          id : id,
          type : type
        };
        var ret = confirmMsg('确认获取');
        if(! ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert(data.message);
                location.reload(true);
            }else{
                alert(data.message);
            }
        },'json')
    }

    var reminder_count = 0;
    var reminder_str;
    function getAllInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();
        var url = '<?php echo Url::toRoute(['fkb/get-fkb-info']);?>';
        //贷款黑名单
        params = {
            id:id,
            type:<?php echo CreditFkb::LOANBLACKLIST;?>
        };
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('贷款黑名单：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //个人违约信息
        params.type = <?php echo CreditFkb::PERSONBREAKINFO;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('个人违约信息：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //个人失信黑名单
        params.type = <?php echo CreditFkb::PERSONBREAKBLACKINFO;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('个人失信黑名单：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //P2P失信黑名单
        params.type = <?php echo CreditFkb::P2PBREAKLIST;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('P2P失信黑名单：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //被信贷机构查询记录
        params.type = <?php echo CreditFkb::CREDITAGENCYQUERYRECORD;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('被信贷机构查询记录：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //手机核查
        params.type = <?php echo CreditFkb::PHONECHECKOUT;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('手机核查：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //贷款重复申请
        params.type = <?php echo CreditFkb::LOANREPEATAPPLY;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('贷款重复申请：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //网贷黑名单
        params.type = <?php echo CreditFkb::NETLOANBLACKLIST;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('网贷黑名单：获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');
    }

    function reminder(){
        if(reminder_count == 8){
            document.getElementById("close").style.display ="block";
            reminder_count = 0;
        }
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


