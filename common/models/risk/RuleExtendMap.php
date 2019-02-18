<?php
namespace common\models\risk;

use Yii;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "kdcp.tb_rule_extend_mapping".
 *
 * @property integer $id
 * @property integer $rule_id
 * @property string $expression
 * @property string $result
 * @property integer $order
 * @property integer $state
 * @property string $create_time
 * @property string $update_time
 * @property integer $status
 */
class RuleExtendMap extends MActiveRecord
{
    //启用与停用
    const STATE_USABLE = 0;
    const STATE_DISABLE = 1;
    const STATE_DEBUG = 3;

    static $label_state = [
        self::STATE_USABLE  => '可用',
        self::STATE_DISABLE => '停用',
        self::STATE_DEBUG   => '调试'
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%rule_extend_mapping}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rule_id','order'], 'required'],
            [['rule_id', 'order', 'state', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['expression'], 'string', 'max' => 256],
            [['result'], 'string', 'max' => 256]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'rule_id' => Yii::t('app', '对应扩展特征ID'),
            'expression' => Yii::t('app', '表达式'),
            'result' => Yii::t('app', '结果'),
            'order' => Yii::t('app', '优先级'),
            'state' => Yii::t('app', '状态'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '存储状态 0 可用 1删除'),
        ];
    }

    public static function findModel($id){
        if (($model = self::findOne($id)) !== null) {
            return  $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function approve(){
        $this->state = self::STATE_USABLE;
        return $this->save();
    }
    public function reject(){
        $this->state = self::STATE_DISABLE;
        return $this->save();
    }

    public static function getExtendRuleMapping($rule_id){
        return RuleExtendMap::find()->where(['state' => RuleExtendMap::STATE_USABLE, 'status' => RuleExtendMap::STATUS_ACTIVE, 'rule_id' => $rule_id])->orderBy('order ASC')->all();
    }
    
}