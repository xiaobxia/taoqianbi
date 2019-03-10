<?php
/**
 * Created by phpDesigner.
 * User: user
 * Date: 2016/10/26
 * Time: 10:53
 */
namespace backend\controllers;
use common\base\LogChannel;
use common\models\AccumulationFund;
use common\models\FinancialLoanRecord;
use backend\models\AdminUser;
use common\models\OrderManualCancelLog;
use common\models\UserOrderLoanCheckLog;
use common\services\UserService;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use common\models\LoanPerson;
use common\models\UserCreditTotal;
use common\models\UserCreditReviewLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserDetail;
use common\models\UserCreditMoneyLog;
use common\models\CreditJxlQueue;
use common\models\CardInfo;
use common\models\UserOperateApplication;
use common\helpers\Util;
use common\models\UserVerification;
use common\models\UserProofMateria;
use common\models\UserFeedback;
use common\helpers\MessageHelper;
use common\models\fund\OrderFundInfo;
use common\models\fund\LoanFund;
use common\models\LoanBlackList;
use common\helpers\ArrayHelper;
use common\models\UserLoginUploadLog;
use common\models\UserCreditDetail;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\LoanOutDel;
use common\models\Channel;

class CustomManagementController extends  BaseController{


    /**
     * @name 客服管理-用户管理-用户列表/actionYgdLoanPersonList
    **/
    public function actionYgdLoanPersonList(){
        new LoanPerson();
        $reg_app_market_detail='-';
        $ygd_loan_person=[];
        $details = [];
        if($this->request->get('search_submit')) {        //过滤
            $condition = "1 = 1 and ".LoanPerson::tableName().".status >= ".LoanPerson::PERSON_STATUS_DELETE;
            $search = $this->request->get();
            if(!empty($search['id'])||!empty($search['name'])||!empty($search['phone'])||!empty($search['id_number'])){
                if(!empty($search['id'])) {
                    $condition .= " AND ".LoanPerson::tableName().".id = ".intval($search['id']);
                }
                if(!empty($search['name'])) {
                    $condition .= " AND ".LoanPerson::tableName().".name like'%".$search['name']."%'";
                }
                if(!empty($search['phone'])) {
                    $condition .= " AND ".LoanPerson::tableName().".phone = '".str_replace(' ','',$search['phone'])."'";
                }
                if(!empty($search['id_number'])) {
                    $condition .= " AND ".LoanPerson::tableName().".id_number = \"".$search['id_number']."\"";
                }

                $query = LoanPerson::find()->where(" 1 =1 ")->andWhere($condition)->select([LoanPerson::tableName().'.*',LoanBlackList::tableName().'.black_status'])
                    ->leftJoin(LoanBlackList::tableName(),LoanPerson::tableName().'.id = '.LoanBlackList::tableName().'.user_id')
                    ->asArray();
                $ret = $query->all(Yii::$app->get('db_kdkj_rd'));

                foreach ($ret as &$v) {
                    // 公积金情况
                    $gjj_info = AccumulationFund::find()->where(['user_id' => intval($v['id'])])->asArray()->one();
                    $v['gjj_status'] = 0;
                    $v['gjj_remark'] = '';
                    if ($gjj_info) {
                        $v['gjj_status'] = $gjj_info['status'];
                        $v['gjj_remark'] = $gjj_info['message'];
                    }

                    $verify = UserVerification::find()->where(['user_id' => $v['id']])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
                    $v['real_bind_bank_card_status'] = $verify['real_bind_bank_card_status'];
                    $v['real_zmxy_status'] = $verify['real_zmxy_status'];
                    // 运营商认证查询
                    $tb_credit_jxl_queue = CreditJxlQueue::find()->where(['user_id' => $v['id']])->asArray()->one();
                    if (!$tb_credit_jxl_queue) {
                        $v['yys_status'] = '未认证';
                    } else {
                        if ($tb_credit_jxl_queue['current_status'] == 6) {
                            $v['yys_status'] = '认证成功';
                        } else {
                            $v['yys_status'] = '认证失败';
                        }
                    }

                    // 用户授信情况
                    $credit_info = UserCreditDetail::find()->where(" 1 =1 ")->andWhere([UserCreditDetail::tableName().'.user_id' => $v['id']])->select([UserCreditDetail::tableName().'.*',UserCreditTotal::tableName().'.amount'])
                        ->leftJoin(UserCreditTotal::tableName(),UserCreditDetail::tableName().'.user_id = '.UserCreditTotal::tableName().'.user_id')
                        ->asArray()->one();
                    $v['credit_status'] = UserCreditDetail::$status[(int)$credit_info['credit_status']];
                    $v['credit_amount'] = $credit_info['amount'];

                    // 下单查询
                    $order = UserLoanOrderRepayment::find()->select(UserLoanOrderRepayment::tableName().'.is_overdue')->where(['user_id' => $v['id']])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                    $overdue = 0;
                    if ($order) {
                        foreach ($order as $_order) {
                            if ($_order['is_overdue'] == 1) {
                                $overdue ++;
                            }
                        }
                    }

                    // 联系人查询
                    $loan_person = LoanPerson::find()->where(['id' => intval($v['id'])])->with('creditJxl')->with('creditZmop')->one(Yii::$app->get('db_kdkj_rd'));

                    $info = Yii::$container->get('loanPersonInfoService')->getLoanPersonInfo( $loan_person );
                    $v['information'] = [];
                    if ($info['contact']) {
                        $v['information'] = $info['contact'];
                    }
                    $v['overdue_num'] = $overdue;
                    $v['order_num'] = count($order);
                    $address = $info['person_relation']['address_distinct']." ".$info['person_relation']['address'];
                    $img_res = false;
                    $type1 = false;
                    $type2 = false;
                    $type3 = false;
                    foreach ($info['proof_image'] as $_img) {
                        if ($_img['type'] == UserProofMateria::TYPE_FACE_RECOGNITION) {
                            $type1 = true;
                        }
                        if ($_img['type'] == UserProofMateria::TYPE_ID_CAR_Z) {
                            $type2 = true;
                        }
                        if ($_img['type'] == UserProofMateria::TYPE_ID_CAR_F) {
                            $type3 = true;
                        }
                    }
                    if ($type1 && $type2 && $type3) {
                        $img_res = true;
                    }

                    // 判断基础信息是否填写完整
                    if ($info['person_relation']['degrees'] && $info['loanPerson']['name'] && $info['loanPerson']['id_number'] && $address && $img_res) {
                        $v['base_info'] = '是';
                    } else {
                        $v['base_info'] = '否';
                    }

                    // 判断通讯录是否上传
//                    $contacts = UserMobileContactsMongo::find()->where(['=','user_id',$v['id']])->all();
//
//                    if (!$contacts) {
//                        $contacts = UserMobileContacts::find()->where(['user_id' => $v['id']])->asArray()->all();
//                    }
//
//                    if ($contacts) {
//                        $v['contacts'] = '是';
//                    } else {
//                        $v['contacts'] = '否';
//                    }
                    $v['contacts'] = 'TODO';
                    // 登录日志
                    $query = UserLoginUploadLog::find()->where(['user_id' => $v['id']])->orderBy([
                        'id' => SORT_DESC,
                    ]);

                    $countQuery = clone $query;
                    $user_login_upload_log = $query->with([
                        'loanPerson' => function(Query $query) {
                            $query->select(['id', 'name', 'phone']);
                        },
                    ])->limit(5)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                    $v['user_login_upload_log'] = $user_login_upload_log;
                    $user_arr[$v['id']]=$v['source_id'];
                }

                if($ret){
                    $ygd_loan_person=$ret;
                    $uids = ArrayHelper::getColumn($ret, 'id');
                    $_res = UserDetail::find()
                        ->select(['user_id', 'company_name', 'reg_app_market', 'reg_device_name','reg_client_type' ])
                        ->where(['user_id' => $uids])
                        ->asArray()->all();
                    foreach($_res as $_row) {
                        if(isset($user_arr[$_row['user_id']])&&$user_arr[$_row['user_id']]==LoanPerson::PERSON_SOURCE_MOBILE_CREDIT) {
                            if (strstr($_row['reg_app_market'], '_')) {
                                $arr = explode('_', $_row['reg_app_market']);

                                if (count($arr) == 2 && $arr[0] == 'xybt') {
                                    $market = $arr[1];
                                }
                                if (count($arr) == 2 && $arr[0] != 'xybt') {
                                    $market = $_row['reg_app_market'];
                                }
                                if (count($arr) == 3 && $arr[0] == 'xybt') {
                                    $market = $arr[1] . '_' . $arr[2];
                                }
                                if (count($arr) == 3 && $arr[0] != 'xybt') {
                                    $market = $_row['reg_app_market'];
                                }
                            } else {
                                $market = $_row['reg_app_market'];
                            }

                            $channel = Channel::find()->where(['appMarket' => $market])->asArray()->One(\yii::$app->db_kdkj_rd);
                            if ($channel) {
                                new LoanPerson();
                                $reg_app_market_detail = LoanPerson::$source_app[$channel['source_str']];
                            } else {
                                $reg_app_market_detail = '-';
                            }
                        }
                        $details[$_row['user_id']] = [
                            'reg_app_market' => $_row['reg_app_market'],
                            'reg_app_market_detail' => $reg_app_market_detail,
                            'company_name' => $_row['company_name'],
                            'reg_device_name' => $_row['reg_device_name'],
                            'reg_client_type' => $_row['reg_client_type'],
                        ];
                    }
                }
            }
        }
        return $this->render('ygd-loan-person-list', array(
            'loan_person' => $ygd_loan_person,
            'details' => $details
        ));
    }
    /**
     * @return string
     * @name 客服管理-额度管理-用户额度列表/actionLoanList
     */
    public function actionLoanList()
    {
        $loan_collection_list=[];
        $condition = '1 = 1 ';
        if ($this->request->get('search_submit')) {
            $search = $this->request->get();
            //搜索条件不为空
            if((isset($search['id']) && !empty($search['id']))||(isset($search['phone']) && !empty($search['phone']))||(isset($search['name']) && !empty($search['name']))){
                if (isset($search['id']) && !empty($search['id'])) {
                    $condition .= " AND  lp.id = " . intval($search['id']);
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND  lp.phone = '" .str_replace(' ','',$search['phone'])."'";
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND  lp.name like '%". $search['name'] ."%'";
                }
                $query = LoanPerson::find()->from(LoanPerson::tableName().'  lp')->leftJoin(UserCreditTotal::tableName().' as ut','lp.id=ut.user_id')->where($condition);
                $data= $query -> select(['lp.id','lp.phone','lp.name','ut.user_id','ut.amount','ut.used_amount','ut.locked_amount','ut.pocket_apr','ut.house_apr','ut.installment_apr'])->all(Yii::$app->get('db_kdkj_rd'));
                //echo $query -> select(['lp.id','lp.phone','lp.name','ut.user_id','ut.amount','ut.used_amount','ut.locked_amount','ut.pocket_apr','ut.house_apr','ut.installment_apr'])
                //->createCommand()->getRawSql();die;
                if($data)
                    $loan_collection_list=$data;
                }
        }
        return $this->render('loan-list', array(
            'loan_collection_list' => $loan_collection_list
        ));
    }
     /**
     * @return string
     * @name 客服管理-额度管理-信用额度调整流水/actionCreditModifyLog
     */
    public function actionCreditModifyLog()
    {
        $list=[];
        $pages = new Pagination();
        $condition = '1 = 1 and a.type = '.UserCreditReviewLog::TYPE_CREDIT_TOTAL_AMOUNT.' and a.status ='.UserCreditReviewLog::STATUS_PASS;
        $search = $this->request->get();
        unset($search['r']);
        if(!empty($search)){//当未点击过滤时，不显示数据；
            //  搜索条件不为空时，取值
            if((isset($search['user_id']) && !empty($search['user_id']))||(isset($search['phone']) && !empty($search['phone']))||(isset($search['name']) && !empty($search['name']))){
                if (isset($search['user_id']) && !empty($search['user_id'])) {
                    $condition .= " AND a.user_id = " . intval($search['user_id']);
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND b.phone = '" .str_replace(' ','',$search['phone'])."'";
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND b.name like '%" . $search['name']."%'";
                }
                $query = UserCreditReviewLog::find()->from(UserCreditReviewLog::tableName().'as a')->leftJoin(LoanPerson::tableName().'as b',' a.user_id = b.id')->where($condition)->select('a.*,b.name,b.phone')->orderBy(['a.id' => SORT_DESC]);
                $countQuery = clone $query;
                $db = Yii::$app->get('db_kdkj_rd');
                if($this->request->get('cache')==1) {
                    $count = $countQuery->count('*', $db);
                } else {
                    $count = $db->cache(function ($db) use ($countQuery) {
                        return $countQuery->count('*', $db);
                    }, 3600);
                }
                $pages->totalCount=$count;
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
                if($ret) $list=$ret;
            }
        }
        return $this->render('credit-modify-log', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }
    /**
     * @return string
     * @name 客服管理-用户管理-银行卡列表/actionCardList
    */
    public function actionCardList()
    {
        $condition = '1 = 1 ';
        $search = $this->request->get();
        //unset($search['r']);
        $info=[];
        $pages = new Pagination();
        //if(!empty($search)){
            if((isset($search['user_id']) && !empty($search['user_id']))||(isset($search['bank_name']) && !empty($search['bank_name']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['user_id']) && !empty($search['user_id'])) {
                    $condition .= " AND user_id = " . intval($search['user_id']);
                }
                /*if (isset($search['bank_name']) && !empty($search['bank_name'])) {
                    $condition .= " AND bank_name = '" . $search['bank_name']."'";
                }*/
                if (isset($search['name']) && !empty($search['name'])) {
                    $person = LoanPerson::find()->where(["name" => $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                    if(empty($person)) {
                        $condition .= " AND user_id = 0";
                    } else {
                        $condition .= " AND user_id = " . $person['id'];
                    }
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND phone = '" .str_replace(' ','',$search['phone'])."'";
                }
                $query = CardInfo::find()->where($condition)->orderBy("id desc");
                $countQuery = clone $query;
                //$pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
                $db = Yii::$app->get('db_kdkj_rd');
                if($this->request->get('cache')==1) {
                    $count = $countQuery->count('*', $db);
                } else {
                    $count = $db->cache(function ($db) use ($countQuery) {
                        return $countQuery->count('*', $db);
                    }, 3600);
                }
                $pages->totalCount=$count;
                $pages->pageSize = 15;
                $info = $query->with([
                    'loanPerson' => function(Query $query) {
                        $query->select(['id', 'name']);
                    },
                ])->offset($pages->offset)->limit($pages->limit)->all($db);
            }
            //echo $query->createCommand()->getRawSql();
        //}
        return $this->render('bank-card-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }
    /**
     * @return string
     * @name 客服管理-借款管理-用户借款管理-借款列表/actionPocketList
    */
    public function actionPocketList(){
        $data=[];
        $pages = new Pagination();
        $search = $this->request->get();
        $order_ids = [];
        $status_data = [];
        //unset($search['r']);
        //if(!empty($search)){
            if((isset($search['id']) && !empty($search['id']))||(isset($search['uid']) && !empty($search['uid']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                $condition = '1 = 1 and a.order_type='.UserLoanOrder::LOAN_TYPE_LQD;
                if (isset($search['id']) && !empty($search['id'])) {
                    $condition .= " AND a.id = " . intval($search['id']);
                }
                if (isset($search['uid']) && !empty($search['uid'])) {
                    $condition .= " AND a.user_id = " . intval($search['uid']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND b.name like '%" . $search['name']."%'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND b.phone = '".str_replace(' ','',$search['phone'])."'";
                }
                $query = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as a ')->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')->leftJoin(UserDetail::tableName().' as c',' a.user_id = c.user_id')->where($condition)->select('a.*,b.name,b.id_number,b.phone,b.customer_type,b.source_id,c.company_name')->orderBy(['a.id'=>SORT_DESC]);
                $countQuery = clone $query;
                $db = Yii::$app->get('db_kdkj_rd');
                if($this->request->get('cache')==1) {
                    $count = $countQuery->count('*', $db);
                } else {
                    $count = $db->cache(function ($db) use ($countQuery) {
                        return $countQuery->count('*', $db);
                    }, 3600);
                }
                $pages ->totalCount=$count;
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
                if($ret) $data=$ret;
                foreach($data as $item){
                    $order_ids[] = $item['id'];
                }
                $user_order_loan_check_log = UserOrderLoanCheckLog::find()->where(['order_id'=>$order_ids,'before_status'=>0,'after_status'=>0])->asArray()->all($db);
                $check_data = [];
                foreach($user_order_loan_check_log as $item){
                    $check_data[$item['order_id']] = $item['order_id'];
                }
                foreach($data as $item){
                    $status = $item['status'];
                    if(UserLoanOrder::STATUS_CHECK == $status){
                        if(isset($check_data[$item['id']])){
                            $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."转人工";
                        }else{
                            $status_data[$item['id']] = UserLoanOrder::$status[$item['status']]."-"."机审";
                        }
                    }else{
                        $status_data[$item['id']] = isset(UserLoanOrder::$status[$item['status']])?UserLoanOrder::$status[$item['status']]:"";
                    }
                }
            }
        //}
        return $this->render('pocket-list', array(
            'data_list' => $data,
            'status_data'=>$status_data,
            'pages' => $pages,
        ));
    }

    /**
     * @param string $type
     * @return string|void
     * @name 客服管理-借款管理-还款订单列表-零钱包还款列表/actionPocketRepayList
    */
    public function actionPocketRepayList($type='list')
    {
        $condition = '1 = 1 ';
        $pages = new Pagination();
        $info=[];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if((isset($search['user_id']) && !empty($search['user_id']))||(isset($search['order_id']) && !empty($search['order_id']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['user_id']) && !empty($search['user_id'])) {
                    $condition .= " AND  ".LoanPerson::tableName().".id = " . intval($search['user_id']);
                }
                if (isset($search['order_id']) && !empty($search['order_id'])) {
                    $condition .= " AND ".UserLoanOrderRepayment::tableName().".order_id = " . intval($search['order_id']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND ".LoanPerson::tableName().".name =  '{$search['name']}'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND ".LoanPerson::tableName().".phone = '{$search['phone']}'";
                }
                $query = UserLoanOrderRepayment::find()->orderBy([UserLoanOrderRepayment::tableName().".id" => SORT_DESC]);
                $query->joinWith([
                'loanPerson' => function (Query $query) {
                    $query->select(['id','name','phone']);
                },
                'userLoanOrder' => function (Query $query) {
                    $query->select(['order_type']);
                }
                ])->where($condition)->andWhere('order_type = '.UserLoanOrder::LOAN_TYPE_LQD);
                //导出逾期表
                //if($this->request->get('submitcsv') == 'exportcsv'){
                   // return $this->_exportOverdue($query);
                //}
                $countQuery = clone $query;
                $pages->totalCount=$countQuery->count('*',UserLoanOrderRepayment::getDb_rd());
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->all(UserLoanOrderRepayment::getDb_rd());
                if($ret)
                    $info = $ret;
                }
            }
        return $this->render('repay-list', array(
            'info' => $info,
            'pages' => $pages,
            'type' => $type
        ));
    }
    /**
     * @param string $type
     * @return string|void
     * @name 客服管理-借款管理-还款订单列表-零钱包还款审核列表/actionPocketRepayTrailList
    */
    public function actionPocketRepayTrailList()
    {
        $_GET['status'] = UserLoanOrderRepayment::STATUS_CHECK;
        return $this->actionPocketRepayList('trail');
    }
    /**
     * @param string $type
     * @return string|void
     * @name 客服管理-借款管理-还款订单列表-零钱包扣款列表/actionPocketRepayCutList
    */
    public function actionPocketRepayCutList()
    {
        $_GET['status'] = UserLoanOrderRepayment::STATUS_REPAY_COMPLEING;
        return $this->actionPocketRepayList('cut');
    }
    /**
     *
     * @return string
     * @name 客服管理-借款管理-打款列表/actionLoanList
    */
    public function actionLoanMoneyList($view = 'list'){
        $condition = '1=1';
        $pages = new Pagination();
        $data=[];
        $dataSt=[];
        $type=[];
        $search = $this->request->get();
        if(!empty($search['username'])||!empty($search['phone'])||!empty($search['user_id'])||!empty($search['rid'])||!empty($search['loan_term'])||!empty($search['order_id'])){
            if (!empty($search['username'])) {
                $username = $search['username'];
                $result = LoanPerson::find() -> where(['name' => $username]) -> one();
                if($result){
                    $uid = $result["id"];
                    $condition .= " AND l.user_id = " . intval($uid);
                }else{
                    $condition .= " AND l.user_id = 0" ;
                }
            }
            if (!empty($search['phone'])) {
                $phone =$search['phone'];
                if(!is_numeric($phone)){
                    $condition = '1!=1';
                }else{
                    $result = LoanPerson::find() -> where(['phone' => $phone]) -> one();
                    if($result){
                        $uid = $result["id"];
                        $condition .= " AND l.user_id = " . intval($uid);
                    }else{
                        $condition .= " AND l.user_id = 0";
                    }
                }
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND l.user_id = " . intval($search['user_id']);
            }
            if (!empty($search['rid'])) {
                $condition .= " AND l.id = " . "'".$search['rid']."'";
            }
            if (!empty($search['loan_term'])) {
                $condition .= " AND u.loan_term = " . "'".$search['loan_term']."'";
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND l.business_id = " . "'".$search['order_id']."'";
            }
            $query = FinancialLoanRecord::find()->from(FinancialLoanRecord::tableName().' as l')->where(['in', 'l.type', FinancialLoanRecord::$kd_platform_type])->andwhere($condition)->
            select(['l.*','l.id as rid','p.name','u.loan_term','u.loan_method'])
            ->leftJoin(LoanPerson::tableName().' as p','l.user_id=p.id')->leftJoin(UserLoanOrder::tableName().' as u','l.business_id=u.id')->orderBy(['l.id'=>SORT_DESC]);
            $countQuery = clone $query;
            $db = Yii::$app->get('db_kdkj_rd');
            if($this->request->get('cache')==1) {
                $count = $countQuery->count('*', $db);
            } else {
                $count = $db->cache(function ($db) use ($countQuery) {
                    return $countQuery->count('*', $db);
                }, 3600);
            }
            $pages ->totalCount=  $count;
            $pages->pageSize = 15;
            $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all($db);
            $dataSt = $countQuery->select('sum(l.money) as money,sum(l.counter_fee) as counter_fee')->one($db);
            $type = FinancialLoanRecord::$types;
            foreach(FinancialLoanRecord::$other_platform_type as $t){
                unset($type[$t]);
            }
        }
        return $this->render('loan-money-list', [
            'withdraws' => $data,
            'pages' => $pages,
            'type' => $type,
            'view' => $view,
            'dataSt'=>$dataSt,
            'export' => 1
        ]);

    }



    /**
     * 过滤条件
     * @return string
     */
    public function getFundFilter(){

        $condition = [];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['fund_id']) && !empty($search['fund_id'])) {
                $condition[] = " a.fund_id = " . intval($search['fund_id']);
            }


            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition[] = " a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $condition[] = " d.name like '%" . $search['name']."%'";
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition[] = " d.phone = '" . (float)$search['phone']."'";
            }

            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition[] = " a.order_id = " . (int)$search['order_id'];
            }

            if (!empty($search['fund_order_id'])) {
                $condition[] = " a.fund_order_id = " . Yii::$app->db->quoteValue(trim($search['fund_order_id']));
            }

            if (isset($search['status'])&&(UserLoanOrder::STATUS_ALL != $search['status'])) {
                if($search['status'] == 10000) {
                    $condition[] = " (c.status = " . UserLoanOrder::STATUS_CANCEL . ' OR c.status = ' . UserLoanOrder::STATUS_REPEAT_CANCEL . ') AND c.is_hit_risk_rule != 1 ';
                }elseif ($search['status'] == 10001){
                    $condition[] = " c.status = " . UserLoanOrder::STATUS_CANCEL . ' AND c.is_hit_risk_rule = 1 ';

                }else{
                    $condition[] = " c.status = " . intval($search['status']);
                }

            }

            if (!empty($search['info_status'])) {
                if($search['info_status'] == 10000) {//所有状态

                }elseif ($search['info_status'] == 10001){//所有有效状态
                    $condition[] = " a.status>=0  ";
                }else{
                    $condition[] = " a.status = " . intval($search['info_status']);
                }
            }

            if (isset($search['begintime'])&&!empty($search['begintime'])) {
                $condition[] = " a.created_at >= " . strtotime($search['begintime']);
            }
            if (isset($search['endtime'])&&!empty($search['endtime'])) {
                $condition[] = " a.created_at <= " . strtotime($search['endtime']);
            }

            if (isset($search['pay_begintime'])&&!empty($search['pay_begintime'])) {
                $condition[] = " a.fund_pay_time >= " . strtotime($search['pay_begintime']);
            }
            if (isset($search['pay_endtime'])&&!empty($search['pay_endtime'])) {
                $condition[] = " a.fund_pay_time <= " . strtotime($search['pay_endtime']);
            }
        }
        return implode(' AND ', $condition);
    }


    /**
     *
     * @return string
     * @name 客服管理-借款管理-资方打款列表/actionLoanList
     */
    public function actionFundLoanMoneyList($view = 'list'){


        $condition = self::getFundFilter();

            if( !empty($condition) ){
            $db = Yii::$app->get('db_kdkj_rd');

            $info_table = OrderFundInfo::tableName();
            $order_table = UserLoanOrder::tableName();
            $user_table = LoanPerson::tableName();

            //银行卡 完善
            $query = OrderFundInfo::find()->from($info_table. ' as a ')->select("a.*,d.name as username,c.loan_term,c.money_amount,c.counter_fee,c.status as order_status")
            ->leftJoin($order_table .' as c','a.order_id=c.id')
            ->leftJoin($user_table .' as d', 'a.user_id = d.id');

            if($condition) {
                $query->where($condition);
                $countQuery = clone $query;
                $count = $countQuery->count('*', $db);
            } else {
                $countQuery = clone $query;
                $count = OrderFundInfo::find()->count('*', $db);
            }

            $pages = new Pagination(['totalCount' => $count]);
            $pages->pageSize = 15;
            $data = $query->orderBy(['id'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        }else{
            $data=[];
            $pages = new Pagination(['totalCount' => 0]);
        }

        return $this->render('order-info-list', [

            'rows' => $data,
            'pagination'=>$pages
        ]);

    }

    /**
     * @return string|void
     * @name 客服管理-借款管理-用户还款日志列表/actionBankpayList
    */
    public function actionBankpayList(){
        $condition = '1=1';
        //if ($this->request->get('search_submit')) { // 过滤
        $info=[];
        $pages = new Pagination();
        $search = $this->request->get();
        if(!empty($search['id'])||!empty($search['order_id'])||!empty($search['user_id'])||!empty($search['user_name'])||!empty($search['order_uuid'])||!empty($search['pay_order_id'])){
        if (!empty($search['id'])) {
                $condition .= " AND id = ".intval($search['id']);
            }
            if (!empty($search['order_id'])) {
                $condition .= " AND order_id = ".intval($search['order_id']);
            }
            if (!empty($search['user_id'])) {
                $condition .= " AND user_id = ".intval($search['user_id']);
            }
            if (!empty($search['user_name'])) {
                $user_info = LoanPerson::find()->where(['phone' => $search['user_name']])->one(Yii::$app->get('db_kdkj_rd'));
                $condition .= " AND user_id = ".intval($user_info['id']);
            }
            if (!empty($search['order_uuid'])) {
                $condition .= " AND order_uuid = '".trim($search['order_uuid'])."'";
            }
            if (!empty($search['pay_order_id'])) {
                $condition .= " AND pay_order_id = '".trim($search['pay_order_id'])."'";
            }
            $query = UserCreditMoneyLog::find()->where($condition)->orderBy(['id' => SORT_DESC]);
            $countQuery = clone $query;
            $db = Yii::$app->get('db_kdkj_rd');
            if($this->request->get('cache')==1) {
                $count = $countQuery->count('*', $db);
            } else {
                $count = $db->cache(function ($db) use ($countQuery) {
                    return $countQuery->count('*', $db);
                }, 3600);
            }
            $pages->totalCount= $count;
            $pages->pageSize = 15;
            $info = $query->offset($pages->offset)->limit($pages->limit)->all($db);
        }
        //}
        return $this->render('bankpay-list', [
                'info' => $info,
                'pages' => $pages,
        ]);
    }
    /**
     * @return string
     * @name 客服管理-借款管理-用户运营商认证状态/actionJxlStatusView
     */
    public function actionJxlStatusView(){
        $condition = '1 = 1';
        $pages = new Pagination();
        $info=[];
        $credit_jxl_data = [];
        $count='';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if((isset($search['uid']) && !empty($search['uid']))||(isset($search['name']) && !empty($search['name']))||(isset($search['phone']) && !empty($search['phone']))){
                if (isset($search['uid']) && !empty($search['uid'])) {
                    $condition .= " and id = " . intval($search['uid']);
                }
                if (isset($search['name']) && !empty($search['name'])) {
                    $condition .= " AND name = '" . $search['name']."'";
                }
                if (isset($search['phone']) && !empty($search['phone'])) {
                    $condition .= " AND phone = '" .str_replace(' ','',$search['phone'])."'";
                }
                $query = LoanPerson::find()->where($condition)->orderBy('id desc');
//                $query = CreditJxlQueue::find()->from(CreditJxlQueue::tableName() . ' as a')
//                ->leftJoin([LoanPerson::tableName() . ' as b on a.user_id = b.id'])
//                ->where($condition)
//                ->select('a.*,b.name,b.phone');
                $countQuery = clone $query;
                $count =  $countQuery->count('*',Yii::$app->get('db_kdkj_rd'));
                $pages ->totalCount=$count;
                $pages->pageSize = 15;
                $ret = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                $user_ids = [];
                if($ret){
                    $info=$ret;
                    foreach($info as $item){
                        $user_ids[] = $item['id'];
                    }
                    $tmp = CreditJxlQueue::find()->where(['user_id'=>$user_ids])->asArray()->all(Yii::$app->get('db_kdkj_risk_rd'));
                    foreach($tmp as $item){
                        $credit_jxl_data[$item['user_id']]= $item;
                    }
                }
            }
        }
        return $this->render('jxl-status-view', array(
            'info' => $info,
            'credit_jxl_data'=>$credit_jxl_data,
            'pages' => $pages,
            'count' => $count,
        ));
    }


    /**
     * @name 客服管理-用户管理-用户列表-注销/删除用户账户
     * @param     $id
     * @param int $approve_application_id
     * @param int $page
     *
     * @return string
     */
    public  function actionLoanPersonLogOutDel($id, $approve_application_id=0, $page=0) {
        if (UserLoanOrder::checkHasUnFinishedOrder($id)) {
            //$now_status =UserOperateApplication::APPROVED_REFUSE;
            //$this->updateOperateApplicaion($approve_application_id,$now_status);
            return $this->redirectMessage('此用户还有未结束的还款单，不能注销号码', self::MSG_ERROR);
        }

        //$updateSql="update  ".LoanPerson::tableName()." set phone=concat('_',phone), username=concat('_',username) where id=".$operate_id;
        $updateSql = "update " . LoanPerson::tableName() . "
                         set status = " . LoanPerson::PERSON_STATUS_DELETE . "
                       where id = {$id}"; //修改状态为-1
        $result = \yii::$app->db_kdkj->createCommand($updateSql)->execute();
        // 记录到表里
        $curUser = Yii::$app->user->identity;
        if($curUser) {
            $admin_name = $curUser->username ;
        } else {
            $admin_name = 0 ;
        }
        $out_del = LoanOutDel::find()->where(['user_id' => $id])->one();
        if (empty($out_del)) {
            $out_del = new LoanOutDel();
            $out_del->created_at = time();
        }
        $out_del->user_id = $id;
        $out_del->updated_at = time();
        $out_del->admin_user = $admin_name;
        $res = $out_del->save();
        if (!$result) {
             //$now_status =UserOperateApplication::APPROVED_REFUSE;
             //$this->updateOperateApplicaion($approve_application_id,$now_status);
             return $this->redirectMessage('注销/删除资料账户失败', self::MSG_ERROR);
        }

        //更新申请审批记录表字段状态
        //$now_status =UserOperateApplication::APPROVED_YES;
        //$this->updateOperateApplicaion($approve_application_id,$now_status);
        return $this->redirectMessage(
            '注销/删除资料账户成功',
            self::MSG_SUCCESS,
            Url::toRoute(['custom-management/ygd-loan-person-list', 'page'=>$page+1])
        );
    }

    /**
     * @name 客服管理-用户管理-用户列表-取消注销
     * @param     $id
     * @return string
     */
    public  function actionLoanPersonCancelOutDel() {
        if ($this->getRequest()->isAjax) {
            $this->getResponse()->format = Response::FORMAT_JSON;
            $user_id = (int)trim($this->getRequest()->post('user_id'));

            $sql = "update " . LoanPerson::tableName() . "
                         set status = " . LoanPerson::PERSON_STATUS_PASS . "
                       where id = {$user_id}"; //修改状态为1

            $result = Yii::$app->db_kdkj->createCommand($sql)->execute();
            if ($result) {
                //return CommonHelper::resp();
                return [
                    'code' => 0,
                    'message' => '恢复成功'
                ];
            } else {
                return [
                    'code' => 222,
                    'message' => '恢复失败'
                ];
            }
        }
    }

    /**
     * @name 客服管理-用户管理-用户列表-重新绑定银行卡/actionLoanPersonAfreshBind
     */
    public function actionLoanPersonAfreshBind($operate_id,$approve_application_id=0,$page=0){
        UserVerification::updateAll(['real_bind_bank_card_status'=>0],['user_id'=>$operate_id]);
        CardInfo::updateAll(['main_card'=>CardInfo::MAIN_CARD_NO],['user_id'=>$operate_id]);
    }


    /**
      * @name 客服管理-用户管理-用户列表-生成操作申请审批记录
    **/
    public function actionOperateApplication($id,$type,$remark,$status=UserOperateApplication::APPROVED_NO,$title=''){
        $admin_user_id = Yii::$app->user->identity->getId();
        $user_operate_application=new UserOperateApplication();
        $user_operate_application->operate_id = $id;
        $user_operate_application->type = $type;
        $user_operate_application->remark = $remark;
        $user_operate_application->status = $status;
        $user_operate_application->created_id=$admin_user_id;
        if($type==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){
            $user_operate_application->approved_id=0;
        }else{
            $user_operate_application->approved_id=$admin_user_id;
        }
        $user_operate_application->created_at=time();
        $user_operate_application->updated_at=time();
        $user_operate_application->save();
        if($type!=UserOperateApplication::OPERATE_DEL_PERSON_PROOF){
            return $this->redirectMessage($title, self::MSG_SUCCESS,Url::toRoute(['custom-management/ygd-loan-person-list']));
        }
     }
     /**
     * @param $id
     * @name 用户管理-用户管理-用户列表-删除用户照片/actionLoanPersonProofDelete
     */
    public function actionLoanPersonProofDelete($operate_id,$approve_application_id,$remark){
        $loan_Person_Proof=UserProofMateria::find()->where(['user_id'=>intval($operate_id)])->andWhere(['<>','status',UserProofMateria::STATUS_DEL])->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('/loan/loan-person-proof-delete'
          ,[
            'loanPersonProofInfo' => $loan_Person_Proof,
            'loanPersonProofType'=>UserProofMateria::$type,
            'approve_application_id'=>$approve_application_id,
            'list_type'=>'custom'
           ]
          );
     }
    /**
     * @name 客服管理-用户管理-用户列表-删除照片/actionLoanPersonProofDeleteOperate
     */
    public function actionLoanPersonProofDeleteOperate()
    {
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->get();
            if(!empty($data['user_id'])&&!empty($data['proof_id'])){
                $loan_Person_Proof=UserProofMateria::find()->where(['id'=>intval($data['proof_id'])])->andWhere(['<>','status',UserProofMateria::STATUS_DEL])->all(Yii::$app->get('db_kdkj_rd'));
                if(!empty($loan_Person_Proof))
                {
                    UserProofMateria::deletePicById($data['user_id'],$data['proof_id']);
                    //更新申请审批记录表字段状态
                    //$now_status=UserOperateApplication::APPROVED_YES;
                    //$approve_application_id = $data['approve_application_id'];
                    //$this->updateOperateApplicaion($approve_application_id,$now_status);
                }
                else{
                    return $this->redirectMessage('照片不存在',self::MSG_ERROR);
                }
            }else
            {
                return $this->redirectMessage('操作失败',self::MSG_ERROR);
            }
        }

    }
    /**
     * @name 客服管理-用户管理-用户列表-重置聚信力/actionRefreshJxlLoanPerson
     **/
    public function actionRefreshJxlLoanPerson($id, $approve_application_id=0, $page=0) {
        UserService::resetJxlStatus($id, \yii::$app->user->identity->id);

        //更新申请审批记录表字段状态
        return $this->redirectMessage('重置聚信力成功', self::MSG_SUCCESS,Url::toRoute(['loan/loan-person-list']));
    }
    /**
     * @author chengyunbo
     * @date 2016-11-03
     * @remark 更新申请操作记录表
    **/
    private function updateOperateApplicaion($id,$status){
        $approve_application = UserOperateApplication::find()->where(['id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        $approve_application->status=$status;
        $admin_user_id = Yii::$app->user->identity->getId();
        $approve_application->approved_id=$admin_user_id;
        $approve_application->updated_at=time();
        $approve_application->save();
    }
    /**
     * @name 客服管理-用户管理-用户列表-操作列表-申请/actionOperateType
     *
    **/
    public function actionOperateType($id){
        if ($this->getRequest()->getIsPost()) {
            if($this->request->post('submit_btn')){
                $search = $this->request->post();
                if(isset($search['operate'])&&!empty($search['operate'])){
                    $operate=intval($search['operate']);
                    $remark = $search['remark'];
                    //在提交之前就对该借款人是否能够提交相关申请操作进行验证
                    if(UserLoanOrder::checkHasUnFinishedOrder($id)){
                        if($operate==UserOperateApplication::OPERATE_DEL_PERSON_PROOF){//删除照片
                            return $this->redirectMessage('此用户还有未结束的还款单，无法修改照片',self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_DEL_PERSON_LOGOUT){//注销用户资料账户
                            return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_PERSON_BIND_BANK){//重新绑定银行卡
                            return $this->redirectMessage('此用户还有未结束的还款单，不能重新绑定银行卡', self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){//
                            return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
                        }
                        elseif($operate==UserOperateApplication::OPERATE_REFRESH_JXL){//重置聚信力
                            return $this->redirectMessage('此用户还有未结束的还款单，不能重置聚信力', self::MSG_ERROR);
                        }
                    }else{
                        if($operate==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){//所对应的新号码也需要进行是否有未完成的还款单的验证

                            //var_dump(strpos($remark,'：'));die;
                            if(strpos($remark,'：') !== false){
                                $remark_arr = explode('：',$remark);
                            }else{
                                $remark_arr = explode(':',$remark);
                            }
                            $remark_str = $remark_arr[1];
                            if(strpos($remark,'；') !== false){
                                $phone_arr =  explode('；',$remark_str);
                            }else{
                                $phone_arr =  explode(';',$remark_str);
                            }
                            $phone_new = $phone_arr[0];
                            //echo $phone_new;die;
                            $new_loan_person = LoanPerson::find()->where(['phone' => $phone_new])->one(Yii::$app->get('db_kdkj_rd'));
                            if($new_loan_person){
                                    if(UserLoanOrder::checkHasUnFinishedOrder($new_loan_person->id)){
                                        return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
                                    }
                            }
                        }
                    }//在提交之前就对该借款人是否能够提交相关申请操作进行验证
                    if(UserLoanOrder::checkLoanOrderIsOK($id)){
                        if($operate==UserOperateApplication::OPERATE_DEL_PERSON_PROOF){//删除照片
                            return $this->redirectMessage('此用户还有未结束的借款订单，无法修改照片',self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_DEL_PERSON_LOGOUT){//注销用户资料账户
                            return $this->redirectMessage('此用户还有未结束的借款订单，不能修改号码', self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_PERSON_BIND_BANK){//重新绑定银行卡
                            return $this->redirectMessage('此用户还有未结束的借款订单，不能重新绑定银行卡', self::MSG_ERROR);
                        }elseif($operate==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){//
                            return $this->redirectMessage('此用户还有未结束的借款订单，不能修改号码', self::MSG_ERROR);
                        }
                        elseif($operate==UserOperateApplication::OPERATE_REFRESH_JXL){//重置聚信力
                            return $this->redirectMessage('此用户还有未结束的借款订单，不能重置聚信力', self::MSG_ERROR);
                        }
                    }else{
                        if($operate==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){//所对应的新号码也需要进行是否有未完成的还款单的验证
                            //var_dump(strpos($remark,'：'));die;
                            if(strpos($remark,'：') !== false){
                                $remark_arr = explode('：',$remark);
                            }else{
                                $remark_arr = explode(':',$remark);
                            }
                            $remark_str = $remark_arr[1];
                            if(strpos($remark,'；') !== false){
                                $phone_arr =  explode('；',$remark_str);
                            }else{
                                $phone_arr =  explode(';',$remark_str);
                            }
                            $phone_new = $phone_arr[0];
                            $new_loan_person = LoanPerson::find()->where(['phone' => $phone_new])->one(Yii::$app->get('db_kdkj_rd'));
                            if($new_loan_person){
                                    if(UserLoanOrder::checkLoanOrderIsOK($new_loan_person->id)){
                                        return $this->redirectMessage('此用户还有未结束的借款订单，不能修改号码', self::MSG_ERROR);
                                    }
                            }
                        }
                    }
                    /**
                     * @edit:chengyunbo
                     * @date:2017-02-14
                     * @remark:应运维组同事要求，现在只让修改手机号码操作提交申请，其他的直接由客服组同事处理
                    **/
                    $status =UserOperateApplication::APPROVED_YES;
                    if($operate==UserOperateApplication::OPERATE_DEL_PERSON_PROOF){//删除照片
                        $title = '';
                        self::actionOperateApplication($id,$operate,$remark,$status,$title);
                        return self::actionLoanPersonProofDelete($id,$approve_application_id=0,$remark);
                    }elseif($operate==UserOperateApplication::OPERATE_DEL_PERSON_LOGOUT){//注销用户资料账户
                        $title = '注销/删除资料账户成功';
                        self::actionLoanPersonLogOutDel($id);
                    }elseif($operate==UserOperateApplication::OPERATE_PERSON_BIND_BANK){//重新绑定银行卡
                        $title = '重新绑定银行卡成功';
                        self::actionLoanPersonAfreshBind($id,$approve_application_id=0,$page=0);
                    }elseif($operate==UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE){
                        $title = '申请操作成功';
                        $status = UserOperateApplication::APPROVED_NO;
                    }
                    elseif($operate==UserOperateApplication::OPERATE_REFRESH_JXL){//重置聚信力
                        $title = '重置聚信力成功';
                        self::actionRefreshJxlLoanPerson($id,$approve_application_id=0,$page=0);
                    }
                    return self::actionOperateApplication($id,$operate,$remark,$status,$title);
                }
            }
        }
        return $this->render('operate-type');
    }

    public function getFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . intval($search['phone']);
            }
            if (isset($search['start_time']) && !empty($search['start_time'])) {
                $condition .= " AND a.created_at >= " . intval($search['start_time']);
            }
            if (isset($search['end_time']) && !empty($search['end_time'])) {
                $condition .= " AND a.created_at < " . intval($search['end_time']);
            }
            if (isset($search['is_first']) && !empty($search['is_first'])) {
                $condition .= " AND c.is_first = " . intval($search['is_first']);
            }
            if (isset($search['sub_type']) && !empty($search['sub_type'])) {
                $condition .= " AND a.sub_type = " . intval($search['sub_type']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND c.status = " . intval($search['status']);
            }
            if (isset($search['deal_status']) && $search['deal_status']!='') {
                $condition .= " AND a.status = " . intval($search['deal_status']);
            }
        }
        return $condition;
    }
    public function getFilterTwo() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND a.user_id = " . intval($search['user_id']);
            }
            if (isset($search['order_id']) && !empty($search['order_id'])) {
                $condition .= " AND a.order_id = " . intval($search['order_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND b.phone = " . intval($search['phone']);
            }
            if (isset($search['start_time']) && !empty($search['start_time'])) {
                $condition .= " AND a.created_at >= " . intval($search['start_time']);
            }
            if (isset($search['end_time']) && !empty($search['end_time'])) {
                $condition .= " AND a.created_at < " . intval($search['end_time']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND a.status = " . intval($search['status']);
            }
        }
        return $condition;
    }


    protected function getAccumulationFundFilter() {
        $condition = '1 = 1 and a.id>0 ';
        $search = $this->request->get();

        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition .= " AND a.user_id = " . intval($search['user_id']);
        }

        if (isset($search['status']) && !empty($search['status'])) {
            $condition .= " AND a.status = '" . $search['status']."'";
        }
        if (isset($search['status']) && $search['status'] == '0') {
            $condition .= " AND a.status = '" . $search['status']."'";
        }

        if (isset($search['add_start']) && !empty($search['add_start'])) {
            $condition .= " AND a.updated_at >= " . strtotime($search['add_start']);
        }
        if (isset($search['add_end']) && !empty($search['add_end'])) {
            $condition .= " AND a.updated_at < " . strtotime($search['add_end']);
        }
        if(isset($search['phone'])&&!empty($search['phone'])){
            $condition .= " and p.phone = {$search['phone']}";
        }
        if(isset($search['id_card'])&&!empty($search['id_card'])){
            $condition .= " and p.id_number = '{$search['id_card']}'";
        }

        return $condition;
    }

    /**
     * @name 用户公积金列表
     */
    public function actionAccumulationFundList() {
        $condition = $this->getAccumulationFundFilter();
        $query = AccumulationFund::find()->from(AccumulationFund::tableName().' as a')->innerJoin(LoanPerson::tableName().' as p','a.user_id = p.id')->where($condition)->orderBy('a.updated_at DESC');
        $countQuery = clone $query;
        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
        }, 3600);
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('house-fund-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @name 客服管理-借款列表-手动取消借款订单
     */
    public function actionManualChancelOrder()
    {
        $this->response->format = Response::FORMAT_JSON;
        $remark = $this->request->get('remark');
        if(empty(trim($remark))){
            return [
                'code' => -1,
                'msg' => '备注不能为空'
            ];
        }
        $order_id = $this->request->get('order_id');
        $user_loan_order = UserLoanOrder::find()->where(['id'=>$order_id])->one();
        if(!$user_loan_order){
            return [
                'code' => -1,
                'msg' => '订单不存在'
            ];
        }
        $admin_user = Yii::$app->user->identity->username;
        $log = OrderManualCancelLog::find()->where(['order_id'=>$order_id])->one();
        if($log){
            return [
                'code' => -1,
                'msg' => '该订单已有取消申请'
            ];
        }
        $log = new OrderManualCancelLog();
        $log->order_id = $order_id;
        $log->admin_user = $admin_user;
        $log->remark = $remark;
//        $log->method_handle = __METHOD__;
        $log->status = 0;
        if($log->save()){
            return [
                'code' => 0,
                'msg' => '提交成功'
            ];
        }else{
            return [
                'code' => -1,
                'msg' => '提交失败'
            ];
        }

    }

    /**
     * 借款协议
     */
    public function actionPlatformService($id,$fund) {
        $this->view->title = '借款协议';
        /* @var $user LoanPerson */
        if($fund == 2){
            $order = UserLoanOrder::findOne($id); // 出借方
            if (!$order) {
                throw new Exception('找不到对应的订单');
            }
            $user = LoanPerson::findOne([$order->user_id]);
            $data = $order->getContractData($user);

            /* @var $order UserLoanOrder */
            $fund = $order->loanFund;
            if (!$fund) {
                $fund = LoanFund::findOne(LoanFund::ID_KOUDAI);
            }

            $data['lender'] = $fund->company_name;
            $data['interest_rate'] = $fund->interest_rate;
            $data['lender_id_number'] = $fund->id_number;
            $data['lender_name'] = $fund->name;
            $find_id = $order->fund_id;
            $company_name = $this->getCompany($find_id,0);
            //四方协议
            return $this->render('platform-service', [
                'order' => $order,
                'data' => $data,
                'order_id' => $id,
                'company'=>$company_name
            ]);

        }else{
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
            /*if($user_agent = $this->getUserAgent()){//判断来源是否在极速荷包
                $source = LoanPerson::$user_agent_source[$user_agent];
            }
            switch ($source){
                case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT:
                    $authorization = '上海凌融网络科技有限公司';
                    break;
                default:
                    $authorization = '德清正恒网络科技有限公司';
                    break;
            }
            $app_name = LoanPerson::$person_source[$source];*/
            $unsure_text = '**订单生成后可见**';
            $day = intval($this->request->get('day', \yii::$app->params['counter_fee_rate']));
            $money = intval($this->request->get('money', 1000));
            $type = intval($this->request->get('type'));
            $type = $type == 2 ? 2 : 1;
            $time_two = date('Y 年 m 月 d 日', time());
            $time = time();
            $time_end = $time + $day * 86400;
            $fee = Util::calcMultiLoanInfo($day, $money); // 服务费
            $money_da = Util::numToMoney($money);
            $data = [
                'name' => '**订单生成后可见**',
                'id_number' => '********',
                'lender' => $unsure_text, // 出借方
                'phone' => '**订单生成后可见**',
                'day' => $day + 1,
                'money' => $money,
                'money_da' => $money_da,
                'time' => $unsure_text, //date("Y年m月d日", time()),
                'time_end' => $unsure_text, //date("Y年m月d日", $time_end),
                'service_fee' => $fee['counter_fee'],
                'service_fee_rate' => ($fee['counter_fee'] / $money) * 100,
                'time_two' => $time_two,
                'lender_id_number' => false,
                'id' => $unsure_text,

            ];

            $id = isset($id) ? $id : "";

            return $this->render('platform-service-2', [
                'order' => null,
                'data' => $data,
                'order_id' => $id,
                'down_url' => '',
            ]);
        }
    }

    /**
     * 反馈列表
     * @name 客服管理-反馈管理-反馈列表
     */
    public function actionFeedbackList()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $db = \yii::$app->db;
        $count = UserFeedback::find()->count('*', $db);
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $data = UserFeedback::find()->offset($pages->offset)
            ->limit($pages->limit)->orderBy(['id' => SORT_DESC])
            ->asArray()->all($db);

        return $this->render('feedback-list', ['data' => $data, 'pages' => $pages]);
    }
}
