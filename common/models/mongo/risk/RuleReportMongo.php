<?php
namespace common\models\mongo\risk;


use Yii;
use yii\mongodb\ActiveRecord;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeRelation;
use common\models\risk\Rule;
use common\models\risk\RuleExtendMap;
/**
 *

 *
 * RuleCheckReport model
 *
 */
class RuleReportMongo extends ActiveRecord{
    public static $arr = [];
    const TYPE_RULE = 0;
    const TYPE_NODE = 1;
    const TYPE_RULE_PERSON = 2;
    const TYPE_NODE_PERSON = 3;
    const TYPE_VALUE = 4; // 模型修改后，最终作为第一步特征获取的类型


    public static function getDb(){
        return Yii::$app->get('mongodb_rule_new');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'rule_report';
    }

    public function attributes()
    {
        return [
            '_id',
            'basic_report',
            'score_report',
            'created_at',
            'updated_at'
        ];
    }

    public static function getValue($user_id,$rule_id = 0){
        $report = self::find()->where(['_id' => intval($user_id)])->one();
        $data = [];
        if(isset($report)&&isset($report['basic_report'])){
            $basic_report = $report['basic_report'];
            if($rule_id){
                if(isset($basic_report[$rule_id])){
                    $rule_data = $basic_report[$rule_id];
                    if(isset($rule_data['risk'])&&isset($rule_data['detail'])&&isset($rule_data['value'])){
                        $data[$rule_id] = [
                            'risk'=>$rule_data['risk'],
                            'detail'=>$rule_data['detail'],
                            'value'=>$rule_data['value']
                        ];
                    }
                }
            }else{
                foreach ($basic_report as $key =>$item){
                    if(isset($item['risk'])&&isset($item['detail'])&&isset($item['value'])){
                        $data[$key] = [
                            'risk'=>$item['risk'],
                            'detail'=>$item['detail'],
                            'value'=>$item['value']
                        ];
                    }
                }

            }
        }

        return $data;
    }

    public static function getScore($id){
        $report = self::find()->where(['_id' => intval($id)])->one();
        if (!isset($report) || !isset($report['score_report'])
            || !isset($report['score_report']['25'])
            || !isset($report['score_report']['26'])
            || !isset($report['score_report']['87'])) {
            return [0, 0, 0];
        }

        return [$report['score_report']['87'], $report['score_report']['25'], $report['score_report']['26']];
    }

    public static function getNewReportValue($id, $node)
    {
        $report = self::find()->where(['_id' => intval($id)])->one();
        if (!isset($report) || !isset($report['basic_report']) || !isset($report['basic_report'][$node]) || !isset($report['basic_report'][$node]['value'])) {
            return [
                'text' => '暂无',
                'children' => []
            ];
        } else {
            $nodeDataArray = [];
            $linkDataArray = [];
            $service = new Rule();
            $rule = Rule::find()->where(['id' => $node])->one();
            $service->generateTree($rule, $nodeDataArray, $linkDataArray);
            $arr = $service->arr;
            $children = [];
            foreach ($arr as $k => $v) {
                if(isset($report['basic_report'][$v]['value']) && isset($report['basic_report'][$v]['detail'])){
                    $children[] = $k . '----' . json_encode($report['basic_report'][$v]['detail']) . '----' . '结果：' . json_encode($report['basic_report'][$v]['value']);
                }
            }
            if (is_array($report['basic_report'][$node]['value'])){
                return [
                    'text' => '：' . $report['basic_report'][$node]['value']['txt'],
                    'children' => $children,
                ];
            }else{
                return [
                    'text' => '：' . $report['basic_report'][$node]['value'],
                    'children' => $children,
                ];
            }

        }
    }

    public static function addBasicReport($r_id, $o_id, $result){
        $report = self::find()->where(['_id' => $o_id])->one();
        if (empty($report)) {
            $report = new self(['_id' => $o_id]);
            $report->created_at = time();
        }
        $basic_report = empty($report->basic_report) ? [] : $report->basic_report;
        $basic_report[$r_id] = $result;
        $report->basic_report = $basic_report;
        $report->updated_at = time();

        return $report->save();
    }

    public static function saveBasicReport($rule_result){
        if (empty($rule_result)) {
            return false;
        }

        foreach ($rule_result as $user_id => $value) {
            $report = self::find()->where(['_id' => $user_id])->one();
            if (empty($report)) {
                $report = new self(['_id' => $user_id]);
                $report->created_at = time();
            }
            $basic_report = empty($report->basic_report) ? [] : $report->basic_report;

            foreach ($value as $rule_id => $result) {
                $basic_report[$rule_id] = $result;
            }

            $report->basic_report = $basic_report;
            $report->updated_at = time();

            if (!$report->save()) {
                return false;
            }
        }

        return true;
    }

    public static function addScoreReport($r_id, $o_id, $result){
        $report = self::find()->where(['_id' => $o_id])->one();
        if (empty($report)) {
            $report = new self(['_id' => $o_id]);
            $report->created_at = time();
        }
        $score_report = empty($report->score_report) ? [] : $report->score_report;
        $score_report[$r_id] = $result;
        $report->score_report = $score_report;
        $report->updated_at = time();

        return $report->save();
    }

    // 默认最近10分钟
    public static function findReportByUpdateTime($from = false, $end = false){
        if ($from === false) {
            $from = time() - 600;
        }
        if ($end === false) {
            $end = time();
        }
        $reports = self::find()->where(['between', 'updated_at' , $from , $end])->all();
        return $reports;
    }

    // public function getName(){
    //     if (in_array($this->type, [self::TYPE_RULE, self::TYPE_RULE_PERSON, self::TYPE_VALUE])) {
    //         $a = Rule::find()->where(['id' => $this->r_id, 'status' => Rule::STATUS_ACTIVE])->one();
    //         if (empty($a)) {
    //             return "未找到对应规则";
    //         }
    //     } elseif ($this->type == self::TYPE_NODE || $this->type == self::TYPE_NODE_PERSON) {
    //         $a = RuleNode::find()->where(['id' => $this->r_id, 'status' => RuleNode::STATUS_ACTIVE])->one();
    //         if (empty($a)) {
    //             return "未找到对应规则节点";
    //         }
    //     } else {
    //         return "未知的类型";
    //     }

    //     return $a->name;
    // }

    public static function getReportTree($params, $score = 100, $report = null){
        if (empty($report)) {
            $report = self::find()->where(['_id' => intval($params['p_id'])])->one();
        }
        if (!isset($report['score_report'][$params['node']->id])) {
            return [
                "text" => $params['node']->name . " 得分：暂无"  ,
                "children" => [],
                // "state" => [ "opened" => true ]
            ];
        }

        $root_node_result = $report['score_report'][$params['node']->id];

        $child_nodes = self::getChildren($params['node']->id);

        if (empty($child_nodes)) {
            if (!isset($root_node_result)) {
                return ["text" => $params['node']->name . " 得分：暂无"];
                // return ["text" => $params['node']->name . '权重：' . round($params['node']->weight / 100, 2) . "%，得分：暂无"];
            }
            $basic_result = $report['basic_report'][$params['node']->rulemap->r_id];
            return ["text" => $params['node']->name . " 得分："
                . round($root_node_result * $score / 100, 2)
                . "/" . $score
                . " ------ 规则：" . $params['node']->rulemap->rule->name . "，结果：" . (isset($basic_result['value']) ? $basic_result['value'] : "--") ];
            // return ["text" => $params['node']->name . '权重：' . round($params['node']->weight / 100, 2) . "%，得分：" . $root_node_result->result .
            //     "，规则：" . $root_node_result->name . "，结果：" . $basic_result->value ];
        }

        $children = [];
        foreach ($child_nodes as $key => $value) {
            $children[] = self::getReportTree(['node' => $value, 'p_id' => $params['p_id']], $score * $value->weight / 10000, $report);
        }

        if (!isset($root_node_result)) {
            return [
                "text" => $params['node']->name . " 得分：暂无",
                "children" => $children,
                // "state" => [ "opened" => true ]
            ];
        }
        return [
            "text" => $params['node']->name . " 得分："
                . round($root_node_result * $score / 100, 2)
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

        $report = self::find()->where(['_id' => intval($params['p_id'])])->one();
        if (empty($report) || empty($report['basic_report'])) {
            return $results;
        }
        $basic_report = $report['basic_report'];

        foreach ($rules as $rule) {
            if (isset($basic_report[$rule->id])) {
                $r = $basic_report[$rule->id];
                $results[] = [
                    'name' => $rule->name,
                    'value' => isset($r['value']) ? $r['value'] : "--",
                    'result' => $r,
                ];
            }
        }

        return $results;

    }
    // 信用分  欺诈分   禁止项
    public static function getNewReportData($id){
        $report = self::find()->where(['_id' => intval($id)])->one();
        if (!isset($report) || !isset($report['basic_report'])) {
            return [-1,-1,-1];
        }
        $credit = isset($report['basic_report']['166']['value'])?round($report['basic_report']['166']['value'],2):-1;
        $antifraud = isset($report['basic_report']['164']['value'])?round($report['basic_report']['164']['value'],2):-1;
        $forbid = isset($report['basic_report']['165']['value'])?$report['basic_report']['165']['value']*100:-1;


        return [$credit, $antifraud, $forbid];
    }
    //  用户特征
    public static function getNewData($id,$node){
        $report = self::find()->where(['_id' => intval($id)])->one();
        if (!isset($report) || !isset($report['basic_report'])) {
            return '暂无';
        }elseif(in_array($node,['164','165','166'])){
            $credit = isset($report['basic_report'][$node]['value'])?round($report['basic_report'][$node]['value'],2):'暂无';
            return $credit;
        }else{
            $name = Rule::find()->where(['id'=>$node])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
            return [
                'name' => $name['name'],
                'value' =>isset($report['basic_report'][$node]['value'])?round($report['basic_report'][$node]['value'],2):'暂无',
            ];
        }

    }

}
