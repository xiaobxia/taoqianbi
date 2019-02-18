<?php

namespace mobile\controllers;

use common\models\LoanPersonInfo;
use common\models\UserContact;
use common\models\UserCredit;
use common\models\UserRealnameVerify;
use common\models\UserVerification;
use Yii;
use common\helpers\TimeHelper;
use common\models\BankConfig;
use common\models\LoanPerson;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\db\Query;
use common\services\UserService;
use common\models\UserCaptcha;
use common\models\UserLoginLog;
use common\helpers\StringHelper;
use yii\web\Response;
use yii\captcha\CaptchaValidator;
use common\models\UserPhoneChange;
use common\models\UserRedis;
use common\helpers\Util;
use common\helpers\ArrayHelper;
use yii\helpers\Url;
use common\models\Company;
use common\helpers\ToolsUtil;
use common\models\UserDetail;
use common\helpers\MailHelper;
use common\models\UserProofMateria;
use yii\validators\FileValidator;
use common\exceptions\UserExceptionExt;
use yii\web\UploadedFile;

// require_once Yii::getAlias('@common/api/oss') . '/sdk.class.php';

/**
 * User controller
 */
class PictureController extends BaseController
{
    protected $userService;
    public $bucket = 'kd-attach';
    public $ossService;

    public function init()
    {
        parent::init();
//         require_once Yii::getAlias('@common/api/oss') . '/sdk_xjk.class.php';
        require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';
        $this->ossService = new \ALIOSS();
        $this->ossService->set_debug_mode(true);
        $this->bucket = DEFAULT_OSS_BUCKET;
    }



    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
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
                'except' => ['reg-get-code', 'reg-get-audio-code', 'register', 'login', 'logout',
                    'reset-pwd-code', 'verify-reset-password', 'reset-password', 'state', 'captcha','yirute-tid'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'testLimit' => 1,
                'height' => 35,
                'width' => 80,
                'padding' => 0,
                'minLength' => 4,
                'maxLength' => 4,
                'foreColor' => 0x444444,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     *
     * @name    删除单张图片 [pictureDeletePic]
     * @uses    删除单张图片
     * @method  post
     * @param integer $id 图片ID
     * @author  honglifeng
     */
    public function actionDeletePic(){
        $curUser = Yii::$app->user->identity;
        $user_id               =   $curUser->getId();

        $id = Yii::$app->request->post("id");
        $user_proof_materia = UserProofMateria::findOne(['id'=>$id,'user_id'=>$user_id]);
        if(false == $user_proof_materia){
            return UserExceptionExt::throwCodeAndMsgExt('该图片不存在');
        }
        $type = $user_proof_materia->type;
        if(!isset(UserProofMateria::$type[$type])){
            return UserExceptionExt::throwCodeAndMsgExt('该类型图片不存在');
        }
        $pic_name = trim($user_proof_materia->pic_name);
        if(empty($pic_name)){
            return UserExceptionExt::throwCodeAndMsgExt('图片名参数丢失');
        }

        switch($type){
            case UserProofMateria::TYPE_OTHER:
                $des_pic_url = 'ygb'.'/other/'.$user_id.'/'.$pic_name;
                break;
            case UserProofMateria::TYPE_ID_CAR:
                $des_pic_url = 'ygb'.'/idcard/'.$user_id.'/'.$pic_name;
                break;
            case UserProofMateria::TYPE_DIPLOMA_CERTIFICATE:
                $des_pic_url = 'ygb'.'/diplma_certificate/'.$user_id.'/'.$pic_name;
                break;
            case UserProofMateria::TYPE_WORK_PROVE:
                $des_pic_url = 'ygb'.'/work_prove/'.$user_id.'/'.$pic_name;
                break;
            case UserProofMateria::TYPE_SALARY_CERTIFICATE:
                $des_pic_url = 'ygb'.'/salary_certificate/'.$user_id.'/'.$pic_name;
                break;
            case UserProofMateria::TYPE_PROOF_of_ASSETS:
                $des_pic_url = 'ygb'.'/proof_of_assets/'.$user_id.'/'.$pic_name;
                break;
            default:
                return UserExceptionExt::throwCodeAndMsgExt('不能上传该类材料');
                break;
        }

        $bool_ss = $this->ossService->is_object_exist($this->bucket, $des_pic_url);
        $pic_status = intval($bool_ss->status);
        if($pic_status != 200){
            throw new UserException("删除图片有误，请重新操作");
        }
        if(!$user_proof_materia->delete()){
            return UserExceptionExt::throwCodeAndMsgExt('删除失败,请稍后再试');
        }
        //删除表里面的数据
        $response = $this->ossService->delete_object($this->bucket, $des_pic_url);
        if ($response->isOK()) {

            return [
                'code' => 0,
                'message' => '您好，删除成功！',
            ];
        } else {
            return [
                'code' => -1,
                'message' => '抱歉，删除失败！',
            ];
        }
    }

    /**
     *
     * @name    获得图片列表 [pictureGetPicList]
     * @uses    获得图片列表
     * @method  post
     * @param   string $type 1:身份证,2:学历证明,3:工作证明,4:薪资证明,5:财产证明,6、工牌照片、7、个人名片，8、银行卡 100:其它证明
     * @author  honglifeng
     */
    public function actionGetPicList(){
        $curUser = Yii::$app->user->identity;

        $user_id        =   $curUser->getId();
        $type                  = Yii::$app->request->post("type");
        if(!isset(UserProofMateria::$type[$type])){
            return UserExceptionExt::throwCodeAndMsgExt('图片类型选择错误');
        }
        $user_proof_materia = UserProofMateria::find()->where(['user_id'=>$user_id,'type'=>$type])->all();
        if(false === $user_proof_materia){
            return UserExceptionExt::throwCodeAndMsgExt('获取数据失败');
        }

        $data = array();
        foreach($user_proof_materia as $item){
            $data[] = [
                'id'=>$item['id'],
                'pic_name'=>$item['pic_name'],
                'type'=>$item['type'],
                'url'=>$item['url'],
            ];
        }
        return [
            'code' => 0,
            'message'=>'成功获取',
            'data'=>['item'=>[
                'type'=>$type,
                'max_pictures'=>UserProofMateria::$type_pic_max[$type],
                'title'=>UserProofMateria::$type[$type]['title'],
                'notice'=>UserProofMateria::$type[$type]['notice'],
                'data'=>$data,
             ],
            ],
        ];

    }

    /**
     * 上传图片
     * @name    上传图片 [pictureUploadImage]
     * @uses    上传图片
     * @method  post
     * @param   string $type 1:身份证,2:学历证明,3:工作证明,4:薪资证明,5:资产证明,6:工牌照片,7:个人名片,8:银行卡或者信用卡,9:好房贷提单房产证照片 100:其它证明
     * @param   file $attach 附件
     * @author  honglifeng
     */

    public function actionUploadImage(){
        $this->response->format = Response::FORMAT_JSON;
        $curUser = Yii::$app->user->identity;
        if(empty($curUser)){
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED],['code'=>CodeException::LOGIN_DISABLED]);
        }

        $user_id = $curUser->getId();
        if ($this->request->getIsPost()) {
            $type = UserProofMateria::TYPE_HFD_HOUSE_CERTIFICATE;

            //获取已经上传的照片数量，看看是否到达上线
            $user_proof_materia = UserProofMateria::find()->where(['user_id'=>$user_id,'type'=>$type]);
            if(false === $user_proof_materia){
                return UserExceptionExt::throwCodeAndMsgExt('上传出错');
            }
            $count = count($user_proof_materia);
            if($count >= UserProofMateria::$type_pic_max[$type]){
                return UserExceptionExt::throwCodeAndMsgExt('上传图片已经达到上限，请先删除原有的图片');
            }

           // 检查上传图片是否已达上线，达到上线，提示删除
            $image_info = UserProofMateria::find()->where(['user_id'=>$user_id,'type'=>$type])->all();
            $total = count($image_info);
            if($total >= UserProofMateria::$type_pic_max[$type]){
                return UserExceptionExt::throwCodeAndMsgExt("您已达到图片最大数量限制，请删除不合规范的图片后再上传！");
            }

            $aliOssService = Yii::$container->get('aliOssService');
            $path = "ygb/".$user_id;
            $params = ['name'=>'attach','type'=>YII_ENV_PROD ? 'yydb':$path];
            $condition = ['extensions'=>['jpg','jpeg', 'png', 'gif']];
            $ret = $aliOssService->attachmentAddByBase64($params,$condition);

            if($ret['code'] == 0){
                if(!$ret['file_urls']){
                    $data['code'] = -1;
                    $data['message'] = '请选择需要上传的图片或者图片文件过大';
                    return $data;
                }
                if(!isset($ret['file_urls'][0])){
                    $data['code'] = -1;
                    $data['message'] = '请选择需要上传的图片或者图片文件过大';
                    return $data;
                }
                $file_url = $ret['file_urls'][0];
                $filename = explode("/",$file_url);
                $filename = $filename[count($filename)-1];

                //保存到数据库
                $model = new UserProofMateria();
                $model->user_id = $user_id;
                $model->type = $type;
                $model->url = $file_url;
                $model->pic_name = $filename;
                $model->created_at = time();
                $model->updated_at = time();
                $model->status = UserProofMateria::STATUS_NORMAL;

                if($model->save()){
                    return  [
                        'code'=>0,
                        'message'=>'上传成功',
                        'data'=>['item'=>[]],
                    ];
                }else{
                    return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
                }
            }else{
                return [
                    'code'=>$ret['code'],
                    'message'=>$ret['msg']
                ];
            }



        }else{
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
    }


}