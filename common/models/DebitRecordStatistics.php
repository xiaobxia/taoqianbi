<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/6/16
 * Time: 下午6:17
 */

namespace common\models;

use Yii;

class DebitRecordStatistics extends BaseActiveRecord
{

    //逾期类型 1:0-5,2:6-10,3:11-15
    const TYPE_0 = 0;
    const TYPE_1 = 1;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;

    public static $TYPE_ARR = array(
       self::TYPE_0 => '未逾期',
       self::TYPE_1 => '逾期1-5',
       self::TYPE_2 => '逾期6-10',
       self::TYPE_3 => '逾期11-15',
       self::TYPE_4 => '逾期16天以上'
    );

    public static function tableName()
    {
        return '{{%debit_record_statistics}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}