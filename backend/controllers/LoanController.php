<?php
namespace backend\controllers;


use backend\models\AdminOperatorLog;
use common\models\Channel;
use common\models\UserDetail;
use common\services\fundChannel\JshbService;
use Yii;
use yii\web\Response;
use common\helpers\StringHelper;
use common\models\LoanPerson;
use common\models\LoanOutDel;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use common\models\LoanBlackList;
use common\models\UserVerification;
use common\models\CardInfo;
use common\models\UserLoanOrder;
use common\models\UserProofMateria;
use common\helpers\Util;
use common\services\UserService;
use common\api\RedisQueue;
use common\helpers\ArrayHelper;
use common\models\UserLoginLog;

/**
 * Class LoanController     借款管理控制器
 * @package backend\controllers
 */
class LoanController extends BaseController {

    /**
     * @name 用户管理-用户管理-今日登录用户/actionLoginList
     */
    public function actionLoginList() {
        $conditions = $this->getFilter();

        $today = strtotime(date('Y-m-d',time()));
        $query = UserLoginLog::find()
            ->from(UserLoginLog::tableName(). ' as a')
            ->leftJoin(LoanPerson::tableName(). ' as b','a.user_id = b.id')
            ->where(['>=', 'a.created_at', $today]);
        foreach ($conditions as $condition){
            $query = $query
                ->andWhere($condition);
        }

        $query = $query
            ->orderBy(['a.created_at' => SORT_DESC])
            ->select('user_id,name,phone,source_id,customer_type,a.created_at,a.created_ip');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $login_list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        return $this->render('login_record_list', array(
            'login_list' => $login_list,
            'pages' => $pages,
        ));
    }

    public function getFilter(){
        $condition = [];
        if ($this->request->getIsGet()) {
            $search = $this->request->get();
            if( !empty($search['user_id'])){
                $condition[] = ['=','user_id',intval($search['user_id'])] ;
            }
            if( !empty($search['phone'])){
                $condition[] = ['=','phone',intval($search['phone'])] ;
            }
            if( !empty($search['name'])){
                $condition[] = ['=','name',intval($search['name'])] ;
            }
            if( !empty($search['customer_type']) && intval($search['customer_type']) <> -1){
                $condition[] = ['=','customer_type',intval($search['customer_type'])] ;
            }
        }
        return $condition;
    }

    /**
     * @name 借款项目列表
     * @return string
     */
    public function actionLoanProjectList() {
        $condition = $this->getLoanProjectFilter();
        $query = LoanProject::find()->where($condition)->orderBy(['id' => SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 8;
        $loan_project_list = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('loan-project-list', array(
            'loan_project_list' => $loan_project_list,
            'pages' => $pages,
        ));
    }

    /**
     * @name 借款项目添加
     */
    public function actionLoanProjectAdd() {
        $loan_project = new LoanProject();
        $loan_project->success_number = 0;
        if ($this->getRequest()->getIsPost()) {
            $loan_project->load($this->request->post());
            $loan_project->amount_min = StringHelper::safeConvertCentToInt($loan_project->amount_min);
            $loan_project->amount_max = StringHelper::safeConvertCentToInt($loan_project->amount_max);
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                if ($loan_project->validate() && $loan_project->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('添加借款项目成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-project-list']));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('添加借款项目失败', self::MSG_ERROR);
            }
        }
        return $this->render('loan-project-add', array(
            'loan_project' => $loan_project,
        ));
    }

    /**
     * @name 借款项目作废
     * @param $id       借款项目id
     */
    public function actionLoanProjectDel($id) {
        $result = Yii::$app->db_kdkj->createCommand()->update(LoanProject::tableName(), [
            'status' => LoanProject::STATUS_DELETE
        ],
            [
                'id' => intval($id),
            ])->execute();
        if (!$result) {
            return $this->redirectMessage('作废借款项目失败', self::MSG_ERROR);
        }

        return $this->redirectMessage('作废借款项目成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-project-list']));
    }

    /**
     * @name 借款项目编辑
     * @param $id       借款项目id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionLoanProjectEdit($id) {
        $loan_project = LoanProject::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if (!isset($loan_project) && empty($loan_project)) {
            throw new NotFoundHttpException('The requested loan project does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $loan_project->load($this->request->post());
            $loan_project->amount_min = StringHelper::safeConvertCentToInt($loan_project->amount_min);
            $loan_project->amount_max = StringHelper::safeConvertCentToInt($loan_project->amount_max);
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($loan_project->validate() && $loan_project->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('编辑借款项目成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-project-list']));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('编辑借款项目失败', self::MSG_ERROR);
            }
        }

        return $this->render('loan-project-edit', array(
            'loan_project' => $loan_project,
        ));
    }

    /**
     * 借款项目信息
     * @param $id       借款项目id
     * @return string
     * @throws NotFoundHttpException
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionLoanProjectView($id) {
        $loan_project = LoanProject::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if (!isset($loan_project) && empty($loan_project)) {
            throw new NotFoundHttpException('The requested loan project does not exist.');
        }
        return $this->render('loan-project-view', array(
            'loan_project' => $loan_project,
        ));
    }

    /**
     * 借款记录列表
     * @return string
     * @author hezhuangzhuang@kdqugou.com
     */
    public function actionLoanRecordList() {
        $condition = $this->getLoanRecordFilter();
        $query = LoanRecord::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $loan_record_list = $query->with([
            'user' => function(Query $query) {
                $query->select(['id', 'username', 'realname']);
            },
            'loanProject' => function(Query $query) {
                $query->select(['id', 'loan_project_name']);
            }
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('loan-record-list', array(
            'loan_record_list' => $loan_record_list,
            'pages' => $pages,
        ));
    }

    /**
     * 借款记录信息
     * @param $id       借款记录ID
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionLoanRecordView($id) {
        $loan_record = LoanRecord::find()->where(['id' => intval($id)])->with([
            'user' => function(Query $query) {
                $query->select(['id', 'username', 'realname', 'id_card', 'phone']);
            },
        ])->one(Yii::$app->get('db_kdkj_rd'));
        if (!isset($loan_record) && empty($loan_record)) {
            throw new NotFoundHttpException('The requested loan record does not exist.');
        }
        return $this->render('loan-record-view', array(
            'loan_record' => $loan_record,
        ));
    }

    /**
     * 借款记录审核
     * @param $id       借款记录ID
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionLoanRecordReview($id) {
        $loan_record = LoanRecord::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if (!isset($loan_record) && empty($loan_record)) {
            throw new NotFoundHttpException('The requested loan record does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $loan_record->load($this->request->post());
            switch ($loan_record['status']) {
                case LoanRecord::STATUS_LOAN_APPLYING:
                    break;
                case LoanRecord::STATUS_CONTACT_SUCCESS:
                case LoanRecord::STATUS_CONTACT_FAIL:
                    $loan_record['contact_username'] = Yii::$app->user->identity->username;
                    $loan_record['contact_time'] = time();
                    break;
                case LoanRecord::STATUS_REVIEW_APPROVE:
                case LoanRecord::STATUS_REVIEW_DISMISS:
                    $loan_record['review_username'] = Yii::$app->user->identity->username;
                    $loan_record['review_time'] = time();
                    break;
                case LoanRecord::STATUS_LOAN_COMPLETE:
                    $loan_record['loan_username'] = Yii::$app->user->identity->username;
                    $loan_record['loan_time'] = time();
                    break;
                case LoanRecord::STATUS_REPAY_COMPLETE:
                    $loan_record['repay_time'] = time();
                    break;
                default:
                    return $this->redirectMessage('未知的借款操作', self::MSG_ERROR);
            }
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($loan_record['status'] == LoanRecord::STATUS_LOAN_COMPLETE){
                    $loan_project = LoanProject::findOne($loan_record['loan_project_id']);
                    if (!$loan_project->updateCounters(['success_number' => 1])){
                        throw new Exception();
                    }
                }
                if ($loan_record->validate() && $loan_record->save()) {
                    $transaction->commit();
                    return $this->redirectMessage('借款操作成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-record-list']));
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('借款操作失败', self::MSG_ERROR);
            }
        }
        return $this->render('loan-record-review', array(
            'loan_record' => $loan_record,
        ));
    }

    /**
     * 借款项目过滤
     * @return string
     * @author hezhuangzhuang@kdqugou.com
     */
    protected function getLoanProjectFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['loan_project_name']) && !empty($search['loan_project_name'])) {
                $condition .= " AND loan_project_name LIKE '%" . trim($search['loan_project_name']) . "%'";
            }
            if (isset($search['type']) && !empty($search['type'])) {
                $condition .= " AND type = " . intval($search['type']);
            }
        }
        return $condition;
    }

    /**
     * 借款记录过滤
     * @return string
     * @author hezhuangzhuang@kdqugou.com
     */
    protected function getLoanRecordFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $user = User::find()->where(['username' => $search['phone']])->one(Yii::$app->get('db_kdkj_rd'));
                $condition .= " AND user_id = " . intval($user['id']);
            }
            if (isset($search['id_number']) && !empty($search['id_number'])) {
                $user = User::find()->where(['id_number' => $search['id_number']])->one(Yii::$app->get('db_kdkj_rd'));
                $condition .= " AND user_id = " . intval($user['id']);
            }
            if (isset($search['type']) && !empty($search['type'])) {
                $condition .= " AND type = " . intval($search['type']);
            }
            if (isset($search['status']) && !empty($search['status'])) {
                $condition .= " AND status = " . intval($search['status']);
            }
            if (isset($search['created_at_start']) && !empty($search['created_at_start'])) {
                $condition .= " AND created_at >= " . strtotime($search['created_at_start']);
            }
            if (isset($search['created_at_end']) && !empty($search['created_at_end'])) {
                $condition .= " AND created_at < " . strtotime($search['created_at_end']);
            }
        }
        return $condition;
    }

    /**
     * 向服务器发起post请求
     * @param $url 服务器地址
     * @param null $postFields 请求参数
     * @param $charset 字符集
     * @return mixed 服务器返回的值
     * @throws Exception post异常
     */
    public static function curl($url, $postFields = null, $charset) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($response, $httpStatusCode);
            }
        }
        curl_close($ch);
        return $response;
    }

    /**
     *
     * @name 用户管理-用户管理-用户列表-列表/actionLoanPersonListCreate
     */
    public function actionLoanPersonListCreate(){
        // $condition = "1 = 1 and status >= ".LoanPerson::PERSON_STATUS_NOPASS;
        $condition = "1 = 1 ";
        if ($this->request->get('search_submit')) {        //过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND id = ".$search['id'];
            }
            if (!empty($search['type'])) {
                $condition .= " AND type = ".$search['type'];
            }
            if (!empty($search['name'])) {
                $condition .= " AND name = "."'".$search['name']."'";
            }
            if (!empty($search['phone'])) {
                $condition .= " AND phone = ".$search['phone'];
            }
            if (!empty($search['id_number'])) {
                $condition .= " AND id_number = ".$search['id_number'];
            }
            if (!empty($search['property'])) {
                $condition .= " AND property = ".$search['property'];
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND created_at >= ".strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND created_at < ".strtotime($search['endtime']);
            }
        }
        if (null != $this->request->get('create_type')){
            $create_type = $this->request->get('create_type');
            if (!isset(LoanProject::$type_list[$create_type])){
                return ;
            }
        } else{
            return;
        }
        $loan_person = LoanPerson::find()->where($condition." order by id desc");
        $countQuery = clone $loan_person;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('loan-person-list-create', array(
            'loan_person' => $loan_person,
            'pages' => $pages,
            'create_type'=>$create_type,
        ));
    }

    public function actionLoanPersonFilter() {
        $condition = LoanPerson::tableName().".status >= ".LoanPerson::PERSON_STATUS_DELETE;
        if ($this->request->get()) {        //过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND ".LoanPerson::tableName().".id = ".intval($search['id']);
            }
            if (!empty($search['type'])) {
                $condition .= " AND ".LoanPerson::tableName().".type = ".$search['type'];
            }
            if (!empty($search['source_id'])) {
                $condition .= " AND ".LoanPerson::tableName().".source_id = ".$search['source_id'];
            }
            // if (!empty($search['name'])) {
            //     $condition .= " AND ".LoanPerson::tableName().".name like'%".$search['name']."%'";
            // }
            if (!empty($search['name'])) {
                $condition .= " AND ".LoanPerson::tableName().".name = '".$search['name']."'";
            }
            if (!empty($search['phone'])) {
                $condition .= " AND ".LoanPerson::tableName().".phone = ".$search['phone'];
            }
            if (!empty($search['id_number'])) {
                $condition .= " AND ".LoanPerson::tableName().".id_number = '".$search['id_number']."'";
            }
            if (!empty($search['property'])) {
                $condition .= " AND ".LoanPerson::tableName().".property = ".$search['property'];
            }
            if (!empty($search['begintime'])) {
                $condition .= " AND ".LoanPerson::tableName().".created_at >= ".strtotime($search['begintime']);
            }
            if (!empty($search['endtime'])) {
                $condition .= " AND ".LoanPerson::tableName().".created_at < ".strtotime($search['endtime']);
            }
            if (isset($search['verify_status']) && $search['verify_status'] !='') {
                $condition .= " AND ".LoanPerson::tableName().".is_verify = ".$search['verify_status'];
            }

            if (isset($search['black_status']) && $search['black_status'] !== '') {
                if ($search['black_status'] == 0){
                    $condition .= " AND (".LoanBlackList::tableName().".black_status = 0 or ".LoanBlackList::tableName().".black_status is null)";
                } else{
                    $condition .= " AND ".LoanBlackList::tableName().".black_status = ".$search['black_status'];
                }
            }
        }
        return $condition;
    }

    /**
     * @name 合作资产-第三方借款管理-用户列表/actionLoanPersonList
     * @return string
     */
    public function actionLoanPersonList(){
        $condition = $this->actionLoanPersonFilter();
        $loan_person = LoanPerson::find()->where($condition)->select([LoanPerson::tableName().'.*',LoanBlackList::tableName().'.black_status'])
            ->leftJoin(LoanBlackList::tableName(),LoanPerson::tableName().'.id = '.LoanBlackList::tableName().'.user_id')
            ->orderBy([LoanPerson::tableName().'.id'=>SORT_DESC])->asArray();
        $countQuery = clone $loan_person;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->with([
            'userDetail' => function(Query $query) {
                $query->select(['id', 'company_name', 'user_id']);
            },
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('loan-person-list', array(
            'type' =>1,
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }

    /**
     * @name 被注销用户列表
     * @return string
     */
    public function actionOutDelList()
    {
        $condition = [];
        $query = LoanOutDel::find()->from(LoanOutDel::tableName() . ' as a')->select(['a.*','p.status','p.name','p.phone'])
            ->leftJoin(LoanPerson::tableName() . ' as p', 'a.user_id = p.id')->where($condition)->orderBy('a.id DESC')->asArray();

        $countQuery = LoanOutDel::find()->select("count(1)")->where($condition);
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = \yii::$app->getRequest()->get('per-page', 15);
        $loan_person = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('loan-out-del-list', array(
            'type' => 1,
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }

    /**
     * @name 用户管理-用户管理-用户列表-添加借款人
     */
    public function actionLoanPersonAdd($tip = 0){
        $list = Company::find()->where([">=","status",0])->asArray()->select(['company_name'])->all(Yii::$app->get('db_kdkj_rd'));
        $data = [];
        foreach($list as  $item) {
            $data[$item['company_name']] = $item['company_name'];
        }

        $loan_person = new LoanPerson();
        $company = new Company();

        if ($loan_person->load($this->request->post()) && $loan_person->validate()){
            $type = Yii::$app->request->post("LoanPerson")['type'];
            $shop_code = trim($this->request->post("shop_code"),"");
            $shop = "";
            if (!empty($shop_code)){
                $shop = Shop::findOne(['shop_code'=>$shop_code,'status'=>Shop::SHOP_ACTIVE]);
                if (empty($shop)){
                    return $this->redirectMessage('机构代码错误', self::MSG_ERROR);
                }
            }

            $loan_person->uid = 0;
            $loan_person->birthday = strtotime($loan_person->birthday);
            $loan_person->status=LoanPerson::PERSON_STATUS_PASS;
            try {
                if ($loan_person->validate() && $loan_person->save()) {
                    if (!empty($shop)){
                        //添加用户
                        $user_hfd_info = UserHfdInfo::findOne(['user_id'=>$loan_person->id]);
                        if (false ===$user_hfd_info){

                        } elseif (empty($user_hfd_info)){
                            $user_hfd_info = new  UserHfdInfo();
                            $can_choose = intval($this->request->post("can_choose"),0);
                            $user_hfd_info->user_id = $loan_person->id;
                            $user_hfd_info->created_at = time();
                            $user_hfd_info->updated_at = time();
                            $user_hfd_info->shop_code = $shop_code;
                            $user_hfd_info->can_choose = ($can_choose == 0)?UserHfdInfo::CAN_NO_CHOOSE_SHOP:UserHfdInfo::CAN_CHOOSE_SHOP;
                            $user_hfd_info->save();

                        }
                    }
                    if ($tip == 1){
                        return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('loan/ygd-list'));
                    } else {
                        return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('loan/loan-person-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }
        return $this->render('loan-person-add', [
                'loan_person' => $loan_person,
                'data' => $data,
                'company' => $company
            ]
        );
    }

    /**
     * @name 用户管理-用户管理-用户列表-查看/actionLoanPersonView
     * @param $id
     */
    public function actionLoanPersonView($id) {
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->with('creditJxl')->with('creditZmop')->one(Yii::$app->get('db_kdkj_rd'));
        $verify = UserVerification::find()->where(['user_id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        //查询渠道信息
        if (!isset($loan_person) && empty($loan_person)) {
            throw new NotFoundHttpException(\sprintf('The requested page does not exist (%s|%s).', empty($loan_person), empty($verify)));
        }

        $type = Yii::$app->request->get('type');
        $tittle_arr = '';
        if ($type == 1) {
            $tittle_arr = LoanPerson::$company;
            return $this->render('loan-person-view', [
                'loan_person' => $loan_person,
                'tittle' => $tittle_arr,
                'verify' => $verify,
                'type' => $type,
            ]);
        }
        else {
            $tittle_arr = LoanPerson::$person;
            $info = Yii::$container->get('loanPersonInfoService')->getLoanPersonInfo( $loan_person );
            return $this->render('loan-person-view', [
                'loan_person' => $loan_person,
                'tittle' => $tittle_arr,
                'verify' => $verify,
                'information' => $info,
                'type' => $type
            ]);
        }
    }

    /**
     * @param $id
     * @name 用户管理-用户管理-用户列表-编辑/actionLoanPersonEdit
     */
    public function actionLoanPersonEdit($id){
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        $shop_code = UserHfdInfo::findOne(['user_id'=>$id]);
        if ($shop_code){
            $shop_code = $shop_code->shop_code;
        } else{
            $shop_code = "";
        }
        $shop_code_post = trim($this->request->post("shop_code"),"");
        $shop = "";
        if (!empty($shop_code_post)){
            $shop = Shop::findOne(['shop_code'=>$shop_code_post,'status'=>Shop::SHOP_ACTIVE]);
            if (empty($shop)){
                return $this->redirectMessage('机构代码错误', self::MSG_ERROR);
            }

        }

        $list = Company::find()->where([">=","status",0])->asArray()->select(['company_name','is_novice'])->all(Yii::$app->get('db_kdkj_rd'));
        $data = [];
        $fee = [];
        foreach($list as $item) {
            $data[$item['company_name']] = $item['company_name'];
            $fee[$item['company_name']] = $item['is_novice'];
        }
        $log = new UserOperateLog();
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if (empty($company)) {
            if ($this->getRequest()->getIsPost()) {
                $loan_person->load($this->request->post());
                //加锁（限制只能由一个用户进行修改操作）
                $lock_key = sprintf("%s:%s", RedisQueue::USER_OPERATE_LOCK, 'LPEDIT:'.$id);
                if (1 == RedisQueue::inc([$lock_key, 1])) {
                    RedisQueue::expire([$lock_key, 30]);
                    if ($loan_person->save()){
                        if (!empty($shop)){
                            //添加用户
                            $user_hfd_info = UserHfdInfo::findOne(['user_id'=>$loan_person->id]);
                            if (false ===$user_hfd_info){

                            } elseif (empty($user_hfd_info)){
                                $user_hfd_info = new  UserHfdInfo();
                                $can_choose = intval($this->request->post("can_choose"),0);
                                $user_hfd_info->user_id = $loan_person->id;
                                $user_hfd_info->created_at = time();
                                $user_hfd_info->updated_at = time();
                                $user_hfd_info->shop_code = $shop_code_post;
                                $user_hfd_info->can_choose = ($can_choose == 0)?UserHfdInfo::CAN_NO_CHOOSE_SHOP:UserHfdInfo::CAN_CHOOSE_SHOP;
                                $user_hfd_info->save();

                            } elseif (!empty($user_hfd_info)&&!empty($shop_code_post)){
                                $can_choose = intval($this->request->post("can_choose"),0);
                                $user_hfd_info->updated_at = time();
                                $user_hfd_info->shop_code = $shop_code_post;
                                $user_hfd_info->can_choose = ($can_choose == 0)?UserHfdInfo::CAN_NO_CHOOSE_SHOP:UserHfdInfo::CAN_CHOOSE_SHOP;
                                $user_hfd_info->save();

                            }
                        }
                        $transaction->commit();
                        return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-person-list']));
                    } else {
                        return $this->redirectMessage('修改失败', self::MSG_ERROR);
                    }
                }
            }

            return $this->render('loan-person-edit',[
                'loan_person' => $loan_person,
                'fee' => $fee,
                'shop_code'=>$shop_code
            ]);
        }
        $company = UserDetail::find()->where(['user_id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        $company_fee = Company::find()->where(['id' => $company['company_id']])->one(Yii::$app->get('db_kdkj_rd'));
        $loan_person->birthday = empty($loan_person->birthday) ? "" :date('Y-m-d', $loan_person->birthday);
        if (!isset($company) && empty($company)) {
            throw new NotFoundHttpException('The requested user detail does not exist.');
        }
        if (!isset($loan_person) && empty($loan_person)) {
            throw new NotFoundHttpException('The requested loan person does not exist.');
        }

        if ($this->getRequest()->getIsPost()) {
            $loan_person->load($this->request->post());
            $company->load($this->request->post());
            $company_name=  Yii::$app->request->post("UserDetail");

            $company->company_name = $company_name['company_name'];
            $loan_person->birthday = strtotime($loan_person->birthday);
            $verify = UserVerification::find()->where(['user_id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
            if ( Yii::$app->request->post('is_novice') == Company::NOT_COLLECTION_FEE) {
                $verify->is_quota_novice = UserVerification::VERIFICATION_QUOTA_NOVICE;
                $verify->is_fzd_novice = UserVerification::VERIFICATION_FZD_NOVICE;
            } elseif (Yii::$app->request->post('is_novice') == Company::COLLECTION_FEE) {
                $verify->is_quota_novice = 0;
                $verify->is_fzd_novice = 0;
            }

            $log->user_id = $id;
            $log->type = UserOperateLog::TYPE_EDIT_USER;
            $log->operator_name = Yii::$app->user->identity->username;
            $log->created_at = time();

            try {
                if ($company->validate() && $loan_person->validate() && $verify->validate() && $log->validate()) {
                    //加锁（限制只能由一个用户进行修改操作）
                    $lock_key = sprintf("%s:%s", RedisQueue::USER_OPERATE_LOCK, 'LPEDIT:'.$id);
                    if (1 == RedisQueue::inc([$lock_key, 1])) {
                        RedisQueue::expire([$lock_key, 30]);
                        if ($company->save() && $loan_person->save() && $verify->save() && $log->save()){
                            if (!empty($shop)){
                                //添加用户
                                $user_hfd_info = UserHfdInfo::findOne(['user_id'=>$loan_person->id]);
                                if (false ===$user_hfd_info){

                                } elseif (empty($user_hfd_info)||(empty($user_hfd_info->shop_code))){
                                    $user_hfd_info = new  UserHfdInfo();
                                    $can_choose = intval($this->request->post("can_choose"),0);
                                    $user_hfd_info->user_id = $loan_person->id;
                                    $user_hfd_info->created_at = time();
                                    $user_hfd_info->updated_at = time();
                                    $user_hfd_info->shop_code = $shop_code_post;
                                    $user_hfd_info->can_choose = ($can_choose == 0)?UserHfdInfo::CAN_NO_CHOOSE_SHOP:UserHfdInfo::CAN_CHOOSE_SHOP;
                                    if (!$user_hfd_info->save()){
                                        $transaction->rollback();
                                        return $this->redirectMessage('修改失败', self::MSG_ERROR);
                                    }


                                }
                            }
                            $transaction->commit();
                            return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-person-list']));
                        } else {
                            throw new Exception;
                        }
                    }
                } else {
                    throw new Exception;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }


        return $this->render('loan-person-edit',[
            'loan_person' => $loan_person,
            'data' => $data,
            'company' => $company,
            'company_fee' => $company_fee,
            'fee' => $fee,
            'shop_code'=>$shop_code,
        ]);
    }

    /**
     * 删除借款人
     * @return string
     */
    public function actionLoanPersonDel($id)
    {
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->one(Yii::$app->get('db_kdkj_rd'));
        if (!$loan_person->delete(false)) {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }
        return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('loan/loan-person-list'));
    }

    /**
     * 查看借款人图片
     * @return string
     * @throws \yii\base\Exception
     */
    public function actionLoanPersonPic(){
        $id = Yii::$app->request->get("id");
        $loan_person = LoanPerson::findOne($id);
        if (empty($loan_person)){
            throw new Exception("不存在该记录");
        }
        $attachment = $loan_person->attachment;
        if (empty($attachment)){
            throw new Exception("该记录不存在附件！");
        }
        $attachment_pic_arr = explode(",", $attachment);
        return $this->render('loan-pic-view',[
            'attachment_pic_arr' => $attachment_pic_arr,
        ]);
    }

    /**
     * 补全借款人信息
     * @return array
     */
    public function actionFillLoanerDetail()
    {
        $phone = Yii::$app->request->get('phone');
        $model = User::find()->where(['username' => $phone])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        if (!$model) {
            $data['code'] = -1;
            $data['msg'] = "该借款人不存在";
            return json_encode($data);
        }
        $data = [
            'code' => 0,
            'id' => $model['id'],
            'realname' => $model['realname'],
            'id_card' => $model['id_card'],
            'birthday' => $model['birthday'],
            'sex' => $model['sex'],
        ];
        return json_encode($data);
    }

    /**
     * @name 用户管理-用户管理-用户列表/actionYgdList
     */
    public function actionYgdList() {

        $db = Yii::$app->get('db_kdkj_rd');
        $reg_app_market_detail='-';
        $condition = $this->actionLoanPersonFilter();
        //$query = LoanPerson::find()->where($condition)->orderBy(['id'=>SORT_DESC]);
        $query = LoanPerson::find()->where($condition)->select([LoanPerson::tableName().'.*',LoanBlackList::tableName().'.black_status'])
            ->leftJoin(LoanBlackList::tableName(),LoanPerson::tableName().'.id = '.LoanBlackList::tableName().'.user_id')
            ->orderBy([LoanPerson::tableName().'.id'=>SORT_DESC])->asArray();
        $countQuery = LoanPerson::find()->select("count(1)")->where($condition);
        if($this->request->get('cache')==1) {
            $count = $countQuery->count('*', $db);
        } else {
            $count = 9999999;
        }

//        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
//            return $countQuery->createCommand()->queryScalar( \yii::$app->db_kdkj_rd );
//        }, 3600);

        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->getRequest()->get('per-page', 15);
        $ygd_loan_person = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all( \yii::$app->db_kdkj_rd );
        foreach($ygd_loan_person as $value){
            $user_arr[$value['id']]=$value['source_id'];
        }

        new LoanPerson();
        $uids = ArrayHelper::getColumn($ygd_loan_person, 'id');
        $_res = UserDetail::find()
            ->select(['user_id', 'company_name', 'reg_app_market', 'reg_device_name','reg_os_version','reg_client_type' ])
            ->where(['user_id' => $uids])
            ->asArray()->all();
        $details = [];
        foreach($_res as $_row) {
            if(isset($user_arr[$_row['user_id']])&&in_array($user_arr[$_row['user_id']],LoanPerson::$source_register_list)){
                if(strstr($_row['reg_app_market'],'_')){
                    $arr=explode('_',$_row['reg_app_market']);

                    if(count($arr)==2&&$arr[0]=='xybt'){
                        $market=$arr[1];
                    }
                    if(count($arr)==2&&$arr[0]!='xybt'){
                        $market=$_row['reg_app_market'];
                    }
                    if(count($arr)==3&&$arr[0]=='xybt'){
                        $market=$arr[1].'_'.$arr[2];
                    }
                    if(count($arr)==3&&$arr[0]!='xybt'){
                        $market=$_row['reg_app_market'];
                    }
                }else{
                    $market=$_row['reg_app_market'];
                }
                $channel=Channel::find()->where(['appMarket'=>$market])->asArray()->One(\yii::$app->db_kdkj_rd );
                if($channel && isset($channel['source_str'])){
                    $reg_app_market_detail=LoanPerson::$source_app[$channel['source_str']];
                }else{
                    $reg_app_market_detail='-';
                }
            }
            $details[$_row['user_id']] = [
                'reg_app_market' => $_row['reg_app_market'],
                'reg_app_market_detail' => $reg_app_market_detail,
                'company_name' => $_row['company_name'],
                'reg_device_name' => $_row['reg_device_name'],
                'reg_os_version' => $_row['reg_os_version'],
                'reg_client_type' => $_row['reg_client_type'],
            ];
        }

        return $this->render('loan-person-list', [
            'loan_person' => $ygd_loan_person,
            'details' => $details,
            'pages' => $pages,
            'tip' => 1,
            'type' => $this->request->get('type'),
        ]);
    }

    /**
     * @name 用户管理-用户列表/渠道分销
     */
    public function actionYgdListChannel()
    {
        $_GET['channel'] = 1;
        $channel=Yii::$app->params['DistributionChannel'];
        $admin_user=Yii::$app->user->identity->username;
        $admin_name=[];
        foreach($channel as $value)
        {
            foreach($value['username'] as $item)
            {
                $admin_name[]=$item;
            }
            if (in_array($admin_user,$value['username']))
            {
                $_GET["source_id"]=$value['source_id'];
            }
        }
        if (!in_array($admin_user,$admin_name))
        {
            return $this->redirectMessage('请配置渠道',self::MSG_ERROR);
        }
        return $this->actionYgdList();
    }
    /**
     * edit:chengyunbo
     * date:2016-10-13
     * @name 注销/删除资料账户
     **/
    public function actionLoanPersonLogOutDel($id)
    {
        if (UserLoanOrder::checkHasUnFinishedOrder($id)){
            return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
        }
        //$updateSql="update  ".LoanPerson::tableName()." set phone=concat('_',phone), username=concat('_',username) where id=".$id;
        $updateSql = "update tb_loan_person set status = -2 where id = {$id}";

        $result = Yii::$app->db_kdkj->createCommand($updateSql)->execute();
        if (!$result) {
            return $this->redirectMessage('注销/删除资料账户失败', self::MSG_ERROR);
        }
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
        /*记录操作日志*/
        AdminOperatorLog::log($id);
        return $this->redirectMessage('注销/删除资料账户成功', self::MSG_SUCCESS, Url::toRoute(['loan/loan-person-list']));
    }

    /**
     * @name 改变注销状态
     * @return array
     */
    public function actionChangeStatus()
    {
        if ($this->getRequest()->isAjax) {
            $this->getResponse()->format = Response::FORMAT_JSON;
            $id = trim($this->getRequest()->post('id'));

            if (!$del_out = LoanOutDel::find()->where(['id' => $id])->one()) {
                return [
                    'code' => 2,
                    'message' => '记录不存在'
                ];
            }

            $sql = "update tb_loan_person set status = 1 where id = {$del_out->user_id}";

            $del_out->updated_at = time();
            $result = Yii::$app->db_kdkj->createCommand($sql)->execute();
            if ($del_out->save()) {
                //return CommonHelper::resp();
                return [
                    'code' => 0,
                    'message' => '更新成功'
                ];
            } else {
                return [
                    'code' => 222,
                    'message' => '更新失败'
                ];
            }
        }
    }

    /**
     * @name 用户管理-用户管理-用户列表-添加借款人/actionLoanPersonAfreshBind
     *
     */
    public function actionLoanPersonAfreshBind($id){
        if (UserLoanOrder::checkHasUnFinishedOrder($id)){
            return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
        }
        UserVerification::updateAll(['real_bind_bank_card_status'=>0],['user_id'=>$id]);
        CardInfo::updateAll(['main_card'=>CardInfo::MAIN_CARD_NO],['user_id'=>$id]);
        return $this->redirectMessage('重新绑定银行卡成功', self::MSG_SUCCESS);
    }

    /**
     * @param $id
     * @name 用户管理-用户管理-用户列表-新旧号码更改/actionLoanPersonUpdatePhone
     */
    public function actionLoanPersonUpdatePhone($id){
        $id = intval($id);
        $loan_person = LoanPerson::find()->where(['id' => $id])->one(Yii::$app->get('db_kdkj_rd'));
        if ($this->getRequest()->getIsPost()) {
            $phone_new=trim($this->request->post("phone_new"),0);
            if (!Util::verifyPhone($phone_new)){
                return $this->redirectMessage('手机号码非法', self::MSG_ERROR);
            }
            if (UserLoanOrder::checkHasUnFinishedOrder($id)){
                return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
            }
            $new_loan_person = LoanPerson::find()->where(['phone' => $phone_new])->one(Yii::$app->get('db_kdkj_rd'));
            if ($new_loan_person){
                if (UserLoanOrder::checkHasUnFinishedOrder($new_loan_person->id)){
                    return $this->redirectMessage('此用户还有未结束的还款单，不能修改号码', self::MSG_ERROR);
                }
                //Yii::$app->db_kdkj->createCommand()->update(LoanPerson::tableName(), [
                //      'phone' => 'concat("_",phone)',
                //     'username' => 'concat("_",username)'
                //],['id' => $new_loan_person->id])->execute();
                $updateSql='update  '.LoanPerson::tableName().' set phone=concat("_",phone), username=concat("_",username) where id='.$new_loan_person->id;
                $result = Yii::$app->db_kdkj->createCommand($updateSql)->execute();
            }
            $loan_person->phone = $phone_new;
            $loan_person->username = $phone_new;
            $loan_person->save();
            /*记录操作日志*/
            AdminOperatorLog::log($id,'更改手机号',['phone'=>$phone_new]);

            return $this->redirectMessage('更改号码成功', self::MSG_SUCCESS);
        }
        return $this->render('loan-person-update-phone',[
            'loan_person' => $loan_person
        ]);
    }

    /**
     * @param $id
     * @name 用户管理-用户管理-用户列表-删除用户照片/actionLoanPersonProofDelete
     */
    public function actionLoanPersonProofDelete($id){
        $loan_Person_Proof=UserProofMateria::find()->where(['user_id'=>intval($id)])->andWhere(['<>','status',UserProofMateria::STATUS_DEL])->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('loan-person-proof-delete'
            ,[
                'loanPersonProofInfo' => $loan_Person_Proof,
                'loanPersonProofType'=>UserProofMateria::$type,
                'list_type'=>'loan-person',
                'approve_application_id'=>0 //客服管理--待审批列表--删除照片操作（给一个空值免得报错）
            ]
        );
    }
    /**
     * @name 用户管理-用户管理-用户列表-删除照片/actionLoanPersonProofDeleteOperate
     */
    public function actionLoanPersonProofDeleteOperate()
    {
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->get();
            if (!empty($data['user_id'])&&!empty($data['proof_id'])){
                $loan_Person_Proof=UserProofMateria::find()->where(['id'=>intval($data['proof_id'])])->andWhere(['<>','status',UserProofMateria::STATUS_DEL])->all(Yii::$app->get('db_kdkj_rd'));
                if (!empty($loan_Person_Proof))
                {
                    UserProofMateria::deletePicById($data['user_id'],$data['proof_id']);
                }
                else{
                    return $this->redirectMessage('照片不存在',self::MSG_ERROR);
                }
            } else
            {
                return $this->redirectMessage('操作失败',self::MSG_ERROR);
            }
        }

    }
    /**
     * @name 用户管理-用户列表-添加渠道商
     */
    public function actionLoanChannelAdd()
    {
        $url="http://credit.kdqugou.com/act/light-loan?invite_code=";
        if ($this->getRequest()->getIsPost())
        {
            $phone=$this->request->post('phone');
            $password=$this->request->post('password');
            if (!Util::verifyPhone($phone)){
                return $this->redirectMessage('手机号码非法', self::MSG_ERROR);
            }
            $loanInfo=LoanPerson::findByPhone($phone);
            if ($loanInfo){
                return $this->redirectMessage('手机号码已注册', self::MSG_ERROR);
            }
            $res=UserService::registerByPhone($phone,$password);
            if ($res){
                return $this->redirectMessage("$url".$res['invite_code'], self::MSG_SUCCESS);
            }

        }
        return $this->render('loan-channel-add');
    }
    /**
     * @author chengyunbo
     * @date 2016-12-06
     * @name 用户管理--用户列表--重置可再借时间
     *
     **/
    public function actionCanLoanTimeUpdate($id){

        if ($this->getRequest()->getIsPost())
        {
            if ($this->request->post('submit_btn')){
                $loan_date=$this->request->post('loan_date');
                //echo $loan_date;
                //echo $loan_date;
                $loan_person = LoanPerson::find()->where(['id' => intval($id)])->one();
                if ($loan_date!=='永不再借'&&$loan_date!=='0'){
                    if ($loan_person->can_loan_time==0||$loan_person->can_loan_time>=429496729)//若之前是随时可借或者永不再借状态时，则初始日期取系统当前时间
                        $current_time = time();
                    else
                        $current_time = $loan_person->can_loan_time;
                    $loan_person->can_loan_time = $current_time+24*60*60*intval($loan_date);
                } elseif ($loan_date==='0'){//若等于0，则表示随时可再借
                    $loan_person->can_loan_time = 0;
                } elseif ($loan_date==='永不再借'){//永不再借
                    $loan_person->can_loan_time = 4294967295;
                }

                $loan_person->save();
                return $this->redirectMessage('重置可再借时间成功', self::MSG_SUCCESS, Url::toRoute(['loan/ygd-list']));
            }
        }
        for($i=0;$i<=31;$i++){
            $can_loan_date[$i] =$i;
        }
        $can_loan_date['永不再借']='永不再借';
        return $this->render('can-loan-time-update',[
            'can_loan_date'=>$can_loan_date
        ]);
    }
    /**
     * @name 用户管理--用户列表--重新绑定用户的银行卡
     */
    public function actionUserChangeCard(){
        //用户当前有没有借款
        if ($this->getRequest()->getIsPost()) {
            $data = $this->request->post();
            $id = $data['id'];
            $post_phone = trim($data['phone']);
            $post_card = trim($data['card']);
            $post_name = trim($data['name']);
            $post_id_number = trim($data['id_number']);
            //银行卡信息
            $bank= json_decode($data['bank'],true);
            $post_bank_name = $bank['val'];
            $post_bank_id = $bank['key'];

            $person = LoanPerson::find()->where([
                'id' => $id,
            ])->asArray()->one();
            $name = $person['name'];
            $id_number = $person['id_number'];
            $source_id = $person['source_id'];
            if ($post_name != $name) {
                return $this->redirectMessage('用户的姓名不对', self::MSG_ERROR);
            }
            if ($post_id_number != $id_number) {
                return $this->redirectMessage('用户的身份证不对', self::MSG_ERROR);
            }
            //查询用户是否实名
            $user_real_name = UserVerification::find()->where([
                'user_id' => $id,
            ])->one();
            if (empty($user_real_name)) {
                return $this->redirectMessage('用户没有实名', self::MSG_ERROR);
            }
            //查询改银行卡在该渠道下是否被绑定过
            $card_check = CardInfo::findOne([
                'user_id'=>$id,
                'source_id'=>$source_id,
                'card_no'=>$post_card,
            ]);
            $user_order = UserLoanOrder::find()->where([
                'user_id' => $id,
            ])->orderBy('id desc')->limit(1)->one();
            $array = [//判断用户不能取消的状态
                UserLoanOrder::STATUS_PASS,//初审
                UserLoanOrder::STATUS_PAY,//打款中
                UserLoanOrder::STATUS_LOAN_COMPLING,//已放款
                UserLoanOrder::STATUS_REPEAT_TRAIL,//待复审
                UserLoanOrder::STATUS_PENDING_LOAN,//待放款
                UserLoanOrder::STATUS_REPAYING//扣款中
            ];

            $sub_type = $data['submit_btn'];//修改银行卡信息不做是否绑定验证
            if($sub_type != '重置卡信息'){
                if($card_check){
                    return $this->redirectMessage('该卡已被绑定过', self::MSG_ERROR);
                }
                if ($user_order && in_array($user_order['status'],$array)) {
                    return $this->redirectMessage('用户有借款且订单未完成不能换卡', self::MSG_ERROR);
                }
            }
            //对比用户信息 并 四要素验证
            //验证银行卡
//            $ip = $this->request->getUserIP();
//            $card_info_soa = KoudaiSoa::instance('BankCard')->cardVerify($post_card, $post_phone, $id_number, $name, ['client_ip' => $ip]);
            $card_info_soa = JshbService::cardVerify($post_card, $post_phone, $id_number, $name ,$post_bank_id);
            if ($card_info_soa['code'] == 500) {
                return $this->redirectMessage($card_info_soa['message'], self::MSG_ERROR);
            }
            //签约绑卡
            $data = [
                // 业务参数
                'name'         => (string)$name,
                'phone'        => (string)$post_phone,
                'id_card_no'   => (string)$id_number,
                'bank_card_no' => (string)$post_card,
                'bank_id'      => (string)$post_bank_id,
            ];
            $service = Yii::$container->get('JshbService');
            $service->preSignNew($data);

            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                $card_info = new CardInfo();
                if($sub_type != '重置卡信息'){
                    $card_info->user_id = $id;
                    $card_info->bank_id = $post_bank_id;//银行id
                    $card_info->bank_name = $post_bank_name;//名称
                    $card_info->card_no = $post_card;//卡号
                    $card_info->type = CardInfo::TYPE_DEBIT_CARD;
                    $card_info->phone = $post_phone;//卡号
                    $card_info->status = CardInfo::STATUS_SUCCESS;//生效
                    $card_info->main_card = CardInfo::MAIN_CARD;//主卡
                    $card_info->created_at = time();
                    $card_info->updated_at = time();
                    $card_info->source_id = $source_id;//渠道
                    //切换之前的卡未副卡
                    $old_card_info = CardInfo::find()->where([
                        'user_id' => $id,
                        'status' => CardInfo::MAIN_CARD,
                    ])->one();
                    $old_card_info->main_card = CardInfo::MAIN_CARD_NO;
                    if ($card_info->save() && $old_card_info->save()) {
                        $transaction->commit();
                        return $this->redirectMessage('绑卡成功', self::MSG_SUCCESS, Url::toRoute('loan/ygd-list'));
                    }else{
                        $transaction->rollBack();
                        return $this->redirectMessage('绑卡失败', self::MSG_ERROR, Url::toRoute('loan/ygd-list'));
                    }
                }else{
                    $card_info = CardInfo::findOne(['user_id'=>$data['id'],'card_no'=>$data['card'],]);
                    if(empty($card_info)){
                        return $this->redirectMessage('信用卡不存在不能修改', self::MSG_SUCCESS, Url::toRoute('loan/ygd-list'));
                    }
                    $card_info->bank_id = $post_bank_id;
                    $card_info->bank_name = $post_bank_name;
                    if($card_info->save()){//修改选择卡的银行卡错误
                        $transaction->commit();
                        return $this->redirectMessage('修改绑卡数据成功', self::MSG_SUCCESS, Url::toRoute('loan/ygd-list'));
                    }else{
                        $transaction->rollBack();
                        return $this->redirectMessage('修改绑卡数据失败', self::MSG_ERROR, Url::toRoute('loan/ygd-list'));
                    }
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return $this->redirectMessage('绑卡失败', self::MSG_SUCCESS, Url::toRoute('loan/ygd-list'));
            }
        }
        //卡列表
        $id = $this->request->get('id');
        $card_list = CardInfo::$bankInfo;
        $loan_person = LoanPerson::find()->where([
            'id'=>$id,
        ])->asArray()->one();
        $old_card_info = CardInfo::find()->where([
            'user_id'=>$id,
        ])->asArray()->one();
        return $this->render('loan-card-update',[
            'card_list'=>$card_list,
            'loan_person'=>$loan_person,
            'loan_card'  =>$old_card_info,
            'id'=>$id
        ]);

    }
}
