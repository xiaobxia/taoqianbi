<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2016/3/14
 * Time: 14:51
 */

namespace mobile\controllers;

use Yii;
use common\models\LoanPerson;
use common\models\UserLevel;
use common\models\UserLevelMission;
use yii\base\Exception;
use yii\base\UserException;
use yii\filters\AccessControl;
use common\services\UserService;
use common\models\User;
use common\models\UserCaptcha;
use common\helpers\StringHelper;
use yii\web\Response;
use common\models\UserPhoneChange;
use yii\validators\FileValidator;
use yii\helpers\Url;
use common\services\ZmopService;
use common\models\CreditZmop;



class UserController extends  BaseController
{

    protected $userService;
    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module,UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 重新指定跳转登录页面
     */
    public function init()
    {
        parent::init();
        // other init
        //指定跳app登录页
    }



    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['register-phone', 'user-login','reg-phone','reg-get-code','register','checkperson'],
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
     * 注册
     * @return string
     */
    public function actionRegisterPhone()
    {
        $this->view->title = '注册-口袋快借';
        $user_phone = $this->request->get('user_phone');
        return $this->render('user-register-phone',[
            'user_phone' => $user_phone ? \yii\helpers\Html::encode($user_phone) : '',
        ]);
    }

    /**
     * 手机号丢失
     * @return string
     * @throws UserException
     */
    public function actionRegPhone()
    {

        $params = $this->request->get();
        if(!isset($params["phone_number"]) || !$params["phone_number"]){
            throw new UserException("手机号丢失！");
        }
        $this->view->title = '注册-口袋快借';
        return $this->render('user-register-message',[
            'phone_number' => \yii\helpers\Html::encode($params['phone_number']),
            'anni_lottery' => isset($params["anni_lottery"]) ? intval($params["anni_lottery"]) : 0 // 周年庆大转盘活动特殊处理
        ]);
    }

    public function actionCheckperson()
    {
        $this->response->format = Response::FORMAT_JSON;
        $username = trim($this->request->post('username'));
        $user_person = User::findByPhone($username);
        if ($user_person)
        {
            //查询借款用户表里面是否有数据
            if(LoanPerson::findByPhone($username))
            {
                return ['code'=>0,'message'=>'success'];
            }

        }
        else if (User::findOne(['phone' => $username, 'status' => User::STATUS_DELETED]))
        {
            return ['code'=>1002,'message'=>'该手机号已禁用'];
        } else if ($phone_change = UserPhoneChange::find()->where([
            'cur_phone' => $username,
            'result' => UserPhoneChange::CHANGE_RESULT_SUCC,
            'change_type' => UserPhoneChange::CHANGE_TYPE_ACCOUNT_PHONE,
        ])->orderBy('id desc')->one())
        {
            return ['code'=>1003,'message'=>'您的账号已变更为' . StringHelper::blurPhone($phone_change['new_phone'])];

        }else{
            return ['code'=>-1,'message'=>'该用户不存在'];
        }
        //插入一条新的借款人
        try{
            $user_id = $user_person['id'];
            $loan_person= UserService::registerByLoanphone($username,$user_id);
        }catch(\Exception $e){
            return ['code'=>-1,'message'=>'登录失败请稍后再试'];
        }

        return ['code'=>0,'message'=>'success'];
		

    }


    public function actionUploadPicture($loan_record_id = "", $type = "", $column = "", $handle = "")
    {
        header('Content-type:text/json');
        $curUser = Yii::$app->user->identity;//验证登录态

        if($this->request->getHeaders())
        {
            $loan_person_id = $this->request->get('loan_person_id');//借款人ID
            $loan_person_id = addslashes($loan_person_id);
            //验证借款人是否存在
            $ret = LoanPerson::findById($loan_person_id);
            if(empty($ret))
            {
                $result['err_msg'] =  "该借款人不存在";
               // Yii::error("该借款人不存在");
                return $result;
            }

            $image_type = $this->request->get('type');//字段类型,身份正面、反面、合影、工作证
            $image_type = addslashes($image_type);

            if(!isset(LoanPerson::$person_image_type[$image_type]))
            {
                $result['err_msg'] =  "图片类型错误";
                return $result;
            }

            $max_num = $this->request->get('max_num');//最多图片上传张数
            $max_num = addslashes($max_num);
            if($max_num >LoanPerson::$person_image_max_num[$image_type])
            {
                $result['err_msg'] =  "图片类型错误";
                return $result;
            }

//            LoanPerson::$person_image_max_num[$image_type]


        }
        else
        {
            $result['err_msg'] =  "系统繁忙，请重新操作";
            return $result;
        }
    }

    /**
     * 上传图片
     * @return array
     */
    public function actionUpImageByone()
    {
        header('Content-type:text/json');
        $curUser = Yii::$app->user->identity;
        if(empty($curUser)){
            Yii::error(" method:".__METHOD__." line:".__LINE__." 没有登录");
            $result = [
                'code' => 0,
                'success' => false,
                'file' => "",
                'err_msg' => '抱歉，您当前未登录！'
            ];
            return $result;
        }
        if(NULL == $this->request->get('loan_person_id'))
        {
            Yii::error(" method:".__METHOD__." line:".__LINE__." loan_person_id参数丢失");
            $result = [
                'err_msg' => '参数丢失'
            ];
            return $result;
        }
        $loan_person_id = $this->request->get('loan_person_id');//借款人ID
        $loan_person_id = addslashes($loan_person_id);
        //验证借款人
        $ret = LoanPerson::findById($loan_person_id);
        if(empty($ret))
        {
            Yii::error(" method:".__METHOD__." line:".__LINE__." 借款人不存在");
            $result = [
                'err_msg' => '借款人不存在'
            ];
            return $result;
        }


        if($this->request->getHeaders())
        {
            $file = UploadedFile::getInstanceByName('attach');
            $validator = new FileValidator();
            $validator->extensions = ['jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF'];
            $validator->maxSize = 2 * 1024 * 1024;
            $validator->checkExtensionByMimeType = false;
            if (!$validator->validate($file, $error)) {
                Yii::error(" method:".__METHOD__." line:".__LINE__." 文件不符合要求，".$error);
                $result['err_msg'] =  "文件不符合要求，".$error;
                return $result;
            }
            $charid = strtoupper(md5(uniqid(mt_rand(), true)));
            $filename = $loan_person_id."_".substr($charid, 7, 13);
            $object = 'loan_person_id'.'/'.$loan_person_id.'/'.$filename.'.'.$file->extension;
            $filename_extension = $filename.'.'.$file->extension;

            $file_path = $file->tempName;
            $response = $this->ossService->upload_file_by_file($this->bucket, $object, $file_path);
            if ($response->isOK())
            {
                $file_url = 'http://res.kdqugou.com/'.$object;
                $result['err_msg'] =  "";
                $result['code'] =  1;
                $result['success'] =  true;
                $result['file'] =  $file_url;
            } else {
                Yii::error(" method:".__METHOD__." line:".__LINE__." 系统繁忙，请重新操作");
                $result['err_msg'] =  "系统繁忙，请重新操作";
                return $result;
            }

        }
        else{
            Yii::error(" method:".__METHOD__." line:".__LINE__." 系统繁忙，请重新操作");
            $result['err_msg'] =  "系统繁忙，请重新操作";
            return $result;
        }
    }


    /**
     * 登录页面
     * @return string
     */
    public function actionUserLogin()
    {
        $this->view->title = '登录-口袋快借';
        $fresh_user = $this->request->get('fresh_user');
        $bbs_mobile = $this->request->get('bbs_mobile');
        $redirect_url = $this->request->get('redirect_url','');
        if ($bbs_mobile && isset($_SERVER['HTTP_REFERER']))
        {
            $redirect_url = $_SERVER['HTTP_REFERER'];
            $redirect_url = urlencode($redirect_url);
        }
        else if ($redirect_url)
        {
            $redirect_url = urlencode($redirect_url);
        }
        return $this->render('user-login',[
            'fresh_user' => $fresh_user ? $fresh_user : 0,
            'bbs_mobile_redirect' => isset($redirect_url) ? $redirect_url : "",
            'bbs_mobile' => $bbs_mobile,
       ]);
    }


    /**
     * 注册步骤一：手机号获取验证码
     *
     * @name    获取注册验证码 [userRegGetCode]
     * @uses    用户注册是拉取验证码
     * @method  post
     * @param   string $phone 手机号
     * @author  kevin
     */
    public function actionRegGetCode()
    {
        $this->response->format = Response::FORMAT_JSON;
        $phone = trim($this->request->post('phone'));

        //查询借款用户表里面是否有数据
        if(LoanPerson::findByPhone($phone))
        {
            return ['code'=>1001,'message'=>'该手机号已注册'];
        }
		
        if ($this->userService->generateAndSendCaptcha(trim($phone), UserCaptcha::TYPE_REGISTER))
        {

            return [
                'code' => 0,
                'message' => 'success'
            ];
        }
        else
        {
            return ['code'=>1004,'message'=>'发送验证码失败，请稍后再试'];
        }
    }

    /**
     * 注册步骤二：验证手机号获和验证码，并设置登录密码
     *
     * @name 注册 [UserRegister]
     * @method  post
     * @param string $phone 手机号
     * @param string $code 验证码
     * @param string $password 密码
     * @param string $name  姓名
     */
    public function actionRegister()
    {

        $this->response->format = Response::FORMAT_JSON;

        $phone = trim($this->request->post('phone'));
        $code = trim($this->request->post('code'));
        $password = $this->request->post('password');
        $name = "";
        if(NULL != $this->request->post('name')){
            $name = $this->request->post('name');
        }

        if (!$this->userService->validatePhoneCaptcha($phone, $code, UserCaptcha::TYPE_REGISTER)) {
            return ['code'=>1000,'message'=>'验证码错误或已过期'];
        } else {
            try{
                $user = $this->userService->registerByPhone($phone, $password);
            }catch (\Exception $e){
                $code = $e->getCode();
                return ['code'=>$code,'message'=>$e->getMessage()];

            }


            $registerInfo = $user->registerInfo;
            if ($user) {
                //注册借款人
                $user_id = $user->id;

                // 插入用户注册或绑卡成功队列（注册消息太多，等优化之后再加）
                $this->userService->pushUserMessageList(UserService::USER_REGISTER,$user->id);

                // 重新查一下，避免很多字段为null
                $user = LoanPerson::findByPhone($phone);
                if(!empty($name)){
                    $user->name = $name;
                    $user->save();
                }
                UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_REGISTER]);


                return [
                    'code' => 0,
                    'user' => [
                        'uid' => $user->id,
                        'username' => $user->username,
                        'realname' => $user->name,
                        'id_card' => $user->id_number,
                        'real_verify_status' => $user->is_verify,
                        //'card_bind_status' => $user->card_bind_status,
                       // 'set_paypwd_status' => $user->userPayPassword ? 1 : 0,
                       // 'is_novice' => $user->is_novice,
                        'user_sign'=> $user->auth_key,
                    ],
//                    'account' => [
//                        'lastday_profits_date' => strtotime('-1 day'),
//                        'lastday_profits' => $user->account->getLastdayProfits(),
//                        'total_profits' => $user->account->total_profits,
//                        'total_money' => $user->account->total_money,
//                        'hold_money' => $user->account->getTotalHoldMoney(),
//                        'remain_money' => $user->account->usable_money + $user->account->withdrawing_money,
//                        'trade_count' => $user->getInvestCount(),
//                    ]
                    'sessionid' => Yii::$app->session->getId(),
                ];
            } else {
                return ['code'=>1003,'message'=>'注册失败，请稍后重试'];
               // throw new UserException('注册失败，请稍后重试');
            }
        }
    }

    public function actionUserRealNameVertify()
    {
        $this->view->title = '实名认证-口袋快借';
        return $this->render('user-real-name-vertify');
    }

    public function actionUserSetPayPassword()
    {
        $this->view->title = '设置交易密码-口袋快借';
        return $this->render('user-set-pay-password');
    }

    public function actionChangePwd()
    {
        $this->view->title = '修改登录密码-口袋快借';
        return $this->render('change-pwd');
    }

    public function actionChangePaypassword()
    {
        $this->view->title = '修改交易密码-口袋快借';
        return $this->render('change-paypassword');
    }

    public function actionBindCard()
    {
        $this->view->title = '绑定银行卡-口袋快借';
        return $this->render('bind-card');
    }
    
    /**
     * 校验交易密码是否正确
     * @return number[]|string[]
     */
    public function actionCheckPayPwdPost(){
        $this->response->format = Response::FORMAT_JSON;
        $password = trim($this->request->post('password'));
        if( !$password ){
            return ['code'=>1,'message'=>'请先输入交易密码'];
        }
        $curUser = Yii::$app->user->identity;
        if(!$curUser->validatePayPassword($password) ){
            return ['code'=>1,'message'=>'输入交易密码不正确'];
        }
        $user_id = Yii::$app->user->id;
        $time = time();
        $sign = md5("uid={$user_id}&pwd={$password}&time={$time}");
        $ret = Yii::$app->redis->executeCommand('SET', [\common\models\UserPayPassword::PAY_PWD_CHECK_KEY."_{$user_id}", $sign, 'EX', 60]);
        return [
            'code' => 0,
            'sign' => $sign,
            'ret' => $ret,
        ];
    }
    
     /**
     * 芝麻授信 需要先登录
     */
    public function actionZmAuthorize()
    {
        $user = Yii::$app->user->getIdentity();
        if (!$user->phone) {
            throw new \Exception('找不到你的手机号');
        }
        $zmop_service = new ZmopService;
        $state = $user->id . ',' . CreditZmop::PRODUCT_XJK_M;
        $authorize_url = $zmop_service->h5AuthorizeUrl($user->phone, $state);
        return $this->redirect($authorize_url);
    }
}