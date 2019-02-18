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
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的支付宝报告&nbsp;&nbsp;报告生成时间<?php echo $data['created_time'] ?$data['created_time']:"";?></span>

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
                <th>真实姓名</th>
                <th>邮箱</th>
                <th>手机号码</th>
                <th>注册时间</th>
                <th>淘宝会员名</th>
                <th>花呗总额度</th>
                <th>花呗可用额度</th>
                <th>总欠款</th>
                <th>总资产</th>
                <th>支付宝余额</th>
                <th>余额宝</th>
                <th>招财宝</th>
                <th>存金宝</th>
                <th>淘宝理财</th>
                <th>基金总额</th>
            </tr>
            <tr>
                <td><?php echo isset($data['real_name']) ? $data['real_name'] : "" ;?></td>
                <td><?php echo isset($data['email']) ? $data['email'] : "";?></td>
                <td><?php echo isset($data['phone']) ? $data['phone'] : "";?></td>
                <td><?php echo isset($data['register_time']) ? $data['register_time'] : "";?></td>
                <td><?php echo isset($data['taobao_name']) ? $data['taobao_name'] : "";?></td>
                <td><?php echo isset($data['ants_lines']['ants_lines_total']) ? $data['ants_lines']['ants_lines_total'] : "";?></td>
                <td><?php echo isset($data['ants_lines']['ants_lines_usable']) ? $data['ants_lines']['ants_lines_usable'] : "";?></td>
                <td><?php echo isset($data['ants_lines']['ants_arrears']) ? $data['ants_lines']['ants_arrears'] : "";?></td>
                <td><?php echo isset($data['wealth']) ? $data['wealth'] : "";?></td>
                <td><?php echo isset($data['balance']) ? $data['balance'] : "";?></td>
                <td><?php echo isset($data['balance_bao']) ? $data['balance_bao'] : "";?></td>
                <td><?php echo isset($data['fortune_bao']) ? $data['fortune_bao'] : "";?></td>
                <td><?php echo isset($data['deposit_bao']) ? $data['deposit_bao'] : "";?></td>
                <td><?php echo isset($data['taobao_financial']) ? $data['taobao_financial'] : "";?></td>
                <td><?php echo isset($data['fund']) ? $data['fund'] : "";?></td>


            </tr>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">实名认证状态</span>
            </th>
        </tr>
        <?php if(!empty($data['real_name_status'])):?>
            <tr>
                <th>实名认证状态</th>
                <th>实名认证日期</th>
            </tr>
            <tr>
                <td><?php echo $data['real_name_status'] == 1? '已实名' : "未实名"; ?></td>
                <td><?php echo isset($data['real_name_time']) ? $data['real_name_time'] : ""; ?></td>
            </tr>
        <?php else:?>
            <tr>
                <td>暂无数据</td>
            </tr>
        <?php endif; ?>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">绑定银行卡信息</span>
            </th>
        </tr>
        <?php if(!empty($data['bank_cards'])):?>
            <tr>
                <th>尾号</th>
                <th>银行名</th>
                <th>银行卡类型</th>

            </tr>
            <?php foreach($data['bank_cards'] as $item):?>
                <tr>
                    <td><?php echo $item['card_no'];?></td>
                    <td><?php echo $item['bank_name'];?></td>
                    <td><?php echo $item['type'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">近期好友联系人</span>
            </th>
        </tr>
        <?php if(!empty($data['friends_contact'])):?>
            <tr>
                <th>昵称</th>
                <th>账号</th>

            </tr>
            <?php foreach($data['friends_contact'] as $item):?>
                <tr>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo $item['account'];?></td>
                </tr>
            <?php endforeach;?>
       <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">近期交易联系人</span>
            </th>
        </tr>
        <?php if(!empty($data['trade_contact'])):?>
            <tr>
                <th>昵称</th>
                <th>账号</th>

            </tr>
            <?php foreach($data['trade_contact'] as $item):?>
                <tr>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo $item['account'];?></td>
                </tr>
            <?php endforeach;?>
        <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>




    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">交易记录</span>
            </th>
        </tr>
        <?php if(!empty($data['deal_record'])):?>
            <tr>
                <th>交易时间</th>
                <th>交易金额</th>
                <th>订单号</th>
                <th>名称</th>
                <th>对方名称</th>
                <th>状态</th>
            </tr>
            <?php foreach($data['deal_record'] as $item):?>
                <tr>
                    <td><?php echo $item['deal_time'];?></td>
                    <td><?php echo $item['detail_amount'];?></td>
                    <td><?php echo $item['order_no'];?></td>
                    <td><?php echo $item['name'];?></td>
                    <td><?php echo $item['other_party'];?></td>
                    <td><?php echo $item['status'];?></td>

                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>






