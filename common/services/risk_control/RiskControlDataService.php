<?php
namespace common\services\risk_control;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\helpers\ArrayHelper;
use common\helpers\ToolsUtil;
use common\helpers\Util;
use common\models\AccumulationFund;
use common\models\CardInfo;
use common\models\CreditBqs;
use common\models\CreditBr;
use common\models\CreditFaceIdCard;
use common\models\CreditJsqb;
use common\models\CreditJsqbBlacklist;
use common\models\CreditJxl;
use common\models\CreditMg;
use common\models\CreditQueryLog;
use common\models\CreditSauron;
use common\models\CreditTd;
use common\models\CreditYx;
use common\models\CreditYxzc;
use common\models\CreditZmop;
use common\models\CreditZzc;
use common\models\loan\LoanCollectionOrder;
use common\models\LoanPerson;
use common\models\mongo\mobileInfo\PhoneOperatorDataMongo;
use common\models\mongo\risk\RiskControlDataSnapshot;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\UserChannelMap;
use common\models\UserContact;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\models\UserProofMateria;
use common\models\UserQuotaPersonInfo;
use common\models\UserVerification;
use common\services\DataToBaseService;

/**

 *

 *
 * 机审数据预处理
 *
 */
class RiskControlDataService extends Component
{

    public function logTime($method, $loan_person, $order = null)
    {
        $now = time();
        if ($order) {
            $data = $this->$method($loan_person, $order);
        } else {
            $data = $this->$method($loan_person);
        }
        /*if(time()-$now > 0){
            echo $method . " cost " . (time() - $now) . " s\n";
        }*/
        return $data;

    }

    //机审数据预处理
    public function getData(LoanPerson $loan_person, $order = null, $snapshot = false) {
        if ($snapshot) {
            if (empty($order)) {
                return [];
            }

            return RiskControlDataSnapshot::findByOrderId($order->id);
        }

        $data = [
            'order' => $order,
            'loan_person' => $loan_person,

            'jxl' => $this->logTime('getJxlData', $loan_person),        // 聚信立
            'yys' => $this->logTime('getYysData', $loan_person),        // 运营商
            'td' => $this->logTime('getTdData', $loan_person),          // 同盾
//            'zm' => $this->logTime('getZmData', $loan_person),          // 芝麻
            'mg' => $this->logTime('getMgData', $loan_person),
//            'sauron' => $this->logTime('getSauronData', $loan_person),
//            'jsqb' => $this->logTime('getJsqbData', $loan_person),
//            'jsqb_blacklist' => $this->logTime('getJsqbBlacklist', $loan_person),
            'br' => $this->logTime('getBrData', $loan_person),
            'bqs' => $this->logTime('getBqsData', $loan_person),
//            'yx' => $this->logTime('getYxData', $loan_person),
//            'yx_af' => $this->logTime('getYxAfData', $loan_person),//宜信阿福数据
//            'zzc' => $this->logTime('getZzcData', $loan_person),
//
            'card_infos' => $this->logTime('getCardInfos', $loan_person),
            'user_detail' => $this->logTime('getUserDetail', $loan_person),
            'user_contact' => $this->logTime('getUserContact', $loan_person),
            'user_loan_orders' => $this->logTime('getUserLoanOrders', $loan_person),
            'user_credit_total' => $this->logTime('getUserCreditTotal', $loan_person, $order),
            'user_proof_materia' => $this->logTime('getUserProofMateria', $loan_person),
            'user_mobile_contacts' => $this->logTime('getUserMobileContacts', $loan_person),
            'user_login_upload_log' => $this->logTime('getUserLoginUploadLog', $loan_person),
            'user_login_upload_logs' => $this->logTime('getUserLoginUploadLogs', $loan_person),
            'user_quota_person_info' => $this->logTime('getUserQuotaPersonInfo', $loan_person),
            'usable_user_loan_orders' => $this->logTime('getUsableUserLoanOrders', $loan_person),
            'loan_collection_order' => $this->logTime('getLoanCollectionOrder', $loan_person),
            'user_loan_order_repayments' => $this->logTime('getUserLoanOrderRepayments', $loan_person),
            // TODO:alexding disable jizhang
            //'external_account' => $this->logTime('getExternalAccount', $loan_person),
//            'external_account' => '',
            'accumulation_fund' => $this->logTime('getAccumulationFund', $loan_person),
            'face_id_card' => $this->logTime('getFaceIdCard', $loan_person),



            'jxl_phone_shot' => $this->logTime('getJxlNum',$loan_person),//匹配异常号码的数量
            'jxl_phone_match' => $this->logTime('getPhoneMatchJxl',$loan_person),//详单前10和通讯录的匹配数量
            'jxl_phone_match_all' => $this->logTime('getALLJxlPhone',$loan_person),//详单与通讯录的匹配个数
            'jxl_phone_ten' => $this->logTime('getALLJxlPhoneTime',$loan_person),//详单和通话10分钟
            'jxl_phone_wten' => $this->logTime('getALLJxlPhoneTimeS',$loan_person),//详单和通话30分钟
            'all_jxl_phone_match_error' => $this->logTime('getALLJxlPhoneBack',$loan_person),//匹配异常号码的数量
            'all_jxl_phone_error' => $this->logTime('getALLJxlPhoneName',$loan_person),//匹配异常号码的数量
        ];
        if (empty($order)) {
            return $data;
        }

        $result = RiskControlDataSnapshot::saveSnapShot($data);
        if (!$result) {
            throw new \Exception('RiskControlDataSnapshot::saveSnapShot failed');
        }

        return $data;
    }

    //机审数据预处理（回测）
    public function getTestData(LoanPerson $loan_person, $order) {
        $data = RiskControlDataSnapshot::findForBackTest($order->id);

        if (empty($data)) {
            $data = [
                'order' => $order,
                'loan_person' => $loan_person,

                'jxl' => $this->logTime('getJxlData', $loan_person),
                'yys' => $this->logTime('getYysData', $loan_person),
                'td' => $this->logTime('getTdData', $loan_person),
//                'zm' => $this->logTime('getZmData', $loan_person),
                'mg' => $this->logTime('getMgData', $loan_person),
//                'sauron' => $this->logTime('getSauronData', $loan_person),
//                'jsqb' => $this->logTime('getJsqbData', $loan_person),
                'br' => $this->logTime('getBrData', $loan_person),
                'bqs' => $this->logTime('getBqsData', $loan_person),
//                'yx' => $this->logTime('getYxData', $loan_person),
//                'zzc' => $this->logTime('getZzcData', $loan_person),

                'card_infos' => $this->logTime('getCardInfos', $loan_person),
                'user_detail' => $this->logTime('getUserDetail', $loan_person),
                'user_contact' => $this->logTime('getUserContact', $loan_person),
                'user_loan_orders' => $this->logTime('getUserLoanOrders', $loan_person),
                'user_credit_total' => $this->logTime('getUserCreditTotal', $loan_person, $order),
                'user_proof_materia' => $this->logTime('getUserProofMateria', $loan_person),
                'user_mobile_contacts' => $this->logTime('getUserMobileContacts', $loan_person),
                'user_login_upload_log' => $this->logTime('getUserLoginUploadLog', $loan_person),
                'user_login_upload_logs' => $this->logTime('getUserLoginUploadLogs', $loan_person),
                'user_quota_person_info' => $this->logTime('getUserQuotaPersonInfo', $loan_person),
                'usable_user_loan_orders' => $this->logTime('getUsableUserLoanOrders', $loan_person),
                'loan_collection_order' => $this->logTime('getLoanCollectionOrder', $loan_person),
                'user_loan_order_repayments' => $this->logTime('getUserLoanOrderRepayments', $loan_person),

                'external_account' => $this->logTime('getExternalAccount', $loan_person),
                'accumulation_fund' => $this->logTime('getAccumulationFund', $loan_person),
                'face_id_card' => $this->logTime('getFaceIdCard', $loan_person),

                'jxl_phone_shot' => $this->logTime('getJxlNum',$loan_person),//匹配异常号码的数量
                'jxl_phone_match' => $this->logTime('getPhoneMatchJxl',$loan_person),//详单前10和通讯录的匹配数量
                'jxl_phone_match_all' => $this->logTime('getALLJxlPhone',$loan_person),//详单与通讯录的匹配个数
                'jxl_phone_ten' => $this->logTime('getALLJxlPhoneTime',$loan_person),//详单和通话10分钟
                'jxl_phone_wten' => $this->logTime('getALLJxlPhoneTimeS',$loan_person),//详单和通话30分钟
                'all_jxl_phone_match' => $this->logTime('getALLJxlPhoneBack',$loan_person),//匹配异常号码的数量
                'all_jxl_phone' => $this->logTime('getALLJxlPhoneName',$loan_person),//匹配异常号码的数量

            ];
        }

        return $data;
    }

    //机审数据预处理
    public function getData1(LoanPerson $loan_person, $order = null, $snapshot = false) {

        if ($snapshot) {
            if (empty($order)) {
                return [];
            }
            $order_id = $order->id;
            $data = RiskControlDataSnapshot::findByOrderId($order_id);
            return $data;
        } else {


            $data = [
                'order' => $order,
                'loan_person' => $loan_person,
            ];

            if (empty($order)) {
                return $data;
            }

            $result = RiskControlDataSnapshot::saveSnapShot($data);

            if ($result) {
                return $data;
            } else {
                throw new Exception("Error Save Data");
            }
        }

    }

    public function getJxlData(LoanPerson $loan_person){
        $v = UserVerification::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($v)) {
            return [];
        }
        if ($v->real_jxl_status == UserVerification::VERIFICATION_JXL) {
            $jxl = CreditJxl::findLatestOne(['person_id' => $loan_person->id], 'db_kdkj_risk_rd');
            if (is_null($jxl)) {
                return [];
            }
            if ($jxl->status == 1) {
                return json_decode($jxl->data, true);
            }
        }
//        elseif ($v->real_yys_status == UserVerification::VERIFICATION_YYS) {
//            $hljr = CreditYys::find()->where(['person_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_risk_rd'));
//            if (is_null($hljr)) {
//                return [];
//            }
//            if ($hljr->status == 1) {
//                return json_decode($hljr->data, true);
//            }
//        }

        return [];
    }

    public function getYysData(LoanPerson $loanPerson) {
        $model = PhoneOperatorDataMongo::find()->where(['_id' => $loanPerson->id])->one();// 更换了数据源修改方法

        if (empty($model)) {

            $data = $this->getJxlData($loanPerson);
            if (empty($data)) {
                //
            } else {
                DataToBaseService::synJXLToBase($data, $loanPerson->id);
            }

            $model = PhoneOperatorDataMongo::find()->where(['_id' => $loanPerson->id])->one();
            if (!empty($model)) {
                return $model;
            }
            return [];
        }

        return $model;
    }

    public function getTdData(LoanPerson $loan_person){

        $model = CreditTd::findLatestOne(['person_id' => $loan_person['id']], 'db_kdkj_risk_rd');
        if (empty($model)) {
            return [];
        }
        return json_decode($model->data, true);
    }

    public function getZmData(LoanPerson $loanPerson){

        $model = CreditZmop::gainCreditZmopLatest(['person_id' => $loanPerson['id']], 'db_kdkj_rd');

        if (empty($model)) {
            return [];
        }

        return $model;
    }

    public function getMgData(LoanPerson $loanPerson){

        $model = CreditMg::findLatestOne(['person_id' => $loanPerson['id']], 'db_kdkj_risk_rd');

        if (empty($model)) {
            return [];
        }
        return json_decode($model->data, true);
    }

    public function getSauronData(LoanPerson $loanPerson){

        $row = CreditSauron::findLatestOne(['person_id' => $loanPerson->id], 'db_kdkj_risk_rd');

        if (empty($row)) {
            return [];
        }

        return json_decode($row->data, true);
    }

    public function getJsqbData(LoanPerson $loanPerson){
        $row = CreditJsqb::findLatestOne(['person_id' => $loanPerson->id], 'db_kdkj_risk_rd');
        if (!$row)
            return [];
        return [
            'is_blacklist' => $row->is_black,
            'is_whitelist' => $row->is_white
        ];
    }

    public function getJsqbBlacklist(LoanPerson $loanPerson){
        $row = CreditJsqbBlacklist::findLatestOne(['user_id' => $loanPerson->id], 'db_kdkj_risk_rd');
        if (!$row)
            return [];
        return [
            'is_in' => $row->is_in,
        ];
    }

    public function getBrData(LoanPerson $loanPerson){
        $list = CreditBr::find()->where(['person_id'=>$loanPerson->id])->asArray()->all();
        if(empty($list)) {
            return [];
        }
        return $list;
    }

    public function getBqsData(LoanPerson $loanPerson){
        $row = CreditBqs::findLatestOne(['person_id' => $loanPerson->id], 'db_kdkj_risk_rd');
        if (empty($row)) {
            return [];
        }
        return json_decode($row->data, true);
    }

    public function getYxData(LoanPerson $loanPerson){

        $model = CreditYx::findLatestOne(['user_id' => $loanPerson->id,'type'=>1]);

        if (empty($model)) {
            return [];
        }

        return json_decode($model->data, true);
    }
    public function getYxAfData(LoanPerson $loanPerson){

        $model = CreditYx::findLatestOne(['user_id' => $loanPerson->id,'type'=>2]);

        if (empty($model)) {
            return [];
        }

        return json_decode($model->data, true);
    }

    public function getZzcData(LoanPerson $loanPerson){

        $log = CreditQueryLog::findLatestOne(['credit_type' => CreditZzc::TYPE_BLACKLIST, 'credit_id' => CreditQueryLog::Credit_ZZC, 'person_id' => $loanPerson->id], 'db_kdkj_risk_rd');

        if (empty($log)) {
            return [];
        }

        return json_decode($log->data, true);
    }


    public function getUserContact(LoanPerson $loan_person){

        $info = UserContact::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }


    public function getUserQuotaPersonInfo(LoanPerson $loan_person){

        $info = UserQuotaPersonInfo::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getCardInfos(LoanPerson $loan_person){

        $info = CardInfo::find()->where(['user_id' => $loan_person->id])->all(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserLoginUploadLog(LoanPerson $loan_person){

        $info = UserLoginUploadLog::find()->where(['user_id' => $loan_person->id])->orderBy('id desc')->limit(1)->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserLoginUploadLogs(LoanPerson $loan_person){

        $info = UserLoginUploadLog::find()->where(['user_id' => $loan_person->id])->all(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserDetail(LoanPerson $loan_person){

        $info = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserMobileContacts(LoanPerson $loan_person){

        $info = UserMobileContacts::getContactData($loan_person->id);
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserLoanOrderRepayments(LoanPerson $loan_person){

        $info = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person->id])->orderBy('created_at desc')->all(Yii::$app->get('db_kdkj_rd'));
        if (empty($info)) {
            return [];
        }

        return $info;
    }


    public function getUserCreditTotal(LoanPerson $loan_person, $order = null){
        $creditChannelService = \Yii::$app->creditChannelService;
        if (empty($order)) {
            $info = $creditChannelService->getCreditTotalByUserId($loan_person->id);
        } else {
            $info = $creditChannelService->getCreditTotalByUserAndOrder($loan_person->id, $order->id);
        }

        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserProofMateria(LoanPerson $loan_person){

        $info = UserProofMateria::find()->where(['user_id' => $loan_person->id, 'status' => UserProofMateria::STATUS_NORMAL])
            ->andWhere(['in', 'type', [
                UserProofMateria::TYPE_ID_CAR,
                UserProofMateria::TYPE_ID_CAR_Z,
                UserProofMateria::TYPE_ID_CAR_F,
                UserProofMateria::TYPE_FACE_RECOGNITION,]])->all();
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUsableUserLoanOrders(LoanPerson $loan_person){

        //汇邦钱包、随心贷只查看自身渠道订单数
        if (in_array($loan_person->source_id, [
            LoanPerson::PERSON_SOURCE_HBJB,
            LoanPerson::PERSON_SOURCE_SX_LOAN
        ])) {
            $condition = ['user_id' => $loan_person->id];
        } else {
            $loan_persons = LoanPerson::find()->where(['id_number' => $loan_person->id_number])->asArray()->all();
            $user_ids = ArrayHelper::getColumn($loan_persons, 'id');
            $condition = ['in', 'user_id', $user_ids];
        }

        $info = UserLoanOrder::find()->where($condition)
            ->andWhere(['not in', 'status', [
                UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL,
                UserLoanOrder::STATUS_REPAY_CANCEL,
                UserLoanOrder::STATUS_PENDING_CANCEL,
                UserLoanOrder::STATUS_REPEAT_CANCEL,
                UserLoanOrder::STATUS_CANCEL,
                UserLoanOrder::STATUS_REPAY_COMPLETE,
                10000,
                10001]])->all();
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getUserLoanOrders(LoanPerson $loan_person){

        $info = UserLoanOrder::find()->where(['user_id' => $loan_person->id])->all();
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    public function getLoanCollectionOrder(LoanPerson $loan_person){

        $info = LoanCollectionOrder::find()->where(['user_id' => $loan_person->id, 'status' => LoanCollectionOrder::STATUS_COLLECTION_FINISH])->orderBy('id desc')->limit(1)->one();
        if (empty($info)) {
            return [];
        }

        return $info;
    }

    /**
     * 获取第三方记账数据
     * @param LoanPerson $loanPerson
     * @return array
     */
//    public function getExternalAccount(LoanPerson $loanPerson) {
//        $ret = [
//            'code' => -1,
//            'data' => []
//        ];
//        if (UserChannelMap::findOne(['user_id' => $loanPerson->getId(), 'channel' => ExternalChannelService::CHANNEL_JIZHANG])) {
//            $info = UserExternalAccount::findOne(['user_id' => $loanPerson->id]);
//            $ret = $info ? ['code' => 1, 'data' => $info->toArray()] : ['code' => 0, 'data' => []];
//        }
//
//        return $ret;
//    }

    /**
     * 获取公积金数据
     * @param LoanPerson $loanPerson
     * @return array
     */
    public function getAccumulationFund(LoanPerson $loanPerson) {
        $ret = [];
        $record = AccumulationFund::findOne(['user_id' => $loanPerson->id, 'status' => AccumulationFund::STATUS_SUCCESS]);
        if ($record && AccumulationFund::validateAccumulationStatus($loanPerson)) {
            $data = json_decode($record->data, true);
            $data_source = $data['data_source'] ?? '';
            $ret = [
                'pay_months' => $record->pay_months,
                'avarage_amt' => $record->average_amt / 100,
                'data_source' => $data_source,
            ];
        }

        return $ret;
    }


    /**
     * 获取face++身份证识别信息
     * @param LoanPerson $loanPerson
     * @return array
     */
    public function getFaceIdCard(LoanPerson $loanPerson)
    {
        $ret = [];
        if ($record = CreditFaceIdCard::find()->where(['user_id' => $loanPerson->id])->orderBy('id desc')->one()) {
            $ret = json_decode($record->data, true);
        }

        return $ret;
    }

    /**
     * 获取秒还卡等记录
     * @param  LoanPerson $loanPerson
     * @return array
     */
    public function getMhkOrder(LoanPerson $loanPerson)
    {
        $user_ids = LoanPerson::find()->select(['id'])->where(['id_number' => $loanPerson->id_number])->column(LoanPerson::getDbMhk());
        $loan = UserLoanOrder::checkHasUnFinishedOrderMhk($user_ids);
        $repay = UserLoanOrderRepayment::find()
                    ->where(['in', 'user_id', $user_ids])
                    ->andWhere(['is_overdue' => UserLoanOrderRepayment::OVERDUE_YES])
                    ->andWhere(['>' , 'overdue_day', 10])
                    ->one(UserLoanOrderRepayment::getDbMhk());

        $collection = LoanCollectionOrder::find()
                        ->where(['in', 'user_id', $user_ids])
                        ->andWhere(['next_loan_advice' => LoanCollectionOrder::RENEW_REJECT])
                        ->one(LoanCollectionOrder::getDbMhk());

        $ret = [
            'loan' => $loan,
            'repay' => $repay,
            'collection' => $collection,
        ];
        return $ret;
    }

    /**
     * 详单前几个为短号的个数
     * @param $num 要比较的个数
     */
    public function getJxlNum(LoanPerson $loanPerson){
        $num = 10;
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
        $count = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)){
                $sort = [];
                foreach($res['contact_list'] as $item){
                    $sort[$item['call_cnt']][] = $item;
                }
                krsort($sort);
                $contact_list = [];
                foreach($sort as $v){
                    foreach ($v as $j){
                        $contact_list[] = $j;
                    }
                }
                $dat_res = $contact_list;
                foreach ($dat_res as $k=>$v){
                    if(strlen($v['phone_num']) <7){//如果前10个号码
                        $count++;
                    }
                    if($k>$num-1){
                        break;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 详单前10和通讯录的匹配数量
     */
    public function getPhoneMatchJxl(LoanPerson $loanPerson){
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);//JXL详单数据
        $count = 0;
        $num = 10;
        $num_res = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)){
                $sort = [];
                foreach($res['contact_list'] as $item){
                    $sort[$item['call_cnt']][] = $item;
                }
                krsort($sort);
                $contact_list = [];
                foreach($sort as $v){
                    foreach ($v as $j){
                        $contact_list[] = $j;
                    }
                }

                $dat_res = $contact_list;
                foreach ($dat_res as $k1=>$v1){
                    if($count>$num-1){
                        break;
                    }
                    $phone_res = Util::getLenPhone(11,$v1['phone_num']);
                    if(strlen(trim($v1['phone_num'])) >7 and
                        UserMobileContacts::getUserPhoneContactData($loanPerson,$phone_res)
                    ){//如果前10个号码
                            $num_res++;
                    }
                    $count++;
                }
            }

        }
        return $num_res;
    }

    /**
     * 详单和通讯匹配个数
     */
    public function getALLJxlPhone(LoanPerson $loanPerson){
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);//JXL详单数据
        $count = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)) {
                foreach ($res['contact_list'] as $k => $v) {
                    $phone_res = Util::getLenPhone(11,$v['phone_num']);
                    if (strlen($v['phone_num']) > 7) {
                        if (UserMobileContacts::getUserPhoneContactData($loanPerson,$phone_res)) {
                            $count++;
                        }
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 详单和通讯匹配个数 10分钟
     */
    public function getALLJxlPhoneTime(LoanPerson $loanPerson){
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);//JXL详单数据
        $count = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)) {
                foreach ($res['contact_list'] as $k=>$v){
                    if(strlen($v['phone_num']) >7 and intval($v['call_len'])>10){
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 详单和通讯匹配个数  30分钟
     */
    public function getALLJxlPhoneTimeS(LoanPerson $loanPerson){
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);//JXL详单数据
        $count = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)) {
                foreach ($res['contact_list'] as $k=>$v){
                    if(strlen($v['phone_num']) >7 and intval($v['call_len'])>20){
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 详单和通讯匹配个数
     */
    public function getALLJxlPhoneBack(LoanPerson $loanPerson){
        $jxl_res =  CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);//JXL详单数据
        $count = 0;
        if(!empty($jxl_res)){
            $res = json_decode($jxl_res->data,true);
            $count = 0;
            if(!empty($res)) {
                foreach ($res['contact_list'] as $k=>$v){
                    $phone_res = Util::getLenPhone(11,$v['phone_num']);
                    if(strlen($v['phone_num']) >7 and in_array($phone_res,LoanPerson::$phone_list)){
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * 匹配异常号码的数量
     */
    public function getALLJxlPhoneName(LoanPerson $loanPerson){
        $jxl_res =  UserMobileContacts::getContactData($loanPerson->id);//JXL详单数据
        $count = 0;
        if(!empty($jxl_res)){
            foreach ($jxl_res as $k=>$v){
                if(strlen($v['mobile']) >7 ){
                    foreach (LoanPerson::$name_arr_list as $v2){
                        if(strstr($v['name'],$v2)){
                            $count++;
                        }
                    }
                }
            }
        }
        return $count;
    }

}
