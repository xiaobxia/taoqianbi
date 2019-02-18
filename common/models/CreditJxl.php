<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use common\models\CreditJxlData;
use common\models\mongo\credit\CreditJxlMongoData;

class CreditJxl extends  ActiveRecord
{

    const STATUS_TURE = 1;//数据生效
    const STATUS_FALSE = 0;//数据失效

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const RAW_STATUS_INIT=0;//初始状态
    const RAW_STATUS_TRUE=1;//生效
    const RAW_STATUS_FALSE=2;//失效
    const RAW_STATUS_FAIL=3;//拉取数据失败
    const RAW_STATUS_DEFAULT=4;//老用户数据（为兼容老用户未拉取数据）
    const RAW_JXL_RULE='raw-jxl-rule-';//聚信立原始数据风控规则缓存

    const TYPE_BASE_REPORT = 1;
    public static function tableName()
    {
        return '{{%credit_jxl}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'status' => '数据是否失效',
            'id_number' => '借款人编号',
            'token'    => '用户报表token',
            'person_id' => '借款人id',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'updt' => '聚信立数据更新时间',
            'log_id' => '取值于表tb_credit_jxl_log',
            'is_overdue' => '是否过期 0未过期，1已过期',
            'data' => '是否过期 0未过期，1已过期',
            'raw_status' => '聚信立原始数据状态',
            'raw_data' => '聚信立原始数据JSON数据',
        ];
    }
    
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->getIsNewRecord()) {
            $result = $this->insert($runValidation, $attributeNames);
        } else {
            $result = $this->update($runValidation, $attributeNames) !== false;
        }

        if($result) {
            $model = CreditJxlMongoData::find()->where(['_id' => $this->id])->one();

            if(empty($model)) {
                $model = new CreditJxlMongoData();
                $model->_id = $this->id;
                $model->person_id = $this->person_id;
                $model->created_at = date('Y-m-d H:i:s');
            }
            $model->status = $this->status;
            $model->id_number = $this->id_number;
            $model->token = $this->token;
            $model->log_id = $this->log_id;
            $model->is_overdue = $this->is_overdue;
            $model->data = $this->data;
            $model->updated_at = date('Y-m-d H:i:s');
            $result = $model->save();
        }

        return $result;
    }

    public function getInfo() {

        if(!empty($this->data)){
            return $this->data;
        }

        $model = CreditJxlMongoData::find()->where(['_id'=>$this->id])->one();

        // mongo迁移
        if(empty($model)){
            $model = CreditJxlMongoData::find()->where(['_id'=>$this->id])->one(Yii::$app->get('mongodb_user_message'));
        }
        if(empty($model)){
            return "";
        }

        return $model->data;
    }

    // public function setInfo($data) {

    //     $this->data = $data;
    // }

    public static function findLatestOne($params, $dbName = null) {
        $db = empty($dbName) ? self::getDb() : \yii::$app->get($dbName);
        return self::findByCondition($params)->orderBy('id Desc')->one( $db );
    }

    // public static function findLatestOne($params, $dbName = null) {
    //     $credit_data = CreditJxlMongoData::findByCondition($params)->orderBy('updated_at Desc')->one();
    //     if ($credit_data) {
    //         YII::info("mongo exists, user_id:{$credit_data['person_id']}", 'credit_jxl_mongo');
    //         return $credit_data;
    //     }

    //     $db = empty($dbName) ? self::getDb() : \yii::$app->get($dbName);
    //     $credit_data = self::findByCondition($params)->orderBy('id Desc')->one( $db );
    //     if ($credit_data) {
    //         YII::info("mongo not exists, user_id:{$credit_data['person_id']}", 'credit_jxl_mongo');
    //     }
    //     return $credit_data;
    // }

    // public static function findCombine($params, $dbName = null) {
    //     $credit_mongo_data = CreditJxlMongoData::findByCondition($params)->orderBy('updated_at Desc')->asArray()->all();
    //     if ($credit_mongo_data) {
    //         return $credit_mongo_data;
    //     }

    //     $db = empty($dbName) ? self::getDb() : \yii::$app->get($dbName);
    //     $credit_db_data = self::findByCondition($params)->orderBy('id Desc')->asArray()->all( $db );

    //     return array_merge($credit_mongo_data, $credit_db_data);
    // }
}
