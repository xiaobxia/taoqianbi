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
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的芝麻信用信息</span>
            <?php if(CreditZmop::STATUS_1 == $info['status']):?>
                <span style="color:green">已授权</span>
            <?php elseif(CreditZmop::STATUS_2 == $info['status']):?>
                <span style="color:red">授权已取消,请联系用户重新授权</span>
            <?php endif;?>

        </th>
    </tr>
    <tr>
        <td>
            <?php if(!empty($info['status']) && $info['status'] == CreditZmop::STATUS_1): ?>
                <a style="color:red" onclick="getAllInfo(<?php echo $info['person_id'];?>)" href="javascript:;">点击获取所有信息</a>
            <?php else:?>
                <a href="javascript:;" onclick="sendZmMsm('<?php echo $info['person_id'];?>')">点击发送授权短信</a>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td>芝麻信用评分</td>
        <td>
            <table>
                <?php if (! empty($info['zm_score'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $zmScoreTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $info['person_id'];?>','<?php echo CreditZmop::ZM_TYPE_SCORE;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            信用评分：<?php echo $info['zm_score'] ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td>暂无信息</td>
                    </tr>
                <?php endif ?>

            </table>
        </td>
    </tr>
<!--
    <tr>
        <td>手机RAIN分</td>
        <td>
            <table>
                <?php if(! empty($info['rain_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $rainTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $info['person_id'];?>','<?php echo CreditZmop::ZM_TYPE_RAIN;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            RAIN分(取值为0-100。得分越高，风险越高)：<?php echo $info['rain_score'] ?> </br>
                            <?php foreach (json_decode($info['rain_info']) as $val): ?>
                                <?php echo $val->name ?>: <?php echo $val->description ?> </br>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td>暂无信息</td>
                    </tr>
                <?php endif; ?>
            </table>
        </td>
    </tr>
     -->
    <tr>
        <td>行业关注名单</td>
        <td>
            <table>
                <?php if (! empty($info['watch_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $watchTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $info['person_id'];?>','<?php echo CreditZmop::ZM_TYPE_WATCH;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php foreach(json_decode($info['watch_info']) as $val ): ?>
                                风险信息行业：<?php echo CreditZmop::$iwatch_type[$val->biz_code] ?> <br/>
                                风险类型：<?php echo CreditZmop::$risk_type[$val->type] ?> <br/>
                                风险说明：<?php echo CreditZmop::$risk_code[$val->code] ?> <br/>
                                负面信息或者风险信息：<?php echo $val->level ?> (取值：1=有负面信息，2=有风险信息)<br/>
                                数据刷新时间：<?php echo $val->refresh_time ?> <br/>
                                <?php if(!empty($val->extend_info)) :?>
                                    <?php foreach($val->extend_info as $v): ?>
                                        芝麻信用申诉id: <?php echo $v->value;?><br/>
                                    <?php endforeach ?>
                                <?php endif ?>
                                <br/>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php elseif($info['watch_matched'] == 1): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $watchTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $info['person_id'];?>','<?php echo CreditZmop::ZM_TYPE_WATCH;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            行业关注未匹配
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td>暂无信息</td>
                    </tr>
                <?php endif ?>
            </table>
        </td>
    </tr>
    <tr>
        <td>IVS信息验证信息</td>
        <td>
            <table>
                <?php if(!empty($info['ivs_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $ivsTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $loanPerson['id'];?>','<?php echo CreditZmop::ZM_TYPE_IVS;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            IVS评分(取值区间为0-100。分数越高，表示可信程度越高。0表示无对应数据)：<?php echo $info['ivs_score'] ?> </br>
                            <?php foreach(json_decode($info['ivs_info']) as $val): ?>
                                <?php echo $val->description ?> </br>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td>暂无信息</td>
                        <td>
                            <a onclick="getZmInfo('<?php echo $loanPerson['id'];?>','<?php echo CreditZmop::ZM_TYPE_IVS;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                <?php endif ?>
            </table>
        </td>
    </tr>
    <!--
    <tr>
        <td>DAS认证信息</td>
        <td>
            <table>
                <?php if(! empty($info['das_info'])): ?>
                    <tr>
                        <td>
                            <span style="color:black;">数据更新于 <?php echo $dasTime;?> </span>
                            <a onclick="getZmInfo('<?php echo $info['person_id'];?>','<?php echo CreditZmop::ZM_TYPE_DAS;?>')" href="javascript:;">点击查询最新数据</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php foreach(json_decode($info['das_info']) as $v): ?>
                                <?php echo CreditZmop::$das_keys[$v->key] . ':';?>
                                <?php echo isset(CreditZmop::$map[$v->key]) ? CreditZmop::${CreditZmop::$map[$v->key]}[$v->value] : $v->value;?>
                                <br/>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td>暂无信息</td>
                    </tr>
                <?php endif ?>
            </table>
        </td>
    </tr>
    -->
</table>

 <br><a href="<?php echo Url::toRoute(['zmop/old-user-zmop-view','id'=>$id]) ?>" target="_blank" style="border: 1px solid;padding: 5px;color: #555">历史查询记录</a>


<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>
<script>

    var reminder_count = 0;
    var reminder_str;


    function getZmInfo(id,type){
        var url = '<?php echo Url::toRoute(['zmop/get-zmop-info']);?>';
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
                location.reload(true);
            }else{
                alert(data.message);
                location.reload(true);
            }
        },'json')
    }

    function sendZmMsm(id){
        var url = '<?php echo Url::toRoute(['zmop/batch-feedback']);?>';
        var params = {id:id};
        var ret = confirmMsg('确认发送');
        if(!ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('发送成功');
            }else{
                alert(data.message);
            }
        },'json')
    }

    function getAllInfo(id){
        if(!confirmMsg('确认获取')){
            return false;
        }
        showDiv();
        var url = '<?php echo Url::toRoute(['zmop/get-zmop-info']);?>';
        //获取芝麻评分
        params = {
            id:id,
            type:<?php echo CreditZmop::ZM_TYPE_SCORE;?>
        };
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('芝麻信用评分获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //获取手机rain分
//        params.type = <?php //echo CreditZmop::ZM_TYPE_RAIN;?>//;
//        $.get(url,params,function(data){
//            reminder_count += 1;
//            if(data.code == 0){
//                insertToShow('手机RAIN评分获取成功',true);
//            }else{
//                insertToShow(data.message,false);
//        }
//            reminder();
//        },'json');

        //获取行业关注名单
        params.type = <?php echo CreditZmop::ZM_TYPE_WATCH;?>;
        $.get(url,params,function(data){
            reminder_count += 1;
            if(data.code == 0){
                insertToShow('行业关注名单获取成功',true);
            }else{
                insertToShow(data.message,false);
            }
            reminder();
        },'json');

        //获取IVS信息
//        params.type = <?php //echo CreditZmop::ZM_TYPE_IVS;?>//;
//        $.get(url,params,function(data){
//            reminder_count += 1;
//            if(data.code == 0){
//                insertToShow('IVS信息获取成功',true);
//            }else{
//                insertToShow(data.message,false);
//            }
//            reminder();
//        },'json');

        //获取DAS信息
//        params.type = <?php //echo CreditZmop::ZM_TYPE_DAS;?>//;
//        $.get(url,params,function(data){
//            reminder_count += 1;
//            if(data.code == 0){
//                insertToShow('DAS信息获取成功',true);
//            }else{
//                insertToShow(data.message,false);
//            }
//            reminder();
//        },'json');
    }

    function reminder(){
        if(reminder_count == 2){
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


