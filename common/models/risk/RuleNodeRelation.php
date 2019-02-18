<?php
namespace common\models\risk;

use Yii;

/**
 *

 *
 * Rule model
 *
 * @property integer $id
 * @property integer $node_id
 * @property integer $parent_id
 * @property integer $parent_result
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class RuleNodeRelation extends MActiveRecord{

    const STATE_USABLE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule_node_relation}}';
    }

    public function getRuleNode(){
    	return RuleNode::find()->where(['id' => $this->node_id, 'status' => RuleNode::STATUS_ACTIVE])->one();
    }


}
