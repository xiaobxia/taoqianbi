<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/8/3
 * Time: 16:36
 */

namespace common\models;


use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Class CreditShumei
 * @package common\models
 * @property integer $id
 * @property integer $user_id
 * @property string $sm_device_id
 * @property string $params
 * @property string $data
 * @property string $request_id
 * @property integer $status
 * @property integer $type
 * @property string $message
 * @property integer $created_at
 * @property integer $updated_at
 */
class CreditShumei extends ActiveRecord
{
    const STATUS_INIT = 0; //未获取报告
    const STATUS_SUCCESS = 1; //获取评分报告成功
    const STATUS_FAILED = -1; //获取评分报告失败

    const TYPE_LOAN = 1; //借贷风险识别
    const TYPE_SOCIALNET = 2; //社交网络分析服务
    const TYPE_SOCIALNET_NO_SMID = 3; //社交网络分析服务（无需数美ID）
    const TYPE_SMS_ANALYSIS = 4; //短信文本分析服务


    public static function tableName()
    {
        return '{{%credit_shumei}}';
    }


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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INIT],
        ];
    }

    /**
     * @param $condition
     * @return array|null|ActiveRecord
     */
    public static function findLatestOne($condition)
    {
        return self::find()->where($condition)->orderBy('id desc')->one();
    }
}