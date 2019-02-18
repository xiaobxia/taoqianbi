<?php

namespace common\models\fund;

use Yii;

/**
 * This is the model class for table "{{%loan_fund_day_quota}}".
 *
 * @property integer $id
 * @property integer $fund_id 资方ID
 * @property string $date 日期 格式 Y-m-d
 * @property integer $remaining_quota 余下配额
 * @property integer $quota 配额
 * @property integer $loan_amount 放款金额
 * @property string $created_at
 * @property string $updated_at
 */
class LoanFundDayQuota extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_fund_day_quota}}';
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
            [['fund_id', 'date', 'remaining_quota', 'quota'], 'required'],
            [['fund_id', 'remaining_quota', 'quota', 'loan_amount'], 'integer'],
            [['date'], 'date', 'format'=>'php:Y-m-d'],
            [['quota'],'compare', 'compareAttribute'=>'remaining_quota','operator' => '>=', 'type' => 'number'],
            [['fund_id'],'exist','targetAttribute'=> 'id', 'targetClass'=>'\common\models\fund\LoanFund' ],
            [['fund_id', 'date'], 'unique', 'targetAttribute' => ['fund_id', 'date'], 'message' => '资金ID 日期 重复', 'when' => function ($model) {
                return  $model->isNewRecord || ($model->fund_id!=$model->getOldAttribute('fund_id')) || ($model->date!=$model->getOldAttribute('date'));
            }],
        ];
    }
    
    public function attributeHints() {
        return [
            'date'=>'格式为 2017-01-03',
            'quota'=>'单位为分',
            'remaining_quota'=>'单位为分',
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
            'remaining_quota' => '余下配额',
            'quota' => '配额',
            'loan_amount' => '放款金额',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
    
    /**
     * 添加配额记录
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $quota 配额 单位为分
     */
    public static function add($fund_id, $date, $quota) {
        $sql = 'INSERT IGNORE INTO '.static::tableName().' (`fund_id`, `date`, `quota`, `remaining_quota`,`created_at`,`updated_at`) VALUES (:fund_id, :date, :quota, :remaining_quota,:created_at,:updated_at)';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':quota'=>trim($quota),
            ':remaining_quota'=>trim($quota),
            ':created_at'=>time(),
            ':updated_at'=>time(),
        ])->execute();
    }
    
    /**
     * 添加配额
     * @param integer $fund_id 资方ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $incr_quota 要增加的配额 单位为分 
     * @param integer $decr_loan_amount 减少放款的金额 默认为要增加的配额（一般放款失败或切换资方时，回增当天的额度，所以当天的借款金额减少等值）
     */
    public static function increaseDayQuota($fund_id, $date, $incr_quota, $decr_loan_amount=null) {
        $sql = 'UPDATE '.static::tableName().' SET `remaining_quota`=(cast(`remaining_quota` as signed) + :incr_quota),`loan_amount`=(cast(`loan_amount` as signed) - :decr_loan_amount) WHERE `fund_id`=:fund_id AND `date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':incr_quota'=>$incr_quota,
            ':decr_loan_amount'=>$decr_loan_amount===null?$incr_quota:$decr_loan_amount,
        ])->execute();
    }
    
    /**
     * 添加配额
     * @param integer $fund_id 资方ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $incr_quota 要增加的配额 单位为分 
     * @param integer $decr_loan_amount 减少放款的金额 默认为要增加的配额（一般放款失败或切换资方时，回增当天的额度，所以当天的借款金额减少等值）
     */
    public static function increaseTotalQuota($fund_id, $date, $incr_quota, $decr_loan_amount=null) {
        $sql = 'UPDATE '.static::tableName().' as a LEFT JOIN '.LoanFund::tableName().' as b ON a.fund_id=b.id  SET `remaining_quota`=b.`can_use_quota`, `loan_amount`=(cast(`loan_amount` as signed) - :decr_loan_amount) WHERE a.`fund_id`=:fund_id AND a.`date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':decr_loan_amount'=>$decr_loan_amount===null?$incr_quota:$decr_loan_amount,
        ])->execute();
    }
    
    /**
     * 减少每日配额
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $decr_quota 要减少的配额 单位为分
     * @param integer $incr_loan_amount 增加放款的金额 默认为增加减少的额度（一般放款时，减少当天的额度，所以当天的借款金额增加等值）
     * @throws \Exception
     */
    public static function decreaseDayQuota($fund_id, $date, $decr_quota, $incr_loan_amount=null) {
        $sql = 'UPDATE '.static::tableName().' SET `remaining_quota`=(cast(`remaining_quota` as signed) - :decr_quota),`loan_amount`=(cast(`loan_amount` as signed) + :incr_loan_amount) WHERE `fund_id`=:fund_id AND `date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':decr_quota'=>(int)$decr_quota,
            ':incr_loan_amount'=>$incr_loan_amount===null?$decr_quota:$incr_loan_amount,
        ])->execute();
    }
    
    /**
     * 减少循环配额
     * @param integer $fund_id 基金ID
     * @param string $date 日期 格式为 YYYY-MM-DD
     * @param integer $decr_quota 要减少的配额 单位为分
     * @param integer $incr_loan_amount 增加放款的金额 默认为增加减少的额度（一般放款时，减少当天的额度，所以当天的借款金额增加等值）
     * @throws \Exception
     */
    public static function decreaseTotalQuota($fund_id, $date, $decr_quota, $incr_loan_amount=null) {
        $sql = 'UPDATE '.static::tableName().' as a LEFT JOIN '.LoanFund::tableName().' as b ON a.fund_id=b.id  SET `remaining_quota`=b.`can_use_quota`, `loan_amount`=(cast(`loan_amount` as signed) + :incr_loan_amount) WHERE a.`fund_id`=:fund_id AND a.`date`=:date';
        static::getDb()->createCommand($sql,[
            ':fund_id'=>(int)$fund_id,
            ':date'=>trim($date),
            ':incr_loan_amount'=>$incr_loan_amount===null?$decr_quota:$incr_loan_amount,
        ])->execute();
    }
}
