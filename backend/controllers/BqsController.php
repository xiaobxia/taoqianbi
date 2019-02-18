<?php
namespace backend\controllers;

use common\models\asset\AssetOrder;
use common\models\CreditCheckHitMap;
use common\models\CreditQueryLog;
use common\models\CreditZzcReport;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Exception;
use yii\web\Response;
use yii\data\Pagination;
use common\models\LoanPerson;
use common\models\CreditBqs;

class BqsController extends BaseController
{

    /**
     * @return array
     * @name 征信管理-用户征信管理-用户征信管理-白骑士-点击查询最新数据/actionGetInfo
     */
    public function actionGetInfo(){
        try{
            $this->response->format = Response::FORMAT_JSON;
            $product = intval($this->request->get('product_id'));
            $order_id = intval($this->request->get('order_id'));
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                throw new Exception('用户不存在');
            }
            $service = Yii::$container->get('bqsService');
            $data = $service->getLoanPersonDecision($loanPerson,$product,$order_id);
            if ($data === true){
	            return [
	                'code'=>0,
	                'message'=>'获取成功'
	            ];
            }else{
                return [
                    'code'=>-1,
                    'message'=>'获取失败'
                ];
            }
        }catch(Exception $e){
            return [
                'code' => -1,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-白骑士/actionView
     */
    public function actionView(){
        $product_id = $this->request->get('product_id');
        $order_id = $this->request->get('order_id');
        $type = CreditBqs::TYPE_LOAN_DECISION;
        $report = null;
        $credit_id = CreditQueryLog::Credit_BQS;
        if($product_id && $order_id){
            switch ($product_id){
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    break;
                default:
                    return $this->redirectMessage('产品号错误',self::MSG_ERROR);
            }
            if(is_null($order)){
                return $this->redirectMessage('订单不存在',self::MSG_ERROR);
            }
            $loanPerson = LoanPerson::findOne($order->user_id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $map = CreditCheckHitMap::find()->select(['log_id'])->where([
                'product_id'=>$product_id,
                'product_order_id'=>$order_id,
                'credit_id'=>CreditQueryLog::Credit_BQS,
                'credit_type'=>$type,
            ])->one();
            if(!is_null($map)){
                $report = CreditQueryLog::findLatestOne(['id'=>$map['log_id']]);
            }
        }else{
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $report = CreditBqs::findLatestOne([
                'person_id'=>$loanPerson->id]);
        }

        return $this->render('view', array(
            'info' => [
                'loanPerson' => $loanPerson,
                'report' => $report,
                'product_id' => $product_id,
                'order_id' => $order_id,
                'type' => $type,
                'id' =>$loanPerson->id
            ]
        ));
    }


     /**
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-白骑士-历史查询
     */
    public function actionOldView(){
        $product_id = $this->request->get('product_id');
        $order_id = $this->request->get('order_id');
        $type = CreditBqs::TYPE_LOAN_DECISION;
        $report = null;
        $credit_id = CreditQueryLog::Credit_BQS;
        if($product_id && $order_id){
            switch ($product_id){
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    break;
                default:
                    return $this->redirectMessage('产品号错误',self::MSG_ERROR);
            }
            if(is_null($order)){
                return $this->redirectMessage('订单不存在',self::MSG_ERROR);
            }
            $loanPerson = LoanPerson::findOne($order->user_id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $map = CreditCheckHitMap::find()->select(['log_id'])->where([
                'product_id'=>$product_id,
                'product_order_id'=>$order_id,
                'credit_id'=>CreditQueryLog::Credit_BQS,
                'credit_type'=>$type,

            ])->oderBy('id desc')->one();
            if(!is_null($map)){
                $report = CreditQueryLog::find()->where(['is_overdue' =>CreditBqs::IS_OVERDUE_1,'id'=>$map['log_id']])->oderBy('id desc')->limit(5)->offset(0)->all();
            }
        }else{
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $report = CreditQueryLog::find()->where(['is_overdue' =>CreditBqs::IS_OVERDUE_1,'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>$type
            ])->orderBy('id desc')->all();
        }

        return $this->render('old-view', array(
            
                'loanPerson' => $loanPerson,
                'report' => $report,
                'product_id' => $product_id,
                'order_id' => $order_id,
                'type' => $type,
            
        ));
    }
}