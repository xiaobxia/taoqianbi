<?php
namespace common\models;

use Yii;
use \yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property string $user_id 用户在我们这边的id
 * @property string $out_user_id 用户在合作方的id
 * @property string $channel 渠道号
 * @property string $phone 手机号
*/
class UserChannelMap extends ActiveRecord {

    public static function getDb() {
        return Yii::$app->db_kdkj;
    }

    public static function tableName() {
        return 'tb_user_channel_map';
    }

    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'id',
            'user_id' => '用户ID',
            'out_user_id' => '渠道侧用户ID',
            'channel' => '渠道名',
            'phone' => '手机号',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    public static function findByChannel($channel,$phone){
        if(empty($channel) || empty($phone)){
            return null;
        }
        return self::findOne(['channel'=>$channel,'phone'=>$phone]);
    }
}