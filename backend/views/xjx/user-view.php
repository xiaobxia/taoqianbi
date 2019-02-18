<?php
use common\services\YkXjxSupportService;
?>

<style>
    #bg{ display: none; position: absolute; top: 0%; left: 0%; width: 100%; height: 100%; background-color: black; z-index:1001; -moz-opacity: 0.7; opacity:.70; filter: alpha(opacity=70);}
    #show{display: none; position: absolute; top: 25%; left: 22%; width: 53%; height: 49%; padding: 8px; border: 8px solid #E8E9F7; background-color: white; z-index:1002; overflow: auto;}
    #content{overflow: auto;width: 100%; height: 80%; }
    #close{display:none;font-weight:bold;border:2px solid #E8E9F7;color:#E8E9F7;padding:1rem 2rem;border-radius:0.3rem;cursor:pointer;background-color:gray;width:50px;margin-left:auto;margin-right:auto;TEXT-ALIGN: center;}
    tr{border:grey solid 1px}
    .son td{border:grey solid 1px}
    .son {text-align: center}
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
        <?php if (empty($data)) : ?>
            <span style="font-size:20px">用户不存在</span>
        <?php else : ?>
            <span style="font-size:20px"><?php echo $name;?>的现金侠信息</span>
        <?php endif; ?>
        </th>
    </tr>

    <?php if (!empty($data)) : ?>
    <?php foreach($data as $_plat => $_plat_ary):?>
        <?php $_display_usr = false; ?>

        <?php foreach($_plat_ary as $v) : ?>
        <?php if (! $_display_usr) : $_display_usr = true; ?>
        <tr style="height: 20px;">
            <td width="200"> <?php echo $_plat ?> 用户信息</td>
            <td>
                <table class="son">
                    <tr>
                        <td>用户id</td>
                        <td>平台id</td>
                        <td>用户手机号</td>
                        <td>真实姓名</td>
                        <td>身份证号</td>
                        <td>注册时间</td>
                        <td>卡号</td>
                    </tr>
                    <tr>
                        <td><?php echo $v['user_id'];?></td>
                        <td><?php echo $v['platform_id'];?></td>

                        <td><?php echo $v['user_phone'];?></td>
                        <td><?php echo $v['realname'];?></td>

                        <td><?php echo $v['id_number'];?></td>
                        <td><?php echo $v['user_createTime'];?></td>

                        <td><?php echo $v['card_no'];?></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php endif ?>
        <tr>
            <td width="200"> <?php echo $_plat ?> 订单信息</td>
            <td>
                <table class="son">
                    <tr>
                        <td>订单id</td>
                        <td>是否是老用户</td>

                        <td>订单金额(元)</td>
                        <td>借款期限</td>

                        <td>申请日期</td>
                        <td>还款日</td>

                        <td>借款服务费</td>
                        <td>滞纳金</td>

                        <td>借款服务费利率</td>
                        <td>订单状态</td>
                    </tr>
                    <tr>
                       <?php if (!empty($v['order_id'])) : ?>
                            <td><?php echo $v['order_id'];?></td>
                            <td><?php echo $v['customer_type'];?></td>

                            <td><?php echo bcdiv($v['money_amount'], 100, 2);?></td>
                            <td><?php echo $v['loan_term'];?></td>

                            <td><?php echo $v['order_createTime'];?></td>
                            <td><?php echo $v['repayment_time'];?></td>

                            <td><?php echo bcdiv($v['loan_interests'], 100, 2) ?></td>
                            <td><?php echo bcdiv($v['plan_late_fee'], 100, 2) ?></td>

                            <td><?php echo bcdiv($v['apr'], 100, 2) . '%' ?></td>
                            <td>
                            <?php
                                if (empty($v['status']) || !isset(YkXjxSupportService::$status_list[$v['status']])) {
                                    echo '状态为空';
                                }
                                else {
                                    echo YkXjxSupportService::$status_list[$v['status']];
                                }
                            ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                </table>
            </td>
        </tr>
        <?php endforeach;?>
    <?php endforeach;?>
    <?php endif;?>
</table>

<div id="bg"></div>
<div id="show">
    <div id="content"></div>
    <div id="close" onclick="hideDiv()">关闭</div>
</div>


