<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017/3/7
 * Time: 10:39
 */
namespace common\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

class CreditBqsLog extends ActiveRecord
{
    const TYPE_LOAN_DECISION = 1;
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    public static $type_list = [
        self::TYPE_LOAN_DECISION => '借款决策'
    ];

    public static function tableName()
    {
        return '{{%credit_bqs_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }


    public function rules(){
        return [
            [[ 'id','person_id','admin_username','price','created_at'], 'safe'],
        ];
    }
}