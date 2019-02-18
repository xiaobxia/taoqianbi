<?php
namespace common\models\mongo\risk;

use Yii;
use yii\mongodb\ActiveRecord;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeRelation;


/**
 *

 *
 * RuleCheckReport model
 *
 */
class MobileContactsReportMongo extends ActiveRecord{

    const STATUS_NO_REMARK = 0;
    const STATUS_ALREAD_REMARK = 1;

    public static $status = [
        self::STATUS_NO_REMARK=>'未备注',
        self::STATUS_ALREAD_REMARK=>'已备注',

    ];

    public static function getDb(){
        return Yii::$app->get('mongodb_rule');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'address_report';
    }

    public function attributes()
    {
        return [
            '_id',
            'mobile',
            'user_ids',
            'count',
            'status',
            'remark',
            'remark_created_time',
            'category'
        ];
    }

    public static function AddMobileUserId($mobile, $name, $user_id){

        $report = MobileContactsReportMongo::find()->where(['mobile' => $mobile])->one();

        $name_key = md5($name);

        if (empty($report)) {
            $report = New MobileContactsReportMongo([
                'mobile' => $mobile,
                'user_ids' => [$name_key => ['user_id'=>[$user_id => 0],'count' => 1, 'name' => $name]],
                'count' => 1,
                'status' => MobileContactsReportMongo::STATUS_NO_REMARK,
                'remark' => '',
                'remark_created_time' => '',
                'category' => ''
            ]);
            return $report->save();
        }

        if( isset($report->user_ids[$user_id]) ){
            return true;
        } else {
            if (empty($report->user_ids)) {
                $report->user_ids =  [$name_key => ['user_id'=>[$user_id => 0],'count' => 1, 'name' => $name]];
                $report->count = 1;
            }else{
                $user_ids = $report->user_ids;
                if(array_key_exists($name_key, $user_ids) ){
                    $a = $user_ids[$name_key];
                    if(array_key_exists($user_id, $a)){
                        return true;
                    }
                    $a['user_id'][$user_id] = 0;
                    $a['count'] ++;
                    $user_ids[$name_key] = $a;
                }else{
                    $user_ids[$name_key] = ['user_id'=>[$user_id => 0],'count' => 1, 'name' => $name];
                }

                $report->user_ids = $user_ids;
                $report->count += 1;
            }
        }

        $report->save();
    }


    public static function isWhiteListContact($phoneNumber){
        $tag = MobileContactsReportMongo::find()->where(['mobile' => $phoneNumber, 'category' => "网络电话"])->one();
        if(empty($tag)){
            return true;
        } else {
            return false;
        }
    }




}
