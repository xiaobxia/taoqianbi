<?php
namespace common\services;

use common\helpers\MailHelper;
use common\helpers\ToolsUtil;
use common\models\asset\AssetOrder;
use common\models\CardInfo;
use common\models\CreditFaceIdCard;
use common\models\CreditJsqb;
use common\models\CreditBqs;
use common\models\CreditBr;
use common\models\CreditCheckHitMap;
use common\models\CreditFacePlus;
use common\models\CreditHd;
use common\models\CreditJsqbBlacklist;
use common\models\CreditJxl;
use common\models\CreditJxlQueue;
use common\models\CreditJy;
use common\models\CreditMg;
use common\models\CreditQueryLog;
use common\models\CreditSauron;
use common\models\CreditTd;
use common\models\CreditYd;
use common\models\CreditYxzc;
use common\models\CreditYys;
use common\models\CreditZmop;
use common\models\CreditZmopLog;
use common\models\CreditZzc;
use common\models\CreditZzcReport;
use common\models\loan\LoanCollectionOrder;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\LoanPersonDegree;
use common\models\OrderAutoPassLog;
use common\models\OrderAutoRejectLog;
use common\models\UserContact;
use common\models\UserCreditData;
use common\models\UserCreditLog;
use common\models\UserCreditReviewLog;
use common\models\UserCreditTotal;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\models\UserOrderLoanCheckLog;
use common\models\UserQuotaPersonInfo;
use common\models\UserVerification;
use common\soa\KoudaiSoa;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use common\helpers\CommonHelper;
use common\base\LogChannel;
use yii\helpers\Console;
use common\models\CreditFacePlusApiLog;
use common\models\UserProofMateria;

class CreditCheckService extends Component
{
    public function saveRejectLog($product_id,$product_order_id,$user_id,$remark){
        $rejectLog = new OrderAutoRejectLog();
        $rejectLog->product_id = $product_id;
        $rejectLog->order_id = $product_order_id;
        $rejectLog->user_id = $user_id;
        $rejectLog->reject_reason =$remark;
        return $rejectLog->save();
    }

    /**
     * 判断是否为老客户
     * @param LoanPerson $loanPerson
     * @param $product_id
     * @param $product_order_id
     * @return array
     * code说明：
     * 1：未过期老用户，可跳过机审；
     * 2：未过期老用户，重新审核；
     * -1：老用户拒绝
     * -2：非老用户
     * -3：过期老用户，重新拉取征信数据
     */
    public function checkRegular(LoanPerson $loanPerson, $product_id, $product_order_id){
        $count = UserLoanOrder::find()
            ->where(['user_id'=>$loanPerson->id])
            ->andWhere(['not in', 'status', [
                UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL,          //还款复审驳回
                UserLoanOrder::STATUS_REPAY_CANCEL,                 //还款初审驳回
                UserLoanOrder::STATUS_PENDING_CANCEL,               //放款驳回
                UserLoanOrder::STATUS_REPEAT_CANCEL,                //复审驳回
                UserLoanOrder::STATUS_CANCEL,                       //初审驳回
                UserLoanOrder::STATUS_REPAY_COMPLETE,               //已还款
                10000,                                              //人工拒绝
                10001,                                              //机审拒绝
            ]])->count("*", Yii::$app->get('db_kdkj_rd'));
        if ($count > 1) {
            return [
                'code' => -2,
                'message' => '机审'
            ];
        }

        $cnt = 0;

        //最近7天内的还款 有大于3次提前4天还款
        $orders = UserLoanOrderRepayment::find()->where(['user_id'=>$loanPerson->id])
            ->andwhere(['status'=>UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])
            ->andwhere(['>','true_repayment_time',strtotime(date('Y-m-d', strtotime('-7 days')))])
            ->all(Yii::$app->get('db_kdkj_rd'));
        if (count($orders) > 0) {
            foreach ($orders as $value) {
                // 提前4天还款
                if ($value['plan_repayment_time'] - $value['true_repayment_time'] > 86400*4) {
                    $cnt ++;
                }
            }
        }
        if ($cnt > 3) {
            return [
                'code' => -1,
                'message' => '拒绝'
            ];
        }

        $time = time();
        $repayment = UserLoanOrderRepayment::find()
            ->where(['user_id'=>$loanPerson->id])
            ->orderBy('id desc')
            ->one(UserLoanOrderRepayment::getDb_rd());
        if ($repayment && $repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) { # 最近有一笔完成的还款
            $overdue_day = $repayment->overdue_day; //逾期天数

            if ($overdue_day > 10) {
                $msg = \sprintf('[%s] overdue_day_more_than_10: %s', date('ymd H:i:s'), $loanPerson->id);
                MailHelper::sendQueueMail($msg, '', NOTICE_MAIL);
                return [
                    'code' => -1,
                    'message' => '拒绝'
                ];
            }

            // 最近一笔成功还款时间的在一个月内，
            if ( ($time - $repayment->updated_at) < 31*86400 ) {
                if ($overdue_day == 0) {
                    return [
                        'code' => 1,
                        'message' => '待放款'
                    ];
                }
                else {
                    // 催收
                    $collection = LoanCollectionOrder::find()
                        ->where([
                            'user_id'=>$loanPerson->id,
                            'status'=>LoanCollectionOrder::STATUS_COLLECTION_FINISH,
                            'user_loan_order_repayment_id'=>$repayment->id,
                        ])
                        ->orderBy('id desc')
                        ->one(LoanCollectionOrder::getDb_rd()); # 催收
                    if (is_null($collection)) {
                        // return [
                        //     'code' => 2,
                        //     'message' => '初审'
                        // ];
                        if ($overdue_day > 10) {
                            $this->saveRejectLog($product_id,$product_order_id,$loanPerson->id,'逾期大于10天');
                            return [
                                'code' => -1,
                                'message' => '拒绝'
                            ];
                        }elseif($overdue_day <= 3){
                            return [
                                'code' => 1,
                                'message' => '待放款'
                            ];
                        }else{
                            return [
                                'code' => 2,
                                'message' => '初审'
                            ];
                        }
                    }
                    //催收建议通过
                    elseif (in_array($collection->next_loan_advice,[LoanCollectionOrder::RENEW_PASS])) {
                        return [
                            'code' => 1,
                            'message' => '待放款'
                        ];
                    }
                    elseif (in_array($collection->next_loan_advice, [
                        LoanCollectionOrder::RENEW_DEFAULT,
                        LoanCollectionOrder::RENEW_CHECK,
                    ])){
                        if ($overdue_day > 10) {
                            $this->saveRejectLog($product_id,$product_order_id,$loanPerson->id,'逾期大于10天');
                            return [
                                'code' => -1,
                                'message' => '拒绝'
                            ];
                        }
                        else if ($overdue_day <= 3) {
                            return [
                                'code' => 1,
                                'message' => '待放款'
                            ];
                        }
                        else {
                            return [
                                'code' => 2,
                                'message' => '初审'
                            ];
                        }
                    }
                    else {
                        $this->saveRejectLog($product_id,$product_order_id,$loanPerson->id,'催收建议拒绝');
                        return [
                            'code' => -1,
                            'message' => '拒绝'
                        ];
                    }
                }
            }
            else {
                Yii::warning(\sprintf("%s 过期老用户重新获取征信数据", $loanPerson->id), LogChannel::CHECK_REGULAR_RESULT);
                return [
                    'code' => -3,
                    'message' => '机审'
                ];
            }
        }
        else {
            return [
                'code' => -2,
                'message' => '机审'
            ];
        }
    }

    /**
     * 极速钱包关注名单
     * @param LoanPerson $loanPerson
     * @return bool
     */
    public function getJsqbWatchList(LoanPerson $loanPerson){
        $soa_client = KoudaiSoa::instance("Loaner");

        $isWhite = $soa_client->isWhitelist($loanPerson->phone,$loanPerson->id_number);
        $isBlack = $soa_client->isBlacklist($loanPerson->phone,$loanPerson->id_number);
        $person_id = $loanPerson->id;

        $cm = CreditJsqb::findLatestOne(['person_id'=>$person_id]);
        if ( empty($cm)) {
            $cm = new CreditJsqb();
        }
        $cm->is_white = $isWhite['data']['is_whitelist'];
        $cm->is_black = $isBlack['data']['is_blacklist'];
        $cm->person_id = $person_id;
        return $cm->save();
    }

    /**
     * 获取极速钱包黑名单
     * @param LoanPerson $loanPerson
     * @return bool
     */
    public function getJsqbBlackList(LoanPerson $loanPerson)
    {
        $record = CreditJsqbBlacklist::findLatestOne(['user_id' => $loanPerson->id]);
        if (!empty($record) && time()  < $record['created_at'] + CreditJsqbBlacklist::VALID_TIME) {
            return true;
        }
        /** @var JsqbService $service */
        $service = Yii::$container->get('jsqbService');
        $token_result = $service->getToken();
        if ($token_result['code'] != 0) {
            return false;
        }
        $result = $service->queryBlacklist($token_result['token'], $loanPerson->name, $loanPerson->id_number, $loanPerson->phone);
        if ($result['code'] != 0) {
            $msg = \sprintf('[%s] %s 钱包黑名单获取失败: %s', date('ymd H:i:s'), $loanPerson->id, $result['message'] ?? '');
            MailHelper::sendQueueMail($msg, '', 'lkw87@163.com');
            return false;
        }

        $blacklist = new CreditJsqbBlacklist();
        $blacklist->user_id = $loanPerson->id;
        $blacklist->is_in = $result['is_in'] ? CreditJsqbBlacklist::IN_BLACKLIST : CreditJsqbBlacklist::NOT_IN_BLACKLIST;

        return $blacklist->save();
    }

    //获取聚信立基本报告
    public function getJxlBaseReport(LoanPerson $loanPerson){
        $v = UserVerification::find()->where(['user_id' => $loanPerson->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($v)){
            throw new Exception('jxl', 3000);
        }
        if ($v->real_jxl_status == UserVerification::VERIFICATION_JXL) {
            $jxl = CreditJxl::findLatestOne(['person_id'=>$loanPerson->id],'db_kdkj_risk_rd');
            if (is_null($jxl) || $jxl->status == CreditJxl::STATUS_FALSE) {
//                $queue = CreditJxlQueue::find()->where(['user_id'=>$loanPerson->id])->one();
//                $queue_type=0;
//                if($queue){
//                    $queue_type=$queue->type;
//                }
//                if ($queue_type == CreditJxlQueue::STATUS_WAIT_PHONE_PWD_RESULT) {
//                    $data = Yii::$app->jxlService->getUserReport($loanPerson, $queue);
//                } else {
//                    $data = Yii::$app->jxlService->getUserBaseReport($loanPerson);
//                }
                //1、保存聚信立运营商报告
                $data = Yii::$app->jxlService->getUserBaseReport($loanPerson);
                DataToBaseService::synJXLToBase($data, $loanPerson->id);

                //2、保存聚信立运营商原始数据
                $data = Yii::$app->jxlService->getUserOperatorsRawData($loanPerson);
                DataToBaseService::synJXLRawToBase($data, $loanPerson->id);

            }
        }

        return true;

    }

    //芝麻信用行业关注名单
    public function getZmopWatch(LoanPerson $loanPerson) {
        $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id' => $loanPerson->id]);
        if (empty($creditZmop)) {
            //TODO 重置芝麻认证状态
            throw new Exception('用户已取消芝麻授权', 3001);
        }

        /* @var $service ZmopService */
        $service = Yii::$container->get('zmopService');
        if(is_null($creditZmop->watch_matched) || empty($creditZmop->watch_matched)){
            if($creditZmop->status == CreditZmop::STATUS_1){
                $service->setAppId($creditZmop->app_id);
                $watch = $service->getWatch($creditZmop->open_id);
                if( ! $watch['success']) {
                    if ($watch['error_code'] == 'ZMCREDIT.authentication_fail') {
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try {
                            $userVerification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                            $userVerification->real_zmxy_status = 0;
                            $ret = $userVerification->save();
                            if (!$ret) {
                                unset($creditZmop);
                                unset($service);
                                throw new Exception('用户步骤验证表保存失败');
                            }
                            $creditZmop->status = CreditZmop::STATUS_2;
                            $ret = $creditZmop->save();
                            if (!$ret) {
                                unset($creditZmop);
                                unset($service);
                                throw new Exception('芝麻信用表保存失败');
                            }
                            $transaction->commit();
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                        }
                        unset($creditZmop);
                        unset($service);
                        throw new Exception('用户已取消芝麻授权',3001);
                        //记录芝麻信用授权取消

                    }else{
                        unset($creditZmop);
                        unset($service);
                        throw new Exception('用户已取消芝麻授权',3001);
                    }

                }
                $watch_matched = $watch['is_matched'];
                if( $watch_matched){
                    $creditZmop->watch_info = json_encode($watch['details']);
                    $creditZmop->watch_matched = 2;
                }else{
                    $creditZmop->watch_matched = 1;
                }
                $creditZmop->save();
            }else{
                unset($creditZmop);
                unset($service);
                throw new Exception('用户已取消芝麻授权',3001);
            }
        }
        unset($creditZmop);
        unset($service);
        return true;

    }
    //芝麻信用芝麻分
    public function getZmopScore(LoanPerson $loanPerson){
        $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$loanPerson->id]);
        if (empty($creditZmop)) {
            unset($creditZmop);
            throw new Exception('用户已取消芝麻授权',3001);
        }
        $service = Yii::$container->get('zmopService');
        if(is_null($creditZmop->zm_score) || empty($creditZmop->zm_score)){
            if($creditZmop->status == CreditZmop::STATUS_1){
                $service->setAppId($creditZmop->app_id);
                $score = $service->getScore($creditZmop->open_id);
                if( ! $score['success']) {
                    if ($score['error_code'] == 'ZMCREDIT.authentication_fail') {
                        $transaction = Yii::$app->db_kdkj->beginTransaction();
                        try {
                            $userVerification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                            $userVerification->real_zmxy_status = 0;
                            $ret = $userVerification->save();
                            if (!$ret) {
                                unset($creditZmop);
                                unset($service);
                                throw new Exception('用户步骤验证表保存失败');
                            }
                            $creditZmop->status = CreditZmop::STATUS_2;
                            $ret = $creditZmop->save();
                            if (!$ret) {
                                unset($creditZmop);
                                unset($service);
                                throw new Exception('芝麻信用表保存失败');
                            }
                            $transaction->commit();
                        } catch (\Exception $e) {
                            $transaction->rollBack();
                        }
                        //记录芝麻信用授权取消
                        throw new Exception('用户已取消芝麻授权',3001);
                    }else{
                        throw new Exception('用户已取消芝麻授权',3001);
                    }

                }
                $creditZmop->zm_score = $score['zm_score'];
                $creditZmop->save();
            }
        }
        unset($creditZmop);
        unset($service);
        return true;
    }
    //获取芝麻IVS信息
    public function getZmopIvs(LoanPerson $loanPerson){
        $service = Yii::$container->get('zmopService');
        $ret = $service->getIvs($loanPerson->phone,$loanPerson->id_number);
        if($ret['success'] != true){
            unset($service);
            unset($ret);
            throw new Exception('芝麻IVS信息获取失败');
        }else{
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try{
                $credit_zmop_log = new CreditZmopLog();
                $credit_zmop_log->person_id = $loanPerson->id;
                $credit_zmop_log->type = CreditZmop::ZM_TYPE_IVS;
                $credit_zmop_log->biz_no = $ret['biz_no'];
                $credit_zmop_log->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
                $credit_zmop_log->price = 0;
                if(!$credit_zmop_log->save()){
                    throw new \Exception("credit_zmop_log保存失败");
                }
                $credit_zmop = CreditZmop::find()->where(['person_id'=>$loanPerson->id])->one();
                if(!$credit_zmop){
                    $credit_zmop =  new CreditZmop();
                    $credit_zmop->person_id = $loanPerson->id;
                }
                $credit_zmop->ivs_info = json_encode($ret['ivs_detail']);
                $credit_zmop->ivs_score = $ret['ivs_score'];
                if(!$credit_zmop->save()){
                    throw new \Exception("credit_zmop保存失败");
                }
                $transaction->commit();
                return true;
            }catch (\Exception $e){
                $transaction->rollback();
                throw $e;
            }
        }
        return true;
    }

    /**
     * 获取蜜罐信息
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getMgData(LoanPerson $loanPerson, $force = false){
        $row = CreditMg::findLatestOne(['person_id'=>$loanPerson->id],'db_kdkj_risk_rd');
        //判断是否已经拉取过数据
        if($row){
            //年月日十分秒
            $update_time_mg=$row->update_time;
            if(empty($update_time_mg)||$update_time_mg==''){
                $update_time_mg=0;
            }else{
                $update_time_mg=strtotime($update_time_mg);
            }
            //时间戳
            $updated_at_mg=$row->updated_at;
            if($updated_at_mg==''||empty($updated_at_mg)){
                $updated_at_mg=0;
            }
            //有效期30天
            $update_time_mg=$update_time_mg+29*24*60*60;
            $updated_at_mg=$updated_at_mg+29*24*60*60;
            if($update_time_mg>time() || $updated_at_mg>time()){
                //近1个月拉取过蜜罐数据
                $force=false;
            }
        }
        if(is_null($row) || empty($row->data) || $force){
            /** @var JxlService $mg_service */
            $service = Yii::$app->jxlService;
            $ret = $service->getBadInfo($loanPerson->name,$loanPerson->id_number,$loanPerson->phone,$loanPerson->id);
            if ($ret['code'] != 0 ) {
                \yii::warning(sprintf('%s mg_data_get_failed: %s', $loanPerson->id, json_encode($ret)), LogChannel::RISK_CONTROL);
                echo sprintf('%s mg_data_get_failed: %s', $loanPerson->id, json_encode($ret));
                unset($row);
                unset($service);
                unset($ret);
                throw new Exception('蜜罐信息获取失败');
            }
        }
        unset($row);
        unset($service);
        unset($ret);
        return true;
    }

    /**
     * 获取葫芦索伦信息
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws \Exception
     */
    public function getSauronData(LoanPerson $loanPerson, $force = false) {
        $row = CreditSauron::findLatestOne(['person_id'=>$loanPerson->id], 'db_kdkj_risk_rd');
        if (is_null($row) || empty($row->data) || $force) {
            /* @var HuluService $service */
            $service = Yii::$container->get('huluService');
            $ret = $service->getBadInfo($loanPerson->name, \strtoupper($loanPerson->id_number), $loanPerson->phone, $loanPerson->id);
            if ($ret['code'] != 0) {
                \yii::error(\sprintf('%s(%s) hulu_Sauron 获取失败', $loanPerson->id, $loanPerson->id_number), LogChannel::CREDIT_HULU);
                unset($row);
                unset($service);
                unset($ret);
                throw new \Exception('葫芦索伦信息获取失败');
            }
        }
        unset($row);
        unset($service);
        unset($ret);
        return true;
    }

    /**
     * 获取百融特殊名单信息
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getBrData(LoanPerson $loanPerson, $force = false){
        $row = CreditBr::findLatestOne(['person_id'=>$loanPerson->id,'type'=>CreditBr::SPECIAL_LIST],'db_kdkj_risk_rd');
        if(is_null($row) || empty($row->data) || $force){
            /* @var BrService $service */
            $service = Yii::$container->get('brService');
            $ret = $service->getBrInfo($loanPerson,CreditBr::SPECIAL_LIST);
            if($ret != true){
                unset($row);
                unset($service);
                unset($ret);
                throw new Exception('百融特殊名单信息获取失败');
            }
        }
        unset($row);
        unset($service);
        unset($ret);
        return true;
    }


    /**
     * 获取百融多次申请核查V2
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getBrApplyData(LoanPerson $loanPerson, $force = false){
        $row = CreditBr::findLatestOne([
            'person_id'=>$loanPerson->id,
            'type'=>CreditBr::APPLY_LOAN_STR,
        ], 'db_kdkj_risk_rd');

        if(is_null($row) || empty($row->data) || $force){
            /* @var BrService $service */
            $service = Yii::$container->get('brService');
            $ret = $service->getBrInfo($loanPerson,CreditBr::APPLY_LOAN_STR);
            if ($ret != true) {
                throw new Exception('百融多次申请核查V2信息获取失败');
            }
        }

        return true;
    }

    /**
     * 获取白骑士决策信息
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getBqsData(LoanPerson $loanPerson, $force = false){
        $product = 0;
        $order_id = 0;
        $row = CreditBqs::findLatestOne(['person_id'=>$loanPerson->id], 'db_kdkj_risk_rd');
        if(is_null($row) || empty($row->data) || $force){
            /* @var bqsService $service */
            $service = Yii::$container->get('bqsService');
            $ret = $service->getLoanPersonDecision($loanPerson,$product,$order_id);
            if($ret != true){
                unset($row);
                unset($service);
                unset($ret);
                throw new Exception('白骑士决策信息获取失败');
            }
        }
        unset($row);
        unset($service);
        unset($ret);
        return true;
    }


    /**
     * 获取同盾信息
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getTdData(LoanPerson $loanPerson, $force = false) {
        $td = CreditTd::findLatestOne(['person_id' => $loanPerson->id], 'db_kdkj_risk_rd');
        if (is_null($td) || empty($td->data) || $force) {
            /* @var $td_service TdService */
            $td_service = Yii::$app->tdService;
            $ret = $td_service->getReportId($loanPerson);
            if ($ret->result) {
                \sleep(1);
                $ret = $td_service->getReportContent($loanPerson);
                if ($ret->result) {
                    return true;
                }
            }

            throw new Exception('同盾获取失败');
        }

        return true;
    }

    /**
     * 获取同盾信息--新接口
     * @param LoanPerson $loanPerson
     * @param bool $force
     * @return bool
     * @throws Exception
     */
    public function getTdDataNew(LoanPerson $loanPerson, $force = false) {
        $td = CreditTd::findLatestOne(['person_id' => $loanPerson->id], 'db_kdkj_risk_rd');
        if (is_null($td) || empty($td->data) || $force) {
            /* @var $td_service TdService */
            $td_service = Yii::$app->tdService;
            $ret = $td_service->bizPreLoan($loanPerson);
            if ($ret) {
                return true;
            }
            throw new Exception('同盾获取失败');
        }

        return true;
    }

    //获取face++数据源
    public function getFacePlus($loanPerson,$product_id,$product_order_id){
        try {
            $order = UserLoanOrder::find()->where(['id'=>$product_order_id])->one(Yii::$app->get('db_kdkj_rd'));
            if (is_null($order)) {
                throw new Exception('订单不存在');
            }

            $face = CreditFacePlus::find()->where(['user_id'=>$loanPerson->id,'status'=>[CreditFacePlus::STATUS_SUCCESS,CreditFacePlus::STATUS_PENDING]])->one(Yii::$app->get('db_kdkj_risk_rd'));
            /* @var $service CreditFacePlusService */
            $service = Yii::$app->creditFacePlusService;
            if (is_null($face)) {
                $service->faceplusplus($loanPerson, CreditFacePlus::STATUS_PENDING);
            }
            else {
                $user_proof_materia = UserProofMateria::find()
                    ->where([
                        'user_id' => $loanPerson->id,
                        'type' => UserProofMateria::TYPE_FACE_RECOGNITION,
                    ])
                    ->orderBy('id desc')
                    ->one();
                $updated_at=0;
                if ($user_proof_materia) {
                    $updated_at = $user_proof_materia->updated_at;
                    if($updated_at=='' || empty($updated_at)){
                        $updated_at = $user_proof_materia->created_at;
                    }
                }

                //判断是否更新过
                $created_at=0;
                $credit_face_plus_api_log=CreditFacePlusApiLog::find()
                    ->where([
                        'user_id' => $loanPerson->id
                    ])
                    ->orderBy('id desc')
                    ->one();
                if($credit_face_plus_api_log){
                    $created_at = $credit_face_plus_api_log->created_at;
                }

                if($updated_at>$created_at){
                    //人脸识别的照片更新过，需要重新拉face++数据
                    $service->faceplusplus($loanPerson, CreditFacePlus::STATUS_SUCCESS);
                }
            }

        }
        catch (\Exception $e) {
            Yii::error([
                'type' => '机审脚本错误',
                'uid' => $loanPerson->id,
                'order_id' => $product_order_id,
                'message'=>$e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], LogChannel::RISK_CONTROL );
        }

        return true;
    }

    //获取宜信数据
    public function getYxData($loanPerson){
        try {
            $service = new Yxservice();
            $service->getData($loanPerson);
        }
        catch (\Exception $e) {
            Yii::error([
                'type' => '机审脚本错误',
                'uid' => $loanPerson->id,
                'message'=>$e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], LogChannel::RISK_CONTROL );
        }
    }


    /**
     * 获取征信数据
     * @param LoanPerson $loanPerson
     * @param $product_id
     * @param $product_order_id
     * @param bool $force //是否重新获取数据
     * @return bool
     * @throws \Exception
     */
    public function getAllDataSource(LoanPerson $loanPerson, $product_id, $product_order_id, $force = false) {

        $rs = UserMobileContacts::getCheckContactResult($loanPerson->id); //判断通讯录是否获取
        if (empty($rs)) {
            throw new \Exception("{$loanPerson->id} 手机通讯录获取不到", 3002);
        }

        $jxl = CreditJxl::findLatestOne(['person_id'=>$loanPerson->id], 'db_kdkj_risk_rd');
        if (empty($jxl)) {
            throw new \Exception("{$loanPerson->id} 无聚信立信息", 3000);
        }

        try {
            //获取聚信立报告
            $jxl_ret = $this->getJxlBaseReport($loanPerson);
            \call_user_func(
                [CommonHelper::class, ($jxl_ret ? 'stdout' : 'stderr')],
                "jxl_ret: {$loanPerson->id} " . ($jxl_ret ? 'success' :  'failed') . PHP_EOL
            );
        }
        catch (\Exception $e) {
            throw new \Exception("{$loanPerson->id} JxlBaseReport获取异常: {$e}", 3000);
        }

        //face++检测
        $face_data = $this->getFacePlus($loanPerson, $product_id, $product_order_id);
        \call_user_func(
            [CommonHelper::class, ($face_data ? 'stdout' : 'stderr')],
            "face_dat: {$loanPerson->id} " . ($face_data ? 'success' :  'failed') . PHP_EOL
        );


        //蜜罐
        $mg_ret = $this->getMgData($loanPerson, $force);
        \call_user_func(
            [CommonHelper::class, ($mg_ret ? 'stdout' : 'stderr')],
            "mg_ret: {$loanPerson->id} " . ($mg_ret ? 'success' :  'failed') . PHP_EOL
        );

        //同盾
        $td_data = $this->getTdDataNew($loanPerson, $force);
        \call_user_func(
            [CommonHelper::class, ($td_data ? 'stdout' : 'stderr')],
            "td_ret: {$loanPerson->id} " . ($td_data ? 'success' :  'failed') . PHP_EOL
        );


        //百融-特殊名单
        $br_dat = $this->getBrData($loanPerson, $force);
        \call_user_func(
            [CommonHelper::class, ($br_dat ? 'stdout' : 'stderr')],
            "br_dat: {$loanPerson->id} " . ($br_dat ? 'success' :  'failed') . PHP_EOL
        );

        //百融-多次申请核查v2
        $br_apply = $this->getBrApplyData($loanPerson, false);
        \call_user_func(
            [CommonHelper::class, ($br_apply ? 'info' : 'error')],
            "br_apply: {$loanPerson->id} " . ($br_apply ? 'success' :  'failed') . PHP_EOL
        );

        //白骑士-决策信息
//        $bqs_dat = $this->getBqsData($loanPerson, $force);
//        \call_user_func(
//            [CommonHelper::class, ($bqs_dat ? 'stdout' : 'stderr')],
//            "bqs_dat: {$loanPerson->id} " . ($bqs_dat ? 'success' :  'failed') . PHP_EOL
//        );
        //宜信数据
//        $yx_dat = $this->getYxData($loanPerson);
//        \call_user_func(
//            [CommonHelper::class, ($yx_dat ? 'stdout' : 'stderr')],
//            "yx_dat: {$loanPerson->id} " . ($yx_dat ? 'success' :  'failed') . PHP_EOL
//        );

        return true;
    }

}
