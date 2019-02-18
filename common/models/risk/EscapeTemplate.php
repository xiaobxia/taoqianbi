<?php
namespace common\models\risk;

use Yii;

/**
 * This is the model class for table "kdkj.tb_escape_template".
 *
 * @property integer $id
 * @property string $name
 * @property integer $state
 * @property string $create_time
 * @property string $update_time
 * @property integer $status
 */
class EscapeTemplate extends MActiveRecord
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
        return '{{%escape_template}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'],'required'],
            [['state', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 128],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', '名称'),
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
}