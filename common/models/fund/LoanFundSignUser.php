<?php

namespace common\models\fund;

use Yii;

/**
 * 借款资金签约用户
 * This is the model class for table "{{%loan_fund_sign_user}}".
 *
 * @property string $id
 * @property string $fund_id 资金ID
 * @property string $user_id 用户ID
 * @property integer $status 状态
 * @property string $card_no 卡号
 * @property string $created_at 创建时间 
 * @property string $updated_at 更新时间
 * @property string $data 数据
 * 
 */
class LoanFundSignUser extends \yii\db\ActiveRecord
{
    const STATUS_UNSIGN = 0;//未签约
    const STATUS_SIGN = 1;//已经签约
    const STATUS_SIGN_ACTIVE =2;//签约并已经激活
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_fund_sign_user}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data'],'default','value'=>''],
            [['fund_id', 'user_id','card_no'], 'required'],
            [['fund_id', 'user_id', 'status'], 'integer'],
            [['user_id', 'fund_id'], 'unique', 'targetAttribute' => ['user_id', 'fund_id'], 'message' => 'The combination of 资金ID and 用户ID has already been taken.'],
            [['data'],'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fund_id' => '资金ID',
            'user_id' => '用户ID',
            'card_no' => '卡号',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'data'=>'数据'
        ];
    }
    
    /**
     * 添加记录
     * @param integer $user_id 用户ID
     * @param integer $fund_id 借款ID
     * @param string $card_no 卡号
     * @param integer $status 签约状态
     * @param array $data 签约数据
     * @return integer
     */
    public static function add($user_id, $fund_id, $card_no, $status, $data) {
        if($status==self::STATUS_SIGN_ACTIVE) {
            //其他签约状态改为未激活
            static::updateAll(['status'=>self::STATUS_SIGN], '`user_id`='.(int)$user_id.' AND `fund_id`='.(int)$fund_id.' AND `status`='.self::STATUS_SIGN_ACTIVE);
        }
        $sql = 'INSERT INTO '.static::tableName().' (`user_id`, `fund_id`, `card_no`, `status`, `created_at`, `updated_at`,`data`) VALUES (:user_id, :fund_id, :card_no, :status, :created_at, :updated_at, :data)'
            . ' ON DUPLICATE KEY UPDATE `status`=VALUES(`status`),`updated_at`=VALUES(`updated_at`)'.($data ? ', `data`=VALUES(`data`)' : '');
        return static::getDb()->createCommand($sql,[
            ':user_id'=>(int)$user_id, 
            ':fund_id'=>$fund_id, 
            ':card_no'=>$card_no,
            ':status'=>$status, 
            ':data'=> $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '',
            ':created_at'=>time(), 
            ':updated_at'=>time()
        ])->execute();
    }
    
    /**
     * 是否已经签名
     * @param integer $user_id 用户ID
     * @param integer $fund_id 资方ID
     * @param string $card_no 银行卡
     * @return static|null ActiveRecord instance matching the condition, or `null` if nothing matches.
     */
    public static function getSignedRecord($user_id, $fund_id, $card_no) {
        return static::findOne([
            'user_id'=>(int)$user_id,
            'fund_id'=>(int)$fund_id,
            'card_no'=>trim($card_no),
            'status'=>[static::STATUS_SIGN, static::STATUS_SIGN_ACTIVE]
        ]);
    }
    
    /**
     * 获取数据
     * @return []
     */
    public function getData() {
        return $this->data ? json_decode($this->data, true) : [];
    }
}
