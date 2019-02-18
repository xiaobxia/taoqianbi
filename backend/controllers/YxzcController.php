<?php
namespace backend\controllers;

use common\models\asset\AssetOrder;
use common\models\CreditCheckHitMap;
use common\models\CreditQueryLog;
use common\models\LoanBlacklistDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\services\YxzcRequestService;
use Yii;
use yii\base\Exception;
use yii\web\Response;
use yii\data\Pagination;
use common\models\LoanPerson;
use common\helpers\StringHelper;
use yii\db\Query;
use common\models\CreditYxzc;
use common\api\yxzc\YxzcProvide;
use common\models\CreditYxzcLog;
use common\helpers\LoanPersonDetailHelper;

class YxzcController extends BaseController
{

    /**
     * @return string
     * @throws Exception
     * @name 征信管理-获取订单信息-宜信至诚/actionGetInfo
     */
    public function actionGetInfo(){
        try{
            $this->response->format = Response::FORMAT_JSON;
            $type = intval($this->request->get('type'));
            $product_id = intval($this->request->get('product_id'));
            $order_id = intval($this->request->get('order_id'));
            switch ($product_id) {
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
                throw new Exception('借款人不存在');
            }
            $Service = Yii::$container->get('yxzcService');
            switch($type){
                case CreditYxzc::TYPE_LOAN_INFO :
                    $ret = $Service->loanInfoQuery($loanPerson,$product_id,$order_id);
                    break;
                case CreditYxzc::TYPE_RISK_LIST :
                    $ret = $Service->riskListQuery($loanPerson,$product_id,$order_id);
                    break;
                case CreditYxzc::TYPE_ZC_SCORE :
                    $ret = $Service->zcScoreQuery($loanPerson,$product_id,$order_id);
                    break;
                case CreditYxzc::TYPE_QUERY_INFO :
                    $ret = $Service->queryInfoQuery($loanPerson,$product_id,$order_id);
                    break;
                default:
                    return [
                        'code' => -1,
                        'message'=>'未定义的类型'
                    ];
            }

            return [
                'code' => 0,
                'message' => '数据获取成功'
            ];

        }catch(Exception $e){
            return [
                'code' => -2,
                'message' => $e->getMessage()
            ];
        }


    }

    /**
     * @return string
     * @throws Exception
     * @name 征信管理-用户征信管理-用户征信管理-宜信至诚/actionView
     */
    public function actionView(){
        $product_id = intval($this->request->get('product_id'));
        $order_id = intval($this->request->get('order_id'));
        $credit_id = CreditQueryLog::Credit_YXZC;
        $loan_info = null;
        $risk_list = null;
        $zc_score = null;
        $query_info = null;
        if($product_id && $order_id ){
            switch ($product_id) {
                case CreditQueryLog::PRODUCT_ASSET_PARTHER:
                    $order = AssetOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                case CreditQueryLog::PRODUCT_YGD:
                    $order = UserLoanOrder::findOne($order_id);
                    $id = $order->user_id;
                    break;
                default:
                    return $this->redirectMessage('产品号错误',self::MSG_ERROR);
            }
            if(is_null($order)){
                throw new Exception('订单不存在');
            }
            $loanPerson = LoanPerson::findOne($id);

            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $map = CreditCheckHitMap::find()->where(['product_id'=>$product_id,'product_order_id'=>$order_id,'credit_id'=>$credit_id])->all();
            if(!empty($map)){
                foreach($map as $v){
                    switch ($v->credit_type){
                        case CreditYxzc::TYPE_LOAN_INFO:
                            $loan_info = CreditQueryLog::findOne($v->log_id);
                            break;
                        case CreditYxzc::TYPE_RISK_LIST:
                            $risk_list = CreditQueryLog::findOne($v->log_id);
                            break;
                        case CreditYxzc::TYPE_ZC_SCORE:
                            $zc_score = CreditQueryLog::findOne($v->log_id);
                            break;
                        case CreditYxzc::TYPE_QUERY_INFO:
                            $query_info = CreditQueryLog::findOne($v->log_id);
                            break;
                    }
                }
            }else{
                $loan_info = CreditQueryLog::findLatestOne([
                    'person_id'=>$loanPerson->id,'credit_id'=>CreditQueryLog::Credit_YXZC,'credit_type'=>CreditYxzc::TYPE_LOAN_INFO
                ]);
                $risk_list = CreditQueryLog::findLatestOne([
                    'person_id'=>$loanPerson->id,'credit_id'=>CreditQueryLog::Credit_YXZC,'credit_type'=>CreditYxzc::TYPE_RISK_LIST
                ]);
                $zc_score = CreditQueryLog::findLatestOne([
                    'person_id'=>$loanPerson->id,'credit_id'=>CreditQueryLog::Credit_YXZC,'credit_type'=>CreditYxzc::TYPE_ZC_SCORE
                ]);
                $query_info = CreditQueryLog::findLatestOne([
                    'person_id'=>$loanPerson->id,'credit_id'=>CreditQueryLog::Credit_YXZC,'credit_type'=>CreditYxzc::TYPE_QUERY_INFO
                ]);

            }
        }else{
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
            }
            $loan_info = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYxzc::TYPE_LOAN_INFO
            ]);
            $risk_list = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYxzc::TYPE_RISK_LIST
            ]);
            $zc_score = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYxzc::TYPE_ZC_SCORE
            ]);
            $query_info = CreditQueryLog::findLatestOne([
                'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYxzc::TYPE_QUERY_INFO
            ]);

        }

        return $this->render('view', array(
            'info' => [
                'loanPerson' => $loanPerson,
                'loan_info' => $loan_info,
                'risk_list' => $risk_list,
                'zc_score' => $zc_score,
                'query_info' => $query_info,
                'product_id' => $product_id,
                'order_id' => $order_id
            ]
        ));
    }
    /**
     * @return string
     * @throws Exception
     * @name 征信管理-查询信息-宜信至诚/actionViewNew
     */


  public function actionGetInfoNew(){
      try{
          $order_id = intval($this->request->get('order_id'));
          $product_id = intval($this->request->get('product_id'));
          $order = UserLoanOrder::findOne($order_id);
          if(!$order){
              throw new Exception('订单不存在');
          }
          $id = $order->user_id;

          $loanPerson = LoanPerson::findOne($id);
          if(!$loanPerson){
              throw new Exception('借款人不存在');
          }
          $Service =new YxzcRequestService();
          $ret = $Service->loanInfoQuery($loanPerson,$order_id,$product_id);
          if($ret){
              $r =  [
                  'code' => 0,
                  'message' => '数据获取成功'
              ];
              echo json_encode($r);

          }else{
              $r =  [
                  'code' => 0,
                  'message' => '查询无结果'
              ];
              echo json_encode($r);
          }


      }catch(Exception $e){

          $a =  [
              'code' => -2,
              'message' => $e->getMessage()
          ];
          echo json_encode($a);
      }

  }
    /**
     * @return string
     * @throws Exception
     * @name 征信管理-用户征信管理-用户征信管理-宜信至诚/actionViewNew
     */

   public function actionViewNew(){
       $product_id = intval($this->request->get('product_id'));
       $order_id = intval($this->request->get('order_id'));
       $credit_id = CreditQueryLog::Credit_YXZC;
       $loan_info = null;

       if($product_id && $order_id ){

           $order = UserLoanOrder::findOne($order_id);
           $loanPerson = LoanPerson::find()->where(['id'=>$order->user_id])->one();
           if(is_null($loanPerson)){
               throw new Exception('该借款人不存在');
           }
           $loan_info = CreditQueryLog::find()->where([
               'person_id'=>$loanPerson->id,'credit_id'=>$credit_id,'credit_type'=>CreditYxzc::NEW_TYPE_LOAN_INFO
           ])->orderBy('id desc')->one();

       }
       $data = null;
     if($loan_info && isset($loan_info['data'])){
         $data = json_decode($loan_info['data'],true);
       // echo "<pre>"; print_r($data);die;
     }


       return $this->render('view-new',[
           'info' => $data,
           'order_id' =>$order_id,
           'date'=>$loan_info['created_at']
       ]
       );
   }
}