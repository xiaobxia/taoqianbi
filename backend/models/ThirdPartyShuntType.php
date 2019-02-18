<?php
namespace backend\models;

use Yii;
use yii\base\Model;

/**
 *  第三方分流  类型 
 *
 */
class ThirdPartyShuntType  extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%third_party_shunt_type}}';
    }
    
    
    public function rules()
    {
        return [
            [['name', 'log_url','status','sort'], 'required'],
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
            'name' => Yii::t('app', '名称'),
            'log_url' => Yii::t('app', 'log地址'),
            'status' => Yii::t('app', '状态'),
            'created_at' => Yii::t('app', '创建时间'),
            'sort' => Yii::t('app', '排序'),
        ];
    }
   
    
}
