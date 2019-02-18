<?php
namespace common\models\risk;

use Yii;

/**
 *

 *
 * RuleCheckReport model
 *
 * @property integer $id
 * @property integer $r_id
 * @property integer $o_id
 * @property string $result
 * @property string $report_time
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class RuleCheckReport extends MActiveRecord{

    const TYPE_RULE = 0;
    const TYPE_NODE = 1;
    const TYPE_RULE_PERSON = 2;
    const TYPE_NODE_PERSON = 3;
    const TYPE_VALUE = 4; // 模型修改后，最终作为第一步特征获取的类型

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule_check_report}}';
    }

    public static function addReport($r_id, $o_id, $result, $type){
    	$report = new RuleCheckReport([
    			'r_id' => $r_id,
    			'o_id' => $o_id,
                'result' => $result,
    			'type' => $type,
    			'report_time' => date("Y-m-d h:i:s"),
    		]);

        if ($type == self::TYPE_VALUE) {
            $result = json_decode($result, true);
            if (isset($result['value'])) {
                $value = $result['value'];
            }elseif(isset($result['risk'])){
                $value = $result['risk'];
            }else{
                $value = -1;
            }
            $report->value = $value;
        }elseif(in_array($type, [self::TYPE_NODE_PERSON])){
            $result = json_decode($result, true);
            if (isset($result['value'])) {
                $value = $result['value'];
            }elseif(isset($result['score'])){
                $value = $result['score'];
            }else{
                $value = -1;
            }
            $report->value = $value;
        }

    	return $report->save();

    }

    public function getName(){
        if (in_array($this->type, [self::TYPE_RULE, self::TYPE_RULE_PERSON, self::TYPE_VALUE])) {
            $a = Rule::find()->where(['id' => $this->r_id, 'status' => Rule::STATUS_ACTIVE])->one();
            if (empty($a)) {
                return "未找到对应规则";
            }
        } elseif ($this->type == self::TYPE_NODE || $this->type == self::TYPE_NODE_PERSON) {
            $a = RuleNode::find()->where(['id' => $this->r_id, 'status' => RuleNode::STATUS_ACTIVE])->one();
            if (empty($a)) {
                return "未找到对应规则节点";
            }
        } else {
            return "未知的类型";
        }

        return $a->name;
    }

    public static function getReportTree($params, $score = 100){

        $root_node_result = self::find()->where(['r_id' => $params['node']->id, 'o_id' => $params['p_id'], 'status' => self::STATUS_ACTIVE, 'type' => self::TYPE_NODE_PERSON])->orderBy("id desc")->limit(1)->one();

        $child_nodes = self::getChildren($params['node']->id);

        if (empty($child_nodes)) {
            if (empty($root_node_result)) {
                return ["text" => $params['node']->name . " 得分：暂无"];
                // return ["text" => $params['node']->name . '权重：' . round($params['node']->weight / 100, 2) . "%，得分：暂无"];
            }
            $basic_result = self::find()->where(['r_id' => $params['node']->rulemap->r_id, 'o_id' => $params['p_id'], 'status' => self::STATUS_ACTIVE, 'type' => self::TYPE_VALUE])->orderBy("id desc")->limit(1)->one();
            return ["text" => $params['node']->name . " 得分："
                . round($root_node_result->result * $score / 100, 2)
                . "/" . $score
                . " ------ 规则：" . $params['node']->rulemap->rule->name . "，结果：" . $basic_result->value ];
            // return ["text" => $params['node']->name . '权重：' . round($params['node']->weight / 100, 2) . "%，得分：" . $root_node_result->result .
            //     "，规则：" . $root_node_result->name . "，结果：" . $basic_result->value ];
        }

        $children = [];
        foreach ($child_nodes as $key => $value) {
            $children[] = self::getReportTree(['node' => $value, 'p_id' => $params['p_id']], $score * $value->weight / 10000 );
        }

        if (empty($root_node_result)) {
            return [
                "text" => $params['node']->name . " 得分：暂无",
                "children" => $children,
                // "state" => [ "opened" => true ]
            ];
        }

        return [
            "text" => $params['node']->name . " 得分："
                . round($root_node_result->result * $score / 100, 2)
                . "/" .$score,
            "children" => $children,
            // "state" => [ "opened" => true ]
        ];


    }

    public static function getChildren($n_id){
        $rule_node_relations = RuleNodeRelation::find()->where(['parent_id' => $n_id, 'status' => RuleNodeRelation::STATUS_ACTIVE])->all();
        if (empty($rule_node_relations)) {
            return [];
        }
        $rule_nodes = [];
        foreach ($rule_node_relations as $key => $value) {
            $rule_nodes[] = $value->rulenode;
        }
        return $rule_nodes;
    }

    public static function getBasicReports($params){

        $rules = Rule::find()->where(['status' => Rule::STATUS_ACTIVE, 'type' => Rule::TYPE_CHECK])->orderBy("order desc")->all();

        $results = [];

        foreach ($rules as $rule) {
            $report = self::find()->where(['r_id' => $rule->id, 'o_id' => $params['p_id'], 'status' => self::STATUS_ACTIVE, 'type' => self::TYPE_VALUE])->orderBy("id desc")->limit(1)->one();
            if (!empty($report)) {
                $results[] = [
                    'name' => $rule->name,
                    'value' => $report->value,
                    'result' => json_decode($report->result, true),
                ];
            }

        }

        return $results;

    }


}
