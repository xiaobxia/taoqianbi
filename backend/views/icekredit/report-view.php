
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
        <?php if (empty($person)) : ?>
            <span style="font-size:20px">用户不存在</span>
        <?php else : ?>
            <span style="font-size:20px"><?php echo $person['name'];?>的冰鉴报告</span>
        <?php endif; ?>
        </th>
    </tr>
        <tr style="height: 20px;">
            <td>
                <table class="son">
                    <tr>
                        <td>id</td>
                        <td>用户id</td>
                        <td>冰鉴建议</td>
                        <td>通讯分</td>
                        <td>通讯分数线</td>
                        <td>支付宝分</td>
                        <td>支付宝分数线</td>
                        <td>击中规则</td>
                        <td>冰鉴额度</td>
                        <td>创建时间</td>
                    </tr>
                    <?php if (!empty($report)) : ?>
                    <tr>
                        <td><?php echo $report['id'];?></td>
                        <td><?php echo $report['user_id'];?></td>
                        <td><?php echo $report['suggestion'];?></td>
                        <td><?php echo $report['comm_score'];?></td>
                        <td><?php echo $report['comm_cutoff'];?></td>
                        <td><?php echo $report['alipay_score'];?></td>
                        <td><?php echo $report['alipay_cutoff'];?></td>
                        <td><?php echo $report['hit_details'];?></td>
                        <td><?php echo $report['amount'];?></td>
                        <td><?php echo date('Y-m-d H:i:s', $report['created_at']);?></td>

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


