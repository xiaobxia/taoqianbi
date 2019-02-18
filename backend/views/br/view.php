<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\helpers\Url;
use common\models\CreditBr;
use common\services\BrService;

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
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的百融信息</span>
        </th>
    </tr>
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a onclick="getInfo('<?php echo $info['loanPerson']['id'];?>','<?php echo CreditBr::SPECIAL_LIST;?>')" href="javascript:;">特殊名单核查</a>
                    </td>
                </tr>
                <?php if (isset($info['res']['special_list']) && !empty($info['res']['special_list'])){ ?>
                    <tr>
                        <td>
                            <br/>取“空/0/1/2”；空：未命中，0：本人直接命中，1：一度关系命中，2：二度关系命中
                            <?php
                            if (is_array($info['res']['special_list'])){
                                foreach ($info['res']['special_list'] as $key =>$val){
                                    echo '<br/>'.$key.'：'.$val;
                                }
                            }else{
                                echo $info['res']['special_list'];
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a onclick="getInfo('<?php echo $info['loanPerson']['id'];?>','<?php echo CreditBr::APPLY_LOAN_STR;?>')" href="javascript:;">多次申请核查V2</a>
                    </td>
                </tr>
                <?php if (isset($info['res']['apply_loan_str']) && !empty($info['res']['apply_loan_str'])){ ?>
                <tr id="bairong-wrap" style="font-size: 14px">
                    <td id="bairong-data" style="display: none;">
                        <br/>取"空/0/N"；空：无申请记录；N申请记录详情
                        <?php
                        if (is_array($info['res']['apply_loan_str'])){
                            foreach ($info['res']['apply_loan_str'] as $key =>$val){
                                echo '<p>'.$key.'：'.$val.'</p>';
                            }
                        }else{
                            echo $info['res']['apply_loan_str'];
                        }
                        ?>
                    </td>
                </tr>
                <tr id="bairong-wrap-2" style="font-size: 14px">
                    <?php } ?>
            </table>
        </td>
    </tr>
    <?php if (isset($info['res']['register_equipment']) && !empty($info['res']['register_equipment'])){ ?>
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            注册设备信息：
                            <?php
                            if (is_array($info['res']['register_equipment'])){
                                foreach ($info['res']['register_equipment'] as $key =>$val){
                                    echo '<br/>'.$key.'：'.$val;
                                }
                            }else{
                                echo $info['res']['register_equipment'];
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
    <?php if (isset($info['res']['sign_equipment']) && !empty($info['res']['sign_equipment'])){ ?>
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            登录设备信息：
                            <?php
                            if (is_array($info['res']['sign_equipment'])){
                                foreach ($info['res']['sign_equipment'] as $key =>$val){
                                    echo '<br/>'.$key.'：'.$val;
                                }
                            }else{
                                echo $info['res']['sign_equipment'];
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
    <?php if (isset($info['res']['loan_equipment']) && !empty($info['res']['loan_equipment'])){ ?>
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            借款设备信息：
                            <?php
                            if (is_array($info['res']['loan_equipment'])){
                                foreach ($info['res']['loan_equipment'] as $key =>$val){
                                    echo '<br/>'.$key.'：'.$val;
                                }
                            }else{
                                echo $info['res']['loan_equipment'];
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
    <?php if (isset($info['res']['equipment_check']) && !empty($info['res']['equipment_check'])){ ?>
        <tr>
            <td>
                <table>
                    <tr>
                        <td>
                            设备信息核查：
                            <?php
                            if (is_array($info['res']['equipment_check'])){
                                foreach ($info['res']['equipment_check'] as $key =>$val){
                                    echo '<br/>'.$key.'：'.$val;
                                }
                            }else{
                                echo $info['res']['equipment_check'];
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php } ?>
</table>
<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script>

    var reminder_count = 0;
    var reminder_str;

    function getInfo(id,type,reminder){
        if(!reminder){
            if(!confirmMsg('确认获取')){
                return false;
            }
        }

        var url = '<?php echo Url::toRoute(['br/get-info']);?>';
        var params = {
            id : id,
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

    function reRenderBaiRong() {
        var list1 = []
        var list2 = []
        var list3 = []
        var list4= []
        var list5= []
        var list6= []
        $('#bairong-data p').each(function() {
            var text = $(this).text()
            if (text.indexOf('近7天') !== -1) {
                list1.push(text)
            } else if (text.indexOf('近15天') !== -1) {
                list2.push(text)
            } else if (text.indexOf('近1个月') !== -1) {
                list3.push(text)
            } else if (text.indexOf('近3个月') !== -1) {
                list4.push(text)
            } else if (text.indexOf('近6个月') !== -1) {
                list5.push(text)
            } else {
                list6.push(text)
            }
        })
        renderTd(list1)
        renderTd(list2)
        renderTd(list3)
        renderTd(list4, true)
        renderTd(list5, true)
        renderTd(list6, true)
    }
    reRenderBaiRong()
    function renderTd(list, ifOther) {
        if (list.length > 0) {
            var html = '<td>'
            for (var i = 0; i < list.length; i++) {
                html += '<p>'+list[i]+'</p>'
            }
            html += '</td>'
            if (ifOther) {
                $('#bairong-wrap-2').append(html)
            } else {
                $('#bairong-wrap').append(html)
            }

        }
    }

</script>

