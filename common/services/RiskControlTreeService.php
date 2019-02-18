<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\risk\Rule;
use common\models\risk\EscapeRule;
use common\models\risk\RuleExtendMap;
use common\models\mongo\risk\RuleReportMongo;
use common\models\mongo\risk\OrderReportMongo;
use common\services\risk_control\RiskControlDataService;
use common\models\OrderBasicRule;
use common\models\mongo\risk\RiskControlDataSnapshot;

/**

 *

 *
 * 决策树3.0
 *
 */
class RiskControlTreeService extends Component
{

    const LOW_RISK = 0;
    const MEDIUM_RISK = 1;
    const HIGH_RISK = 2;

    public $data_service;
    public $check_service;
    public $risk_service;

    public function __construct(){
        $this->data_service = new RiskControlDataService();
        $this->check_service = new RiskControlCheckService();
        $this->risk_service = new RiskControlService();
    }

    private function clear() {
        $this->reject_roots = '';
        $this->reject_detail = '';
    }

    /****************** 与订单相关 data begin ******************/

    //缓存已经校验过的规则值, 以rule_id为索引
    private $rules_value;

    //缓存正在校验的规则值, 以rule_id为索引, 避免环出现
    private $rules_calculating;

    //缓存需要计算的特征id
    private $basic_data;

    private $root_ids;
    private $identity;
    private $is_real;
    private $reject_roots;
    private $reject_detail;

    /****************** 与订单相关 data end ******************/

    /****************** 与决策树本身相关 data begin ******************/

    //缓存节点, 减少数据库访问
    private $cached_rules;

    //缓存所有扩展特征的映射, 减少数据库访问
    private $extend_rules_mapping;

    /****************** 与决策树本身相关 data end ******************/

    /**


     *
     * 决策树优化3.0
     *
     * param    LoanPerson              借款人
     * param    UserLoanOrder           借款订单
     * param    root_rule_ids           决策树根节点数组
     * return   value                   根节点值
     */
    public function runDesicionTree($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0){
        /************ 节点准备 begin ************/

        $this->rules_value = [];

        $this->rules_calculating = [];

        if($is_real == 0){
            $this->basic_data = $this->data_service->getData($loan_person, $order);
        }else{
            $this->basic_data = $this->data_service->getData($loan_person, $order, true);
        }

        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        if(!isset($this->cached_rules) || !isset($this->extend_rules_mapping)){
            $this->cached_rules = $this->getCachedRules();
            $this->extend_rules_mapping = $this->getExtendRulesMapping();
        }

        /************ 节点准备 end ************/

        //依次计算每棵决策树
        $basic_rule = [];
        foreach ($root_rule_ids as $root_rule_id) {
            $rule = $this->cached_rules[$root_rule_id];
            if ($rule->type != Rule::TYPE_EXTEND) {
                $params = json_decode($rule->params, true);
                $method = "check" . $rule->url;
                $result = $this->check_service->$method($this->basic_data, $params);
                $result['value'] = $this->calRuleValue($root_rule_id);
                $basic_rule[$rule->id] = $result;
            }else{
                $this->calRuleValue($root_rule_id);
            }
        }
        if(!empty($this->basic_data['order'])){
            $report['order_id'] = $this->basic_data['order']->id;
            $report['user_id'] = $this->basic_data['order']->user_id;
            $report['basic_rule'] = json_encode($basic_rule);
            $model = new OrderBasicRule();
            $model->saveData($report);
        }

        $this->clear();

        //返回根节点以及路径子节点的值
        return $this->rules_value;
    }

    private function backTestRunDecisionTree($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0, $param){

        /************ 节点准备 begin ************/

        $this->rules_value = [];

        $this->rules_calculating = [];

        if($is_real == 1){
            $this->basic_data = RiskControlDataSnapshot::findForBackTest($order->id);
        }

        if(empty($this->basic_data)){
            if($param == 0){
                $this->basic_data = $this->getRiskData($loan_person, $order);
            }else{
                return false;
            }
        }

        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        if(!isset($this->cached_rules) || !isset($this->extend_rules_mapping)){
            $this->cached_rules = $this->getCachedRules();
            $this->extend_rules_mapping = $this->getExtendRulesMapping();
        }

        /************ 节点准备 end ************/

        //依次计算每棵决策树
        $basic_rule = [];
        foreach ($root_rule_ids as $root_rule_id) {
            $rule = $this->cached_rules[$root_rule_id];
            if($rule->type != Rule::TYPE_EXTEND){
                $params = json_decode($rule->params, true);
                $method = "check" . $rule->url;
                $result = $this->check_service->$method($this->basic_data, $params);
                $result['value'] = $this->calRuleValue($root_rule_id);
                $basic_rule[$rule->id] = $result;
            }else{
                $this->cached_rules = $this->getCachedRules();
                $this->calRuleValue($root_rule_id);
            }
        }


        //返回根节点以及路径子节点的值
        return $this->rules_value;
    }


    // 回测接口
    public function backTest($order_id, $tree_name, $params){

        $order = UserLoanOrder::findOne($order_id);

        if(empty($order)){
            throw new Exception("Order_id: $order_id Not Found");
        }

        $tree = Rule::findByTreeName($tree_name);

        if(empty($tree)){
            throw new Exception("Tree_name: $tree_name Not Found");
        }

        $rule_id = $tree->id;

        $loan_person = LoanPerson::findOne($order->user_id);
        if(empty($loan_person)){
            echo "订单{$order_id}的借款人{$order->user_id}不存在\n";
            return false;
        }
        $result = $this->backTestRunDecisionTree([$rule_id], $loan_person, $order, 1, $params);

        if($result === false){
            return $result;
        }

        $result = $result[$rule_id];

        if(is_array($result['value']['result'])) return 0;

        return $result['value']['result'];

    }

    //快照没有取到数据重新采集数据
    public function getRiskData(LoanPerson $loan_person, $order)
    {

        $risk_data_service = new RiskControlDataService();
        $data = [
            'order'         => $order,
            'loan_person'   => $loan_person,

            'jxl'   =>    $risk_data_service->getJxlData($loan_person),
            'yys'   =>    $risk_data_service->getYysData($loan_person),
            'td'    =>    $risk_data_service->getTdData($loan_person),
            'zm'    =>    $risk_data_service->getZmData($loan_person),
            'mg'    =>    $risk_data_service->getMgData($loan_person),
            'yx'    =>    $risk_data_service->getYxData($loan_person),
            'zzc'   =>    $risk_data_service->getZzcData($loan_person),

            'card_infos'            =>    $risk_data_service->getCardInfos($loan_person),
            'user_detail'           =>    $risk_data_service->getUserDetail($loan_person),
            'user_contact'          =>    $risk_data_service->getUserContact($loan_person),
            'user_loan_orders'      =>    $risk_data_service->getUserLoanOrders($loan_person),
            'user_credit_total'     =>    $risk_data_service->getUserCreditTotal($loan_person, $order),
            'user_proof_materia'            =>    $risk_data_service->getUserProofMateria($loan_person),
            'user_mobile_contacts'          =>    $risk_data_service->getUserMobileContacts($loan_person),
            'user_login_upload_log'         =>    $risk_data_service->getUserLoginUploadLog($loan_person),
            'user_login_upload_logs'        =>    $risk_data_service->getUserLoginUploadLogs($loan_person),
            'user_quota_person_info'        =>    $risk_data_service->getUserQuotaPersonInfo($loan_person),
            'usable_user_loan_orders'       =>    $risk_data_service->getUsableUserLoanOrders($loan_person),
            'loan_collection_order'         =>    $risk_data_service->getLoanCollectionOrder($loan_person),
            'user_loan_order_repayments'    =>    $risk_data_service->getUserLoanOrderRepayments($loan_person),
        ];

        return $data;

    }

    //调试模式
    public function runDesicionTreeDebug($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0){

        /************ 节点准备 begin ************/

        $this->rules_value = [];

        $this->rules_calculating = [];

        if($is_real == 0){
            $this->basic_data = $this->data_service->getData($loan_person, $order);
        }else{
            $this->basic_data = $this->data_service->getData($loan_person, $order, true);
        }

        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        if(!isset($this->cached_rules) || !isset($this->extend_rules_mapping)){
            $this->cached_rules = $this->getCachedRulesDebug();
            $this->extend_rules_mapping = $this->getExtendRulesMappingDebug();
        }

        /************ 节点准备 end ************/

        //依次计算每棵决策树
        foreach ($root_rule_ids as $root_rule_id) {
            $this->calRuleValue($root_rule_id);
        }

        //返回结果
        $result = [];
        foreach ($root_rule_ids as $root_rule_id) {
            $result[$root_rule_id] = $this->rules_value[$root_rule_id];
        }
        return $result;
    }
    public function runDesicionTreeDebug2($root_rule_ids, LoanPerson $loan_person, $order = null, $is_real = 0){

        /************ 节点准备 begin ************/

        $this->rules_value = [];

        $this->rules_calculating = [];

        $this->basic_data = $this->data_service->getData1($loan_person, $order);


        $this->root_ids = implode("_",$root_rule_ids);
        $this->identity = time().rand(0, 100);
        $this->is_real = $is_real;

        if(!isset($this->cached_rules) || !isset($this->extend_rules_mapping)){
            $this->cached_rules = $this->getCachedRulesDebug();
            $this->extend_rules_mapping = $this->getExtendRulesMappingDebug();
        }

        /************ 节点准备 end ************/

        //依次计算每棵决策树
        foreach ($root_rule_ids as $root_rule_id) {
            $this->calRuleValue($root_rule_id);
        }

        //返回结果
        $result = [];
        foreach ($root_rule_ids as $root_rule_id) {
            $result[$root_rule_id] = $this->rules_value[$root_rule_id];
        }
        return $result;
    }



    private function getCachedRules(){
        $rules = Rule::find()->where(['state' => Rule::STATE_USABLE, 'status' => Rule::STATUS_ACTIVE])->all(Yii::$app->get('db_kdkj_rd'));
        $result = [];
        foreach ($rules as $rule) {
            $result[$rule->id] = $rule;
        }
        return $result;
    }

    private function getCachedRulesDebug(){
        $rules = Rule::find()->where(['state' => [Rule::STATE_USABLE, Rule::STATE_DEBUG]])->all(Yii::$app->get('db_kdkj_rd'));
        $result = [];
        foreach ($rules as $rule) {
            $result[$rule->id] = $rule;
        }
        return $result;
    }

    private function getExtendRulesMapping(){
        $mappings = RuleExtendMap::find()->where(['state' => RuleExtendMap::STATE_USABLE, 'status' => RuleExtendMap::STATUS_ACTIVE])->orderBy('rule_id ASC, order ASC')->all(Yii::$app->get('db_kdkj_rd'));
        $result = [];
        foreach ($mappings as $mapping) {
            $result[$mapping->rule_id][$mapping->order] = $mapping;
        }
        return $result;
    }

    private function getExtendRulesMappingDebug(){
        $mappings = RuleExtendMap::find()->where(['state' => [RuleExtendMap::STATE_USABLE, Rule::STATE_DEBUG]])->orderBy('rule_id ASC, order ASC')->all(Yii::$app->get('db_kdkj_rd'));
        $result = [];
        foreach ($mappings as $mapping) {
            $result[$mapping->rule_id][$mapping->order] = $mapping;
        }
        return $result;
    }

    private function calRuleValue($rule_id){

        //判断是否曾经计算过
        if(array_key_exists($rule_id, $this->rules_value)){
            return $this->rules_value[$rule_id]['value'];
        }

        //判断是否存在环
        if(array_key_exists($rule_id, $this->rules_calculating)){
            echo "rule: $rule_id 规则路径存在环 \r\n";
            die;
        }

        $this->rules_calculating[$rule_id] = true;

        //判断是否在缓存节点中
        if(!array_key_exists($rule_id, $this->cached_rules)){
            echo "rule: $rule_id 规则暂时不存在 \r\n";
            die;
        }

        $rule = $this->cached_rules[$rule_id];

        if($rule->type == Rule::TYPE_EXTEND){
            $result = $this->calExtendRuleValue($rule_id);
        }else{
            $result = $this->calBasicRuleValue($rule);
        }

        unset($this->rules_calculating[$rule_id]);

        return $result;
    }

    private function calExtendRuleValue($rule_id){

        $rule = $this->cached_rules[$rule_id];

        $extend_type = $rule->extend_type;

        if($extend_type == Rule::EXTEND_TYPE_EXPRESSION){
            $value = $this->calExtendRuleByExpression($rule->expression);
        }else{
            $value = $this->calExtendRuleByMapping($rule);
        }

        $result = [
            'risk'      => self::MEDIUM_RISK,
            'detail'    => $rule->name,
            'value'     => $value,
        ];

        $this->rules_value[$rule_id] = $result;

        RuleReportMongo::addBasicReport($rule_id, $this->basic_data['loan_person']->id, $result);
        OrderReportMongo::addBasicReport($this->basic_data['order'], $this->root_ids, $this->identity, $rule_id, $result, $this->is_real, $this->reject_roots, $this->reject_detail);

        return $value;
    }

    private function calBasicRuleValue($rule){

        //计算基础特征值
        $method = "check" . $rule->url;
        $params = json_decode($rule->params, true);

        if (!method_exists($this->check_service, $method)) {
            echo "rule: ".$rule->id." 基础规则查无方法 \r\n";
            die;
        }

        $result = $this->check_service->$method($this->basic_data, $params);

        if (isset($result['value'])) {
            $result['value'] = $this->risk_service->escapeValue($result['value'], $rule->template_id);
        }else{
            $result['value'] = $this->risk_service->escapeValue(-1, $rule->template_id);
        }

        $this->rules_value[$rule->id] = $result;

        // 每条规则
        RuleReportMongo::addBasicReport($rule->id, $this->basic_data['loan_person']->id, $result);
        OrderReportMongo::addBasicReport($this->basic_data['order'], $this->root_ids, $this->identity, $rule->id, $result, $this->is_real, $this->reject_roots, $this->reject_detail);

        return $result['value'];
    }

    private function calExtendRuleByExpression($expression){
        $expression = str_replace(' ', '', $expression);

        //解析表达式
        $expression = preg_replace_callback(
            "/@[0-9]+/",
            function ($matches) {
                $matche = str_replace('@', '', $matches[0]);
                return '$this->calRuleValue('.$matche.')';
            },
            $expression
        );

        $expression .= ';';

        $result = eval("return ".$expression);

        return $result;
    }

    private function calExtendRuleByMapping($extend_rule){
        $rule_id = $extend_rule->id;

        $default_result = $extend_rule->result;

        if(!array_key_exists($rule_id, $this->extend_rules_mapping)){
            echo "rule: $rule_id 扩展映射规则存在问题 \r\n";
            die;
        }

        $extend_rule_mapping = $this->extend_rules_mapping[$rule_id];

        foreach ($extend_rule_mapping as $key => $mapping) {
            $expression = $mapping->expression;
            if($this->calExtendRuleByExpression($expression)){
                preg_match('/[\x{4e00}-\x{9fa5}]/u', $mapping->result, $match_result);
                if ($expression !== '1' && !$this->reject_detail && (strpos($mapping->result, 'head_code') !== false || count($match_result) > 0)) {
                    $tmp_expression = $this->analyseExpression($expression);
                    $this->reject_roots = $tmp_expression['roots'];
                    $this->reject_detail = "root_id: $rule_id, expression: " . $tmp_expression['detail'] . ", result: {$mapping->result}";
                }
                return $this->calExtendRuleByExpression($mapping->result);
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
                    $name = $this->cached_rules[$match]['name'] ?? '';
                    $value = $this->rules_value[$match]['value'] ?? '';
                    return  $name . '(id: ' . $match . ', value: ' . $value . ')';
                },
                $expression
            )
        ];
    }

}
