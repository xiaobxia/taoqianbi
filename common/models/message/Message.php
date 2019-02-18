<?php

namespace common\models\message;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\NotFoundHttpException;
/**
 * This is the model class for table "{{%message}}".
 */
class Message extends ActiveRecord
{
    const MENU_USER         = 1; // 用户管理
    const MENU_LOAN         = 2; // 借款管理
    const MENU_FINANCIAL    = 3; // 财务管理
    const MENU_ZMOP         = 4; // 风控管理
    const MENU_COLLECTION   = 5; // 催收管理
    const MENU_CUSTOM       = 6; // 客服管理
    public static $menu = [
        self::MENU_USER         => '用户管理',
        self::MENU_LOAN         => '借款管理',
        self::MENU_FINANCIAL    => '财务管理',
        self::MENU_ZMOP         => '风控管理',
        self::MENU_COLLECTION   => '催收管理',
        self::MENU_CUSTOM       => '客服管理',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%message}}';
    }

    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }

    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    // 读取通知消息
    public static function getMessageList(){
        // 接收过滤信息
        $condition = '1 = 1';
        $query = self::find()->where($condition)->andWhere(["=", "delete_status", 0])->orderBy(['id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',self::getDb_rd())]);
        $pages->pageSize = 15;
        $message_list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(self::getDb_rd());

        return array('message_list'=>$message_list,'pages'=>$pages);
    }

    public static function messageViewById($id=0){
        $view_info  = self::findOne($id);
        if ($view_info) {
            return $view_info;
        }else{
            return false;
        }
    }
}
