<?php
namespace common\models\risk;

use Yii;

/**
 *

 *
 * Rule model
 *
 * @property integer $id
 * @property integer $r_n_id
 * @property integer $r_id
 * @property string $params
 * @property integer $state
 * @property integer $order
 * @property integer $weight
 * @property string $description
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class RuleNodeMap extends MActiveRecord{

    const STATE_USABLE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule_node_map}}';
    }

    public function getRule(){
    	return Rule::find()->where(['id' => $this->r_id, 'status' => Rule::STATUS_ACTIVE])->one();
    }


}
