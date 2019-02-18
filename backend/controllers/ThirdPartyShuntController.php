<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/12
 * Time: 14:05
 */

namespace backend\controllers;

use ALIOSS;
use Yii;
use backend\models\ThirdPartyShunt;
use backend\models\ThirdPartyShuntType;
use common\helpers\Url;
use yii\data\Pagination;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\models\UserProofMateria;

require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';

class ThirdPartyShuntController extends BaseController{

    /**
     * @name 导流 列表 actionShuntList
     */
    public  function actionShuntList(){


        $type = ThirdPartyShuntType::find()->where(('status >= 0 '))->asArray()->orderBy('sort desc')->all();

        $types = [];
        if($type)
            foreach($type as $v){
            $types[$v['id']] = $v['name'];
        }

        $query = ThirdPartyShunt::find()->where('status >= 0')->orderBy('sort DESC');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;

        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('shunt-list', array(
            'list' => $list,
            'pages' => $pages,
            'types'=>$types,
        ));


    }


    /**
     * @name 导流 添加 actionShuntAdd
     */
    public  function actionShuntAdd(){

        $model = new ThirdPartyShunt();

        $type = ThirdPartyShuntType::find()->where(['status'=>1])->asArray()->orderBy('sort desc')->all();

        $types = [];
        if($type)
        foreach($type as $v){
            $types[$v['id']] = $v['name'];
        }

        // 有提交则装载post值并验证
        if ($model->load(Yii::$app->getRequest()->post())) {

            $upload_ret = $this->uploadFile('log_url');
            $model->log_url = $upload_ret['url'];

            if ($model->validate() && $model->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('shunt-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

        return $this->render('shunt-add', array(
            'type'=>'add',
            'model'=>$model,
            'types'=>$types,
        ));

    }


    /**
     * @name 导流 修改 actionShuntEdit
     */
    public  function actionShuntEdit(){

        $id = Yii::$app->getRequest()->get('id',0);

        $model = ThirdPartyShunt::findOne($id);


        if(!$model){
            return $this->redirectMessage('改类型不存在', self::MSG_ERROR);
        }

        $type = ThirdPartyShuntType::find()->where(['status'=>1])->asArray()->orderBy('sort desc')->all();

        $types = [];
        if($type)
            foreach($type as $v){
            $types[$v['id']] = $v['name'];
        }

        if ($model->load(Yii::$app->getRequest()->post())) {

            if($_FILES['log_url']['name']) {
                $upload_ret = $this->uploadFile('log_url');
                $model->log_url = $upload_ret['url'];
            }

            if ($model->validate() && $model->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('shunt-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

        return $this->render('shunt-add', array(
            'model' => $model,
            'type' => 'edit',
            'types'=>$types,
        ));

    }

    /**
     * @name 导流类型  删除  actionShuntDel
     */
    public function actionShuntDel(){

        $id = Yii::$app->getRequest()->get('id',0);

        $model = ThirdPartyShunt::findOne($id);

        $model->status = -1;

        if ($model->save()) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('shunt-list'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }

    }


    /**
     * @name 导流类型 列表 actionTypeList
     */
    public function actionTypeList(){


        $query = ThirdPartyShuntType::find()->where('status >= 0')->orderBy('sort DESC');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;

        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('type-list', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }


    /**
     * @name 导流类型  添加  actionTypeAdd
     */
    public function actionTypeAdd(){

        $model = new ThirdPartyShuntType();

        // 有提交则装载post值并验证
        if ($model->load(Yii::$app->getRequest()->post())) {

            $upload_ret = $this->uploadFile('log_url');
            $model->log_url = $upload_ret['url'];

            if ($model->validate() && $model->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('type-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

        return $this->render('type-add', array(
            'type'=>'add',
            'model'=>$model,
        ));

    }

    /**
     * @name 导流类型 修改  actionTypeEdit
     */
    public function actionTypeEdit(){

        $id = Yii::$app->getRequest()->get('id',0);

        $model = ThirdPartyShuntType::findOne($id);

        if(!$model){
            return $this->redirectMessage('改类型不存在', self::MSG_ERROR);
        }

        if ($model->load(Yii::$app->getRequest()->post())) {

            if($_FILES['log_url']['name']) {
                $upload_ret = $this->uploadFile('log_url');
                $model->log_url = $upload_ret['url'];
            }

            if ($model->validate() && $model->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('type-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }


        return $this->render('type-add', array(
            'model' => $model,
            'type' => 'edit',
        ));
    }

    /**
     * @name 导流类型  删除  actionTypeDel
     */
    public function actionTypeDel(){

        $id = Yii::$app->getRequest()->get('id',0);

        $model = ThirdPartyShuntType::findOne($id);

        $model->status = -1;

        if ($model->save()) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('type-list'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }

    }

    /**
     * @name 图片上传
     */
    protected function uploadFile($name)
    {
        $file = UploadedFile::getInstanceByName($name);

        $data = $this->UploadImg($file);
        $file_url = OSS_RES_URL . $data['object'];

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
        $object = $this->handleObject('shunt',UserProofMateria::TYPE_OTHER,$filename,$file->extension);

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


}
