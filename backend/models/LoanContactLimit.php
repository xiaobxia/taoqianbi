<?php

namespace backend\models;

use Yii;
/**
 * This is the model class for table "{{%loan_contact_limit}}".
 * 短信催收限制表
 */
class LoanContactLimit extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_contact_limit}}';
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
            [['order_id', 'operator_id', 'contact_phone'], 'integer'],
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
            'operator_id' => Yii::t('app', '操作者ID'),
            'contact_phone' => Yii::t('app', '联系人电话'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
    //获取限制表记录
    public static function getLimitRecord($order_id,$operator_id){
        $limit_record = self::find()->where(['order_id'=>$order_id,'operator_id'=>$operator_id])->asArray()->all();
        // $time = time();
        // $two_days = 3600*24*2;
        // //判断时间是否大于三天
        // foreach ($limit_record as $key => $value) {
        //     if (($time-$value['created_at'])<$two_days) {
        //         unset($limit_record[$key]);
        //     }
        // }
        return $limit_record;
    }

    public static function conditionOne($condition){
        return self::find()->where($condition)->limit(1)->one();
    }
}
