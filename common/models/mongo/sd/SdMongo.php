<?php
namespace common\models\mongo\sd;

use Yii;
use yii\mongodb\ActiveRecord;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeRelation;
use common\models\risk\Rule;

/**
 * author wolfbian
 * date 2016-10-10
 *
 * RuleCheckReport model
 *
 */
class SdMongo extends ActiveRecord{

    const TYPE_RULE = 0;
    const TYPE_NODE = 1;
    const TYPE_RULE_PERSON = 2;
    const TYPE_NODE_PERSON = 3;
    const TYPE_VALUE = 4; // 模型修改后，最终作为第一步特征获取的类型


    public static function getDb(){
        return Yii::$app->get('mongodb_rule');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'sd';
    }

    public function attributes()
    {
        return [
            '_id',
            'userInfo'
        ];
    }

    public static function addSdInfo($_id, $data){
        $report = self::find()->where(['_id' => $_id])->one();
        if (empty($report)) {
            $report = new self(['_id' => $_id]);
        }
        $report->userInfo = $data;
        return $report->save();
    }


}
