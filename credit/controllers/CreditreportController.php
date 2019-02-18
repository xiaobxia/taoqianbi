<?php
namespace credit\controllers;

use common\base\LogChannel;
use common\helpers\Util;
use common\services\ZmopService;
use yii\web\HttpException;
use yii\web\Response;
use Yii;
use common\models\LoanPerson;
use common\models\CreditZmop;
use common\services\UserService;
use yii\filters\AccessControl;
use common\exceptions\UserExceptionExt;
use common\models\UserVerification;
use credit\components\ApiUrl;
use yii\base\Exception;
use yii\helpers\Url;
use common\helpers\CommonHelper;
use common\models\CreditRealTime;

class CreditreportController extends BaseController {
    protected $userService;

    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['h5-call-back'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 与口袋理财的芝麻信用数据返回接口
     *
     * @name    与口袋理财的芝麻信用数据返回接口 [creditZmAuthorizeStatus]
     * @uses    用于接收芝麻信用短信授权的数据
     * @method  post
     * @param   string $params 数据参数
     * @param   string $sign 验签信息
     * @author  wangcheng
     */
    public function actionZmMessageResult() {
        throw new HttpException(500);

        $curren_time = time();
        $params = $this->request->post('params');
        $sign = base64_decode($this->request->post('sign'));
        $public_key_file = Yii::getAlias('@common/config/cert/kdlc/key/public_key.pem');
        $pu_key = file_get_contents($public_key_file);
        $result = openssl_public_decrypt(base64_decode($params),$decrypted,$pu_key);
        if (! $result) {
            return UserExceptionExt::throwCodeAndMsgExt('解密失败');
        }
        if (sha1($decrypted) != $sign) {
            return UserExceptionExt::throwCodeAndMsgExt( '验签失败' );
        }

        $data = json_decode($decrypted,true);
        $id = intval($data['id']);
        $open_id = $data['open_id'];
        $type = intval($data['type']);
        $time = intval($data['time']);
        $time_diff = $curren_time - $time;
        //判断参数的生效时间
        if ( ($time_diff <= 300) && ($time_diff >= -300) ) {
            $loanPerson = LoanPerson::findOne($id);
            if (is_null($loanPerson)) {
                return UserExceptionExt::throwCodeAndMsgExt('借款人不存在', [
                    'mongo' => true,
                    'content' => [
                        'param' => $data,
                    ]
                ]);
            }

            $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$id]);
            if (is_null($creditZmop)) {
                $creditZmop = new CreditZmop();
            }
            $creditZmop->person_id = $id;
            $creditZmop->open_id = $open_id;
            $creditZmop->status = CreditZmop::STATUS_1;
            $creditZmop->id_number = $loanPerson['id_number'];
            $creditZmop->type = $type;
            $ret = $creditZmop->save();
            if ($ret) {
                return [
                    'code' => 0,
                    'message' => 'success'
                ];
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt('数据保存失败',[
                    'mongo' => true,
                    'content' => [
                        'param' => $data,
                    ]
                ]);
            }
        }
        else {
            return UserExceptionExt::throwCodeAndMsgExt('参数失效',[
                'mongo' => true,
                'content' => [
                    'param' => $data,
                    'curren_time' => $curren_time
                ]
            ]);
        }
    }

    /**
     * 芝麻信用授权URL
     *
     * @name    芝麻信用授权URL
     * @uses    获取芝麻信用授权跳转地址
     * @method  get
     */
    public function actionZmVerifyUrl() {
        $this->response->format = Response::FORMAT_JSON;

        /* @var LoanPerson $user */
        $user = \yii::$app->user->identity;
        if ( empty($user) || (! $user->isRealVerify) ) {
            return CommonHelper::resp([], -1, '请先完成实名认证');
        }

        /* @var ZmopService $zmService */
        $zmService = Yii::$container->get('zmopService');
        return CommonHelper::resp([
            'url' => $zmService->zmAuthorize($user->id_number, $user->name, $user->id),
        ]);
    }

    /**
     * 芝麻信用授权客户端API
     *
     * @name    芝麻信用授权客户端API [creditZmMobileApi]
     * @uses    获取芝麻信用授权所需的参数
     * @method  post
     * @author  wangcheng
     */
    public function actionZmMobileApi(){
        $this->response->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        $id = intval($curUser->getId());

        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return UserExceptionExt::throwCodeAndMsgExt('用户不存在',[
                'mongo' => true,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }
        $userVer = UserVerification::find()->where(['user_id'=>$id])->one();
        if(is_null($userVer) || UserVerification::VERIFICATION_VERIFY != $userVer->real_verify_status){
            return UserExceptionExt::throwCodeAndMsgExt('用户未实名',[
                'mongo' => true,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }
        if(1 == $userVer->real_zmxy_status){
            return UserExceptionExt::throwCodeAndMsgExt('芝麻信用已授权',[
                'mongo' => true,
                'code' => 11,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }
        $id_number = $loanPerson['id_number'];
        $name = $loanPerson['name'];
        $zmService = Yii::$container->get('zmopService');
        $sign = $zmService->generateSign($id_number,$name,$id);
        $params = $zmService->generateParams($id_number,$name,$id);
        $appid = $zmService->appId;
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'sign' => $sign,
                'params' => $params,
                'appid' => $appid,
                'id_number' => $id_number,
                'id' => $id
            ]
        ];

    }
    private function log($user_id,$msg){
        if($user_id == 2753760){
            \Yii::error($msg);
        }
    }
    /**
     * 芝麻信用客户端授权回调接口
     * @param   string $info 回调参数
     * @param   string $sign 回调校验
     * @name    芝麻信用客户端授权回调接口 [ZmMobileResultSave]
     * @uses    芝麻信用客户端授权回调接口
     * @method  post
     *
     * @author  wangcheng
     */
    public function actionZmMobileResultSave(){
        $this->response->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        $id = intval($curUser->getId());
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return UserExceptionExt::throwCodeAndMsgExt('用户不存在',[
                'mongo' => true,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }

        $params = $this->request->post('info');
        $sign = $this->request->post('sign');
        $zmopService = Yii::$container->get('zmopService');
        $result_arr = $zmopService->decodingResult($params,$sign);
        if(!$result_arr){
            $this->log($id, "验签失败:".$sign);
            return UserExceptionExt::throwCodeAndMsgExt('验签失败',[
                'mongo' => true,
                'content' => [
                    'param' => $params,
                    'sign' => $sign,
                    'id' => $id
                ]
            ]);
        }

        if($result_arr['success'] != 'true'){
            $error_message = '芝麻信用授权失败，请联系客服';
            if($result_arr['error_code'] == 'ZMCSP.mobile_unbound'){
                $error_message = '芝麻信用未绑定手机号';
            }
            $this->log($id, "验签失败:".$error_message);
            return UserExceptionExt::throwCodeAndMsgExt($error_message,[
                'mongo' => true,
                'content' => [
                    'result_arr' => $result_arr,
                    'id' => $id
                ]
            ]);
        }
        $open_id = $result_arr['open_id'];
        $state = explode(',',$result_arr['state']);
        $uid = $state[0];
//        $source = isset($state[1]) ? $state[1] : '';
        if($id != $uid){
            $this->log($id, "验签失败:回调信息不一致");
            return UserExceptionExt::throwCodeAndMsgExt('回调信息不一致',[
                'mongo' => true,
                'content' => [
                    'result' => $result_arr,
                    'id' => $id
                ]
            ]);
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$id]);
            if(is_null($creditZmop)){
                $creditZmop = new CreditZmop();
            }
            $creditZmop->person_id = $id;
            $creditZmop->open_id = $open_id;
            $creditZmop->status = CreditZmop::STATUS_1;
            $creditZmop->id_number = $loanPerson['id_number'];
            $creditZmop->type = CreditZmop::TYPE_CLIENT;
            $creditZmop->app_id = $zmopService->appId;
            $ret = $creditZmop->save();
            if(!$ret){
                throw new \Exception('芝麻信用表保存失败');
            }
            $userVerification = UserVerification::find()->where(['user_id'=>$id])->one();
            $userVerification->real_zmxy_status = 1;
            $ret =$userVerification->save();
            //芝麻信用认证时间
            if(!$ret){
                throw new \Exception('用户步骤验证表保存失败');
            }
            $transaction->commit();
        }catch(\Exception $e){
            $transaction->rollBack();
            $this->log($id, "验签失败:".$e->getMessage());
            return UserExceptionExt::throwCodeAndMsgExt('操作失败，请重试',[
                'mongo' => true,
                'content' => [
                    'result' => $result_arr,
                    'id' => $id,
                    'error'=>$e
                ]
            ]);
        }
        @$this->log($id, "操作成功:".$id.var_export($result_arr,true));
        return [
            'code' => 0,
            'message' => '授权成功',
            'data' => ''
        ];
    }

    /**
     * @name 获取芝麻信用授权URL
     */
    public function actionZmAuthorizeUrl() {
        $this->response->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        $id = intval($curUser->getId());
        $loanPerson = LoanPerson::findById($id);
        if(is_null($loanPerson)){
            return UserExceptionExt::throwCodeAndMsgExt('用户不存在',[
                'mongo' => true,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }

        // 未实名前不得调用芝麻信用
        $userVer = UserVerification::findOne(['user_id' => $id]);
        if(is_null($userVer) || UserVerification::VERIFICATION_VERIFY != $userVer->real_verify_status){
            return UserExceptionExt::throwCodeAndMsgExt('用户未实名',[
                'mongo' => true,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }
        if (1 == $userVer->real_zmxy_status) {
            return UserExceptionExt::throwCodeAndMsgExt('芝麻信用已授权',[
                'code' => 11,
                'content' => [
                    'param' => $id,
                ]
            ]);
        }
        $id_number = $loanPerson['id_number'];
        $name = $loanPerson['name'];
        $source_id = $this->getSource();
        $zmService = Yii::$container->get('zmopService');
        $zmService->setAppId( CreditZmop::getAppId($source_id) );

        //根据source dev 获取配置
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'url' => $zmService->zmAuthorize($id_number, $name, $id),
            ]
        ];
    }

    /**
     * 芝麻信用H5授权结果回调接口
     * @name 芝麻信用授权结果回调接口 [actionGetIdentifyResult]
     */
    public function actionH5CallBack() {
        $this->response->format = Response::FORMAT_HTML;

        try {
            $product = null;
            $params = Yii::$app->request->get("params");
            $sign = Yii::$app->request->get("sign");
            $source_id = $this->getSource();
            $zmopService = Yii::$container->get('zmopService');
            $zmopService->setAppId( CreditZmop::getAppId($source_id) );

            $result_arr = $zmopService->decodingResult($params, $sign);
            if (!$result_arr) {
                throw new \Exception('验签失败');
            }
            if ($result_arr['success'] != 'true') {
                $error_message = '芝麻信用授权失败，请联系客服';
                if ($result_arr['error_code'] == 'ZMCSP.mobile_unbound') {
                    $error_message = '芝麻信用未绑定手机号';
                }

                throw new \Exception($error_message);
            }

            //获取open id
            $open_id = $result_arr['open_id'];
            $state = explode(',', $result_arr['state']);
            $uid = $state[0];
            $product = $state[1];
            $callback_url = $state[2];
            $title = $state[3];
            $content = $state[4];
            $describe = $state[5];
            if (!in_array($product, array_keys(CreditZmop::$product_list))){
                throw new Exception('未知的业务类型', 0);
            }

            $loanPerson = LoanPerson::findOne($uid);
            if (is_null($loanPerson)) {
                throw new Exception('用户不存在', 0);
            }

            $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$uid]);
            if (is_null($creditZmop)) {
                $creditZmop = new CreditZmop();
            }
            $creditZmop->person_id = $uid;
            $creditZmop->open_id = $open_id;
            $creditZmop->status = CreditZmop::STATUS_1;
            $creditZmop->id_number = $loanPerson['id_number'];
            $creditZmop->type = CreditZmop::TYPE_H5;
            $creditZmop->app_id = $zmopService->appId;
            if (!$creditZmop->save()) {
                throw new Exception('数据保存失败',0);
            }

            //设置用户的芝麻信用授权为1
            $userVerify = $loanPerson->userVerification;
            if (!$userVerify) {
                throw new \Exception('缺少用户认证数据');
            }

            if (!$userVerify->real_zmxy_status) {
                $userVerify->updateAttributes(['real_zmxy_status' => 1]);
            }
            $view = Yii::$app->getView();
            $view->params['menu_add'] = $title;
            $color = $this->getColor($source_id);
            return $this->render('success-result', [
                'url' => $callback_url,
                'content' => $content,
                'describe' => $describe,
                'color' => $color,
                'img' => 'success_xybt.png',
                'jump' => 0,
            ]);
        }
        catch (\Exception $e) {
            \yii::error(sprintf('zm_callback_failed:%s', $e), 'zm-callback');
            $source = $this->getSource();
            $color = $this->getColor($source);
            return $this->render('failed-result', [
                'url' => '',
                'color' => $color,
            ]);
        }
    }
}
