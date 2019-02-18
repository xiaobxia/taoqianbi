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
            <span style="font-size:20px"><?php echo $info['loanPerson']['name'];?>的有盾信息历史查询记录</span>
        </th>
    </tr>
    <tr>
        <td class="td31">
            身份证泄漏查询
        </td>
        <td>
            <?php if(empty($info['idNumberLeak'])):?>
                    无历史查询
            <?php else:?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                <select name="" class="selectShow">
                    <?php foreach ($info['idNumberLeak'] as $key=>$val): ?>
                        <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="float: left;">
                    <?php
                        $i = 0;
                        foreach ($info['idNumberLeak'] as $val):?>
                            <div class="selectShow" id="tb_<?php echo $i++ ?>">
                                  <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                :&nbsp;
                                数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            法院失信个人
        </td>
        <td>

             <?php if(empty($info['courtLoseCreditPerson'])):?>
                    无历史查询
            <?php else:?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                <select name="" class="selectShow1">
                    <?php foreach ($info['courtLoseCreditPerson'] as $key=>$val): ?>
                        <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="float: left;">
                    <?php
                        $i = 0;
                        foreach ($info['courtLoseCreditPerson'] as $val):?>
                            <div class="selectShow1" id="tb1_<?php echo $i++ ?>">
                                  <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                :&nbsp;
                                数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-手机号
        </td>
        <td>

            <?php if(empty($info['stolenCardBlacklistPhone'])):?>
                    无历史查询
            <?php else:?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                <select name="" class="selectShow2">
                    <?php foreach ($info['stolenCardBlacklistPhone'] as $key=>$val): ?>
                        <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="float: left;">
                    <?php
                        $i = 0;
                        foreach ($info['stolenCardBlacklistPhone'] as $val):?>
                            <div class="selectShow2" id="tb2_<?php echo $i++ ?>">
                                  <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                :&nbsp;
                                数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;?>

        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-身份证
        </td>
        <td>
            <?php if(empty($info['stolenCardBlacklistIdNumber'])):?>
                    无历史查询
            <?php else:?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                <select name="" class="selectShow3">
                    <?php foreach ($info['stolenCardBlacklistIdNumber'] as $key=>$val): ?>
                        <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="float: left;">
                    <?php
                        $i = 0;
                        foreach ($info['stolenCardBlacklistIdNumber'] as $val):?>
                            <div class="selectShow3" id="tb3_<?php echo $i++ ?>">
                                  <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                :&nbsp;
                                数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;?>

        </td>
    </tr>
    <tr>
        <td class="td31">
            盗卡黑名单-银行卡号
        </td>
        <td>

            <?php if(empty($info['stolenCardBlacklistCard'])):?>
                    无历史查询
            <?php else:?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                    <select name="" class="selectShow4">
                        <?php foreach ($info['stolenCardBlacklistCard'] as $key=>$val): ?>
                            <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="float: left;">
                        <?php
                            $i = 0;
                            foreach ($info['stolenCardBlacklistCard'] as $val):?>
                                <div class="selectShow4" id="tb4_<?php echo $i++ ?>">
                                      <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                    :&nbsp;
                                    数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                                </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            国际反洗钱制裁名单
        </td>
        <td>
            <?php if(empty($info['moneyLaunderingSanctionlist'])):?>
                    无历史查询
            <?php else:?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                    <select name="" class="selectShow5">
                        <?php foreach ($info['moneyLaunderingSanctionlist'] as $key=>$val): ?>
                            <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="float: left;">
                        <?php
                            $i = 0;
                            foreach ($info['moneyLaunderingSanctionlist'] as $val):?>
                                <div class="selectShow5" id="tb5_<?php echo $i++ ?>">
                                      <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                    :&nbsp;
                                    数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                                </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif;?>
        </td>
    </tr>
    <tr>
        <td class="td31">
            p2p失信名单
        </td>
        <td>

            <?php if(empty($info['p2pLoseCreditList'])):?>
                    无历史查询
            <?php else:?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;历史查询时间：
                    <select name="" class="selectShow6">
                        <?php foreach ($info['p2pLoseCreditList'] as $key=>$val): ?>
                            <option value="<?php echo $key ?>"><?php echo date('Y-m-d',$val['created_at']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="float: left;">
                        <?php
                            $i = 0;
                            foreach ($info['p2pLoseCreditList'] as $val):?>
                                <div class="selectShow6" id="tb6_<?php echo $i++ ?>">
                                      <?php echo isset(CreditYd::$id_number_leak_map[$val['data']])?CreditYd::$id_number_leak_map[$val['data']]:'数据解析错误'; ?>
                                    :&nbsp;
                                    数据获取于<?php echo date('Y-m-d',$val['created_at']);?><br/>
                                </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif;?>
        </td>
    </tr>
</table>

<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>

<script>
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


    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto1();
    });

    $(".selectShow1").change(function(){
          checkauto1();
    })

    function checkauto1(){
         var show_id = $(".selectShow1 option:selected").val();
            console.log(show_id);
          $(".selectShow1 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb1_"+show_id).show();   //当前的显示
              } else {
                 $("#tb1_"+$(this).val()).hide();
              }
          })
    }

    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto2();
    });

    $(".selectShow2").change(function(){
          checkauto2();
    })

    function checkauto2(){
         var show_id = $(".selectShow2 option:selected").val();
            console.log(show_id);
          $(".selectShow2 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb2_"+show_id).show();   //当前的显示
              } else {
                 $("#tb2_"+$(this).val()).hide();
              }
          })
    }

    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto3();
    });

    $(".selectShow3").change(function(){
          checkauto3();
    })

    function checkauto3(){
         var show_id = $(".selectShow3 option:selected").val();
            console.log(show_id);
          $(".selectShow3 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb3_"+show_id).show();   //当前的显示
              } else {
                 $("#tb3_"+$(this).val()).hide();
              }
          })
    }

    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto4();
    });

    $(".selectShow4").change(function(){
          checkauto4();
    })

    function checkauto4(){
         var show_id = $(".selectShow4 option:selected").val();
            console.log(show_id);
          $(".selectShow4 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb4_"+show_id).show();   //当前的显示
              } else {
                 $("#tb4_"+$(this).val()).hide();
              }
          })
    }

    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto5();
    });

    $(".selectShow5").change(function(){
          checkauto5();
    })

    function checkauto5(){
         var show_id = $(".selectShow5 option:selected").val();
            console.log(show_id);
          $(".selectShow5 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb5_"+show_id).show();   //当前的显示
              } else {
                 $("#tb5_"+$(this).val()).hide();
              }
          })
    }


    $(document).ready(function(){
        $("select option:eq(0)").prop("selected", 'selected');
         checkauto6();
    });

    $(".selectShow6").change(function(){
          checkauto6();
    })

    function checkauto6(){
         var show_id = $(".selectShow6 option:selected").val();
            console.log(show_id);
          $(".selectShow6 option").each(function(){
            console.log($(this).val());
              if($(this).val() == show_id) {
                 $("#tb6_"+show_id).show();   //当前的显示
              } else {
                 $("#tb6_"+$(this).val()).hide();
              }
          })
    }
</script>

