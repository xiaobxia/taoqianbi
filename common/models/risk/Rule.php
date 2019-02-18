<?php
namespace common\models\risk;

use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
/**
 *

 *
 * Rule model
 *
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property string $params
 * @property integer $state
 * @property integer $order
 * @property integer $type
 * @property string $description
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class Rule extends MActiveRecord{
    public  $arr =[];
    //基础特征与扩展特征
    const TYPE_CHECK = 0; //兼容老逻辑
    const TYPE_BASIC = 0;
    const TYPE_EXTEND = 1;

    //扩展特征的表达式与映射
    const EXTEND_TYPE_EXPRESSION = 0;
    const EXTEND_TYPE_MAPPING = 1;

    //启用与停用
    const STATE_USABLE = 0;
    const STATE_DISABLE = 1;
    const STATE_DEBUG = 3;

    const P_TYPE_SECTION = 'section';
    const P_TYPE_ARRAY = 'array';

    const TREE_ROOT_NO = 0;
    const TREE_ROOT_YES = 1;

    static $label_state = [
        self::STATE_USABLE  => '可用',
        self::STATE_DISABLE => '停用',
        self::STATE_DEBUG   => '调试'
    ];

    static $label_type = [
        self::TYPE_BASIC  => '基础',
        self::TYPE_EXTEND => '扩展'
    ];

    static $label_extend_type = [
        self::EXTEND_TYPE_EXPRESSION => '表达式',
        self::EXTEND_TYPE_MAPPING    => '映射'
    ];

    static $label_tree_root = [
        self::TREE_ROOT_NO  => '否',
        self::TREE_ROOT_YES => '是'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%rule}}';
    }

    public function rules()
    {
        return [
            [['name', 'type', 'tree_root'], 'required'],
            [['type', 'extend_type', 'template_id', 'order', 'state', 'status', 'tree_root'], 'integer'],
            [['params', 'description', 'tree_description'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['name', 'module', 'result'], 'string', 'max' => 256],
            [['url', 'expression'], 'string', 'max' => 256],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', '规则名称'),
            'type' => Yii::t('app', '类型'),
            'module' => Yii::t('app', '模块'),
            'url' => Yii::t('app', '方法'),
            'params' => Yii::t('app', '默认参数'),
            'extend_type' => Yii::t('app', '扩展特征的类型:0表达式 1映射'),
            'expression' => Yii::t('app', '表达式'),
            'result' => Yii::t('app', '默认结果'),
            'template_id' => Yii::t('app', '转义模版id'),
            'order' => Yii::t('app', '优先级'),
            'description' => Yii::t('app', '描述'),
            'state' => Yii::t('app', '状态'),
            'tree_root' => Yii::t('app', '决策树'),
            'tree_description' => Yii::t('app', '映射字符串'),
            'source' => Yii::t('app', '来源'),
            'data_source' => Yii::t('app', '数据来源'),
            'value' => Yii::t('app', '值'),
            'create_time' => Yii::t('app', '创建时间'),
            'update_time' => Yii::t('app', '更新时间'),
            'status' => Yii::t('app', '存储状态 0 可用 1删除'),
        ];
    }
    public static function findModel($id){
        if (($model = self::findOne($id)) !== null) {
            return  $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public static function findByTreeName($tree_name){
        $model = self::find()->where(['tree_root'=>self::TREE_ROOT_YES, 'tree_description'=>$tree_name, 'state'=>self::STATE_USABLE, 'status'=>self::STATUS_ACTIVE])->one();
        return $model;
    }

    public function approve(){
        $this->state = self::STATE_USABLE;
        return $this->save();
    }
    public function reject(){
        $this->state = self::STATE_DISABLE;
        return $this->save();
    }
    public function debug(){
        $this->state = self::STATE_DEBUG;
        return $this->save();
    }
    public function generateTree($rule, &$nodeDataArray, &$linkDataArray,$parentId=""){
        $temp = [];
        $title = "特征".$rule->id."-".$rule->name;
        if($rule->type == self::TYPE_BASIC){
            $description = '基础特征 : '.$rule->url;
            $this->arr[$rule->name]= $rule->id;
        }else{

            $description = self::$label_extend_type[$rule->extend_type];

            if($rule->extend_type == self::EXTEND_TYPE_EXPRESSION){

                $description .= '\r\n\r\n'.self::expressionTransform($rule->expression);

            }else{

                $temp = self::addNodeByMapping($rule);

                $mappingDescription = $temp['description'];

                $mappingDescription[] = [
                    'left'      => '默认结果',
                    'middle'    => ' : ',
                    'right'     => $rule->result
                ];
                $left = "";$middle = "";$right = "";
                foreach ($mappingDescription as $key => $value) {
                    $left .= $value['left'].'\r\n';
                    $middle .= $value['middle'].'\r\n';
                    $right .= $value['right'].'\r\n';
                }

            }
        }

        $key = $rule->id;

        if(!array_key_exists($key, $nodeDataArray)){

            if($rule->type == self::TYPE_EXTEND && $rule->extend_type == self::EXTEND_TYPE_MAPPING){
                $nodeDataArray[$key] = [
                    'key'       => $key,
                    'category'  => 'Mapping',
                    'text'      => $description,
                    'title'     => $title,
                    'left'      => $left,
                    'middle'    => $middle,
                    'right'     => $right,
                ];
            }else{
                $nodeDataArray[$key] = [
                    'key' => $key,
                    'category' => 'Source',
                    'title'     => $title,
                    'text' => $description
                ];
            }
        }

        if(!empty($parentId)){
            $linkDataArray[$parentId][$key] = true;
        }

        if($rule->type == self::TYPE_EXTEND){

            if($rule->extend_type == self::EXTEND_TYPE_EXPRESSION){

                self::addNodeByExpression($rule->id, $rule->expression, $nodeDataArray, $linkDataArray);

            }else{

                $child_ids = $temp['child_ids'];

                foreach ($child_ids as $id) {
                    $linkDataArray[$key][$id] = true;
                    self::generateTree(self::findModel($id), $nodeDataArray, $linkDataArray, $key);
                }
            }
        }
    }

    public function addNodeByExpression($parentId, $expression, &$nodeDataArray, &$linkDataArray){

        $child_ids = self::getIdsFromExpression($expression);

        foreach ($child_ids as $id) {
            $child_rule = self::findModel($id);
            self::generateTree($child_rule, $nodeDataArray, $linkDataArray, $parentId);
        }
    }


    public function addNodeByMapping($rule){

        $description = [];

        $child_ids = [];

        $rule_mapping = RuleExtendMap::getExtendRuleMapping($rule->id);

        foreach ($rule_mapping as $key => $mapping) {
            $expression = self::expressionTransform($mapping->expression);
            $result = $mapping->result;
            $description[] = [
                'left'      => $expression,
                'middle'    => ' : ',
                'right'     => $result
            ];
            $child_ids = array_unique(array_merge($child_ids, self::getIdsFromExpression($mapping->expression)));
            $child_ids = array_unique(array_merge($child_ids, self::getIdsFromExpression($mapping->result)));
            $child_ids = array_unique(array_merge($child_ids, self::getIdsFromExpression($rule->result)));
        }

        return [
            'description' => $description,
            'child_ids' => $child_ids
        ];

    }

    public function expressionTransform($expression){

        $expression = str_replace(' ', '', $expression);

        $expression = preg_replace_callback(
            "/@[0-9]+/",
            function ($matches) {
                $matche = str_replace('@', '', $matches[0]);
                return '特征'.$matche;
            },
            $expression
        );

        return $expression;
    }

    public function getIdsFromExpression($expression){

        $matches = [];
        $expression = str_replace(' ', '', $expression);
        $expression = preg_match_all("/@[0-9]+/", $expression, $matches);

        $child_ids = [];

        foreach ($matches[0] as $key => $value) {
            $child_id = str_replace('@', '', $value);
            $child_ids[] = $child_id;
        }

        return $child_ids;
    }

    //复制树
    public function copyTree($rule){

        //查找该树依赖关系
        $nodeDataArray = [];
        $linkDataArray = [];
        $this->generateTree($rule, $nodeDataArray, $linkDataArray);

        if (empty($linkDataArray)) {//只有根
            $ret = self::copyRuleNode($rule->id);
            if (empty($ret)) {
                return false;
            }
        } else {
            $transaction = self::getDb()->beginTransaction();
            try {
                $nodes = [$rule->id];
                $ret = $this->copyNode($nodes, $linkDataArray);
                if (empty($ret)) {
                    return false;
                }
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::error("复制树失败(rule_id = ".$rule->id.") ".$e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * 复制节点
     * 递归：复制当前节点,并修改父节点中的子节点为当前新建的节点
     * @param array                     $nodes             当前需要复制的节点
     * @param array                     $linkDataArray     整颗树的节点数组
     * @param array                     $parentNodes       当前节点的父节点
     * @param array                     $copied_node       已复制过的数组
     */
    public function copyNode($nodes, $linkDataArray, $parentNodes=[], $copied_node = [])
    {
        $next_nodes = [];
        $next_parent_ids = [];
        foreach ($nodes as $key => $old_rule_id) {
            //第1步 判断当前节点能否扩展
            if(empty($linkDataArray[$old_rule_id])){
                continue;
            }

            //第2步 在rule中复制该节点
            $newRuleNode = self::copyRuleNode($old_rule_id, $copied_node);
            if (empty($newRuleNode)) {
                return false;
            }

            //第3步 若该节点是映射，则复制映射关系
            if ($newRuleNode->extend_type == self::EXTEND_TYPE_MAPPING) {
                $mappingNodes = self::copyMappingNodes($old_rule_id, $newRuleNode->id, $copied_node);
                if (empty($mappingNodes)) {
                    return false;
                }
            }

            $copied_node[$old_rule_id] = $newRuleNode->id;
            //第4步 修改父节点中的子节点为当前新建的节点
            if (!empty($parentNodes)) {
                foreach ($parentNodes as $parent_id => $value) {
                    if (in_array($old_rule_id, $parentNodes[$parent_id])) {
                        $parentNode = self::findModel($parent_id);
                        switch ($parentNode->extend_type) {
                            //父节点为表达式 修改rule中父节点expression中的rule_id
                            case self::EXTEND_TYPE_EXPRESSION:
                                $parentNode = self::findModel($parent_id);
                                $parentNode->expression = str_replace("@".$old_rule_id,"@".$newRuleNode->id,$parentNode->expression);
                                if (!$parentNode->save()) {
                                    return false;
                                }
                                break;

                            //父节点为映射
                            case self::EXTEND_TYPE_MAPPING:
                                $newMappings = RuleExtendMap::find()
                                ->where(['state' => RuleExtendMap::STATE_USABLE, 'status' => RuleExtendMap::STATUS_ACTIVE, 'rule_id' => $parent_id])
                                ->andWhere(['or' , ['like' , 'expression' , '@'.$old_rule_id] , ['like' , 'result' , '@'.$old_rule_id]])
                                ->orderBy('order ASC')
                                ->all();

                                if (empty($newMappings)) {//修改rule中父节点expression中的rule_id
                                    $parentNode = self::findModel($parent_id);
                                    $parentNode->expression = str_replace("@".$old_rule_id,"@".$newRuleNode->id,$parentNode->expression);
                                    if (!$parentNode->save()) {
                                        return false;
                                    }
                                } else {//修改rule_extend_mapping中父节点expression或者result中的rule_id
                                    foreach ($newMappings as $key => $newMapping) {
                                        $newMapping->expression = str_replace("@".$old_rule_id,"@".$newRuleNode->id,$newMapping->expression);
                                        $newMapping->result = str_replace("@".$old_rule_id,"@".$newRuleNode->id,$newMapping->result);
                                        if (!$newMapping->save()) {
                                            return false;
                                        }
                                    }
                                }
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                }

            }

            //整理下次要复制的节点，并传入当前复制的节点作为下次的父节点
            foreach ($linkDataArray[$old_rule_id] as $key => $value) {
                $next_nodes[] = $key;
                $next_parent_ids[$newRuleNode->id][] = $key;
            }
        }

        //第5步 递归
        if (!empty($next_nodes) || !empty($next_parent_ids)) {
            $ret = self::copyNode($next_nodes, $linkDataArray, $next_parent_ids, $copied_node);
            return $ret;
        }else{
            return true;
        }

    }

    //复制rule节点
    public function copyRuleNode($old_rule_id, $copied_node=[])
    {

        //已复制过的节点直接返回
        if (!empty($copied_node[$old_rule_id])) {
            $newRuleNode = self::findModel($copied_node[$old_rule_id]);

            if (empty($newRuleNode)) {
                return false;
            }
            return $newRuleNode;
        }
        $newRuleNode = self::findModel($old_rule_id);
        $newRuleNode = new self($newRuleNode);
        $newRuleNode->id = '';
        $newRuleNode->name .= "(copy{$old_rule_id}";
        if (!$newRuleNode->save()) {
            Yii::error("复制树失败(rule_id = ".$old_rule_id.") ".var_export($newRuleNode->getFirstErrors(), true));
            return false;
        }

        $newRuleNode->name .= " => {$newRuleNode->id})";
        if (!$newRuleNode->save()) {
            Yii::error("复制树失败(rule_id = ".$old_rule_id.") ".var_export($newRuleNode->getFirstErrors(), true));
            return false;
        }
        return $newRuleNode;
    }

    //复制rule_extend_mapping映射关系表
    public function copyMappingNodes($old_rule_id, $new_rule_id, $copied_node=[])
    {

        //已复制过的节点直接返回
        if (!empty($copied_node[$old_rule_id])) {
            return true;
        }
        $oldMappings = RuleExtendMap::getExtendRuleMapping($old_rule_id);
        if (empty($oldMappings)) {
            return false;
        }

        foreach ($oldMappings as $key => $oldMapping) {
            $oldMapping->id = '';
            $oldMapping->rule_id = $new_rule_id;
            $newMapping = new RuleExtendMap($oldMapping);

            if (!$newMapping->save()) {
                Yii::error("复制树(rule_id = ".$old_rule_id.")失败 =》复制映射关系失败 =》".var_export($newMapping->getFirstErrors(), true));
                return false;
            }
        }

        return true;
    }
}
