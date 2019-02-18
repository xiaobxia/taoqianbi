<?php
use common\helpers\Url;

?>
<style>
    .table {
        max-width: 100%;
        width: 100%;
        border: 1px solid #ddd;
    }

    .table th {
        border: 1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }

    .table td {
        border: 1px solid darkgray;

    }
</style>
<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            <span style="font-size:20px"><?php echo $person['name']; ?>的支付宝信息</span>
            <?php if (!empty($report['status_timestamp'])):?>
            <span style="color:red">数据更新时间：<?php echo date('Y-m-d H:i:s', $report['status_timestamp']); ?></span>
            <?php endif;?>
        </th>
    </tr>

</table>
<?php if (!empty($report['data'])):?>
<table class="tb tb2 fixpadding table" style="margin-top:20px">


    <tr>
        <th width="110px;">基本信息</th>
        <td style="padding: 2px;margin-bottom: 1px">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th width="200px;">姓名</th>
                    <td><?php echo $report['data']['base_info']['name']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">状态</th>
                    <td><?php echo $report['data']['base_info']['status']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">证件类型</th>
                    <td><?php echo $report['data']['base_info']['certificate_type']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">证件号码</th>
                    <td><?php echo $report['data']['base_info']['certificate_id']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">淘宝账号数量</th>
                    <td><?php echo $report['data']['base_info']['amount']; ?></td>
                </tr>

                <tr>
                    <th>淘宝账号</th>
                    <td style="padding: 2px;margin-bottom: 1px">
                        <table style="margin-bottom: 0px" class="table">
                            <tr>
                                <th>绑定的手机号</th>
                                <th>绑定的邮箱</th>
                                <th>绑定的淘宝</th>
                                <th>是否为默认淘宝账户</th>
                            </tr>

                            <?php if(!empty($report['data']['base_info']['account'])):?>
                            <?php foreach($report['data']['base_info']['account'] as $v):?>
                            <tr class="ng-scope">
                                <td><?php echo $v['bind_phone']; ?></td>
                                <td><?php echo $v['bind_email']; ?></td>
                                <td><?php echo $v['bind_taobao']; ?></td>
                                <td><?php echo $v['default']; ?></td>
                            </tr>
                                <?php endforeach;?>
                                <?php endif;?>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px;">注册日期</th>
                    <td><?php echo $report['data']['base_info']['register_time']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">设备终端</th>
                    <td><?php echo $report['data']['base_info']['terminal']; ?></td>
                </tr>

                <tr>
                    <th>银行卡</th>
                    <td style="padding: 2px;margin-bottom: 1px">
                        <table style="margin-bottom: 0px" class="table">
                            <tr>
                                <th>银行种类</th>
                                <th>尾号</th>
                                <th>卡种</th>
                                <th>姓名</th>
                                <th>电话号码</th>
                            </tr>

                            <?php if(!empty($report['data']['base_info']['cards'])):?>
                            <?php foreach($report['data']['base_info']['cards'] as $v):?>
                            <tr class="ng-scope">
                                <td><?php echo $v['bank']; ?></td>
                                <td><?php echo $v['number']; ?></td>
                                <td><?php echo $v['type']; ?></td>
                                <td><?php echo $v['name']; ?></td>
                                <td><?php echo $v['phone']; ?></td>
                            </tr>
                                <?php endforeach;?>
                                <?php endif;?>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px;">银行卡是否获取完成</th>
                    <td><?php echo $report['data']['base_info']['cards_done']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">联系人信息</th>
                    <td>
                        <?php foreach ($report['data']['base_info']['contacts'] as $v): ?>
                            <table>
                                <tr>
                                    <td><?php echo $v; ?></td>
                                </tr>
                            </table>
                        <?php endforeach; ?>
                    </td>
                </tr>

                <tr>
                    <th width="200px;">联系人是否获取完成</th>
                    <td><?php echo $report['data']['base_info']['contacts_done']; ?></td>
                </tr>

                <tr>
                    <th width="200px;">设备终端</th>
                    <td><?php echo $report['data']['base_info']['terminal']; ?></td>
                </tr>


            </table>
        </td>
    </tr>

    <tr>
        <th width="110px;">资产信息</th>
        <td style="padding: 2px;margin-bottom: 1px">
            <table style="margin-bottom: 0px" class="table">


                <tr>
                    <th>资产</th>
                    <td style="padding: 2px;margin-bottom: 1px">
                        <table style="margin-bottom: 0px" class="table">
                            <tr>
                                <th width="200px">银行卡号</th>
                                <th width="183px">支出</th>
                                <th>收入</th>
                            </tr>

                            <?php if(!empty($report['data']['assets'])):?>
                            <?php foreach($report['data']['assets'] as $v):?>
                            <tr class="ng-scope">
                                <td><?php echo $v['card']; ?></td>
                                <td><?php echo $v['payout']; ?></td>
                                <td><?php echo $v['income']; ?></td>
                            </tr>
                                <?php endforeach;?>
                                <?php endif;?>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px;">资产是否获取完成</th>
                    <td><?php echo $report['data']['assets_done']; ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <th width="110px;">余额宝数据</th>
        <td style="padding: 2px;margin-bottom: 1px">
            <table style="margin-bottom: 0px" class="table">

                <tr>
                    <th width="200px;">余额宝余额</th>
                    <td><?php echo $report['data']['yuebao_info']['balance'];; ?></td>
                </tr>

                <tr>
                    <th width="200px;">余额宝累计收益</th>
                    <td><?php echo $report['data']['yuebao_info']['accumulative_bonus'];; ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <th width="110px;">花呗数据</th>
        <td style="padding: 2px;margin-bottom: 1px">
            <table style="margin-bottom: 0px" class="table">

                <tr>
                    <th width="200px;">花呗总额度</th>
                    <td><?php echo $report['data']['huabei_info']['limit'];; ?></td>
                </tr>

                <tr>
                    <th width="200px;">花呗可用额度</th>
                    <td><?php echo $report['data']['huabei_info']['available_limit'];; ?></td>
                </tr>

                <tr>
                    <th width="200px;">花呗还款日</th>
                    <td><?php echo $report['data']['huabei_info']['date'];; ?></td>
                </tr>

                <tr>
                    <th width="200px;">是否开通自动还款</th>
                    <td><?php echo $report['data']['huabei_info']['auto_repay']; ?></td>
                </tr>

            </table>
        </td>
    </tr>


    <tr>
        <th width="110px;">交易信息</th>
        <td style="padding: 2px;margin-bottom: 1px">
            <table style="margin-bottom: 0px" class="table">

                <tr>
                    <th width="200px">3个月模型</th>
                    <td>
                        <table>
                            <tr>
                                <th>3个月内交易总数</th>
                                <td><?php echo $report['data']['trade_records']['model']['paycount_3m']; ?></td>
                            </tr>
                            <tr>
                                <th>3个月内还款总数</th>
                                <td><?php echo $report['data']['trade_records']['model']['repaycount_3m']; ?></td>
                            </tr>
                            <tr>
                                <th>3个月内还款失败总数</th>
                                <td><?php echo $report['data']['trade_records']['model']['repayFailCount_3m']; ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px">购物</th>
                    <td>
                        <table>
                            <tr>
                                <th>支出</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['payout']; ?></td>
                            </tr>
                            <tr>
                                <th>收入</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['income']; ?></td>
                            </tr>
                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>收入笔数</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['income_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出详细</th>
                                <td style="padding: 2px;margin-bottom: 1px">
                                    <table style="margin-bottom: 0px" class="table">
                                        <tr>
                                            <th width="200px">交易时间</th>
                                            <th width="183px">交易名称</th>
                                            <th>金额</th>
                                            <th>对方</th>
                                            <th>订单号</th>
                                            <th>交易id</th>
                                        </tr>

                                        <?php if(!empty($report['data']['trade_records']['shopping']['detail'])):?>
                                            <?php foreach($report['data']['trade_records']['shopping']['detail'] as $v):?>
                                                <tr class="ng-scope">
                                                    <td><?php echo $v['time']; ?></td>
                                                    <td><?php echo $v['name']; ?></td>
                                                    <td><?php echo $v['money']; ?></td>
                                                    <td><?php echo $v['opposite']; ?></td>
                                                    <td><?php echo $v['order_id']; ?></td>
                                                    <td><?php echo $v['transaction_id']; ?></td>
                                            <?php endforeach;?>
                                        <?php endif;?>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th>是否已经获取过detail</th>
                                <td><?php echo $report['data']['trade_records']['shopping']['detail_done']; ?></td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px">转账</th>
                    <td>
                        <table>
                            <tr>
                                <th>支出</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['payout']; ?></td>
                            </tr>
                            <tr>
                                <th>收入</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['income']; ?></td>
                            </tr>
                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>收入笔数</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['income_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出详细</th>
                                <td style="padding: 2px;margin-bottom: 1px">
                                    <table style="margin-bottom: 0px" class="table">
                                        <tr>
                                            <th width="200px">交易时间</th>
                                            <th width="183px">交易名称</th>
                                            <th>金额</th>
                                            <th>对方</th>
                                            <th>订单号</th>
                                            <th>交易id</th>
                                        </tr>

                                        <?php if(!empty($report['data']['trade_records']['transfer']['detail'])):?>
                                        <?php foreach($report['data']['trade_records']['transfer']['detail'] as $v):?>
                                        <tr class="ng-scope">
                                            <td><?php echo $v['time']; ?></td>
                                            <td><?php echo $v['name']; ?></td>
                                            <td><?php echo $v['money']; ?></td>
                                            <td><?php echo $v['opposite']; ?></td>
                                            <td><?php echo $v['order_id']; ?></td>
                                            <td><?php echo $v['transaction_id']; ?></td>
                                            <?php endforeach;?>
                                            <?php endif;?>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th>是否已经获取过detail</th>
                                <td><?php echo $report['data']['trade_records']['transfer']['detail_done']; ?></td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px">还款</th>
                    <td>
                        <table>
                            <tr>
                                <th>支出</th>
                                <td><?php echo $report['data']['trade_records']['repay']['payout']; ?></td>
                            </tr>
                            <tr>
                                <th>收入</th>
                                <td><?php echo $report['data']['trade_records']['repay']['income']; ?></td>
                            </tr>
                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['repay']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>收入笔数</th>
                                <td><?php echo $report['data']['trade_records']['repay']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['repay']['income_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出详细</th>
                                <td style="padding: 2px;margin-bottom: 1px">
                                    <table style="margin-bottom: 0px" class="table">
                                        <tr>
                                            <th width="200px">交易时间</th>
                                            <th width="183px">交易名称</th>
                                            <th>金额</th>
                                            <th>对方</th>
                                            <th>订单号</th>
                                            <th>交易id</th>
                                        </tr>

                                        <?php if(!empty($report['data']['trade_records']['repay']['detail'])):?>
                                        <?php foreach($report['data']['trade_records']['repay']['detail'] as $v):?>
                                        <tr class="ng-scope">
                                            <td><?php echo $v['time']; ?></td>
                                            <td><?php echo $v['name']; ?></td>
                                            <td><?php echo $v['money']; ?></td>
                                            <td><?php echo $v['opposite']; ?></td>
                                            <td><?php echo $v['order_id']; ?></td>
                                            <td><?php echo $v['transaction_id']; ?></td>
                                            <?php endforeach;?>
                                            <?php endif;?>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th>是否已经获取过detail</th>
                                <td><?php echo $report['data']['trade_records']['repay']['detail_done']; ?></td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px">余额宝</th>
                    <td>
                        <table>
                            <tr>
                                <th>支出</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['payout']; ?></td>
                            </tr>
                            <tr>
                                <th>收入</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['income']; ?></td>
                            </tr>
                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>收入笔数</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['income_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出详细</th>
                                <td style="padding: 2px;margin-bottom: 1px">
                                    <table style="margin-bottom: 0px" class="table">
                                        <tr>
                                            <th width="200px">交易时间</th>
                                            <th width="183px">交易名称</th>
                                            <th>金额</th>
                                            <th>对方</th>
                                            <th>订单号</th>
                                            <th>交易id</th>
                                        </tr>

                                        <?php if(!empty($report['data']['trade_records']['yuebao']['detail'])):?>
                                        <?php foreach($report['data']['trade_records']['yuebao']['detail'] as $v):?>
                                        <tr class="ng-scope">
                                            <td><?php echo $v['time']; ?></td>
                                            <td><?php echo $v['name']; ?></td>
                                            <td><?php echo $v['money']; ?></td>
                                            <td><?php echo $v['opposite']; ?></td>
                                            <td><?php echo $v['order_id']; ?></td>
                                            <td><?php echo $v['transaction_id']; ?></td>
                                            <?php endforeach;?>
                                            <?php endif;?>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th>是否已经获取过detail</th>
                                <td><?php echo $report['data']['trade_records']['yuebao']['detail_done']; ?></td>
                            </tr>

                        </table>
                    </td>
                </tr>

                <tr>
                    <th width="200px">电话详单</th>
                    <td>
                        <table>
                            <tr>
                                <th>支出</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['payout']; ?></td>
                            </tr>
                            <tr>
                                <th>收入</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['income']; ?></td>
                            </tr>
                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>收入笔数</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['payout_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出笔数</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['income_number']; ?></td>
                            </tr>

                            <tr>
                                <th>支出详细</th>
                                <td style="padding: 2px;margin-bottom: 1px">
                                    <table style="margin-bottom: 0px" class="table">
                                        <tr>
                                            <th width="200px">交易时间</th>
                                            <th width="183px">交易名称</th>
                                            <th>金额</th>
                                            <th>对方</th>
                                            <th>订单号</th>
                                            <th>交易id</th>
                                        </tr>

                                        <?php if(!empty($report['data']['trade_records']['phone_recharge']['detail'])):?>
                                        <?php foreach($report['data']['trade_records']['phone_recharge']['detail'] as $v):?>
                                        <tr class="ng-scope">
                                            <td><?php echo $v['time']; ?></td>
                                            <td><?php echo $v['name']; ?></td>
                                            <td><?php echo $v['money']; ?></td>
                                            <td><?php echo $v['opposite']; ?></td>
                                            <td><?php echo $v['order_id']; ?></td>
                                            <td><?php echo $v['transaction_id']; ?></td>
                                            <?php endforeach;?>
                                            <?php endif;?>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <th>是否已经获取过detail</th>
                                <td><?php echo $report['data']['trade_records']['phone_recharge']['detail_done']; ?></td>
                            </tr>

                        </table>
                    </td>
                </tr>

            </table>
        </td>
    </tr>


</table>
<?php endif;?>


