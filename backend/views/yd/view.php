<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\helpers\Url;
use common\models\CreditYd;
?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
</style>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的有盾信息</span>
        </th>
    </tr>
    <tr>
        <td class="td31">
            身份证泄漏查询
        </td>
        <td>
            <?php if(is_null($info['idNumberLeak'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_ID_NUMBER_LEAK;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$id_number_leak_map[$info['idNumberLeak']['data']])?CreditYd::$id_number_leak_map[$info['idNumberLeak']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['idNumberLeak']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            法院失信个人
        </td>
        <td>
            <?php if(is_null($info['courtLoseCreditPerson'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_COURT_LOSE_CREDIT_PERSON;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['courtLoseCreditPerson']['data']])?CreditYd::$common_status_map[$info['courtLoseCreditPerson']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['courtLoseCreditPerson']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-手机号
        </td>
        <td>
            <?php if(is_null($info['stolenCardBlacklistPhone'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_STOLEN_CARD_BLACKLIST_PHONE;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['stolenCardBlacklistPhone']['data']])?CreditYd::$common_status_map[$info['stolenCardBlacklistPhone']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['stolenCardBlacklistPhone']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-身份证
        </td>
        <td>
            <?php if(is_null($info['stolenCardBlacklistIdNumber'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_STOLEN_CARD_BLACKLIST_ID_NUMBER;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['stolenCardBlacklistIdNumber']['data']])?CreditYd::$common_status_map[$info['stolenCardBlacklistIdNumber']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['stolenCardBlacklistIdNumber']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-银行卡号
        </td>
        <td>
            <?php if(is_null($info['stolenCardBlacklistCard'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_STOLEN_CARD_BLACKLIST_CARD_NUM;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['stolenCardBlacklistCard']['data']])?CreditYd::$common_status_map[$info['stolenCardBlacklistCard']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['stolenCardBlacklistCard']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            国际反洗钱制裁名单
        </td>
        <td>
            <?php if(is_null($info['moneyLaunderingSanctionlist'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_MONEY_LAUNDERING_SANCATIONLIST;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['moneyLaunderingSanctionlist']['data']])?CreditYd::$common_status_map[$info['moneyLaunderingSanctionlist']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['moneyLaunderingSanctionlist']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            p2p失信名单
        </td>
        <td>
            <?php if(is_null($info['p2pLoseCreditList'])):?>
                <a onclick="getInfo(<?php echo $info['product_id'];?>,<?php echo $info['order_id'];?>,<?php echo CreditYd::TYPE_P2P_LOSE_CREDIT_LIST;?>)" href="javascript:;">点击获取</a>
            <?php else:?>
                <?php echo isset(CreditYd::$common_status_map[$info['p2pLoseCreditList']['data']])?CreditYd::$common_status_map[$info['p2pLoseCreditList']['data']]:'数据解析错误';?>
                <br/>
                数据获取于<?php echo date('Y-m-d H:i:s',$info['p2pLoseCreditList']['updated_at']);?>
            <?php endif;?>
        </td>
    </tr>
</table>
<br><a href="<?php echo Url::toRoute(['yd/old-view','id'=>$info['id']]) ?>" target='_blank' style="border: 1px solid;padding: 5px;color: #555">历史查询记录</a>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script>

    var reminder_count = 0;
    var reminder_str;

    function getInfo(product_id,order_id,type,reminder){
        if(!reminder){
            if(!confirmMsg('确认获取')){
                return false;
            }
        }

        var url = '<?php echo Url::toRoute(['yd/get-info']);?>';
        var params = {
            product_id : product_id,
            order_id : order_id,
            type : type
        };
        $.get(url,params,function(data){
            if(data.code == 0){
                if(reminder){
                    insertToShow(data.message,true);
                }else{
                    alert('获取成功');
                    location.reload(true);
                }
            }else{
                if(reminder){
                    insertToShow(data.message,false);
                }else{
                    alert(data.message);
                }
            }
        },'json')
    }


    function getAllInfo(product_id,order_id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();
        $.ajaxSetup({
            async: false
        });
        var type = [1,2,3,4,5,6,7];
        for (var i in type){
            getInfo(product_id,order_id,type[i],true);
        }
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

