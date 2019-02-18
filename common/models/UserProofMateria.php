<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\exceptions\UserExceptionExt;
/**
 * This is the model class for table "{{%province}}".
 */
class UserProofMateria extends ActiveRecord
{

    const OCR_TYPE_NORMAL = 0;
    const OCR_TYPE_FACE = 1;
    const OCR_TYPE_PHOTO = 2;
    const OCR_TYPE_CHANNEL = 3;

    public static $ocr_type = [
        self::OCR_TYPE_NORMAL=>'默认',
        self::OCR_TYPE_FACE=>'人脸识别',
        self::OCR_TYPE_PHOTO=>'拍照',
        self::OCR_TYPE_CHANNEL=>'渠道照片', 
    ];

    const STATUS_NORMAL = 0;
    const STATUS_DEL = 1;
    
    const TYPE_OTHER=100;
    const TYPE_ID_CAR = 1;
    const TYPE_DIPLOMA_CERTIFICATE=2;
    const TYPE_WORK_PROVE=3;
    const TYPE_SALARY_CERTIFICATE=4;
    const TYPE_PROOF_of_ASSETS=5;
    const TYPE_WORK_CARD = 6;
    const TYPE_BUSINESS_CARD = 7;
    const TYPE_BANK_CARD = 8;
    const TYPE_HFD_HOUSE_CERTIFICATE = 9;
    const TYPE_FACE_RECOGNITION = 10;
    const TYPE_ID_CAR_Z = 11;
    const TYPE_ID_CAR_F = 12;
    const TYPE_ORDER_VOUCHER = 13;



    public static $type =[
        self::TYPE_OTHER=>['title'=>'其它证明','notice'=>''],
        self::TYPE_ID_CAR=>['title'=>'身份证','notice'=>'请提供本人身份证正面、反面照片，以及本人手持身份证正面照片'],
        self::TYPE_DIPLOMA_CERTIFICATE=>['title'=>'学历证明','notice'=>'请提供可证明学历的学历证书，如毕业证书、学位证书等'],
        self::TYPE_WORK_PROVE=>['title'=>'工作证明','notice'=>'请提供可证明工作相关合同，如劳动合同'],
        self::TYPE_SALARY_CERTIFICATE=>['title'=>'收入证明','notice'=>'请提供本人近期工资卡流水、信用卡消费流水等能证明您的收入照片'],
        self::TYPE_PROOF_of_ASSETS=>['title'=>'财产证明','notice'=>'请提供本人近期工资流水、信用卡消费流水等能证明您的收入的照片'],
        self::TYPE_WORK_CARD=>['title'=>'工作证照','notice'=>'请提供可以证明您在此公司工作的照片，如含本人照片的工牌照、名片、与公司Logo合影照等'],
        self::TYPE_BUSINESS_CARD=>['title'=>'个人名片','notice'=>'请提个人名片照片'],
        self::TYPE_BANK_CARD=>['title'=>'银行卡','notice'=>'请提供您的信用卡正面照片'],
        self::TYPE_HFD_HOUSE_CERTIFICATE=>['title'=>'好房贷房产证','notice'=>'好房贷提单房产证照片'],
    	self::TYPE_FACE_RECOGNITION=>['title'=>'人脸识别','notice'=>APP_NAMES.'人脸识别'],
        self::TYPE_ID_CAR_Z=>['title'=>'身份证正面','notice'=>'请提供本人身份证正面照片'],
        self::TYPE_ID_CAR_F=>['title'=>'身份证反面','notice'=>'请提供本人身份证反面照片'],
        self::TYPE_ORDER_VOUCHER=>['title'=>'还款凭证','notice'=>'用户还款凭证'],
    ];

    public static $type_max =[
        self::TYPE_OTHER=>10,
        self::TYPE_ID_CAR=>3,
        self::TYPE_DIPLOMA_CERTIFICATE=>5,
        self::TYPE_WORK_PROVE=>5,
        self::TYPE_SALARY_CERTIFICATE=>5,
        self::TYPE_PROOF_of_ASSETS=>5,
        self::TYPE_WORK_CARD=>3,
        self::TYPE_BUSINESS_CARD=>2,
        self::TYPE_BANK_CARD=>5,
        self::TYPE_HFD_HOUSE_CERTIFICATE =>5,
        self::TYPE_FACE_RECOGNITION =>10,
        self::TYPE_ID_CAR_Z=>10,
        self::TYPE_ID_CAR_F=>10,
    ];

    //每种类型图片上传上线
    public static $type_pic_max = [
        self::TYPE_OTHER=>10,
        self::TYPE_ID_CAR=>3,
        self::TYPE_DIPLOMA_CERTIFICATE=>5,
        self::TYPE_WORK_PROVE=>5,
        self::TYPE_SALARY_CERTIFICATE=>5,
        self::TYPE_PROOF_of_ASSETS=>5,
        self::TYPE_WORK_CARD=>3,
        self::TYPE_BUSINESS_CARD=>2,
        self::TYPE_BANK_CARD=>5,
        self::TYPE_HFD_HOUSE_CERTIFICATE =>5,
        self::TYPE_FACE_RECOGNITION =>10,
        self::TYPE_ID_CAR_Z=>10,
        self::TYPE_ID_CAR_F=>10,
        self::TYPE_ORDER_VOUCHER=>3,
    ];


    public static $status = [
        self::STATUS_NORMAL=>'正常',
        self::STATUS_DEL=>'已删除',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_proof_materia}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    public static function findOneByType($user_id,$type){
        return self::find()->where(['user_id'=>$user_id,'type'=>$type,'status'=>self::STATUS_NORMAL])->orderBy('id desc')->limit(1)->one();
    }
    public static function findAllByType($user_id,$type=0){
        $where = ['user_id'=>$user_id,'status'=>self::STATUS_NORMAL];
        if($type){
            $where['type'] = $type;
        }
        return self::findAll($where);
    }
    public static function getPicCount($user_id,$type=0){
        $where = ['user_id'=>$user_id,'status'=>self::STATUS_NORMAL];
        if($type){
            $where['type'] = $type;
        }
        return self::find()->where($where)->count();
    }
    public static function deletePicById($user_id,$id){
        if(UserLoanOrder::checkHasUnFinishedOrder($user_id)){
            UserExceptionExt::throwCodeAndMsg(-1, '申请已受理，无法修改照片');
        }
        return self::updateAll(['status'=>self::STATUS_DEL,'updated_at'=>time()],['user_id'=>$user_id,'id'=>$id]);
    }

    /**`
     * @param $user_id
     * @param $id
     * @return int
     */
    public static function deleteById($user_id,$id){
        return self::updateAll(['status'=>self::STATUS_DEL,'updated_at'=>time()],['user_id'=>$user_id,'id'=>$id]);
    }
    public static function deletePicByType($user_id,$type){
        if(!isset(self::$type[$type])){
            return false;
        }
        if(UserLoanOrder::checkHasUnFinishedOrder($user_id)){
            UserExceptionExt::throwCodeAndMsg(-1, '申请已受理，无法修改照片');
        }
        return self::updateAll(['status'=>self::STATUS_DEL,'updated_at'=>time()],['user_id'=>$user_id,'type'=>$type]);
    }
    
    /**
     * 添加一个记录
     * @param type $user_id 用户ID
     * @param type $type 类型
     * @param type $filename 文件名称
     * @param type $file_url 文件URL
     * @param integer $ocr_type 子类型 0为默认， 1为识别， 2为拍照
     * @return boolean|\common\models\UserProofMateria
     */
    public static function add($user_id, $type, $filename, $file_url, $ocr_type=0) {
        $model = new static;
        $model->user_id = $user_id;
        $model->type = $type;
        $model->pic_name = $filename;
        $model->url = $file_url;
        $model->created_at = $model->updated_at = time();
        $model->ocr_type = (int)$ocr_type;
        if($model->save(false)) {
            return $model;
        } else {
            return false;
        }
    }
}