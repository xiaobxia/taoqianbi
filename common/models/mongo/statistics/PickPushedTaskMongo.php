<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 选人平台短信推送任务表
 * @author lujingfeng
 */
class PickPushedTaskMongo extends ActiveRecord{
    
    const SEND_TYPE_SMS = 1;    //短信
    const SEND_TYPE_MSG = 2;    //推送
    
    const TASK_STATUS_UNEXEC = 0;   //未执行
    const TASK_STATUS_DELAY  = 1;   //待执行
    const TASK_STATUS_EXEC   = 2;   //执行

    public static function getDb(){
        return Yii::$app->get('mongodb');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'kdkj_pick_pushed_set_task';
    }

    public function attributes(){
        return [
            '_id',
            'id',
            'name',             //用户包名称
            'code',             //用户包标识
            'type',             //发送类型      1短信 2推送
            'begin_time',       //开始执行时间
            'number',           //发送用户数
            'status',           //任务状态  0未执行 1待执行 2执行
            'content',          //短信内容
            'created_at',       //生成时间
            'updated_at',       //更新时间
        ];
    }
    
    /**
     * 添加数据
     */
    public static function insertData($name, $code, $begin_time, $number, $content, $type=self::SEND_TYPE_SMS, $status = 0) {
        $model = new self();
        $model->created_at =  date("Y-m-d H:i:s");
        $last_id = self::getLastId();
        $model->id = ++$last_id;
        $model->name = $name;
        $model->code = $code;
        $model->type = $type;
        $model->begin_time = strtotime($begin_time);
        $model->number = $number;
        $model->content = $content;
        $model->status = $status;
        $model->updated_at = date("Y-m-d H:i:s");
    
        if($model->save()) {
            return $model;
        } else {
            Yii::error("insert PickPushedTaskMongo error. name: {$name}, type:{$type}");
            return false;
        }
        return false;
    }
    
    /**
     * 更新数据
     */
    public static function updateData($_id, $name, $code, $begin_time, $number, $content, $type=self::SEND_TYPE_SMS, $status = 0) {
        $model = static::findOne(['_id'=>$_id]);
        $model->name = $name;
        $model->code = $code;
        $model->type = $type;
        $model->begin_time = strtotime($begin_time);
        $model->number = $number;
        $model->content = $content;
        $model->status = $status;
        $model->updated_at = date("Y-m-d H:i:s");
        
        if($model->save()) {
            return $model;
        } else {
            Yii::error("save PickPushedTaskMongo error. name: {$name}, type:{$type}");
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
