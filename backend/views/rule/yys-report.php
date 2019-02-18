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
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table td{
        border:1px solid darkgray;

    }
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的运营商基本报告</span>

        </th>
    </tr>
    <!--
    <tr>
        <td>
            <a style="color:red" onclick="getJxlInfo(<?php echo $loanPerson['id'];?>)" href="JavaScript:;">点击获取报表</a>
        </td>
    </tr>
    -->
</table>
<?php if(empty($data)):?>
    暂无报告
<?php endif?>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="25">
            <span style="font-size:20px">通讯录</span>
        </th>
    </tr>
    <?php if(!empty($data['common_contactors'])):?>
        <tr>
            <th>姓名</th>
            <th>手机号</th>

        </tr>
        <?php foreach($data['common_contactors'] as $item):?>
            <tr>
                <td><?php echo $item['name'];?></td>
                <td><?php echo $item['phone'];?></td>
            </tr>
        <?php endforeach;?>
    <?php else:?>
        <tr><td>暂无数据</td></tr>
    <?php endif;?>
</table>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="25">
            <span style="font-size:20px">实名认证状态</span>
        </th>
    </tr>

    <tr>
        <th>实名认证状态</th>
        <th>实名认证日期</th>
    </tr>
    <tr>
        <td><?php echo (isset($data['real_name_status']) && $data['real_name_status']== 1) ? '已实名' : "未实名"; ?></td>
        <td><?php echo isset($data['real_name_time'])? $data['real_name_time']:""; ?></td>
    </tr>



</table>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="25">
            <span style="font-size:20px">话费单</span>
        </th>
    </tr>
    <?php if(!empty($data['bill_list'])):?>
        <tr>
            <th>日期</th>
            <th>金额</th>

        </tr>
        <?php foreach($data['bill_list'] as $item):?>
            <tr>
                <td><?php echo $item['month'];?></td>
                <td><?php echo $item['amount'];?></td>

            </tr>
        <?php endforeach;?>
    <?php else:?>
        <tr><td>暂无数据</td></tr>
    <?php endif;?>
</table>



<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="25">
            <span style="font-size:20px">通话记录</span>
        </th>
    </tr>
    <?php if(!empty($data['contact_list'])):?>
        <tr>
            <th>号码</th>
            <th>联系人</th>
            <th>标签</th>
            <th>首次联系时间</th>
            <th>最后联系时间</th>
            <th>通话时长</th>
            <th>通话次数</th>
            <th>主叫时长</th>
            <th>主叫次数</th>
            <th>被叫时长</th>
            <th>被叫次数</th>
            <th>短信总数</th>
            <th>发送短信数</th>
            <th>接收短信数</th>
            <th>未识别状态短信数</th>
            <th>近一周联系次数</th>
            <th>近一个月联系次数</th>
            <th>近三个月联系次数</th>

        </tr>
        <?php foreach($data['contact_list'] as $item):?>
            <tr>
                <td class="recordPhone"><?php echo $item['phone'];?></td>
                <td>--</td>
                <td><?php echo $item['phone_label'];?></td>
                <td><?php echo $item['first_contact_date'];?></td>
                <td><?php echo $item['last_contact_date'];?></td>
                <td><?php echo round($item['talk_seconds'],2);?></td>
                <td><?php echo $item['talk_cnt'];?></td>
                <td><?php echo round($item['call_seconds'],2);?></td>
                <td><?php echo $item['call_cnt'];?></td>
                <td><?php echo round($item['called_seconds'],2);?></td>
                <td><?php echo isset($item['called_cnt']) ? $item['called_cnt'] :"";?></td>
                <td><?php echo $item['msg_cnt'];?></td>
                <td><?php echo $item['send_cnt'];?></td>
                <td><?php echo $item['receive_cnt'];?></td>
                <td><?php echo $item['unknown_cnt'];?></td>
                <td><?php echo $item['contact_1w'];?></td>
                <td><?php echo $item['contact_1m'];?></td>
                <td><?php echo $item['contact_3m'];?></td>

            </tr>
        <?php endforeach;?>
    <?php else:?>
        <tr><td>暂无数据</td></tr>
    <?php endif;?>
</table>
<script>
    var contactData = <?php echo json_encode($contact)?>;
    contactData = contactData || []
    $('.recordPhone').each(function(){
        var phone = $(this).text()
        for (var i = 0; i<contactData.length; i++) {
            if (contactData[i].mobile === phone) {
                $(this).next().text(contactData[i].name)
                break;
            }
        }
    });
</script>






