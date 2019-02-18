<?php
/**
 * Created by PhpStorm.
 * User: xiaolongliu
 * Date: 2018/10/25
 * Time: 16:26
 */
namespace backend\controllers;

use Yii;
use common\models\UserLoanOrder;
use common\models\PhoneReviewLog;

class PhoneReviewController extends  BaseController{
    /**
     * return string
     * @name 借款列表-查看-新增联系/actionPhoneReviewLogAdd
    **/
    public function actionPhoneReviewLogAdd(){
        $get = Yii::$app->request->get();
        if($get['order_id'] && $get['user_id'] && $get['remark'] && $get['time']){
            //订单id
            $order_id=trim($get['order_id']);
            //用户id
            $user_id=trim($get['user_id']);
            //联系内容
            $remark=addslashes(trim($get['remark']));
            //联系时间
            $time=addslashes(trim($get['time']));
            //审核人
            $operator_name=Yii::$app->user->identity->username;
            //判断订单是否存在
            $order=UserLoanOrder::findOne($order_id);
            if(!$order){
                return json_encode(['code'=>500]);
            }
            $order_user_id=$order->user_id;
            if($user_id!=$order_user_id){
                return json_encode(['code'=>500]);
            }
            $PhoneReviewLog=new PhoneReviewLog();
            $PhoneReviewLog->user_id=$user_id;
            $PhoneReviewLog->order_id=$order_id;
            $PhoneReviewLog->operator_name=$operator_name;
            $PhoneReviewLog->time=strtotime($time);
            $PhoneReviewLog->remark=$remark;
            if($PhoneReviewLog->save()){
                return json_encode(['code'=>0]);
            }
            return json_encode(['code'=>500]);
        }
    }
}