<?php
namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\helpers\VarDumper;
use common\models\credit_line\CreditLine;
use common\models\CreditJsqb;
use common\helpers\ToolsUtil;
use common\models\CardInfo;
use common\models\CreditCheckHitMap;
use common\models\CreditFacePlus;
use common\models\CreditJxl;
//use common\models\InstallmentShop;
//use common\models\InstallmentShopOrder;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\LoanPersonBadInfoLog;
use common\models\mongo\risk\RuleReportMongo;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\mongo\statistics\UserPhoneMessageMongo;
use common\models\OrderAutoRejectLog;
use common\models\PhoneReviewLog;
use common\models\UserContact;
use common\models\UserCreditData;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\models\UserOrderLoanCheckLog;
use common\models\UserPhoneMessage;
use common\models\UserProofMateria;
use common\models\UserQuotaPersonInfo;
use common\models\UserQuotaWorkInfo;
use common\models\UserRealnameVerify;
use common\services\risk_control\RiskControlDataService;
use common\models\mongo\risk\OrderReportMongo;

/**
 * 个人信息、行为信息、征信信息service
 */
class LoanPersonInfoService extends Component
{
    /**
     * 获得零钱贷信息
     * @return string
     */
    public function getPocketInfo($id)
    {
        $info = UserLoanOrder::find()->where(['id' => $id])->one();
        if(empty($info) && !isset($info)) {
            throw new NotFoundHttpException('订单不存在');
        }
        $loanPerson = LoanPerson::findOne($info['user_id']);
        if(is_null($loanPerson)){
            throw new NotFoundHttpException('用户不存在');
        }
        $info->populateRelation('loanPerson', $loanPerson);
        $id_number_address = "";
        $id_number = $loanPerson->id_number;
        if(!empty($id_number)&&ToolsUtil::checkIdNumber($id_number)){
            $id_number_address = ToolsUtil::get_addr($id_number);
        }
        $proof_image = UserProofMateria::findAllByType($info['user_id']);
        // 身份证照片数量：
        $type = [UserProofMateria::TYPE_ID_CAR,UserProofMateria::TYPE_BUSINESS_CARD,UserProofMateria::TYPE_FACE_RECOGNITION,UserProofMateria::TYPE_ID_CAR_Z,UserProofMateria::TYPE_ID_CAR_F];
        $idcard_count = count(UserProofMateria::find()->where(['user_id'=>$info['user_id']])->andWhere(['type'=>$type])->all(Yii::$app->get('db_kdkj_rd')));
        //$credit = UserCreditTotal::find()->where(['user_id' => $info['user_id']])->one();
        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($info['user_id'], $id);
        $credit_line = CreditLine::findLatestOne([
            'user_id' => $info['user_id'],
            'status' => CreditLine::STATUS_ACTIVE,
        ]);
        //  财产证明：
        $proof_pic = UserProofMateria::find()->where(['user_id'=>$info['user_id'],'type'=>UserProofMateria::TYPE_PROOF_of_ASSETS])->all(Yii::$app->get('db_kdkj_rd'));
        $proof_pic_count = count($proof_pic);
        // 个人名片：
        $business = UserProofMateria::find()->where(['user_id'=>$info['user_id'],'type'=>UserProofMateria::TYPE_BUSINESS_CARD])->all(Yii::$app->get('db_kdkj_rd'));
        $business_count = count($business);
        // 工作证明:
        $work_proof = UserProofMateria::find()->where(['user_id'=>$info['user_id'],'type'=>UserProofMateria::TYPE_WORK_CARD])->all(Yii::$app->get('db_kdkj_rd'));
        $work_count = count($work_proof);
        //  信用分 欺诈分 禁止项
        $credit_score = RuleReportMongo::getNewReportValue($info['user_id'],'166');
        $fake_score = RuleReportMongo::getNewReportValue($info['user_id'],'164');
        $jinzhi = RuleReportMongo::getNewReportValue($info['user_id'],'165');
        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id' => $id])->orderBy(['id' => SORT_ASC])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $person_detail = UserRealnameVerify::find()->where(['user_id' => $info['user_id']])->one();
        $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $info['user_id']])->one();
        $work = UserQuotaWorkInfo::findOne(['user_id'=>$info['user_id']]);
        $contact = UserContact::find()->where(['user_id' => $info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $bank = CardInfo::find()->where(['user_id' => $info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $equipment = UserDetail::find()->where(['user_id' => $info['user_id']])->one();
        $badInfoLog = LoanPersonBadInfoLog::find()->where(['person_id'=>$info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $all_loan_orders = UserLoanOrder::find()->from(UserLoanOrder::tableName()."as a")
            ->where(['a.user_id'=>$info['user_id']])->andWhere(['<>','a.id',$info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $phone_log = PhoneReviewLog::find()->where(['user_id' => $info['user_id']])->andWhere(['order_id' => $info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $past_phone_log = PhoneReviewLog::find()->where(['user_id'=>$info['user_id']])->andWhere(['<>','order_id',$info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        //var_dump($past_phone_log);die;
        $hit_map = null;
        if($info->auto_risk_check_status == 1 && $info->is_hit_risk_rule == 1){
            $hit_map = CreditCheckHitMap::find()->where(['product_id'=>CreditCheckHitMap::PRODUCT_YGD,'product_order_id'=>$info->id,'status'=>1])->with('creditQueryLog')->all(Yii::$app->get('db_kdkj_rd'));
        }
        //  手机通讯录数目
//        $moblie_count = UserMobileContactsMongo::find()->where(['user_id'=>$info['user_id']])->count();
//        if ($moblie_count <= 0) {
//            $moblie_count = UserMobileContacts::getContactData($info['user_id']);
//        }
//        /*** mongo迁移 ***/
//        if ($moblie_count <= 0) {
//            $moblie_count = UserMobileContactsMongo::find()->where(['user_id'=>$info['user_id']])->count('*', Yii::$app->get('mongodb_rule'));
//        }
//        // 短信数目
//        $message_count = count(UserPhoneMessageMongo::find()->where(['user_id'=>$info['user_id']])->all());
//        if ($message_count <= 0) {
//            $message_count = count(UserPhoneMessage::find()->where(['user_id' => $info['user_id']])->all(Yii::$app->get('db_kdkj_rd')));
//        }
        // 聚兴立通讯录数目
        $loanPerson = LoanPerson::findOne($info['user_id']);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
//        $creditJxl = CreditJxl::findLatestOne(['person_id'=>$info['user_id']]);
//        $jxl_count = "";
//        if(!is_null($creditJxl) && !empty($creditJxl['data'])) {
//            $data = json_decode($creditJxl['data'], true);
//            $jxl_count = count($data['contact_list']);
//        }
        //历史审核日志
        if(!empty($all_loan_orders)){
            $order_ids = [];
            foreach($all_loan_orders as $order){
                $order_ids[] = $order['id'];
            }
            $past_trail_log = UserOrderLoanCheckLog::find()->where(['order_id' => $order_ids])->orderBy(['id' => SORT_ASC])->all(Yii::$app->get('db_kdkj_rd'));
        }else{
            $past_trail_log = null;
        }

        if (!empty($trail_log)) {
            $order_report = OrderReportMongo::find()->where(['order_id' => intval($id)])->asArray()->all();
            foreach ($trail_log as $key => $item) {
                if (UserLoanOrder::STATUS_CANCEL == $item['after_status'] && $item['type'] == UserOrderLoanCheckLog::TYPE_LOAN) {
                    foreach ($order_report as $report) {
                        if ($report['root_ids'] == 390) {
                            $trail_log[$key]['reject_roots'] = $report['reject_roots'] ?? '无';
                            $trail_log[$key]['reject_detail'] = $report['reject_detail'] ?? '无';
                        }
                    }
                }
            }
        }

        //用户常住地址
//        $distinct_match = [];
//        $address_match = [];
//        if(!is_null($person_relation)){
//            //常住地址区域匹配
//            $address_distinct = $person_relation->address_distinct;
//            $address = $person_relation->address;
//            if(!empty($address_distinct)){
//                $distinct_match = UserQuotaPersonInfo::find()->where(['address_distinct'=>$address_distinct])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
//            }
//            //常住地址完全匹配
//
//            if(!empty($distinct_match)){
//                foreach($distinct_match as $v){
//                    if($v->address == $address){
//                        $address_match[] = $v;
//                    }
//                }
//            }
//        }

        //登录地址
//        $login_log = UserLoginUploadLog::find()->where(['user_id'=>$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
//        $log_deviceId_list = [];
//        $log_address_list = [];
//        if(!empty($login_log)){
//            foreach($login_log as $v){
//                if(!empty(trim($v->deviceId)) && !is_null($v->deviceId) && $v->deviceId != 'null'){
//                    $log_deviceId_list[] = $v->deviceId;
//                }
//                if(!empty(trim($v->address))){
//                    $log_address_list[] = $v->address;
//                }
//
//            }
//        }
//        $log_deviceId_list = array_unique($log_deviceId_list);
//        $log_address_list = array_unique($log_address_list);
//        $log_dev_match = UserLoginUploadLog::find()->select(['user_id','deviceId'])->where(['deviceId'=>$log_deviceId_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
//        $log_address_match = UserLoginUploadLog::find()->select(['user_id','address'])->where(['address'=>$log_address_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        //单位名称重复
//        $repeat_company_address = [];
//        $repeat_company_name = [];
//        if(!is_null($equipment)){
//            $company_name = $equipment->company_name;
//            $company_address = $equipment->company_address;
//            if(!empty(trim($company_name))){
//                $repeat_company_name = UserDetail::find()->where(['company_name'=>$company_name])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
//            }
//            if(!empty(trim($company_address))){
//                $repeat_company_address = UserDetail::find()->where(['company_address'=>$company_address])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
//            }
//        }

        $jsqb_watchlist = CreditJsqb::findLatestOne(['person_id' => $info['user_id']], 'db_kdkj_risk_rd');

        $reject_log = OrderAutoRejectLog::find()->where(['product_id'=> 2,'order_id'=>$info->id])->one();

        //新机审命中项
        $user_credit_data = UserCreditData::find()->where(['user_id'=>$info['user_id'], 'order_id'=>$info->id])->one();

        //face++
        $face = CreditFacePlus::find()->where(['user_id'=>$info['user_id'],'status'=>[CreditFacePlus::STATUS_PENDING,CreditFacePlus::STATUS_SUCCESS]])->one();

        return [
            'jsqb_watchlist' => $jsqb_watchlist,
            'info' => $info,
            'loanPerson'=>$loanPerson,
            'trail_log' => $trail_log,
            'proof_pic' =>$proof_pic,
            'proof_pic_count' => $proof_pic_count,
//            'jxl_count' => $jxl_count,
            'business' => $business,
            'business_count' => $business_count,
            'person_relation' => $person_relation,
            'person_detail' => $person_detail,
            'work' => $work,
            'bank' => $bank,
            'contact' => $contact,
            'equipment' => $equipment,
            'credit' => $credit,
            'credit_line' => $credit_line,
            'credit_score' => $credit_score,
            'fake_score' => $fake_score,
            'jinzhi' => $jinzhi,
            'proof_image' => $proof_image,
            'badInfoLog' => $badInfoLog,
            'all_loan_orders'=>$all_loan_orders,
            'phone_log' => $phone_log,
            'past_phone_log' => $past_phone_log,
            'hit_map' => $hit_map,
            'id_number_address'=>$id_number_address,
            'reject_log' => $reject_log,
            'idcard_count' =>$idcard_count,
            'work_proof' =>$work_proof,
            'work_count' =>$work_count,

//            'distinct_match' => $distinct_match,
//            'address_match' => $address_match,
//            'log_dev_match' => $log_dev_match,
//            'log_address_match' => $log_address_match,
//            'log_deviceId_list' => $log_deviceId_list,
//            'log_address_list' => $log_address_list,
//            'repeat_company_name' => $repeat_company_name,
//            'repeat_company_address' => $repeat_company_address,

            'distinct_match' => [],
            'address_match' => [],
            'log_dev_match' => [],
            'log_address_match' => [],
            'log_deviceId_list' => [],
            'log_address_list' => [],
            'repeat_company_name' => [],
            'repeat_company_address' => [],
//            'mobile_count' => $moblie_count,
//            'message_count' => $message_count,
            'past_trail_log' => $past_trail_log,
            'user_credit_data' => $user_credit_data,
            'face' => $face,
        ];
    }

    /**
     * 获得房租贷信息
     * @return string
     */
    public function getHouseRentInfo($id)
    {
        $info = UserLoanOrder::find()->where(['id' => $id])->one();
        if(empty($info) && !isset($info)) {
            throw new NotFoundHttpException('订单不存在');
        }
        $loanPerson = LoanPerson::findOne($info['user_id']);
        if(is_null($loanPerson)){
            throw new NotFoundHttpException('用户不存在');
        }
        $proof_image = UserProofMateria::findAllByType($info['user_id']);

        //$credit = UserCreditTotal::find()->where(['user_id' => $info['user_id']])->one();
        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($info['user_id'], $id);

        $trail_log = UserOrderLoanCheckLog::find()->where(['order_id' => $id])->orderBy(['id' => SORT_ASC])->all(Yii::$app->get('db_kdkj_rd'));
        $person_detail = UserRealnameVerify::find()->where(['user_id' => $info['user_id']])->one();
        $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $info['user_id']])->one();
        $work = UserQuotaWorkInfo::findOne(['user_id'=>$info['user_id']]);
        $contact = UserContact::find()->where(['user_id' => $info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $bank = CardInfo::find()->where(['user_id' => $info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $equipment = UserDetail::find()->where(['user_id' => $info['user_id']])->one();
        $badInfoLog = LoanPersonBadInfoLog::find()->where(['person_id'=>$info['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $all_loan_orders = UserLoanOrder::find()->where(['user_id'=>$info['user_id']])->andWhere(['<>','id',$info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $phone_log = PhoneReviewLog::find()->where(['user_id' => $info['user_id']])->andWhere(['order_id' => $info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $past_phone_log = PhoneReviewLog::find()->where(['user_id'=>$info['user_id']])->andWhere(['<>','order_id',$info['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $hit_map = null;
        if($info->auto_risk_check_status == 1 && $info->is_hit_risk_rule == 1){
            $hit_map = CreditCheckHitMap::find()->where(['product_id'=>CreditCheckHitMap::PRODUCT_YGD,'product_order_id'=>$info->id,'status'=>1])->with('creditQueryLog')->all(Yii::$app->get('db_kdkj_rd'));
        }

        //用户常住地址
        $distinct_match = [];
        $address_match = [];
        if(!is_null($person_relation)){
            //常住地址区域匹配
            $address_distinct = $person_relation->address_distinct;
            $address = $person_relation->address;
            if(!empty($address_distinct)){
                $distinct_match = UserQuotaPersonInfo::find()->where(['address_distinct'=>$address_distinct])->all(Yii::$app->get('db_kdkj_rd'));
            }
            //常住地址完全匹配

            if(!empty($distinct_match)){
                foreach($distinct_match as $v){
                    if($v->address == $address){
                        $address_match[] = $v;
                    }
                }
            }
        }

        //登录地址
        $login_log = UserLoginUploadLog::find()->where(['user_id'=>$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
        $log_deviceId_list = [];
        $log_address_list = [];
        if(!empty($login_log)){
            foreach($login_log as $v){
                if(!empty(trim($v->deviceId))){
                    $log_deviceId_list[] = $v->deviceId;
                }
                if(!empty(trim($v->address))){
                    $log_address_list[] = $v->address;
                }
            }
        }
        $log_deviceId_list = array_unique($log_deviceId_list);
        $log_address_list = array_unique($log_address_list);
        $log_dev_match = UserLoginUploadLog::find()->select(['user_id','deviceId'])->where(['deviceId'=>$log_deviceId_list])->andWhere(['<>','user_id',$loanPerson->id])->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $log_address_match = UserLoginUploadLog::find()->select(['user_id','address'])->where(['address'=>$log_address_list])->andWhere(['<>','user_id',$loanPerson->id])->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return [
            'info' => $info,
            'loanPerson'=>$loanPerson,
            'trail_log' => $trail_log,
            'person_relation' => $person_relation,
            'person_detail' => $person_detail,
            'work' => $work,
            'bank' => $bank,
            'contact' => $contact,
            'equipment' => $equipment,
            'credit' => $credit,
            'proof_image' => $proof_image,
            'badInfoLog' => $badInfoLog,
            'all_loan_orders'=>$all_loan_orders,
            'phone_log' => $phone_log,
            'past_phone_log' => $past_phone_log,
            'hit_map' => $hit_map,
            'distinct_match' => $distinct_match,
            'address_match' => $address_match,
            'log_dev_match' => $log_dev_match,
            'log_address_match' => $log_address_match,
            'log_deviceId_list' => $log_deviceId_list,
            'log_address_list' => $log_address_list
        ];
    }

    /**
     * 获得分期购信息
     * @return string
     */
    public function getInstallmentShopInfo($id)
    {
        $shop_order = InstallmentShopOrder::find()->where(['user_loan_order_id'=>intval($id),'status'=>1])->one();
        if(is_null($shop_order)){
            throw new NotFoundHttpException('订单不存在');
        }
        $loanPerson = LoanPerson::findOne($shop_order['person_id']);
        if(is_null($loanPerson)){
            throw new NotFoundHttpException('用户不存在');
        }
        $userLoanOrder = UserLoanOrder::findOne($shop_order['user_loan_order_id']);
        if(is_null($userLoanOrder)){
            throw new NotFoundHttpException('借款订单不存在');
        }
        $installmentShop = InstallmentShop::findOne($shop_order['installment_shop_id']);
        if(is_null($installmentShop)){
            throw new NotFoundHttpException('商品不存在');
        }
        $person_detail = UserRealnameVerify::find()->where(['user_id' => $shop_order['person_id']])->one();
        $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $shop_order['person_id']])->one();
        $work = UserQuotaWorkInfo::findOne(['user_id'=>$shop_order['person_id']]);
        $contact = UserContact::find()->where(['user_id' => $shop_order['person_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $bank = CardInfo::find()->where(['user_id' => $shop_order['person_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $equipment = UserDetail::find()->where(['user_id' => $shop_order['person_id']])->one();
        $proof_image = UserProofMateria::findAllByType($shop_order['person_id']);
        $log = UserOrderLoanCheckLog::find()->where(['order_id'=>$shop_order['user_loan_order_id']])->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        //$credit = UserCreditTotal::findOne(['user_id'=>$shop_order['person_id']]);
        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserAndOrder($shop_order['person_id'], $shop_order['user_loan_order_id']);

        $badInfoLog = LoanPersonBadInfoLog::find()->where(['person_id'=>$shop_order['person_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $all_loan_orders = UserLoanOrder::find()->where(['user_id'=>$shop_order['person_id']])->andWhere(['<>','id',$shop_order['person_id']])->all(Yii::$app->get('db_kdkj_rd'));
        $phone_log = PhoneReviewLog::find()->where(['user_id' => $userLoanOrder['user_id']])->andWhere(['order_id' => $userLoanOrder['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $past_phone_log = PhoneReviewLog::find()->where(['user_id'=>$userLoanOrder['user_id']])->andWhere(['<>','order_id',$userLoanOrder['id']])->all(Yii::$app->get('db_kdkj_rd'));
        $hit_map = null;
        if($userLoanOrder->auto_risk_check_status == 1 && $userLoanOrder->is_hit_risk_rule == 1){
            $hit_map = CreditCheckHitMap::find()->where(['product_id'=>CreditCheckHitMap::PRODUCT_YGD,'product_order_id'=>$userLoanOrder->id,'status'=>1])->all(Yii::$app->get('db_kdkj_risk_rd'));
        }

        //用户常住地址
        $distinct_match = [];
        $address_match = [];
        if(!is_null($person_relation)){
            //常住地址区域匹配
            $address_distinct = $person_relation->address_distinct;
            $address = $person_relation->address;
            if(!empty($address_distinct)){
                $distinct_match = UserQuotaPersonInfo::find()->where(['address_distinct'=>$address_distinct])->all(Yii::$app->get('db_kdkj_rd'));
            }
            //常住地址完全匹配

            if(!empty($distinct_match)){
                foreach($distinct_match as $v){
                    if($v->address == $address){
                        $address_match[] = $v;
                    }
                }
            }
        }

        //登录地址
        $login_log = UserLoginUploadLog::find()->where(['user_id'=>$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
        $log_deviceId_list = [];
        $log_address_list = [];
        if(!empty($login_log)){
            foreach($login_log as $v){
                if(!empty(trim($v->deviceId))){
                    $log_deviceId_list[] = $v->deviceId;
                }
                if(!empty(trim($v->address))){
                    $log_address_list[] = $v->address;
                }
            }
        }
        $log_deviceId_list = array_unique($log_deviceId_list);
        $log_address_list = array_unique($log_address_list);
        $log_dev_match = UserLoginUploadLog::find()->select(['user_id','deviceId'])->where(['deviceId'=>$log_deviceId_list])->andWhere(['<>','user_id',$loanPerson->id])->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $log_address_match = UserLoginUploadLog::find()->select(['user_id','address'])->where(['address'=>$log_address_list])->andWhere(['<>','user_id',$loanPerson->id])->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));


        return [
            'shop_order'=>$shop_order,
            'loanPerson'=>$loanPerson,
            'info'=>$userLoanOrder,
            'installmentShop'=>$installmentShop,
            'trail_log'=>$log,
            'person_relation' => $person_relation,
            'person_detail' => $person_detail,
            'work' => $work,
            'bank' => $bank,
            'contact' => $contact,
            'equipment' => $equipment,
            'proof_image' => $proof_image,
            'credit'=>$credit,
            'badInfoLog' => $badInfoLog,
            'all_loan_orders'=>$all_loan_orders,
            'phone_log' => $phone_log,
            'past_phone_log' => $past_phone_log,
            'hit_map' => $hit_map,
            'distinct_match' => $distinct_match,
            'address_match' => $address_match,
            'log_dev_match' => $log_dev_match,
            'log_address_match' => $log_address_match,
            'log_deviceId_list' => $log_deviceId_list,
            'log_address_list' => $log_address_list
        ];
    }

    //备注码
    public function getRemarkCode()
    {
        $reject_list = LoanPersonBadInfo::$reject_code;
        $reject_tmp = [];
        foreach($reject_list as $k=>$v){
            foreach($v['child'] as $value){
                $reject_tmp[$k.'o'.$value['id']] = $v['backend_name']." / ".$value['backend_name'];
            }
        }
        $pass_list = LoanPersonBadInfo::$pass_code;
        $pass_tmp = [];
        foreach($pass_list as $k=>$v){
            foreach($v['child'] as $value){
                $pass_tmp[$k.'o'.$value['id']] = $v['backend_name']." / ".$value['backend_name'];
            }
        }

        return [
            'reject_tmp' => $reject_tmp,
            'pass_tmp' => $pass_tmp,
        ];
    }

    //备注码
    public function getActiveRemarkCode()
    {
        $reject_list = LoanPersonBadInfo::$reject_code;
        $reject_tmp = [];
        foreach($reject_list as $k=>$v){
            foreach($v['child'] as $value){
                if ( in_array($value['id'], $v['active']) ) {
                    $reject_tmp[$k.'o'.$value['id']] = $v['backend_name']." / ".$value['backend_name'];
                }
            }
        }
        $pass_list = LoanPersonBadInfo::$pass_code;
        $pass_tmp = [];
        foreach($pass_list as $k=>$v){
            foreach($v['child'] as $value){
                if ( in_array($value['id'], $v['active']) ) {
                    $pass_tmp[$k.'o'.$value['id']] = $v['backend_name']." / ".$value['backend_name'];
                }
            }
        }

        return [
            'reject_tmp' => $reject_tmp,
            'pass_tmp' => $pass_tmp,
        ];
    }

    //获取中智诚所需的订单信息
    public function getOrderZzcParams($order_id)
    {
        $order = UserLoanOrder::findOne($order_id);
        if(is_null($order)){
            throw new Exception('订单不存在');
        }
        switch ($order->order_type){
            case UserLoanOrder::LOAN_TYPE_LQD:
                $loan_type = '零钱贷';
                $loan_purpose = '消费';
                break;
            case UserLoanOrder::LOAN_TYPR_FZD:
                $loan_type = '房租贷';
                $loan_purpose = '房租';
                break;
            case UserLoanOrder::LOAN_TYPE_FQSC:
                $loan_type = '商城分期';
                $loan_purpose = '消费';
                break;
            default:
                throw new Exception('订单类型错误');
        }
        switch ($order->loan_method){
            case UserLoanOrder::LOAN_METHOD_DAY:
                $period = ceil($order->loan_term/30);
                break;
            case UserLoanOrder::LOAN_METHOD_MONTH:
                $period = $order->loan_term;
                break;
            case UserLoanOrder::LOAN_METHOD_YEAR:
                $period = $order->loan_term *12;
                break;
            default:
                throw new Exception('订单期数类型错误');
        }
        $period = $period ? intval($period): 1;
        $loanPerson = LoanPerson::findOne($order->user_id);
        if(is_null($loanPerson)){
            throw new Exception('借款人信息不存在');
        }
        $info = [];
        $info['name'] = trim($loanPerson->name);
        $info['pid'] = trim($loanPerson->id_number);
        $info['mobile'] = trim($loanPerson->phone);
        $info['loan_term'] = $period;
        foreach ($info as $k=>$item) {
            if(empty($item)){
                throw new Exception("{$k}不能为空");
            }
        }

        $params = [
            'loan_type' =>$loan_type,
            'loan_purpose' => $loan_purpose,
            'loan_term' => $period,
            'applicant' => [
                'name' => $info['name'],
                'pid' => $info['pid'],
                'mobile' => $info['mobile'],
            ]
        ];

        return $params;
    }

    /**
     * 获取借款人信息
     * @param mixed $id
     * @throws NotFoundHttpException
     * @return multitype:multitype: \yii\db\static multitype:unknown  multitype:\yii\db\static  unknown string
     */
    public function getLoanPersonInfo($id) {
        if ($id instanceof LoanPerson) {
            $loanPerson = $id;
            $id = $loanPerson->id;
        }
        else {
            $loanPerson = LoanPerson::findOne($id);
            if (empty($loanPerson)) {
                throw new NotFoundHttpException('用户不存在');
            }
        }

        $id_number_address = "";
        $id_number = $loanPerson->id_number;
        if(!empty($id_number)&&ToolsUtil::checkIdNumber($id_number)){
            $id_number_address = ToolsUtil::get_addr($id_number);
        }
        $proof_image = UserProofMateria::findAllByType($id);

        $creditChannelService = \Yii::$app->creditChannelService;
        $credit = $creditChannelService->getCreditTotalByUserId($id);

        $person_detail = UserRealnameVerify::find()->where(['user_id' => $id])->one();
        $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $id])->one();
        $work = UserQuotaWorkInfo::findOne(['user_id'=>$id]);
        $contact = UserContact::find()->where(['user_id' => $id])->all(Yii::$app->get('db_kdkj_rd'));
        $bank = CardInfo::find()->where(['user_id' =>$id])->all(Yii::$app->get('db_kdkj_rd'));
        $equipment = UserDetail::find()->where(['user_id' => $id])->one();
        $badInfoLog = LoanPersonBadInfoLog::find()->where(['person_id'=>$id])->all(Yii::$app->get('db_kdkj_rd'));

        //用户常住地址
        $distinct_match = [];
        $address_match = [];
        if(!is_null($person_relation)){
            //常住地址区域匹配
            $address_distinct = $person_relation->address_distinct;
            $address = $person_relation->address;
            if(!empty($address_distinct)){
                //$distinct_match = UserQuotaPersonInfo::find()->where(['address_distinct'=>$address_distinct])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
            }
            //常住地址完全匹配
            if(!empty($distinct_match)){
                foreach($distinct_match as $v){
                    if($v->address == $address){
                        $address_match[] = $v;
                    }
                }
            }
        }

        //登录地址
        $login_log = [];//UserLoginUploadLog::find()->where(['user_id'=>$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
        $log_deviceId_list = [];
        $log_address_list = [];
        if(!empty($login_log)){
            foreach($login_log as $v){
                if(!empty(trim($v->deviceId))){
                    $log_deviceId_list[] = $v->deviceId;
                }
                if(!empty(trim($v->address))){
                    $log_address_list[] = $v->address;
                }
            }
        }
        $log_deviceId_list = array_unique($log_deviceId_list);
        $log_address_list = array_unique($log_address_list);
        $log_dev_match = [];//UserLoginUploadLog::find()->select(['user_id','deviceId'])->where(['deviceId'=>$log_deviceId_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $log_address_match = [];//UserLoginUploadLog::find()->select(['user_id','address'])->where(['address'=>$log_address_list])->andWhere(['<>','user_id',$loanPerson->id])->orderBy('user_id')->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        //单位名称重复
        $repeat_company_address = [];
        $repeat_company_name = [];
        if(!is_null($equipment)){
            $company_name = $equipment->company_name;
            $company_address = $equipment->company_address;
            if(!empty(trim($company_name))){
                //$repeat_company_name = UserDetail::find()->where(['company_name'=>$company_name])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
            }
            if(!empty(trim($company_address))){
                //$repeat_company_address = UserDetail::find()->where(['company_address'=>$company_address])->andWhere(['<>','user_id',$loanPerson->id])->all(Yii::$app->get('db_kdkj_rd'));
            }
        }

        return [
            'loanPerson'=>$loanPerson,
            'person_relation' => $person_relation,
            'person_detail' => $person_detail,
            'work' => $work,
            'bank' => $bank,
            'contact' => $contact,
            'equipment' => $equipment,
            'credit' => $credit,
            'proof_image' => $proof_image,
            'badInfoLog' => $badInfoLog,
            'id_number_address'=>$id_number_address,
            'distinct_match' => $distinct_match,
            'address_match' => $address_match,
            'log_dev_match' => $log_dev_match,
            'log_address_match' => $log_address_match,
            'log_deviceId_list' => $log_deviceId_list,
            'log_address_list' => $log_address_list,
            'repeat_company_name' => $repeat_company_name,
            'repeat_company_address' => $repeat_company_address,
        ];
    }

    /**
     * 提额审核时所需用户信息
     */
    public function LimitPersonInfo($user_id)
    {
        $creditChannelService = \Yii::$app->creditChannelService;
        $information['credit'] = $creditChannelService->getCreditTotalByUserId($user_id);

        $information['loanPerson'] = LoanPerson::findOne($user_id);
        $information['person_relation'] = UserQuotaPersonInfo::find()->where(['user_id' => $user_id])->one();
        $id_number_address = "";
        $id_number =  $information['loanPerson']->id_number;
        if(!empty($id_number)&&ToolsUtil::checkIdNumber($id_number)){
            $information['id_number_address'] = ToolsUtil::get_addr($id_number);
        }
        $information['proof_image'] = UserProofMateria::findAllByType($user_id);
        $information['contact'] = UserContact::find()->where(['user_id' => $user_id])->all(Yii::$app->get('db_kdkj_rd'));
        $information['bank'] = CardInfo::find()->where(['user_id' => $user_id])->all(Yii::$app->get('db_kdkj_rd'));
        $information['all_loan_orders'] = UserLoanOrder::find()->from(UserLoanOrder::tableName()."as a")
            ->where(['a.user_id'=>$user_id])->all(Yii::$app->get('db_kdkj_rd'));
        $information['equipment'] = UserDetail::find()->where(['user_id' => $user_id])->one();
        if(!empty($information['all_loan_orders'])){
            $order_ids = [];
            foreach($information['all_loan_orders'] as $order){
                $order_ids[] = $order['id'];
            }
            $information['past_trail_log'] = UserOrderLoanCheckLog::find()->where(['order_id' => $order_ids])->orderBy(['id' => SORT_ASC])->all(Yii::$app->get('db_kdkj_rd'));
        }else{
            $information['past_trail_log'] = null;
        }
        $information['distinct_match'] = [];
        $information['address_match'] = [];
        $information['log_dev_match'] = [];
        $information['log_address_match'] = [];
        $information['log_deviceId_list'] = [];
        $information['log_address_list'] = [];
        $information['repeat_company_name'] = [];
        $information['repeat_company_address'] = [];
        $information['past_phone_log'] = PhoneReviewLog::find()->where(['user_id'=>$user_id])->all(Yii::$app->get('db_kdkj_rd'));
        return $information;
    }
}