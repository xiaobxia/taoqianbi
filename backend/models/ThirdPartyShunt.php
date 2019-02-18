<?php
namespace backend\models;

use Yii;
use yii\base\Model;

/**
 *  第三方分流   
 *
 */
class ThirdPartyShunt  extends \yii\db\ActiveRecord
{

    
    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%third_party_shunt}}';
    }
    
    public function rules()
    {
        return [
            [['name', 'log_url','status','type_id','remark','number','rate','sort'], 'required'],
            ['trait','string'],
            ['url','url'],
            ['log_url','file','extensions'=>['png','jpg','gif'],'maxSize'=>1024*1024*1024],
        ];
    }
    
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type_id' => Yii::t('app', '类型ID'),
            'name' => Yii::t('app', '名称'),
            'log_url' => Yii::t('app', 'log地址'),
            'status' => Yii::t('app', '状态'),
            'remark' => Yii::t('app', '说明'),
            'number' => Yii::t('app', '数量'),
            'rate' => Yii::t('app', '利率'),
            'sort' => Yii::t('app', '排序'),
            'trait'=> Yii::t('app','特点'),
        ];
    }
    
}
