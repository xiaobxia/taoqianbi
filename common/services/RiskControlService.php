<?php
namespace common\services;

use common\base\LogChannel;
use common\models\CardInfo;
use common\models\CreditBqs;
use common\models\CreditJxl;
use common\models\CreditYys;
use common\models\CreditMg;
use common\models\CreditQueryLog;
use common\models\CreditTd;
use common\models\CreditZmop;
use common\models\info\TaobaoFormatData;
use common\models\LoanPersonBadInfo;
use common\models\mongo\alipay\AlipayFormatReportMongo;
use common\models\OrderAutoRejectLog;
use common\models\UserContact;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\models\UserQuotaPersonInfo;
use common\models\UserVerification;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\CreditHd;
use common\models\CreditYxzc;
use common\models\CreditZzc;
use common\models\CreditYd;
use common\models\LoanPerson;
use common\models\CreditCheckHitMap;

use common\models\risk\Rule;
use common\models\risk\RuleExtendMap;
use common\models\risk\RuleCheckReport;
use common\models\mongo\risk\RuleReportMongo;
//use common\models\mongo\risk\RuleReportTestMongo;
use common\models\mongo\risk\OrderReportMongo;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeMap;
use common\models\risk\RuleNodeRelation;
use common\models\mongo\mobileInfo\PhoneOperatorDataMongo;
use common\models\info\AlipayInfo;

use common\models\risk\EscapeRule;
use common\services\risk_control\RiskControlDataService;
use common\models\OrderBasicRule;
/**
 *

 *
 * 用于自动验证规则
 *
 */
class RiskControlService extends Component
{
    const LOW_RISK = 0;
    const MEDIUM_RISK = 1;
    const HIGH_RISK = 2;

    public $data_service;
    public $score_service;
    public $check_service;

    public function __construct(){
        $this->data_service = new RiskControlDataService();
        $this->score_service = new RiskControlScoreService();
        $this->check_service = new RiskControlCheckService();
    }

    private function clear() {
        $this->reject_roots = '';
        $this->reject_detail = '';
    }

    /****************** 决策树1.0 data begin ******************/

    //缓存已经校验过的规则值, 以rule_id为索引
    private $rules_value;

    //缓存正在校验的规则值, 以rule_id为索引, 避免环出现
    private $rules_calculating;

    //缓存所有扩展节点, 减少数据库访问
    private $extend_rules;

    private $loan_person_id;
    private $order;

    private $root_ids;
    private $identity;
    private $is_real;

    private $reject_roots;
    private $reject_detail;

    /****************** 决策树1.0 data end ******************/

    /****************** 决策树2.0 data begin ******************/

    //缓存需要计算的特征id
    private $cached_rules_id;

    //缓存所有基础节点, 减少数据库访问
    private $basic_rules = [];

    //缓存所有扩展特征的映射, 减少数据库访问
    private $extend_rules_mapping;

    /****************** 决策树2.0 data end ******************/

    /**
     *

     *
     * 自动验证规则的入口方法
     *
     * param    LoanPerson            借款人
     * return   nil
     */
    public function runCheckPersonMongo(LoanPerson $loan_person, $debug = false){

        $data = $this->data_service->getData($loan_person);

        $rules = $this->getRules(Rule::TYPE_CHECK, $debug);

        // RuleCheckReport::deleteAll(['o_id' => $loan_person->id]);

        foreach ($rules as $rule) {
            $method = "check" . $rule->url;
            $params = json_decode($rule->params, true);
            if (!method_exists($this->check_service, $method)) {
                RuleReportMongo::addBasicReport($rule->id, $loan_person->id, ['risk' => self::MEDIUM_RISK, 'detail' => "该规则路径存在问题"]);
                continue;
            }
            $result = $this->check_service->$method($data, $params);
            // 每条规则
            RuleReportMongo::addBasicReport($rule->id, $loan_person->id, $result);
        }

        return true;
    }


    /**


     *
     * 指定决策树验证
     *
     * param    LoanPerson              借款人
     * param    UserLoanOrder           借款订单
     * param    root_rule_id                 决策树根节点id
     * return   value                   根节点值
     */
    public function runSpecificRule($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0,$test_dependenc = 0){
        $printTime_0 = time();
        $root_rule_flag = "";
        foreach ($root_rule_ids as $key => $value) {
            $root_rule_flag .= $value."-";
        }
        //缓存已经校验过的规则值, 以rule_id为索引
        $this->rules_value = [];
        $printTime_1 = time();
        //缓存正在校验的规则值, 以rule_id为索引, 避免环出现
        $this->rules_calculating = [];

        $this->loan_person_id = $loan_person->id;
        $this->order = $order;

        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        //缓存需要计算的基础特征id 与 扩展特征id
        if(!isset($this->cached_rules_id[$root_rule_flag])){
            $this->cached_rules_id[$root_rule_flag] = [];
            foreach ($root_rule_ids as $key => $value) {
                $this->getRuleIds($root_rule_flag, $value);
            }
        }
        $printTime_2 = time();
        if($is_real == 0){
            $data = $this->data_service->getData($loan_person, $order);
        }else{
            $data = $this->data_service->getData($loan_person, $order, true);
        }

        $printTime_3 = time();
        //计算基础特征值
        $basic_rule = [];

        $tmp_rule_result = [];
        $tmp_order_result = [];
        foreach ($this->basic_rules as $rule) {
            $method = "check" . $rule->url;
            $params = json_decode($rule->params, true);
            if (!method_exists($this->check_service, $method)) {
                echo "rule: ".$rule->id." 基础规则查无方法 \r\n";
                die;
            }
            $result = $this->check_service->$method($data, $params);
            if (isset($result['value'])) {
                $result['value'] = $this->escapeValue($result['value'], $rule->template_id);
            }else{
                $result['value'] = $this->escapeValue(-1, $rule->template_id);
            }

            $this->rules_value[$rule->id] = $result;
            // 每条规则
            $tmp_rule_result[$loan_person->id][$rule->id] = $result;
            if (!empty($order)) {
                $tmp_order_result[$this->identity . "@" . $order->user_id . "@" . $order->id . "@" . $this->root_ids . "@" . $is_real][$rule->id] = $result;
            }

            $basic_rule[$rule->id] = $result;
        }

        $printTime_3_1 = time();
        if($test_dependenc == 0){
            RuleReportMongo::saveBasicReport($tmp_rule_result);
            OrderReportMongo::saveBasicReport($tmp_order_result, $this->reject_roots, $this->reject_detail);
        }
//        if ($test_dependenc > 0) {
//            RuleReportTestMongo::saveBasicReport($tmp_rule_result);
//        }

        $printTime_4 = time();
        if (!empty($order)) {
            $report['order_id'] = $order->id;
            $report['user_id'] = $order->user_id;
            $report['basic_report'] = json_encode($basic_rule);
            $model = new OrderBasicRule();
            $model->saveData($report);
        }

        $printTime_5 = time();
        if(isset($this->extend_rules)){
            //计算根节点扩展特征值
            foreach ($this->extend_rules as $rule_id => $rule) {
                $this->calExtendRule($rule_id);
            }
        }
        $printTime_6 = time();
        // return $this->cached_rules_id[$root_rule_id];
        $result = [];
        foreach ($root_rule_ids as $key => $value) {
            $result[$value] = isset($this->rules_value[$value])?$this->rules_value[$value]:"Exception";
        }
        $printTime_7 = time();
        $result['printTime'] = ($printTime_1-$printTime_0).'-'   #
            .($printTime_2-$printTime_1).'-' #
            .($printTime_3-$printTime_2).'-' #
            .($printTime_3_1-$printTime_3).'-' #
            .($printTime_4-$printTime_3_1).'-' # ?
            .($printTime_5-$printTime_4).'-' #
            .($printTime_6-$printTime_5).'-' # ?
            .($printTime_7-$printTime_6); #
        unset($printTime_0,$printTime_1,$printTime_2,$printTime_3,$printTime_4,$printTime_5,$printTime_6,$printTime_7);

        $this->clear();

        return ($test_dependenc > 0) ? $this->rules_value : $result;
    }


    public function runSpecificRule2($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0){

        $root_rule_flag = "";
        foreach ($root_rule_ids as $key => $value) {
            $root_rule_flag .= $value."-";
        }
        //缓存已经校验过的规则值, 以rule_id为索引
        $this->rules_value = [];

        //缓存正在校验的规则值, 以rule_id为索引, 避免环出现
        $this->rules_calculating = [];

        $this->loan_person_id = $loan_person->id;
        $this->order = $order;

        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        //缓存需要计算的基础特征id 与 扩展特征id
        if(!isset($this->cached_rules_id[$root_rule_flag])){
            $this->cached_rules_id[$root_rule_flag] = [];
            foreach ($root_rule_ids as $key => $value) {
                $this->getRuleIds($root_rule_flag, $value);
            }
        }
        if($is_real == 0){
            $data = $this->data_service->getData($loan_person, $order);
        }else{
            $data = $this->data_service->getData($loan_person, $order, true);
        }

        //计算基础特征值
        $basic_rule = [];
        foreach ($this->basic_rules as $rule) {
            $method = "check" . $rule->url;
            $params = json_decode($rule->params, true);
            if (!method_exists($this->check_service, $method)) {
                echo "rule: ".$rule->id." 基础规则查无方法 \r\n";
                die;
            }
            $result = $this->check_service->$method($data, $params);
            if (isset($result['value'])) {
                $result['value'] = $this->escapeValue($result['value'], $rule->template_id);
            }else{
                $result['value'] = $this->escapeValue(-1, $rule->template_id);
            }

            $this->rules_value[$rule->id] = $result;
            // 每条规则
            // RuleReportMongo::addBasicReport($rule->id, $loan_person->id, $result);
            // OrderReportMongo::addBasicReport($order, $this->root_ids, $this->identity, $rule->id, $result, $this->is_real);
            $basic_rule[$rule->id] = $result;
        }

        if(isset($this->extend_rules)){
            //计算根节点扩展特征值
            foreach ($this->extend_rules as $rule_id => $rule) {
                $this->calExtendRule($rule_id);
            }
        }
        // return $this->cached_rules_id[$root_rule_id];
        $result = [];
        foreach ($root_rule_ids as $key => $value) {
            $result[$value] = isset($this->rules_value[$value])?$this->rules_value[$value]:"Exception";
        }

        if(!empty($order)){
            $report['order_id'] = $order->id;
            $report['user_id'] = $order->user_id;
            $report['basic_report'] = json_encode($this->rules_value);
            $model = new OrderBasicRule();
            $model->saveOrUpdateData($report);
        }
        return $result;

    }


    /**


     *
     * 递归获取所有需要计算的基础特征与扩展特征
     *
     * param    root_rule_id              根节点id
     * param    child_rule_id              子节点id
     * return   nil
     */
    public function getRuleIds($root_rule_id, $child_rule_id){

        if(!array_key_exists($child_rule_id, $this->cached_rules_id[$root_rule_id])){

            $rule = Rule::findOne($child_rule_id);
            if(empty($rule) || $rule->state == Rule::STATE_DISABLE){
                echo "rule : $child_rule_id 不可用";
//                throw new Exception("rule : $child_rule_id 不可用");
            }

            $this->cached_rules_id[$root_rule_id][$child_rule_id] = $child_rule_id;

            if($rule->type == Rule::TYPE_EXTEND){
                $this->extend_rules[$rule->id] = $rule;
                if($rule->extend_type == Rule::EXTEND_TYPE_EXPRESSION){
                    $this->addRuleIdsByExpression($root_rule_id, $rule->expression);
                }else{
                    $this->addRuleIdsByMapping($root_rule_id, $child_rule_id);
                }
            }else{
                $this->basic_rules[$rule->id] = $rule;
            }
        }
    }

    private function addRuleIdsByExpression($root_rule_id, $expression){

        $expression = str_replace(' ', '', $expression);

        preg_match_all("/@[0-9]+/", $expression, $matches);

        foreach ($matches[0] as $value) {
            $matche = str_replace('@', '', $value);
            $this->getRuleIds($root_rule_id, $matche);
        }
    }

    private function addRuleIdsByMapping($root_rule_id, $child_rule_id){

        $extend_rule_mapping = $this->getExtendRuleMapping($child_rule_id);
        $this->extend_rules_mapping[$child_rule_id] = $extend_rule_mapping;
        foreach ($extend_rule_mapping as $key => $mapping) {
            $expression = $mapping->expression;
            $this->addRuleIdsByExpression($root_rule_id, $expression);
            $result = $mapping->result;
            $this->addRuleIdsByExpression($root_rule_id, $result);
        }
    }

    private function getRulesByIds($ids, $type){
        return Rule::find()->where(['state' => Rule::STATE_USABLE, 'status' => Rule::STATUS_ACTIVE, 'type' => $type, 'id'=>$ids])->orderBy('order')->all(Yii::$app->get('db_kdkj_rd'));
    }

    public function escapeValue($value, $t_id){
        if (empty($t_id)) {
            return $value;
        }
        $r = EscapeRule::find()->where(['template_id' => $t_id, 'sign'=>$value, 'status' => EscapeRule::STATUS_ACTIVE])->one();
        if (empty($r)) {
            return $value;
        }
        return $r->value;
    }


    /**


     *
     * 扩展特征计算
     *
     * param    rule_id                 对应扩展特征id
     * param    loan_person_id          借款人id
     * return   value                   计算后的特征值
     */
    public function calExtendRule($rule_id){

        //判断是否曾经计算过
        if(array_key_exists($rule_id, $this->rules_value)){
            // echo "calExtendRule: $rule_id ".$this->rules_value[$rule_id]['value']."\r\n";
            return $this->rules_value[$rule_id]['value'];
        }

        //判断是否存在环
        if(array_key_exists($rule_id, $this->rules_calculating)){
            echo "rule: $rule_id 路径存在环 \r\n";
            die;
        }

        $this->rules_calculating[$rule_id] = true;

        if(!array_key_exists($rule_id, $this->extend_rules)){
            echo "rule: $rule_id 规则不存在 \r\n";
            die;
        }

        $extend_rule = $this->extend_rules[$rule_id];

        $extend_type = $extend_rule->extend_type;

        switch ($extend_type) {
            case Rule::EXTEND_TYPE_EXPRESSION:
                $result = $this->calRuleByExpression($extend_rule->expression);
                break;
            case Rule::EXTEND_TYPE_MAPPING:
                $result = $this->calRuleByMapping($extend_rule);
                break;
            default:
                echo "rule: $rule_id 扩展特征类型存在问题 \r\n";
                die;
        }

        $result = [
            'risk' => self::MEDIUM_RISK,
            'detail' => $extend_rule->name,
            'value' => $result,
        ];

        $this->rules_value[$rule_id] = $result;

        RuleReportMongo::addBasicReport($rule_id, $this->loan_person_id, $result);
        OrderReportMongo::addBasicReport($this->order, $this->root_ids, $this->identity, $rule_id, $result, $this->is_real, $this->reject_roots, $this->reject_detail);

        unset($this->rules_calculating[$rule_id]);
        return $result['value'];
    }

    /**
     * 扩展特征表达式计算
     *
     * param    expression              表达式
     * return   value                   计算后的特征值
     */
    public function calRuleByExpression($expression){
        $expression = str_replace(' ', '', $expression);

        //解析表达式
        $expression = preg_replace_callback(
            "/@[0-9]+/",
            function ($matches) {
                $match = str_replace('@', '', $matches[0]);
                return '$this->calExtendRule('.$match.')';
            },
            $expression
        );

        $expression .= ';';
        try {
            $result = eval("return ".$expression);
        } catch (\Exception $e) {
            yii::warning("{$expression} exp_exception: " . $e->getMessage(), LogChannel::RISK_DEBUG);
        }

        return $result;
    }

    /**


     *
     * 扩展特征映射计算
     *
     * param    extend_rule             对应扩展特征
     * return   value                   计算后的特征值
     */
    public function calRuleByMapping($extend_rule){

        $rule_id = $extend_rule->id;
        $default_result = $extend_rule->result;

        if(isset($this->extend_rules_mapping[$rule_id]) && !empty($this->extend_rules_mapping[$rule_id])){
            $extend_rule_mapping = $this->extend_rules_mapping[$rule_id];
        }else{
            $extend_rule_mapping = $this->getExtendRuleMapping($rule_id);
        }

        foreach ($extend_rule_mapping as $key => $mapping) {
            $expression = $mapping->expression;
            if($this->calRuleByExpression($expression)){
                preg_match('/[\x{4e00}-\x{9fa5}]/u', $mapping->result, $match_result);
                if ($expression !== '1' && !$this->reject_detail && (strpos($mapping->result, 'head_code') !== false || count($match_result) > 0)) {
                    $tmp_expression = $this->analyseExpression($expression);
                    $this->reject_roots = $tmp_expression['roots'];
                    $this->reject_detail = "root_id: $rule_id, expression: " . $tmp_expression['detail'] . ", result: {$mapping->result}";
                }
                return $this->calRuleByExpression($mapping->result);
            }
        }
        return $default_result;
    }

    private function analyseExpression($expression) {
        preg_match_all("/@[0-9]+/", $expression, $match_all);

        return [
            'roots' => is_array($match_all[0]) ? str_replace("@", "", implode("_", array_unique($match_all[0]))) : '',
            'detail' => preg_replace_callback(
                "/@[0-9]+/",
                function ($matches) {
                    $match = str_replace('@', '', $matches[0]);
                    $name = $this->basic_rules[$match]['name'] ?? '';
                    $value = $this->rules_value[$match]['value'] ?? '';
                    return  $name . '(id: ' . $match . ', value: ' . $value . ')';
                },
                $expression
            )
        ];
    }

    /**


     *
     * 获取扩展特征映射
     *
     * param    rule_id                 对应扩展特征id
     * return   value                   对应扩展特征映射
     */
    public function getExtendRuleMapping($rule_id){
        return RuleExtendMap::find()->where(['state' => RuleExtendMap::STATE_USABLE, 'status' => RuleExtendMap::STATUS_ACTIVE, 'rule_id' => $rule_id])->orderBy('order ASC')->all(Yii::$app->get('db_kdkj_rd'));
    }

    /**
     *

     *
     * 自动验证规则节点入口和流程管理方法
     * 决策树
     *
     * param    UserLoanOrder         借款单
     * return   boolean               true/false
     */
    public function runCheckRuleByNode(UserLoanOrder $user_loan_order){

        $loan_person = LoanPerson::findOne($user_loan_order->user_id);

        $data = [
            'jxl' => $this->getJxlData($loan_person),
            'loan_person' => $loan_person,
        ];

        // 获取根节点
        $rule_node = $this->getNextNode();

        $hit_result = false;

        while(!empty($rule_node)){
            $result = $this->runCheckRuleNode($loan_person, $user_loan_order, $data, $rule_node->id);
            RuleCheckReport::addReport($rule_node->id, $user_loan_order->id, json_encode(['risk' => $result]), 1);
            $rule_node = $this->getNextNode($rule_node->id, $result);
        }

        if ($result === self::HIGH_RISK) {
            $hit_result = true;
        }

        var_dump($hit_result);

        return $hit_result;

    }


    /**
     *

     *
     * 自动验证规则节点具体方法
     * 决策树
     *
     * param    UserLoanOrder         借款单
     * return   array                 ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function runCheckRuleNode(LoanPerson $loan_person, UserLoanOrder $user_loan_order, $data, $node_id){

        $node_rules = $this->getNodeRules($node_id);

        $hit_result = 0;

        foreach ($node_rules as $node_rule) {
            $rule = $node_rule->rule;
            $method = "check" . $rule->url;

            // 节点配置的规则属性
            $params = json_decode($node_rule->params, true);
            // 不存在去获取规则的默认属性
            if (empty($params)) {
                $params = json_decode($rule->params, true);
            }

            if (!method_exists($this->check_service, $method)) {
                RuleCheckReport::addReport($rule->id, $user_loan_order->id, json_encode(['risk' => self::MEDIUM_RISK, 'detail' => "该规则路径存在问题"]));
                continue;
            }
            $result = $this->check_service->$method($data, $params);

            // 取风险值最高的结果
            $hit_result = $this->maxRisk($result['risk'], $hit_result);

            // 每条规则
            RuleCheckReport::addReport($rule->id, $user_loan_order->id, json_encode($result), 0);

        }

        return $hit_result;

    }

    /**
     *

     *
     * 自动验证规则节点入口和流程管理方法
     * 评分树
     *
     * param    LoanPerson    loan_person         借款人
     * return   Integer       score               得分
     */
    public function runScoreRuleNode(LoanPerson $loan_person, $rule_node_name){


        $rule_node = RuleNode::find()->where(['name' => $rule_node_name, 'status' => RuleNode::STATUS_ACTIVE])->one(Yii::$app->get('db_kdkj_rd'));

        return $this->scoreRuleNodeInterior($rule_node, $loan_person->id);

    }




    public function getScoreRuleNode(LoanPerson $loan_person, $rule_node_name){
        $rule_node = RuleNode::find()->where(['name' => $rule_node_name, 'status' => RuleNode::STATUS_ACTIVE])->one(Yii::$app->get('db_kdkj_rd'));

        if (empty($rule_node)) {
            return "暂无";
        }

        $report = RuleCheckReport::find()->where(['r_id' => $rule_node->id, 'o_id' => $loan_person->id, 'type' => RuleCheckReport::TYPE_NODE_PERSON, 'status' => RuleCheckReport::STATUS_ACTIVE])->one(Yii::$app->get('db_kdkj_rd'));
        if (!empty($report)) {
            return $report->result;
        }

        return "未计算";
    }

    /**
     *

     *
     * 自动验证规则节点入口和流程管理方法
     * 评分树
     *
     * param    LoanPerson    loan_person         借款人
     * return   Integer       score               得分
     */
    public function runScoreRuleNodeMongo(LoanPerson $loan_person, $rule_node_name){


        $rule_node = RuleNode::find()->where(['name' => $rule_node_name, 'status' => RuleNode::STATUS_ACTIVE])->one(Yii::$app->get('db_kdkj_rd'));

        return $this->scoreRuleNodeMongoInterior($rule_node, $loan_person->id);

    }

    /**
     *

     *
     * 自动验证规则节点具体方法
     * 评分树
     *
     */
    public function scoreRuleNodeInterior(RuleNode $rule_node, $p_id){

        $nodes = $this->getChildNodes($rule_node->id);

        if (!empty($nodes)) {
            $score = 0;
            foreach ($nodes as $node) {
                $score += $this->scoreRuleNodeInterior($node, $p_id);
            }
            // 每条规则节点检测结果报告
            RuleCheckReport::addReport($rule_node->id, $p_id, $score, RuleCheckReport::TYPE_NODE_PERSON);
            return $rule_node->weight * $score / 10000;
        }

        // 规则节点和规则关联对象
        $node_rule = $this->getNodeRule($rule_node->id);

        // 具体规则对象
        try{
            $rule = $node_rule->rule;
        }catch(\Exception $e){
            echo $rule_node->id;die;
        }
        // 节点配置的规则属性
        $params = json_decode($node_rule->params, true);
        // 不存在去获取规则的默认属性
        if (empty($params)) {
            $params = json_decode($rule->params, true);
        }

        $result = $this->score_service->score($rule->id, $p_id, $params);

        // 每条规则检测结果报告
        RuleCheckReport::addReport($rule->id, $p_id, json_encode($result), RuleCheckReport::TYPE_RULE_PERSON);
        // 每条规则节点检测结果报告
        RuleCheckReport::addReport($rule_node->id, $p_id, $result['score'], RuleCheckReport::TYPE_NODE_PERSON);

        return $rule_node->weight * $result['score'] / 10000;

    }


    /**
     *

     *
     * 自动验证规则节点具体方法
     * 评分树
     *
     */
    public function scoreRuleNodeMongoInterior(RuleNode $rule_node, $p_id){

        $nodes = $this->getChildNodes($rule_node->id);

        if (!empty($nodes)) {
            $score = 0;
            foreach ($nodes as $node) {
                $score += $this->scoreRuleNodeMongoInterior($node, $p_id);
            }
            // 每条规则节点检测结果报告
            RuleReportMongo::addScoreReport($rule_node->id, $p_id, $score);
            return $rule_node->weight * $score / 10000;
        }

        // 规则节点和规则关联对象
        $node_rule = $this->getNodeRule($rule_node->id);

        // 具体规则对象
        try{
            $rule = $node_rule->rule;
        }catch(\Exception $e){
            echo $rule_node->id;die;
        }
        // 节点配置的规则属性
        $params = json_decode($node_rule->params, true);
        // 不存在去获取规则的默认属性
        if (empty($params)) {
            $params = json_decode($rule->params, true);
        }

        $report = RuleReportMongo::find()->where(['_id' => $p_id])->one();

        $result = $this->score_service->scoreMongo($rule->id, $params, $report);

        // 每条规则节点检测结果报告
        RuleReportMongo::addScoreReport($rule_node->id, $p_id, $result['score']);

        return $rule_node->weight * $result['score'] / 10000;

    }

    // 获取所有规则
    public function getRules($type = Rule::TYPE_CHECK, $debug = false){

        if ($debug == false) {
            return Rule::find()->where(['state' => Rule::STATE_USABLE, 'status' => Rule::STATUS_ACTIVE, 'type' => $type])->orderBy('order')->all(Yii::$app->get('db_kdkj_rd'));
        }

        return Rule::find()->where(['state' => [Rule::STATE_USABLE, Rule::STATE_DEBUG], 'status' => Rule::STATUS_ACTIVE, 'type' => $type])->orderBy('order')->all(Yii::$app->get('db_kdkj_rd'));
    }

    // 决策树获取下一个节点
    public function getNextNode($now_node_id = 0, $check_result = 0){
        $condition = [
            'status' => RuleNodeRelation::STATUS_ACTIVE
        ];
        if(!empty($now_node_id)){
            $condition['parent_id'] = $now_node_id;
            $condition['parent_result'] = $check_result;
        }
        $relation = RuleNodeRelation::find()->where($condition)->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($relation)) {
            return $relation;
        }

        return $relation->rulenode;
    }

    // 评分树获取子节点
    public function getChildNodes($now_node_id = 0){
        $condition = [
            'status' => RuleNodeRelation::STATUS_ACTIVE
        ];
        if(!empty($now_node_id)){
            $condition['parent_id'] = $now_node_id;
        }
        $relations = RuleNodeRelation::find()->where($condition)->all(Yii::$app->get('db_kdkj_rd'));
        if (empty($relations)) {
            return null;
        }

        $nodes = [];
        foreach ($relations as $key => $relation) {
            $nodes[] = $relation->rulenode;
        }

        return $nodes;
    }

    // 获取节点下面的所有规则
    public function getNodeRules($node_id){
        return RuleNodeMap::find()
            ->where([ 'r_n_id' => $node_id, 'state' => RuleNodeMap::STATE_USABLE, 'status' => RuleNodeMap::STATUS_ACTIVE ])
            ->orderBy('order')->all(Yii::$app->get('db_kdkj_rd'));
    }

    // 获取节点下面的单个规则
    public function getNodeRule($node_id){
        return RuleNodeMap::find()
            ->where([ 'r_n_id' => $node_id, 'state' => RuleNodeMap::STATE_USABLE, 'status' => RuleNodeMap::STATUS_ACTIVE ])
            ->orderBy('order')->one(Yii::$app->get('db_kdkj_rd'));
    }

    // 比较哪个风险等级更高
    public function maxRisk($array){
        // 现在三种等级可以用max来划分
        if (is_array($array)) {
            return max($array);
        }
        return max(func_get_args());
    }

    public function getJxlData(LoanPerson $loan_person){

        $v = UserVerification::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($v)){
            return null;
        }
        if ($v->real_jxl_status == UserVerification::VERIFICATION_JXL) {
            $jxl = CreditJxl::findLatestOne(['person_id'=>$loan_person->id],'db_kdkj_risk_rd');
            if(is_null($jxl)){
                return null;
            }
            if($jxl->status == 1){
                return json_decode($jxl->info,true);
            }
        }elseif ($v->real_yys_status == UserVerification::VERIFICATION_YYS){
            $hljr = CreditYys::find()->where(['person_id'=>$loan_person->id])->one(Yii::$app->get('db_kdkj_risk_rd'));
            if(is_null($hljr)){
                return null;
            }
            if($hljr->status == 1){
                return json_decode($hljr->data,true);
            }
        }

        return null;
        // $data = Yii::$app->jxlService->getUserBaseReport($loanPerson);
        // return $data;
    }

    public function getTdData(LoanPerson $loan_person){

//        $model = CreditTd::findOne(['person_id'=>$loan_person['id']]);
        $model = CreditTd::findLatestOne(['person_id'=>$loan_person['id']],'db_kdkj_risk_rd');
        if (empty($model)) {
            return null;
        }

        return json_decode($model->data, true);

    }

    public function getZmData(LoanPerson $loanPerson){

//        $model = CreditZmop::findOne(['person_id'=>$loanPerson['id']]);
        $model = CreditZmop::gainCreditZmopLatest(['person_id'=>$loanPerson['id']],'db_kdkj_rd');
        if (empty($model)) {
            return null;
        }

        return $model;
    }

    public function getMgData(LoanPerson $loanPerson){

//        $model = CreditMg::findOne(['person_id'=>$loanPerson['id']]);
        $model = CreditMg::findLatestOne(['person_id'=>$loanPerson['id']],'db_kdkj_rd');
        if (empty($model)) {
            return null;
        }

        return json_decode($model->data, true);
    }

    public function getYxData(LoanPerson $loanPerson){
        $model = CreditQueryLog::findLatestOne([
            'person_id'=>$loanPerson->id,'credit_id'=>CreditQueryLog::Credit_YXZC,'credit_type'=>CreditYxzc::TYPE_LOAN_INFO
        ],'db_kdkj_rd');
        if (empty($model)) {
            return null;
        }

        return json_decode($model->data, true);
    }

    public function getZzcData(LoanPerson $loanPerson){

        $log = CreditQueryLog::findLatestOne(['credit_type'=>CreditZzc::TYPE_BLACKLIST, 'credit_id'=>CreditQueryLog::Credit_ZZC, 'person_id' => $loanPerson->id],'db_kdkj_rd');
        if(empty($log)){
            return null;
        }

        return json_decode($log->data, true);
    }

    public function getYysData(LoanPerson $loanPerson){
        $model = PhoneOperatorDataMongo::find()->where([
            '_id'=>$loanPerson->id
        ])->one();
        if (empty($model)) {
            $data = $this->getJxlData($loanPerson);
            if (empty($data)) {
                //
            }else{
                DataToBaseService::synJXLToBase($data, $loanPerson->id);
            }
            $model = PhoneOperatorDataMongo::find()->where([
                '_id'=>$loanPerson->id
            ])->one();
            if (!empty($model)) {
                return $model;
            }
            return null;
        }

        return $model;
    }

    public function getTaobaoData(LoanPerson $loan_person){

        $user = UserVerification::find()->where(['real_taobao_status' => 1, 'user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        if ($user) {
            DataToBaseService::synTaoBaoDataToBase($user['user_id']);
        }
        $info = TaobaoFormatData::find()->where(['user_id' => $loan_person->id])->one();
        if (empty($info)) {
            return null;
        }

        return $info;
    }

}
