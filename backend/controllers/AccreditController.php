<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/12
 * Time: 14:05
 */

namespace backend\controllers;
use common\api\RedisQueue;
use common\exceptions\CodeException;
use common\exceptions\UserExceptionExt;
use common\models\AccreditRecord;
use common\models\AccreditRecordDetail;
use common\models\CardInfo;
use common\models\CreditJxlQueue;
use common\models\UserCreditLog;
use common\models\LoanPerson;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserVerification;
use common\services\MessageService;
use common\helpers\StringHelper;
use Yii;
use yii\base\Exception;
use common\helpers\TimeHelper;
use common\helpers\Util;
use credit\components\ApiUrl;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\data\Pagination;
use common\models\ContentActivity;
use common\models\ChannelOrderData;

class AccreditController extends BaseController{


    public function actionIndex(){
        $user_id = Yii::$app->request->get('user_id');
        $info = null;
        if($user_id){
            $info = AccreditRecord::find()->where(['user_id'=>$user_id])->one();
        }
        return $this->render('index',[
            'info'=>$info,
            'user_id'=>$user_id
        ]);

    }

    public function actionLogin(){

        $id = Yii::$app->request->get('id');
        $user_id = Yii::$app->request->get('user_id');

        return $this->render('login',[
            'id'=>$id,
            'user_id'=>$user_id
        ]);


    }
    public function actionAddInfo(){
        try{
            $user_name = Yii::$app->request->post('user_name');
            if(!$user_name){
                throw new Exception('用户名不能为空');
            }
            $password = Yii::$app->request->post('password');
            if(!$password){
                throw new Exception('密码不能为空');
            }
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $type_id = Yii::$app->request->post('type_id');
            $user_id = Yii::$app->request->post('user_id');

            $user = AccreditRecord::find()->where(['user_id'=>$user_id])->one();
            if(!$user){
                $detail = new AccreditRecordDetail();
                $model = new AccreditRecord();
                $model->user_id = $user_id;
                $detail->user_name = $user_name;
                $detail->password = $password;
                $detail->user_id = $user_id;
                switch($type_id){
                    case '1':
                        $model->sy_status = 1;
                        $detail->type = 1;
                        break;
                    case '2':
                        $model->xyqb_status = 1;
                        $detail->type = 2;
                        break;
                    case '3':
                        $model->ppd_status = 1;
                        $detail->type = 3;
                        break;
                    case '4':
                        $model->yqb_status = 1;
                        $detail->type = 4;
                        break;
                    case '5':
                        $model->sjd_status = 1;
                        $detail->type = 5;
                        break;
                    case '6':
                        $model->xjbs_status = 1;
                        $detail->type = 6;
                        break;
                    case '7':
                        $model->dkw_status = 1;
                        $detail->type = 7;
                        break;
                }

                if( $model->save() && $detail->save()){
                    echo json_encode(['status'=>1,'message'=>'点亮成功']);
                    $transaction->commit();
                }else{
                    echo json_encode(['status'=>0,'message'=>'点亮失败']);
                    $transaction->rollBack();
                }
            }else {
                $detail = new AccreditRecordDetail();
                $detail->user_name = $user_name;
                $detail->password = $password;
                $detail->user_id = $user_id;
                switch ($type_id) {
                    case '1':
                        $user->sy_status = 1;
                        $detail->type = 1;
                        break;
                    case '2':
                        $user->xyqb_status = 1;
                        $detail->type = 2;
                        break;
                    case '3':
                        $user->ppd_status = 1;
                        $detail->type = 3;
                        break;
                    case '4':
                        $user->yqb_status = 1;
                        $detail->type = 4;
                        break;
                    case '5':
                        $user->sjd_status = 1;
                        $detail->type = 5;
                        break;
                    case '6':
                        $user->xjbs_status = 1;
                        $detail->type = 6;
                        break;
                    case '7':
                        $user->dkw_status = 1;
                        $detail->type = 7;
                        break;
                }
                if( $user->save() && $detail->save()){
                    if($user['sy_status'] && $user['xyqb_status'] && $user['ppd_status'] && $user['yqb_status'] && $user['sjd_status'] && $user['xjbs_status'] && $user['dkw_status']){
                        UserVerification::updateAll(['real_accredit_status'=>1],['user_id'=>$user_id]);
                    }
                    echo json_encode(['status'=>1,'message'=>'点亮成功']);
                    $transaction->commit();
                }else{
                    echo json_encode(['status'=>0,'message'=>'点亮失败']);
                    $transaction->rollBack();
                }
            }

        }catch(Exception $e){
            $transaction->rollBack();
            echo json_encode(['status'=>0,'message'=>$e->getMessage()]);
        }
    }


}
