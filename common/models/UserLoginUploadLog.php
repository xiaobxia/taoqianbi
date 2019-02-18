<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * 用户登录上传的信息日志
 * This is the model class for table "{{%user_login_upload_log}}".
 * @property integer $id 
 * @property integer $user_id 用户ID
 * @property string $longitude 
 * @property string $latitude
 * @property string $clientType
 * @property string $address
 * @property string $osVersion
 * @property string $appVersion
 * @property string $deviceName 
 * @property string $appMarket 应用市场
 * @property string $deviceId 设备码
 * @property integer $created_at
 */
class UserLoginUploadLog extends ActiveRecord
{

    const ANDROID = 'android';
    const IOS = 'ios';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_login_upload_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 获取借款人信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }
    
    /**
     * 添加数据 
     * @param integer $user_id
     * @param array $attrs
     * @return \static
     */
    public static function addData($user_id, $attrs) {
        $model = new static();
        $model->user_id = (int)$user_id;
        $model->created_at = time();
        $model->time = time();
        
        $allow_set_attrs = [
            'longitude', 'latitude', 'clientType', 'address', 'osVersion', 'appVersion', 'deviceName', 'appMarket','deviceId'
        ];
        foreach($attrs as $key=>$val) {
            if(in_array($key, $allow_set_attrs)) {
                $model->$key = $val;
            }
        }
        
        $model->save(false);
        return $model;
    }

}