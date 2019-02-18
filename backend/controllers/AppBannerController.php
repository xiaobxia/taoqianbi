<?php
namespace backend\controllers;

use ALIOSS;
use common\models\LoanPerson;
use Yii;
use common\models\AppBanner;
use common\helpers\Url;
use yii\data\Pagination;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\models\UserProofMateria;
use common\models\LoanSearchPublicList;

require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';
/**
 * Banner controller
 */
class AppBannerController extends BaseController
{
    private $source_list = [
        LoanPerson::PERSON_SOURCE_MOBILE_CREDIT ,
        LoanPerson::PERSON_SOURCE_WZD_LOAN,
        LoanPerson::PERSON_SOURCE_HBJB,
    ];

    /**
     * @name banner列表
     */
    public function actionList()
    {
        $query = AppBanner::find()->where(['<>','status','2'])->orderBy('id DESC');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;

        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('list', array(
            'list' => $list,
            'type_name' => AppBanner::$type_name,
            'type_float'=>AppBanner::$type_float,
            'status_name' => AppBanner::$status_name,
            'pages' => $pages,
        ));
    }

    /**
     * @name 添加banner
     */
    public function actionAdd()
    {
        new LoanPerson();
        $model = new AppBanner();

        // 有提交则装载post值并验证
        if ($model->load(Yii::$app->getRequest()->post())) {
            $upload_ret = $this->uploadFile();
            $model->image_url = $upload_ret['url'];
            if ($model->validate() && $model->save()) {
                self::bannerAddRedis($model->source_id);
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

        // 公共列表
        $public_list = LoanSearchPublicList::find()->where(['status' => LoanSearchPublicList::STATUS_INIT])->asArray()->all();
        $public = [0 => '--所有用户--'];
        foreach ($public_list as $val) {
            $public[$val['id']] = $val['task_id']. '/' .$val['name'];
        }

        return $this->render('add', array(
            'model' => $model,
            'type' => 'add',
            'award' => [],
            'platform' => [],
            'public' => $public
        ));
    }

    /**
     * @name 编辑banner
     */
    public function actionEdit(int $id)
    {
        $model = AppBanner::findOne($id);
        if(!$model || $model->status==AppBanner::BANNER_STATUS_DEL) {
            return $this->redirectMessage('banner已删除', self::MSG_ERROR);
        }

        $origin_source = $model->source_id;
        // 有提交则装载post值并验证
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            if($_FILES['file']['name']) {
                $upload_ret = $this->uploadFile();
                $model->image_url = $upload_ret['url'];
            }

            if ($model->save()) {
                //改变source 原source对应banner也要变化
                if($origin_source != $model->source_id){
                    self::bannerAddRedis($origin_source);
                }
                self::bannerAddRedis($model->source_id);
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }

        // 公共列表
        $public_list = LoanSearchPublicList::find()->where(['status' => LoanSearchPublicList::STATUS_INIT])->asArray()->all();
        $public = [0 => '--所有用户--'];
        foreach ($public_list as $val) {
            $public[$val['id']] = $val['task_id']. '/' .$val['name'];
        }
        return $this->render('add', array(
            'model' => $model,
            'type' => 'edit',
            'award' => [],
            'platform' => [],
            'public' => $public,
        ));
    }


    /**
     * @name 删除banner
     */
    public function actionDel(int $id)
    {
        $model = AppBanner::findOne($id);
        if(!$model || $model->status==AppBanner::BANNER_STATUS_DEL) {
            return $this->redirectMessage('banner已删除', self::MSG_ERROR);
        }

        $model->status = AppBanner::BANNER_STATUS_DEL;

        if ($model->save()) {
            self::bannerAddRedis($model->source_id);
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('list'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }

    }
    /**
     * @name 图片上传
     */
    protected function uploadFile()
    {
        $file = UploadedFile::getInstanceByName('file');

        $data = $this->UploadImg($file);
        $file_url = OSS_RES_URL_OUTSIDE . $data['object'];

        return [
            'code' => 0,
            'url' => $file_url,
        ];
    }

    private function UploadImg($file){
        $validator = new FileValidator();
        $validator->extensions = ['jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF'];
        $validator->maxSize = 10 * 1024 * 1024;
        $validator->checkExtensionByMimeType = false;
        $error = "";
        if (!$validator->validate($file, $error)) {
            return $this->redirectMessage('图片上传失败', self::MSG_ERROR);
        }
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $filename = substr($charid, 7, 13);
        $object = $this->handleObject('banner',UserProofMateria::TYPE_OTHER,$filename,$file->extension);

        if (empty($object)) {
            return $this->redirectMessage('图片上传失败', self::MSG_ERROR);
        }
        $file_path = $file->tempName;
        $ossService = new ALIOSS();
        $response = $ossService->upload_file_by_file(DEFAULT_OSS_BUCKET, $object, $file_path);
        if (!$response->isOK()) {
            return $this->redirectMessage('图片上传失败', self::MSG_ERROR);
        }
        $data['filename'] = $filename;
        $data['object'] = $object;
        return $data;
    }

    private function handleObject($user_id,$type,$filename,$extension) {
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

    /**
     * 删除对应的banner并重新生成
     * @param $source
     */
    public static function bannerAddRedis($source){
        $redis = Yii::$app->redis;
        $banner_list = AppBanner::bannerList($source);
        if($redis->EXISTS('app-index-banner:'.$source)){
            $redis->DEL('app-index-banner:'.$source);
        }
        $redis->SET('app-index-banner:'.$source,json_encode($banner_list));
    }

}