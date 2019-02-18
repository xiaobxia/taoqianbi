<?php
namespace common\models\mongo\mobileInfo;

use Yii;
use yii\mongodb\ActiveRecord;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeRelation;
use common\models\risk\Rule;

/**
 *

 *
 * RuleCheckReport model
 *
 */
class PhoneOperatorDataMongo extends ActiveRecord{

    const TYPE_RULE = 0;
    const TYPE_NODE = 1;
    const TYPE_RULE_PERSON = 2;
    const TYPE_NODE_PERSON = 3;
    const TYPE_VALUE = 4;


    public static function getDb(){
        return Yii::$app->get('mongodb_rule_new');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'phone_operator_data';
    }

    public function attributes()
    {
        return [
           '_id',
            'bill_list',
            'real_name_status',
            'real_name_time',
            'real_name_name',
            'real_name_id_card',
            'contact_list',
            'common_contactors',
            'raw_basic',
            'raw_calls',
            'raw_smses',
            'raw_nets',
            'raw_transactions',
        ];
    }

    public static function addPhoneInfo($data){
        $report = self::find()->where(['_id' => $data['user_id']])->one();
        if (empty($report)) {
            $report = new self(['_id' => $data['user_id']]);
        }
        $report->bill_list = $data['bill_list'];
        $report->real_name_status = $data['real_name_status'];
        $report->real_name_time = $data['real_name_time'];
        $report->contact_list = $data['contact_list'];
        $report->common_contactors = $data['common_contactors'];
        $report->real_name_name = $data['real_name_name'];
        $report->real_name_id_card = $data['real_name_id_card'];
        return $report->save();
    }

    public static function addRawPhoneInfo($data){
        $report = self::find()->where(['_id' => $data['user_id']])->one();
        if (empty($report)) {
            $report = new self(['_id' => $data['user_id']]);
        }
        $report->raw_basic = $data['raw_basic'];
        $report->raw_calls = $data['raw_calls'];
        $report->raw_smses = $data['raw_smses'];
        $report->raw_nets = $data['raw_nets'];
        $report->raw_transactions = $data['raw_transactions'];
        return $report->save();
    }
}
