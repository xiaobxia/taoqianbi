<?php

namespace backend\controllers;

use backend\models\AdminUser;
use common\helpers\ArrayHelper;
use common\helpers\MailHelper;
use Yii;
use yii\base\Exception;
use yii\base\ErrorException;

use common\models\LoanPerson;
use common\models\risk\Rule;
use common\models\risk\RuleExtendMap;
use common\models\risk\EscapeRule;
use common\models\risk\EscapeTemplate;
use common\models\risk\RuleOperateLog;

use common\services\RiskControlService;

use backend\models\search\RuleSearch;
use backend\models\search\RuleExtendSearch;
use backend\models\search\EscapeRuleSearch;
use backend\models\search\EscapeTemplateSearch;
use common\models\mongo\risk\OrderReportMongo;
use common\models\mongo\risk\RuleReportMongo;
use yii\data\Pagination;

class DecisionTreeController extends BaseController {
    static $mails_watcher = [
        NOTICE_MAIL,
    ];

    /**
     * @name 决策树管理 -特征列表/actionCharacteristicsList
     *
     */
    public function actionCharacteristicsList(){
        $searchModel = new RuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('characteristics-list',[
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * @name 决策树管理 -新建特征/actionCharacteristicsAdd
     *
     */
    public function actionCharacteristicsAdd(){
        $ruleModel = new Rule();
        $extendModel = new RuleExtendMap();
        $searchModel = new RuleExtendSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $params = Yii::$app->request->post();
        $escapetemplate = EscapeTemplate::find()->where("1=1")->asArray()->all();
        if ($ruleModel->load($params)) {
            $ruleModel->state = Rule::STATE_DISABLE;
            $isValid = $ruleModel->validate();
            if($isValid&&$ruleModel->save()){
                $log = new RuleOperateLog();
                $log->rule_id = $ruleModel->id;
                $log->user_id = Yii::$app->user->id;
                $log->operate = RuleOperateLog::OPERATE_ADD;
                $log->remark = "新建了特征".$ruleModel->name;
                $log->save();
                if($ruleModel->extend_type==Rule::EXTEND_TYPE_MAPPING)
                    return $this->redirect(['extend-add','rule_id'=>$ruleModel->id]);
                if (YII_ENV_PROD) {
                    $user = AdminUser::findOne(['id' => $log->user_id]);
                    MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
                }
                return $this->redirect(['characteristics-list']);
            }
        }
        return $this->render('characteristics-add', [
            'ruleModel' => $ruleModel,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'extendModel' => $extendModel,
            'escapetemplate' => $escapetemplate,
        ]);
    }



    /**
     * @name 决策树管理 -编辑特征/actionCharacteristicsUpdate
     */
    public function actionCharacteristicsUpdate($id){
        $extendModel = new RuleExtendMap();
        $ruleModel = Rule::findModel($id);
        $old = [];
        foreach ($ruleModel->attributes() as $attr) {
            $old[$attr] = $ruleModel[$attr];
        }
        $searchModel = new RuleExtendSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['rule_id'=>$ruleModel->id]);
        $params = Yii::$app->request->post();

        $escapetemplate = EscapeTemplate::find()->where("1=1")->asArray()->all();
        if ($ruleModel->load($params)) {
            $isValid = $ruleModel->validate();
            if($isValid&&$ruleModel->save()){
                $changes = "";
                foreach ($ruleModel->attributes() as $attr) {
                    if($old[$attr] != $ruleModel[$attr] && $attr != 'update_time'){
                        $changes .= "属性".$attr.":".$old[$attr]."=>".$ruleModel[$attr]."。";
                    }
                }
                if(!empty($changes)){
                    $log = new RuleOperateLog();
                    $log->rule_id = $id;
                    $log->user_id = Yii::$app->user->id;
                    $log->operate = RuleOperateLog::OPERATE_UPDATE;
                    $log->remark = "特征更新。".$changes;
                    $log->save();

                    if (YII_ENV_PROD) {
                        $user = AdminUser::findOne(['id' => $log->user_id]);
                        MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
                    }
                }
                return $this->redirect(['characteristics-list']);
            }
        }

        return $this->render('characteristics-update', [
            'ruleModel' => $ruleModel,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'extendModel' => $extendModel,
            'escapetemplate' => $escapetemplate,
        ]);
    }

    /**
     * @name 决策树管理 -测试特征/actionCharacteristicsTest
     *
     */
    public function actionCharacteristicsTest($id){
        $extendModel = new RuleExtendMap();
        $ruleModel = Rule::findModel($id);
        $searchModel = new RuleExtendSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['rule_id'=>$ruleModel->id]);
        $escapetemplate = EscapeTemplate::find()->where("1=1")->asArray()->all();
        return $this->render('characteristics-test', [
            'ruleModel' => $ruleModel,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'extendModel' => $extendModel,
            'escapetemplate' => $escapetemplate,
        ]);
    }

    /**
     * @name 决策树管理 -运行测试特征/actionTestRule
     *
     */
    public function actionTestRule($rule_id,$user_id){
        $loanperson = LoanPerson::findOne($user_id);
        if(empty($loanperson)){
            return json_encode(['result'=>'用户不存在']);
        }
        $result = "运行特征失败";
        try{
            $riskControlService = new RiskControlService();
            $ret = $riskControlService->runSpecificRule([$rule_id],$loanperson);
            foreach ($ret as $key => $value) {
                $result = "risk:".$value['risk']."\ndetail:".$value['detail']."\nvalue:".var_export($value['value'],true).".";
                break;
            }
        }catch(Exception $e1){
            $result = "---error1---\nmessage:".$e1->getMessage()."\n".$e1->getTraceAsString();
        }catch(ErrorException $e2){

            var_dump($e2->getMessage());
            var_dump($e2->getFile());
            var_dump($e2->getLine());
            $result = "error: \n". $e2->getMessage() . "; \n" . $e2->getFile() . "; \n" . $e2->getLine() . "; \n" .$e2->getTraceAsString();
            //$result = "---error2---\nmessage:".$e2->getMessage()."\n".$e2->getTraceAsString();
        }
        ob_end_clean();
        return json_encode(['result'=>$result]);
    }

    /**
     * @name 决策树管理 -添加映射/actionExtendAdd
     *
     */
    public function actionExtendAdd($rule_id){
        $extendModel = new RuleExtendMap();
        $ruleModel = Rule::findModel($rule_id);
        $searchModel = new RuleExtendSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['rule_id'=>$rule_id]);
        $params = Yii::$app->request->post();
        $escapetemplate = EscapeTemplate::find()->where("1=1")->asArray()->all();
        if ($extendModel->load($params)) {
            $extendModel->rule_id = $rule_id;
            $isValid = $extendModel->validate();
            if($isValid&&$extendModel->save()){
                $log = new RuleOperateLog();
                $log->rule_id = $extendModel->id;
                $log->user_id = Yii::$app->user->id;
                $log->operate = RuleOperateLog::OPERATE_EXTEND_ADD;
                $log->remark = "新建了映射".$extendModel->id;
                $log->save();
                if (YII_ENV_PROD) {
                    $user = AdminUser::findOne(['id' => $log->user_id]);
                    MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
                }
                return $this->redirect(['characteristics-update','id'=>$rule_id]);
            }
        }
        return $this->render('extend-add', [
            'ruleModel' => $ruleModel,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
            'escapetemplate' => $escapetemplate,
            'extendModel' => $extendModel
        ]);
    }

    /**
     * @name 决策树管理 -编辑映射/actionExtendUpdate
     *
     */
    public function actionExtendUpdate($id){
        $extendModel =  RuleExtendMap::findModel($id);
        $old = [];
        foreach ($extendModel->attributes() as $attr) {
            $old[$attr] = $extendModel[$attr];
        }
        $ruleModel = Rule::findModel($extendModel->rule_id);
        $searchModel = new RuleExtendSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['rule_id'=>$extendModel->rule_id]);
        $params = Yii::$app->request->post();
        if (isset($params['RuleExtendMap']['result']) && !empty($expression = $params['RuleExtendMap']['result'])) {
            $expression = str_replace(' ', '', $expression);
            $expression .= ';';
            //语法检查
            eval("return ".$expression);
        }

        $escapetemplate = EscapeTemplate::find()->where("1=1")->asArray()->all();
        if ($extendModel->load($params)) {
            $isValid = $extendModel->validate();
            if($isValid&&$extendModel->save()){
                $changes = "";
                foreach ($extendModel->attributes() as $attr) {
                    if($old[$attr] != $extendModel[$attr] && $attr != 'update_time'){
                        $changes .= "属性".$attr.":".$old[$attr]."=>".$extendModel[$attr]."。";
                    }
                }
                if(!empty($changes)){
                    $log = new RuleOperateLog();
                    $log->rule_id = $id;
                    $log->user_id = Yii::$app->user->id;
                    $log->operate = RuleOperateLog::OPERATE_EXTEND_UPDATE;
                    $log->remark = "映射更新。".$changes;
                    $log->save();

                    if (YII_ENV_PROD) {
                        $user = AdminUser::findOne(['id' => $log->user_id]);
                        MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
                    }
                }
                return $this->redirect(['characteristics-update','id'=>$extendModel->rule_id]);
            }
        }
        return $this->render('extend-update', [
            'ruleModel' => $ruleModel,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
            'escapetemplate' => $escapetemplate,
            'extendModel' => $extendModel
        ]);
    }

    /**
     * @name 决策树管理 -启用特征/actionCharacteristicsApprove
     *
     */
    public function actionCharacteristicsApprove($id){
        $ruleModel = Rule::findModel($id);
        $ret = $ruleModel->approve();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_USE;
        $log->remark = "启用了特征".$ruleModel->name;
        $log->save();

        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }
        return $ret;
    }

    /**
     * @name 决策树管理 -停用特征/actionCharacteristicsReject
     *
     */
    public function actionCharacteristicsReject($id){
        $ruleModel = Rule::findModel($id);
        $ret = $ruleModel->reject();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_DISUSE;
        $log->remark = "停用了特征".$ruleModel->name;
        $log->save();

        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }

        return $ret;
    }

    /**
     * @name 决策树管理 -调试特征/actionCharacteristicsDebug
     *
     */
    public function actionCharacteristicsDebug($id){
        $ruleModel = Rule::findModel($id);
        $ret = $ruleModel->debug();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_DEBUG;
        $log->remark = "将特征".$ruleModel->name."置为调试";
        $log->save();

        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }

        return $ret;
    }

    /**
     * @name 决策树管理 -启用映射/actionExtendApprove
     *
     */
    public function actionExtendApprove($id){
        $extendModel = RuleExtendMap::findModel($id);
        $ret = $extendModel->approve();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_EXTEND_USE;
        $log->remark = "启用了映射".$id;
        $log->save();
        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }
        return $this->redirect(['characteristics-update','id'=>$extendModel->rule_id]);
    }

    /**
     * @name 决策树管理 -停用映射/actionExtendReject
     *
     */
    public function actionExtendReject($id){
        $extendModel = RuleExtendMap::findModel($id);
        $ret = $extendModel->reject();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_EXTEND_DISUSE;
        $log->remark = "停用了映射".$id;
        $log->save();
        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }
        return $this->redirect(['characteristics-update','id'=>$extendModel->rule_id]);
    }

    /**
     * @name 决策树管理 -删除映射/actionExtendDelete
     *
     */
    public function actionExtendDelete($id){
        $extendModel = RuleExtendMap::findModel($id);
        $ret = $extendModel->delete();
        $log = new RuleOperateLog();
        $log->rule_id = $id;
        $log->user_id = Yii::$app->user->id;
        $log->operate = RuleOperateLog::OPERATE_EXTEND_DELETE;
        $log->remark = "删除了映射".$id."。";
        $log->save();
        if (YII_ENV_PROD) {
            $user = AdminUser::findOne(['id' => $log->user_id]);
            MailHelper::sendCmdMail('风控决策树变动通知', date('Y-m-d') . ', ' . $user->username . ", " . $log->remark, self::$mails_watcher);
        }
        return $this->redirect(['characteristics-update','id'=>$extendModel->rule_id]);
    }

    /**
     * @name 决策树管理 -验证表达式/actionExtendDelete
     *
     */
    public function actionValidateExpression(){
        $ids = Yii::$app->request->get('id',null);
        if(empty($ids))
            return "";
        $id = explode(",",$ids);
        $rules = Rule::find()->where(['in','id',$id])->asArray()->all();
        if(count($rules)!=count($id)){
            $idfound = [];
            $erroid = '';
            foreach ($rules as $key => $value) {
                $idfound[$value['id']] = $value;
            }
            foreach ($id as $key => $value) {
                if(!array_key_exists($value, $idfound)){
                    $erroid .= $value.';';
                }
            }
            if(!empty($erroid)){
                return $erroid;
            }
        }
        return "";
    }

    /**
     * @name 决策树管理 -依赖关系/actionDependencyView
     */
    public function actionCharacteristicsViewDependence($id){
        $nodeDataArray = [];
        $linkDataArray = [];
        $rule = Rule::findModel($id);
        $service = new Rule();
        $service->generateTree($rule, $nodeDataArray, $linkDataArray);
        //echo json_encode($nodeDataArray);die;
        // 根节点数据
        $root = [
            'id' => $id,
            "isroot" => true,
            "topic" => $nodeDataArray[$id]['title'],
        ];

        // 决策树，先添加根节点
        $tree [] = $root;

        // 获取决策树子节点数据
        $this->_getSubTree($id, $tree, $nodeDataArray, $linkDataArray);

        // echo json_encode($tree);die;
        return $this->render('dependency-view', [
            'tree' => $tree,
        ]);
    }

    /**
     * @name 决策树管理 -依赖关系/actionCharacteristicsViewDependenceTest
     */
    public function actionCharacteristicsViewDependenceTest($id){
        $nodeDataArray = [];
        $linkDataArray = [];
        $rule = Rule::findModel($id);
        $service = new Rule();
        $service->generateTree($rule, $nodeDataArray, $linkDataArray);
        //echo json_encode($nodeDataArray);die;
        // 根节点数据
        $root = [
            'id' => $id,
            "isroot" => true,
            "topic" => $nodeDataArray[$id]['title'],
        ];

        // 决策树，先添加根节点
        $tree [] = $root;
        // 获取决策树子节点数据
        $this->_getSubTree($id, $tree, $nodeDataArray, $linkDataArray);
        return $this->render('dependency-view-test', [
            'tree' => $tree,
            'id' => intval($id),
        ]);
    }

    /**
     * @name 决策树管理 -运行测试特征/actionTestDependenceRule
     *
     */
    public function actionTestDependenceRule($rule_id,$user_id){
        $loanperson = LoanPerson::findOne($user_id);
        if(empty($loanperson)){
            return json_encode(['code'=>-1,'msg'=>'用户不存在']);
        }
        $result = ['code'=>-1,'msg'=>'运行特征失败'];
        try{
            $riskControlService = new RiskControlService();
            $msg = $riskControlService->runSpecificRule([$rule_id],$loanperson,null,0,1);
            $result = ['code'=> 0,'msg' => $msg];
        }catch(Exception $e1){
            $error1 = "---error1---\nmessage:".$e1->getMessage()."\n".$e1->getTraceAsString();
            $result = ['code'=> -2 ,'msg' => $error1];
        }catch(ErrorException $e2){
            var_dump($e2->getMessage());
            var_dump($e2->getFile());
            var_dump($e2->getLine());
            $error2 = "error: \n". $e2->getMessage() . "; \n" . $e2->getFile() . "; \n" . $e2->getLine() . "; \n" .$e2->getTraceAsString();
            $result = ['code' => -3,'msg' => $error2];
        }
        ob_end_clean();
        return json_encode($result);
    }


    /**
     * @name 决策树管理 -依赖关系/actionDependencyView
     */
    public function actionCharacteristicsViewDependence2($id){

        $nodeDataArray = [];
        $linkDataArray = [];

        $rule = Rule::findModel($id);
        $service = new Rule();
        $service->generateTree($rule, $nodeDataArray, $linkDataArray);

        //echo json_encode($nodeDataArray);die;
        // 根节点数据
        $root = [
            'id' => $id,
            "isroot" => true,
            "topic" => $nodeDataArray[$id]['title'],
        ];

        // 决策树，先添加根节点
        $tree [] = $root;

        // 获取决策树子节点数据
        $this->_getSubTree($id, $tree, $nodeDataArray, $linkDataArray);

        // echo json_encode($tree);die;
        return $this->render('dependency-view2', [
            'tree' => $tree,
        ]);
    }

    private function _getSubTree($id, &$subTree, &$nodeDataArray, &$linkDataArray)
    {
        if(empty($linkDataArray[$id]))
        {
            return false;
        }

        $subNodeId = array_keys($linkDataArray[$id]);
        foreach($subNodeId as $node_id)
        {
            // 构造子节点
            $topic = $nodeDataArray[$node_id]['title']."<br>".$nodeDataArray[$node_id]['text'];
            $topic = str_replace("\\r\\n","<br>",$topic);
            $topic = str_replace("<br><br>","<br>",$topic);
            $rule_map = RuleExtendMap::findAll(['rule_id' => $node_id]);
            if(!empty($rule_map) && !empty($nodeDataArray[$node_id]['category']) && $nodeDataArray[$node_id]['category'] == "Mapping"){
                $topic = $topic . "<br><br>映射规则:";
                foreach($rule_map as $r){
                    $topic = "$topic <br> {$r['order']}. [ 表达式:{$r['expression']}],[结果: {$r['result']} ]";
                }
            }
            $node = [
                'id' => $node_id,
                'parentid' => $id,
                'topic' => $topic,
                'expanded' => false
            ];

            // 添加子节点
            $subTree[] = $node;
            $this->_getSubTree($node_id, $subTree, $nodeDataArray, $linkDataArray);
        }

        return true;
    }

    /**
     * @name 决策树管理 -转义模版列表/actionEscapeTemplateList
     *
     */
    public function actionEscapeTemplateList(){
        $model = new EscapeTemplate();
        $searchModel = new EscapeTemplateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('escape-template-list',[
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * @name 决策树管理 -新建模版/actionEscapeTemplateAdd
     *
     */
    public function actionEscapeTemplateAdd(){
        $model = new EscapeTemplate();
        $searchModel = new EscapeTemplateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $params = Yii::$app->request->post();
        if ($model->load($params)) {
            $model->state = EscapeTemplate::STATE_DISABLE;
            if($model->validate()&&$model->save()){
                return $this->redirect(['escape-template-list']);
            }
        }
        return $this->render('escape-template-add', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
        ]);
    }

    /**
     * @name 决策树管理 -编辑模版/actionEscapeTemplateUpdate
     *
     */
    public function actionEscapeTemplateUpdate($id){
        $model = EscapeTemplate::findModel($id);
        $searchModel = new EscapeTemplateSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $params = Yii::$app->request->post();
        if ($model->load($params)) {
            if($model->validate()&&$model->save()){
                return $this->redirect(['escape-template-list']);
            }
        }
        return $this->render('escape-template-update', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
        ]);
    }

    /**
     * @name 决策树管理 -启用模版/actionEscapeTemplateApprove
     *
     */
    public function actionEscapeTemplateApprove($id){
        $model = EscapeTemplate::findModel($id);
        $ret = $model->approve();
        $type = ($ret) ? 'success' : 'error';
        $message = "启用模版{$model->id}" . (($ret['flag']) ? '成功' : '失败');
        Yii::$app->getSession()->setFlash($type, $message);
        return $this->redirect(['escape-template-list']);
    }

    /**
     * @name 决策树管理 -停用模版/actionEscapeTemplateReject
     *
     */
    public function actionEscapeTemplateReject($id){
        $model = EscapeTemplate::findModel($id);
        $ret = $model->reject();
        $type = ($ret) ? 'success' : 'error';
        $message = "停用模版{$model->id}" . (($ret['flag']) ? '成功' : '失败');
        Yii::$app->getSession()->setFlash($type, $message);
        return $this->redirect(['escape-template-list']);
    }

    /**
     * @name 决策树管理 -转义模版规则列表/actionEscapeRuleList
     *
     */
    public function actionEscapeRuleList($template_id=''){
        $model = new EscapeRule();
        $searchModel = new EscapeRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['template_id'=>$template_id]);
        return $this->render('escape-rule-list',[
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'template_id' => $template_id,
        ]);
    }

    /**
     * @name 决策树管理 -新建模版/actionEscapeRuleAdd
     *
     */
    public function actionEscapeRuleAdd($template_id){
        $model = new EscapeRule();
        $searchModel = new EscapeRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['template_id'=>$template_id]);
        $params = Yii::$app->request->post();
        if ($model->load($params)) {
            $model->template_id = $template_id;
            if($model->validate()&&$model->save()){
                return $this->redirect(['escape-rule-list','template_id'=>$template_id]);
            }
        }
        return $this->render('escape-rule-add', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
            'template_id' => $template_id,
        ]);
    }

    /**
     * @name 决策树管理 -编辑模版/actionEscapeRuleUpdate
     *
     */
    public function actionEscapeRuleUpdate($id){
        $model = EscapeRule::findModel($id);
        $searchModel = new EscapeRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['template_id'=>$model->template_id]);
        $params = Yii::$app->request->post();
        if ($model->load($params)) {
            if($model->validate()&&$model->save()){
                return $this->redirect(['escape-rule-list','template_id'=>$model->template_id]);
            }
        }
        return $this->render('escape-rule-update', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'add_map' => true,
            'template_id' => $model->template_id,
        ]);
    }

    /**
     * @name 决策树管理 -启用模版规则/actionEscapeRuleApprove
     *
     */
    public function actionEscapeRuleApprove($id){
        $model = EscapeRule::findModel($id);
        $ret = $model->approve();
        $type = ($ret) ? 'success' : 'error';
        $message = "启用模版规则{$model->id}" . (($ret['flag']) ? '成功' : '失败');
        Yii::$app->getSession()->setFlash($type, $message);
        return $this->redirect(['escape-rule-list','template_id'=>$model->template_id]);
    }

    /**
     * @name 决策树管理 -停用模版规则/actionEscapeRuleReject
     *
     */
    public function actionEscapeRuleReject($id){
        $model = EscapeRule::findModel($id);
        $ret = $model->reject();
        $type = ($ret) ? 'success' : 'error';
        $message = "停用模版规则{$model->id}" . (($ret['flag']) ? '成功' : '失败');
        Yii::$app->getSession()->setFlash($type, $message);
        return $this->redirect(['escape-rule-list','template_id'=>$model->template_id]);
    }

    /**
     * @name 决策树管理 -删除模版规则/actionEscapeRuleDelete
     *
     */
    public function actionEscapeRuleDelete($id){
        $model = EscapeRule::findModel($id);
        $ret = $model->delete();
        $type = ($ret) ? 'success' : 'error';
        $message = "删除模版规则{$model->id}" . (($ret['flag']) ? '成功' : '失败');
        Yii::$app->getSession()->setFlash($type, $message);
        return $this->redirect(['escape-rule-list','template_id'=>$model->template_id]);
    }

    /**
     * @name 决策树管理 -获取模版规则/actionGetEscapeRule
     *
     */
    public function actionGetEscapeRule(){
        $template_id = Yii::$app->request->get("template_id",null);
        $ret = [];
        if(!empty($template_id)){
            $rules = EscapeRule::find()->where(["template_id"=>$template_id,"status"=>EscapeRule::STATUS_NORMAL,"state"=>EscapeRule::STATE_USABLE])->asArray()->all();
            if($rules){
                foreach ($rules as $key => $value) {
                    $ret[] = ["value"=>$value['value'],"sign"=>$value['sign']];
                }
            }
        }
        return json_encode($ret);
    }

    /**
     * @name 决策树管理 -开始事务/actionTransactionBegin
     *
     */
    public function actionTransactionBegin(){

        $user = Yii::$app->user;
    }

    /**
     * @name 决策树管理 -提交事务/actionTransactionCommit
     *
     */
    public function actionTransactionCommit(){

    }

    /**
     * @name 决策树管理 -放弃事务/actionTransactionRollback
     *
     */
    public function actionTransactionRollback(){

    }

    /**
     * @name 决策树管理 -监测事务/actionTransactionCheck
     *
     */
    public function actionTransactionCheck(){

    }

    /**
     * @name 决策树管理 -复制决策树/actionCharacteristicsCopy
     */
    public function actionCharacteristicsCopy($id){
        $rule = Rule::findModel($id);
        $model = new Rule();
        $ret = $model->copyTree($rule);
        return $ret;
    }

    /**
     * @name 订单决策详情 -列表/actionOrderReportList
     *
     */
    public function actionOrderReportList() {
        $condition = $this->getFilter();
        $query = OrderReportMongo::find()->where($condition);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('order-report-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     *
     * @name 订单决策详情 -详细信息/actionOrderReportView
     * @param string $_id
     * @return mixed
     */
    public function actionOrderReportView($_id) {
        $list = OrderReportMongo::findOne($_id);

        $nodeDataArray = [];
        $linkDataArray = [];

        $root_id = 390;
        $rule = Rule::findModel($root_id);
        $service = new Rule();
        $service->generateTree($rule, $nodeDataArray, $linkDataArray);

        // 根节点数据
        $root = [
            'id' => $root_id,
            "isroot" => true,
            "topic" => $nodeDataArray[$root_id]['title'],
        ];

        // 决策树，先添加根节点
        $tree [] = $root;

        // 获取决策树子节点数据
        $this->_getSubTree($root_id, $tree, $nodeDataArray, $linkDataArray);

        $report = $list['basic_report'] ? (array)$list['basic_report'] : [];
        $result = [];
        foreach ($tree as $val) {
            $risk = $report[$val['id']]['risk'] ?? '';
            if (!isset($report[$val['id']]['risk']) && isset($report[$root_id])) {
                continue;
            }
            $detail = $report[$val['id']]['detail'] ?? '';
            $value = isset($report[$val['id']]['value']) ? (is_array($report[$val['id']]['value']) ? json_encode($report[$val['id']]['value'], JSON_UNESCAPED_UNICODE) : $report[$val['id']]['value']) : '';
            if ($root_id == $val['id']) {
                $value = $this->wrapStr($value);
            }
            $topic = $val['id'].":<br> risk: ".$risk."<br>detail: ".json_encode($detail,JSON_UNESCAPED_UNICODE)."<br>value: ".$value;
            $result[] = [
                'id' => $val['id'],
                'isroot' => $val['isroot'] ?? false,
                'parentid' => $val['parentid'] ?? '',
                'topic' => $topic
            ];
        }

        return $this->render('order-report-view', array(
            'list' => $list,
            'tree' => $result,
        ));
    }

    /**
     * @name 授信决策详情 -列表/actionRuleReportList
     *
     */
    public function actionRuleReportList() {
        $condition = $this->getFilter();
        $query = RuleReportMongo::find()->where($condition);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('rule-report-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     *
     * @name 授信决策详情 -详细信息/actionRuleReportView
     * @param string $_id
     * @return mixed
     */
    public function actionRuleReportView($_id) {
        $list = RuleReportMongo::findOne(intval($_id));
        $report = $list['basic_report'] ? (array)$list['basic_report'] : [];
        $score = $list['score_report'] ? (array)$list['score_report'] : [];

        $nodeDataArray = [];
        $linkDataArray = [];

        $root_id = 347;
        $rule = Rule::findModel($root_id);
        $service = new Rule();
        $service->generateTree($rule, $nodeDataArray, $linkDataArray);

        // 根节点数据
        $root = [
            'id' => $root_id,
            "isroot" => true,
            "topic" => $nodeDataArray[$root_id]['title'],
        ];

        // 决策树，先添加根节点
        $tree [] = $root;

        // 获取决策树子节点数据
        $this->_getSubTree($root_id, $tree, $nodeDataArray, $linkDataArray);

        $result = [];
        foreach ($tree as $val) {
            $risk = $report[$val['id']]['risk'] ?? '';
            if (!isset($report[$val['id']]['risk']) && isset($report[$root_id])) {
                continue;
            }
            $detail = $report[$val['id']]['detail'] ?? '';
            $value = isset($report[$val['id']]['value']) ? (is_array($report[$val['id']]['value']) ? json_encode($report[$val['id']]['value'], JSON_UNESCAPED_UNICODE) : $report[$val['id']]['value']) : '';
            $score = $score[$val['id']] ?? '';

            if ($root_id == $val['id']) {
                $value = $this->wrapStr($value);
            }
            $topic = "{$val['id']}:<br> risk: {$risk}<br>detail: {$detail}<br>value: {$value}<br>score: {$score}";

            $result[] = [
                'id' => $val['id'],
                'isroot' => $val['isroot'] ?? false,
                'parentid' => $val['parentid'] ?? '',
                'topic' => $topic
            ];
        }

        return $this->render('rule-report-view', array(
            'list' => $list,
            'tree' => $result,
        ));
    }

    private function wrapStr($value) {
        $value_info = explode(",", $value);
        if ($value_info <= 2) {
            return $value;
        }
        $len = count($value_info);
        $ret_value = "";
        for ($i = 0; $i < $len; $i++) {
            $ret_value .= $value_info[$i];
            if ($i < ($len - 1)) {
                $ret_value .= ",";
                if (($i+1) % 2 == 0) {
                    $ret_value .= "<br>";
                }
            }
        }
        return $ret_value;
    }

    protected function getFilter() {
        $condition = [];

        $search = $this->request->get();

        if (isset($search['_id']) && !empty($search['_id'])) {
            $condition['_id'] = intval($search['_id']);
        }

        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition['user_id'] = intval($search['user_id']);
        }

        if (isset($search['order_id']) && !empty($search['order_id'])) {
            $condition['order_id'] = intval($search['order_id']);
        }

        $timeType = 'created_at';
        if (isset($search['timeType']) && in_array($search['timeType'], ['created_at', 'updated_at'])) {
            $timeType = $search['timeType'];
        }
        if (isset($search['start_time']) && !empty($search['start_time'])) {
            $condition[$timeType]['$gte'] = strtotime($search['start_time']);
        }
        if (isset($search['end_time']) && !empty($search['end_time'])) {
            $condition[$timeType]['$lte'] = strtotime($search['end_time']);
        }

        return $condition;
    }
}
