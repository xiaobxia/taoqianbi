<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 选人平台用户包表
 * @author lujingfeng
 */
class PickPushedUserBagMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'kdkj_pick_pushed_user_bag';
    }

    public function attributes(){
        return [
            '_id',          
            'id',               //自增id
            'name',             //用户包名称
            'code',             //用户包标识
            'type',             //用户类型
            'reg_begin_time',   //注册开始时间
            'reg_end_time',     //注册结束时间
            'number',           //用户数
            'created_at',       //生成时间
            'updated_at',       //更新时间
            'custom',           //其他查询条件
            'exec_sql',         //执行sql
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['name', 'code'], 'unique'],
        ];
    }
    
    /**
     * 添加数据
     */
    public static function addData($name, $code, $type, $reg_begin_time, $reg_end_time, $number, $data=[]) {
        $_id = trim($name) . '_' . trim($code) . '_' . trim($type) . '_' . strtotime($reg_begin_time) . '_' . strtotime($reg_end_time);
        $model = static::findOne(['_id'=>$_id]);
        if (!$model) {
            $model = new self(['_id' => $_id]);
            $model->created_at =  date("Y-m-d H:i:s");
        }
        $last_id = self::getLastId();
        $model->id = ++$last_id;
        $model->name = $name;
        $model->code = $code;
        $model->type = $type;
        $model->reg_begin_time = $reg_begin_time;
        $model->reg_end_time = $reg_end_time;
        $model->updated_at = date("Y-m-d H:i:s");
        $model->number = $number;

        foreach($data as $key=>$val) {
            if(in_array($key, ['custom'])) {
                $model->$key = $val;
            }
        }
        if($model->save()) {
            return $model;
        } else {
            Yii::error("save PickPushedUserBagMongo error. name: {$name}, type:{$type}, reg_begin_time:{$reg_begin_time}, reg_end_time:{$reg_end_time}, data:". var_export($data, true));
            return false;
        }
        return false;
    }

    public static function getLastId(){
        $model = static::find()->orderBy('id DESC')->limit(1)->asArray()->one();
        if (empty($model)) {
            return 0;
        } else {
            return $model['id'];
        }
    }
}
