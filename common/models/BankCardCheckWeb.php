<?php
namespace common\models;


use common\base\LogChannel;
use common\models\loan\LoanCollectionOrder;
use common\services\RiskControlNewService;
use common\services\RiskControlService;
use console\models\NetUtil;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\db\Query;
use common\models\UserPassword;
use common\models\UserDetail;
use common\models\mongo\risk\RuleReportMongo;
use yii\base\UserException;
use common\api\RedisQueue;
use common\models\Setting;
use common\services\UserService;
use common\models\asset\AssetLoadPlat;
use common\helpers\Util;

class BankCardCheckWeb extends \yii\db\ActiveRecord{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bank_cardcheckweb}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_rd');
    }

    public static function getDbMhk()
    {
        return Yii::$app->get('db_mhk');
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [[
                'id','card_four_element_consistence', 'status', 'cardNo',
            ], 'safe'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'id',
            'card_four_element_consistence' => '鉴权结果',
            'status' => '结果',
            'cardNo' => '银行卡号',

        ];
    }
	
}
