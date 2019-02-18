<?php
namespace common\models\mongo\risk;

use Yii;
use yii\base\Exception;
use yii\mongodb\ActiveRecord;

use common\models\LoanPerson;
use common\models\UserLoanOrder;

class RiskControlDataSnapshot extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_rule_new');
    }

    public static function collectionName(){
        return 'data_snapshot';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'order_id',
            'created_time',
            'order',
            'loan_person',
            'jxl',
            'yys',
            'td',
            'zm',
            'mg',
            'sauron',
            'jsqb',
            'jsqb_blacklist',
            'br',
            'bqs',
            'yx',
            'yx_af',
            'zzc',
            'zfb',
            'online_bank',
            'credit_jy',
            'card_infos',
            'user_detail',
            'user_contact',
            'user_loan_orders',
            'user_credit_total',
            'loan_person_degree',
            'user_proof_materia',
            'user_mobile_contacts',
            'user_login_upload_log',
            'user_login_upload_logs',
            'user_quota_person_info',
            'usable_user_loan_orders',
            'loan_collection_order',
            'user_loan_order_repayments',
            'is_back_trace',//是否是回测数据
            'external_account', //口袋记账数据
            'accumulation_fund', //用户公积金数据
            'face_id_card',
            'mhk_order',
            'wy',
            'jxl_phone_shot',
            'jxl_phone_match',
            'jxl_phone_match_all',
            'jxl_phone_ten',
            'jxl_phone_wten',
            'all_jxl_phone_match_error',
            'all_jxl_phone_error',
        ];
    }

    public static function findByOrderId($id){

        $model = self::find()->where(['order_id'=>$id])->limit(1)->one();


        if(empty($model)){
            throw new Exception("SnapShotData OrderId: $id in Mongo Not Found");
        }

        $data = [];


        foreach ($model as $key => $value) {
            $data[$key] = json_decode($value, true);
        }
        $data['order'] = new UserLoanOrder(json_decode($model['order'], true));
        $data['loan_person'] = new LoanPerson(json_decode($model['loan_person'], true));

        return $data;
    }

    public static function findForBackTest($id){

        $model = self::find()->where(['order_id'=>$id])->limit(1)->one();

        $data = [];
        if(empty($model)){
            return $data;
        }

        foreach ($model as $key => $value) {
            $data[$key] = json_decode($value, true);
        }
        $data['order'] = new UserLoanOrder(json_decode($model['order'], true));
        $data['loan_person'] = new LoanPerson(json_decode($model['loan_person'], true));

        return $data;
    }


    public static function saveSnapShot($data){

        $snapshot = new RiskControlDataSnapshot();
        $snapshot->order_id     = $data['order']->id;
        $snapshot->created_time = time();

        foreach($data as $key => $value) {
            $snapshot->$key = self::object_to_json($value);
        }

        return $snapshot->save();
    }

    private static function object_to_json($obj){

        return json_encode(self::object_to_array($obj), JSON_UNESCAPED_UNICODE);
    }

    private static function object_to_array($obj){

        if(empty($obj)) return $obj;

        if(is_object($obj)){

            $obj = $obj->attributes;

        }elseif(is_array($obj)){

            foreach ($obj as $key => $value) {
                $obj[$key] = self::object_to_array($value);
            }

        }

        return $obj;
    }

}