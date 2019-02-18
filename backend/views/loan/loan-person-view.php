<?php
use common\helpers\Url;
use common\models\UserQuotaPersonInfo;
use common\models\UserContact;
use common\models\CardInfo;
use common\models\UserProofMateria;
use common\models\loanPerson;
?>

<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>
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
<?php if($type == 1):?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款人信息</th></tr>
    <?php if(isset($loan_person) && !empty($loan_person)): ?>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'id'); ?></td>
            <td width="300"><?php echo $loan_person['id']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'uid'); ?></td>
            <td width="300"><?php echo $loan_person['uid']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['id_number']; ?></td>
            <td ><?php echo $loan_person['id_number']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'type'); ?></td>
            <td ><?php echo empty($loan_person['type']) ? "--" : LoanPerson::$person_type[$loan_person['type']]; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['name']; ?></td>
            <td><?php echo $loan_person['name']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'phone'); ?></td>
            <td colspan="3"><?php echo $loan_person['phone']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['birthday']; ?></td>
            <td colspan="3"><?php echo date('Y-m-d',$loan_person['birthday']); ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['property']; ?></td>
            <td colspan="3"><?php echo $loan_person['property']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['contact_username']; ?></td>
            <td colspan="3"><?php echo $loan_person['contact_username']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['contact_phone']; ?></td>
            <td colspan="3"><?php echo $loan_person['contact_phone']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'attachment'); ?></td>
            <?php if(empty($loan_person['attachment'])) :?>
            <td colspan="3">--待上传--</td>
            <?php else: ?>
            <td colspan="3"><a href="<?php echo Url::toRoute(['loan/loan-person-pic', 'id' => $loan_person['id']])?>">查看</a></td>
            <?php endif;?>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'credit_limit'); ?></td>
            <td colspan="3"><?php echo $loan_person['credit_limit']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'open_id'); ?></td>
            <td colspan="3"><?php echo $loan_person['open_id']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'created_at'); ?></td>
            <td colspan="3"><?php echo date('Y-m-d H:i:s',$loan_person['created_at']); ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'updated_at'); ?></td>
            <td colspan="3"><?php echo date('Y-m-d H:i:s',$loan_person['updated_at']); ?></td>
        </tr>
        <tr>
            <td class="td24">是否设置了支付密码:</td>
            <td colspan="3"><?php echo empty($verify['real_pay_pwd_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否进行了身份认证:</td>
            <td colspan="3"><?php echo empty($verify['real_verify_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否进行了工作信息认证:</td>
            <td colspan="3"><?php echo empty($verify['real_work_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否进行了联系人认证:</td>
            <td colspan="3"><?php echo empty($verify['real_contact_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否绑定银行卡:</td>
            <td colspan="3"><?php echo empty($verify['real_bind_bank_card_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否进行了芝麻信用认证:</td>
            <td colspan="3"><?php echo empty($verify['real_zmxy_status']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否是零钱贷新手:</td>
            <td colspan="3"><?php echo empty($verify['is_quota_novice']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否是房租贷新手:</td>
            <td colspan="3"><?php echo empty($verify['is_fzd_novice']) ? "否" : "是"; ?></td>
        </tr>
        <tr>
            <td class="td24">是否进行了房租贷公司认证:</td>
            <td colspan="3"><?php echo empty($verify['real_work_fzd_status']) ? "否" : "是"; ?></td>
        </tr>
    <?php else: ?>
        <tr>
            <td>暂无借款人相关信息</td>
        </tr>
    <?php endif; ?>
</table>

<?php else:?>
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
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif;?>
                        <?php endforeach;?>
                    </td>
                    <th>身份证：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_ID_CAR || $list['type'] == UserProofMateria::TYPE_BUSINESS_CARD || $list['type'] == UserProofMateria::TYPE_FACE_RECOGNITION || $list['type'] == UserProofMateria::TYPE_ID_CAR_Z || $list['type'] == UserProofMateria::TYPE_ID_CAR_F) : ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif;?>
                        <?php endforeach;?>
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
                        <td><?php echo isset($v['type']) ? CardInfo::$type[$v['type']] : "" ;?></td>
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
                        <td><?php echo isset(UserContact::$sources[$value['source']]) ? UserContact::$sources[$value['source']] : "";?></td>
                    </tr>
                    <tr>
                        <th>关系：</th>
                        <td><?php echo isset($value['relation_spare']) ? UserContact::$relation_types[$value['relation_spare']] : "" ;?></td>
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
                    <td colspan="10"><a href="<?php echo Url::toRoute(['mobile-contacts/mobile-contacts-list','user_id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
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
                    <td class="mark"><?php echo $information['equipment']['company_address'];?></td>
                </tr>

                <tr>
                    <th>工作证照：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_WORK_CARD): ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                    <th>工作证明：</th>
                    <td class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_WORK_PROVE) : ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                    <th>收入证明：</th>
                    <td colspan="10" class="gallerys">
                        <?php foreach($information['proof_image'] as $list): ?>
                            <?php if($list['type'] == UserProofMateria::TYPE_SALARY_CERTIFICATE) : ?>
                                <img class="gallery-pic" height="100" src="<?php echo $list['url'];?>"/>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                </tr>

            </table>
        </td>
    </tr>


</table>

<table class="tb tb2 fixpadding">
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
                    <td colspan="10"><a href="<?php echo Url::toRoute(['log/ygb-login-log-list','user_id'=>$information['loanPerson']['id']]);?>">点击查看</a></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="10">团伙骗贷提示</th></tr>
    <tr>
        <th width="110px;" class="person">用户登录信息</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <td>设备id：</td>
                    <td><?php echo empty($information['log_deviceId_list']) ? '' : implode('，',$information['log_deviceId_list']);?></td>
                </tr>
                <tr>
                    <td>登录地址：</td>
                    <td><?php echo empty($information['log_address_list']) ? '' : implode('，',$information['log_address_list']);?></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">常住区县重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['distinct_match']);?></th>

                </tr>
                <tr>
                    <th>用户ID：</th>
                    <td>
                        <?php if(!empty($information['distinct_match'])):?>
                            <?php foreach($information['distinct_match'] as $v):?>
                                <a href="<?php echo Url::toRoute(['loan/loan-person-view','id'=>$v->user_id,'type'=>2]);?>"><?php echo $v->user_id;?></a>
                            <?php endforeach;?>
                        <?php else:?>
                            无
                        <?php endif;?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">常住地址重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['address_match']);?></th>
                </tr>
                <tr>
                    <th>用户ID：</th>
                    <td>
                        <?php if(!empty($information['address_match'])):?>
                            <?php foreach($information['address_match'] as $v):?>
                                <a href="<?php echo Url::toRoute(['loan/loan-person-view','id'=>$v->user_id,'type'=>2]);?>"><?php echo $v->user_id;?></a>
                            <?php endforeach;?>
                        <?php else:?>
                            无
                        <?php endif;?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">公司名称重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['repeat_company_name']);?></th>

                </tr>
                <tr>
                    <th>用户ID：</th>
                    <td>
                        <?php if(!empty($information['repeat_company_name'])):?>
                            <?php foreach($information['repeat_company_name'] as $v):?>
                                <a href="<?php echo Url::toRoute(['loan/loan-person-view','id'=>$v->user_id,'type'=>2]);?>"><?php echo $v->user_id;?></a>
                            <?php endforeach;?>
                        <?php else:?>
                            无
                        <?php endif;?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">公司地址重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['repeat_company_address']);?></th>

                </tr>
                <tr>
                    <th>用户ID：</th>
                    <td>
                        <?php if(!empty($information['repeat_company_address'])):?>
                            <?php foreach($information['repeat_company_address'] as $v):?>
                                <a href="<?php echo Url::toRoute(['loan/loan-person-view','id'=>$v->user_id,'type'=>2]);?>"><?php echo $v->user_id;?></a>
                            <?php endforeach;?>
                        <?php else:?>
                            无
                        <?php endif;?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">登录设备重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['log_dev_match']);?></th>
                </tr>
                <tr>
                    <th>用户id</th>
                    <th>设备id</th>
                </tr>

                <?php if(!empty($information['log_dev_match'])):?>
                    <?php foreach($information['log_dev_match'] as $v):?>
                        <tr>
                            <td><?php echo $v['user_id'];?></td>
                            <td><?php echo $v['deviceId'];?></td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td>无</td></tr>
                <?php endif;?>
            </table>
        </td>
    </tr>
    <tr>
        <th width="110px;" class="person">登录地址重复</th>
        <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
            <table style="margin-bottom: 0px" class="table">
                <tr>
                    <th colspan="10">重复次数：<?php echo count($information['log_address_match']);?></th>
                </tr>
                <tr>
                    <th>用户ID</th>
                    <th>登录地址</th>
                </tr>

                <?php if(!empty($information['log_address_match'])):?>
                    <?php foreach($information['log_address_match'] as $v):?>
                        <tr>
                            <td><?php echo $v['user_id'];?></td>
                            <td><?php echo $v['address'];?></td>
                        </tr>
                    <?php endforeach;?>
                <?php else:?>
                    <tr><td>无</td></tr>

                <?php endif;?>
            </table>
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
    </script>
<?php endif;?>



