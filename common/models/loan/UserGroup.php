<?php
namespace common\models\loan;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\loan\LoanCollection;
/**
 * UserGroup model
 *
 */
class UserGroup extends ActiveRecord 
{

   
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%lzg_user_group}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     *从数据库中查找催收分组信息
     *备注：已废弃（分组固定，无需保存在数据库中）
     */
    // public static function lists(){
    //     $lists = self::find()->all();
    //     $res = array();
    //     if(!empty($lists)){
    //         foreach ($lists as $key => $item) {
    //             $res[$item['id']] = $item;
    //         }
    //     }
    //     return $res;
    // }

    /**
     *催收人员分组信息保存在LoanCollection的静态变量中
     */
    public static function lists(){
        return LoanCollection::$group;
    }
}
