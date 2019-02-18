<?php

namespace backend\controllers;

use common\models\LoanPerson;
use common\models\OnlineBankInfo;
use common\models\mongo\mobileInfo\PhoneOperatorDataMongo;
use common\services\DataToBaseService;
use common\services\RiskControlService;
use Yii;
use yii\base\Exception;
use yii\web\NotFoundHttpException;
use common\models\risk\Rule;
use common\models\risk\RuleNode;
use yii\data\Pagination;
use common\models\mongo\statistics\UserMobileContactsMongo;

/**
 * 风控规则后台
 *
 */
class RuleController extends  BaseController{

    /**
     * @name 规则列表
     */
    public function actionRuleList(){
        $query = Rule::find()->where(['status' => Rule::STATUS_ACTIVE]);
        $pages = new Pagination(['totalCount' => $query->count()]);
        $pages->pageSize = 5;
        $data = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('rule-list', array(
            'data_list' => $data,
            'pages' => $pages,
        ));
    }

    /**
     * @name 评分树可视化编辑
     */
    public function actionRuleTree(){
        return $this->render('rule-tree');
    }


    public function actionRuleNodeList(){

        $query = RuleNode::find()->where(['status' => RuleNode::STATUS_ACTIVE]);
        $data = $query->all();

        $query2 = Rule::find()->where(['status' => Rule::STATUS_ACTIVE]);
        $pages = new Pagination(['totalCount' => $query2->count()]);
        $pages->pageSize = 5;
        $data2 = $query2->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('rule-node-list', array(
                'data_list' => $data,
            )) . $this->render('rule-list', array(
                'data_list' => $data2,
                'pages' => $pages,
            ));
    }

    public function actionAddRuleNode(){
        $name = Yii::$app->request->post('name', '');

        if ($name === "") {
            Yii::$app->getSession()->setFlash("message", "请输入名称");
            return $this->redirect(['rule-node-list']);
        }

        $ret = RuleNode::addRuleNode(["name" => $name, "description" => ""]);

        Yii::$app->getSession()->setFlash("message", $ret[1]);

        return $this->redirect(['rule-node-list']);
    }

    public function actionAddRuleNodeRelation(){
        $node_id = Yii::$app->request->post('node_id', '');
        $parent_id = Yii::$app->request->post('parent_id', '');
        $parent_result = Yii::$app->request->post('parent_result', '');

        if ($node_id === "" || $parent_id === "" || $parent_result === "") {
            Yii::$app->getSession()->setFlash("message", "请输入有效的节点ID和结果");
            return $this->redirect(['rule-node-list']);
        }

        $ret = RuleNode::addRuleNodeRelation(["node_id" => $node_id, "parent_id" => $parent_id, "parent_result" => $parent_result]);

        Yii::$app->getSession()->setFlash("message", $ret[1]);

        return $this->redirect(['rule-node-list']);
    }

    public function actionAddRuleToNode(){
        $r_n_id = Yii::$app->request->post('r_n_id', '');
        $r_id = Yii::$app->request->post('r_id', '');
        $order = Yii::$app->request->post('order', '');

        if ($r_n_id === "" || $r_id === "") {
            Yii::$app->getSession()->setFlash("message", "请输入有效的规则节点ID或规则ID");
            return $this->redirect(['rule-node-list']);
        }

        $ret = RuleNode::addRuleToNode(["r_n_id" => $r_n_id, "r_id" => $r_id, "order" => $order]);

        Yii::$app->getSession()->setFlash("message", $ret[1]);

        return $this->redirect(['rule-node-list']);
    }

    public function actionDeleteNodeRule(){
        $id = Yii::$app->request->post('id', '');
        $ret = RuleNode::deleteNodeRule(['id' => $id]);



    }

    /**
     * @name 特征结果列表
     */

    public function actionCheckReport(){
        $id = Yii::$app->request->get('id', '');

        return $this->render('check-report', array(
            'id' => $id
        ));
    }

    /**
     * @name 特征结果列表
     */
    public function actionBasicReport(){
        $id = Yii::$app->request->get('id', '');

        return $this->render('basic-report', array(
            'id' => $id
        ));
    }
    /**
     * @name 获取运营商数据
     */
    public function actionGetYysReport(){

        $user_id = Yii::$app->request->get('id');
        $loanPerson = LoanPerson::find()->where(['id'=>$user_id])->one();
        $contact = UserMobileContactsMongo::find()->where(['user_id' => $user_id . '' ])->asArray()->all();

        $service = new RiskControlService();

        return $this->render('yys-report', array(
            'data' => $service->getYysData($loanPerson),
            'loanPerson' => $loanPerson,
            'contact' => $contact
        ));
    }

    /**
     * @name 获取淘宝数据
     */
    public function actionGetTaobaoReport()
    {
        $user_id = Yii::$app->request->get('id');

        $loanPerson = LoanPerson::find()->where(['id' => $user_id])->one(Yii::$app->get('db_kdkj_rd'));
        $service = new RiskControlService();
        $model = $service->getTaobaoData($loanPerson);
        // echo '<pre>';
        // print_r($info);die;
        return $this->render('taobao-report', array(
            'data' => $model,
            'loanPerson' => $loanPerson
        ));

    }


    /**
     * @name 获取银行数据
     */
    public function actionGetInternetbankReport()
    {


        $user_id = Yii::$app->request->get('id');
        //$user_id = 3510;
        $loanPerson = LoanPerson::find()->where(['id' => $user_id])->one();
        $info = OnlineBankInfo::find()->where(['user_id'=>$user_id,'status'=>10])->one(Yii::$app->get('db_kdkj_rd'));
        $data = json_decode($info['data'],true);

        return $this->render('internetbank-report', array(
            'info' => $info,
            'data' => $data,
            'loanPerson' => $loanPerson
        ));

    }


}
