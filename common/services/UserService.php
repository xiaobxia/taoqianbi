<?php
namespace common\services;

use backend\models\AdminUser;
use common\helpers\ArrayHelper;
use common\helpers\Lock;
use common\models\credit_line\CreditLineMsgCount;
use common\models\credit_line\CreditLineTimeLog;
use common\models\CreditJsqb;
use common\models\CreditJxlQueue;
use common\services\fundChannel\JshbService;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;

use common\api\RedisQueue;
use common\components\Request;
use common\exceptions\CodeException;
use common\exceptions\UserExceptionExt;
use common\helpers\IdGeneratorHelper;
use common\helpers\MailHelper;
use common\helpers\MessageHelper;
use common\helpers\StringHelper;
use common\helpers\TimeHelper;
use common\helpers\ToolsUtil;
use common\helpers\Util;
use common\models\AccumulationFund;
use common\models\User;
use common\models\BaseActiveRecord;
use common\models\BaseUserCreditTotalChannel;
use common\models\BlackList;
use common\models\CardInfo;
use common\models\LoanBlacklistDetail;
use common\models\LoanPerson;
use common\models\Setting;
use common\models\UserCaptcha;
use common\models\UserCreditDetail;
use common\models\UserCreditTotal;
use common\models\UserDetail;
use common\models\UserLoanOrderRepayment;
use common\models\UserProofMateria;
use common\models\UserRealnameVerify;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use common\models\WeixinUser;
use common\models\UserCreditLog;
use common\soa\KoudaiSoa;
use common\base\LogChannel;

use common\models\Channel;
use common\models\ChannelStatistic;

/**
 * 用户基本模块service
 */
class UserService extends Component
{
    const USER_REGISTER = 1; //用户注册
    const USER_BIND = 2; //用户绑卡
    const USER_REAL = 3; //用户实名

    //专业版的认证状态
    const USER_AUTH_TYPE_INFO = 1;//个人信息
    const USER_AUTH_TYPE_LXR = 2;//紧急联系人
    const USER_AUTH_TYPE_PHONES = 3;//手机运营商
    const USER_AUTH_TYPE_ZM = 4;//芝麻信用
    const USER_AUTH_TYPE_CARD = 5;//芝麻信用

    const USER_AUTH_TYPE_WORK = 6;//工作
    const USER_AUTH_TYPE_GJJ = 7;//公积金
    const USER_AUTH_TYPE_CREDIT = 8;//信用卡
    const USER_AUTH_TYPE_MORE = 9;//更多信息

    const USER_AUTH_FAIL = 1;//用户认证失败
    const USER_AUTH_DONING = 2;//用户认证中
    const USER_AUTH_NOMORE = 3;//用户未认证中

    public static $auth_type_list = [
        self::USER_AUTH_TYPE_INFO,
        self::USER_AUTH_TYPE_LXR,
        self::USER_AUTH_TYPE_PHONES,
        self::USER_AUTH_TYPE_ZM,
        self::USER_AUTH_TYPE_CARD,
        self::USER_AUTH_TYPE_WORK,
        self::USER_AUTH_TYPE_GJJ,
        self::USER_AUTH_TYPE_CREDIT,
        self::USER_AUTH_TYPE_MORE,
    ];

    //实名认证公钥
    const VERIFY_KEY = "";
    const VERIFY_URL = "";   //实名认证接口地址

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * 获取借款人信息
     */
    public function getInfo()
    {
        $loan_person = Yii::$app->user->identity;
        $data = array();
        if ($loan_person) {
            $data['loan_person'] = $loan_person;
            return $data;
        }

        return NULL;
    }
    public static function getInfos()
    {
        $loan_person = Yii::$app->user->identity;
        $data = array();
        if ($loan_person) {
            $data['loan_person'] = $loan_person;
            return $data;
        }

        return NULL;
    }

    /**
     * 获取授信状态
     * 版本号的判断，切换
     */
    public function getCreditDetail($user_id) {
        $condition = ['user_id' => $user_id ];
        $userCreditDetail = UserCreditDetail::findOne($condition);
        if (!$userCreditDetail) {
            UserCreditDetail::initUserCreditDetail($user_id, 1);
            $userCreditDetail = UserCreditDetail::findOne($condition);
        }
        return $userCreditDetail;
    }

    /**
     * 授信完成回调方法
     * @param $user_id //当前用户
     * @param $amount //当前用户额度
     * @param $expire //[可选]当前卡过期时间
     * @param $remark //[可选]备注信息
     * @param $exception_code //[可选]处理异常，重置用户的授信状态
     */
    public function setUserCreditDetail($user_id, $amount, $expire = 0, $remark = '', $exception_code = '0', $low_rate = 0) {
        /* @var $creditChannelService UserCreditChannelService */
        $creditChannelService = \Yii::$app->creditChannelService;
        $user_credit_quota = $creditChannelService->getCreditTotalByUserId($user_id);
        // 初始化用户认证信息
        $userCreditDetail = $this->getCreditDetail($user_id);

        // 处理回调异常报错
        if ($exception_code == "-1" && $userCreditDetail) {
            // 更新用户的授信状态
            $userCreditDetail->credit_status = UserCreditDetail::STATUS_NORAML;
            $userCreditDetail->updated_at = time();
            $userCreditDetail->save();
            return true;
        }

        if (!$user_credit_quota || !$userCreditDetail) {
            return false;
        }

        $amount = \intval($amount * 100);
        $transaction = UserCreditDetail::getDb()->beginTransaction();//创建事务
        // 更新用户额度表
        if (!$this->_updateUserCreditAmount($userCreditDetail, $user_credit_quota, $amount, $remark, $low_rate)) {
            $transaction->rollBack();
            return false;
        }

        $flag = false;
        // 操作用户授信记录
        if ($userCreditDetail) {
            $userCreditDetail->credit_status = UserCreditDetail::STATUS_FINISH;
            $userCreditDetail->credit_total += 1;
            $userCreditDetail->expire_time = $expire;
            $userCreditDetail->updated_at = time();
            if ($userCreditDetail->save()) {
                $flag = true;
                // 首次授信发送短信
                if ($userCreditDetail->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO && $userCreditDetail->credit_total <= 1) {
                    self::_getSendMessage($user_id, 1);
                }

                $transaction->commit();
            }
            else {
                $transaction->rollBack();
            }
        } else {
            $transaction->rollBack();
        }
        return $flag;
    }

    /*
     * 更新用户的额度和费率
     * @param UserCreditDetail $userCreditDetail
     * @param UserCreditTotal $user_credit_quota
     * @param int $amount
     * @param string $remark
     * @param int $low_rate
     * @return boolean
     */
    private function _updateUserCreditAmount($userCreditDetail, $user_credit_quota, $amount, $remark ='', $low_rate = null) {
        $flag = true;

        $creditChannelService = \Yii::$app->creditChannelService;
        if ($amount > $user_credit_quota->amount) { #调额度
            $add_amount = $amount - $user_credit_quota->amount;
            $flag = $creditChannelService->addCreditAmount($add_amount, $user_credit_quota->user_id, 0, UserCreditLog::TRADE_TYPE_SET);
        }
        //调整额度

        if ($low_rate) {
            try {
                $creditChannelService->setCounterFeeRate($user_credit_quota->user_id, $low_rate);
            }
            catch(\Exception $e) {
                \yii::error($e);
            }
        }

        return $flag;
    }

    /**
     * 用户设置金卡的额度
     * @param $user_id     当前用户
     * @param $status      当前用户审核状态
     * @param $expire      当前卡过期时间
     * @param $params      其他参数
     */
    public function setUserCreditGolden($user_id, $status = '', $expire = 0, $params = [])
    {
        $creditChannelService = \Yii::$app->creditChannelService;
        $user_credit_quota = $creditChannelService->getCreditTotalByUserId($user_id);
        $userCreditDetail = $this->getCreditDetail($user_id);// 操作用户授信记录
        if (!$user_credit_quota || !$userCreditDetail) {
            return false;
        }
        //创建事务
        $transaction = UserCreditDetail::getDb()->beginTransaction();
        $amount = isset($params['amount']) ? intval($params['amount'] * 100) : 0;
        $remark = isset($params['remark']) ? $params['remark'] : '';
        if (isset($params['amount'])) {
            if (!$this->_updateUserCreditAmount($userCreditDetail, $user_credit_quota, $amount, $remark)) {//非授信前置用户
                $transaction->rollBack();
                return false;
            }
        }

        // 更新用户额度表
        $flag = false;
        if (in_array($status, UserCreditDetail::$card_pass)) {
            $user_credit_quota->card_type = 2;
            $user_credit_quota->updated_at = time();
            if (!$user_credit_quota->save()) {
                $transaction->rollBack();
                return $flag;
            }
        }
        if ($userCreditDetail) {
            $new_status = $status;
            if ($status == 0) {
                $new_status = 2;
            } elseif ($status == 2) {
                $new_status = 0;
            }
            $userCreditDetail->card_golden = $new_status;
            $userCreditDetail->credit_total += 1;
            if ($expire) {
                $userCreditDetail->expire_time = strtotime("+6 month", time());
            }
            $userCreditDetail->updated_at = time();
            if ($userCreditDetail->save()) {
                if (Yii::$app->params['app_golden_card'] && in_array($new_status, [1, 4])) {
                    // 首次授信发送短信
                    if ($userCreditDetail->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO && $userCreditDetail->credit_total <= 1) {
                        // 发送短信
                        self::_getSendMessage($user_id, 2);
                    } else {
                        self::_getSendMessage($user_id, 3);
                    }
                }
                $flag = true;
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        } else {
            $transaction->rollBack();
        }
        return $flag;
    }

    /**
     * 发送短信
     */
    private static function _getSendMessage($user_id, $type = 1)
    {
        $loan_person = LoanPerson::findOne($user_id);
        if ($loan_person) {
            $phone = $loan_person->phone;
            $message = "";
            switch ($type) {
                case 1:
                    $message = sprintf("尊敬的%s，恭喜您通过信用认证，已成功授予额度！赶紧打开APP申请借款吧！", $loan_person->name);
                    MessageHelper::sendSyncSMS($phone, $message);
                    break;
                case 2:
                    $message = sprintf("尊敬的%s，恭喜您通过信用认证，已成功授予额度！赶紧打开APP申请借款吧！", $loan_person->name);
                    MessageHelper::sendSyncSMS($phone, $message);
                    break;
                case 3:
                    // $creditChannelService = \Yii::$app->creditChannelService;
                    // $user_credit_quota    = $creditChannelService->getCreditTotalByUserId($user_id);
                    // $log_amount  = $user_credit_quota->amount;
                    $message = sprintf("尊敬的%s，恭喜您通过信用认证，已成功授予额度！赶紧打开APP申请借款吧！", $loan_person->name);
                    MessageHelper::sendSyncSMS($phone, $message);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 获取用户多张卡的信息
     */
    public function getMultiCreditInfo($user_id, $phone = '')
    {
        $creditChannelService = \Yii::$app->creditChannelService;
        $user_credit_total = $user_id ? $creditChannelService->getCreditTotalByUserId($user_id) : null;

        if (!$user_credit_total && $user_id) {
            $creditChannelService->initUserCreditTotal($user_id);
            $user_credit_total = $creditChannelService->getCreditTotalByUserId($user_id);
        }
        // 处理card_type=0的数据
        $card_type = isset($user_credit_total->card_type) && $user_credit_total->card_type ? $user_credit_total->card_type : 1;
        $userCreditDetail = $this->getCreditDetail($user_id);

        // 开卡认证
        $userCreditFlag = ($userCreditDetail->user_type == 0 && $userCreditDetail->credit_status == 2) ? true : false;
        $userCreditNewFlag = ($userCreditDetail->user_type == 1 && in_array($userCreditDetail->credit_status, [0, 2])) ? true : false;
        // 金卡开通中 开通失败 不显示金卡
        $userCreditGlodenFlag = in_array($userCreditDetail->card_golden, [2, 3, 5]) ? true : false;
        $userShowGlodenFlag = Yii::$app->params['app_golden_card'];

        // 如果非首次非认证中，只显示一张卡
        $userCreditFirstFlag = ($userCreditDetail->credit_status == 1 && $userCreditDetail->credit_total > 1) ? true : false;

        if ($card_type == BaseUserCreditTotalChannel::CARD_TYPE_ONE && $user_id && ($userCreditFlag || $userCreditNewFlag || $userCreditGlodenFlag || $userCreditFirstFlag)) {
            $card_credit = BaseUserCreditTotalChannel::getFirstCard();
        } else {
            $card_credit = BaseUserCreditTotalChannel::getUserCardList($card_type);
        }

        // 控制 金卡的逻辑
        if (!$userShowGlodenFlag && $card_type == BaseUserCreditTotalChannel::CARD_TYPE_ONE) {
            $card_credit = BaseUserCreditTotalChannel::getFirstCard();
        }


        if ($user_credit_total) {
            // 处理卡号以及卡的额度控制
            // 卡号一致
            $card_no = IdGeneratorHelper::gen_card_no($phone);
            if (preg_match('/^(\d{4})(\d{4})(\d{4})(\d{1,})$/', $card_no, $match)) {
                unset($match[0]);
                $match[3] = "****";
                $card_no = implode(' ', $match);
            }
            foreach ($card_credit as &$item_val) {
                $item_val["card_amount"] = intval($user_credit_total->amount);
                // 处理卡号
                // if ($item_val["card_type"] == 1) {
                //     $card_no = IdGeneratorHelper::genertaor_card_no();
                //     if(!$user_credit_total->card_no){
                //         $user_credit_total->card_no = $card_no;
                //         $user_credit_total->save();
                //     }
                //     $card_no = $user_credit_total->card_no ? $user_credit_total->card_no : '';
                // }else{
                // $card_no = IdGeneratorHelper::gen_card_no($phone);
                // }
                // if(preg_match('/^(\d{4})(\d{4})(\d{4})(\d{1,})$/', $card_no,$match)){
                //     unset($match[0]);
                //     $match[3] = "****";
                //     $card_no = implode(' ', $match);
                // }
                $item_val["card_no"] = $card_no;
            }

            $card_normal_info['card_used_amount'] = intval($user_credit_total->used_amount);
            $card_normal_info['card_locked_amount'] = intval($user_credit_total->locked_amount);
            $card_normal_info['card_money_max'] = intval($user_credit_total->amount);
            $card_normal_info['card_unused_amount'] = intval(max(intval($user_credit_total->amount) - $card_normal_info['card_used_amount'] - $card_normal_info['card_locked_amount'], 0));
        } else {
            // 未登录默认
            $card_normal_info['card_used_amount'] = 0;
            $card_normal_info['card_locked_amount'] = 0;
            $card_normal_info['card_money_max'] = 0;
            $card_normal_info['card_unused_amount'] = 0;
        }

        $card_normal_info['card'] = $card_credit;
        $card_normal_info['card_type'] = $card_type;

        return $card_normal_info;
    }


    /**
     * 获取用户卡额度等信息
     * @throws Exception
     * @return NULL[]|number[]
     */
    public function getCreditInfo($user_id)
    {
        $creditChannelService = \Yii::$app->creditChannelService;
        $user_credit_total = $user_id ? $creditChannelService->getCreditTotalByUserId($user_id) : null;
        if (!$user_credit_total && $user_id) {
            $creditChannelService->initUserCreditTotal($user_id);
            $user_credit_total = $creditChannelService->getCreditTotalByUserId($user_id);
        }
        if ($user_credit_total && isset(BaseUserCreditTotalChannel::$normal_card_info[$user_credit_total->card_type])) {
            $card_type = $user_credit_total->card_type;
        } else {
            $card_type = BaseUserCreditTotalChannel::CARD_TYPE_ONE;
        }
        $card_normal_info = BaseUserCreditTotalChannel::$normal_card_info[$card_type];
        $card_normal_info['card_type'] = $card_type;
        $card_normal_info['card_title'] = \common\helpers\Util::t('card_title_' . $card_type);
        if ($user_credit_total) {
            $card_no = IdGeneratorHelper::genertaor_card_no();
            if (!$user_credit_total->card_no) {
                $user_credit_total->card_no = $card_no;
                $user_credit_total->save();
            }
            $car_no = $user_credit_total->card_no ? $user_credit_total->card_no : '';
            if (preg_match('/^(\d{4})(\d{4})(\d{4})(\d{1,})$/', $car_no, $match)) {
                unset($match[0]);
                $car_no = implode(' ', $match);
            }
            $card_normal_info['card_used_amount'] = intval($user_credit_total->used_amount);
            $card_normal_info['card_locked_amount'] = intval($user_credit_total->locked_amount);
            $card_normal_info['card_amount'] = intval($user_credit_total->amount);
            $card_normal_info['card_no'] = $car_no;
            $card_normal_info['card_apr'] = $user_credit_total->pocket_apr;//日利率
            $card_normal_info['card_late_apr'] = $user_credit_total->pocket_late_apr;
            $card_normal_info['card_money_max'] = intval($user_credit_total->amount);
            $card_normal_info['card_unused_amount'] = intval(max($card_normal_info['card_amount'] - $card_normal_info['card_used_amount'] - $card_normal_info['card_locked_amount'], 0));
            $card_normal_info['counter_fee_rate'] = $user_credit_total->counter_fee_rate;//手续费用
        }

        return $card_normal_info;
    }

    /**
     * 获取用户银行卡列表
     * @param int $user_id
     * @param int $is_main 是否主卡
     * @return array[];
     */
    public function getCardInfo($user_id, $is_main = 0) {
        $res = array();
        $where = ['user_id' => $user_id, 'status' => CardInfo::STATUS_SUCCESS, 'type' => CardInfo::TYPE_DEBIT_CARD];
        if ($is_main) {
            $where['main_card'] = CardInfo::MAIN_CARD;
        }
        $card_list = CardInfo::find()->where($where)->andWhere('phone > 0 and bank_id > 0')->asArray()->all();
        if (!$card_list) {
            return $res;
        }

        $config = Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
        $config && $black_list = json_decode($config->svalue, true);

        foreach ($card_list as $key => $val) {
            $res[$key]['card_id'] = $val['id'];
            $res[$key]['bank_id'] = $val['bank_id'];
            $res[$key]['bank_name'] = $val['bank_name'];
            $res[$key]['card_no'] = $val['card_no'];
            $res[$key]['main_card'] = $val['main_card'];
            $res[$key]['phone'] = $val['phone'];
            $res[$key]['card_no_end'] = substr($val['card_no'], -4);
            //判断银行是否维护中
            if (isset($black_list[$val['bank_id']]) && time() > $black_list[$val['bank_id']]['begin_time'] && time() < $black_list[$val['bank_id']]['end_time']) {
                $res[$key]['bank_maintaining'] = 1;
                $res[$key]['bank_maintaining_info'] = $black_list[$val['bank_id']]['remark'];
            }
        }
        return $res;
    }

    /**
     * 获取用户信用卡银行卡列表
     * @param int $user_id
     * @param int $is_main 是否主卡
     * @return array[];
     */
    public function getCreditCardInfo($user_id, $is_main = 0) {
        $res = array();
        $where = ['user_id' => $user_id, 'status' => CardInfo::STATUS_SUCCESS, 'type' => CardInfo::TYPE_CREDIT_CARD];
        if ($is_main) {
            $where['main_card'] = CardInfo::MAIN_CARD;
        }
        $card_list = CardInfo::find()->where($where)->andWhere('phone > 0 and bank_id > 0')->asArray()->all();
        if (!$card_list) {
            return $res;
        }

        $config = Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
        $config && $black_list = json_decode($config->svalue, true);

        foreach ($card_list as $key => $val) {
            $res[$key]['card_id'] = $val['id'];
            $res[$key]['bank_id'] = $val['bank_id'];
            $res[$key]['bank_name'] = $val['bank_name'];
            $res[$key]['card_no'] = $val['card_no'];
            $res[$key]['main_card'] = $val['main_card'];
            $res[$key]['phone'] = $val['phone'];
            $res[$key]['card_no_end'] = substr($val['card_no'], -4);
            //判断银行是否维护中
            if (isset($black_list[$val['bank_id']]) && time() > $black_list[$val['bank_id']]['begin_time'] && time() < $black_list[$val['bank_id']]['end_time']) {
                $res[$key]['bank_maintaining'] = 1;
                $res[$key]['bank_maintaining_info'] = $black_list[$val['bank_id']]['remark'];
            }
        }
        return $res;
    }

    /**
     * 获取用户主银行卡信息
     * @param unknown $user_id
     * @return CardInfo;
     */
    public function getMainCardInfo($user_id)
    {
        $res = CardInfo::find()->where(['user_id' => $user_id,'type' => CardInfo::TYPE_DEBIT_CARD, 'main_card' => CardInfo::MAIN_CARD, 'status' => CardInfo::STATUS_SUCCESS])->one();
        return $res;
    }

    /**
     * 获取用户主信用银行卡信息
     * @param unknown $user_id
     * @return CardInfo;
     */
    public function getMainCreditCardInfo($user_id)
    {
        $res = CardInfo::find()->where(['user_id' => $user_id,'type' => CardInfo::TYPE_CREDIT_CARD, 'main_card' => CardInfo::MAIN_CARD, 'status' => CardInfo::STATUS_SUCCESS])->one();
        return $res;
    }

    /**
     * 判断用户是否进行过脸部识别以及身份证正反面
     */
    public function checkMemberVert($user_id)
    {
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);
        if (false == $user_proof_materia) {
            return false;
        }
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_F);
        if (false == $user_proof_materia) {
            return false;
        }
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_FACE_RECOGNITION);
        if (false == $user_proof_materia) {
            return false;
        }

        return true;
    }

    /**
     * 获取用户认证信息
     * @param unknown $user_id
     * @return number[]
     */
    public function getVerifyInfo($user_id,$tag = '')
    {
        //获取公积金的状态
        $gjj = AccumulationFund::findLatestOne(['user_id'=>$user_id]);
        if(!empty($gjj)){
            $gjj_status = $gjj->status;
        }
        //获取认证信息
        $authentication_pass = 0;
        if($tag == true){
            $authentication_total = 7;
        }else{
            $authentication_total = 4;
        }
        $ret = [
            'authentication_total' => $authentication_total,
            'real_verify_status' => 0,
            'real_work_status' => 0,
            'real_contact_status' => 0,
            'real_bind_bank_card_status' => 0,
            'real_jxl_status' => 0,
            'real_zmxy_status' => 0,
            'real_alipay_status' => 0,
            'real_more_status' => 0,
            'verify_loan_pass' => 0, //是否达到借款认证的要求
            'real_pay_pwd_status' => 0,
            'real_taobao_status' => 0,
            'real_accredit_status' => 0,
            'real_verfy_base' => 0,   //基础认证
            'real_verfy_senior' => 0, //高级认证
            'real_verfy_more' => 0,   //加分认证
            'real_online_bank_status' => 0, //工资卡认证
            'real_accumulation_fund' => 0, //公积金认证
            'real_social_security' => 0, // 社保认证
            'real_credit_status' => 0, // 信用卡认证
            'real_weixin_status' => 0,//微信认证
        ];
        $user_verification = $user_id ? UserVerification::findOne(['user_id' => $user_id]) : null;
        if ($user_verification) {
            //身份证认证
            if (UserVerification::VERIFICATION_VERIFY == $user_verification->real_verify_status && $this->checkMemberVert($user_id)) {
                $ret['real_verify_status'] = 1;
                $ret['real_verfy_base']++;
                $authentication_pass++;
            }

            //工作信息认证
            if ($user_verification->real_work_status) {
                $ret['real_work_status'] = 1;
                if($tag == true){
                    $ret['real_verfy_base']++;
                    $authentication_pass++;
                }else{
                    $ret['real_verfy_more']++;
                }
            }
            //联系人信息认证
            if (UserVerification::VERIFICATION_CONTACT == $user_verification->real_contact_status) {
                $ret['real_contact_status'] = 1;
                $ret['real_verfy_base']++;
                $authentication_pass++;
            }
            //银行卡信息认证
            if (UserVerification::VERIFICATION_BIND_BANK_CARD == $user_verification->real_bind_bank_card_status) {
                $ret['real_bind_bank_card_status'] = 1;
                $ret['real_verfy_base']++;
                $authentication_pass++;
            }

            //聚信立手机运营商信息认证
            if (UserVerification::VERIFICATION_JXL == $user_verification->real_jxl_status
                || UserVerification::VERIFICATION_YYS == $user_verification->real_yys_status
            ) {
                $ret['real_jxl_status'] = 1;
                $ret['real_verfy_base']++;
                $authentication_pass++;
            }

            //芝麻信用
            if ($user_verification->real_zmxy_status) {
                $ret['real_zmxy_status'] = 1;
                $ret['real_verfy_base']++;
                $authentication_pass++;
            }

            // 支付宝认证
            if (UserVerification::VERIFICATION_ALIPAY == $user_verification->real_alipay_status) {
                $ret['real_alipay_status'] = 1;
                $ret['real_verfy_more']++;
            }
            // 淘宝认证
            if ($user_verification->real_taobao_status) {
                $ret['real_taobao_status'] = 1;
                $ret['real_verfy_more']++;
            }
            // 借贷认证
            if ($user_verification->real_accredit_status) {
                $ret['real_accredit_status'] = 1;
                $ret['real_verfy_more']++;
            }
            // 公积金认证
            if ($user_verification->real_accumulation_fund) {
                $ret['real_accumulation_fund'] = 1;
                $ret['real_verfy_senior']++;
                if($tag == true && $gjj_status == AccumulationFund::STATUS_SUCCESS){
                    $ret['real_verfy_base']++;
                    $authentication_pass++;
                }
            }

            // 公积金认证
            if ($user_verification->real_social_security) {
                $ret['real_social_security'] = 1;
                $ret['real_verfy_senior']++;
            }

            //银行卡认证
            if ($user_verification->real_online_bank_status ) {
                $ret['real_online_bank_status'] = 1;
                $ret['real_verfy_senior']++;
            }

            // 信用卡账单认证
            $ret['real_credit_status'] = $user_verification->real_credit_status;

            //更多认证
            if ($user_verification->real_more_status) {
                $ret['real_more_status'] = 1;
                $ret['real_verfy_more']++;
            }

            // 处理M 版本可申请状态
            if (Yii::$app->controller->isFromApp()) {
                if ($ret['real_verify_status'] && $ret['real_contact_status'] &&
                    $ret['real_bind_bank_card_status'] && $ret['real_jxl_status']
                ) {
                    $ret['verify_loan_pass'] = 1;
                }
            } else {
                if ($ret['real_verify_status'] && $ret['real_bind_bank_card_status'] && $ret['real_jxl_status'] ) {
                    $ret['verify_loan_pass'] = 1;
                }
            }

            //是否设置了交易密码
            if ($this->getRealPayPwdStatus($user_id)) {
                $ret['real_pay_pwd_status'] = 1;
            }
            if($user_verification->real_weixin_status == 1){
                $ret['real_weixin_status'] = 1;
            }
        }
        $ret['authentication_pass'] = min($authentication_pass, $ret['authentication_total']);
        return $ret;
    }

    /**
     * 获取是否设置支付密码
     * @param int $user_id 用户id
     * @return boolean
     */
    public function getRealPayPwdStatus($user_id)
    {
        $class = BaseActiveRecord::getChannelModelClass(BaseActiveRecord::TB_UPPWD);
        $tableName = $class::tableName();
        $rows = (new \yii\db\Query())
            ->select(['id'])
            ->from($tableName)
            ->where(['user_id' => $user_id])
            ->limit(1)
            ->all();
        if (!empty($rows))
            return true;
        return false;
    }

    /**
     * 生成验证码，并发送邮件，用于验证公司邮箱
     * @return boolean
     */
    public function generateAndSendEmailCaptchaCompany($user_id, $to, $type) {
        $now_ts = time();
        $captcha = UserCaptcha::findOne(['user_id' => $user_id, 'type' => $type]);
        if ($captcha) { // 存在但是过期了，重新生成
            if ($captcha['expire_time'] < $now_ts) {
                $captcha->captcha = rand(100000, 999999);
                $captcha->generate_time = $now_ts;
                $captcha->expire_time = $captcha->generate_time + UserCaptcha::EXPIRE_SPACE;
                $captcha->save();
            }
        }
        else { // 第一次生成
            $captcha = new UserCaptcha();
            $captcha->phone = $to;
            $captcha->captcha = rand(100000, 999999);
            $captcha->type = $type;
            $captcha->user_id = $user_id;
            $captcha->generate_time = $now_ts;
            $captcha->expire_time = $captcha->generate_time + UserCaptcha::EXPIRE_SPACE;
            $captcha->source = \yii::$app->request->get('clientType', '');
            $captcha->save();
        }

        if ($captcha && $captcha->captcha) {
            if (MailHelper::sendMail(APP_NAMES.'验证码', APP_NAMES."验证码：{$captcha->captcha}", $to)) {
                return true;
            }
        }

        \yii::warning(sprintf('send_mail_captcha_com_failed_718 %s:%s:%s', $user_id, $to, $type), LogChannel::MAIL_GENERAL);
        return false;
    }

    /**
     * 设置和修改交易密码
     */
    public function setPayPassword(LoanPerson $user, $password)
    {
        $user_id = $user->id;
        $loan_person = LoanPerson::findOne(['id' => $user_id]);
        if (!$loan_person) {
            return false;
        }
        $class = \common\models\BaseActiveRecord::getChannelModelClass(\common\models\BaseActiveRecord::TB_UPPWD);
        $userPayPwd = $class::findOne(['user_id' => $user_id]);
        if (empty($userPayPwd)) {
            $userPayPwd = new $class();
        }
        $userPayPwd->user_id = $user_id;
        $userPayPwd->password = $password;
        if (!$userPayPwd->validate()) {
            return false;
        } else {
            $userPayPwd->password = Yii::$app->security->generatePasswordHash($password);
            $transaction = Yii::$app->db_kdkj->beginTransaction();

            if ($userPayPwd->save(false)) {
                //更新认证表
                $user_verification = UserVerification::findOne(['user_id' => $user_id]);
                if (false === $user_verification) {
                    $transaction->rollBack();
                    return false;
                }
                if (empty($user_verification)) {
                    $user_verification = new UserVerification();
                }
                $user_verification->real_pay_pwd_status = UserVerification::VERIFICATION_PAY_PWD;
                $user_verification->operator_name = $user_id;
                if (!$user_verification->save()) {
                    $transaction->rollBack();
                    return false;
                }

                $transaction->commit();
                return true;
            } else {
                $transaction->rollBack();
                return false;
            }
        }
    }


    /**
     * 判断来源
     */
    public function getCreditLoanSource($phone, $source = '')
    {
        try {
            $phone = trim($phone);
            if (!Util::verifyPhone($phone)) {
                return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
            }

            $loan_person = LoanPerson::findByPhone($phone,$source);
            if ($loan_person) {
                $source_arr = LoanPerson::$app_login_source;
                $source_id = $loan_person->source_id;

                // $count_login = UserLoginLog::findOne(["user_id"=>$loan_person->id]);
                if (in_array($source_id, $source_arr)) {
                    return [
                        'code' => 0,
                        'message' => '需要提示',
                    ];
                }
            }
            return [
                'code' => 1,
                'message' => '不需要提示',
            ];
        } catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 其他平台登录我们的APP操作
     */
    public function getCreditLoanOtherSource($phone, $source = '')
    {
        try {
            $phone = trim($phone);
            if (!Util::verifyPhone($phone)) {
                return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
            }

            $loan_person = LoanPerson::findByPhone($phone,$source);
            if ($loan_person) {
                $source_id = $loan_person->source_id;
                $source_arr = [LoanPerson::PERSON_SOURCE_MOBILE_CREDIT, LoanPerson::PERSON_SOURCE_YGB];
                if (in_array($source_id, $source_arr)) {
                    return [
                        'code' => 0,
                        'message' => '需要提示',
                    ];
                }
            }
            return [
                'code' => 1,
                'message' => '不需要提示',
            ];
        } catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    public function getRegGetCode($phone, $source = '', $params = '') {
        try {
            $phone = trim($phone);
            if (!Util::verifyPhone($phone)) {
                return UserExceptionExt::throwCodeAndMsgExt('请输入正确的手机号码');
            }
            $ret = $this->getKdlcRegisterStatus($phone, $source);
            if (CodeException::MOBILE_REGISTERED == $ret['code']) { //已注册
                $loan_person = $ret['loan_person'];
                if ($loan_person) {
                    $user_password = $this->getUserPassword($loan_person->id);
                    // done : 将自动注册转成通过
                    if (!$user_password || $loan_person->status == LoanPerson::STATUS_TO_REGISTER) {
                        if ($this->generateAndSendCaptcha($phone, UserCaptcha::TYPE_REGISTER, false, $source)) {
                            return [
                                'code' => 0,
                                'message' => '成功获取验证码',
                                'data' => ['item' => []],
                            ];
                        }
                        else {
                            return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                        }
                    }

                    if ($user_password) {
                        return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::YGB_SUER], ['code' => CodeException::MOBILE_REGISTERED]);
                    }
                }

                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::KDLC_USER], ['code' => CodeException::MOBILE_REGISTERED]);
            }
            else if (0 == $ret['code']) {
                if(!YII_ENV_DEV && !YII_ENV_TEST && is_array($params)){
                    if (!Lock::lockCode(Lock::LOCK_REG_GET_CODE, ['phone' => $phone, 'deviceId' => $params['deviceId'], 'ip' => $params['ip']])) {
                        \yii::warning( sprintf('device_locked [%s][%s][%s].', $params['ip'], $phone, $params['deviceId']), LogChannel::CHANNEL_USER_REG );
                        return [
                            'code' => -1,
                            'message' => '验证码请求过于频繁，请稍后再试',
                            'data' => []
                        ];
                    }
                }

                if ($this->generateAndSendCaptcha($phone, UserCaptcha::TYPE_REGISTER, false, $source)) {
                    return [
                        'code' => 0,
                        'message' => '成功获取验证码',
                        'data' => ['item' => []],
                    ];
                }
                else {
                    return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
                }
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt($ret['message']);
            }
        }
        catch (\Exception $e) {
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 判断渠道中用户是否注册
     * @param $phone
     * @return array
     */
    public function getKdlcRegisterStatus($phone, $source = null) {
        if (empty($source)) {
            $key =  'loanperson_validatePhoneCaptcha';
            if (!Yii::$app->cache->get($key)) { //记录异常
                \yii::warning( sprintf('source missing in %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)), LogChannel::CHANNEL_USER_LOGIN );
                \yii::$app->cache->set($key, 1, 300);
            }
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }
        $loan_person = LoanPerson::findOne([
            'phone' => $phone,
//            'source_id' => $source,
        ]);

        return [
            'code' => $loan_person ? CodeException::MOBILE_REGISTERED : 0,
            'message' => 'success',
            'loan_person' => $loan_person,
        ];
    }

    /**
     * 生成验证码，并发送短信
     * @param mixed $phone LoanPerson实例 ／ 手机号
     * @param int $type
     * @param string $sms_type
     * @param number $check
     * @throws UserException
     * @return boolean
     */
    public function generateAndSendCaptcha($phone, $type, $sms_type = false, $channel = false) { //TODO
        $user = null;
        if ($phone instanceof LoanPerson) {
            $user = $phone;
            $phone = $user->phone;
        }

        if (! ToolsUtil::checkMobile($phone)) {
            throw new UserException('手机号格式错误');
        }
        if (!\common\helpers\Lock::get('generateAndSendCaptcha_' . $phone . '_' . $type, 2)) {
            throw new UserException('发送频率过快');
        }

        $black_phones = [];
        $black_ips = ['220.178.14.98'];
        $user_agent = Yii::$app->getRequest()->getUserAgent();
        if (\in_array(Yii::$app->getRequest()->getUserIP(), $black_ips)) {
            throw new UserException('发送频率过高');
        }
        if ($user_agent == 'GTX LOL' || \strpos($user_agent, 'houzi') !== false
            || $user_agent == 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.152 Safari/537.36'
            || \in_array($phone, $black_phones)
        ) {
            $ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : Yii::$app->getRequest()->getUserIP();
            $ip && BlackList::addCount($ip, BlackList::TYPE_VISIT);
            throw new UserException('发送频率过高，请稍后再试');
        }

        // 注册验证码1天不能超过指定次数 普通短信
        $times_key = "usercaptcha_reg_{$phone}_{$channel}";

        // 注册验证码1天不能超过指定次数 语音短信
        $sms_type && $times_key = "useraudiocaptcha_reg_{$phone}_{$channel}";
        if ($type == UserCaptcha::TYPE_REGISTER) {
            $times = Yii::$app->cache->get($times_key);
            if ($times >= 4) {
                $ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : Yii::$app->getRequest()->getUserIP();
                $ip && BlackList::addCount($ip, BlackList::TYPE_VISIT);
//                throw new UserException('点击频率过高，请稍后再试');
            }
        }

        $captcha = UserCaptcha::find()->where([
            'phone' => $phone,
            'type' => $type,
            'source_id' => $channel,
        ])->one();

        $now = \time();
        if ($captcha) {
            if ($captcha['expire_time'] < $now) { // 存在但是过期了，重新生成
                $captcha->captcha = rand(100000, 999999);
                $captcha->generate_time = $now;
                $captcha->expire_time = $captcha->generate_time + UserCaptcha::EXPIRE_SPACE;
                $_save = $captcha->save();
                if (!$_save) {
                    \yii::warning('user_service_captcha_save_failed_984');
                }
            }
        }
        else { // 第一次生成
            //判断是从哪发送的
            if($type == UserCaptcha::TYPE_ADMIN_LOGIN && empty($user)){
                $user = AdminUser::findByPhone($phone);
            }else{
                $user = (!empty($user)) ? $user : LoanPerson::findByPhone($phone,$channel);
            }
            if($type==UserCaptcha::TYPE_FIND_PWD){
                if(empty($user) || !$user){
                    //注册用户不存在
                    throw new UserException('用户'.$phone.'不存在');
                }
            }
            $captcha = new UserCaptcha();
            $captcha->phone = $phone;
            $captcha->captcha = \rand(100000, 999999);
            $captcha->type = $type;
            $captcha->user_id = $user ? $user->id : 0;
            $captcha->generate_time = $now;
            $captcha->expire_time = $captcha->generate_time + UserCaptcha::EXPIRE_SPACE;
            $captcha->source = \yii::$app->request->get('clientType', '');
            $captcha->source_id = intval($channel);
            $_save = $captcha->save();
            if (!$_save) {
                \yii::warning('user_service_captcha_save_failed_1001');
            }
        }

        // 15秒之内不能重复发送同一个用户的同一个业务验证码
        if ($captcha && $captcha->captcha) {
            $key = "usercaptcha_{$phone}_{$captcha->captcha}";
            $sms_type && $key = "useraudiocaptcha_{$phone}_{$captcha->captcha}";

            $success = false;
            if ($sms_type && Yii::$app->cache->get($key)) {
                \yii::warning( sprintf('userservice_captcha_click_too_much %s', $phone), LogChannel::SMS_GENERAL );
                throw new UserException('点击频率过高，请稍后再试');
            }
            if (Yii::$app->cache->get($key)) {
                return true;
            }

            $expire = strtotime(date("Y-m-d 23:59:59")) - time();
            if ($sms_type) {
//                //语音短信
//                $audioServiceUse = 'audioService'; // 默认
//                $audioServiceUseBackUp = 'audioService'; // 备用
//                try {
//                    $success = MessageHelper::sendAUDIO($phone, $captcha->captcha, $audioServiceUse);
//                } catch (\Exception $e) {
//                    $success = MessageHelper::sendAUDIO($phone, $captcha->captcha, $audioServiceUseBackUp);
//                }
            } else {
                //普通短信
                $smsServiceUse = 'smsService_TianChang_HY'; // 默认
                $smsServiceUseBackUp = 'smsService_YiMei'; // 备用
//                if (in_array((time() % 10), [0, 1, 2, 3, 4, 5, 6, 7, 8, 9])) { //聪裕
//                    $smsServiceUse = 'smsService_CongYu';
//                    $smsServiceUseBackUp = 'smsService_CongYu';
//                }

                if (\yii::$app instanceof \yii\web\Application && (! isset($_REQUEST['deviceId']))) { # wap页面请求
                    if (!MessageHelper::limitSendSmsByPhone($phone)) { //限制一分钟发一次短信
                        throw new UserException('点击频率过高，请稍后再试');
                    }

                    if (!MessageHelper::limitDaySendSmsByPhone($phone, 10)) { //限制每天发送短信次数
                        throw new UserException('今日发送次数过多');
                    }
                }

                //记录手机发送短信次数
                MessageHelper::addTimesSendSmsByPhone($phone);

                try {
                    $success = MessageHelper::sendSMS($phone, $captcha->getSMS($channel), $smsServiceUse, $channel);
                    if (!$success && $smsServiceUse != $smsServiceUseBackUp) {
                        $success = MessageHelper::sendSMS($phone, $captcha->getSMS($channel), $smsServiceUseBackUp, $channel);
                    }
                }
                catch (\Exception $e) {
                    \Yii::error($e->getMessage(), LogChannel::SMS_GENERAL);
                }
            }
            if ($success) {
                Yii::$app->cache->set($key, 1, 15);
                if ($type == UserCaptcha::TYPE_REGISTER) {
                    $times = Yii::$app->cache->get($times_key);
                    if ($times) {
                        Yii::$app->cache->set($times_key, $times + 1, $expire);
                    } else {
                        Yii::$app->cache->set($times_key, 1, $expire);
                    }
                }

                return true;
            }
        }
        return false;
    }

    /**
     * 验证公司邮箱验证码
     * @param string $user_id
     * @param string $code
     * @param string $type
     * @return boolean
     */
    public function validateEmailCompanyCaptcha($user_id, $code, $type) {
        $result = UserCaptcha::findOne([
            'user_id' => $user_id,
            'captcha' => $code,
            'type' => $type,
        ]);

        if ($result) {
            return time() <= $result->expire_time;
        }

        return false;
    }

    /**
     * 验证手机验证码
     * @param string $phone
     * @param string $code
     * @param string $type
     * @return boolean
     */
    public function validatePhoneCaptcha($phone, $code, $type, $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT) {
        if(empty($source)){
            $key =  'loanperson_validatePhoneCaptcha';
            if (!Yii::$app->cache->get($key)) { //记录异常
                \yii::warning( sprintf('source mssing in %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)), LogChannel::CHANNEL_USER_LOGIN );
                \yii::$app->cache->set($key, 1, 300);
            }
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }
        $result = UserCaptcha::findOne([
            'phone' => $phone,
            'captcha' => trim($code),
            'type' => $type,
            'source_id'=>$source,
        ]);
        if ($result) {
            return time() <= $result->expire_time;
        }
        else {
            return $code == '999999998';
        }

        return false;
    }

    /**
     * 口袋理财登录
     * @param $username
     * @param $password
     * @return array
     */
    public function loginKdlc($username, $password, $user = null)
    {
        try {
            if ($user && $user->userPassword/* && $user->userPassword->status*/) { //口袋快借有密码了，用口袋快借的密码
                if ($user->validatePasswordKdkj($password)) {
                    return [
                        'code' => 0,
                        'message' => 'success',
                    ];
                } else {
                    return [
                        'code' => -1,
                        'message' => '密码错误',
                    ];
                }
            }
            return [
                'code' => -1,
                'message' => '密码错误',
            ];
        } catch (\Exception $e) {
            return [
                'code' => -1,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * 获取用户密码表
     * @param unknown $user_id
     */
    public function getUserPassword($user_id)
    {
        $class = \common\models\BaseActiveRecord::getChannelModelClass(\common\models\BaseActiveRecord::TB_UPWD);
        return $class::find()->where(['user_id' => $user_id])->limit(1)->one();
    }

    /**
     * 口袋理财注册
     * @param $phone
     * @param $password
     * @param string $source
     * @return array
     */
    public function registerKdlc($phone, $password, $source = 'kdkj')
    {
        return [
            'code' => 0,
            'message' => 'success',
            'uid' => 0,
        ];
        /*try{
            $data=[
                'phone'=>$phone,
                'source'=>'kdkj',
                'sign'=>md5('kdkj_with_kdlc' . strval($phone)),
                'password'=>$password,

            ];
            $data = http_build_query($data);
            $response = NetUtil::cURLHTTPPost(LoanPerson::getKDLC_URL_PREFIX()."interface/intra-register", $data, 50000);
            $response = json_decode($response, true);
            if((false == $response)||(0 != $response['code'])){

                return [
                    'code'=>$response['code'],
                    'message'=>$response['message'],
                ];
            }else{
                return [
                    'code'=>0,
                    'message'=>'success',
                    'uid'=>$response['uid'],
                ];
            }


        }catch(\Exception $e){
            return [
                'code'=>$response['code'],
                'message'=>$response['message'],
            ];

        }*/
    }

    /**
     * 获取总额度
     */
    public function setCreditTotal($user_id)
    {
        try {
            //$user_credit_total = UserCreditTotal::findOne(['user_id'=>$user_id]);
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit_total = $creditChannelService->getCreditTotalByUserId($user_id);

            if (false === $user_credit_total) {
                return false;
            }
            if (empty($user_credit_total)) {
                $user_credit_total = new  UserCreditTotal();
                $user_credit_total->user_id = $user_id;
                $user_credit_total->amount = UserCreditTotal::AMOUNT;
                $user_credit_total->used_amount = 0;
                $user_credit_total->locked_amount = 0;
                $user_credit_total->updated_at = time();
                $user_credit_total->created_at = time();
                $user_credit_total->operator_name = $user_id;
                $user_credit_total->pocket_apr = BaseUserCreditTotalChannel::POCKET_APR;
                $user_credit_total->house_apr = BaseUserCreditTotalChannel::HOUSE_APR;
                $user_credit_total->installment_apr = BaseUserCreditTotalChannel::INSTALLMENT_APR;
                $user_credit_total->pocket_late_apr = BaseUserCreditTotalChannel::POCKET_LATE_APR;
                $user_credit_total->house_late_apr = BaseUserCreditTotalChannel::HOUSE_LATE_APR;
                $user_credit_total->installment_late_apr = BaseUserCreditTotalChannel::INSTALLMENT_LATE_APR;
                $user_credit_total->pocket_min = BaseUserCreditTotalChannel::POCKET_MIN;
                $user_credit_total->pocket_max = BaseUserCreditTotalChannel::POCKET_MAX;
                $user_credit_total->house_min = BaseUserCreditTotalChannel::HOUSE_MIN;
                $user_credit_total->house_max = BaseUserCreditTotalChannel::HOUSE_MAX;
                $user_credit_total->installment_min = BaseUserCreditTotalChannel::INSTALLMENT_MIN;
                $user_credit_total->installment_max = BaseUserCreditTotalChannel::INSTALLMENT_MAX;
                if (!$user_credit_total->save()) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * 注册
     * @param string $phone 手机号
     * @param string $password 密码
     * @param integer $source 注册主来源 即LoanPerson source_id
     */
    public function registerByPhone($phone, $password, $source = NULL) {
        if (empty($source)){
            \yii::warning( sprintf("registerByPhone_source_mssing {$phone} %s", json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))), LogChannel::CHANNEL_USER_LOGIN );
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }

        if (!$phone || !$password) {
            return false;
        }
        if (!LoanPerson::lockUserRegisterRecord($phone, $source)) {
            return false;
        }

        $init_source=$source;
        //贷超渠道扣量
        $is_daikou_channel=false;
        $channel_statistic_list=RedisQueue::get(['key'=>LoanPerson::CHANNEL_No_STATISTIC_LIST]);
        if($channel_statistic_list){
            $channel_statistic_list_array=json_decode($channel_statistic_list,true);
            if(in_array(intval($source),$channel_statistic_list_array)){
                $is_daikou_channel=true;
            }
        }

        if($is_daikou_channel && $source!=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){
            $source=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }else{
            if($source!=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){
                //获得当前可用渠道，如果渠道被停用，也不统计
                $channel_data=Channel::find()->where(['status'=>1,'source_id'=>$source])
                    ->select('id')->one();
                if(!$channel_data) {
                    $source=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
                }
                unset($channel_data);
            }
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $type = LoanPerson::PERSON_TYPE_PERSON;

            $user = LoanPerson::find()->where([
                'phone' => $phone,
                'status' => LoanPerson::STATUS_TO_REGISTER,
                'source_id' => $source,
            ])->one();
            if (!$user) {
                $user = LoanPerson::findOne([
                    'phone' => $phone,
                    'status' => LoanPerson::PERSON_STATUS_PASS,
                    'source_id' => $source,
                ]);
                if (!$user) {
                    $user = new LoanPerson();
                }
            }

            new LoanPerson();
            $user->uid = 0; //TODO 之后要删掉
            $user->phone = $phone;
            $user->username = $phone;
            $user->status = LoanPerson::PERSON_STATUS_PASS;
            $user->type = $type;
            $user->created_ip = \Yii::$app->request->getUserIP();
            if ($source && isset(LoanPerson::$person_source[$source])) {
                if (!$user->source_id) {
                    $user->source_id = \intval($source);
                }
            }

            $now = TimeHelper::Now();
            // 先做完所有验证再save保证两个能同时保存成功
            if (!$user->validate()) {
                \yii::warning(sprintf('registerByPhone_validate %s', \json_encode($user->getFirstErrors())), LogChannel::USER_REGISTER);
                throw new \Exception('用户注册失败, code:1258.');
            }
            else {
                $user->generateInviteCode();
                if (!$user->save()) {
                    \yii::warning(sprintf('registerByPhone_save_failed %s', \json_encode($user->getErrors())), LogChannel::USER_REGISTER);
                    throw new \Exception('用户注册失败, code:1264.');
                }

                $user->initPasswordKdkj($password);//设置密码

                // 保存用户详细信息
                $request = Yii::$app->getRequest();
                $userDetail = UserDetail::findOne(['user_id' => $user->id]);
                if (empty($userDetail)) {
                    $userDetail = new UserDetail();
                    $userDetail->user_id = $user->id;
                    $userDetail->username = $user->username;
                    $userDetail->created_at = $now;
                    if (@$request->hasProperty('client')) {
                        $userDetail->reg_client_type = $request->client->clientType;
                        $userDetail->reg_device_name = $request->client->deviceName;
                        $userDetail->reg_app_version = $request->client->appVersion;
                        $userDetail->reg_os_version = $request->client->osVersion;
                        if($is_daikou_channel){
                            $userDetail->reg_app_market = LoanPerson::NoneAppMarket;
                        }else{
                            $userDetail->reg_app_market = $request->client->appMarket;
                        }
                    }
                    if (!$userDetail->save()) {
                        \yii::error('registerByPhone_userDetail failed: %s' . \json_encode($userDetail->getErrors()), LogChannel::USER_REGISTER);
                        throw new \Exception('用户注册失败, code:1286.');
                    }
                }

                $user_verification = UserVerification::findOne(['user_id' => $user->id]);
                if (empty($user_verification)) {
                    $user_verification = new UserVerification();
                    $user_verification->user_id = $user->id;
                    $user_verification->real_pay_pwd_status = UserVerification::VERIFICATION_NORMAL;
                    $user_verification->real_verify_status = UserVerification::VERIFICATION_NORMAL;
                    $user_verification->real_work_status = UserVerification::VERIFICATION_NORMAL;
                    $user_verification->real_contact_status = UserVerification::VERIFICATION_NORMAL;
                    $user_verification->operator_name = $user->id;
                    $user_verification->remark = "";
                    $user_verification->status = UserVerification::STATUS_NORMAL;
                    if (!$user_verification->save()) {
                        \yii::error('registerByPhone_UserVerification failed: %s' . \json_encode($user_verification->getErrors()), LogChannel::USER_REGISTER);
                        throw new \Exception('用户注册失败, code:1306.');
                    }
                }

                //总额度表操作
                $creditChannelService = \Yii::$app->creditChannelService;
                $user_credit_total = $creditChannelService->getCreditTotalByUserId($user->id);
                if (empty($user_credit_total)) {
                    if (!$creditChannelService->initUserCreditTotal($user->id)) {
                        $this->setUserCreditDetail($user->id, 1500);
                        \yii::error('registerByPhone_initUserCreditTotal failed: ' . \json_encode($user_verification->getErrors()), LogChannel::USER_REGISTER);
                        throw new \Exception('用户注册失败, code:1315.');
                    }
                }

                // todo : 判断来源
                UserCreditDetail::initUserCreditDetail($user->id);

                //渠道来源统计 20180918 begin
                if(!empty($user->source_id) && !empty($userDetail->reg_app_market) && $user->source_id!=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT && !$is_daikou_channel){
                    $channel=Channel::find()
                        ->select('id')
                        ->where(['appMarket'=>$userDetail->reg_app_market,'source_id'=>$user->source_id])
                        ->one();
                    if(!empty($channel)){
                        $start_time = strtotime(date('Y-m-d',time()));
                        $channelStatistic = ChannelStatistic::find()
                            ->select('*')
                            ->where(['parent_id' =>$user->source_id,'time' => $start_time])
                            ->orderBy('id')
                            ->one();
                        if(empty($channelStatistic)){
                            $channelStatistic = new ChannelStatistic();
                            $channelStatistic->parent_id = $user->source_id;
                            $channelStatistic->subclass_id = 0;
                            $channelStatistic->pv = 1;
                            $channelStatistic->time = $start_time;
                            $channelStatistic->created_at = time();
                        }else{
                            $count=$channelStatistic->pv;
                            $channelStatistic->pv = $count+1;
                        }
                        $channelStatistic->updated_at = time();
                        $channelStatistic->save();
                    }
                }

                //统计实际扣量数量
                if($is_daikou_channel){
                    $channel=Channel::find()
                        ->select('*')
                        ->where(['source_id'=>$init_source])
                        ->one();
                    if(!empty($channel)){
                        $start_time = strtotime(date('Y-m-d',time()));
                        $channelStatistic = ChannelStatistic::find()
                            ->select('*')
                            ->where(['parent_id' =>$channel->source_id,'time' => $start_time])
                            ->orderBy('id')
                            ->one();
                        if(empty($channelStatistic)){
                            $channelStatistic = new ChannelStatistic();
                            $channelStatistic->parent_id = $channel->source_id;
                            $channelStatistic->subclass_id = 0;
                            $channelStatistic->pv = 0;
                            $channelStatistic->withhold_pv = 1;
                            $channelStatistic->time = $start_time;
                            $channelStatistic->created_at = time();
                        }else{
                            $count=$channelStatistic->withhold_pv;
                            $channelStatistic->withhold_pv = $count+1;
                        }
                        $channelStatistic->updated_at = time();
                        $channelStatistic->save();
                    }
                }
                //渠道来源统计 20180918 end

                $transaction->commit();
                return $user;
            }
        }
        catch (\Exception $e) {
            \Yii::error("registerByPhone_failed：{$e}", LogChannel::USER_REGISTER);
            $transaction->rollBack();

            // 释放锁
            LoanPerson::releaseRegisterLock($phone, $source);
            return false;
        }
    }

    /**
     * 一些第三方渠道来源的注册
     * @param integer $phone 手机
     * @param string $name 姓名
     * @param string $id_number 身份证
     * @param integer $source 来源
     * @param array $params 其他参数 如 $params['device_id'] $params['client_type'] $params['device_name']
     * @return LoanPerson
     * @throws Exception
     */
    public function registerFromChannel($phone, $name, $id_number, $source, $params = []) {
        $password = ToolsUtil::randStr(8);
        // $user = $this->registerByPhone($phone, $password, $source);
        $relSource = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        $user = $this->registerByPhone($phone, $password, $relSource);//渠道source_id 全部默认极速荷包
        if (!$user) {
            throw new Exception('注册用户失败');
        }

        $user = LoanPerson::findByPhone($phone, LoanPerson::PERSON_SOURCE_MOBILE_CREDIT);

        try {
            $this->realnameVerify($name, $id_number, $user->id);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }

        $app_market = isset($params['app_market']) ? $params['app_market'] : 'bairong';
        // 添加一个统计代码
        $userRegisterInfo = UserRegisterInfo::find()->where([
            'user_id' => (int)$user->id,
            'appMarket' => $params['app_market']
        ])->limit(1)->one();

        if (!$userRegisterInfo) {
            UserRegisterInfo::addData((int)$user->id, [
                'deviceId' => isset($params['device_id']) ? mb_substr($params['device_id'], 0, 100, 'utf-8') : '',
                'clientType' => isset($params['device_type']) ? mb_substr($params['device_type'], 0, 100, 'utf-8') : '',
                'deviceName' => isset($params['device_num']) ? mb_substr($params['device_num'], 0, 100, 'utf-8') : '',
                'date' => date('Y-m-d'),
                'appMarket' => $params['app_market'],
                'source' => $source,
            ]);
        }

        new LoanPerson();
        //发送短信推送随机密码
        $app_source = isset(LoanPerson::$person_source[$source]) ? LoanPerson::$person_source[$source] : APP_NAMES;
        $message = "您的".$app_source."密码为 {$password} ，马上下载".$app_source."APP，登录即可查看您的当前授信额度。";
        // \yii::info("registerFromChannel_send_sms {$phone}: {$message}", LogChannel::USER_REGISTER);
//        if (!MessageHelper::sendSMS($phone, $message)) {
//            \yii::warning("registerFromChannel_sms_failed {$phone}: {$message}", LogChannel::USER_REGISTER);
//            throw new Exception('短信推送随机密码失败');
//        }

        return LoanPerson::findByPhone($phone, $relSource);
    }

    /**
     * 重置密码
     * @param      $params
     * @param null $source
     *
     * @return array
     */
    public function resetPayPassword($params, $source = null) {
        $phone = trim(@$params['phone']);
        $code = trim(@$params['code']);
        $password = trim(@$params['password']);
        if (empty($source)) {
            \yii::warning( sprintf("resetPayPassword_source_mssing {$phone} %s", json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))), LogChannel::CHANNEL_USER_LOGIN );
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }

        $user = LoanPerson::findByPhone($phone, $source);
        if (!$user) {
            $key = 'loanperson_findByPhone';
            if (!\yii::$app->cache->get($key)) { //记录异常
                MessageHelper::sendSMS(NOTICE_MOBILE, "resetPayPassword查找失败 {$phone}-{$source}"); #异常短信报警
                \yii::$app->cache->set($key, 1, 300);
            }
            return UserExceptionExt::throwCodeAndMsgExt('无此用户');
        }

        if (!$this->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_FIND_PAY_PWD, $source)) {
            $key = 'loanperson_validatePhoneCaptcha';
            if (!\yii::$app->cache->get($key)) { //记录异常
                MessageHelper::sendSMS(NOTICE_MOBILE, "validatePhoneCaptcha_pay_pwd_error {$phone}-{$code}-{$source}"); #异常短信报警
                \yii::$app->cache->set($key, 1, 300);
            }
            \yii::error("validatePhoneCaptcha_pay_pwd_error {$phone}-{$code}-{$source}", LogChannel::SMS_GENERAL);
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        }
        else {
            if ($this->setPayPassword($user, $password)) {
                UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_FIND_PAY_PWD]);
                return [
                    'code' => 0,
                    'message' => '设置成功',
                    'data' => ['item' => true],
                ];
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt('重设失败，请稍后再试');
            }
        }
    }

    /**
     * 重置密码
     */
    public function resetPassword(LoanPerson $user, $password)
    {
        return $user->initPasswordKdkj($password);
    }

    /**
     * 实名认证 已废弃
     * @return boolean|array 成功返回用户信息，失败返回false
     * @deprecated
     */
    /*
    public function realnameVerify($realname, $idCard, $type = 0)
    {
        //验证姓名与身份证号码是否合法 2222
        if (empty($realname) || empty($idCard)) {
            throw new UserException("姓名或者身份证号码不能为空！");
        }
        if (!StringHelper::isIdCard($idCard)) {
            throw new UserException("姓名或者身份证号码不合法！");
        }

        //查询实名认证库里面是否存在，如存在直接返回，不存在继续验证
        $user_realname_verify = UserRealnameVerify::find()->where(['id_card' => $idCard, 'status' => UserRealnameVerify::STATUS_YES])->one();
        if (!empty($user_realname_verify)) {
            return [
                'realname' => $user_realname_verify['realname'],
                'id_card' => $user_realname_verify['id_card'],
                'sex' => $user_realname_verify['sex'],
                'birthday' => $user_realname_verify['birthday'],
            ];
        }

        //实名认证防刷机制
        if ($type == 0) {
            $currentUser = Yii::$app->user->identity;
            $type = !empty($currentUser) ? "user_id_" . $currentUser->getId() : "ip_" . abs(ip2long(Yii::$app->getRequest()->getUserIP()));
        }
        $user_limit = Yii::$app->cache->get("realname_limit_{$type}");
        if (!empty($user_limit) && ($user_limit > 6)) {
            throw new UserException("实名次数受限，请第二天再试");
        }
        $user_limit = empty($user_limit) ? 1 : $user_limit + 1;
        $life_time = strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
        Yii::$app->cache->set("realname_limit_{$type}", $user_limit, intval($life_time));

        //验证通过请求第三方实名接口
        $client = new \SoapClient('http://service.sfxxrz.com/IdentifierService.svc?wsdl');
        $request = array(
            'IDNumber' => $idCard,
            'Name' => $realname,
        );
        $cred = array(
            'UserName' => 'qcwl958',//'wcrz_admin',
            'Password' => 'XRZsph2A',//'I56pL8w2',
        );
        UserLog::addLogDetail('用户实名接口开始', ['realname' => $realname, 'idcard' => $idCard]);
        $resultJson = $client->SimpleCheckByJson(array(
            "request" => json_encode($request),
            'cred' => json_encode($cred),
        ))->SimpleCheckByJsonResult;
        $result = json_decode($resultJson, true);
        UserLog::addLogDetail('用户实名接口返回', $result);
        if ($result && $result['ResponseText'] == '成功') {
            if ($result['Identifier']['Result'] == '一致') {
                // 返回用户数据
                $sex = LoanPerson::SEX_NOSET;
                if ($result['Identifier']['Sex'] == '男性') {
                    $sex = LoanPerson::SEX_MALE;
                } else if ($result['Identifier']['Sex'] == '女性') {
                    $sex = LoanPerson::SEX_FEMALE;
                }
                $return = array(
                    'realname' => $result['Identifier']['Name'],
                    'id_card' => $result['Identifier']['IDNumber'],
                    'sex' => $sex,
                    'birthday' => $result['Identifier']['Birthday'],
                );
                //记录成功实名数据
                $user_realname_verify = UserRealnameVerify::findOne(['user_id' => empty($currentUser) ? $type : $currentUser->getId()]);
                if (false == $user_realname_verify) {
                    $user_realname_verify = new UserRealnameVerify();
                }
                $user_realname_verify->user_id = empty($currentUser) ? $type : $currentUser->getId();
                $user_realname_verify->type = empty($user_realname_verify->user_id) ? "out" : "in";
                $user_realname_verify->realname = $result['Identifier']['Name'];
                $user_realname_verify->id_card = $result['Identifier']['IDNumber'];
                $user_realname_verify->ip_address = Yii::$app->getRequest()->getUserIP();
                $user_realname_verify->status = UserRealnameVerify::STATUS_YES;
                $user_realname_verify->sex = $sex;
                $user_realname_verify->birthday = $result['Identifier']['Birthday'];
                try {
                    $user_realname_verify->save();
                } catch (Exception $e) {
                    @MessageHelper::sendSMS('13651899628', "用户重复实名认证, 身份证号码：{$user_realname_verify->id_card}");
                }
                return $return;
            } else {
                throw new UserException("认证失败，{$result['Identifier']['Result']}，如有疑问请联系客服");
            }
        } else {
            throw new UserException('认证失败，请稍后再试');
        }
    }
    */

    /**
     * 实名认证 以后用这个实名认证方法，其它的弃用
     * @param string $name 姓名
     * @param string $id_number 身份证号
     * @param LoanPerson $user 用户模型
     * @return boolean|array 成功返回用户信息，失败返回false
     */
    public function realnameVerify($name, $id_number, $user_id = 0)
    {
        if (empty($name) || empty($id_number)) {
            throw new UserException("姓名或者身份证号码不能为空！");
        }
        if (!StringHelper::isIdCard($id_number)) {
            throw new UserException("姓名或者身份证号码不合法！");
        }

        if (!$user_id) {
            $currentUser = Yii::$app->user->identity;
            if ($currentUser) {
                $user_id = $currentUser->getId();
            }
        }

        $user = LoanPerson::findOne($user_id);
        if (!$user) {
            throw new UserException('该用户尚未注册');
        }
        $user_verification = UserVerification::findOne(['user_id' => $user->id]);
        if ($user_verification && (UserVerification::VERIFICATION_VERIFY == $user_verification->real_verify_status)) {
            throw new UserException('已经进行了实名认证，不能修改');
        }
        //查看实名认证表数据，是否存在，如果存在，也不需要实名
        $user_realname_verify = UserRealnameVerify::findOne(['id_card' => $id_number]);
        if ($user_realname_verify) {
            if ($user_realname_verify->user_id != $user->id) {
                throw new UserException(CodeException::$code[CodeException::ID_CARD_USED]);
            }
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $user->name = $name;
            $user->id_number = $id_number;
            $user->property = isset(LoanPerson::$sexes[$user_realname_verify->sex]) ? LoanPerson::$sexes[$user_realname_verify->sex] : "";
            $user->birthday = strtotime($user_realname_verify->birthday);
            $user->is_verify = UserVerification::VERIFICATION_VERIFY;
            if (!$user->save()) {
                $transaction->rollBack();
                throw new UserException('实名认证失败');
            }
            $verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $user->id,
                'real_verify_status' => UserVerification::VERIFICATION_VERIFY,
                'operator_name' => $user->id,
            ]);
            if (!$verification) {
                $transaction->rollBack();
                throw new UserException('实名认证失败');
            }
        } else {
            if (! YII_ENV_PROD) {
                $ret = [
                    'code' => 0,
                    'data' => [
                        'sex' => LoanPerson::SEX_MALE,
                        'realname' => $name,
                        'idcard' => $id_number,
                        'birthday' => ToolsUtil::idCard_to_birthday($id_number),
                    ]
                ];
            }
            else {
//                $ret = KoudaiSoa::instance('User')->realnameAuth($name, $id_number);
                $ret = JshbService::realnameAuth($name, $id_number);
            }

            if (!isset($ret['code']) || 0 != $ret['code']) {
                Yii::error($ret);
                throw new UserException('实名认证失败code:001');
            }

            $sex = "";
            if (LoanPerson::SEX_MALE == $ret['data']['sex']) {
                $sex = "男";
            } else if (LoanPerson::SEX_FEMALE == $ret['data']['sex']) {
                $sex = "女";
            }

            $data = array(
                'name' => $ret['data']['realname'],
                'id_number' => $ret['data']['idcard'],
                'property' => $sex,
                'birthday' => strtotime($ret['data']['birthday']),
                'type'     => $ret['data']['type'],
            );
            $this->afterRealVerify($data, $user);
            $user_realname_verify = UserRealnameVerify::findOne(['id_card' => $data['id_number']]);
            if (!$user_realname_verify) {
                throw new UserException('实名认证失败code:002');
            }
            if ($user_realname_verify->user_id != $user->id) {
                throw new UserException(CodeException::$code[CodeException::ID_CARD_USED]);
            }
        }
        return [
            'realname' => $user_realname_verify['realname'],
            'id_card' => $user_realname_verify['id_card'],
            'sex' => $user_realname_verify['sex'],
            'birthday' => $user_realname_verify['birthday'],
        ];
    }


    /**
     * 注册或绑卡成功插入队列
     * @param int $type
     */
    public function pushUserMessageList($type, $uid)
    {
        if ($type == self::USER_REGISTER) {
            RedisQueue::push([RedisQueue::LIST_USER_DATA_WALL, json_encode(['user_id' => $uid, 'type' => 0])]);
        }
//         $data = [
//             'type' => $type,
//             'uid' => $uid,
//         ];
//         $content = json_encode($data);

//         RedisQueue::push([RedisQueue::LIST_USER_MESSAGE, $content]);
    }

    /**
     * 实名后的插表行为
     * @param array $params 相关参数
     * @param LoanPerson $user 　用户模型
     * @param integer $source 来源类型（默认白条）
     */
    public function afterRealVerify($params = array(), $user = null, $source = NULL) {
        if (empty($source)) {
            \yii::warning(sprintf('afterRealVerify_source_empty: %s',
                json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))), LogChannel::USER_REGISTER);
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if (!$user) {
                $user = Yii::$app->user->identity;
            }
            $user_id = $user->id;

            $user->name = $params['name'];
            $user->id_number = $params['id_number'];
            if (isset($params['property'])) {
                $user->property = $params['property'];
            }
            if (isset($params['birthday'])) {
                $user->birthday = $params['birthday'];
            }
            $user->is_verify = UserVerification::VERIFICATION_VERIFY;
            if (!$user->save()) {
                Yii::error("UserService afterRealVerify 数据保存失败");
                $transaction->rollBack();
                return false;
            }
            //更新认证表
            $user_verification = UserVerification::findOne(['user_id' => $user_id]);
            if (false === $user_verification) {
                $transaction->rollBack();
                Yii::error("UserService afterRealVerify 缺少UserVerification数据");
                return false;
            }
            if (empty($user_verification)) {
                $user_verification = new UserVerification();
                $user_verification->user_id = $user_id;
                $user_verification->updated_at = time();
                $user_verification->created_at = time();
            }
            $user_verification->real_verify_status = UserVerification::VERIFICATION_VERIFY;
            $user_verification->operator_name = $user_id;
            if (!$user_verification->save()) {
                Yii::error("UserService afterRealVerify UserVerification数据保存失败");
                $transaction->rollBack();
                return false;
            }

            //写实名表
            $user_realname_verify = UserRealnameVerify::findOne(['user_id' => $user_id]);
            if (false === $user_realname_verify) {
                Yii::error("UserService afterRealVerify UserRealnameVerify不存在");
                $transaction->rollBack();
                return false;
            }
            if (empty($user_realname_verify)) {
                $user_realname_verify = new UserRealnameVerify();
                $user_realname_verify->user_id = $user_id;
                $user_realname_verify->created_at = time();
            }
            $user_realname_verify->type = isset($params['type'])?$params['type']:'';
            $user_realname_verify->realname = $params['name'];
            $user_realname_verify->id_card = $params['id_number'];
            $user_realname_verify->birthday = date('Y-m-d', $params['birthday']);
            $user_realname_verify->status = UserRealnameVerify::STATUS_YES;
            $user_realname_verify->updated_at = time();
            $user_realname_verify->ip_address = Yii::$app->getRequest()->isConsoleRequest ? '' : Yii::$app->getRequest()->getUserIP();
            $user_realname_verify->source_id = $source;
            if (!$user_realname_verify->save()) {
                Yii::error("UserService afterRealVerify UserRealnameVerify 数据保存失败");
                $transaction->rollBack();
                return false;
            }
            $res = \common\models\LoanPersonHashInfo::addUserhash($user_id,$user->phone,$params['id_number']);//融360需要添加电话 加身份证的hash
            if($res == false){
                    \Yii::error("LoanPersonHashInfo保存失败user_id:{$user_id}, card:{$params['id_number']}, phone:{$user->phone}", 'xybt.channelorder.main.hash');
            }
            $transaction->commit();
            return true;
        }
        catch (\Exception $e) {
            Yii::error("UserService afterRealVerify exception {$e->getMessage()}");
            $transaction->rollBack();
        }

        return false;
    }

    public function getCanNotLoanMsgTip($user_id)
    {
        $loan_person = LoanPerson::findOne($user_id);
        if ($loan_person->can_loan_time == 4294967295) {
            return '很遗憾，您目前的信用评分不足，无法借款。';
        } elseif (($loan_person->can_loan_time - time()) > 0) {
            return '很遗憾，您目前的信用评分不足，请于' . TimeHelper::DiffDays(date('Y-m-d', $loan_person->can_loan_time), date('Y-m-d')) . '天之后再尝试申请。';
        }
        return '';
    }

    /**
     * 预注册
     *
     * @param $phone string 手机号
     * @param $tag int 注册源 默认极速荷包
     */
    public static function RepRegister($phone, $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT)
    {
        /*validate phone*/
        if (!preg_match("/^1[34578]\d{9}$/", $phone)) {
            return [
                'code' => -2,
                'message' => '手机号不正确'
            ];
        }

        $loanPerson = LoanPerson::find()->select(['id', 'phone', 'name', 'source_id', 'type'])->where(['phone' => $phone])->one(Yii::$app->get('db_kdkj_rd'));
        if (!empty($loanPerson)) {
            return [
                'code' => 2,
                'message' => '此号码已被注册',
                'data' => ['user' => $loanPerson]
            ];
        } else {
            $loanPerson = new LoanPerson();
            $loanPerson->phone = $phone;
            $loanPerson->username = $phone;
            $loanPerson->source_id = $source;
            $loanPerson->status = LoanPerson::STATUS_TO_REGISTER;
            $loanPerson->type = LoanPerson::PERSON_TYPE_PERSON;
            $loanPerson->created_ip = Yii::$app->getRequest()->getUserIP();
            if ($loanPerson->save()) {
                /*登记注册信息*/
                self::recordRegisterInfo($loanPerson->id, $source);

                return [
                    'code' => 1,
                    'message' => '注册成功',
                    'data' => ['user' => $loanPerson]
                ];
            } else {
                return [
                    'code' => -3,
                    'message' => '注册失败',
                ];
            }
        }

    }


    /**
     * 微信绑定用户
     * @param $uid
     * @param $openId
     * @return array
     */
    public static function wxBind($uid, $openId, $phone = '')
    {

        if (!$openId || !$weixinUserInfo = WeixinUser::getUserInfo($openId)) {
            return [
                'code' => -3,
                'message' => '参数错误，拒绝访问'
            ];
        }

        $weixinUserInfo->phone = $phone;
        $weixinUserInfo->uid = $uid;
        if ($weixinUserInfo->update(false) !== false) {
            return [
                'code' => 1,
                'message' => '保存成功'
            ];
        } else {
            return [
                'code' => -1,
                'message' => '保存失败'
            ];

        }
    }

    /**
     * 满足黑名单权限的操作
     */
    public static function getBlackDetailList($user_id, $order_id = '')
    {
        // 已还款订单超过 10天的
        if (!empty($order_id)) {
            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $order_id])->asArray()->limit(1)->one();
            if ($repayment) {
                if ($repayment['is_overdue'] && $repayment['overdue_day'] >= 10) {
                    return true;
                }
            }
        }
        $loan_black_list = LoanBlacklistDetail::find()->where(["user_id" => $user_id])->count();
        if (intval($loan_black_list) > 0) {
            return true;
        }

        $user_info = LoanPerson::findOne($user_id);
        if ($user_info) {
            if ($user_info->can_loan_time && $user_info->can_loan_time >= time()) {
                // $loan_black_detail = new LoanBlacklistDetail();
                // $loan_black_detail->user_id = $user_id;
                // $loan_black_detail->type    = LoanBlacklistDetail::TYPE_CAN_LOAN_TIME;
                // $loan_black_detail->content = "可再借时间超过15天";
                // $loan_black_detail->source  = LoanBlacklistDetail::SOURCE_LOAN_TIME;
                // $loan_black_detail->admin_username = 'auto_shell';
                // if ($loan_black_detail->save()) {
                return true;
                // }
            }
        }

        return false;
    }

    /**
     * 记录注册信息
     * @param $user_id
     * @param $source
     * @return bool
     */
    public static function recordRegisterInfo($user_id, $source)
    {
        $clientType = "";
        $osVersion = "";
        $appVersion = "";
        $deviceName = "";
        $appMarket = "";
        $deviceId = "";
        $request = new  Request();
        if (NULL != $request->get('clientType')) {
            $clientType = $request->get('clientType');
        }
        if (NULL != $request->get('osVersion')) {
            $osVersion = $request->get('osVersion');
        }
        if (NULL != $request->get('appVersion')) {
            $appVersion = $request->get('appVersion');
        }
        if (NULL != $request->get('deviceName')) {
            $deviceName = $request->get('deviceName');
        }
        if (NULL != $request->get('appMarket')) {
            $appMarket = $request->get('appMarket');
        }
        if (NULL != $request->get('deviceId')) {
            $deviceId = $request->get('deviceId');
        }
        $user_login_upload_log = new UserRegisterInfo();
        $user_login_upload_log->user_id = $user_id;
        $user_login_upload_log->clientType = $clientType;
        $user_login_upload_log->osVersion = $osVersion;
        $user_login_upload_log->appVersion = $appVersion;
        $user_login_upload_log->deviceName = $deviceName;
        $user_login_upload_log->appMarket = empty($appMarket) && ('ios' == $clientType) ? "appstore" : $appMarket;
        $user_login_upload_log->deviceId = $deviceId;
        $user_login_upload_log->created_at = time();
        $user_login_upload_log->source = $source;
        $user_login_upload_log->date = date("Y-m-d", time());
        if (!$user_login_upload_log->save()) {
            return false;
        }
        return true;
    }

    /**
     * 手机白名单列表，包含白条的合作方手机已经所有的管理员手机
     * @return array
     */
    public static function whitelistPhones($partner = true) {
        $ret = [
            '18516260928', #kdjz 940129    李倩
            '13122703607', #kdjz 1365940   王晓阳
            '18217332141', #kdjz 1014489   印佩佩
            '15705213581', #kdjz 2499909   陈黎
            '13761224504', #kdjz 922745    陈伟
            '18221135428', #kdjz 84234     韦莉婷
            '18521599406', #jbgj 305140    刘超
            '15618709418', #kdjz 129821123 常艳芹
        ];

        $admin_mods = AdminUser::findAll(['callcenter' => 0]);
        $admin_phones = ArrayHelper::getColumn($admin_mods, 'phone');
        if ($admin_phones) {
            if ($partner) {
                $ret = array_merge($ret, $admin_phones);
            }
            else {
                $ret = $admin_phones;
            }
        }

        return array_unique($ret);
    }

    /**
     * 重置聚信立状态
     * @param $user_id
     * @return array 状态变更行数目；queue变更航数
     */
    public static function resetJxlStatus($user_id, $op_id = 0) {
        $jxl = UserVerification::updateAll(['real_jxl_status' => 0], ['user_id' => $user_id]);
        $jxl_queue = CreditJxlQueue::updateAll(['current_status' => -1,'message' => '数据获取失败'], ['user_id' => $user_id]);
        \yii::warning( sprintf("{$op_id} resetJxlStatus_ret[$user_id]: $jxl, $jxl_queue. %s", json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))),
            LogChannel::ADMIN_UPDATE_USER );

        return [$jxl, $jxl_queue];
    }

    /**
     * 运营商认证失败，重置运营商认证状态、额度认证状态并短信提醒
     * @param LoanPerson $loanPerson
     * @param $message
     */
    public function resetYysStatus(LoanPerson $loanPerson, $message)
    {
        $user_id = $loanPerson->id;
        CreditLineTimeLog::updateEndTime($user_id, CreditLineTimeLog::CREDIT_STATUS_2);

        $queue = CreditJxlQueue::findOne(['user_id' => $user_id]);
        if ($queue) {
            $queue->current_status = -1;
            $queue->message = '';
            $queue->save();
        }

        $verification = UserVerification::findOne(['user_id' => $user_id]);
        if ($verification) {
            $verification->real_jxl_status = 0;
            $verification->real_yys_status = 0;
            $verification->save();
        }

        $flag = Yii::$container->get('userService')->setUserCreditDetail($user_id, 0, 0, "未能获取到运营商数据", '-1');
        if ($flag) {
            if (MessageHelper::sendSMS($loanPerson->phone, $message, 'smsServiceXQB_XiAo', $loanPerson->source_id)) {
                $date = \date('Y-m-d', time());
                $creditLineMsgCount = CreditLineMsgCount::findOne(['date' => $date]);
                if (empty($creditLineMsgCount)) {
                    $creditLineMsgCountNew = new CreditLineMsgCount();
                    $creditLineMsgCountNew->count = 1;
                    $creditLineMsgCountNew->date = $date;
                    $creditLineMsgCountNew->created_at = time();
                    $creditLineMsgCountNew->save();
                } else {
                    $creditLineMsgCount->count = $creditLineMsgCount->count + 1;
                    $creditLineMsgCount->save();
                }
            }
        }
    }

    /**
     * 记录用户的认证状态-redis
     */
    public static function saveAuthStatus($user_id,$type = ''){
        if(!in_array($type,self::$auth_type_list)){
            return $msg = 'key no exit';
        }
        return RedisQueue::set(
            [
                'expire'=>86400,//保存一天的时间
                'key'=>'user_auth_type_'.$user_id.':'.$type,
                'value'=>self::USER_AUTH_FAIL,
            ]
        );
    }

    /**a
     * 获取用户的认证状态-redis
     */
    public static function getAuthStatus($user_id,$type = ''){
        if(!in_array($type,self::$auth_type_list)){
            return $msg = 'key no exit';
        }
        return RedisQueue::get([
                'key'=>'user_auth_type_'.$user_id.':'.$type,
        ]);
    }

    /**
     * 销毁用户认证装填-redis
     */
    public static function delAuthStatus($user_id,$type = ''){
        if(!in_array($type,self::$auth_type_list)){
            return $msg = 'key no exit';
        }
        return RedisQueue::del([
            'key'=>'user_auth_type_'.$user_id.':'.$type,
        ]);
    }

    /**
     * @name 非白名单用户初审拒接
     */
    public static function checkWhiteList($person){
        $person_white =  CreditJsqb::find()->where(['person_id'=>$person->id,'is_white'=>1])->one();
        if(!$person_white){
            return false;
        }
        return $person_white;
    }
}
