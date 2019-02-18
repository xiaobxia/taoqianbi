<?php

namespace backend\controllers;

use Yii;
use common\helpers\TimeHelper;
use yii\base\Exception;
use common\models\Setting;
use common\models\Order;

use common\models\FinancialManReview;

/**
 * AdminNotifyController
 */
class AdminNotifyController extends \frontend\controllers\BaseController
{
     /**
      * @name 获取人工打款审核通过记录
     * 获取人工打款审核通过记录
     */
    public function actionManReviewPassPayList(){
        //验签
        $params = $this->request->post();
        $sign = $this->request->post('sign');
        $t = $this->request->get('type','');
        if (!Order::validateSign($params, $sign)) {
            Yii::info("Failed To Verify Sign");
            return [
                'code' => "-2",
                'err_msg' => "Failed To Verify Sign",
            ];
        }
        //一次取出500条 ID小到大
        $limit = 500;
        //$list = FinancialManReview::passList($limit);
        $sql = "select id,ticket_id,username,credit_card_no,money,status,dk_success_at from tb_financial_man_review where `status`=".FinancialManReview::REVIEW_STATUS_APPROVE." order by id asc limit 0,$limit";
        $list = Yii::$app->db_staff->createCommand($sql)->queryAll();

        return [
            'code' => 0,
            'data' => $list,
        ];
    }

    /**
     * @name (人工审核) 发起打款回调
     * @return [type] [description]
     */
    public function actionManReviewPayNotify()
    {
        try {
            //验签
            $params = $this->request->post();
            $sign = $this->request->post('sign');
            if (!Order::validateSign($params, $sign)) {
                Yii::info("Failed To Verify Sign");
                return [
                    'code' => "-2",
                    'err_msg' => "Failed To Verify Sign",
                ];
            }
            $order_id = $params['order_id'];
            $code = $params['code'];
            $result = $params['result'];
            $req_order = empty($params['req_order']) ? "" : $params['req_order'];
            $sign = $params['sign'];
            
            $withdrawInfo = FinancialManReview::ticket_id($order_id);
            if(empty($withdrawInfo)) {
                Yii::info("no withdraw info for order:".$order_id);
                return [
                    'code' => "-1",
                    'err_msg' => "no withdraw info",
                ];
            }
            $withdrawInfo->status = intval($code) == 0 ? FinancialManReview::UMP_CMB_PAYING : FinancialManReview::UMP_PAYING;
            $withdrawInfo->save();
            return [
                'code' => "0",
                'err_msg' => "success",
            ];
        } catch(Exception $e) {
            Yii::error("Update Withdraw Info Failed actionCmbPayNotify:".$e->getMessage());
            return [
                'code' => "-1",
                'err_msg' => $e->getMessage(),
            ];
        }
    }
}
