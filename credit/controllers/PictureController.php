<?php

namespace credit\controllers;

use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use common\services\UserService;
use common\models\UserProofMateria;
use yii\validators\FileValidator;
use common\exceptions\UserExceptionExt;
use yii\web\UploadedFile;

require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';

/**
 * User controller
 */
class PictureController extends BaseController {
    protected $userService;

    public $bucket = DEFAULT_OSS_BUCKET;

    public $ossService;

    public function init() {
        parent::init();

        $this->ossService = new \ALIOSS();
        $this->ossService->set_debug_mode(true);
    }

    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = []) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                // 除了下面的action其他都需要登录
                'except' => [
                    'reg-get-code', 'reg-get-audio-code', 'register', 'login', 'logout', 'captcha',
                    'reset-pwd-code', 'verify-reset-password', 'reset-password', 'state', 'yirute-tid',
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions() {
        return [
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
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
     * @name    删除单张图片 [pictureDeletePic]
     * @uses    删除单张图片
     * @method  post
     * @param integer $id 图片ID
     * @author  honglifeng
     */
    public function actionDeletePic(){
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $id = Yii::$app->request->post("id");
        if (UserProofMateria::deletePicById($user_id,$id)) {
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
     * @param   string $type 1:身份证,2:学历证明,3:工作证明,4:薪资证明,5:财产证明,6、工牌照片、7、个人名片，8、银行卡,10、人脸识别,11:身份证正面,12:身份证反面 100:其它证明
     * @author  honglifeng
     */
    public function actionGetPicList(){
        $curUser = Yii::$app->user->identity;

        $user_id        =   $curUser->getId();
        $type      = Yii::$app->request->post("type");
        if(!isset(UserProofMateria::$type[$type])){
            return UserExceptionExt::throwCodeAndMsgExt('图片类型选择错误');
        }
        $user_proof_materia = UserProofMateria::findAllByType($user_id, $type);

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
     * @param   string $type 1:身份证,2:学历证明,3:工作证明,4:薪资证明,5:资产证明,6:工牌照片,7:个人名片,8:银行卡或者信用卡,9:好房贷房产证,10:人脸识别,11:身份证正面,12:身份证反面,100:其它证明
     * @param   file $attach 附件
     * @author  honglifeng
     */
    public function actionUploadImage(){

        $curUser = Yii::$app->user->identity;
        if(empty($curUser)){
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED],['code'=>CodeException::LOGIN_DISABLED]);
        }

        $user_id = $curUser->getId();
        if ($this->request->getIsPost()) {
            try {
                $type = intval($this->request->post('type', 0));
                if (empty($type)) {
                    return UserExceptionExt::throwCodeAndMsgExt('参数丢失');
                }
                if (!isset(UserProofMateria::$type[$type])) {
                    return UserExceptionExt::throwCodeAndMsgExt('不能上传该类材料');
                }
                // 针对安卓拍照上传的修改
                $ocrtype_val = intval($this->request->post('ocrtype', 0));
                $ocrtype = empty($ocrtype_val) ? 0 : $ocrtype_val ;

                //检查上传图片是否已达上线，达到上线，提示删除
                $total = UserProofMateria::getPicCount($user_id,$type);
                if ($total >= UserProofMateria::$type_pic_max[$type]) {
                    return UserExceptionExt::throwCodeAndMsgExt("您已达到图片最大数量限制，请删除不合规范的图片后再上传！");
                }

                $file = UploadedFile::getInstanceByName('attach');
                $validator = new FileValidator();
                $validator->extensions = ['jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF'];
                $validator->maxSize = 10 * 1024 * 1024;
                $validator->checkExtensionByMimeType = false;
                $error = "";
                if (!$validator->validate($file, $error)) {
                    return UserExceptionExt::throwCodeAndMsgExt("文件不符合要求，" . $error);
                }

                $charid = strtoupper(md5(uniqid(mt_rand(), true)));
                $filename = substr($charid, 7, 13);

                $object = $this->handleObject($user_id,$type,$filename,$file->extension);
                if (empty($object)) {
                    return UserExceptionExt::throwCodeAndMsgExt('不能上传该类材料');
                }

                $file_path = $file->tempName;
                $response = $this->ossService->upload_file_by_file($this->bucket, $object, $file_path);
                if ($response->isOK()) {
                    $file_url    = OSS_RES_URL_OUTSIDE .  $object;

                    //保存到数据库
                    $model = new UserProofMateria();
                    $model->user_id = $user_id;
                    $model->type = $type;
                    $model->url = $file_url;
                    $model->pic_name = $filename . '.' . $file->extension;
                    $model->created_at = time();
                    $model->updated_at = time();
                    $model->status = UserProofMateria::STATUS_NORMAL;
                    $model->ocr_type = $ocrtype;

                    if ($model->save()) {
                        return [
                            'code' => 0,
                            'message' => '上传成功',
                            'data' => ['item' => [
                                'url' => $file_url
                            ]],
                        ];
                    } else {
                        return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
                    }
                } else {
                    return UserExceptionExt::throwCodeAndMsgExt('上传失败2,请稍后再试');
                }

            }catch (\Exception $e){
                return UserExceptionExt::throwCodeAndMsgExt('上传失败,请稍后再试');
            }
        }else{
            return UserExceptionExt::throwCodeAndMsgExt('系统繁忙,请稍后再试');
        }
    }

    /**
     * 处理用户
     */
    private function handleObject($user_id,$type,$filename,$extension){
        $object = '';
        switch ($type) {
            case UserProofMateria::TYPE_OTHER:
                $object = 'jsqb' . '/other/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_ID_CAR_Z:
            case UserProofMateria::TYPE_ID_CAR_F:
            case UserProofMateria::TYPE_ID_CAR:
                $object = 'jsqb' . '/idcard/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_DIPLOMA_CERTIFICATE:
                $object = 'jsqb' . '/diplma_certificate/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_WORK_PROVE:
                $object = 'jsqb' . '/work_prove/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_SALARY_CERTIFICATE:
                $object = 'jsqb' . '/salary_certificate/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_PROOF_of_ASSETS:
                $object = 'jsqb' . '/proof_of_assets/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_WORK_CARD:
                $object = 'jsqb' . '/work_card/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_BUSINESS_CARD:
                $object = 'jsqb' . '/business_card/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_BANK_CARD:
                $object = 'jsqb' . '/bank_card/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_FACE_RECOGNITION:
                $object = 'jsqb' . '/face_recognition/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            case UserProofMateria::TYPE_ORDER_VOUCHER:
                $object = 'jsqb' . '/order_voucher/' . $user_id . '/' . $filename . '.' . $extension;
                break;
            default:
                $object = '';
                break;
        }

        return $object;
    }

}