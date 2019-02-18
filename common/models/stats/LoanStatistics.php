<?php
namespace common\models\stats;

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
class LoanStatistics extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_statistics}}';
    }
   /* public static function getDb()
    {
        return Yii::$app->get('db_stats');
    }*/

    //滞纳金
    const LOAN_TYPE_SHORT = 0;//短期
    const LOAN_TYPE_LONG = 1;//分期

    public static $loan_type = [
        self::LOAN_TYPE_SHORT => '短期',
        self::LOAN_TYPE_LONG => '分期',
    ];
}
