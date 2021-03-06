<?php

namespace common\models\loan;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%loan_collection}}".
 *
 * @property integer $id
 * @property integer $admin_user_id
 * @property string $username
 * @property string $phone
 * @property integer $group
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $operator_name
 * @property integer $status
 */
class StatisticsByMoney extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_statistics}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public static function getDb_rd()
    {
        return Yii::$app->get('db_kdkj_rd');
    }
}
