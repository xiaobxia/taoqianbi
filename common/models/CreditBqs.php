<?php
namespace common\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;

class CreditBqs extends ActiveRecord
{
    const TYPE_LOAN_DECISION = 1;
    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    public static $type_list = [
        self::TYPE_LOAN_DECISION => '借款决策'
    ];
    public static $resultCode = [
        'BQS101' => '认证失败',
        'BQS102' => '参数不合法',
        'BQS103' => '授权过期',
        'BQS104' => 'partnerd Id不存在',
        'BQS500' => '系统内部异常',
    ];

      public static function tableName()
    {
        return '{{%credit_bqs}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }



    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
     * @inheritdoc
     */
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function rules(){
        return [
            [[ 'id','person_id','resultcode','log_id','data','status','created_at','updated_at'], 'safe'],
        ];
    }

    public static function findLatestOne($params,$dbName = null)
    {
        if(is_null($dbName))
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditMg;
    }

}