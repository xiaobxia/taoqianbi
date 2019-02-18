<?php
/**
 * Created by PhpStorm.
 * User: wangwei
 * Date: 2018/4/20
 * Time: 19:58
 */

namespace common\models;
use yii\db\ActiveRecord;

class CreditYx extends ActiveRecord
{
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    public static function tableName()
    {
        return '{{%credit_yx}}';
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public static function findLatestOne($arr){
        return self::find()->where($arr)->orderBy('id desc')->one();
    }

    const FALSEHOOD_INFO_1 = 1;//伪冒类
    const FALSEHOOD_INFO_2 = 2;//资料虚假类
    const FALSEHOOD_INFO_3 = 3;//丧失还款能力类
    const FALSEHOOD_INFO_4 = 4;//用途虚假类
    const FALSEHOOD_INFO_5 = 5;//其他

    public static $info_error = [
        11=>self::FALSEHOOD_INFO_1,
        12=>self::FALSEHOOD_INFO_2,
        10=>self::FALSEHOOD_INFO_3,
        13=>self::FALSEHOOD_INFO_4,
        19=>self::FALSEHOOD_INFO_5
    ];

    public static $overdueStatusList =[
        'M2',
        'M3',
        'M3+',
        'M4',
        'M5',
        'M6',
        'M6+',
    ];
    /*
     * 风险名单列表
     */
    public static $danger_string_list = [
        '黑名单','逾期','资料虚假','永久拒绝'
    ];
}