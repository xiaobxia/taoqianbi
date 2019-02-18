    <?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
// use common\models\loanPerson;
// use common\helpers\Url;
// use common\models\CreditZmop;

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
            <span style="font-size:20px"><?php echo $loanPerson['name'];?>的银行报告&nbsp;&nbsp;报告生成时间&nbsp;<?php echo $info['created_at'] ?date('Y-m-t H:i:s',$info['created_at']):"";?></span>

        </th>
    </tr>

</table>
<?php if(empty($data)):?>
    暂无报告
<?php else:?>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">银行基本信息</span>
            </th>
        </tr>

            <tr>
                <th>用户姓名</th>
                <th>身份证号</th>
                <th>邮箱</th>
                <th>性别</th>
                <th>地址</th>
                <th>网络套现</th>
                <th>POS套现</th>
                <th>是否与网银用户匹配</th>
            </tr>
            <tr>
                <td><?php echo isset($data['house_holder']) ? $data['house_holder'] : "" ;?></td>
                <td><?php echo isset($data['cert_no']) ? $data['cert_no'] : "";?></td>
                <td><?php echo isset($data['email']) ? $data['email'] : "";?></td>
                <td><?php echo isset($data['gender']) ? $data['gender'] : "";?></td>
                <td><?php echo isset($data['address']) ? $data['address'] : "";?></td>
                <td><?php if( isset($data['is_t1']) ){ echo $data['is_t1'] == 1 ? '是' : "否"; }?></td>
                <td><?php if( isset($data['is_t2']) ){echo $data['is_t2'] == 1 ? '是' : "否";}?></td>
                <td><?php if( isset($data['is_match_netbank_house_holder']) ){echo $data['is_match_netbank_house_holder'] ? '是' : "否" ;}?></td>
            </tr>
        <?php endif;?>
    </table>

    </table>
<?php if(empty($data)):?>

<?php else:?>
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"></span>
            </th>
        </tr>

            <tr>

                <th>全额还款用户</th>
                <th>还款能力不足</th>
                <th>轻微逾期用户</th>
                <th>严重逾期用户</th>
                <th>频繁取现</th>
                <th>账单波动过大</th>
                <th>常态高额信用卡</th>
                <th>活卡增加</th>
                <th>提额用户</th>
                <th>降额用户</th>
                <th>有车</th>
                <th>海外购物用户</th>
                <th>商旅用户</th>
                <th>网购用户</th>
                <th>信用卡张数</th>
            </tr>
            <tr>


                <td><?php  if( isset($data['is_full_repayment']) ){ echo $data['is_full_repayment'] == 1 ?  '是' : "否"; } ?></td>
                <td><?php  if( isset($data['is_lack_repayment']) ){ echo $data['is_lack_repayment'] == 1 ?  '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_slightly_overdue']) ){ echo $data['is_slightly_overdue'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_seriously_overdue']) ){ echo $data['is_seriously_overdue'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_c1']) ){ echo $data['is_c1'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_b1']) ){ echo $data['is_b1'] == 1 ? '是' : "否";} ?>
                <td><?php  if( isset($data['is_a1']) ){ echo $data['is_a1'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_a2']) ){ echo $data['is_a2'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_a3']) ){ echo $data['is_a3'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_h3']) ){ echo $data['is_h3'] == 1 ? '是' : "否";} ?>
                <td><?php  if( isset($data['is_cz']) ){ echo $data['is_cz'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_hw']) ){ echo $data['is_hw'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_ly']) ){ echo $data['is_ly'] == 1 ? '是' : "否";} ?></td>
                <td><?php  if( isset($data['is_wg']) ){ echo $data['is_wg'] == 1 ? '是' : "否";} ?></td>
                <td><?php  echo isset($data['netbank_credit_num']) ? $data['netbank_credit_num'] : 0;?></td>

            </tr>
        <?php endif;?>
    </table>


 <!--       授信数据    -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">授信数据</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankCreditCardInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>卡片后四位</th>
                <th>卡片级别 </th>
                <th>卡种类</th>
                <th>所属银行</th>
                <th>银行类型 </th>
                <th>账单日 </th>
                <th>最后还款日</th>
                <th>卡片信用额度</th>
                <th>卡片可用额度 </th>
                <th>取现额度</th>
                <th>币种 </th>
            </tr>
            <?php foreach( $data['lstNetBankCreditCardInfo'] as $item1 ):?>
                <tr>
                    <td><?php echo isset($item1['card_id'])?$item1['card_id']:'';?></td>
                    <td><?php echo isset($item1['card_end'])?$item1['card_end']:0;?></td>
                    <td><?php echo isset($item1['card_level'])?$item1['card_level']:'';?></td>
                    <td><?php echo isset($item1['card_type'])?$item1['card_type']:'';?></td>
                    <td><?php echo isset($item1['bank_code'])?$item1['bank_code']:'';?></td>
                    <td><?php echo isset($item1['bank_name'])?$item1['bank_name']:'';?></td>
                    <td><?php echo isset($item1['bill_date'])?$item1['bill_date']:0;?></td>
                    <td><?php echo isset($item1['pay_date'])?$item1['pay_date']:0;?></td>
                    <td><?php echo isset($item1['credit_limit'])?$item1['credit_limit']:0;?></td>
                    <td><?php echo isset($item1['limit_avail'])?$item1['limit_avail']:0;?></td>
                    <td><?php echo isset($item1['cash_credit_limit'])?$item1['cash_credit_limit']:0;?></td>
                    <td><?php echo isset($item1['currency'])?$item1['currency']:'';?></td>

                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


 <!--       分期订单数据    -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">分期订单数据</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankOrderDataInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>分期日期</th>
                <th>分期类型 </th>
                <th>分期订单原始金额</th>
                <th>分期期数</th>
                <th>分期当期期数 </th>
                <th>分期剩余未还款金额 </th>
                <th>每期应还本金</th>
                <th>每期应还手续费</th>
                <th>分期总手续费 </th>
            </tr>
            <?php foreach( $data['lstNetBankOrderDataInfo'] as $item2 ):?>
                <tr>
                    <td><?php echo isset($item2['card_id'])?$item2['card_id']:'';?></td>
                    <td><?php echo isset($item2['post_time'])?$item2['post_time']:0;?></td>
                    <td><?php
                    if( isset($item2['staging_type']) ){
                         if( $item2['staging_type'] == 1 ){
                                echo '交易分期';
                            }elseif ( $item2['staging_type'] ==2 ) {
                                echo '账单分期';
                            }elseif ( $item2['staging_type'] == 3) {
                                echo '现金分期';
                         }
                    }
                    ?></td>

                    <td><?php echo isset($item2['original_principal'])?$item2['original_principal']:0;?></td>
                    <td><?php echo isset($item2['total_staging_num'])?:0;?></td>
                    <td><?php echo isset($item2['current_staging_num'])?$item2['current_staging_num']:0;?></td>
                    <td><?php echo isset($item2['remaining_principal'])?$item2['remaining_principal']:0;?></td>
                    <td><?php echo isset($item2['each_principal'])?$item2['each_principal']:0;?></td>
                    <td><?php echo isset($item2['each_fee'])?$item2['each_fee']:0;?></td>
                    <td><?php echo isset($item2['total_fee'])?$item2['total_fee']:0;?></td>
                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


     <!--      账单数据（分卡片近12个月账单单独提供）    -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">账单数据（分卡片近12个月账单单独提供）</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankCreditBillInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>月份</th>
                <th>账单金额 </th>
                <th>账单金额币种</th>
                <th>最低应激金额</th>
                <th>本期是否足额还款 </th>
                <th>当期积分 </th>
                <th>延滞状态 </th>
            </tr>
            <?php foreach( $data['lstNetBankCreditBillInfo'] as $item3 ):?>
                <tr>
                    <td><?php echo isset($item3['card_id'])?$item3['card_id']:'';?></td>
                    <td><?php echo isset($item3['month'])?$item3['month']:0;?></td>
                    <td><?php echo isset($item3['new_charges'])?$item3['new_charges']:0;?></td>
                    <td><?php echo isset($item3['currency'])?$item3['currency']:'';?></td>
                    <td><?php echo isset($item3['min_payment'])?$item3['min_payment']:0;?></td>
                    <td><?php if( isset( $item3['is_full_repayment'] )){ echo $item3['is_full_repayment'] == 1 ?'是':'否';}?></td>
                    <td><?php echo isset($item3['pointsavailable'])?$item3['pointsavailable']:0;?></td>
                    <td><?php
                    if( isset($item3['delay_status']) ){

                        if( $item3['delay_status'] == 'M0'){
                                echo '正常';
                            }elseif ($item3['delay_status'] == 'M1') {
                                echo '欠款X—30天';
                            }elseif ($item3['delay_status'] == 'M2') {
                                echo '欠款31—60天';
                            }elseif ($item3['delay_status'] == 'M3') {
                                echo '欠款61—90天';
                            }elseif ($item3['delay_status'] == 'M4') {
                                echo '欠款90天以上';
                            }elseif ($item3['delay_status'] == 'MX') {
                                echo '未知天数';
                            }

                            }else {
                                echo '无查询结果';
                        }

                        ?></td>
                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


 <!--       储蓄卡数据     -->
 <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">储蓄卡数据</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankDepositCardInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>银行编码</th>
                <th>银行名称 </th>
                <th>当期活期余额 </th>
            </tr>
            <?php foreach( $data['lstNetBankDepositCardInfo'] as $item4 ):?>
                <tr>
                    <td><?php echo isset($item4['card_id'])?$item4['card_id']:'';?></td>
                    <td><?php echo isset($item4['bank_code'])?$item4['bank_code']:'';?></td>
                    <td><?php echo isset($item4['bank_name'])?$item4['bank_name']:'';?></td>
                    <td><?php echo isset($item4['deposit_balance'])?$item4['deposit_balance']:0;?></td>
                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>




 <!--       活期明细数据     -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px">活期明细数据</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankDepositBillInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>月份</th>
                <th>每月支出笔数 </th>
                <th>每月支出金额</th>
                <th>每月收入笔数</th>
                <th>每月收入金额 </th>
                <th>固定支出笔数 </th>
                <th>固定支出金额</th>
                <th>固定收入笔数</th>
                <th>固定收入金额 </th>
            </tr>
            <?php foreach( $data['lstNetBankDepositBillInfo'] as $item5 ):?>
                <tr>
                    <td><?php echo isset($item5['card_id'])?$item5['card_id']:'';?></td>
                    <td><?php echo isset($item5['month'])?$item5['month']:0;?></td>
                    <td><?php echo isset($item5['month_exp_cnt'])?$item5['month_exp_cnt']:0;?></td>
                    <td><?php echo isset($item5['month_exp_amt'])?$item5['month_exp_amt']:0;?></td>
                    <td><?php echo isset($item5['month_income_cnt'])?$item5['month_income_cnt']:0;?></td>
                    <td><?php echo isset($item5['month_income_amt'])?$item5['month_income_amt']:0;?></td>
                    <td><?php echo isset($item5['normal_exp_cnt'])?$item5['normal_exp_cnt']:0;?></td>
                    <td><?php echo isset($item5['normal_exp_amt'])?$item5['normal_exp_amt']:0;?></td>
                    <td><?php echo isset($item5['normal_income_cnt'])?$item5['normal_income_cnt']:0;?></td>
                    <td><?php echo isset($item5['normal_income_amt'])?$item5['normal_income_amt']:0;?></td>

                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


 <!--       明细统计（按卡片和月份统计）     -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"> 明细统计（按卡片+月份统计）</span>
            </th>
        </tr>
        <?php if(!empty($data['lstNetBankDetailSummaryInfo'])):?>
            <tr>
                <th>卡片id</th>
                <th>月份</th>
                <th>当月交易方向总额 </th>
                <th>当月交易方向总笔数</th>
                <th>当月交易类型总额</th>
                <th>当月交易类型总笔数 </th>
                <th>当月固定交易次数 </th>
                <th>当月固定交易金额</th>
            </tr>
            <?php foreach( $data['lstNetBankDetailSummaryInfo'] as $item5 ):?>
                <tr>
                    <td><?php echo isset($item5['card_id'])?$item5['card_id']:'';?></td>
                    <td><?php echo isset($item5['month'])?$item5['month']:0;?></td>
                    <td><?php echo isset($item5['trans_category_amt'])?$item5['trans_category_amt']:0;?></td>
                    <td><?php echo isset($item5['trans_category_cnt'])?$item5['trans_category_cnt']:0;?></td>
                    <td><?php echo isset($item5['trans_type_amt'])?$item5['trans_type_amt']:0;?></td>
                    <td><?php echo isset($item5['trans_type_cnt'])?$item5['trans_type_cnt']:0;?></td>
                    <td><?php echo isset($item5['normal_trans_cnt'])?$item5['normal_trans_cnt']:0;?></td>
                    <td><?php echo isset($item5['normal_trans_amt'])?$item5['normal_trans_amt']:0;?></td>
                </tr>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>

        <!--     交易方向列表  -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"> 交易方向列表</span>
            </th>
        </tr>
        <?php if(!empty( $data['lstNetBankDetailSummaryInfo'])):?>
            <tr>
                <th>交易方向名称</th>
                <th>消费总额</th>
                <th>消费笔数 </th>
                <th>消费总额占比</th>
                <th>消费笔数占比</th>
            </tr>
            <?php foreach( $data['lstNetBankDetailSummaryInfo'] as $item6 ):?>
                <?php if(!empty($item6['transCategoryList'])){ ?>
                    <?php foreach($item6['transCategoryList'] as $val ){ ?>
                <tr>
                    <td><?php echo isset($val['trans_category'])?$val['trans_category']:0;?></td>
                    <td><?php echo isset($val['trans_amt'])?$val['trans_amt']:0;?></td>
                    <td><?php echo isset($val['trans_cnt'])?$val['trans_cnt']:0;?></td>
                    <td><?php echo isset($val['trans_amt_per'])?$val['trans_amt_per']:0;?></td>
                    <td><?php echo isset($val['trans_cnt_per'])?$val['trans_cnt_per']:0;?></td>
                </tr>
                    <?php } ?>
                <?php } ?>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>




        <!--     交易类型列表  -->
    <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"> 交易类型列表</span>
            </th>
        </tr>
        <?php  if(!empty( $data['lstNetBankDetailSummaryInfo'])):?>
            <tr>
                <th>交易类型名称</th>
                <th>消费总额</th>
                <th>消费笔数 </th>
                <th>消费总额占比</th>
                <th>消费笔数占比</th>
            </tr>
            <?php foreach( $data['lstNetBankDetailSummaryInfo'] as $item7 ):?>
                <?php if (!empty($item7['transTypeList'])) { ?>
                    <?php foreach ($item7['transTypeList'] as  $value) {?>
                        <tr>
                            <td><?php echo isset($value['trans_type'])?$value['trans_type']:0;?></td>
                            <td><?php echo isset($value['trans_amt'])?$value['trans_amt']:0;?></td>
                            <td><?php echo isset($value['trans_cnt'])?$value['trans_cnt']:0;?></td>
                            <td><?php echo isset($value['trans_amt_per'])?$value['trans_amt_per']:0;?></td>
                            <td><?php echo isset($value['trans_cnt_per'])?$value['trans_cnt_per']:0;?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>

            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


         <!--     工作日交易分布  -->
        <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"> 工作日交易分布</span>
            </th>
        </tr>
        <?php  if(!empty( $data['lstNetBankDetailSummaryInfo'])):?>
            <tr>
                <th>交易周</th>
                <th>消费总额</th>
                <th>消费笔数 </th>
            </tr>
            <?php foreach( $data['lstNetBankDetailSummaryInfo'] as $item8 ):?>
                <?php if (!empty($item8['workDayTransList'])) { ?>
                    <?php foreach ($item8['workDayTransList'] as  $val1) {?>
                        <tr>
                            <td><?php echo isset($val1['trans_week'])?$val1['trans_week']:0;?></td>
                            <td><?php echo isset($val1['trans_amt'])?$val1['trans_amt']:0;?></td>
                            <td><?php echo isset($val1['trans_cnt'])?$val1['trans_cnt']:0;?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>


            <!--     非工作日交易分布  -->
        <table class="tb tb2 fixpadding">
        <tr>
            <th class="partition" colspan="25">
                <span style="font-size:20px"> 非工作日交易分布</span>
            </th>
        </tr>
        <?php  if(!empty( $data['lstNetBankDetailSummaryInfo'])):?>
            <tr>
                <th>交易周</th>
                <th>消费总额</th>
                <th>消费笔数 </th>
            </tr>
            <?php foreach( $data['lstNetBankDetailSummaryInfo'] as $item9 ):?>
                <?php if (!empty($item9['nonWorkDayTransList'])) { ?>
                    <?php foreach ($item9['nonWorkDayTransList'] as  $val2) {?>
                        <tr>
                            <td><?php echo isset($val2['trans_week'])?$val2['trans_week']:0;?></td>
                            <td><?php echo isset($val2['trans_amt'])?$val2['trans_amt']:0;?></td>
                            <td><?php echo isset($val2['trans_cnt'])?$val2['trans_cnt']:0;?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            <?php endforeach;?>
            <?php else:?>
            <tr><td>暂无数据</td></tr>
        <?php endif;?>
    </table>