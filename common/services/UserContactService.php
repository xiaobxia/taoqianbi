<?php

namespace common\services;



use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use common\base\LogChannel;
use common\api\RedisQueue;
use common\models\UserContact;
use common\models\UserContactsRecord;

/**
 * 用户联系人基本模块service
 */
class UserContactService extends Component
{

    public function __construct( $config = [])
    {
        parent::__construct($config);
    }

    /**
     * 获取单条联系人信息
     */
    public function getInfo($id){
        $contact = UserContact::findOne($id);
        return $contact;
    }

    /**
     * 获取用户所有联系人信息（后台使用）
     */
    public function getUserContacts($user_id, $condition=""){
        $contacts = UserContact::find()->where("user_id = ".$user_id.(empty($condition) ? "" : " and ".$condition))->all();
        return $contacts;
    }

    /**
     * 获取用户所有联系人信息（前端展示）
     */
    public function getUserContactsFront($user_id){
        $contacts = self::getUserContacts($user_id, "source != ".UserContact::SOURCE_UPLOAD." and status = ".UserContact::STATUS_NORMAL);
        return $contacts;
    }

    /**
     * 获取用户必填联系人信息
     */
    public function getNeceContacts($user_id){
        $contacts = self::getUserContacts($user_id, "source = ".UserContact::SOURCE_NECESSARY." and status = ".UserContact::STATUS_NORMAL);
        return $contacts;
    }

    /**
     * 获取用户补充联系人信息
     */
    public function getSupContacts($user_id){
        $contacts = self::getUserContacts($user_id, "source = ".UserContact::SOURCE_SUPPLEMENT." and status = ".UserContact::STATUS_NORMAL);
        return $contacts;
    }

    /**
     * 获取用户系统获取联系人信息
     */
    public function getUploadContacts($user_id){
        $contacts = self::getUserContacts($user_id, "source = ".UserContact::SOURCE_UPLOAD." and status = ".UserContact::STATUS_NORMAL);
        return $contacts;
    }

    /**
     * 保存用户联系人信息
     */
    public function addUserContact($user_contact) {
        $model = new UserContact();
        $model->user_id = $user_contact['user_id'];
        $model->relation = $user_contact['relation'];
        $model->name = $user_contact['name'];
        $model->mobile = $user_contact['mobile'];
        $model->source = $user_contact['source'];
        $model->status = UserContact::STATUS_NORMAL;
        if($model->save()) {
            return true;
        } else {
            throw new UserException(array_shift($model->getFirstErrors()));
        }
    }

    /**
     * 是否已经存在改记录
     */
    public static function isExistSameUserRecord($user_id) {
        /* @var $redis \yii\redis\Connection */
        $redis = \yii::$app->redis;
        $exists = $redis->hexists(RedisQueue::HASH_USER_CONTACTS_UPLOAD, $user_id);
        if ($exists) {
            return TRUE;
        }

        $mod = UserContactsRecord::findOne([
            'user_id' => $user_id,
            'is_upload' => UserContactsRecord::STATUS_ON,
        ]);
        if ($mod) {
            $redis->hset(RedisQueue::HASH_USER_CONTACTS_UPLOAD, $user_id, (isset($mod->updated_at) ? $mod->updated_at : 1));
            return TRUE;
        }

        return FALSE;
    }

    /**
     * 处理 特殊用户 需要再次上传通讯
     */
    public static function addUserContactRecord($user_id){
        // 如果已存在则不需要再次插入
        if (self::isExistSameUserRecord($user_id) == true) {
            return true;
        }

        $model = new UserContactsRecord();
        $model->user_id   = $user_id;
        $model->is_upload = UserContactsRecord::STATUS_OFF;
        if ($model->save()) {
            /* @var $redis \yii\redis\Connection */
            $redis = \yii::$app->redis;
            $redis_update = $redis->hset(RedisQueue::HASH_USER_CONTACTS_UPLOAD, $user_id, (isset($model->updated_at) ? $model->updated_at : time()));
            if (! $redis_update) {
                \yii::warning(sprintf('[%s] %s user_contact_updated_failed.', date('y-m-d H:i:s'), $user_id), LogChannel::USER_UPLOAD);
            }
            return true;
        }

        throw new UserException(array_shift($model->getFirstErrors()));
    }

    /**
     * 重置用户的上传状态
     * @param user_id  用户编号
     * @param status   用户状态 0: 上传失败；1: 上传成功
     */
    public static function updateUserContactRecord($user_id,$status=0){
        $condition   = sprintf("user_id=%d",$user_id);
        $user_record = UserContactsRecord::find()->where($condition)->limit(1)->one();
        if ($user_record) {
            if ($status > 0) {
                $user_record->is_upload = UserContactsRecord::STATUS_ON;
            }else{
                $user_record->is_upload = UserContactsRecord::STATUS_ERR;
            }
            $user_record->upload_time = time();
            $user_record->total = $user_record->total + 1;
            if($user_record->save()) {
                return true;
            }else{
                throw new UserException(array_shift($user_record->getFirstErrors()));
            }
        }else{
            return false;
        }
    }
}
