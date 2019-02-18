<?php

use common\models\UserLoanOrder;
use common\models\UserQuotaPersonInfo;
use common\models\UserContact;
use common\models\CardInfo;
use common\models\UserProofMateria;
use common\models\UserQuotaWorkInfo;
use common\models\UserOrderLoanCheckLog;
use common\models\UserLoanOrderRepayment;
use common\models\UserRepaymentPeriod;
use \common\models\CreditQueryLog;
use common\models\LoanPersonBadInfo;
use common\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\CreditZzcReport;
use common\models\UserLoanCollection;

use common\models\loan\LoanCollectionOrder;

$is_hide = empty(\Yii::$app->user->identity->role)
    ? true
    : \Yii::$app->user->identity->role == 'sales';

?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<style>
    .person {
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .table td{
        border:1px solid darkgray;
    }
    .tb2 th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .tb2 td{
        border:1px solid darkgray;
    }
    .tb2 {
        border:1px solid darkgray;
    }
    .mark {
        font-weight: bold;
        /*background-color:indianred;*/
        color:red;
    }
</style>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">个人信息详情页</th></tr>
    <tr>
        <th width="110px;" class="person">个人详情</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th >用户ID：</th>
                    <td><?php echo $information['loanPerson']['id'];?></td>
                    <th >姓名：</th>
                    <td><?php echo $information['loanPerson']['name'];?></td>
                    <th>身份证号：</th>
                    <td><?php echo $information['loanPerson']['id_number'];?></td>
                    <th>联系方式：</th>
                    <td><?php echo $information['loanPerson']['phone'];?></td>
                </tr>
                <tr>
                    <th>出生日期：</th>
                    <td><?php echo empty($information['loanPerson']['birthday'])?"--:--":date('Y-m-d',$information['loanPerson']['birthday']);?></td>
                    <th>性别：</th>
                    <td><?php echo empty($information['loanPerson']['property'])?"--":$information['loanPerson']['property'];?></td>
                    <th>年龄：</th>
                    <td class="mark"><?php
                        $birthday = $information['loanPerson']['birthday'];
                        if(empty($birthday)){
                            echo "--";
                        }else{
                            $birthday = date('Y-m-d',$birthday);
                            $age = date('Y', time()) - date('Y', strtotime($birthday)) - 1;
                            if (date('m', time()) == date('m', strtotime($birthday))){

                                if (date('d', time()) > date('d', strtotime($birthday))){
                                    $age++;
                                }
                            }elseif (date('m', time()) > date('m', strtotime($birthday))){
                                $age++;
                            }
                            echo $age;
                        }
                        ?></td>
                    <th>婚姻：</th>
                    <td><?php echo empty($information['person_relation']['marriage']) ? "--" : UserQuotaPersonInfo::$marriage[$information['person_relation']['marriage']];?></td>

                </tr>
                <tr>
                    <th>学历：</th>
                    <td class="mark"><?php echo empty($information['person_relation']['degrees']) ? "--" : UserQuotaPersonInfo::$degrees[$information['person_relation']['degrees']];?></td>
                    <th>身份证地址：</th>
                    <td class="mark"><?php echo empty($information['id_number_address']) ? "--" : $information['id_number_address'];?></td>

                    <th>常住地址：</th>
                    <td><?php echo $information['person_relation']['address_distinct']." ".$information['person_relation']['address'];?></td>
                    <th>账号申请创建时间：</th>
                    <td><?php echo empty($information['loanPerson']['created_at'])?"--:--":date('Y-m-d H:i:s',$information['loanPerson']['created_at']);?></td>

                </tr>
                <tr>
                    <th>学历证明：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_DIPLOMA_CERTIFICATE) : ?>
                                <img class="gallery-pic" id="proof<?php echo $list['id']?>" height="100" src="<?php echo $list['url'];?>"/>
                                <input type="button" id="delete_submit<?php echo $list['id']?>" value="X" onclick="delete_proof_info(<?php echo $list['id']?>,<?php echo $information['loanPerson']['id']?>);"/>
                            <?php endif;?>
                        <?php endforeach;?>

                    </td>
                    <th>身份证：</th>
                    <td class="gallerys" >
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if(in_array($list['type'],[UserProofMateria::TYPE_ID_CAR,UserProofMateria::TYPE_BUSINESS_CARD,UserProofMateria::TYPE_FACE_RECOGNITION,UserProofMateria::TYPE_ID_CAR_Z,UserProofMateria::TYPE_ID_CAR_F])) : ?>
                                <img class="gallery-pic" id="proof<?php echo $list['id']?>" height="100" src="<?php echo $list['url'];?>"/>
                                <input type="button" id="delete_submit<?php echo $list['id']?>" value="X" onclick="delete_proof_info(<?php echo $list['id']?>,<?php echo $information['loanPerson']['id']?>);"/>
                            <?php endif;?>
                        <?php endforeach;?>

                        <br/>
                        <?php if(!empty($information['face'])):?>
                            <table>
                                <tr>
                                    <td>confidence</td>
                                    <td><?php echo $information['face']['confidence'];?></td>
                                </tr>
                                <tr>
                                    <td>1e-3</td>
                                    <td><?php echo $information['face']['1e-3'];?></td>
                                </tr>
                                <tr>
                                    <td>1e-4</td>
                                    <td><?php echo $information['face']['1e-4'];?></td>
                                </tr>
                                <tr>
                                    <td>1e-5</td>
                                    <td><?php echo $information['face']['1e-5'];?></td>
                                </tr>
                                <tr>
                                    <td>1e-6</td>
                                    <td><?php echo $information['face']['1e-6'];?></td>
                                </tr>
                            </table>
                        <?php endif;?>
                    </td>
                    <th>个人名片：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_BUSINESS_CARD) : ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif;?>
                        <?php endforeach;?>
                    </td>
                    <th>财产证明：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_PROOF_of_ASSETS) : ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif;?>
                        <?php endforeach;?>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">银行卡信息</th>
        <td style="padding: 2px;margin-bottom: 1px;border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <?php foreach ($information['bank'] as $v): ?>
                    <tr>
                        <th >种类：</th>
                        <td><?php echo isset(CardInfo::$type[$v['type']]) ? CardInfo::$type[$v['type']] : "" ;?></td>
                        <th >类型：</th>
                        <td><?php echo isset(CardInfo::$mark[$v['main_card']])?CardInfo::$mark[$v['main_card']]:'--';?></td>
                        <th>卡号：</th>
                        <td><?php echo $v['card_no'];?></td>
                        <th>发卡行：</th>
                        <td><?php echo $v['bank_name'];?></td>
                        <th>银行预留手机号：</th>
                        <td><?php echo $v['phone'];?></td>
                    </tr>
                    <tr>
                        <th>添加时间：</th>
                        <td><?php echo date("Y-m-d",$v['created_at']);?></td>
                        <th>影像资料：</th>
                        <td colspan="10" class="gallerys">
                            <?php foreach($information['proof_image'] as $list): ?>
                                <?php if($list['type'] == UserProofMateria::TYPE_BANK_CARD) : ?>
                                    <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($information['bank'])): ?>
                    <div class="no-result">暂无记录</div>
                <?php endif; ?>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">联系人</th>
        <td style="padding: 2px;margin-bottom: 1px;border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <?php foreach ($information['contact'] as $value): ?>
                    <tr>
                        <th>关系：</th>
                        <td><?php echo isset($value['relation']) ? UserContact::$relation_types[$value['relation']] : "" ;?></td>
                        <th>姓名：</th>
                        <td><?php echo $value['name'];?></td>
                        <th>电话：</th>
                        <td><?php echo $value['mobile'];?></td>
                        <th>添加时间：</th>
                        <td><?php echo date("Y-m-d",$value['created_at']);?></td>
                        <th>状态：</th>
                        <td><?php echo isset($value['status']) ? UserContact::$status[$value['status']] : "";?></td>
                        <th>来源：</th>
                        <td><?php echo isset($value['source'],UserContact::$sources[$value['source']])  ? UserContact::$sources[$value['source']] : "";?></td>
                    </tr>
                    <tr>
                        <th>关系：</th>
                        <td><?php echo isset($value['relation_spare'],UserContact::$relation_types[$value['relation_spare']]) ? UserContact::$relation_types[$value['relation_spare']] : "" ;?></td>
                        <th>姓名：</th>
                        <td><?php echo $value['name_spare'];?></td>
                        <th>电话：</th>
                        <td><?php echo $value['mobile_spare'];?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($information['contact'])): ?>
                    <div class="no-result">暂无记录</div>
                <?php endif; ?>
                <tr>
                    <th>全部联系人</th>
                    <td colspan="10">
                        <a target="_blank" href="<?php echo Url::toRoute(['mobile-contacts/mobile-contacts-list','user_id'=>$information['loanPerson']['id']]);?>">手机通讯录</a>
                        <a target="_blank" href="<?php echo Url::toRoute(['jxl/user-report-view','id'=>$information['loanPerson']['id'],'type'=>2]);?>">聚信立通讯录</a>
                        <a target="_blank" href="<?php echo Url::toRoute(['mobile-contacts/phone-message-list','user_id'=>$information['loanPerson']['id']]);?>">手机短信息</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">工作信息</th>
        <td style="padding: 2px;margin-bottom: 1px;border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th>单位名称：</th>
                    <td class="mark"><?php echo $information['equipment']['company_name'];?></td>
                    <th>公司电话：</th>
                    <td class="mark"><?php echo $information['equipment']['company_phone'];?></td>
                    <th>公司地址：</th>
                    <td class="mark"><?php echo ($information['work'] ? $information['work']['work_address_distinct'].' ':'').$information['equipment']['company_address'];?></td>
                </tr>

                <tr>
                    <th>工作证照：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_WORK_CARD): ?>
                                <img class="gallery-pic" id="proof<?php echo $list['id']?>" height="100" src="<?php echo $list['url'];?>"/>
                                <input type="button" id="delete_submit<?php echo $list['id']?>" value="X" onclick="delete_proof_info(<?php echo $list['id']?>,<?php echo $information['loanPerson']['id']?>);"/>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </td>
                    <th>工作证明：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_WORK_PROVE) : ?>
                                <img class="gallery-pic" id="proof<?php echo $list['id']?>" height="100" src="<?php echo $list['url'];?>"/>
                                <input type="button" id="delete_submit<?php echo $list['id']?>" value="X" onclick="delete_proof_info(<?php echo $list['id']?>,<?php echo $information['loanPerson']['id']?>);"/>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </td>
                    <th>收入证明：</th>
                    <td colspan="10" class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_SALARY_CERTIFICATE) : ?>
                                <img class="gallery-pic" id="proof<?php echo $list['id']?>" height="100" src="<?php echo $list['url'];?>"/>
                                <input type="button" id="delete_submit<?php echo $list['id']?>" value="X" onclick="delete_proof_info(<?php echo $list['id']?>,<?php echo $information['loanPerson']['id']?>);"/>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    </td>
                </tr>

            </table>
        </td>
    </tr>


</table>

<?php
if (!$is_hide) {
    echo $this->render('/rule/check-report', [
        'id' => $information['loanPerson']['id']
    ]);
}
?>

<table class="tb tb2 fixpadding" <?php echo $is_hide ? 'style="display:none"' : '';?>>
    <tr><th class="partition" colspan="10">行为信息详情页</th></tr>
    <tr>
        <th width="110px;" class="person">注册信息</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th >注册终端类型：</th>
                    <td><?php echo $information['equipment']['reg_client_type'];?></td>
                    <th >注册app版本：</th>
                    <td><?php echo $information['equipment']['reg_app_version'];?></td>
                    <th>注册设备名称：</th>
                    <td><?php echo $information['equipment']['reg_device_name'];?></td>
                    <th>os版本：</th>
                    <td><?php echo $information['equipment']['reg_os_version'];?></td>
                    <th>注册来源：</th>
                    <td><?php echo $information['equipment']['reg_app_market'];?></td>
                </tr>
                <tr>
                    <th>登录日志</th>
                    <td colspan="10"><a target="_blank" href="<?php echo Url::toRoute(['log/ygb-login-log-list','user_id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table class="tb tb2 fixpadding" id="creditreport" <?php echo $is_hide ? 'style="display:none"' : '';?>>
    <tr><th class="partition" colspan="10">征信详情页</th></tr>
    <tr>
        <th width="110px;" class="person">征信查询</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th>芝麻信用：</th>
                    <td>无</td>
                    <!--                    <td><a target="_blank" href="--><?php //echo Url::toRoute(['zmop/user-zmop-view','id'=>$information['loanPerson']['id']]);?><!--">点击查看</a></td>-->
                    <th >蜜罐查询：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['jxl/user-view','id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                    <th >同盾查询：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['td/user-view','id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                    <th>百融查询：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['br/view','id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                    <th>葫芦索伦查询：</th>
                    <td>无</td>
                    <!--                    <td><a target="_blank" href="--><?php //echo Url::toRoute(['hulu/user-view','id'=>$information['loanPerson']['id']]);?><!--">点击查看</a></td>-->
                </tr>
                <tr>
                    <th>白骑士查询：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['bqs/view','id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                    <th>聚信立报告：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['jxl/user-report-view','id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                    <th>公积金报告：</th>
                    <!--                    --><?php
                    //                    $accumulation = \common\models\AccumulationFund::findLatestOne(['user_id' => $information['loanPerson']['id']]);
                    //                    $accumulation_id = $accumulation ? $accumulation->id : '';
                    //                    ?>
                    <!--                    <td><a target="_blank" href="--><?php //echo Url::toRoute(['house-fund/house-fund-view','id'=>$accumulation_id]);?><!--">点击查看</a></td>-->
                    <td>无</td>
                    <th>孚临灵芝分：</th>
                    <td><a target="_blank" href="<?php echo Url::toRoute(['lzf/user-report-view','id'=>$information['loanPerson']['id']])?>">点击查看</a></td>
                    <th>淘宝查询：</th>
                    <td>无</td>
                </tr>
            </table>
        </td>
    </tr>
    <?php if(!is_null($information['reject_log'])):?>
        <tr>
            <th width="110px;" class="person">机审拒绝理由</th>
            <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th>拒绝理由</th>
                    </tr>
                    <tr>
                        <td><?php echo $information['reject_log']['reject_reason'];?></td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php endif;?>
    <tr>
        <th width="110px;" class="person">历史借款记录</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <?php if(!empty($information['all_loan_orders'])):?>
                    <tr>
                        <th>借款ID</th>
                        <th>借款项目</th>
                        <th>借款金额</th>
                        <th>状态</th>
                        <th>借款时间</th>
                        <th>是否逾期</th>
                        <th>备注</th>
                        <th>逾期天数</th>
                        <th>计划还款日期</th>
                        <th>实际还款金额</th>
                        <th>实际还款日期</th>
                        <th>借款详情</th>
                        <th>催收续借建议</th>
                        <th>催收详情</th>
                    </tr>
                    <?php foreach($information['all_loan_orders'] as $v):?>
                        <tr>
                            <td><?php echo $v['id'];?>
                            <td><?php echo UserLoanOrder::$sub_order_type[$v['sub_order_type']];?></td>
                            <td><?php echo sprintf("%0.2f",$v['money_amount']/100)."元";?></td>
                            <td><?php echo UserLoanOrder::$status[$v['status']];?></td>
                            <td><?php echo date('Y-m-d H:i:s',$v['order_time']);?></td>
                            <?php if(!is_null($v->userLoanOrderRepayment) && $v->userLoanOrderRepayment->is_overdue == 0) : ?>
                                <td><?php echo "否";?></td>
                            <?php else: ?>
                                <?php if(is_null($v->userLoanOrderRepayment) ): ?>
                                    <td>--</td>
                                <?php else: ?>
                                    <td style="color: red;"><?php echo "是";?></td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <td><?php echo is_null($v->remark) ? '' :$v->remark;?>
                            <td><?php echo is_null($v->userLoanOrderRepayment) ? '':$v->userLoanOrderRepayment->overdue_day;?>
                            <td><?php echo is_null($v->userLoanOrderRepayment) || empty($v->userLoanOrderRepayment->plan_fee_time) ? "--": date("Y-m-d",$v->userLoanOrderRepayment->plan_fee_time);?>
                            <td><?php echo is_null($v->userLoanOrderRepayment) ||empty($v->userLoanOrderRepayment->true_total_money) ? "--" :sprintf("%0.2f",$v->userLoanOrderRepayment->true_total_money / 100)."元";?>
                            <td><?php echo is_null($v->userLoanOrderRepayment) ||empty($v->userLoanOrderRepayment->true_repayment_time) ? "--": date("Y-m-d",$v->userLoanOrderRepayment->true_repayment_time);?>
                            <td>
                                <?php if($v['order_type'] == UserLoanOrder::LOAN_TYPE_LQD):?>
                                    <a href="<?php echo Url::toRoute(['pocket/pocket-detail','id'=>$v['id']]);?>">点击查看</a>
                                <?php elseif($v['order_type'] == UserLoanOrder::LOAN_TYPR_FZD):?>
                                    <a href="<?php echo Url::toRoute(['house-rent/house-rent-detail','id'=>$v['id']]);?>">点击查看</a>
                                <?php elseif($v['order_type'] == UserLoanOrder::LOAN_TYPE_FQSC):?>
                                    <a href="<?php echo Url::toRoute(['installment-shop/orders-view','id'=>$v['id']]);?>">点击查看</a>
                                <?php endif;?>
                            </td>
                            <td><?php echo empty($v->loanCollectionOrder->next_loan_advice) ? LoanCollectionOrder::$next_loan_advice[0] : LoanCollectionOrder::$next_loan_advice[$v->loanCollectionOrder->next_loan_advice]; ?></td>
                            <td>
                                <?php if(!is_null($v->userLoanOrderRepayment) && $v->userLoanOrderRepayment->is_overdue):?>
                                    <a href="<?php echo Url::toRoute(['collection/collection-record-list','loan_person_id'=>$information['loanPerson']['id']]);?>">点击查看</a>
                                <?php endif;?>
                            </td>

                        </tr>
                    <?php endforeach;?>

                <?php else:?>
                    暂无信息
                <?php endif;?>
            </table>
        </td>
    </tr>
</table>
<table class="tb tb2 fixpadding" <?php echo $is_hide ? 'style="display:none"' : '';?>>
    <tr><th class="partition" colspan="10">团伙骗贷提示</th></tr>
    <!--    <tr>-->
    <!--        <th width="110px;" class="person">用户登录信息</th>-->
    <!--        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">-->
    <!--            <table style="margin-bottom: 0px" class="table">-->
    <!--                <tr>-->
    <!--                    <td>设备id：</td>-->
    <!--                    <td>--><?php //echo empty($information['log_deviceId_list']) ? '' : implode('，',$information['log_deviceId_list']);?><!--</td>-->
    <!--                </tr>-->
    <!--                <tr>-->
    <!--                    <td>登录地址：</td>-->
    <!--                    <td>--><?php //echo empty($information['log_address_list']) ? '' : implode('，',$information['log_address_list']);?><!--</td>-->
    <!--                </tr>-->
    <!--            </table>-->
    <!--        </td>-->
    <!--    </tr>-->
    <tr>
        <th width="110px;" class="person">常住区县重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table" id="distinct_match_wrap">
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">常住地址重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table" id="address_match_wrap">
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">公司名称重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table" id="repeat_company_name_wrap">
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">公司地址重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table" id="repeat_company_address_wrap">
            </table>
        </td>
    </tr>
    <!--    <tr>-->
    <!--        <th width="110px;" class="person">登录设备重复</th>-->
    <!--        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">-->
    <!--            <table style="margin-bottom: 0px" class="table">-->
    <!--                <tr>-->
    <!--                    <th colspan="10">重复次数：--><?php //echo count($information['log_dev_match']);?><!--</th>-->
    <!--                </tr>-->
    <!--                <tr>-->
    <!--                    <th>用户id</th>-->
    <!--                    <th>设备id</th>-->
    <!--                </tr>-->
    <!---->
    <!--                --><?php //if(!empty($information['log_dev_match'])):?>
    <!--                    --><?php //foreach($information['log_dev_match'] as $v):?>
    <!--                        <tr>-->
    <!--                            <td><a href="--><?php //echo Url::toRoute(['loan/loan-person-view','id'=>$v['user_id'],'type'=>2]);?><!--">--><?php //echo $v['user_id'];?><!--</a></td>-->
    <!--                            <td>--><?php //echo $v['deviceId'];?><!--</td>-->
    <!--                        </tr>-->
    <!--                    --><?php //endforeach;?>
    <!--                --><?php //else:?>
    <!--                    <tr><td>无</td></tr>-->
    <!--                --><?php //endif;?>
    <!--            </table>-->
    <!--        </td>-->
    <!--    </tr>-->
    <!--    <tr>-->
    <!--        <th width="110px;" class="person">登录地址重复</th>-->
    <!--        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">-->
    <!--            <table style="margin-bottom: 0px" class="table">-->
    <!--                <tr>-->
    <!--                    <th colspan="10">重复次数：--><?php //echo count($information['log_address_match']);?><!--</th>-->
    <!--                </tr>-->
    <!--                <tr>-->
    <!--                    <th>用户ID</th>-->
    <!--                    <th>登录地址</th>-->
    <!--                </tr>-->
    <!---->
    <!--                --><?php //if(!empty($information['log_address_match'])):?>
    <!--                    --><?php //foreach($information['log_address_match'] as $v):?>
    <!--                        <tr>-->
    <!--                            <td><a href="--><?php //echo Url::toRoute(['loan/loan-person-view','id'=>$v['user_id'],'type'=>2]);?><!--">--><?php //echo $v['user_id'];?><!--</a></td>-->
    <!--                            <td>--><?php //echo $v['address'];?><!--</td>-->
    <!--                        </tr>-->
    <!--                    --><?php //endforeach;?>
    <!--                --><?php //else:?>
    <!--                    <tr><td>无</td></tr>-->
    <!---->
    <!--                --><?php //endif;?>
    <!--            </table>-->
    <!--        </td>-->
    <!--    </tr>-->
</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >历史审核信息</th></tr>
    <tr>
        <?php if (empty($information['past_trail_log'])): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style=" padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th>订单号：</th>
                        <th >审核人：</th>
                        <th >审核类型：</th>
                        <th>审核时间：</th>
                        <th>审核内容：</th>
                        <th>操作类型：</th>
                        <th>审核码</th>
                        <th>审核前状态：</th>
                        <th>审核后状态：</th>
                    </tr>
                    <?php foreach ($information['past_trail_log'] as $log): ?>
                        <tr>
                            <td><?php echo $log['order_id'];?></td>
                            <td><?php echo $log['operator_name'];?></td>
                            <td><?php echo isset($log['type']) ? UserOrderLoanCheckLog::$type[$log['type']] : "--";?></td>
                            <td><?php echo date("Y-m-d H:i:s",$log['created_at']);?></td>
                            <td><?php echo $log['remark'];?></td>
                            <td><?php echo empty($log['operation_type']) ? "--" : UserOrderLoanCheckLog::$operation_type_list[$log['operation_type']] ;?></td>
                            <?php if(empty($log['repayment_type'])) : ?>
                                <?php if(in_array($log['head_code'],['A1','A2','A3'])):?>
                                    <td><?php echo LoanPersonBadInfo::$pass_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$pass_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php elseif (in_array($log['head_code'],['D1','D2','D3'])):?>
                                    <td><?php echo LoanPersonBadInfo::$reject_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$reject_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php else:?>
                                    <td></td>
                                <?php endif;?>
                                <td><?php echo UserLoanOrder::$status[$log['before_status']];?></td>
                                <td><?php echo UserLoanOrder::$status[$log['after_status']];?></td>
                            <?php else : ?>
                                <td></td>
                                <?php if($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD) : ?>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['before_status']];?></td>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['after_status']];?></td>
                                <?php elseif ($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD || $log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC) : ?>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['before_status']];?></td>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['after_status']];?></td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php  ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10" >审核信息</th></tr>
    <tr>
        <?php if (empty($information['trail_log'])): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style=" padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th >审核人：</th>
                        <th >审核类型：</th>
                        <th>审核时间：</th>
                        <th>审核内容：</th>
                        <th>操作类型：</th>
                        <th>审核码</th>
                        <th>审核前状态：</th>
                        <th>审核后状态：</th>
                        <th>是否可再借：</th>
                        <th>拒绝节点</th>
                        <th>拒绝详情</th>
                    </tr>
                    <?php foreach ($information['trail_log'] as $log): ?>
                        <tr>
                            <td><?php echo $log['operator_name'];?></td>
                            <td><?php echo isset($log['type']) ? UserOrderLoanCheckLog::$type[$log['type']] : "--";?></td>
                            <td><?php echo date("Y-m-d H:i:s",$log['created_at']);?></td>
                            <td><?php echo $log['remark'];?></td>
                            <td><?php echo empty($log['operation_type']) ? "--" : UserOrderLoanCheckLog::$operation_type_list[$log['operation_type']] ;?></td>
                            <?php if(empty($log['repayment_type'])) : ?>
                                <?php if(in_array($log['head_code'],['A1','A2','A3'])):?>
                                    <td><?php echo LoanPersonBadInfo::$pass_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$pass_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php elseif (in_array($log['head_code'],['D1','D2','D3'])):?>
                                    <td><?php echo LoanPersonBadInfo::$reject_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$reject_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php else:?>
                                    <td></td>
                                <?php endif;?>
                                <td><?php echo UserLoanOrder::$status[$log['before_status']];?></td>
                                <td><?php echo UserLoanOrder::$status[$log['after_status']];?></td>
                                <td><?php echo $log['can_loan_type'] ? UserOrderLoanCheckLog::$can_loan_type[$log['can_loan_type']] : '';?></td>
                            <?php else : ?>
                                <td></td>
                                <?php if($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD) : ?>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['before_status']];?></td>
                                    <td><?php echo UserLoanOrderRepayment::$status[$log['after_status']];?></td>
                                <?php elseif ($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD || $log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC) : ?>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['before_status']];?></td>
                                    <td><?php echo UserRepaymentPeriod::$status[$log['after_status']];?></td>
                                <?php endif; ?>
                            <?php endif; ?>
                            <td><?php echo $log['reject_roots'] ?? '';?></td>
                            <td><?php echo $log['reject_detail'] ?? '';?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
    </tr>
</table>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition">电核记录</th>
        <th class="partition" colspan="15">
            <span style="cursor:pointer;" class="addtr" onclick="addTr()">新增联系</span>
        </th>
    </tr>
    <?php if(!empty($information['past_phone_log'])): ?>
        <tr>
            <td width="110px;" class="person">历史电核记录</td>
            <td>
                <table class="tb tb2 fixpadding">
                    <tr>
                        <th class="td22">审核人</th>
                        <th class="td22">联系时间</th>
                        <th class="td22">借款人ID</th>
                        <th class="td22">订单ID</th>
                        <th class="td22">联系内容</th>
                    </tr>
                    <?php foreach($information['past_phone_log'] as $value):?>
                        <tr>
                            <td><?php echo $value['operator_name'];?></td>
                            <td><?php echo empty($value['time']) ? "--" : date("Y-m-d H:i:s",$value['time']);?></td>
                            <td><?php echo $value['user_id'];?></td>
                            <td><?php echo $value['order_id'];?></td>
                            <td><?php echo $value['remark'];?></td>
                        </tr>
                    <?php endforeach;?>
                </table>
            </td>
        </tr>
    <?php endif; ?>
    <?php if(!empty($information['phone_log'])) : ?>
        <tr>
            <td width="110px;" class="person">此订单电核记录</td>
            <td>
                <table class="tb tb2 fixpadding">
                    <tr>
                        <th class="td22">审核人</th>
                        <th class="td22">联系时间</th>
                        <th class="td22">借款人ID</th>
                        <th class="td22">订单ID</th>
                        <th class="td22">联系内容</th>
                    </tr>
                    <?php foreach($information['phone_log'] as $value):?>
                        <tr>
                            <td><?php echo $value['operator_name'];?></td>
                            <td><?php echo empty($value['time']) ? "--" : date("Y-m-d H:i:s",$value['time']);?></td>
                            <td><?php echo $value['user_id'];?></td>
                            <td><?php echo $value['order_id'];?></td>
                            <td><?php echo $value['remark'];?></td>
                        </tr>
                    <?php endforeach;?>
                </table>
            </td>
        </tr>
    <?php endif;?>
    <?php if(empty($information['phone_log']) && empty($information['past_phone_log'])) : ?>
        <tr>
            <td colspan="16">暂无联系记录</td>
        </tr>
    <?php endif;?>
</table>
<table style="display: none" id="phone-review">
    <tr>
        <td width="60px">联系时间：</td>
        <td width="200px">
            <input type="text" name="phone-review-time" id="time" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})"/>
        </td>
        <td>
            <span width="60px">联系内容：</span>
        </td>
        <td style="width:450px">
            <textarea id="remark" name="phone-review-log" style="width:400px" row="4"></textarea>
        </td>
        <td>
            <button id="submit" class="btn">添加记录</button>
        </td>
    </tr>
</table>

<script>
    $('.gallery-pic').click(function(){
        $.openPhotoGallery(this);
    });
    $(window).resize(function(){
        $('#J_pg').height($(window).height());
        $('#J_pg').width($(window).width());
    });


    function delete_proof_info(proof_id,user_id)
    {
        $.ajax({
            url:"<?php echo Url::toRoute(['pocket/loan-proof-delete'])?>",
            type : 'GET',
            data : {proof_id:proof_id,user_id:user_id
            },
            dataType : 'text',
            contentType : 'application/x-www-form-urlencoded',
            async : false,
            success : function(mydata) {

                $('#proof'+proof_id).remove();
                $('#delete_submit'+proof_id).remove();
            },
            error : function() {
                alert("error");
            }
        });
    }

    function addTr(){
        var phone_review = document.getElementById("phone-review");
        if(phone_review.style.display == 'block'){
            phone_review.style.display = 'none';
        }else{
            phone_review.style.display = 'block';
        }
    }

    $('#submit').click(function(){
        var params = {
            order_id : <?php echo $information['info']['id'] ?>,
            user_id : <?php echo $information['info']['user_id'] ?>,
            remark : $('#remark').val(),
            time : $('#time').val()
        };
        $.get("<?php echo Url::toRoute(['phone-review/phone-review-log-add'], true); ?>",params, function(data){
            if (data.code == 0) {
                alert("添加成功");
                window.location.reload();
            }else {
                alert("添加失败");
            }
        },'json');
    });
    var rawAUrl = '<a style="margin-right: 10px" href="<?php echo Url::toRoute(['loan/loan-person-view','id'=> 'thisId','type'=>2]);?>">thisId</a>'

    function createMatch(list) {
        var html = '<tr><th colspan="10">重复次数：'
        html += list.length
        html += '</th></tr><tr><th>用户ID：</th><td>'
        if (list && list.length > 0) {
            for (var i = 0; i <list.length; i++) {
                if (list[i].user_id) {
                    html += rawAUrl.replace(/thisId/g, list[i].user_id || '')
                }
            }
        } else {
            html += '无'
        }
        html += '</td></tr>'
        return html
    }
    $.get("<?php echo Url::toRoute(['pocket/detail-match'], true); ?>",
        {id: <?php echo $information['info']['id'] ?>}
        , function(data){
            console.log(data)
            $('#distinct_match_wrap').html(createMatch(data.distinct_match))
            $('#address_match_wrap').html(createMatch(data.address_match))
            $('#repeat_company_name_wrap').html(createMatch(data.repeat_company_name))
            $('#repeat_company_address_wrap').html(createMatch(data.repeat_company_address))
        },'json');
</script>
