<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%visit_stat}}".
 */
class VisitStat extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_tag' => '来源标识',
            'ip' => 'ip',
            'created_at' => '创建时间',
            'source_url' => '监测-来源url',
            'current_url' => '监测-当前url',
            'remark' => '备注',
        ];
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%visit_stat}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}