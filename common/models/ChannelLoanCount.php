<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 19:34
 */
namespace common\models;
use yii;
use yii\db\ActiveRecord;
class ChannelLoanCount extends ActiveRecord{


    public static function tableName(){
        return '{{%channel_loan_count}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db');
    }


    /**
     * 查询对应条件的数据
     * @param $condition
     * @return array|ActiveRecord|null
     */
    public function getData($condition)
    {

        return self::find()->where($condition)->one();
    }


}