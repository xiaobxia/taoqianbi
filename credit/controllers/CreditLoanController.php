<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/12
 * Time: 14:05
 */

namespace credit\controllers;

use common\exceptions\CodeException;
use common\exceptions\UserExceptionExt;
use common\models\AutoDebitLog;
use common\models\CardInfo;
use common\models\CreditShumei;
use common\models\RepaymentIncreaseCreditLog;
use common\models\UserCreditData;
use common\models\UserCreditLog;
use common\models\LoanPerson;
use common\models\UserCreditMoneyLog;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\services\MessageService;
use common\services\ShumeiService;
use Yii;
use yii\base\Exception;
use common\helpers\TimeHelper;
use common\helpers\Util;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use credit\components\ApiUrl;
use yii\filters\AccessControl;
use common\api\RedisQueue;
use yii\rest\ViewAction;
use common\models\CreditFacePlus;
use common\models\CreditFacePlusApiLog;
use common\models\UserProofMateria;
use common\models\UserVerification;

class CreditLoanController extends BaseController {

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['get-new-my-loan'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 获取借款确认订单页面信息
     * @TODO 获取借款确认订单页面信息
     * @name 获取借款确认订单页面信息 [CreditLoanGetConfirmLoan]
     * @method post
     * @param integer $money 借款总额
     * @param integer $period 借款天数
     * @param integer $card_type 产品类型
     * @author  honglifeng
     */
    public function actionGetConfirmLoan() {
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();
        //判断对应的状态
        if(version_compare($this->getClientInfo()->appVersion ,'2.4.5') >= 0){//2.4.5才修改的提示
            $info = UserLoanOrder::checkUserOrderStatus($user_id);
            if($info != false){
                return [
                    'code' => 10,
                    'message' => $info,
                    'data' => [],
                ];
            }
        }
        $period = intval($this->request->post('period'));       // 借款天数 用于判断红包是否可用
        $money = intval($this->request->post('money')) * 100;   // 借款金额 用于判断红包是否可用
        $card_type = intval($this->request->post('card_type', \common\models\BaseUserCreditTotalChannel::CARD_TYPE_ONE));   // 借款产品种类 1：白卡；2：金卡
        $client = Yii::$app->getRequest()->getClient();
        $coupon_id = intval($this->request->post('coupon_id', 0));;//优惠券id
        $appVersion = $client->appVersion;
        try {
            $service = Yii::$container->get('loanService');
            $checkRet = $service->checkCanApply($user_id, $money, ['sub_order_type' => $this->sub_order_type]);
            if ($checkRet['code']) {
                return $checkRet;
            }

            $data = [];
            $data['money'] = sprintf("%0.2f", $money / 100);
            $loan_info = Util::calcLoanInfo($period, $money, $card_type);
            $data['original_counter_fee'] = "";//原始综合费用
            $data['counter_fee'] = sprintf("%0.2f", $loan_info['counter_fee'] / 100);//减息后的综合费用
            $true_money = $money - $loan_info['counter_fee'];
            $data['true_money'] = sprintf("%0.2f", ($true_money/100));//实际到账金额
//            $data['url_one_text'] = "《借款协议》";
            $data['url_one_text'] = "";
            $data['url_two_text'] = "《平台服务协议》";
            $data['url_three_text'] = "《授权扣款委托书》";
//            $data['url_one'] = ApiUrl::toCredit(["credit-web/platform-service", "day" => $period, "money" => $money / 100, 'type' => $card_type]);
            $data['url_one'] = '';
            $data['url_two'] = ApiUrl::toCredit(["credit-web/loan-agreement"]);
            $data['url_three'] = ApiUrl::toCredit(["credit-web/license-agreement"]);

            $data['period'] = $period;

            $service = Yii::$container->get('userService');
            $verify_info = $service->getVerifyInfo($user_id);

            //判断卡是否存在
            $user_card_info = $service->getMainCardInfo($user_id);
            if (!$user_card_info) {
                return UserExceptionExt::throwCodeAndMsgExt('请先绑定银行卡');
            }
            $data['repayment_tips'] = '';
            $data['bank_name'] = $user_card_info['bank_name'];
            $data['card_no'] = $user_card_info['card_no'];
            $config_management_fee_rate = \Yii::$app->params['management_fee_rate'];
            $new_management_free=(intval($this->request->post('money'))*$config_management_fee_rate)/100;
            if ($this->isFromXjk() && version_compare($appVersion, '1.4.1') > 0) {
                $data['tips'] = '<font color="#999999">说明：您需要 </font><font color="#f18d00">' . $period . '</font><font color="#999999"> 天后，还款</font>';
            } else if($this->isFromHBJB() && version_compare($appVersion, '1.4.1') > 0){
                $data['tips'] = '说明：您需要<font color="#ff6462">' . $period . '</font>天后，还款';
            } else if($this->isFromWZD() && version_compare($appVersion, '1.4.1') > 0 ){
                $data['tips'] = '<font color="#999999">说明：您需要 </font><font color="#f18d00">' . $period . '</font><font color="#999999"> 天后，还款</font>';
            }else if($this->isFromSXD()  && version_compare($appVersion, '1.4.1') > 0){
                $data['tips'] = '<font color="#999999">说明：您需要 </font><font color="#f18d00">' . $period . '</font><font color="#999999"> 天后，还款</font>';
            }else if ($this->isFromKxjie()) {
                $data['tips'] = '<font color="#999999">说明：您需要 </font><font color="#f18d00">' . $period . '</font><font color="#999999"> 天后，还款</font>';
            }
            else{
                $data['tips'] = '说明：您需要' . $period . '天后，还款' . ($data['money']+$new_management_free) . '元';
                $data['repayment_tips'] = $data['tips'];
            }
            $data['verify_loan_pass'] = $verify_info['verify_loan_pass'];
            $data['real_pay_pwd_status'] = $verify_info['real_pay_pwd_status'];
            $data['protocol_url'] = $this->t('protocol_url');
            $data['protocol_msg'] = ''; //'我已阅读并同意<font color="#61cae4">《</font>'.$this->t('app_name').'借款协议<font color="#61cae4">》</font>';
            $data['bank_and_card'] = $user_card_info['bank_name'].'('.\substr($user_card_info['card_no'],-4,4).')';//银行字段
            //添加借款金额范围数组 最低到最高
            $UserCreditTotal = UserCreditTotal::find()->select(['amount','used_amount','locked_amount'])->where(['user_id'=>$user_id])->one();
            if ($UserCreditTotal) {
                $amounts_max = $UserCreditTotal->amount;
                if ($amounts_max >= 100000) {
                    $amounts_max = intval($amounts_max/10000)*10000;
                }else{
                    $amounts_max = intval($amounts_max/1000)*1000;
                }
                $amounts_new_max = $amounts_max;
                $amounts_min = 50000;
                $unused_amount =  ($amounts_new_max - $UserCreditTotal->used_amount - $UserCreditTotal->locked_amount);
                for(50000; $amounts_min<=$unused_amount; $amounts_min += 10000) {
                    $amounts[] = $amounts_min;
                }
            }
            $data['money_list'] = $amounts;
            $counter_fee = $loan_info['counter_fee']/100;
            $all_late_free = $data['all_late_free'] = sprintf("%0.2f",$counter_fee*0.1);
            $data['fidelity_reserve_free'] = sprintf("%0.2f",$counter_fee*0.4);
            $data['new_management_free'] = sprintf("%0.2f",$new_management_free);
            $data['management_free'] = sprintf("%0.2f",$counter_fee*0.2);
            $data['approval_free'] = sprintf("%0.2f",($counter_fee*100-($data['all_late_free']+$data['fidelity_reserve_free']+$data['management_free'])*100)/100);
            if ($this->isFromXjk() && version_compare($appVersion, '1.4.1') > 0) {
                $data['explain'] = '<font color="#999999">说明：您的借款区间为 </font><font color="#f18d00">'.'500'.'-'.($unused_amount/100).'</font><font color="#999999"> 元</font>';//说明字段
            } else if($this->isFromHBJB() && version_compare($appVersion, '1.4.1') > 0) {
                $data['explain'] = '说明：您的借款区间为<font color="#ff6462">'.'500'.'-'.($unused_amount/100).'</font>';//说明字段
            } else if($this->isFromWZD() && version_compare($appVersion, '1.4.1') > 0) {
                $data['explain'] = '<font color="#999999">说明：您的借款区间为 </font><font color="#f18d00">'.'500'.'-'.($unused_amount/100).'</font><font color="#999999"> 元</font>';//说明字段
            }else  if ($this->isFromKxjie()) {
                $data['explain'] = '<font color="#999999">说明：您的借款区间为 </font><font color="#f18d00">'.'500'.'-'.($unused_amount/100).'</font><font color="#999999"> 元</font>';//说明字段
            }
            else {
                $data['explain'] = '说明：您的借款区间为'.'500'.'-'.$unused_amount/100;//说明字段
            }

            //综合费用明细
            $zh_fee = sprintf("%0.2f",  $counter_fee);
            $lx_fee = sprintf("%0.2f",  $all_late_free);
            $all_late_free_str = sprintf("%0.2f", $all_late_free);
            $lx_original_fee = $zh_original_fee = "";
            if(!empty($data['coupon_list'])){
                foreach ($data['coupon_list'] as $k => $coupon_info){
                    if($coupon_info['coupon_id'] == $coupon_id){
                        $lx_original_fee = sprintf("%0.2f", $data['all_late_free']);
                        $zh_original_fee = sprintf("%0.2f", $counter_fee);
                        $counter_fee_handle = $counter_fee * 100;
                        $all_late_free = $data['all_late_free'] * 100;
                        $coupon_amount =  $coupon_info["amount"] == 0 ? $coupon_info['calc_amount'] : $coupon_info["amount"] ;
                        $coupon_amount = $coupon_amount * 100;
                        $zh_fee = sprintf("%0.2f",($counter_fee_handle - $coupon_amount)/100);
                        $lx_fee = sprintf("%0.2f",($all_late_free - $coupon_info['calc_amount']*100)/100);
                        $data['original_counter_fee'] = $data['counter_fee'];//原始综合费用
                        $loan_info['counter_fee'] = $loan_info['counter_fee'] - $coupon_amount;
                        $data['counter_fee'] = sprintf("%0.2f", $loan_info['counter_fee'] / 100);//减息后的综合费用
                        $data['true_money'] = sprintf("%0.2f", ($true_money + $coupon_amount)/100);//实际到账金额
                        $all_late_free_str = sprintf("%0.2f", ($all_late_free - $coupon_info['calc_amount']*100)/100);
                        break;
                    }
                }
            }

//            $data['free_money'] = '<font color="#999999">利息:</font> <font color="#f18d00">'.$all_late_free_str.'</font> <font color="#999999">元</font>';
            $data['free_money'] = '<font color="#999999">利息:</font> <font color="#f18d00">'.$new_management_free.'</font> <font color="#999999">元</font>';
            //tips完善
            if(empty( $data['repayment_tips'] )) {
                $data['repayment_tips'] = $data['tips'].' <font color="#f18d00">'. ($data['money']+$new_management_free) . '</font> <font color="#999999">元</font>，'. $data['free_money'];
            } else {
                $data['repayment_tips'] = $data['tips']."，". $data['free_money'];
            }
            $data['fee_list'] = [
                ['title'=>'综合费用','fee'=>$zh_fee, 'original_fee' => $zh_original_fee],
                ['title'=>'互保准备金','fee'=>$data['fidelity_reserve_free']],
                ['title'=>'管理费','fee'=>$data['management_free']],
                ['title'=>'审批费','fee'=>$data['approval_free']],
                ['title'=>'利息','fee'=>$lx_fee, 'original_fee' =>$lx_original_fee],
            ];
            //征信报告提示
            $data['credit_report_tips']='征信报告费：此费用用作购买信用报告，购买信用报告，100%放款，不过此费用将不会收取。';
            $data['management_tip']='综合利息：一期（共7天）利息'.$config_management_fee_rate.'%';

            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => $data,
                    'coupon_id' => $coupon_id
                ],
            ];
        } catch (\Exception $e) {
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }


    /**
     * 申请借款
     * @TODO 申请借款
     * @name 申请借款 [creditLoanApplyLoan]
     * @method post
     * @param integer $money 借款金额
     * @param integer $period   期数，零钱贷为天数
     * @param integer $coupon_id   使用券的编号
     * @param string $pay_password 支付密码
     * @author  honglifeng
     */
    public function actionApplyLoan() {
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();

        $service = Yii::$container->get('userService');
        $verify_info = $service->getVerifyInfo($user_id);
        if (!$verify_info['verify_loan_pass']) {//认证未完成
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::NEED_VERIFY], ['code' => CodeException::NEED_VERIFY]);
        }
        if (!$verify_info['real_pay_pwd_status']) {//未设置交易密码
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::UNSET_TRADE_PASSWORD], ['code' => CodeException::UNSET_TRADE_PASSWORD]);
        }

        $day = intval($this->request->post('period'));
        $money = intval($this->request->post('money')) * 100;
        $card_type = intval($this->request->post('card_type', \common\models\BaseUserCreditTotalChannel::CARD_TYPE_ONE));
        $pay_password = $this->request->post('pay_password');
        // 处理 M版 和 58 生成订单的
        $order_other   = $this->request->post('order_other',"0");
        if (empty($pay_password)) {
            return UserExceptionExt::throwCodeAndMsgExt('请输入支付密码');
        }
        //验证支付密码
        if (!$currentUser->validatePayPassword($pay_password)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::PAYPASSWORD_ERROR], ['code' => CodeException::PAYPASSWORD_ERROR]);
        }

        //判断卡是否存在
        $user_card_info = $service->getMainCardInfo($user_id);
        if (!$user_card_info) {
            return UserExceptionExt::throwCodeAndMsgExt('请选择银行卡');
        }

        // 判断是否已经借过
        if (!UserLoanOrder::lockUserApplyLoanRecord($user_id)) {
            return UserExceptionExt::throwCodeAndMsgExt('对不起，您点击过快！');
        }

        $loanPerson = LoanPerson::findOne([
            'id' => $user_id,
            'status' => LoanPerson::PERSON_STATUS_PASS,
        ]);
        if(!$loanPerson){
            return UserExceptionExt::throwCodeAndMsgExt('对不起，您的个人资料不完善！');
        }

        // 判断face++是否通过
        $credit_face_plus = CreditFacePlus::find()->where(['user_id' => $user_id])->one();
        if (!$credit_face_plus) {
            //face++没有数据,判断用户是否更新人脸识别的照片
            $user_proof_materia = UserProofMateria::find()
                ->where([
                    'user_id' => $user_id,
                    'type' => UserProofMateria::TYPE_FACE_RECOGNITION,
                ])
                ->orderBy('id desc')
                ->one();
            if (!$user_proof_materia) {
                return UserExceptionExt::throwCodeAndMsgExt('对不起，请到个人信息中人脸识别！');
            }
            $updated_at = $user_proof_materia->updated_at;
            if($updated_at=='' || empty($updated_at)){
                $updated_at = $user_proof_materia->created_at;
            }

            $face_api_day = CreditFacePlusApiLog::find()
                ->where(['user_id'=>$user_id])
                ->andWhere(['>=','created_at',strtotime(date('Y-m-d'))])->count();
            if($face_api_day>=CreditFacePlus::FACE_PLUS_DAY){
                return UserExceptionExt::throwCodeAndMsgExt('对不起，人脸识别每日只能识别'.CreditFacePlus::FACE_PLUS_DAY.'次！');
            }

            $key=CreditFacePlus::FACE_PLUE_REDIS.$user_id;
            $credit_face_plus_redis=RedisQueue::get(['key'=>$key]);
            if($credit_face_plus_redis){
                return UserExceptionExt::throwCodeAndMsgExt('对不起，正在更新人脸识别数据，请稍后申请借款！');
            }

            //判断是否更新过
            $created_at=0;
            $credit_face_plus_api_log=CreditFacePlusApiLog::find()
                ->where([
                    'user_id' => $user_id
                ])
                ->orderBy('id desc')
                ->one();
            if($credit_face_plus_api_log){
                $created_at = $credit_face_plus_api_log->created_at;
            }

            if($updated_at>$created_at){
                //过期时间
                $expire=strtotime(date("Ymd")) + 3600*24 - time();
                RedisQueue::set(['expire'=>$expire,'key'=>$key,'value'=>time()]);

                //需要调用face++接口
                $service = Yii::$app->creditFacePlusService;
                $service->faceplusplus($loanPerson, CreditFacePlus::STATUS_PENDING);

                RedisQueue::del(["key"=>$key]);
                $credit_face_plus = CreditFacePlus::find()->where(['user_id' => $user_id])->one();
                if(!$credit_face_plus){
                    return UserExceptionExt::throwCodeAndMsgExt('对不起，人脸识别失败，请重新人脸识别！');
                }
            }else{
                //修改tb_user_verification中real_verify_status=0
                $user_verification=UserVerification::find()->where(['user_id'=>$user_id,'real_verify_status'=>UserVerification::VERIFICATION_YES])->select('*')->one();
                if($user_verification){
                    $user_verification->real_verify_status=UserVerification::VERIFICATION_NO;
                    $user_verification->save();
                }
                unset($user_verification);
                return UserExceptionExt::throwCodeAndMsgExt('对不起，请重新人脸识别！');
            }
        }

        $free_percentage = 0;
        $amount = 0;
        $service2 = Yii::$container->get('loanService');
        //订单类型
        $sub_order_type = $this->sub_order_type;

        $ret = $service2->applyLoan($user_id, $money, $day, $user_card_info->id, [
            'sub_order_type' => $sub_order_type,
            'from_app' => $this->from_app,
            'amount' =>  $amount,
            'card_type' => empty($card_type) ? 1 : $card_type,
            'is_real_contact' => $verify_info["real_contact_status"],
            'order_other'  => empty($order_other) ? 0 : $order_other, //处理M版 和 58 没有 通讯录下单的问题
            'free_percentage' => $free_percentage,//减息比例
        ]);

        // 释放锁
        if ($ret['code'] != '0') {
            UserLoanOrder::releaseApplyLoanLock($user_id);
        }

        // 如果是M版，发送短信通知下载APP
        if (!$this->isFromApp() && $order_other == UserLoanOrder::ORDER_CONTACT_STATUS_M  && $ret['code']==0) {
            $is_send_flag = $verify_info["real_contact_status"] ? true : false;
            if ( $is_send_flag == false) {
                $phone = $currentUser->phone;
                if ($phone) {
                    $message = "恭喜您成功提交借款订单，下载app完成最后一步即可激活订单，激活后30分钟内放款。";
                    UserLoanOrder::sendSyncSms($phone,$message,["sub_order_type"=>$this->sub_order_type]);
                }
            }
        }

        if(isset($ret['order_id']))
        {
            $apply_url = ApiUrl::toRouteMobile('loan/application-success?order_id=' . $ret['order_id']);
            $data['data']['item']['apply_url'] = $apply_url;
        }


        $data['data']['item']['order_id'] = isset($ret['order_id']) ? $ret['order_id'] : 0;

        $data['code'] = $ret['code'];
        $data['message'] = $ret['message'];

        return $data;
    }

    /**
     * 获取借款详情
     * @name 获取借款详情 [CreditLoanGetLoanDetail]
     * @method post
     * @param integer $id
     * @author  yuxuejin
     */
    public function actionGetLoanDetail() {
        $data = [];
        return [
            'code' => 0,
            'message' => '成功获取',
            'data' => [
                'item' => $data,
            ],
        ];
    }

    /**
     * 获取我的借款
     * @TODO 获取我的借款
     * @name 获取我的借款 [CreditLoanGetMyOrders]
     * @method POST
     * @param integer $page 第几页，0,1都属于第一页
     * @param integer $pagsize 每页数量
     * @author  honglifeng
     */
    public function actionGetMyOrders() {
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();
        $page = (intval($this->request->post('page')) == 0) ? 1 : intval($this->request->post('page'));
        $pagsize = (intval($this->request->post('pagsize')) == 0) ? 20 : intval($this->request->post('pagsize'));
        $query = UserLoanOrder::find()->from(UserLoanOrder::tableName() . ' as o')->leftJoin(UserLoanOrderRepayment::tableName() . ' as r', 'o.id=r.order_id')
            ->where(['o.user_id' => $user_id, 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])
            ->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere(['sub_order_type' => $this->sub_order_type]))
            ->andWhere(['sub_order_type'=>UserLoanOrder::SUB_TYPE_XJD])
            ->select(['o.*', 'r.id as rep_id', 'r.is_overdue', 'r.overdue_day', 'r.status as rep_status', 'r.plan_fee_time', 'r.coupon_id as rep_coupon_id', 'r.coupon_money as rep_coupon_money', 'c.money'])->orderBy('o.id desc');
        $query->leftJoin(RepaymentIncreaseCreditLog::tableName() . ' AS c', 'o.id = c.order_id');

        $user_loan_order = $query->offset(($page - 1) * $pagsize)->limit($pagsize)->asArray()->all();
        $data = [];
        $day = date('Y-m-d');
        $diff_day = 0;
        $color = $this->getColor();
        foreach ($user_loan_order as $item) {


            $text_tip = '';
            $plan_fee_time = (!empty($item['plan_fee_time'])) ? date("Y-m-d", $item['plan_fee_time']) : "";
            if ($item['rep_id']) {//已放款
                if ($item['rep_status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                    if($this->isFromXjk() || $this->isFromKxjie()){
                        $text_tip = '<font color="#B0B0B0" size="3">已还款</font>';
                    }else{
                        $text_tip = '<font color="#999999" size="3">已还款</font>';
                    }
                } else if ($item['is_overdue'] && $item['overdue_day']) {
                    if($this->isFromXjk() || $this->isFromKxjie()){
                        $text_tip = '<font color="#F41C1C" size="3">已逾期' . $item['overdue_day'] . '天</font>';//修改下发字体颜色 guoxiayong 2017-08-24
                    }else{
                        $text_tip = '<font color="#F41C1C" size="3">已逾期' . $item['overdue_day'] . '天</font>';
                    }
                } else if ($diff_day = TimeHelper::DiffDays($item['plan_fee_time'], date('Y-m-d'), $day) && $diff_day > 0) {
                    if($this->isFromXjk() || $this->isFromKxjie()) {
                        if($plan_fee_time == $day) {
                            $text_tip = '<font color="#ff8003" size="3">今日还款有惊喜</font>';
                        }else{
                            $text_tip = '<font color="#ff8003" size="3">' . $diff_day . '天后还款</font>';
                        }
                    }else{
                        $text_tip = '<font color="#ff6462" size="3">' . $diff_day . '天后还款</font>';
                    }
                } else {
                    if($this->isFromXjk() || $this->isFromKxjie()){
                        if($plan_fee_time == $day){
                            $text_tip = '<font color="#ff8003" size="3">今日还款有惊喜</font>';
                        }else{
                            $text_tip = '<font color="#ff8003" size="3">待还款</font>';
                        }
                    }else if($this->isFromWZD()){
                        $text_tip = '<font color="#ff6462" size="3">待还款</font>';
                    }else{
                        $text_tip = '<font color="#ff6462" size="3">待还款</font>';
                    }
                }
            } else {
                if ($item['status'] < UserLoanOrder::STATUS_CHECK) {
                    if($this->isFromXjk() || $this->isFromKxjie()) {
                        $text_tip = '<font color="#B0B0B0" size="3">审核未通过</font>';
                        if(isset($currentUser->can_loan_time)
                            && $currentUser->can_loan_time - time() > 0
                            && version_compare($this->getClientInfo()->appVersion ,'2.4.5') >= 0
                        ){
                            $text_tip = '<font color="#B0B0B0" size="3">状态有更新</font>';
                        }
                    }else{
                        $text_tip = '<font color="#ffb400" size="3">审核未通过</font>';
                    }
                } else if ($item['status'] >= UserLoanOrder::STATUS_PAY && !in_array($item['status'], UserLoanOrder::$checkStatus)) {
                    if($this->isFromXjk() || $this->isFromKxjie()) {
                        $text_tip = '<font color="#ff8003" size="3">打款中</font>';
                    }else{
                        $text_tip = '<font color="#212121" size="3">打款中</font>';
                    }
                } else {
                    if($this->isFromXjk() || $this->isFromKxjie()) {
                        $text_tip = '<font color="#ff8003" size="3">审核中</font>';
                    }else{
                        $text_tip = '<font color="#212121" size="3">审核中</font>';
                    }
                }
            }
            $userCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$currentUser->getId(),'order_id'=>$item['id']])->orderBy(['id'=>SORT_DESC])->one();
            $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$currentUser->getId(),'order_id'=>$item['id']])->orderBy(['id'=>SORT_DESC])->one();
            if ($autoDebitLog && in_array($autoDebitLog['status'],[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT])) {
                $text_tip = '<font color="#ff8003" size="3">还款中</font>';
                $url = ApiUrl::toRouteMobile(['loan/alipay-result', 'id' => $item['id']]);
            } elseif($userCreditMoneyLog && in_array($userCreditMoneyLog['status'],[UserCreditMoneyLog::STATUS_ING,UserCreditMoneyLog::STATUS_NORMAL,UserCreditMoneyLog::STATUS_APPLY])) {
                $text_tip = '<font color="#ff8003" size="3">还款中</font>';
                $url = ApiUrl::toRouteMobile(['loan/alipay-result', 'id' => $item['id']]);
            } else  {
                $url = ApiUrl::toRouteMobile(['loan/loan-detail', 'id' => $item['id']]);
            }

            if($item['money'] === null)
                $is_credit_line = 0;
            else
                $is_credit_line = 1;

            $data[] = [
                'title' => '借款' . sprintf("%0.2f", $item['money_amount'] / 100) . '元',
                'time' => date('Y-m-d H:i', $item['order_time']),
                'url' => $url,
                'text' => $text_tip,
                'is_credit_line' => $is_credit_line,
            ];
        }

        return [
            'code' => 0,
            'message' => '成功获取',
            'data' => [
                'item' => $data,
                "link_url" => $this->t('repayment_help_url'),
            ],
        ];
    }

    /**
     * 获取我的还款
     * @TODO 获取我的还款
     * @name 获取我的还款 [CreditLoanGetMyLoan]
     * @method POST
     * @author  honglifeng
     *
     */
    public function actionGetMyLoan() {
        $currentUser = Yii::$app->user->identity;
        $page = (intval($this->request->post('page')) == 0) ? 1 : intval($this->request->post('page'));
        $pagsize = (intval($this->request->post('pagsize')) == 0) ? 20 : intval($this->request->post('pagsize'));
        $query = UserLoanOrderRepayment::find()->from(UserLoanOrderRepayment::tableName() . ' as r')->leftJoin(UserLoanOrder::tableName() . ' as o', 'r.order_id=o.id')
            ->where(['r.user_id' => $currentUser->getId(), 'o.order_type' => UserLoanOrder::LOAN_TYPE_LQD])->andWhere(" r.status <> " . UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
            ->select(['r.*', 'o.counter_fee'])->andWhere(UserLoanOrder::getOutAppSubOrderTypeWhere(['sub_order_type' => $this->sub_order_type]))
            ->orderBy('r.id desc');
        $user_loan_order = $query->asArray()->all();
        $data = array();
        $source = $this->getSource();
        $color1 = '#ff8003';
        $color2 = '#ff8003';
        switch ($source){
            case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
                $color1 = '#FC6161';//待还款
                $color2 = '#f41c1c';//逾期
                break;
        }
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $color1 = '#31b27a';//待还款
            $color2 = '#f41c1c';//逾期
        }
        if (count($user_loan_order) > 0) {
            $day = date('Y-m-d');
            $diff_day = 0;
            foreach ($user_loan_order as $key => $item) {
                $userCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$currentUser->getId(),'order_id'=>$item['order_id']])->orderBy(['id'=>SORT_DESC])->one();
                $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$currentUser->getId(),'order_id'=>$item['order_id']])->orderBy(['id'=>SORT_DESC])->one();
                if ($autoDebitLog && in_array($autoDebitLog['status'],[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT])) {
                    $text_tip = '<font color='.$color1.' size="3">还款中</font>';
                    $span_tip = '<em style="color:#ff8003">已逾期' . $item['overdue_day'] . '天</em>';
                    $url = ApiUrl::toRouteMobile(['loan/alipay-result', 'id' => $item['order_id']]);
                } elseif($userCreditMoneyLog && in_array($userCreditMoneyLog['status'],[UserCreditMoneyLog::STATUS_ING,UserCreditMoneyLog::STATUS_NORMAL,UserCreditMoneyLog::STATUS_APPLY])){
                    $text_tip = '<font color='.$color1.' size="3">还款中</font>';
                    $span_tip = '<em style="color:#ff8003">已逾期' . $item['overdue_day'] . '天</em>';
                    $url = ApiUrl::toRouteMobile(['loan/alipay-result', 'id' => $item['order_id']]);
                } else {
                    $url =   ApiUrl::toRouteMobile(['loan/loan-detail', 'id' => $item['order_id']]);
                    if ($item['is_overdue'] && $item['overdue_day']) {
                        $text_tip = '<font color='.$color2.' size="3">已逾期' . $item['overdue_day'] . '天</font>';
                        $span_tip = '<em style="color:#ff8003">已逾期' . $item['overdue_day'] . '天</em>';
                    } else if ($diff_day = TimeHelper::DiffDays($item['plan_fee_time'], $day) && $diff_day > 0) {
                        $text_tip = '<font color= '.$color1.' size="3">' . $diff_day . '天后还款</font>';
                        $span_tip = '<em style="color:#ff8003">已逾期' . $item['overdue_day'] . '天</em>';
                    } else {
                        $text_tip = '<font color= '.$color1.' size="3">待还款</font>';
                        $span_tip = '<em style="color:#ff8003">已逾期' . $item['overdue_day'] . '天</em>';
                    }
                }

                $loan_term = UserLoanOrder::find()->where(['id'=>$item['order_id']])->select(['loan_term'])->asArray()->one();
                $data[$key] = [
                    'debt' => sprintf('%.2f', ($item['principal'] + $item['interests'] + $item['late_fee'] - $item['true_total_money']) / 100), //实际欠款
                    'principal' => sprintf('%.2f', $item['principal'] / 100), //本金
                    'counter_fee' => sprintf('%.2f', $item['counter_fee'] / 100), //服务费
                    'receipts' => sprintf('%.2f', ($item['principal'] - $item['counter_fee']) / 100), //实际到账
                    'interests' => sprintf('%.2f', $item['interests'] / 100), //利息
                    'late_fee' => sprintf('%.2f', $item['late_fee'] / 100), //滞纳金
                    'plan_fee_time' => date('Y-m-d', $item['plan_fee_time']), //应还日期
                    'text_tip' => $text_tip,
                    'span_tip' => $span_tip,
                    'loan_time' => date('Y-m-d',$item['created_at']),
                    'url' => $url,
                    'pay_url' => ApiUrl::toRouteMobile(['loan/loan-repayment-type', 'id' => $item['order_id']]),//立即还款
                    'day'=>$loan_term['loan_term'],
                    'have_repayment'=>sprintf('%.2f', $item['true_total_money'] / 100), //已还金额
                ];
            }
        }

        $pay_title = "支持多种还款方式，方便快捷";
        $pay_type = array(
            array(
                "type" => 1,
                "title" => "主动还款(银行卡)",
                "img_url" => $this->staticUrl('image/card/union_pay.png'),
                "link_url" => $this->t('repayment_help_url'),
            ),
            array(
                "type" => 2,
                "title" => "到期自动扣款(银行卡)",
                "img_url" => $this->staticUrl('image/card/union_pay.png'),
                "link_url" => $this->t('repayment_help_url'),
            ),
            array(
                "type" => 3,
                "title" => "支付宝还款",
                "img_url" => $this->staticUrl('image/card/alipay_card_info.png'),
                "link_url" => $this->t('repayment_help_url'),
            ),
        );

        // 处理APP还款白屏逻辑的
        $count = count($data);
//         if ($this->isFromXjk() && $this->client->clientType == 'ios' && $count > 0) {
//             $count = sprintf("如无法打开还款页请卸载重装。%d",$count);
//         }

        return [
            'code' => 0,
            'message' => '成功获取',
            'data' => [
                'item' => ['list' => $data, 'count' => $count, 'pay_title' => $pay_title, 'pay_type' => $pay_type],
            ],
        ];
    }



    /**
     * 用户确认失败记录
     * @name 用户确认失败记录 [CreditConfirmFailedLoan]
     * @method post
     * @param integer $id
     * @author  cheyanbing
     */
    public function actionConfirmFailedLoan() {
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();
        $service = Yii::$container->get('loanService');
        $id = $this->request->post('id');
        if ($id && $user_id && $service->confirmFailedLoan($id, $user_id)) {
            return[
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => [],
                ],
            ];
        } else {
            return[
                'code' => -1,
                'message' => '操作失败',
                'data' => [
                    'item' => [],
                ],
            ];
        }
    }

    /**
     * 用户待还款记录列表
     * @name 用户待还款记录列表 [user-repayment-list]
     * @method post
     * @param int coupon_id 卡券号
     * @author  wangjie
     */
    public function actionUserRepaymentList() {
        $user_id = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
        $query = UserLoanOrderRepayment::find()->where(['user_id' => $user_id])->andWhere(['<>', 'status', 4])->select(['id', 'user_id', 'order_id', 'status', 'principal', 'interests', 'late_fee', 'true_total_money', 'plan_repayment_time'])->orderBy('plan_repayment_time ASC')->asArray()->all();

        $data = [];
        if ($query && count($query) > 0) {
            foreach ($query as $value) {
                $repayMoney = ($value['principal'] + $value['interests'] + $value['late_fee']) - $value['true_total_money'];
                $repayMoney = $repayMoney < 0 ? 0 : intval($repayMoney) / 100;
                array_push($data, [
                    'pay_id' => $value['id'], // UserLoanOrderRepayment中的id
                    'user_id' => $value['user_id'], // 用户id
                    'order_id' => $value['order_id'], // 订单id
                    'total_money_time' => '还款时间',
                    'total_money_name' => '还款金额',
                    'true_total_money' => sprintf('%.2f', $repayMoney),//(string) $repayMoney, // 实际还款总额 ，单位分
                    'plan_repayment_time' => date('Y-m-d', $value['plan_repayment_time']), // 结息日期(计划还款时间)
                ]);
            }
        }
        if ($data) {
            return[
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => $data,
                ],
            ];
        } else {
            return[
                'code' => -1,
                'message' => '暂无借款记录',
                'data' => [
                    'item' => (object) array(),
                ],
            ];
        }
    }

    public function actionRepayGetCoupon(){
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [],
        ];
    }
}
