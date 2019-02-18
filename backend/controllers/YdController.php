<?php
namespace backend\controllers;

use common\helpers\CurlHelper;
use common\models\asset\AssetOrder;
use common\models\CreditCheckHitMap;
use common\models\CreditQueryLog;
use common\models\LoanBlacklistDetail;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Exception;
use yii\web\Response;
use yii\data\Pagination;
use common\models\LoanPerson;
use common\models\CreditYd;
use common\helpers\LoanPersonDetailHelper;

class YdController extends BaseController
{
    /**
     * @return array
     * @name 征信管理-用户征信管理-用户征信管理-有盾-点击获取数据/actionGetInfo
     */
    public function actionGetInfo(){
        $this->response->format = Response::FORMAT_JSON;
        $type = intval($this->request->get('type'));
        $product_id = intval($this->request->get('product_id'));
        $order_id = intval($this->request->get('order_id'));
        try{
            switch ($product_id){
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    $uid = $order->user_id;
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    $uid = $order->user_id;
                    break;
                default:
                    throw new Exception('产品号错误');
            }
            if(is_null($order)){
                throw new Exception('订单不存在');
            }
            if(!in_array($type,array_keys(CreditYd::$type_list))){
                throw new Exception('类型错误');
            }
            $loanPerson = LoanPerson::findOne($uid);
            if(is_null($loanPerson)){
                throw new Exception('用户不存在');
            }
            $service = Yii::$container->get('ydService');
            $data = $service->getLoanPersonInfo($loanPerson,$type,$product_id,$order_id);
            $result = $service->saveData();

            return [
                'code'=>0,
                'message'=>CreditYd::$type_list[$type].':获取成功'
            ];
        }catch(Exception $e){
            return [
                'code' => -1,
                'message' => CreditYd::$type_list[$type].':'.$e->getMessage()
            ];
        }

    }

    /**
     * @return string
     * @throws Exception
     * @name 征信管理-用户征信管理-用户征信管理-有盾/actionView
     */
    public function actionView(){
        $product_id = intval($this->request->get('product_id'));
        $order_id = intval($this->request->get('order_id'));
        $credit_id = CreditQueryLog::Credit_YD;
        $idNumberLeak = null;
        $courtLoseCreditPerson = null;
        $stolenCardBlacklistPhone = null;
        $stolenCardBlacklistIdNumber = null;
        $stolenCardBlacklistCard = null;
        $moneyLaunderingSanctionlist = null;
        $p2pLoseCreditList = null;

        if($order_id && $credit_id){
            switch ($product_id){
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                default:
                    throw new Exception('产品号错误');
            }
            if(is_null($order)){
                throw new Exception('订单不存在');
            }
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $map = CreditCheckHitMap::find()->select(['log_id','credit_type'])->where([
                'product_id'=>$product_id,
                'product_order_id'=>$order_id,
                'credit_id'=> $credit_id,
            ])->all();
            foreach ($map as $item) {
                switch ($item->credit_type){
                    case CreditYd::TYPE_ID_NUMBER_LEAK:
                        $idNumberLeak = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_COURT_LOSE_CREDIT_PERSON:
                        $courtLoseCreditPerson = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_PHONE:
                        $stolenCardBlacklistPhone = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_ID_NUMBER:
                        $stolenCardBlacklistIdNumber = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_CARD_NUM:
                        $stolenCardBlacklistCard = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_MONEY_LAUNDERING_SANCATIONLIST:
                        $moneyLaunderingSanctionlist = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                    case CreditYd::TYPE_P2P_LOSE_CREDIT_LIST:
                        $p2pLoseCreditList = CreditQueryLog::findLatestOne(['id'=>$item->log_id]);
                        break;
                }
            }
        }else{
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $idNumberLeak = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_ID_NUMBER_LEAK]);
            $courtLoseCreditPerson = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_COURT_LOSE_CREDIT_PERSON]);
            $stolenCardBlacklistPhone = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_PHONE]);
            $stolenCardBlacklistIdNumber = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_ID_NUMBER]);
            $stolenCardBlacklistCard = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_CARD_NUM]);
            $moneyLaunderingSanctionlist = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_MONEY_LAUNDERING_SANCATIONLIST]);
            $p2pLoseCreditList = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_P2P_LOSE_CREDIT_LIST]);
        }
        

        return $this->render('view', array(
            'info' => [
                'loanPerson' => $loanPerson,
                'idNumberLeak' => $idNumberLeak,
                'courtLoseCreditPerson' => $courtLoseCreditPerson,
                'stolenCardBlacklistPhone' => $stolenCardBlacklistPhone,
                'stolenCardBlacklistIdNumber' => $stolenCardBlacklistIdNumber,
                'stolenCardBlacklistCard' => $stolenCardBlacklistCard,
                'moneyLaunderingSanctionlist' => $moneyLaunderingSanctionlist,
                'p2pLoseCreditList' => $p2pLoseCreditList,
                'product_id' => $product_id,
                'order_id' => $order_id,
                'id'    => $id
            ]
        ));
    }

      /**
     * @return string
     * @throws Exception
     * @name 征信管理-用户征信管理-用户征信管理-有盾-历史查询
     */
    public function actionOldView(){
        $product_id = intval($this->request->get('product_id'));
        $order_id = intval($this->request->get('order_id'));
        $credit_id = CreditQueryLog::Credit_YD;
        $idNumberLeak = null;
        $courtLoseCreditPerson = null;
        $stolenCardBlacklistPhone = null;
        $stolenCardBlacklistIdNumber = null;
        $stolenCardBlacklistCard = null;
        $moneyLaunderingSanctionlist = null;
        $p2pLoseCreditList = null;

        if($order_id && $credit_id){
            switch ($product_id){
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                default:
                    throw new Exception('产品号错误');
            }
            if(is_null($order)){
                throw new Exception('订单不存在');
            }
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $map = CreditCheckHitMap::find()->select(['log_id','credit_type'])->where([
                'product_id'=>$product_id,
                'product_order_id'=>$order_id,
                'credit_id'=> $credit_id,
            ])->all();
            foreach ($map as $item) {
                switch ($item->credit_type){
                    case CreditYd::TYPE_ID_NUMBER_LEAK:
                        $idNumberLeak = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_COURT_LOSE_CREDIT_PERSON:
                        $courtLoseCreditPerson = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_PHONE:
                        $stolenCardBlacklistPhone = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_ID_NUMBER:
                        $stolenCardBlacklistIdNumber = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_STOLEN_CARD_BLACKLIST_CARD_NUM:
                        $stolenCardBlacklistCard = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_MONEY_LAUNDERING_SANCATIONLIST:
                        $moneyLaunderingSanctionlist = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                    case CreditYd::TYPE_P2P_LOSE_CREDIT_LIST:
                        $p2pLoseCreditList = CreditQueryLog::find()->where([$item->log_id,'is_overdue'=>CreditYd::IS_OVERDUE_1])->all();
                        break;
                }
            }
        }else{
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $idNumberLeak = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_ID_NUMBER_LEAK,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $courtLoseCreditPerson = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_COURT_LOSE_CREDIT_PERSON,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $stolenCardBlacklistPhone = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_PHONE,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $stolenCardBlacklistIdNumber = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_ID_NUMBER,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $stolenCardBlacklistCard = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_STOLEN_CARD_BLACKLIST_CARD_NUM,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $moneyLaunderingSanctionlist = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_MONEY_LAUNDERING_SANCATIONLIST,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
            $p2pLoseCreditList = CreditQueryLog::find()->where([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYd::TYPE_P2P_LOSE_CREDIT_LIST,'is_overdue'=>CreditYd::IS_OVERDUE_1
            ])->orderBy('id desc')->all();
        }
        

        return $this->render('old-view', array(
            'info' => [
                'loanPerson' => $loanPerson,
                'idNumberLeak' => $idNumberLeak,
                'courtLoseCreditPerson' => $courtLoseCreditPerson,
                'stolenCardBlacklistPhone' => $stolenCardBlacklistPhone,
                'stolenCardBlacklistIdNumber' => $stolenCardBlacklistIdNumber,
                'stolenCardBlacklistCard' => $stolenCardBlacklistCard,
                'moneyLaunderingSanctionlist' => $moneyLaunderingSanctionlist,
                'p2pLoseCreditList' => $p2pLoseCreditList,
                'product_id' => $product_id,
                'order_id' => $order_id
            ]
        ));
    }
    
}