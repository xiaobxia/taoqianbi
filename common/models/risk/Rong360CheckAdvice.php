<?php
namespace common\models\risk;

use Yii;

/**
 * This is the model class for table "rcm.tb_rong_360_check_advice".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $user_id
 * @property integer $rules_score
 * @property integer $anti_fraud_model_score
 * @property integer $credit_model_score
 * @property string $create_time
 * @property string $update_time
 * @property integer $status
 */
class Rong360CheckAdvice extends MActiveRecord
{

    public static function tableName()
    {
        return '{{%rong_360_check_advice}}';
    }

    public static function getDb(){
        return Yii::$app->get('db_rcm');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id','rules_score', 'anti_fraud_model_score', 'credit_model_score'], 'required'],
        ];
    }

    /**
     * 将融360的风控结果记录下来
     *
     * 用法：
     * @code php
     * $order_id = 3945876;
     * $user_id = 3900876;
     * $rules_score = 0;
     * $anti_fraud_model_score = 60;
     * $credit_model_score = 460;
     * Rong360CheckAdvice::addAdvice($order_id, $user_id, $rules_score, $anti_fraud_model_score, $credit_model_score);
     *
     *   // 返回true为修改或新增成功，false为失败
     * @endcode
     *
     * @param integer $order_id 对应的订单id
     * @param integer $user_id 对应的用户id
     * @param integer $rules_score 风控政策分
     * @param integer $anti_fraud_model_score 反欺诈模型分
     * @param integer $credit_model_score 信用模型分
     */
    public static function addAdvice($order_id, $user_id, $rules_score, $anti_fraud_model_score, $credit_model_score){
        // 存在则更新，不存在则新建
        $rong_360_check_advice = Rong360CheckAdvice::find()->where(['order_id' => $order_id])->one();
        if (empty($rong_360_check_advice)) {
            $rong_360_check_advice = new Rong360CheckAdvice();
            $rong_360_check_advice->order_id = $order_id;
        }

        $rong_360_check_advice->user_id = $user_id;
        $rong_360_check_advice->rules_score = $rules_score;
        $rong_360_check_advice->anti_fraud_model_score = $anti_fraud_model_score;
        $rong_360_check_advice->credit_model_score = $credit_model_score;

        return $rong_360_check_advice->save();
    }
}