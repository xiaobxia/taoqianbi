<?php
namespace backend\models;


use common\models\LoanPerson;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\models\LoanRepayment;

class LoanCollectionRecord extends ActiveRecord
{
//    const WAIT_COLLECTION = 0;
    const ALREADY_COLLECTION = 1;
    const IN_COLLECTION = 2;
    const ALREADY_COLLECTION_PART = 3;
    const SUCCESS_COLLECTION = 4;
    const PROMISE_COLLECTION = 5;

    public static $status = [
//        self::WAIT_COLLECTION => '待催收',
        self::IN_COLLECTION => '催收中',
        self::ALREADY_COLLECTION => '已催收',
        self::ALREADY_COLLECTION_PART => '已催收，部分还款',
        self::PROMISE_COLLECTION => '承诺还款',
        self::SUCCESS_COLLECTION => '催收成功',


    ];

    //催收中状态
    public static $collection_status = [
        self::IN_COLLECTION => '催收中',
        self::ALREADY_COLLECTION => '已催收',
        self::ALREADY_COLLECTION_PART => '已催收，部分还款',
    ];
    //催收类型
    const SMS = 1;
    const PHONE = 2;
    const VISIT = 3;
    const PARTNER = 4;

    public static $type = [
        self::PHONE => '电话催收',
        self::VISIT => '上门催收',
        self::PARTNER => '合作方催收',
        self::SMS => '短信催收',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%loan_collection_record}}';
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

    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'loan_record_id', 'repayment_id', 'loan_person_id', 'loan_status', 'type',
            'collection_at', 'collection_comment', 'status', 'op_user',  'created_at', 'updated_at'],'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    /**
     * 获取借款人信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'loan_person_id'));
    }

    /**
     * 获取借款人信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanRepayment()
    {
        return $this->hasOne(LoanRepayment::className(), array('id' => 'repayment_id'));
    }
}