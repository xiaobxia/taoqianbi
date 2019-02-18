<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017/9/21
 * Time: 14:27
 */

namespace common\models;

use Yii;

class RidOverdueLog extends BaseActiveRecord
{
    const TYPE_ADMIN_SYSTEM = 1;
    const TYPE_CS_SYSTEM = 2;

    public static $type = [
        self::TYPE_ADMIN_SYSTEM=>'管理后台',
        self::TYPE_CS_SYSTEM=>'催收后台',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%rid_overdue_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
}