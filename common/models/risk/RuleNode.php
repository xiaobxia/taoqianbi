<?php
namespace common\models\risk;

use Yii;

/**
 *

 *
 * Rule model
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $weight
 * @property integer $state
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class RuleNode extends MActiveRecord{

    const STATE_USABLE = 0;

    const LOW_RISK = 0;
    const MEDIUM_RISK = 1;
    const HIGH_RISK = 2;

    static $risks = [self::LOW_RISK, self::MEDIUM_RISK, self::HIGH_RISK];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule_node}}';
    }

    /**
     *

     *
     * param    name
     * param    description
     * return   array               [true/false, 'detail']
     */
    public static function addRuleNode($params){

    	$node_exist = self::find()->where(['name' => $params['name'], 'state' => self::STATE_USABLE, 'status' => self::STATUS_ACTIVE])->exists();

    	if ($node_exist) {
    		return [false, "节点名称已存在"];
    	}

    	$model = new RuleNode();
		$model->name = $params['name'];
		$model->description = $params['description'];
		$model->state = self::STATE_USABLE;
		$ret = $model->save();
		return [$ret, $ret ? "创建节点成功" : "创建节点失败".var_dump($model->getErrors())];

    }

    public static function createRuleNode(){
        $model = new RuleNode();
        $model->name = "(new node)";
        $model->weight = 0;
        $model->state = self::STATE_USABLE;
        $model->save();
        return $model;
    }

    /**
     *

     *
     * param    node_id
     * param    parent_id
     * param    parent_result
     * return   array               [true/false, 'detail']
     */
    public static function addRuleNodeRelation($params){

        $node_exist = self::find()->where(['id' => $params['node_id'], 'state' => self::STATE_USABLE, 'status' => self::STATUS_ACTIVE])->exists();
        if (!$node_exist) {
            return [false, "节点(" . $params['node_id'] .")不存在"];
        }

        if (!empty($params['parent_id'])) {
            $parent_node_exist = self::find()->where(['id' => $params['parent_id'], 'state' => self::STATE_USABLE, 'status' => self::STATUS_ACTIVE])->exists();

            if (!$parent_node_exist) {
                return [false, "父节点(" . $params['parent_id'] .")不存在"];
            }

            $node_relation_exist = RuleNodeRelation::find()->where(['parent_id' => $params['parent_id'], 'parent_result' => $params['parent_result'], 'status' => self::STATUS_ACTIVE])->exists();
            if ($node_relation_exist) {
                return [false, "父节点(" . $params['parent_id'] .")对应结果(". $params['parent_result'] . ")已存在"];
            }

        }else{
            $params['parent_id'] = 0;
        }

        $model = new RuleNodeRelation();
        $model->node_id = $params['node_id'];
        $model->parent_id = $params['parent_id'];
        $model->parent_result = $params['parent_result'];
        $ret = $model->save();
        return [$ret, $ret ? "创建节点关系成功" : "创建节点关系失败".var_dump($model->getErrors())];

    }

    /**
     *

     *
     * param    r_n_id
     * param    r_id
     * param    order
     * return   array               [true/false, 'detail']
     */
    public static function addRuleToNode($params){

        $node_exist = self::find()->where(['id' => $params['r_n_id'], 'state' => self::STATE_USABLE, 'status' => self::STATUS_ACTIVE])->exists();

        if (!$node_exist) {
            return [false, "未找到规则节点"];
        }

        $rule_exist = Rule::find()->where(['id' => $params['r_id'], 'state' => Rule::STATE_USABLE, 'status' => Rule::STATUS_ACTIVE])->exists();

        if (!$rule_exist) {
            return [false, "未找到规则"];
        }

        $model = new RuleNodeMap();
        $model->r_n_id = $params['r_n_id'];
        $model->r_id = $params['r_id'];
        $model->order = empty($params['order']) ? 0 : $params['order'];
        $model->state = RuleNodeMap::STATE_USABLE;
        $ret = $model->save();
        return [$ret, $ret ? "向节点添加规则成功" : "向节点添加规则失败".var_dump($model->getErrors())];

    }

    public function getRules(){
        return RuleNodeMap::find()->where(['r_n_id' => $this->id, 'status' => RuleNodeMap::STATUS_ACTIVE])->orderBy("order")->all();
    }

    public function getRuleMap(){
        return RuleNodeMap::find()->where(['r_n_id' => $this->id, 'status' => RuleNodeMap::STATUS_ACTIVE])->one();
    }

    public function getRuleRelation(){
        return RuleNodeRelation::find()->where(['node_id' => $this->id, 'status' => RuleNodeRelation::STATUS_ACTIVE])->all();
    }

    public static function getRootNodes(){
        $rule_node_relations = RuleNodeRelation::find()->where(['parent_id' => 0, 'status' => RuleNodeRelation::STATUS_ACTIVE])->all();
        $r_n = [];
        foreach ($rule_node_relations as $rule_node_relation) {
            $r_n[] = $rule_node_relation->RuleNode;
        }
        return $r_n;
    }

    public static function getTreeConstruct($id){

        $rule_node = self::find()->where(['id' => $id, 'status' => self::STATUS_ACTIVE])->one();

        $rule_node_relations = RuleNodeRelation::find()->where(['parent_id' => $id, 'status' => RuleNodeRelation::STATUS_ACTIVE])->all();
        if (empty($rule_node_relations)) {
            $rule_node_relations = [];
        }

        $children = [];
        foreach ($rule_node_relations as $value) {
            $children[] = self::getTreeConstruct($value->node_id);
        }

        if (empty($children)) {
            return ['id' => $id, 'name' => $rule_node->name, 'weight' => $rule_node->weight];
        }

        return ['id' => $id, 'name' => $rule_node->name, 'weight' => $rule_node->weight, 'children' => $children];
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
        return RuleNode::find()->where(['id'=>$rule_id['r_n_id'],'status'=>Rule::STATE_USABLE])->asArray()->one();
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
