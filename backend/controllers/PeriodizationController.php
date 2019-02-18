<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/3/31
 * Time: 16:01
 */

namespace backend\controllers;
use common\helpers\StringHelper;
use common\models\IndianaOrder;
use common\models\LoanPerson;
use common\models\LoanProject;
use common\models\LoanProtocol;
use common\models\LoanRecord;
use common\models\LoanRecordPeriod;
use common\models\LoanRepaymentPeriod;
use common\models\LoanAudit;
use common\models\LoanRepayment;
use common\models\LoanContract;
use common\models\User;
use common\models\UserDetail;
use common\models\UserAccount;
use common\models\UserAccountLog;
use backend\models\PhoneReviewLog;
use backend\models\LoanFkRecord;
use common\models\LoanTrial;
use common\models\LoanReview;
use common\models\Shop;
use common\service\AccountService;
use common\service\LoanService;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\redis\ActiveQuery;
use yii\web\NotFoundHttpException;
use common\helpers\MessageHelper;
use backend\helpers\loanrepayment\loanRepaymentFactory;
use yii\web\Response;
use common\helpers\TimeHelper;

class PeriodizationController extends  BaseController
{

    public function getFilter()
    {

        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $loan_person = LoanPerson::find()->where(['name' => $search['name']])->one();
                $condition .= " AND loan_person_id = " . intval($loan_person['id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $loan_person = LoanPerson::find()->where(['phone' => $search['phone']])->one();
                $condition .= " AND loan_person_id = " . intval($loan_person['id']);
            }
            if (isset($search['id_number']) && !empty($search['id_number'])) {
                $loan_person = LoanPerson::find()->where(['id_number' => $search['id_number']])->one();
                $condition .= " AND loan_person_id = " . intval($loan_person['id']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $loan_record_period = LoanRecordPeriod::find()->select(['id'])->where(['loan_project_id'=>18,'status'=>$search['status']])->asArray()->all();
                if(!empty($loan_record_period)){
                    foreach($loan_record_period as $v){
                        $status[] = $v['id'];
                    }
                    $status = implode(',',$status);
                }else{
                    $status = 0;
                }
                $condition .= " AND loan_record_period_id in ({$status})";
            }

            if (isset($search['begintime']) && !empty($search['begintime'])) {
                $condition .= " AND created_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])) {
                $condition .= " AND created_at < " . strtotime($search['endtime']);
            }
        }
        return $condition;
    }


    public function actionList(){
        $condition = $this->getFilter();
        $query = IndianaOrder::find()->where($condition)->andWhere(['type'=>IndianaOrder::TYPE_NORMAL])->orderBy([
            'id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $fqsc_status = LoanRecordPeriod::$fqsc_status_msg;

        $data_list = $query->with([
            'loanPerson' => function(Query $query) {
                $query->select(['id', 'name', 'phone','id_number','type']);
            },
            'indiana'=> function(Query $query) {
                $query->select(['id', 'installment_price','title']);
            },
            'loanPrecordPeriod' => function(Query $query) {
                $query->select(['id', 'product_type_name','amount','period','status']);
            },
            'user' => function(Query $query) {
                $query->select(['id', 'source']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('periodization-list', array(
            'data_list' => $data_list,
            'pages' => $pages,
            'loanService' => Yii::$container->get('loanService'),
            'fqsc_status' => $fqsc_status
        ));

    }


    private function _getData($indiana_order_id,$action='detail')
    {
        $id = $indiana_order_id;
        $indianaOrder = IndianaOrder::find()->where(['id' => $id])->with('indiana')->asArray()->one();
        $indianaOrder['created_at'] = date('Y-m-d H:i:s', $indianaOrder['created_at']);
        $indianaOrder['indiana']['installment_price'] = StringHelper::safeConvertIntToCent($indianaOrder['indiana']['installment_price']);

        //用户基本信息
        $userinfo = User::find()->where(['id' => $indianaOrder['uid']])->asArray()->one();
        $userinfo['source'] = isset(User::$source_list[$userinfo['source']]) ? User::$source_list[$userinfo['source']] : '--';
        $userinfo['sex'] = isset(User::$sexes[$userinfo['sex']]) ? User::$sexes[$userinfo['sex']] : '--' ;
        $userinfo['created_at'] = date('Y-m-d', $userinfo['created_at']);
        //用户详细信息
        $userdetail = UserDetail::find()->where(['id' => $indianaOrder['uid']])->asArray()->one();
        $userinfo['reg_client'] = $userdetail['reg_client_type'];
        $userinfo['reg_device'] = $userdetail['reg_device_name'];
        $userinfo['reg_app'] = $userdetail['reg_app_version'];
        $userinfo['reg_os'] = $userdetail['reg_os_version'];
        //用户征信信息
        $loan_person = LoanPerson::find()->where(['id' => $indianaOrder['loan_person_id']])->with('creditJxl')->with('creditZmop')->one();

        //备注信息
        $loanRecordPeriod = LoanRecordPeriod::find()->where(['id'=>$indianaOrder['loan_record_period_id']])->one();

        $loan_audit =  LoanAudit::findOne([$loanRecordPeriod->loan_audit_id]);

        $loan_repayment =  LoanRepayment::findOne([$loanRecordPeriod->loan_repayment_id]);
        $loan_repayment_period = LoanRepaymentPeriod::find()->where(['loan_record_id' => $loanRecordPeriod->id])->orderBy(['period' => SORT_ASC])->all();
        //合同信息
        $loanContract = LoanContract::find()->where(['indiana_order_id'=>$id])->asArray()->one();

        //用户账户信息
        $loanService = Yii::$container->get('loanService');
        $accountService = Yii::$container->get('accountService');
        $userAccount = UserAccount::find()->where(['user_id' => $userinfo['id']])->asArray()->one();
        $userAccount['net_assets'] = StringHelper::safeConvertIntToCent($accountService->getRealHoldAsset($userinfo['id']));
        $userAccount['duein_repay'] = StringHelper::safeConvertIntToCent($loanService->getRepayAmount($userinfo['id']));
        $userAccount['total_money'] = StringHelper::safeConvertIntToCent($userAccount['total_money']);
        $userAccount['duein_capital'] = StringHelper::safeConvertIntToCent($userAccount['duein_capital']);
        $userAccount['usable_money'] = StringHelper::safeConvertIntToCent($userAccount['usable_money']);
        $userAccount['withdrawing_money'] = StringHelper::safeConvertIntToCent($userAccount['withdrawing_money']);
        $userAccount['investing_money'] = StringHelper::safeConvertIntToCent($userAccount['investing_money']);
        $userAccount['kdb_total_money'] = StringHelper::safeConvertIntToCent($userAccount['kdb_total_money']);
        $userAccount['investing_money'] = StringHelper::safeConvertIntToCent($userAccount['investing_money']);

        //用户资金流水过滤
        $search = [];
        $condition = '1=1';
        if ($this->request->get('search_submit')) { // 过滤
            $search = $this->request->get();
            if (!empty($search['operation_type'])) {
                $condition .= " AND type = " . intval($search['operation_type']);
            }

            if (!empty($search['begintime'])) {
                $condition .= " AND created_at >= " . strtotime($search['begintime']);
                if (!empty($search['endtime'])) {
                    $condition .= " AND created_at <= " . strtotime($search['endtime']);
                }
            } else {
                if (!empty($search['endtime'])) {
                    $condition .= " AND created_at <= " . strtotime($search['endtime']);
                }
            }
        }
        //用户资金流水记录
        // 1. 查询 tb_user_account_log 表
        $query = UserAccountLog::find()->where(['user_id' => $userinfo['id']])->andWhere($condition)->orderBy('id desc');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 10;
        $results = $query->offset($pages->offset)->limit($pages->limit)->with([
            // 2. 通过 with("user") 关联用户表，获得投资者姓名
            'user' => function ($query) {
                $query->select([
                    'id',
                    'username',
                ]);
            }
        ])->asArray()->all();
        $creditList = [];
        if (!empty($results)) {
            foreach ($results as $value) {
                $stat = [];
                $stat['id'] = $value['id'];
                $stat['user_id'] = $value['user_id'];
                if (isset(UserAccount::$tradeTypes[$value['type']])) {
                    $stat['type'] = UserAccount::$tradeTypes[$value['type']];
                } else {
                    $stat['type'] = "未知类型";
                }
                $stat['operate_money'] = $value['operate_money'];
                $stat['total_money'] = $value['total_money'];
                $stat['usable_money'] = $value['usable_money'];
                $stat['investing_money'] = $value['investing_money'];
                $stat['withdrawing_money'] = $value['withdrawing_money'];
                $stat['duein_capital'] = $value['duein_capital'];
                $stat['duein_profits'] = $value['duein_profits'];
                $stat['kdb_total_money'] = $value['kdb_total_money'];
                $stat['created_at'] = $value['created_at'];

                $user = $value['user'];
                $userSummary = [];
                $userSummary['id'] = $user['id'];
                $userSummary['username'] = $user['username'];
                $stat['user'] = $userSummary;
                $creditList[] = $stat;
            }
        }

        //电话审核记录
        $phoneReviewLog = PhoneReviewLog::find()->where(['loan_record_period_id'=>$indianaOrder['loan_record_period_id']])->asArray()->all();
        if(!empty($phoneReviewLog)){
            foreach($phoneReviewLog as &$v){
                $v['time'] = date('Y-m-d H:i:s',$v['time']);
                $v['created_at'] = date('Y-m-d H:i:s',$v['created_at']);
            }
        }

        $type = $this->request->get('type', 'baseinfo');
        if(isset($_GET['page'])){
            $type = 'accountlog';
        }
        switch ($action) {
            case 'detail':
                return $this->render('periodization-detail', array(
                    'indiana_order' => $indianaOrder,
                    'user' => $userinfo,
                    'user_account' => $userAccount,
                    'type' => $type,
                    'user_account_log_list' => $creditList,
                    'pages' => $pages,
                    'loan_person' => $loan_person,
                    'phoneReviewLog' => $phoneReviewLog,
                    'loanRecordPeriod' => $loanRecordPeriod,
                    'loanContract' => $loanContract,
                    'action' => 'detail',
                    'loan_audit' => $loan_audit,
                    'loan_repayment' => $loan_repayment,
                    'loan_repayment_period' => $loan_repayment_period
                ));
                break;
            case 'review':
                return $this->render('periodization-review', array(
                    'indiana_order' => $indianaOrder,
                    'user' => $userinfo,
                    'user_account' => $userAccount,
                    'type' => $type,
                    'user_account_log_list' => $creditList,
                    'pages' => $pages,
                    'loan_person' => $loan_person,
                    'phoneReviewLog'=> $phoneReviewLog,
                    'action' => 'review',
                ));
                break;
            case 'check':
                return $this->render('periodization-review', array(
                    'indiana_order' => $indianaOrder,
                    'user' => $userinfo,
                    'user_account' => $userAccount,
                    'type' => $type,
                    'user_account_log_list' => $creditList,
                    'pages' => $pages,
                    'loan_person' => $loan_person,
                    'phoneReviewLog'=> $phoneReviewLog,
                    'action' => 'check',
                ));
                break;
            case 'shipping':
                return $this->render('periodization-shipping', array(
                    'indiana_order' => $indianaOrder,
                    'user' => $userinfo,
                    'user_account' => $userAccount,
                    'type' => $type,
                    'user_account_log_list' => $creditList,
                    'pages' => $pages,
                    'loan_person' => $loan_person,
                    'phoneReviewLog' => $phoneReviewLog,
                    'loanRecordPeriod' => $loanRecordPeriod,
                    'loanContract' => $loanContract,
                    'action' => 'shipping',
                    'loan_audit' => $loan_audit,
                    'loan_repayment' => $loan_repayment
                ));
                break;
            default:
                break;
        }
    }
    public function actionPeriodizationDetail($id){
        return $this->_getData($id,'detail');
    }

    public function actionPeriodizationReview($id){
        return $this->_getData($id,'review');
    }

    public function actionPeriodizationCheck($id){
        return $this->_getData($id,'check');
    }

    public function actionShipping($id){
        return $this->_getData($id,'shipping');
    }

    public function actionReviewReject()
    {
        $this->response->format = Response::FORMAT_JSON;
        $remark = Yii::$app->request->post("remark");
        $action = Yii::$app->request->post("action");
        if(empty($remark)) {
            return [
                'code' => -1,
                'message' => '请填写备注'
            ];
        }
        $loan_record_period_id = Yii::$app->request->post("loan_record_period_id");
        if(empty($loan_record_period_id)) {
            return [
                'code' => -1,
                'message' => 'id异常'
            ];
        }
        $model = LoanRecordPeriod::findOne(intval($loan_record_period_id));
        $model->status =  LoanRecordPeriod::STATUS_APPLY_CAR_FALSE;
        $model->remark = $remark;
        if($model->save()) {
            return [
                'code' => 0,
                'message' => 'success'
            ];
        } else {
            return [
                'code' => -1,
                'message' => '保存失败'
            ];
        }
    }

    public function actionCheckReject()
    {
        $this->response->format = Response::FORMAT_JSON;
        $remark = Yii::$app->request->post("remark");
        if(empty($remark)) {
            return [
                'code' => -1,
                'message' => '请填写备注'
            ];
        }
        $loan_record_period_id = Yii::$app->request->post("loan_record_period_id");
        if(empty($loan_record_period_id)) {
            return [
                'code' => -1,
                'message' => 'id异常'
            ];
        }
        $model = LoanRecordPeriod::findOne(intval($loan_record_period_id));
        $model->status = LoanRecordPeriod::STATUS_APPLY_MONEY_FALSE;
        $model->remark = $remark;
        if($model->save()) {
            return [
                'code' => 0,
                'message' => 'success'
            ];
        } else {
            return [
                'code' => -1,
                'message' => '保存失败'
            ];
        }
    }

    public function actionCheckPass()
    {
        $this->response->format = Response::FORMAT_JSON;
        $remark = Yii::$app->request->post("remark");
        if(empty($remark)) {
            return [
                'code' => -1,
                'message' => '请填写备注'
            ];
        }
        $loan_record_period_id = Yii::$app->request->post("loan_record_period_id");
        if(empty($loan_record_period_id)) {
            return [
                'code' => -2,
                'message' => 'id异常'
            ];
        }

        $model = LoanRecordPeriod::findOne(intval($loan_record_period_id));
        $loanService = Yii::$container->get('loanService');
        //判断净资产是否满足申请条件
        if(!$loanService->verifyReqChance($model->user_id, $model->amount)) {
            return [
                'code' => -3,
                'message' => '该用户当前净资产不符合申请条件'
            ];
        }

        $model->status = LoanRecordPeriod::STATUS_APPLY_MONEY_APPLY;
        $model->remark = $remark;
        if($model->save()) {
            return [
                'code' => 0,
                'message' => 'success'
            ];
        } else {
            return [
                'code' => -1,
                'message' => '保存失败'
            ];
        }
    }

    public function actionContractFill($id,$loan_record_period_id,$remark){
        $model = new LoanContract();
        $loan_record_period = LoanRecordPeriod::find()->select(['period','amount','product_type_name','user_id'])->where(['id'=>$loan_record_period_id])->asArray()->one();
        $info['period'] = $loan_record_period['period'];
        $info['single_repay_money'] = round($loan_record_period['amount'] / 100 / $info['period'],2);
        $indiana_order = IndianaOrder::find()->where(['loan_record_period_id'=>$loan_record_period_id])->asArray()->one();
        $user = User::find()->select(['username','realname','id_card'])->where(['id'=>$loan_record_period['user_id']])->asArray()->one();
        if($this->request->isPost){
            $type = $this->request->post('LoanContract')['type'];
            if(! isset(LoanContract::$contract_template_map[$type])){
                return $this->redirectMessage('请选择合同类型', self::MSG_ERROR);
            }
            $template_path = Yii::$app->basePath.'/../frontend/web/attachment/pdf/'.LoanContract::$contract_template_map[$type];
            $product_model = $this->request->post('product_model');
            if(empty($product_model)){
                return $this->redirectMessage('请填写产品型号', self::MSG_ERROR);
            }
            $service_charge = $this->request->post('service_charge');
            $single_repay_money = $this->request->post('single_repay_money');
            if(empty($single_repay_money)){
                return $this->redirectMessage('请填写分期还款金额', self::MSG_ERROR);
            }
            $first_repay_date = $this->request->post('first_repay_date');
            if(empty($first_repay_date)){
                return $this->redirectMessage('请选择首次还款时间', self::MSG_ERROR);
            }
            $first_repay_date = date('Y-m-d',strtotime($first_repay_date));
            $ship_address = $this->request->post('ship_address');
            if(empty($ship_address)){
                return $this->redirectMessage('请填写发货地址', self::MSG_ERROR);
            }
            $file = file_get_contents($template_path);
            $repay_date =  date('d',strtotime($first_repay_date));
            if($repay_date > 28){
                $repay_date = 28;
            }
            $repay_date = '每月'.$repay_date.'日';
            $color = $indiana_order['installment_option'];
            $form_list = [
                'remark' => $remark,
                'indiana_order_id' => $id,
                'loan_record_period_id' => $loan_record_period_id,
                'type' => $product_model,
                'template_type' => $type,
                'amount' => StringHelper::safeConvertIntToCent($loan_record_period['amount']).' 元',
                'use' => '用于购买'. $loan_record_period['product_type_name'],
                'color' => $color,
                'address' => $ship_address,
                'fee' => $service_charge.'%',
                'repayment_type' => '按月分期还款',
                'period' => $info['period'].'期',
                'first_repay_date' => date('Y 年 m 月 d 日',strtotime($first_repay_date)),
                'repay_date' => $repay_date,
                'period_repay_money' => $single_repay_money.' 元',
                'company_name' => '上海鱼耀金融信息服务有限公司',
                'customer_realname' => $user['realname'],
                'idcard' => $user['id_card'],
                'username' => $user['username']
            ];
            $map = [
                '/\\r\\n/' => '<br/>',
                '/\{\$amount\}/' => $form_list['amount'],
                '/\{\$use\}/' => $form_list['use'],
                '/\{\$type\}/' => $form_list['type'],
                '/\{\$color\}/' => $form_list['color'],
                '/\{\$address\}/' => $form_list['address'],
                '/\{\$fee\}/' => $form_list['fee'],
                '/\{\$repayment_type\}/'=> $form_list['repayment_type'],
                '/\{\$period\}/'=>$form_list['period'],
                '/\{\$first_repay_date\}/'=> $form_list['first_repay_date'],
                '/\{\$repay_date\}/' => $form_list['repay_date'],
                '/\{\$period_repay_money\}/' =>$form_list['period_repay_money'],
                '/\{\$company_name\}/' =>$form_list['company_name'],
                '/\{\$customer_realname\}/' => $form_list['customer_realname'],
                '/\{\$idcard\}/' => $form_list['idcard'],
                '/\{\$username\}/' => $form_list['username'],
            ];

            $content = preg_replace(array_keys($map),array_values($map),$file);
            return $this->render('contract-view',[
                'form_list' => $form_list,
                'content' => $content,
            ]);

        }

        return $this->render('contract-fill',[
            'model' => $model,
            'info' => $info,
            'remark'=>$remark
        ]);
    }

    public function actionContractCreate()
    {
        $loan_record_period_id = $this->request->post('loan_record_period_id');
        if (empty($loan_record_period_id)) {
            return $this->redirectMessage('借款记录不存在', self::MSG_ERROR);
        }
        $loan_record_period = LoanRecordPeriod::findOne($loan_record_period_id);
        $uid = $loan_record_period['user_id'];
        $periodization_signed = LoanContract::find()->where(['loan_record_period_id' => $loan_record_period_id])->one();
        if (is_null($periodization_signed)) {
            $periodization_signed = new LoanContract();
        }
        $template_type = $this->request->post('template_type');
        if (!isset(LoanContract::$contract_template_map[$template_type])) {
            return $this->redirectMessage('模板不存在', self::MSG_ERROR, Url::toRoute(['periodization/periodization-list']));
        }
        $remark = $this->request->post('remark');
        if(empty($remark)){
            return $this->redirectMessage('备注不能为空', self::MSG_ERROR, Url::toRoute(['periodization/periodization-list']));
        }
        $indiana_order_id = intval($this->request->post('indiana_order_id'));
        $content = $this->request->post('content');
        $content = json_encode($content);
        $periodization_signed->loan_record_period_id = $loan_record_period_id;
        $periodization_signed->indiana_order_id = $indiana_order_id;
        $periodization_signed->type = $template_type;
        $periodization_signed->uid = $uid;
        $periodization_signed->contract_content = $content;
        $periodization_signed->status = 0;
        $periodization_signed->creater = Yii::$app->user->identity->username;
        $ret = $periodization_signed->save();
        if($ret){
            $loan_record_period->remark = $remark;
            $loan_record_period->status = LoanRecordPeriod::STATUS_APPLY_SIGN;
            $result = $loan_record_period->save();
        }else{
            return $this->redirectMessage('审批失败', self::MSG_ERROR, Url::toRoute(['periodization/periodization-list']));
        }
        if ($result) {
            return $this->redirectMessage('审批成功', self::MSG_SUCCESS, Url::toRoute(['periodization/periodization-list']));
        } else {
            return $this->redirectMessage('审批失败', self::MSG_ERROR, Url::toRoute(['periodization/periodization-list']));
        }
    }

    public function actionContractCat($id){
        $loanContract = LoanContract::find()->where(['indiana_order_id'=>intval($id)])->asArray()->one();
        $status = $loanContract['status'];
        if(1 == $status){
            $url = 'http://res.koudailc.com/kdfq_shop/'.$loanContract['contract_url'];
            return $this->redirect($url);
        }
        $type = $loanContract['type'];
        $template_path = Yii::$app->basePath.'/../frontend/web/attachment/pdf/'.LoanContract::$contract_template_map[$type];
        $file = file_get_contents($template_path);
        $content = json_decode($loanContract['contract_content']);
        foreach($content as $k=>$v){
            $preg = '/\{\\$'. $k . '\\}/';
            $file = preg_replace($preg,$v,$file);
        }
        $file = preg_replace('/\\r\\n/','<br/>',$file);
        echo $file;

    }

    public function actionPhoneReviewLogAdd($id){
        if(!TimeHelper::identifyTime(5, '_add_reviewlog')) {
            return $this->redirectMessage('您好，两次提交中间不能小于五秒！', self::MSG_ERROR);
        }
        $indianaOrder = IndianaOrder::findOne(intval($id));
        $loan_record_period_id = $indianaOrder->loan_record_period_id;
        $content = $this->request->post('phone-review-log');
        if(empty($content)){
            return $this->redirectMessage('审核内容不能为空', self::MSG_ERROR);
        }
        $time = $this->request->post('phone-review-time');
        if(empty($time)){
            return $this->redirectMessage('审核时间不能为空', self::MSG_ERROR);
        }
        $time = strtotime($time);
        $type = $this->request->post('type');
        if(empty($type)) {
            return $this->redirectMessage("联系类型不能为空", self::MSG_ERROR);
        }
        $phoneReviewLog = new PhoneReviewLog();
        $phoneReviewLog->content = $content;
        $phoneReviewLog->loan_record_period_id = $loan_record_period_id;
        $phoneReviewLog->auditor = Yii::$app->user->identity->username;
        $phoneReviewLog->time = $time;
        $phoneReviewLog->type = $type;
        if(PhoneReviewLog::TYPE_MESSAGE == $type) {
            $loan_record_period = LoanRecordPeriod::find()->where(["id" => $loan_record_period_id])->select(['user_id'])->one();
            $phone = User::find()->where(["id" => $loan_record_period['user_id']])->select(['phone'])->one();
            MessageHelper::sendSMS($phone['phone'], $content);
        }
        if($phoneReviewLog->save()){
            $this->redirect(['periodization/periodization-review','id'=>$id]);
        } else {
            return $this->redirectMessage("添加记录失败", self::MSG_ERROR);
        }


    }

    public function actionLoanCreditBackend(){
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            $loan_record_period_id = $search['loan_record_period_id'];
            $type = $search['type'];
            $status = $search['status'];
            $ems_order = $search['ems_order'];
            try {
                $loan_record_period = LoanRecordPeriod::findOne($loan_record_period_id);
                if(empty($loan_record_period)){
                    throw new Exception("不存在此借款记录！");
                }
                $repay_operation = 1;//还款方式
                $repayment_type = $loan_record_period->repay_type;//还款类型
                switch($type){
                    case "credit_backend":
                        if(!in_array($loan_record_period->status, [LoanRecordPeriod::STATUS_APPLY_MONEY_APPLY])){
                            throw new Exception("状态不符合放款要求的状态！");
                        }

                        if($status == LoanRecordPeriod::STATUS_APPLY_REPAYING){
                            $credit_repayment_time = strtotime($search['credit_repayment_time']);//放款时间
                            $sign_repayment_time = strtotime($search['sign_repayment_time']);//签约时间
                            $repayment_start_time = strtotime($search['repayment_start_time']);//首次还款时间
                            $period = $search['period'];//期限
                            $repayment_amount = bcmul($search['repayment_amount']  , 100);
                            if(empty($credit_repayment_time) || empty($sign_repayment_time)  || empty($period) || empty($repayment_amount)){
                                throw new Exception("参数不能为空！");
                            }
                            try{
                                $loan_record_period->credit_amount = $repayment_amount;//记录实际放款金额
                                $loan_record_period->save();
                                $origin_loan_repayment = LoanRepayment::findOne(['loan_record_id' => $loan_record_period_id]);
                                $origin_loan_repayment_period = LoanRepaymentPeriod::findOne(['loan_record_id' => $loan_record_period_id]);
                                if(!empty($origin_loan_repayment) || !empty($origin_loan_repayment_period)){
                                    throw new \Exception('还款表或者分期还款记录表中已存在此订单表记录，不能重复放款');
                                }
                                $factory = new loanRepaymentFactory();
                                $repay_obj = $factory->route($repayment_type);
                                $repay_obj->setProperty($repayment_type, $loan_record_period, $period, $repayment_amount, $credit_repayment_time, $sign_repayment_time, $repayment_start_time, $repay_operation);
                                $repay_obj->insertData();
                            }catch (Exception $w){
                                throw new Exception($w->getMessage());
                            }
                        }
                        break;
                    default:
                        throw new Exception("非法操作！");
                }
                $loan_record_period->status = $status;
                if(!$loan_record_period->save()){
                    throw new Exception("更新订单表状态失败！");
                }
                //记录放款情况
                $check = LoanFkRecord::find()->where("loan_record_id =".$loan_record_period->id)->one();

                if(empty($check)) {
                    $loan_fk = new LoanFkRecord();
                    $loan_fk->loan_person_id = $loan_record_period->loan_person_id;
                    $loan_fk->loan_record_id = $loan_record_period->id;
                    $loan_fk->status = 1;
                    $loan_fk->repay_type = $repayment_type;
                    $loan_fk->apr = $loan_record_period->apr;
                    $loan_fk->fee_money = $loan_record_period->fee_amount;
                    $loan_fk->urgent_money = $loan_record_period->urgent_amount;
                    $loan_fk->repay_operation = $repay_operation;
                    $loan_fk->credit_repayment_time = strtotime($search['credit_repayment_time']);
                    $loan_fk->sign_repayment_time = strtotime($search['sign_repayment_time']);
                    $loan_fk->first_repay_time = strtotime($search['repayment_start_time']);
                    $loan_fk->fk_money = $loan_record_period->credit_amount;
                    $loan_fk->period = $search['period'];
                    $loan_fk->audit_person = Yii::$app->user->identity->username;
                    if (!$loan_fk->save()) {
                        throw new Exception("更新放款表失败！");
                    }
                }
                //发送提醒短信
                $user = User::find()->where(["id" => $loan_record_period->user_id])->one();
                $content = "尊敬的".$user->realname.($user->sex == 1 ? "先生" : "女士")."，您在口袋理财分期购买的".$loan_record_period->product_type_name."已发货，".$ems_order."，请注意查收，谢谢！";
                MessageHelper::sendSMS($user->phone, $content);
                $phoneReviewLog = new PhoneReviewLog();
                $phoneReviewLog->content = $content;
                $phoneReviewLog->loan_record_period_id = $loan_record_period_id;
                $phoneReviewLog->auditor = "发货";
                $phoneReviewLog->time = time();
                $phoneReviewLog->type = PhoneReviewLog::TYPE_MESSAGE;
                $phoneReviewLog->save();

                $result = "提交成功";
            }catch (Exception $e){
                $result = $e->getMessage()." 当前为“".LoanRecordPeriod::$status[$loan_record_period->status]."”状态";
            }
            return $this->redirectMessage($result, self::MSG_SUCCESS);

        }
    }
}