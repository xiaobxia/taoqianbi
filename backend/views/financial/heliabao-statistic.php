<?php
$this->shownav('financial', 'menu_debit_helibao');
$this->showsubmenu('合利宝参数统计');
?>
<style>
    table {
        width: 100%;
    }
    table th {
        text-align: left;
    }
    table td {
        text-align: left;
    }
</style>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>类目</th>
        <th>总次数</th>
        <th>成功次数</th>
        <th>成功率</th>
        <th>失败率</th>
    </tr>
    <tr class="hover">
        <td>总还款</td>
        <td><?php echo isset($info['sum']) ? $info['sum']:0;?></td>
        <td><?php echo isset($info['sum_succ']) ? $info['sum_succ']:0;?></td>
        <td><?php echo (!empty($info['sum'])) ? sprintf("%0.2f",($info['sum_succ']/$info['sum'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['sum'])) ? sprintf("%0.2f",(($info['sum'] - $info['sum_succ'])/$info['sum'])*100)."%" : '-'?></td>
    </tr>
    <tr class="hover">
        <td>系统代扣</td>
        <td><?php echo isset($info['sys']) ? $info['sys']:0;?></td>
        <td><?php echo isset($info['sys_succ']) ? $info['sys_succ']:0;?></td>
        <td><?php echo (!empty($info['sys'])) ? sprintf("%0.2f",($info['sys_succ']/$info['sys'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['sys'])) ? sprintf("%0.2f",(($info['sys'] - $info['sys_succ'])/$info['sys'])*100)."%" : '-'?></td>
    </tr>
    <tr class="hover">
        <td>催收扣款</td>
        <td><?php echo isset($info['collection']) ? $info['collection']:0;?></td>
        <td><?php echo isset($info['collection_succ']) ? $info['collection_succ']:0;?></td>
        <td><?php echo (!empty($info['collection'])) ? sprintf("%0.2f",($info['collection_succ']/$info['collection'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['collection'])) ? sprintf("%0.2f",(($info['collection'] - $info['collection_succ'])/$info['collection']*100))."%" : '-'?></td>
    </tr>
    <tr class="hover">
        <td>后台代扣</td>
        <td><?php echo isset($info['backend']) ? $info['backend']:0;?></td>
        <td><?php echo isset($info['backend_succ']) ? $info['backend_succ']:0;?></td>
        <td><?php echo (!empty($info['backend'])) ? sprintf("%0.2f",($info['backend_succ']/$info['backend'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['backend'])) ? sprintf("%0.2f",(($info['backend'] - $info['backend_succ'])/$info['backend'])*100)."%" : '-'?></td>
    </tr>
    <tr class="hover">
        <td>小额代扣</td>
        <td><?php echo isset($info['little']) ? $info['little']:0;?></td>
        <td><?php echo isset($info['little_succ']) ? $info['little_succ']:0;?></td>
        <td><?php echo (!empty($info['little'])) ? sprintf("%0.2f",($info['little_succ']/$info['little'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['little'])) ? sprintf("%0.2f",(($info['little'] - $info['little_succ'])/$info['little'])*100)."%" : '-'?></td>
    </tr>
    <tr class="hover">
        <td>主动还款</td>
        <td><?php echo isset($info['active']) ? $info['active']:0;?></td>
        <td><?php echo isset($info['active_succ']) ? $info['active_succ']:0;?></td>
        <td><?php echo (!empty($info['active'])) ? sprintf("%0.2f",($info['active_succ']/$info['active'])*100)."%" : '-'?></td>
        <td><?php echo (!empty($info['active'])) ? sprintf("%0.2f",(($info['active'] - $info['active_succ'])/$info['active'])*100)."%" : '-'?></td>
    </tr>
</table>
<div>
    <h3>今日能失败<span><?php echo $info['sum']*0.3 ?></span>次，已失败<span><?php echo ($info['sum'] >= $info['sum_succ']) ? ($info['sum'] - $info['sum_succ']):0; ?></span>次，最多能扣次数<span><?php echo (($info['sum'] * 0.3) >= ($info['sum'] - $info['sum_succ'])) ? (($info['sum'] * 0.3) - ($info['sum'] - $info['sum_succ'])) : 0; ?></span>次</h3>
</div>
