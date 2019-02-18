<?php

namespace common\models\mongo\statistics;

use common\base\LogChannel;
use common\helpers\MailHelper;
use Yii;

class UserMobileContactsMongo extends \yii\mongodb\ActiveRecord {

    public static function getDb(){
        return Yii::$app->mongodb_log;
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'user_mobile_contacts';
    }

    public function attributes(){
        return [
            '_id',
            'user_id',
            'mobile',
            'name',
            'type',
            'text',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * 添加数据
     * @param string $user_id 用户ID
     * @param string $mobile 通讯录联系人手机号
     * @param string $name 联系人名称
     * @param array $data 其他数据
     * @return mixed
     */
    public static function addData($user_id, $mobile, $name, $data=[]) {
        $_id = \trim($user_id) . '_' . \trim($mobile);
        $mod = self::findOne(['_id' => $_id]);
        if ($mod) {
            return $mod;
        }

        $model = new self(['_id' => $_id]);
        $model->user_id = \strval( $user_id );
        $model->mobile = trim($mobile);
        $model->name = trim($name);

        $_now_ts = \date("Y-m-d H:i:s");
        $model->created_at = $_now_ts;
        $model->updated_at = $_now_ts;

        foreach($data as $key => $val) {
            if (\in_array($key, ['type', 'text', 'created_at', 'updated_at'])) {
                $model->$key = $val;
            }
        }

        try {
            if ($model->save()) {
                return $model;
            }
        }
        catch (\Exception $e) {
            if (\strpos($e->getMessage(), 'duplicate key')) {
                return static::findOne(['_id' => $_id]);
            }

            $msg = sprintf('save user_mobile_contacts_mongo error. user_id:%s, mobile:%s, name:%s, data:%s, exception:%s',
                $user_id, $mobile, $name, json_encode($data), $e->getMessage());
            MailHelper::sendQueueMail($msg, '', [
                NOTICE_MAIL2,
                NOTICE_MAIL,
            ]);
            \yii::warning($msg, LogChannel::USER_UPLOAD);

            throw $e;
        }

        Yii::error("save user_mobile_contacts_mongo error. user_id:{$user_id}, mobile:{$mobile}, name:{$name}, data:". var_export($data,1), LogChannel::USER_UPLOAD);
        return false;
    }

}
