<?php
namespace common\components;

use Yii;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;

/**
 * 前后台模型基类
 * @author skilly
 */
class ARModel extends \yii\db\ActiveRecord
{


    /**
     * 覆写父类方法，目的在于设定关联表的别名，设定为方法名
     */
    public function hasMany($class, $link)
    {
        // 获取调用的方法
        $method = debug_backtrace()[1]['function'];
        // 获取关联名
        preg_match('/^get(.*)$/U', $method, $res);
        // 获取别名
        $alias = lcfirst($res[1]);

        return parent::hasMany($class, $link)->from([$alias => $class::tableName()]);
    }

    /**
     * 覆写父类方法，目的在于设定关联表的别名，设定为方法名
     */
    public function hasOne($class, $link)
    {
        // 获取调用的方法
        $method = debug_backtrace()[1]['function'];
        // 获取关联名
        preg_match('/^get(.*)$/U', $method, $res);
        // 获取别名
        $alias = lcfirst($res[1]);

        return parent::hasOne($class, $link)->from([$alias => $class::tableName()]);
    }

    /**
     * 覆写父类方法，目的在于改为实例化自定义的 common\componets\ARQuery
     */
    public static function find()
    {
        $modelClass = get_called_class();

        return Yii::createObject(\yii\db\ActiveQuery::className(), [$modelClass])->from([lcfirst(\common\helpers\StringHelper::basename($modelClass)) => $modelClass::tableName()]);
    }
}
