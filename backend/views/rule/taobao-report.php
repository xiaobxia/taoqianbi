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
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的淘宝报告&nbsp;&nbsp;报告生成时间<?php echo $data['created_time'] ?$data['created_time']:"";?></span>

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
                <span style="font-size:20px">个人信息</span>
            </th>
        </tr>

            <tr>
                <th>淘宝会员名</th>
                <th>登录邮箱</th>
                <th>淘宝绑定手机</th>
                <th>个人成长值</th>
                <th>支付宝绑定邮箱</th>
                <th>支付宝绑定手机</th>
                <th>支付宝账户类型</th>
                <th>支付宝实名认证</th>
                <th>淘宝收货地址</th>

            </tr>
            <tr>
                <td><?php echo isset($data['taobaoName']) ? $data['taobaoName'] : "" ;?></td>
                <td><?php echo isset($data['loginEmail']) ? $data['loginEmail'] : "";?></td>
                <td><?php echo isset($data['bindingMobile']) ? $data['bindingMobile'] : "";?></td>
                <td><?php echo isset($data['growth']) ? $data['growth'] : "";?></td>
                <td><?php echo isset($data['alipayEmail']) ? $data['alipayEmail'] : "";?></td>
                <td><?php echo isset($data['alipayMobile']) ? $data['alipayMobile'] : "";?></td>
                <td><?php echo isset($data['accountType']) ? $data['accountType'] : "";?></td>
                <td><?php echo ($data['realName'] == 1) ?  "已认证" : "未认证";?></td>
                <td><?php echo isset($data['taobaoAddress']) ? $data['taobaoAddress'] : "";?></td>

            </tr>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">个人信息</span>
            </th>
        </tr>

        <tr>

            <th>淘宝信誉评分</th>
            <th>最近6个月好评</th>
            <th>最近6个月中评</th>
            <th>最近6个月差评</th>
            <th>天猫积分</th>
            <th>天猫信誉评级</th>
            <th>天猫等级</th>
            <th>天猫经验值</th>
        </tr>
        <tr>

            <td><?php echo isset($data['creditPoint']) ? $data['creditPoint'] : "";?></td>
            <td><?php echo isset($data['goodRate']) ? $data['goodRate'] : "";?></td>
            <td><?php echo isset($data['middleRate']) ? $data['middleRate'] : "";?></td>
            <td><?php echo isset($data['badRate']) ? $data['badRate'] : "";?></td>
            <td><?php echo isset($data['tianMaoPoint']) ? $data['tianMaoPoint'] : "";?></td>
            <td><?php echo isset($data['tianMaoCreditLevel']) ? $data['tianMaoCreditLevel'] : "";?></td>
            <td><?php echo isset($data['tianMaoLevel']) ? $data['tianMaoLevel'] : "";?></td>
            <td><?php echo isset($data['tianMaoExperience']) ? $data['tianMaoExperience'] : "";?></td>


        </tr>
    </table>






    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">交易记录</span>
            </th>
        </tr>
        <?php if(!empty($data['dealRecord'])):?>
            <tr>
                <th>交易时间</th>
                <th>订单号</th>
                <th>交易名称</th>
                <th>单价</th>
                <th>数量</th>
                <th>总价</th>
                <th>状态</th>
            </tr>
            <?php foreach($data['dealRecord'] as $item):?>
                <tr>
                    <td><?php echo $item['deal_time'];?></td>
                    <td><?php echo $item['order_no'];?></td>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo $item['price'];?></td>
                    <td><?php echo $item['num'];?></td>
                    <td><?php echo $item['sum'];?></td>
                    <td><?php echo $item['status'];?></td>

                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>






