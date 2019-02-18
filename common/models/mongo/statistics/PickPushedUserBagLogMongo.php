<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 选人平台用户包日志表
 * @author lujingfeng
 */
class PickPushedUserBagLogMongo extends ActiveRecord{

    public static function getDb(){
        return Yii::$app->get('mongodb');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'kdkj_pick_pushed_user_bag_log';
    }

    public function attributes(){
        return [
            '_id',          
            'name',             //用户包名称
            'code',             //用户包标识
            'user_num',         //发送用户数
            'send_num',         //发送短信数
            'success_num',      //发送成功数
            'fail_num',         //发送失败数
            'content',          //发送内容
            'created_at',       //生成时间
            'updated_at',       //更新时间
        ];
    }
    
    /**
     * 添加数据
     */
    public static function addData($code, $data = []) {
        $_id = trim($code);
        $model = self::findOne(['_id'=>$_id]);
        if (!$model) {
            $model = new self(['_id' => $_id]);
            $model->name = '';
            $model->user_num = 0;
            $model->send_num = 0;
            $model->success_num = 0;
            $model->fail_num = 0;
            $model->created_at =  date("Y-m-d H:i:s");
        }
        $model->code = $code;
        $model->updated_at = date("Y-m-d H:i:s");
    
        foreach($data as $key=>$val) {
            if(in_array($key, self::attributes())) {
                $model->$key = $val;
            }
        }
    
        if($model->save()) {
            return $model;
        } else {
            Yii::error("save PickPushedUserBagLogMongo error. code:{$code}, data:". var_export($data, true));
            return false;
        }
        return false;
    }
}
