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
use common\models\CardInfo;
use common\models\CreditJxlQueue;
use common\models\OnlineBankErrorMsg;
use common\models\OnlineBankInfo;
use common\models\UserCreditLog;
use common\models\LoanPerson;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserVerification;
use common\services\MessageService;
use common\helpers\StringHelper;
use common\services\OnlineBankService;
use Yii;
use yii\base\Exception;
use common\helpers\TimeHelper;
use common\helpers\Util;
use credit\components\ApiUrl;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\data\Pagination;
use common\models\ContentActivity;

class PayrollCardController extends BaseController
{
    public $enableCsrfValidation = false;
    /**
     * @return string
     * @name 未认证银行卡列表
     */
    public function actionIndex()
    {
        $user_id = 89670721;
        $person  =  LoanPerson::findOne($user_id);

        if(!$person){
            throw new Exception('用户不存在');
        }
        $name = $person['name'];
        $id_card = $person['id_number'];
        if(!$id_card || !$name){
            throw new Exception('请先实名认证');
        }
        $mobile = $person['phone'];
        if(!$mobile){
            throw new  Exception('请绑定手机号');
        }
        $service = new OnlineBankService();
        $open_id = $service->getAuthOpenId($name,$id_card,$mobile);
        $bank_info = $service->getBankListInfo($open_id);
//        echo "<pre>";
//        print_r($bank_info);die;
        return $this->render('index', [
            'bank_info' => $bank_info,
            'user_id' => $user_id
        ]);


    }

    /**
     * @return string
     * @name 确认申请
     */
    public function actionApply()
    {
        $item = Yii::$app->request->get('item');

        $user_id = Yii::$app->request->get('user_id');
        $bank_id = Yii::$app->request->get('bank_id');
        $bank_name = Yii::$app->request->get('bank_name');
//        echo "<pre>";
//        print_r($item);die;
        $flag = false;
        $new_arr = [];
        if (isset($item['login_types'])) {
            foreach ($item['login_types'] as $value) {
                if(substr_count($value['title'],'信用卡')){
                    continue;
                }

                    $temp['name'] = $value['title'];
                    $temp['entry_id'] = $value['entry_id'];
                    $temp['login_valid'] = isset($value['inputs'][0]['valid']) ? $value['inputs'][0]['valid'] : "";
                    $temp['label'] = isset($value['inputs'][1]['label']) ? $value['inputs'][1]['label'] : "";
                    $temp['user_label'] = isset($value['inputs'][0]['label']) ? $value['inputs'][0]['label'] : "";
                    $temp['password_valid'] = isset($value['inputs'][1]['valid']) ? $value['inputs'][1]['valid'] : "";
                    $temp['desc'] = isset($value['inputs'][1]['desc']) ? $value['inputs'][1]['desc'] : "";
                    $temp['user_desc'] = isset($value['inputs'][0]['desc']) ? $value['inputs'][0]['desc'] : "";
                    $new_arr[] = $temp;
                    if (substr_count($value['title'], '储蓄') || substr_count($value['title'], '信用')) {
                        $flag = true;
                    }

            }
        }

        return $this->render('apply', [
            'flag' => $flag,
            'types' => $new_arr,
            'user_id' => $user_id,
            'bank_id' => $bank_id,
            'bank_name' => $bank_name
        ]);

    }

    /**
     * @return string
     * @name 绑卡列表
     */

    public function actionMyCard()
    {

        $currentUser = Yii::$app->user->identity;
        $user_id = 1011907;
        $mycard = OnlineBankInfo::find()->select('bank_id,bank_name,bank_num')->distinct()->where(['status'=>10,'user_id'=>$user_id])->all();

        return $this->render('my-card', [
           'data'=>$mycard
        ]);

    }

    /**
     * @return string
     * @name 授权页面
     */

    public function actionAuthority()
    {

        return $this->render('authority', [

        ]);

    }

    /**
     * @return string
     * @name 发起请求
     */

    public function actionRequest()
    {

       try{
           $user_name = Yii::$app->request->post('user_name');
           $password = Yii::$app->request->post('password');
           $entry_id = Yii::$app->request->post('entry_id');
           $user_id = Yii::$app->request->post('user_id');
           $bank_id = Yii::$app->request->post('bank_id');
           $bank_name = Yii::$app->request->post('bank_name');
           $type =1;

           $loan_person = LoanPerson::findOne($user_id);
           //print_r($loan_person);die;
           $id_number = $loan_person['id_number'];
           $name = $loan_person['name'];
           $id_number = '52252619931001261X';
           $name = '王萌';
           if(!$id_number || !$name ){
               throw new Exception('用户未实名');
           }
           $phone = $loan_person['phone'];
           $service = new OnlineBankService();

           $repeat = $service->getIsRepeat($user_id,$bank_id,$user_name);

           if($repeat){
               echo json_encode(['status'=>50,'msg'=>'该卡片已经认证通过']);die;
           }


           $auth_result = $service->getAuthUpload($entry_id, $user_name, $name, $id_number, $user_id,$phone, $bank_id,$type,$bank_name);

          // var_dump($auth_result);die;

           if ($auth_result) {

               $open_id = $service->checkLogin($user_name, $name, $id_number, $phone, $entry_id, $password);
               if(!$open_id){
                   throw new Exception('账号或密码错误');
               }
               if ($open_id) {
                   $res = $service->getState($open_id,$user_id);
                   return  $res;
               }
           }else{
               throw new Exception('授权认证失败');
           }
       }catch(Exception $e){

          return ['status'=>100,'msg'=>$e->getMessage()];

       }

    }

    /**
     * @return string
     * @name 最终校验
     */
    public function actionSendCode()
    {
        $code = Yii::$app->request->post('code');
        $open_id = Yii::$app->request->post('open_id');

        $service = new OnlineBankService();
        $res = $service->getFinalState($open_id, $code);

        echo json_encode(['status' => $res]);
    }

    /**
     * @return string
     * @name 获取轮询结果
     */
    public function actionGetState()
    {
        try{
            $open_id = Yii::$app->request->post('open_id');
            $user_id = Yii::$app->request->post('user_id');
          //  print_r($user_id);die;
            $service = new OnlineBankService();

            $res = $service->getState($open_id,$user_id);
            echo json_encode(['status' => $res['data']['state']]);
        }catch(Exception $e){
            echo json_encode(['status'=>100,'msg'=>$e->getMessage()]);

        }

    }
    /**
     * @return string
     * @name 提交错误信息
     */
    public function actionGetProcessMessage(){
        $user_id = Yii::$app->request->post('user_id');
        $message = Yii::$app->request->post('message');
        $type = Yii::$app->request->post('type');

        $msg_str = json_encode(['user_id'=>$user_id,'message'=>$message,'type'=>$type]);
        RedisQueue::push([OnlineBankErrorMsg::LIST_GET_ONLINE_BANK_INFO_MESSAGE,$msg_str]);

    }


}
