<?php

namespace common\models\loan;

use Yii;

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
class LoanCollectionDispatchLog extends \common\components\ARModel
{

	const TYPE_NEW = 0; //新用户
	const TYPE_OLD = 1; //老用户
    
    public $status;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_dispatch_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }


    public function getUserLoanOrderRepayment()
    {
        return $this->hasOne(\common\models\UserLoanOrderRepayment::className(), ['id' => 'order_repayment_id'])->select(['id', 'status', 'true_repayment_time']);
    }

    /**
     * @inheritdoc
     */
    // public function rules()
    // {
    //     return [
    //         [['loan_order_id', 'loan_admin_id', 'created_at', 'updated_at'], 'integer'],
    //         ['apply_reason', 'required'],
    //     ];
    // }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'order_id' => Yii::t('app', '借款订单id'),
            'order_repayment_id' => Yii::t('app', '还款id'),
            'user_id' => Yii::t('app', '用户id'),
            'type' => Yii::t('app', '类型'),
            'remark'=>Yii::t('app','未入催理由'),
            'created_at' => Yii::t('app', '创建时间'),
        ];
    }
}
