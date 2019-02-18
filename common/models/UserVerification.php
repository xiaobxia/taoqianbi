<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * 用户认证信息
 * This is the model class for table "{{%user_verification}}".
 * @property integer $id
 * @property integer $user_id 用户ID
 * @property integer $real_pay_pwd_status 是否设置了支付密码
 * @property integer $real_verify_status 是否进行了身份认证
 * @property integer $real_work_status 是否进行了工作信息认证
 * @property integer $real_contact_status 是否进行了紧急联系人认证
 * @property integer $real_bind_bank_card_status 是否绑定银行卡
 * @property integer $real_alipay_status 是否完成了支付宝认证
 * @property integer $real_zmxy_status 是否进行了芝麻信用认证
 * @property integer $updated_at 更新时间
 * @property integer $created_at 创建时间
 * @property string $operator_name 操作人
 * @property string $remark 备注
 * @property integer $status 状态，默认为0，备用
 * @property integer $is_quota_novice  零钱贷是否是新手：0：新手，1：非新手
 * @property integer $is_fzd_novice 房租贷是否是新手：0：新手，1：非新手
 * @property integer $real_work_fzd_status 房租贷公司认证，0，没有认证，1，进行了认证
 * @property integer $real_credit_card_status 信用卡是否添加，0：否，1：是
 * @property integer $is_first_loan 是否是首次借款，0：是，1：否
 * @property integer $real_jxl_status 是否完成聚信立流程
 * @property integer $real_more_status 是否认证了更多
 * @property integer $real_yys_status 是否完成葫芦金融流程
 * @property integer $real_taobao_status 淘宝状态
 * @property integer $real_jd_status 京东状态
 * @property integer $real_accumulation_fund 是否认证公积金
 * @property integer $real_credit_status 是否认证信用卡
 * @property integer $online_banking_status 是否授权魔蝎网银获取数据
 */
class UserVerification extends ActiveRecord {
    const TAG_ID_CARD = 1;
    const TAG_WORK_INFO = 2;
    const TAG_CONTACT_INFO = 3;
    const TAG_BANK_CARD_INFO = 4;
    const TAG_MOBILE_INFO = 5;
    const TAG_QUOTA_INFO = 6;
    const TAG_MORE_INFO = 7;
    const TAG_ZMXY_INFO = 8;
    const TAG_ALIPAY_INFO = 9;
    const TAG_TAOBAO_INFO = 10;
    const TAG_ACCREDIT_INFO = 11;
    const TAG_ACCREDIT_BANK = 12;
    const TAG_ACCREDIT_FUND = 13;
    const TAG_SOCIAL_SECURITY = 14;
    const TAG_CREDIT_INFO = 15;
    const TAG_WEIXIN_INFO = 16;
    const TAG_BANK_CARD_NEW_INFO = 17;
    const TAG_CREDIT_BANK_CARD_BILL_INFO = 18;

    public static $tags = [
        self::TAG_ID_CARD=>'身份证认证',
        self::TAG_WORK_INFO =>'工作信息认证',
        self::TAG_CONTACT_INFO =>'联系人认证',
        self::TAG_BANK_CARD_INFO =>'银行卡信息认证',
        self::TAG_MOBILE_INFO =>'手机运营商认证',
        self::TAG_QUOTA_INFO =>'额度提升',
        self::TAG_MORE_INFO =>'更多认证',
        self::TAG_ZMXY_INFO =>'芝麻信用认证',
        self::TAG_ALIPAY_INFO =>'支付宝认证',
        self::TAG_TAOBAO_INFO =>'淘宝认证',
        self::TAG_ACCREDIT_INFO => '借贷认证',
        self::TAG_ACCREDIT_BANK => '工资卡认证',
        self::TAG_ACCREDIT_FUND => '公积金认证',
        self::TAG_SOCIAL_SECURITY => '社保认证',
        self::TAG_CREDIT_INFO => '信用卡认证',
        self::TAG_WEIXIN_INFO => '微信认证',
        self::TAG_BANK_CARD_NEW_INFO => '绑定银行卡',
        self::TAG_CREDIT_BANK_CARD_BILL_INFO => '信用卡账单认证'
    ];

    /**
     * 基础认证
     */
    public static $verify_base = [
        self::TAG_ID_CARD =>'real_verify_status',
        self::TAG_CONTACT_INFO =>'real_contact_status',
        self::TAG_BANK_CARD_INFO =>'real_bind_bank_card_status',
        self::TAG_MOBILE_INFO =>'real_jxl_status',
        self::TAG_ZMXY_INFO =>'real_zmxy_status'
    ];

    /**
     * 高级认证
     */
    public static $verify_senior = [
        self::TAG_ALIPAY_INFO =>'real_alipay_status',
        self::TAG_TAOBAO_INFO =>'real_taobao_status',
        self::TAG_WORK_INFO =>'real_work_status',
        self::TAG_CREDIT_INFO => 'real_credit_status',
    ];

    /**
     * 加分认证
     */
    public static $verify_more = [
        self::TAG_ACCREDIT_INFO =>'real_accredit_status',
        self::TAG_MORE_INFO =>'real_more_status',
    ];

    const STATUS_NORMAL = 0;
    const VERIFICATION_NORMAL = 0;

    const VERIFICATION_PAY_PWD = 1;
    const VERIFICATION_VERIFY = 1;
    const VERIFICATION_WORK = 1;
    const VERIFICATION_CONTACT = 1;
    const VERIFICATION_BIND_BANK_CARD = 1; //绑定银行卡
    const VERIFICATION_BIND_CREDIT_CARD = 1; //绑定信用卡

    const VERIFICATION_WORK_FSZ =1;

    const VERIFICATION_QUOTA_NOVICE = 1;
    const VERIFICATION_FZD_NOVICE = 1;

    const VERIFICATION_JXL = 1;
    const VERIFICATION_YYS = 1;
    const VERIFICATION_ALIPAY = 1;

    const IS_FIRST_LOAN_NEW = 0;
    const IS_FIRST_LOAN_NO =1;

    const VERIFICATION_YES=1;
    const VERIFICATION_NO =0;
    const VERIFICATION_ACCUMULATION_FUND = 1;
    //认证列表 公积金状态
    const VERIFICATION_ACCUMULATION_DOING = 1;//待认证
    const VERIFICATION_ACCUMULATION_FILED = 2;//认证失败
    const VERIFICATION_ACCUMULATION_SUCCESS = 3;//已填写

    const VERIFICATION_CREDIT_EMAIL = 3;

    public static $is_first_loan=[
        self::IS_FIRST_LOAN_NEW=>'新手',
        self::IS_FIRST_LOAN_NO=>'非新手',
    ];
    public static $verification_status=[
        self::VERIFICATION_NO=>'否',
        self::VERIFICATION_YES=>'是',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_verification}}';
    }

    public static function getDb() {
        return Yii::$app->get('db_kdkj');
    }

    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'user_id' => '用户ID',
            'real_pay_pwd_status' => '支付密码',
            'real_verify_status' => '实名认证',
            'real_work_status' => '工作认证',
            'real_contact_status' => '紧急联系人认证',
            'real_bind_bank_card_status' => '绑定银行卡认证',
            'real_zmxy_status' => '芝麻认证',
            'real_alipay_status' => '支付宝认证',
            'is_quota_novice' => '零钱贷新手',
            'is_fzd_novice' => '房租贷新手',
            'real_accumulation_fund'=>'公积金认证',
            'real_credit_status' => '信用卡认证',
        ];
    }

    const TYPE_PERSON_INFO_STATUS = 1;//认证中心
    const TYPE_PERSON_CONTACT_STATUS = 2;//联系人认证
    const TYPE_PERSON_JXL_STATUS = 3;//手机运营商
    const TYPE_PERSON_ZML_STATUS = 4;//芝麻授信
    const TYPE_PERSON_CARD_STATUS = 5;//绑卡
    /**
     * 新认证流程的状态
     */
    public static $verification = [

    ];

    /**
     * 重置用户认证状态
     * @param unknown $user_id
     * @param unknown $type
     */
    public static function resetVerificationInfo($user_id,$type){
        if(!isset(self::$tags[$type])){
            return false;
        }
        if($type == self::TAG_CONTACT_INFO){//紧急联系人
            return self::updateAll(['real_contact_status'=>0],['user_id'=>$user_id]);
        }else if($type == self::TAG_MOBILE_INFO){//聚信力重置
            CreditJxlQueue::updateAll(['current_status'=>0],['user_id'=>$user_id]);
            return self::updateAll(['real_jxl_status'=>0],['user_id'=>$user_id]);
        }
        return false;
    }

    public static function saveUserVerificationInfo($attrs){

        $user_id = $attrs['user_id'];
        $user_verification = UserVerification::findOne(['user_id'=>$user_id]);
        if(empty($user_verification)){
            $user_verification = new UserVerification();
            $user_verification->user_id = $user_id;
            $user_verification->created_at = time();
        }
        if(isset($attrs['real_pay_pwd_status'])){
            $user_verification->real_pay_pwd_status =  trim($attrs['real_pay_pwd_status']);
        }
        if(isset($attrs['real_verify_status'])){
            $user_verification->real_verify_status =  trim($attrs['real_verify_status']);
        }
        if(isset($attrs['real_work_status'])){
            $user_verification->real_work_status =  trim($attrs['real_work_status']);
        }
        if(isset($attrs['real_contact_status'])){
            $user_verification->real_contact_status =  trim($attrs['real_contact_status']);
        }
        if(isset($attrs['real_bind_bank_card_status'])){
            $user_verification->real_bind_bank_card_status =  trim($attrs['real_bind_bank_card_status']);
        }
        if(isset($attrs['real_zmxy_status'])){
            $user_verification->real_zmxy_status =  trim($attrs['real_zmxy_status']);
        }
        if(isset($attrs['real_alipay_status'])){
            $user_verification->real_alipay_status =  trim($attrs['real_alipay_status']);
        }
        if(isset($attrs['remark'])){
            $user_verification->remark =  trim($attrs['remark']);
        }
        if(isset($attrs['status'])){
            $user_verification->status =  trim($attrs['status']);
        }
        if(isset($attrs['is_quota_novice'])){
            $user_verification->is_quota_novice =  trim($attrs['is_quota_novice']);
        }
        if(isset($attrs['is_fzd_novice'])){
            $user_verification->is_fzd_novice =  trim($attrs['is_fzd_novice']);
        }
        if(isset($attrs['real_work_fzd_status'])){
            $user_verification->real_work_fzd_status =  $attrs['real_work_fzd_status'];
        }
        if(isset($attrs['real_credit_card_status'])){
            $user_verification->real_credit_card_status =  $attrs['real_credit_card_status'];
        }
        if(isset($attrs['is_first_loan'])){
            $user_verification->is_first_loan =  $attrs['is_first_loan'];
        }
        if(isset($attrs['real_jxl_status'])){
            $user_verification->real_jxl_status =  $attrs['real_jxl_status'];
        }
        if(isset($attrs['real_yys_status'])){
            $user_verification->real_yys_status =  $attrs['real_yys_status'];
        }
        if(isset($attrs['real_more_status'])){
            $user_verification->real_more_status =  $attrs['real_more_status'];
        }

        if(isset($attrs['real_credit_status'])){
            $user_verification->real_credit_status =  $attrs['real_credit_status'];
        }
        if(isset($attrs['online_banking_status'])){
            $user_verification->online_banking_status =  $attrs['online_banking_status'];
        }
        if(isset($attrs['real_accumulation_fund'])){
            $user_verification->real_accumulation_fund =  $attrs['real_accumulation_fund'];
        }
        if(isset($attrs['operator_name'])){
            $user_verification->operator_name =  $attrs['operator_name'];
        }

        $user_verification->updated_at = time();
        if(!$user_verification->save()){
            return false;
        }
        return $user_verification;
    }
    /**
     * 关联对象：借款人表
     * @return LoanPerson|null
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

}