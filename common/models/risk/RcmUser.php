<?php
/**
 *
 * @author Shayne Song
 * @date 2017-02-17
 *
 */

namespace common\models\risk;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class RcmUser extends  ActiveRecord{

    const STATUS_ALL = 0;
    const VALIDATE_TURE = 1;
    const VALIDATE_FALSE = -1;

    public static $status = [
        self::STATUS_ALL => '全部',
        self::VALIDATE_FALSE => '已停用',
        self::VALIDATE_TURE => '启用中',
    ];

    public static function tableName(){
        return '{{%rcm_user}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb(){
        return Yii::$app->get('db_kdkj');
    }

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
            'name' => '姓名',
            'id_number' => '身份证号码',
            'phone' => '手机号',
            'status' => '是否启用',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'message' => '备注',
        ];
    }

}