<?php
namespace common\models;

use common\models\risk\Rule;
use common\models\risk\RuleNodeMap;
use common\models\risk\RuleNodeRelation;
use common\services\RiskControlService;
use Yii;
use yii\db\ActiveRecord;

/**
 * UserLoginLog model
 *
 * @property integer $id
 * @property integer $user_id
 */
class RuleNode extends ActiveRecord
{

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%rule_node}}';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('db_kdkj');
	}
	public function getItem($rule_node_id){

		$nodes =$this->getChildNodes($rule_node_id);
        if(!empty($nodes)){
			$node_rules = [];
			foreach($nodes as $node){

				$node_rules[] = $this->getItem($node);
			}
           return $node_rules;
		}
		$node_rule = $this->getNodeRule($rule_node_id);

		return $node_rule;
	}

	public function getNodeRule($node_id){
		$rule_id =  RuleNodeMap::find()
			->where([ 'r_n_id' => $node_id, 'state' => RuleNodeMap::STATE_USABLE, 'status' => RuleNodeMap::STATUS_ACTIVE ])
			->one();
		return Rule::find()->where(['id'=>$rule_id['r_id'],'status'=>Rule::STATE_USABLE])->asArray()->one();
	}
	public function getChildNodes($now_node_id = 0){

		$condition = [
			'status' => RuleNodeRelation::STATUS_ACTIVE
		];

		if(!empty($now_node_id)){
			$condition['parent_id'] = $now_node_id;
		}

		$relations = RuleNodeRelation::find()->where($condition)->all();

		if (empty($relations)) {
			return null;
		}
		$nodes = [];
		foreach ($relations as $key => $relation) {

			$nodes[] = $relation->rulenode->id;
		}

		return $nodes;

	}
}