<?php

namespace common\models\loan;

use Yii;


class VoiceSMSRecords extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_voice_records}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }


    const SEND_CHANNEL1 = 'chuanglan';  //创蓝

    /**
     *添加新记录
     */
    public static function input_records($type, $phone_str, $channel = self::SEND_CHANNEL1){
        $item = new self();
        $item->create_time = time();
        $item->type = $type;
        $item->phones = $phone_str;
        $item->voice_type = $channel;
        return $item->save();
    }
}
