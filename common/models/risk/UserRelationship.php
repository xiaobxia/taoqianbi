<?php
/**
 *
 * @author Shayne Song
 * @date 2017-02-11
 *
 */

namespace common\models\risk;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class UserRelationship extends  ActiveRecord{

    const STATUS_ALL = 0;
    const VALIDATE_TURE = 1;
    const VALIDATE_FALSE = -1;

    public static $status = [
        self::STATUS_ALL => '全部',
        self::VALIDATE_FALSE => '已停用',
        self::VALIDATE_TURE => '启用中',
    ];

    public static function tableName(){
        return '{{%rcm_user_relationship}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb(){
        return Yii::$app->get('db_kdkj');
    }

    public static $status_list = [
        self::VALIDATE_TURE => '启用',
        self::VALIDATE_FALSE => '禁用',
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(){
        return [
            TimestampBehavior::className(),
        ];
    }


    public function attributeLabels(){
        return [
            'id' => 'id',
            'user_id' => '用户id',
            'relation_id' => '关系id',
            'value'    => '属性值',
            'status' => '是否启用',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'message' => '备注',
        ];
    }

}