<?php

namespace backend\controllers;

use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use common\models\risk\Rule;
use common\models\risk\RuleCheckReport;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\RuleNode;
use common\models\risk\RuleNodeMap;
use common\models\risk\RuleNodeRelation;
use common\models\LoanPerson;
use common\services\RiskControlService;
use frontend\controllers\BaseController;

/**
 *

 *
 * 风控规则后台API接口
 *
 */
class RuleJsonController extends  BaseController{


	/**
	 *

	 *
	 * 创建新结点
	 *
	 */
	public function actionCreateNode(){
		$parent_id = Yii::$app->request->post('parent_id', '');
		$rule_node = RuleNode::createRuleNode();

		if (!empty($parent_id)) {
			$rule_node_relation = new RuleNodeRelation();
			$rule_node_relation->node_id = $rule_node->id;
			$rule_node_relation->parent_id = $parent_id;
			if(!$rule_node_relation->save()){
				return [
					'code' => -1,
					'message' => '创建关联失败',
					'data' => [],
				];
			}
		}

		return ['code' => 0, 'data' => $rule_node];
	}

	public function actionCreateNodeRelation(){
		$node_id = Yii::$app->request->post('node_id', '');
		$parent_id = Yii::$app->request->post('parent_id', '');

		if(empty($node_id) || empty($parent_id)){
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$rule_node_map = RuleNodeMap::find()->where(['r_n_id' => $parent_id, 'status' => RuleNodeMap::STATUS_ACTIVE])->one();
		if (!empty($rule_node_map)) {
			return [
				'code' => -1,
				'message' => '指定的父节点为规则节点',
				'data' => [],
			];
		}

		$rule_node_relation = RuleNodeRelation::find()->where(['node_id' => $node_id, 'status' => RuleNodeRelation::STATUS_ACTIVE])->one();
		if(empty($rule_node_relation)){
			$rule_node_relation = new RuleNodeRelation();
			$rule_node_relation->node_id = $node_id;
		}
		$rule_node_relation->parent_id = $parent_id;
		if(!$rule_node_relation->save()){
			return [
				'code' => -1,
				'message' => '创建关联失败',
				'data' => [],
			];
		}

		return ['code' => 0, 'data' => $rule_node_relation];
	}

	public function actionRemoveNode(){
		$node_id = Yii::$app->request->post('node_id', '');
		if (empty($node_id)) {
			return [
				'code' => -1,
				'message' => '创建关联失败',
				'data' => [],
			];
		}


		RuleNode::UpdateAll(['status' => RuleNode::STATUS_DELETED], ['id' => $node_id, 'status' => RuleNode::STATUS_ACTIVE]);
		RuleNodeRelation::UpdateAll(['status' => RuleNodeRelation::STATUS_DELETED], ['node_id' => $node_id, 'status' => RuleNodeRelation::STATUS_ACTIVE]);
		RuleNodeRelation::UpdateAll(['status' => RuleNodeRelation::STATUS_DELETED], ['parent_id' => $node_id, 'status' => RuleNodeRelation::STATUS_ACTIVE]);
		RuleNodeMap::UpdateAll(['status' => RuleNodeMap::STATUS_DELETED], ['r_n_id' => $node_id, 'status' => RuleNodeMap::STATUS_ACTIVE]);

		return ['code' => 0, 'data' => []];
	}

	public function actionModifyNode(){
		$node_id = Yii::$app->request->post('node_id', '');
		$parent_id = Yii::$app->request->post('parent_id', '');
		$name = Yii::$app->request->post('name', '');
		$weight = Yii::$app->request->post('weight', '');
		$rule_node = RuleNode::find()->where(['id' => $node_id, 'status' => RuleNode::STATUS_ACTIVE])->one();
		if (empty($rule_node)) {
			return [
				'code' => -1,
				'message' => '未找到节点',
				'data' => [],
			];
		}

		$rule_node->name = $name;
		$rule_node->weight = $weight;
		$rule_node->save();

		$rule_node_relation = RuleNodeRelation::find()->where(['node_id' => $node_id, 'status' => RuleNodeRelation::STATUS_ACTIVE])->one();
		if(empty($rule_node_relation)){
			$rule_node_relation = new RuleNodeRelation();
			$rule_node_relation->node_id = $node_id;
		}
		$rule_node_relation->parent_id = $parent_id;
		if(!$rule_node_relation->save()){
			return [
				'code' => -1,
				'message' => '创建关联失败',
				'data' => [],
			];
		}
		return ['code' => 0, 'data' => []];
	}

	public function actionReports(){
		$id = Yii::$app->request->get('id', 0);
		$node_name = Yii::$app->request->get('node_name', '');

		if (empty($id) || empty($node_name)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$node = RuleNode::find()->where(['name' => $node_name, 'status' => RuleNode::STATUS_ACTIVE])->one();

		if (empty($node)) {
			return [
				'code' => -1,
				'message' => '该评分树未被定义',
				'data' => [],
			];
		}

		$tree = RuleCheckReport::getReportTree(['p_id' => $id, 'node' => $node]);

		return [
			'code' => 0,
			'message' => 'success',
			'data' => $tree,
		];

	}

	public function actionReportsMongo(){
		$id = Yii::$app->request->get('id', 0);
		$node_name = Yii::$app->request->get('node_name', '');

		if (empty($id) || empty($node_name)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$node = RuleNode::find()->where(['name' => $node_name, 'status' => RuleNode::STATUS_ACTIVE])->one();

		if (empty($node)) {
			return [
				'code' => -1,
				'message' => '该评分树未被定义',
				'data' => [],
			];
		}

		$tree = RuleReportMongo::getReportTree(['p_id' => $id, 'node' => $node]);
		return [
			'code' => 0,
			'message' => 'success',
			'data' => $tree,
		];

	}

	public function actionBasicReports(){
		$id = Yii::$app->request->get('id', 0);
		if (empty($id)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$reports = RuleCheckReport::getBasicReports(['p_id' => $id]);

		return [
			'code' => 0,
			'message' => 'success',
			'data' => $reports,
		];
	}

	public function actionBasicReportsMongo(){
		$id = Yii::$app->request->get('id', 0);
		if (empty($id)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$reports = RuleReportMongo::getBasicReports(['p_id' => $id]);

		return [
			'code' => 0,
			'message' => 'success',
			'data' => $reports,
		];
	}

	public function actionCheckPerson(){
		$id = Yii::$app->request->post('id', 0);
		if (empty($id)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$loan_person = LoanPerson::find()->where(['id' => $id])->one();

		if (empty($loan_person)) {
			return [
				'code' => -2,
				'message' => '未找到该人',
				'data' => [],
			];
		}

        $risk_control_service = new RiskControlService();

        $risk_control_service->runCheckPerson($loan_person);

		return [
			'code' => 0,
			'message' => 'success',
			'data' => [],
		];
	}

	public function actionScorePerson(){
		$id = Yii::$app->request->post('id', 0);
		if (empty($id)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}

		$loan_person = LoanPerson::find()->where(['id' => $id])->one();

		if (empty($loan_person)) {
			return [
				'code' => -2,
				'message' => '未找到该人',
				'data' => [],
			];
		}
        $risk_control_service = new RiskControlService();

		$risk_control_service->runCheckPersonMongo($loan_person);
    	$risk_control_service->runScoreRuleNodeMongo($loan_person, '信用评估');
    	$risk_control_service->runScoreRuleNodeMongo($loan_person, '反欺诈');
    	$risk_control_service->runScoreRuleNodeMongo($loan_person, '禁止项');

		return [
			'code' => 0,
			'message' => 'success',
			'data' => [],
		];
	}

	public function actionGetRootNodes(){
		$root_nodes = RuleNode::getRootNodes();
		return ['code' => 0, 'data' => $root_nodes];
	}

	public function actionGetTreeConstruct(){
		$id = Yii::$app->request->get('id', 0);
		if (empty($id)) {
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}
		$tree_construct = RuleNode::getTreeConstruct($id);
		return ['code' => 0, 'data' => $tree_construct];
	}

	public function actionNewReportValue(){
		$id = Yii::$app->request->get('id',0);
		$node = Yii::$app->request->get('node_id','');
		if(empty($id)){
			return [
				'code' => -1,
				'message' => '输入信息不全',
				'data' => [],
			];
		}
		$value = RuleReportMongo::getNewReportValue($id,$node);
		return [
			'code' => 0,
			'message' => 'success',
			'data' => $value,
		];
	}


}
