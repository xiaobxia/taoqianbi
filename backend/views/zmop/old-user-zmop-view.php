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
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的芝麻信用信息历史查询记录</span>
           <span style="color: black">请选择历史查询时间:
                <select name="" class="selectShow">
                    <?php foreach ($creditZmop as $key=>$val): ?>
                        <option value="<?php echo $key ?>"><?php echo date('Y-m-d H:i:s',$val['created_at']) ?></option>
                    <?php endforeach; ?>
                </select>
        </th>
    </tr>
</table>
<?php
$i = 0;
foreach ($creditZmop as $info):?>
    <div class="selectShow" id="tb_<?php echo $i++ ?>">
<table class="tb tb2 fixpadding">
    <?php   if(empty($creditZmop)): ?>
        <tr>
             <td>
                无历史查询记录
            </td>
        </tr>
    <?php   endif; ?>
    <tr>
        <td>
            <span style="color:red;">数据更新于 <?php echo date('Y-m-d H:i:s',$info['created_at']);?> </span>

        </td>
    </tr>
    <tr>
        <td>芝麻信用评分</td>
        <td>
            <table>
                <?php if (! empty($info['zm_score'])): ?>

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
    <!--
    <tr>
        <td>IVS信息验证信息</td>
        <td>
            <table>
                <?php if(! empty($info['ivs_info'])): ?>
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
                    </tr>
                <?php endif ?>
            </table>
        </td>
    </tr>
    -->
    <!--
    <tr>
        <td>DAS认证信息</td>
        <td>
            <table>
                <?php if(! empty($info['das_info'])): ?>

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
 </div>
<?php endforeach; ?>

<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script type="text/javascript">

    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto();
    });

    $(".selectShow").change(function(){
          checkauto();
    })

    function checkauto(){
         var show_id = $(".selectShow option:selected").val();

          $(".selectShow option").each(function(){
              if($(this).val() == show_id) {
                 $("#tb_"+show_id).show();   //当前的显示
              } else {
                 $("#tb_"+$(this).val()).hide();
              }
          })
    }

</script>
