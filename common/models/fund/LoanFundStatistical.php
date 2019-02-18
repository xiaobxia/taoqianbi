<?php

namespace common\models\fund;

use Yii;

/**
 * This is the model class for table "{{%loan_fund_statistical}}".
 *
 * @property string $id
 * @property string $fund_id
 * @property string $date
 * @property string $all_lines
 * @property string $already_lines
 * @property string $remaining_lines
 * @property string $actuall_line
 * @property string $reimbursement_amount
 * @property string $actual_payment_amount
 * @property string $advance_amount
 * @property string $no_reimbursement
 * @property string $no_advances_amount
 * @property string $pay_show_margin
 */
class LoanFundStatistical extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_fund_statistical}}';
    }
    
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
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
            //[['fund_id', 'all_lines'], 'required'],
            [['fund_id', 'all_lines', 'already_lines', 'remaining_lines', 'actuall_line', 'reimbursement_amount', 'actual_payment_amount', 'advance_amount', 'no_reimbursement', 'no_advances_amount', 'pay_show_margin'], 'integer'],
            [['date'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fund_id' => 'Fund ID',
            'date' => 'Date',
            'all_lines' => 'All Lines',
            'already_lines' => 'Already Lines',
            'remaining_lines' => 'Remaining Lines',
            'actuall_line' => 'Actuall Line',
            'reimbursement_amount' => 'Reimbursement Amount',
            'actual_payment_amount' => 'Actual Payment Amount',
            'advance_amount' => 'Advance Amount',
            'no_reimbursement' => 'No Reimbursement',
            'no_advances_amount' => 'No Advances Amount',
            'pay_show_margin' => 'Pay Show Margin',
        ];
    }
}
