<?php
namespace common\models\mongo\statistics;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 选人平台用户包对应数据表
 * @author lujingfeng
 */
class PickUserBagDataMongo extends ActiveRecord{
    private static $collectionName = 'kdkj_pick_user_bag_data';

    public static function getDb(){
        return Yii::$app->get('mongodb');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return self::$collectionName;
    }
    
    public static function setCollectionName($tag){
        self::$collectionName = $this->collectionName . '_' . $tag;
        return self;
    }

    public function attributes(){
        return [
            '_id',          
            'name',         //用户包名称
            'code',         //用户包唯一标识
            'user_id',      //用户ID
            'phone',        //手机号
            'status',       //发送状态  0初始状态 1成功 2失败
            'send_num',     //累计发送次数
            'success_num',  //累计成功次数
            'fail_num',     //累计失败次数
            'created_at',   //生成时间
            'updated_at'    //更新时间
        ];
    }
    
    /**
     * 添加数据
     */
    public static function addData($name, $code, $user_id, $phone, $data = []) {
        $_id = trim($code) . '_' . trim($user_id) . '_' . trim($phone);
        $model = self::findOne(['_id'=>$_id]);
        if (!$model) {
            $model = new self(['_id' => $_id]);
            $model->status = 0;
            $model->send_num = 0;
            $model->success_num = 0;
            $model->fail_num = 0;
            $model->created_at =  date("Y-m-d H:i:s");
        }
        $model->name = $name;
        $model->code = $code;
        $model->user_id = $user_id;
        $model->phone = $phone;
        $model->updated_at = date("Y-m-d H:i:s");
        
        foreach($data as $key=>$val) {
            if(in_array($key, ['status', 'send_num', 'success_num', 'fail_num'])) {
                if ($key == 'status') {
                    $model->$key = $val;
                } else {
                    $model->$key += $val;
                }
            }
        }
        
        if($model->save()) {
            return $model;
        } else {
            Yii::error("save PickUserBagDataMongo error. name: {$name}, code:{$code}, user_id:{$user_id}, data:". var_export($data, true));
            return false;
        }
        return false;
    }
}
