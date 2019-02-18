<?php

namespace common\models\loan;

use Yii;
/**
 * This is the model class for table "{{%loan_contact_review}}".
 *短信催收审核表
 */
class LoanContactReview extends \yii\db\ActiveRecord
{
    const APPLY_PASS = 1;  //申请通过
    const APPLY_WAIT = 0;    //拒绝通过
    const APPLY_REFUSE = -1;    //待审核
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_contact_review}}';
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
            [['order_id', 'apply_id', 'authorize_id'], 'integer'],
            ['created_at','default', 'value'=>time()],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'order_id' => Yii::t('app', '催收订单ID'),
            'apply_id' => Yii::t('app', '申请者ID'),
            'authorize_id' => Yii::t('app', '审核人ID'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
    //获取审核表记录
    public static function getApplyLog($order_id,$apply_id){
        return self::find()->where(['order_id'=>$order_id,'apply_id'=>$apply_id])->one();
    }
    //获取待审核的记录
    public static function getUnpassApply($order_id,$apply_id){
        return self::find()->where(['order_id'=>$order_id,'apply_id'=>$apply_id,'status'=>-1])->one();
    }

    public static function getPassApplyLog($order_id,$apply_id){
        return self::find()->where(['order_id'=>$order_id,'apply_id'=>$apply_id,'status'=>1])->one();
    }
}
