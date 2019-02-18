<?php

namespace common\models\fund;

use Yii;

/**
 * This is the model class for table "{{%loan_fund_day_quota}}".
 *
 * @property integer $id
 * @property integer $fund_id 资方ID
 * @property string $date 日期 格式 Y-m-d
 * @property integer $incr_amount 预增加配额
 * @property integer $decr_amount 预减少配额
 * @property string $created_at 
 * @property string $updated_at
 */
class LoanFundDayPreQuota extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_fund_day_pre_quota}}';
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
            [['fund_id', 'date','incr_amount', 'decr_amount'], 'required'],
            [['fund_id', 'incr_amount', 'decr_amount'], 'integer'],
            [['incr_amount', 'decr_amount'], 'integer', 'min'=>1],
            [['date'], 'date', 'format'=>'php:Y-m-d'],
            [['fund_id', 'date'], 'unique', 'targetAttribute' => ['fund_id', 'date'], 'message' => '资金ID 日期 重复', 'when' => function ($model) {
                return  $model->isNewRecord || ($model->fund_id!=$model->getOldAttribute('fund_id')) || ($model->date!=$model->getOldAttribute('date'));
            }],
        ];
    }
    
    public function attributeHints() {
        return [
            'date'=>'格式为 2017-01-03',
            'incr_amount'=>'单位为分',
            'decr_amount'=>'单位为分',
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
            'date' => '日期',
            'incr_amount' => '预增加配额',
            'decr_amount' => '预减少配额',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
    
    /**
     * 添加预配额记录
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $incr_amount 预增加配额 单位为分
     * @param integer $decr_amount 预减少配额 单位为分
     */
    public static function add($fund_id, $date, $incr_amount, $decr_amount) {
        $sql = 'INSERT IGNORE INTO '.static::tableName().' (`fund_id`, `date`, `incr_amount`, `decr_amount`,`created_at`,`updated_at`) VALUES (:fund_id, :date, :incr_amount, :decr_amount,:created_at,:updated_at)';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':incr_amount'=>trim($incr_amount),
            ':decr_amount'=>trim($decr_amount),
            ':created_at'=>time(),
            ':updated_at'=>time(),
        ])->execute();
    }
    
    /**
     * 增加预配额
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param string $field 增加的字段 单位为分 
     * @param integer $incr_quota 增加额度
     */
    public static function increase($fund_id, $date, $field, $incr_quota) {
        if($field !== 'incr_amount' && $field !== 'decr_amount') {
            throw new \Exception("不支持的字段 {$field}");
        } else if($date == date('Y-m-d')) {
            throw new \Exception("不能修改当天的 预留额度");
        }
        
        $record = static::findOne(['fund_id'=>$fund_id, 'date'=>$date]);
        if(!$record) {
            static::add($fund_id, $date, 0, 0);
        }
        
        $sql = 'UPDATE '.static::tableName().' SET `'.$field.'`=(`'.$field.'` + :incr_quota) WHERE `fund_id`=:fund_id AND `date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':incr_quota'=>$incr_quota,
        ])->execute();
    }
    
    /**
     * 预减少配额
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $decr_quota 要减少的配额 单位为分
     * @throws \Exception
     */
    public static function decrease($fund_id, $date, $field, $decr_quota) {
        if($field !== 'incr_amount' && $field !== 'decr_amount') {
            throw new \Exception("资方预算额度不支持的字段 {$field}");
        } else if($date == date('Y-m-d')) {
            throw new \Exception("不能修改当天的 预留额度");
        }
        
        $record = static::findOne(['fund_id'=>$fund_id, 'date'=>$date]);
        if(!$record) {
            static::add($fund_id, $date, 0, 0);
        }
        
        $sql = 'UPDATE '.static::tableName().' SET `'.$field.'`=(`'.$field.'` - :decr_quota) WHERE `fund_id`=:fund_id AND `date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':decr_quota'=>$decr_quota,
        ])->execute();
    }
}
