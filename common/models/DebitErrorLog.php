<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/6/1
 * Time: 上午10:02
 */

namespace common\models;

use Yii;

class DebitErrorLog extends BaseActiveRecord
{
    const ERROR_TYPE_USER = 1;
    const ERROR_TYPE_AUTO = 2;


    const STATUS_0 = 0; //默认
    const STATUS_1 = 1; //已处理

    public static $ERROR_TYPE = array(
        self::ERROR_TYPE_USER => '主动还款',
        self::ERROR_TYPE_AUTO => '系统代扣'
    );

    public static $ERROR_STATUS = array(
        self::STATUS_0 => '默认',
        self::STATUS_1 => '已处理',
    );

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%debit_error_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}