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

?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script type="text/javascript"
        src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    *{
    background: aliceblue;
    }
    .box{
        width: 1500px;
        height: 400px;
    }
    .box1{
        width:1500px;
        height: auto;
        margin-top: 10px;
    }
    .th {
        width: 100px;
        height: 30px;
        background: #f5f5f5 none repeat scroll 0 0;
        border: 1px solid #ddd;
        text-align: center;
    }
    .th1 {
        width: 95px;
        height: 30px;
        background: #f5f5f5 none repeat scroll 0 0;
        border: 1px solid #ddd;
        text-align: center;
    }
    .td {
        width: 143px;
        height: 30px;
        border: 1px solid #ddd;
        text-align: center;
    }
    .td1{
        width: 95px;
        height: 30px;
        border: 1px solid #ddd;
        text-align: center;
    }
    .th2{
        width: 115px;
        height: 29px;
        background: #f5f5f5 none repeat scroll 0 0;;
        border: 1px solid #ddd;
        text-align: center;
    }
    .th3{
        width: 102px;
        height: 30px;
        background: #f5f5f5 none repeat scroll 0 0;;
        border: 1px solid #ddd;
        text-align: center;
    }
    .th4{
        width: 142px;
        height: 30px;
        background: #f5f5f5 none repeat scroll 0 0;;
        border: 1px solid #ddd;
        text-align: center;
    }
    .th5{
        width: 180px;
        height: 30px;
        border: 1px solid #ddd;
        background: #f5f5f5 none repeat scroll 0 0;;
        text-align: center;
    }
    .title{
        width: 80px;
        height: 20px;
        border: 1px solid #ddd;
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
    #pic_proof{
        width: 90px;
        height70px;margin: 30px 10px 0 0;
        float: left;
        cursor: pointer
    }
</style>

<div class="box">
    <div style="width: 1200px;height: 400px;float: left;">
        <div style="width: 690px;height: 400px;float: left">
            <div id="pic_proof">
                <p id="p1" class="title" style="background: #99BBFF" onclick="changePic(1)">身份证明(<?php echo $information['idcard_count']?>)</p>
                <p id="p2" class="title" onclick="changePic(2)">学历证明</p>
                <p id="p3" class="title" onclick="changePic(3)">财产证明(<?php echo $information['proof_pic_count']?>)</p>
                <p id="p4" class="title" onclick="changePic(4)">个人名片(<?php echo $information['business_count'] ?>)</p>
                <p id="p5" class="title" onclick="changePic(5)">工作证照(<?php echo $information['work_count'] ?>)</p>
            </div>
            <div style="width: 550px;height: 400px; float: right" id="pic1">
                <div style="margin: 30px 10px 0 60px;">
                    <?php if(!empty($information['proof_image'])): ?>
                    <img id="img_id1" height="250" width="300" src="<?php  echo $information['proof_image'][0]['url'];?>" />
                    <?php endif; ?>
                </div>
                <div style="margin: 30px 0px 0 60px">
                    <?php foreach($information['proof_image'] as $list): ?>
                        <?php if(in_array($list['type'],[UserProofMateria::TYPE_ID_CAR,UserProofMateria::TYPE_BUSINESS_CARD,UserProofMateria::TYPE_FACE_RECOGNITION,UserProofMateria::TYPE_ID_CAR_Z,UserProofMateria::TYPE_ID_CAR_F])) : ?>
                            <img src="<?php echo $list['url'];?>" style="cursor: pointer;text-align: center" height="100" width="50" onclick=showPic("<?php echo $list['url']?>",'img_id1') />
                        <?php endif;?>
                    <?php endforeach;?>
                </div>
            </div>
            <div style="width: 550px;height: 400px; float: right;display: none" id="pic2">
                <div style="margin: 30px 10px 0 60px;">
                </div>
                <div style="margin: 30px 0px 0 60px">
                </div>
            </div>
            <div style="width: 550px;height: 400px; float: right;display: none" id="pic3" >
                <div style="margin: 30px 10px 0 60px;">
                    <?php if(!empty($information['proof_pic'])): ?>
                        <img id="img_id3" height="250" width="300" src="<?php  echo $information['proof_pic'][0]['url'];?>" />
                    <?php endif; ?>
                </div>
                <div style="margin: 30px 0px 0 60px">
                    <?php if(isset($information['proof_pic'])): ?>
                    <?php foreach($information['proof_pic'] as $list): ?>
                            <img src="<?php echo $list['url'];?>" style="cursor: pointer" height="100" onclick=showPic("<?php echo $list['url']?>",'img_id3') />
                    <?php endforeach;?>
                    <?php endif ?>
                </div>
            </div>
            <div style="width: 550px;height: 400px; float: right;display: none" id="pic4" >
                <div style="margin: 30px 10px 0 60px;">
                    <?php if(!empty($information['business'])): ?>
                        <img id="img_id4" height="250" width="300" src="<?php echo $information['business'][0]['url'];?>" />
                    <?php endif ?>
                </div>
                <div style="margin: 30px 0px 0 60px">
                    <?php if(isset($information['business'])): ?>
                    <?php foreach($information['business'] as $list): ?>
                        <img src="<?php echo $list['url'];?>" style="cursor: pointer" height="100" onclick=showPic("<?php echo $list['url']?>",'img_id4') />
                    <?php endforeach;?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="width: 550px;height: 400px; float: right;display: none" id="pic5" >
                <div style="margin: 30px 10px 0 60px;">
                    <?php if(!empty($information['work_proof'])): ?>
                        <img id="img_id5" height="250" width="300" src="<?php echo $information['work_proof'][0]['url'];?>" />
                    <?php endif ?>
                </div>
                <div style="margin: 30px 0px 0 60px">
                    <?php if(isset($information['work_proof'])): ?>
                        <?php foreach($information['work_proof'] as $list): ?>
                            <img src="<?php echo $list['url'];?>" style="cursor: pointer" height="100" onclick=showPic("<?php echo $list['url']?>",'img_id5') />
                        <?php endforeach;?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="width: 502px;height: 400px;float: right">
            <h4 style="margin: 10px 0 10px 0;color:#3325ff;">基本信息</h4>
            <table>
                <tr>
                    <th class="th">用户ID:</th>
                    <td class="td"><?php echo $information['loanPerson']['id']; ?></td>
                    <th class="th">手机</th>
                    <td class="td"><?php echo $information['loanPerson']['phone']; ?></td>
                </tr>
                <tr>
                    <th class="th">姓名:</th>
                    <td class="td"><?php echo $information['loanPerson']['name']; ?></td>
                    <th class="th">身份证:</th>
                    <td class="td"><?php echo $information['loanPerson']['id_number']; ?></td>
                </tr>
                <tr>
                    <th class="th">出生日期:</th>
                    <td class="td"><?php echo empty($information['loanPerson']['birthday']) ? "--:--" : date('Y-m-d', $information['loanPerson']['birthday']); ?></td>
                    <th class="th">年龄:</th>
                    <td class="td">
                        <?php
                        $birthday = $information['loanPerson']['birthday'];
                        if (empty($birthday)) {
                            echo "--";
                        } else {
                            $birthday = date('Y-m-d', $birthday);
                            $age = date('Y', time()) - date('Y', strtotime($birthday)) - 1;
                            if (date('m', time()) == date('m', strtotime($birthday))) {

                                if (date('d', time()) > date('d', strtotime($birthday))) {
                                    $age++;
                                }
                            } elseif (date('m', time()) > date('m', strtotime($birthday))) {
                                $age++;
                            }
                            echo $age;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th class="th">性别:</th>
                    <td class="td"><?php echo empty($information['loanPerson']['property']) ? "--" : $information['loanPerson']['property']; ?></td>
                    <th class="th">婚姻状况</th>
                    <td class="td"><?php echo empty($information['person_relation']['marriage']) ? "--" : UserQuotaPersonInfo::$marriage[$information['person_relation']['marriage']]; ?></td>
                </tr>
                <tr>
                    <th class="th">学历:</th>
                    <td class="td"><?php echo empty($information['person_relation']['degrees']) ? "--" : UserQuotaPersonInfo::$degrees[$information['person_relation']['degrees']]; ?></td>
                    <th class="th">人脸识别:</th>
                    <td class="td"><?php echo $information['face']['confidence'];?></td>
                </tr>
                <tr>
                    <th class="th">身份证地址：</th>
                    <td colspan="3"
                        class="td"><?php echo empty($information['id_number_address']) ? "--" : $information['id_number_address']; ?></td>
                </tr>
                <tr>
                    <th class="th">常住地址：</th>
                    <td colspan="3"
                        class="td"><?php echo $information['person_relation']['address_distinct'] . " " . $information['person_relation']['address']; ?></td>
                </tr>
            </table>
            <h4 style="margin: 10px 0 10px 0;color:#3325ff;">工作信息</h4>
            <table>
                <tr>
                    <th class="th">单位名称</th>
                    <td style="width: 398px;border: 1px solid #ddd"><?php echo $information['equipment']['company_name']; ?></td>
                </tr>
                <tr>
                    <th class="th">公司电话</th>
                    <td style="width: 398px;border: 1px solid #ddd"><?php echo $information['equipment']['company_phone']; ?></td>
                </tr>
                <tr>
                    <th class="th">公司地址</th>
                    <td style="width: 398px;border: 1px solid #ddd"><?php echo ($information['work'] ? $information['work']['work_address_distinct'] . ' ' : '') . $information['equipment']['company_address']; ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div style="width: 280px;height: 335px;float: right">
        <h4 style="margin: 10px 0 10px 0;color:#3325ff;">借款订单</h4>
        <table>
            <tr>
                <th class="th">订单号:</th>
                <td class="td"><?php echo $information['info']['id']; ?></td>
            </tr>
            <tr>
                <th class="th">申请时间：</th>
                <td class="td"><?php echo date("Y-m-d H:i:s", $information['info']['order_time']); ?></td>
            </tr>
            <tr>
                <th class="th">项目:</th>
                <td class="td"><?php echo isset(UserLoanOrder::$sub_order_type[$information['info']['sub_order_type']]) ? UserLoanOrder::$sub_order_type[$information['info']['sub_order_type']] : ""; ?></td>
            </tr>
            <tr>
                <th class="th">借款金额：</th>
                <td class="td"><?php echo sprintf("%.2f", $information['info']['money_amount'] / 100); ?></td>
            </tr>
            <tr>
                <th class="th">借款利率%：</th>
                <td class="td"><?php echo $information['info']['apr']; ?></td>
            </tr>
            <tr>
                <th class="th">服务费：</th>
                <td class="td"><?php echo sprintf("%.2f", $information['info']['counter_fee'] / 100); ?></td>
            </tr>
            <tr>
                <th class="th">总额度：</th>
                <td class="td"><?php echo sprintf("%.2f", $information['credit']['amount'] / 100); ?></td>
            </tr>
            <tr>
                <th class="th">已用额度：</th>
                <td class="td"><?php echo sprintf("%.2f", ($information['credit']['used_amount'] + $information['credit']['locked_amount']) / 100); ?></td>
            </tr>
            <tr>
                <th class="th">剩余额度：</th>
                <td class="td"><?php echo sprintf("%.2f", ($information['credit']['amount'] - $information['credit']['used_amount'] - $information['credit']['locked_amount']) / 100); ?></td>
            </tr>

        </table>
    </div>
</div>
<div class="box1">
    <div style="width: 1200px;height: auto;float: left;">
        <div style="width: 690px;height: auto;float: left">
            <h4 style="margin: 10px 0 10px 0;color:#3325ff;">联系人</h4>
            <table>
                <tr>
                    <th class="th1">关系</th>
                    <th class="th1">姓名</th>
                    <th class="th1">电话</th>
                    <th class="th1">添加时间</th>
                    <th class="th1">状态</th>
                    <th class="th1">来源</th>
                </tr>
                <?php foreach ($information['contact'] as $value): ?>
                    <tr>
                        <td class="td1"><?php echo isset($value['relation']) ? UserContact::$relation_types[$value['relation']] : "" ;?></td>
                        <td class="td1"><?php echo $value['name'];?></td>
                        <td class="td1"><?php echo $value['mobile'];?></td>
                        <td class="td1"><?php echo date("Y-m-d",$value['created_at']);?></td>
                        <td class="td1"><?php echo isset($value['status']) ? UserContact::$status[$value['status']] : "";?></td>
                        <td class="td1"><?php echo isset($value['source'],UserContact::$sources[$value['source']])  ? UserContact::$sources[$value['source']] : "";?></td>
                    </tr>
                    <tr>
                        <td class="td1"><?php echo isset($value['relation_spare'],UserContact::$relation_types[$value['relation_spare']]) ? UserContact::$relation_types[$value['relation_spare']] : "" ;?></td>
                        <td class="td1"><?php echo $value['name_spare'];?></td>
                        <td class="td1"><?php echo $value['mobile_spare'];?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($information['contact'])): ?>
                    <div class="no-result">暂无记录</div>
                <?php endif; ?>
            </table>
        </div>
        <div style="width: 502px;height:auto;float: right;">
            <h4 style="margin: 10px 0 10px 0;color:#3325ff;">通信</h4>
            <table>
                <tr>
                    <th class="th">通讯录</th>
                    <th class="th">短信</th>
                    <th class="th">聚信立通讯录</th>
                    <th class="th">重复记录</th>
                </tr>
                <tr>
                    <!--                    <td class="td">--><?php //echo $information['mobile_count']?><!--</td>-->
                    <!--                    <td class="td">--><?php //echo $information['message_count']?><!--</td>-->
                    <!--                    <td class="td">--><?php //echo $information['jxl_count']?><!--</td>-->
                    <td class="td">暂不查看</td>
                    <td class="td">暂不查看</td>
                    <td class="td">暂不查看</td>
                    <td class="td">10</td>
                </tr>
                <tr>
                    <td class="td"><a target="_blank" href="<?php echo Url::toRoute(['mobile-contacts/mobile-contacts-list','user_id'=>$information['loanPerson']['id']]);?>">查看</a></td>
                    <td class="td"><a target="_blank" href="<?php echo Url::toRoute(['mobile-contacts/phone-message-list','user_id'=>$information['loanPerson']['id']]);?>">查看</a></td>
                    <td class="td"><a target="_blank" href="<?php echo Url::toRoute(['jxl/user-report-view','id'=>$information['loanPerson']['id'],'type'=>2]);?>">查看</a></td>
                    <td class="td">查看</td>
                </tr>
            </table>
        </div>
        <div style="float: left;margin-top:30px ">
            <h4 style="margin: 10px 0 10px 0;color:#3325ff;">征信详情</h4>
            <table>
                <tr>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['zmop/user-zmop-view','id'=>$information['loanPerson']['id']]);?>">芝麻</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['jxl/user-view','id'=>$information['loanPerson']['id']]);?>">蜜罐</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['td/user-view','id'=>$information['loanPerson']['id']]);?>">同盾</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['hd/view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id']]);?>">华道</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['yxzc/view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id']]);?>">宜信</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['zzc/view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id']]);?>">中智诚</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['yd/view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id']]);?>">有盾</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['zzc/report-view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id']]);?>">中智诚反欺诈</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['bqs/view','product_id'=>CreditZzcReport::PRODUCT_YGD,'order_id'=>$information['info']['id'],'id'=>$information['info']['id']]);?>">白骑士</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['jxl/user-report-view','id'=>$information['loanPerson']['id']]);?>">聚信立</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['zs/view','id'=>$information['loanPerson']['id']]);?>">学历</a></th>
                </tr>
                <tr>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['jy/view','id'=>$information['loanPerson']['id']]);?>">91征信</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['rule/get-yys-report','id'=>$information['loanPerson']['id']]);?>">运营商数据</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['rule/get-zfb-report','id'=>$information['loanPerson']['id']]);?>">支付宝</a></th>
                    <th class="th2"><a target="_blank" href="<?php echo Url::toRoute(['yys/view','id'=>$information['loanPerson']['id']]);?>">葫芦金融</a></th>
                </tr>
            </table>
        </div>
    </div>
    <div style="width: 280px;height: auto;float: right;margin: 5px 0 0 0">
        <div style="height: 32px">
            <?php foreach ($information['bank'] as $k=>$v): ?>
                <div style="width:84px;height: 30px;background: #f5f5f5 none repeat scroll 0 0;
        border: 1px solid #ddd;float: left"><p style="margin: 5px 0 5px 0;cursor: pointer" onclick="changeCard(<?php echo $k; ?>,4)"><?php echo isset(CardInfo::$type[$v['type']]) ? CardInfo::$type[$v['type']] : "" ;?>(<?php echo $k + 1; ?>)</p></div>
            <?php endforeach; ?>
        </div>
        <?php foreach ($information['bank'] as $k=>$v): ?>
            <div id="card<?php echo $k;?>" <?php if($k!=0){?>
                style="display: none;float: left"
            <?php }?>>
                <table>
                    <tr>
                        <th class="th">卡片种类：</th>
                        <td class="td"><?php echo isset(CardInfo::$type[$v['type']]) ? CardInfo::$type[$v['type']] : "" ;?></td>
                    </tr>
                    <tr>
                        <th class="th">卡号：</th>
                        <td class="td"><?php echo $v['card_no'];?></td>
                    </tr>
                    <tr>
                        <th class="th">发卡行：</th>
                        <td class="td"><?php echo $v['bank_name'];?></td>
                    </tr>
                    <tr>
                        <th class="th">预留手机：</th>
                        <td class="td"><?php echo $v['phone'];?></td>
                    </tr>
                    <tr>
                        <th class="th">添加时间：</th>
                        <td class="td"><?php echo date("Y-m-d",$v['created_at']);?></td>
                    </tr>
                    <tr>
                        <th class="th">影像资料：</th>
<!--                        <div id="goodcover">-->
<!--                            <div style="float: left;position: relative;z-index: 99999;display: block"><</div>-->
<!--                            <div style="float: right;position: relative">></div>-->
<!--                        </div>-->
<!--                        <div id="code"  style="display:none;height: auto;">-->
                        <td class="gallerys td">
                            <?php foreach($information['proof_image'] as $list): ?>
                                <?php if($list['type'] == UserProofMateria::TYPE_BANK_CARD) : ?>
                                    <img class="gallery-pic" style="cursor: pointer;margin-right: 5px" height="30" src="<?php echo $list['url'];?>"/>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>

<!--                        </div>-->
<!--                        <td class="td" id="ClickMe">查看</td>-->
                    </tr>
                </table>
            </div>
        <?php endforeach; ?>
        <?php if (empty($information['bank'])): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
        </table>
</div>
<div style="width:auto;height: auto;float: left;margin-bottom: 20px">
    <h4 style="margin: 10px 0 10px 0;color:#3325ff;">历史借款</h4>
    <table>
        <?php if(!empty($information['all_loan_orders'])):?>
        <tr>
            <th class="th3">借款ID</th>
            <th class="th3">借款项目</th>
            <th class="th3">借款时间</th>
            <th class="th3">借款金额</th>
            <th class="th3">状态</th>
            <th class="th3">是否逾期</th>
            <th class="th3">逾期天数</th>
            <th class="th3">计划还款日期</th>
            <th class="th3">实际还款日期</th>
            <th class="th3">实际还款金额</th>
            <th class="th3">借款详情</th>
            <th class="th3">催收续借建议</th>
            <th class="th3">催收详情</th>
            <th class="th3">备注</th>
        </tr>
            <?php foreach($information['all_loan_orders'] as $v):?>
                <tr>
                    <td class="td1"><?php echo $v['id'];?></td>
                    <td class="td1"><?php echo UserLoanOrder::$sub_order_type[$v['sub_order_type']];?></td>
                    <td class="td1"><?php echo date('Y-m-d H:i:s',$v['order_time']);?></td>
                    <td class="td1"><?php echo sprintf("%0.2f",$v['money_amount']/100)."元";?></td>
                    <td class="td1"><?php echo UserLoanOrder::$status[$v['status']];?></td>
                    <?php if(!is_null($v->userLoanOrderRepayment) && $v->userLoanOrderRepayment->is_overdue == 0) : ?>
                        <td class="td1"><?php echo "否";?></td>
                    <?php else: ?>
                        <td style="color: red;" class="td1"><?php echo "是";?></td>
                    <?php endif; ?>
                    <td class="td1"><?php echo is_null($v->userLoanOrderRepayment) ? '':$v->userLoanOrderRepayment->overdue_day;?></td>
                    <td class="td1"><?php echo is_null($v->userLoanOrderRepayment) || empty($v->userLoanOrderRepayment->plan_fee_time) ? "--": date("Y-m-d",$v->userLoanOrderRepayment->plan_fee_time);?></td>
                    <td class="td1"><?php echo is_null($v->userLoanOrderRepayment) ||empty($v->userLoanOrderRepayment->true_repayment_time) ? "--": date("Y-m-d",$v->userLoanOrderRepayment->true_repayment_time);?></td>
                    <td class="td1"><?php echo is_null($v->userLoanOrderRepayment) ||empty($v->userLoanOrderRepayment->true_total_money) ? "--" :sprintf("%0.2f",$v->userLoanOrderRepayment->true_total_money / 100)."元";?></td>
                    <td class="td1">
                        <?php if($v['order_type'] == UserLoanOrder::LOAN_TYPE_LQD):?>
                            <a href="<?php echo Url::toRoute(['pocket/pocket-detail','id'=>$v['id']]);?>">点击查看</a>
                        <?php elseif($v['order_type'] == UserLoanOrder::LOAN_TYPR_FZD):?>
                            <a href="<?php echo Url::toRoute(['house-rent/house-rent-detail','id'=>$v['id']]);?>">点击查看</a>
                        <?php elseif($v['order_type'] == UserLoanOrder::LOAN_TYPE_FQSC):?>
                            <a href="<?php echo Url::toRoute(['installment-shop/orders-view','id'=>$v['id']]);?>">点击查看</a>
                        <?php endif;?>
                    </td>
                    <td class="td1"><?php echo empty($v->loanCollectionOrder->next_loan_advice) ? LoanCollectionOrder::$next_loan_advice[0] : LoanCollectionOrder::$next_loan_advice[$v->loanCollectionOrder->next_loan_advice]; ?></td>
                    <td class="td1">
                        <?php if(!is_null($v->userLoanOrderRepayment) && $v->userLoanOrderRepayment->is_overdue):?>
                            <a href="<?php echo Url::toRoute(['collection/collection-record-list','loan_person_id'=>$information['loanPerson']['id']]);?>">点击查看</a>
                        <?php endif;?>
                    <td class="td1"><?php echo is_null($v->remark) ? '' :$v->remark;?>
                    </td>
                </tr>
            <?php endforeach;?>
            <?php else: ?>
            <tr>
                <th style="width: 1470px;height: 30px;border: 1px solid #ddd;">暂无记录</th>
            </tr>
        <?php endif; ?>

    </table>
</div>

<div style="width: 1000px;height: auto;clear: both">
    <table>
        <tr>
            <th id="color1" class="th5" style="cursor: pointer;color:#0000FF;" onclick="showRule(1)">特征结果：</th>
            <?php if($information['credit_score']['text'] === '暂无'): ?>
            <th id="color2" class="th5" >信用评估：<?php echo $information['credit_score']['text'];?></th>
                <?php else: ?>
                <th id="color2" class="th5" style="cursor: pointer" onclick="showRule(2)">信用评估<?php echo $information['credit_score']['text'];?></th>
            <?php endif; ?>
            <?php if($information['fake_score']['text'] === '暂无'): ?>
                <th id="color3" class="th5">欺诈分：<?php echo $information['fake_score']['text'];?></th>
            <?php else: ?>
                <th id="color3" class="th5" style="cursor: pointer" onclick="showRule(3)">欺诈分<?php echo $information['fake_score']['text'];?></th>
            <?php endif; ?>
            <?php if($information['jinzhi']['text'] === '暂无'): ?>
                <th id="color4" class="th5">禁止项：<?php echo $information['jinzhi']['text'];?></th>
            <?php else: ?>
                <th id="color4" class="th5" style="cursor: pointer" onclick="showRule(4)">禁止项<?php echo $information['jinzhi']['text'];?></th>
            <?php endif; ?>
            <th id="color5" class="th5" style="cursor: pointer" onclick="showRule(5)">历史审核结果：</th>
<!--            <h4  onclick="scorePerson(this)" style="float:right;color: #00b3ee;cursor: pointer;">更新评分</h4>-->
        </tr>
    </table>
    <div style="width: auto;height:auto;" id="rule1"> <table id="data3" style="margin-bottom: 0px" class="table">
        </table></div>
    <div style="width: auto;height:auto;display: none;" id="rule2"><table style="margin-bottom: 0px" class="table">
                <?php foreach($information['credit_score']['children'] as $v):?>
                <tr>
                    <td><?php echo $v;?></td>
                </tr>
                <?php endforeach;?>
        </table></div>
    <div style="width: auto;height:auto;display: none;" id="rule3"><table style="margin-bottom: 0px" class="table">
            <?php foreach($information['fake_score']['children'] as $v):?>
                <tr>
                    <td><?php echo $v;?></td>
                </tr>
            <?php endforeach;?>
        </table></div>
    <div style="width: auto;height:auto;display: none;" id="rule4"><table style="margin-bottom: 0px" class="table">
            <?php foreach($information['jinzhi']['children'] as $v):?>
                <tr>
                    <td><?php echo $v;?></td>
                </tr>
            <?php endforeach;?>
        </table></div>
    <div style="width: auto;height:auto;display: none;" id="rule5" >
        <table class="tb tb2 fixpadding">
            <tr>
                <?php if (empty($information['past_trail_log'])): ?>
                    <td>暂无记录</td>
                <?php else : ?>
                    <td style=" padding: 2px;margin-bottom: 1px">
                        <table class="table">
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
    </div>
</div>

<div style="width: 1000px;height: auto">
    <h4 style="margin: 10px 0 10px 0;color:#3325ff;">审核信息</h4>
    <tr>
        <?php if (empty($information['trail_log'])): ?>
            <td>暂无记录</td>
        <?php else : ?>
            <td style=" padding: 2px;margin-bottom: 1px">
                <table style="margin-bottom: 0px">
                    <tr>
                        <th class="th4">审核人：</th>
                        <th class="th4">审核类型：</th>
                        <th class="th4">审核时间：</th>
                        <th class="th4">审核内容：</th>
                        <th class="th4">操作类型：</th>
                        <th class="th4">审核码</th>
                        <th class="th4">审核前状态：</th>
                        <th class="th4">审核后状态：</th>
                        <th class="th4">是否可再借：</th>
                    </tr>
                    <?php foreach ($information['trail_log'] as $log): ?>
                        <tr>
                            <td class="td"><?php echo $log['operator_name'];?></td>
                            <td class="td"><?php echo isset($log['type']) ? UserOrderLoanCheckLog::$type[$log['type']] : "--";?></td>
                            <td class="td"><?php echo date("Y-m-d H:i:s",$log['created_at']);?></td>
                            <td class="td"><?php echo $log['remark'];?></td>
                            <td class="td"><?php echo empty($log['operation_type']) ? "--" : UserOrderLoanCheckLog::$operation_type_list[$log['operation_type']] ;?></td>
                            <?php if(empty($log['repayment_type'])) : ?>
                                <?php if(in_array($log['head_code'],['A1','A2','A3'])):?>
                                    <td class="td"><?php echo LoanPersonBadInfo::$pass_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$pass_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php elseif (in_array($log['head_code'],['D1','D2','D3'])):?>
                                    <td class="td"><?php echo LoanPersonBadInfo::$reject_code[$log['head_code']]['backend_name'].'/'. LoanPersonBadInfo::$reject_code[$log['head_code']]['child'][$log['back_code']]['backend_name'];?></td>
                                <?php else:?>
                                    <td class="td"></td>
                                <?php endif;?>
                                <td class="td"><?php echo UserLoanOrder::$status[$log['before_status']];?></td>
                                <td class="td"><?php echo UserLoanOrder::$status[$log['after_status']];?></td>
                                <td class="td"><?php echo $log['can_loan_type'] ? UserOrderLoanCheckLog::$can_loan_type[$log['can_loan_type']] : '';?></td>
                            <?php else : ?>
                                <td class="td"></td>
                                <?php if($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_LQD) : ?>
                                    <td class="td"><?php echo UserLoanOrderRepayment::$status[$log['before_status']];?></td>
                                    <td class="td"><?php echo UserLoanOrderRepayment::$status[$log['after_status']];?></td>
                                <?php elseif ($log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FZD || $log['repayment_type'] == UserOrderLoanCheckLog::REPAYMENT_TYPE_FQSC) : ?>
                                    <td class="td"><?php echo UserRepaymentPeriod::$status[$log['before_status']];?></td>
                                    <td class="td"><?php echo UserRepaymentPeriod::$status[$log['after_status']];?></td>
                                <?php endif; ?>
                            <?php endif; ?>

                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        <?php endif; ?>
</div>
<div style="width: 600px;height: ">
    <h4 style="margin: 10px 0 10px 0;color:#3325ff;">电核记录</h4>
    <table class="tb tb2 fixpadding">
        <?php if(!empty($information['past_phone_log'])): ?>
            <tr>
                <td width="110px;" class="person">历史电核记录</td>
                <td>
                    <table>
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
                <td>
                    <table class="tb tb2 fixpadding">
                        <tr>
                            <th class="th">审核人</th>
                            <th class="th">联系时间</th>
                            <th class="th">借款人ID</th>
                            <th class="th">订单ID</th>
                            <th class="th">联系内容</th>
                        </tr>
                        <?php foreach($information['phone_log'] as $value):?>
                            <tr>
                                <td class="td"><?php echo $value['operator_name'];?></td>
                                <td class="td"><?php echo empty($value['time']) ? "--" : date("Y-m-d H:i:s",$value['time']);?></td>
                                <td class="td"><?php echo $value['user_id'];?></td>
                                <td class="td"><?php echo $value['order_id'];?></td>
                                <td class="td"><?php echo $value['remark'];?></td>
                            </tr>
                        <?php endforeach;?>
                    </table>
                </td>
            </tr>
        <?php endif;?>
        <?php if(empty($information['phone_log']) && empty($information['past_phone_log'])) : ?>
            <tr>
                <td class="td">暂无联系记录</td>
            </tr>
        <?php endif;?>
    </table>
</div>
<div style="width: auto;height:auto;margin-top: 10px">
    <table>
        <tr>
            <th class="th">时间</th>
            <th class="th">内容</th>
        </tr>
        <tr>
            <td> <input style="height: 30px;" type="text" name="phone-review-time" id="time" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})"/></td>
            <td> <textarea  id="remark" name="phone-review-log" style="width:400px" rows="2"></textarea></td>
            <td><input type="button"  style="color: #00a0e9;width: 80px;height: 30px;cursor: pointer" value="确认添加" id="submit"></td>
        </tr>
    </table>
</div>
<div style="width: 1000px;height: auto">
    <?php $form = ActiveForm::begin(['id' => 'review-form']); ?>
    <table class="tb tb2 fixpadding">
        <tr><th class="partition" colspan="15">审核此项目</th></tr>
        <tr>
            <td class="td24">操作</td>
            <td><?php echo Html::radioList('operation', 1, [
                    '1' => '初审通过',
                    '2' => UserLoanOrder::$status[UserLoanOrder::STATUS_CANCEL]
                ]); ?></td>
        </tr>
        <tr>
            <td class="td24">审核码：</td>
            <td class="pass"><?php echo Html::dropDownList('code', Yii::$app->getRequest()->get('code', ''), $pass_tmp); ?></td>
            <td class="reject" style="display: none"><?php echo Html::dropDownList('nocode', Yii::$app->getRequest()->get('code', ''), $reject_tmp); ?></td>
        </tr>
        <tr class="loan_cation" style="display: none">
            <td>拒绝操作：</td>
            <td>
                <?php echo Html::dropDownList('loan_action', UserOrderLoanCheckLog::CAN_LOAN, UserOrderLoanCheckLog::$can_loan_type); ?>
            </td>
        </tr>
        <tr>
            <td class="td24">备注：</td>
            <td><?php echo Html::textarea('remark', '', ['style' => 'width:300px;', 'id' => "review-remark"]); ?></td>
        </tr>
        <tr>
            <td colspan="15">
                <input id="submit_btn" value="提交" name="submit_btn" class="btn" style="align-items: flex-start;text-align: center;">
            </td>
        </tr>
    </table>
    <?php ActiveForm::end(); ?>
</div>


<script>
    $('.gallery-pic').click(function(){
        $.openPhotoGallery(this);
    });
    $(window).resize(function(){
        $('#J_pg').height($(window).height());
        $('#J_pg').width($(window).width());
    });

    function checkPerson(id, callback){
        $.post(
            "<?php echo Url::toRoute('rule-json/check-person') ?>",
            {
                id : id
            },
            function(ret){
                if (ret.code == 0) {
                    callback && callback();
                }
            }
        )
    }
    $(':radio').click(function(){
        var code = $(this).val();
        if(code == 1){
            $('.pass').show();
            $('.pass select').attr('name','code');
            $('.loan_cation').hide();
            $('.reject').hide();
            $('.reject select').attr('name','nocode');
        }else{
            $('.pass').hide();
            $('.pass select').attr('name','nocode');
            $('.reject').show();
            $('.loan_cation').show();
            $('.reject select').attr('name','code');
        }
    });

    $("#submit_btn").click(function(){
        var code = $(":radio:checked").val();
        var text = "";
        if(code == 1){
            text = $('.pass select  option:selected').text();
        }else{
            text = $('.reject select  option:selected').text();
        }

        if (text.indexOf("须备注原因") != -1 && $("#review-remark").val() == "") {
            alert("该选项必须备注原因");
            return;
        }

        $("#review-form").submit();
    })
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
        });
    });
</script>

<script type="text/javascript">
    function changeCard(k,num){
        for(i = 0 ; i<=num;i++){
            if(i == k){
                $('#card'+i).show();
            }else{
                $('#card'+i).hide();
            }
        }
    }
    function showPic(url,id) {
        $('#' + id).attr('src', url);
    }
    function changePic(k){
        var color = '#99BBFF' ;
        for(i=1;i<6;i++){
            if(i==k){
                $('#pic'+i).show()
                $('#p'+i).css('background',color);
            }else {
                $('#pic'+i).hide()
                $('#p'+i).css('background','');
            }
        }
    }
    function showRule(id) {
        var font_color ='#0000FF';
        for (var i = 1; i < 6; i++) {
            if (i == id) {
                $('#rule' + i).show();
                $('#color' + i).css('color',font_color);
            } else {
                $('#rule' + i).hide();
                $('#color' + i).css('color','');
            }
        }
    }


    var id = "<?php echo $information['loanPerson']['id']; ?>";
    $(function() {
        getBasicReports();
    });

    function getBasicReports(){
        $.getJSON(
            "<?php echo Url::toRoute('rule-json/basic-reports-mongo'); ?>",
            {
                id : id
            },
            function(ret){
                var html = "";
                for(var i in ret.data){
                    html += "<tr><th>" + ret.data[i]['name'] + "</th>";
                    html += "<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\">" + ret.data[i]['value'] + "</td>";
                    html += "<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;\">" + ret.data[i]['result']['detail'] + "</td>";
                    html += "</tr>";
                }
                if (html == "") {
                    html = "<tr><th>暂无记录</th></tr>";
                }
                $("#data3").html(html);
            }

        )
    }

    function checkPerson(id, callback){
        $.post(
            "<?php echo Url::toRoute('rule-json/check-person'); ?>",
            {
                id : id
            },
            function(ret){
                if (ret.code == 0) {
                    callback && callback();
                }
            }
        )
    }

//    $(function() {
//
//        getTree('信用评估', $("#color2"));
//        getTree('反欺诈', $("#color3"));
//        getTree('禁止项', $("#color4"));
//
//    });
//
//    function getTree(tree_name, dom) {
//        $.getJSON(
//            "<?php //Url::toRoute('rule-json/reports-mongo') ?>//",
//            {
//                node_name: tree_name,
//                id: id
//            },
//            function (ret) {
//                if (ret['data']) {
//                    dom.text(ret['data']['text']);
//                }else {
//                    dom.text('信用评估 得分：暂无');
//                }
//            }
//        )
//    }

    // 更新评分
    function scorePerson(ob){
        $(ob).css("background", "#ccc").text("计算中");
        $.post(
            //"<?php //echo Url::toRoute('rule-json/new-report-value'); ?>//",
            {
                id : id
            },
            function(ret){
                if (ret.code == 0) {
                    location.reload();
                }else{
                    alert(ret.message);
                    $(ob).text("重新计算");
                }
            },
            'json'
        )
    }
</script>
