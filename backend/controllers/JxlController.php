<?php

namespace backend\controllers;

use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\CreditJxl;
use common\models\CreditYys;
use common\models\CreditJxlLog;
use Yii;
use yii\db\Query;
use common\models\CreditMg;
use common\models\CreditMgLog;
use yii\web\Response;
use yii\data\Pagination;
use common\models\ErrorMessage;
use common\models\CreditJxlQueue;
use common\models\UserLoanOrder;
use common\helpers\Util;

/**
 * 聚信立接口
 */
class JxlController extends BaseController {
    private $client_secret = '23a9560463aa406b92a7014975388e8e';
    private $org_name = 'dichangjr';
    private $token_hours = 24;
    private $price = 0;

    /**
     * @name	获取用户的聚信立基础报告 [userRegGetCode]
     * @uses	用户注册是拉取验证码
     * @method	get
     * @param	int $id 用户编号
     * @author	baiyinliang
     */
    public function actionGetUserJxlStatus()
    {
        $person_id = Yii::$app->request->get('id');
        $loan_person = LoanPerson::findOne($person_id);
        if( ! $access_token = $this->_getAccessToken()){
            return $this->redirectMessage('访问token获取失败', self::MSG_ERROR);
        }
        $name = $loan_person['name'];
        $phone = $loan_person['phone'];
        $idcard = $loan_person['id_number'];
        $url = 'https://www.juxinli.com/api/access_report_data';
        $param = [
            'client_secret' => $this->client_secret,
            'access_token' => $access_token,
            'name' => $name,
            'phone' => $phone,
            'idcard' => $idcard,
        ];
        if( ! $result = $this->actionCurl($url,$param)){
            return $this->redirectMessage('用户数据获取失败', self::MSG_ERROR);
        }
        $log = new CreditJxlLog();
        $log->person_id = $person_id;
        $log->data = json_encode($result,JSON_UNESCAPED_UNICODE);
        if(!$log->save()){
            throw new \Exception("credit_jxl_log保存失败");
        }
        if( $result->success != 'true' ) {
            return $this->redirectMessage($result->note, self::MSG_ERROR);
        }
        $updt = strtotime($result->report_data->report->update_time);
        $token = $result->report_data->report->token;
        $credit = CreditJxl::find()->select(['id'])->where(['person_id' => $person_id])->orderBy('id Desc')->one();
        if($credit['id'] === null) {
            $credit = new CreditJxl();
        }else{
            $credit = CreditJxl::findOne($credit['id']);
        }
        $credit->person_id = $person_id;
        $credit->id_number = $idcard;
        $credit->log_id = $log->id;
        $credit->token = $token;
        $credit->data = json_encode($result->report_data,JSON_UNESCAPED_UNICODE);
        $credit->updt = $updt;
        $credit->status = 1;
        $credit->save();
        return $this->redirectMessage('数据查询成功', self::MSG_SUCCESS);
    }

    /**
     * 获取访问token
     */
    private function _getAccessToken(){
        $url = 'https://www.juxinli.com/api/access_report_token';  //接口地址
        $param = [
            'client_secret' => $this->client_secret,
            'hours' => $this->token_hours,
            'org_name' => $this->org_name,
        ];

        if ( ! $result = $this->actionCurl($url,$param)){
            return false;
        }
        if( ! $result->success){
            echo 'get access_token failed';
            echo $result->note;
            return false;
        }
        return $result->access_token;

    }

    public function actionCurl($url, $arr){
        $param = [];
        foreach($arr as $k => $v){
            $param[] = "${k}=${v}";
        }
        $param = implode('&',$param);
        $url = $url . '?' . $param;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if ( ! $result = curl_exec($curl)){
            return false;
        }
        curl_close($curl);
        $result = json_decode($result);
        return $result;
    }
    /**
     * @name 征信 -蜜罐信息/actionGetMiguanInfo
     */
    public function actionGetMiguanInfo(){
        $this->response->format = Response::FORMAT_JSON;
        $person_id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($person_id);
        if(is_null($loanPerson)){
            return [
                'code' => -1,
                'message' => '借款人不存在'
            ];
        }
        $name = $loanPerson->name;
        $idcard = $loanPerson->id_number;
        $phone = $loanPerson->phone;
        if(!$name || !$idcard || !$phone){
            return [
                'code' => -1,
                'message' => '该借款人，姓名、身份证、手机号信息不全'
            ];
        }
        $jxlService = Yii::$app->jxlService;
        $ret = $jxlService->getBadInfo($name,$idcard,$phone,$person_id);
        return $ret;

    }

    public function actionUserMiguanList()
    {
        $condition = "1 = 1 and " . LoanPerson::tableName() . ".status >= " . LoanPerson::PERSON_STATUS_NOPASS;
        if ($this->request->get('search_submit')) {        //过滤
            $search = $this->request->get();
            if (!empty($search['id'])) {
                $condition .= " AND " . LoanPerson::tableName() . ".id = " . intval($search['id']);
            }
            if (!empty($search['name'])) {
                $condition .= " AND " . LoanPerson::tableName() . ".name = " . "'" . $search['name'] . "'";
            }
            if (!empty($search['phone'])) {
                $condition .= " AND " . LoanPerson::tableName() . ".phone = " . $search['phone'];
            }
        }
        $loan_person = LoanPerson::find()->select([
            LoanPerson::tableName() . '.id',
            LoanPerson::tableName() . '.type',
            LoanPerson::tableName() . '.name',
            LoanPerson::tableName() . '.phone',
            LoanPerson::tableName() . '.property',
        ])->where($condition)->orderBy(LoanPerson::tableName() . '.id desc');
        $countQuery = clone $loan_person;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('loan-person-list', array(
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 征信管理-用户征信管理-蜜罐/actionUserView
     */
    public function actionUserView(){
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $creditMg = CreditMg::findLatestOne(['person_id'=>$id]);
        if($creditMg){
            $update_time = $creditMg->update_time;
            $data = json_decode($creditMg->data,true);
            $user_blacklist = $data['user_blacklist'];  //用户黑名单.
            $user_basic = $data['user_basic'];  //用户基本信息
            $user_gray = $data['user_gray'];    //用户灰度信息
            $user_register_orgs = $data['user_register_orgs'];  //用户注册信息情况
            $user_searched_statistic = $data['user_searched_statistic'];    //被机构查询数量（去重数据）
            $user_phone_suspicion = $data['user_phone_suspicion'];  //手机号码存疑
            $user_idcard_suspicion = $data['user_idcard_suspicion'];    //身份证号码存疑
            $user_searched_history_by_orgs = $data['user_searched_history_by_orgs'];    //用户被机构查询历史
        }else{
            $update_time = '';
            $user_blacklist = '';
            $user_basic = '';  //用户基本信息
            $user_gray = '';    //用户灰度信息
            $user_register_orgs = '';  //用户注册信息情况
            $user_searched_statistic = '';    //被机构查询数量（去重数据）
            $user_phone_suspicion = '';  //手机号码存疑
            $user_idcard_suspicion = '';    //身份证号码存疑
            $user_searched_history_by_orgs = '';    //用户被机构查询历史
        }
        return $this->render('user-view', array(
            'loanPerson' => $loanPerson,
            'update_time' => $update_time,
            'user_blacklist' => $user_blacklist,
            'user_basic' => $user_basic,
            'user_gray' => $user_gray,
            'user_register_orgs' => $user_register_orgs,
            'user_searched_statistic' => $user_searched_statistic,
            'user_phone_suspicion' => $user_phone_suspicion,
            'user_idcard_suspicion' => $user_idcard_suspicion,
            'user_searched_history_by_orgs' => $user_searched_history_by_orgs,
            'id'    => $id,
        ));
    }

    /**
     * @return string
     * @name 征信管理-用户征信管理-蜜罐-历史查询记录
     */

    public function actionOldUserView(){
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $creditMg = CreditMg::find()->where(['person_id'=>$id,'is_overdue'=>CreditMg::IS_OVERDUE_1])->asArray()->orderBy('id desc')->all();
        if(!$creditMg){
            $creditMg ='';

        }
        return $this->render('old-user-view', array(
            'loanPerson' => $loanPerson,
            'creditMg' => $creditMg,

        ));
    }




    public function actionUserList(){
        $condition = "1 = 1 and ".LoanPerson::tableName().".status >= ".LoanPerson::PERSON_STATUS_NOPASS;
        if($this->request->get('search_submit')) {        //过滤
            $search = $this->request->get();
            if(!empty($search['id'])) {
                $condition .= " AND ".LoanPerson::tableName().".id = ".intval($search['id']);
            }
            if(!empty($search['name'])) {
                $condition .= " AND ".LoanPerson::tableName().".name = "."'".$search['name']."'";
            }
            if(!empty($search['phone'])) {
                $condition .= " AND ".LoanPerson::tableName().".phone = ".$search['phone'];
            }

        }

        $loan_person = LoanPerson::find()->select([
            LoanPerson::tableName().'.id',
            LoanPerson::tableName().'.type',
            LoanPerson::tableName().'.name',
            LoanPerson::tableName().'.phone',
            LoanPerson::tableName().'.property',
            CreditJxl::tableName().'.status',
        ])->leftJoin(
            CreditJxl::tableName(),
            LoanPerson::tableName() . '.id ='.CreditJxl::tableName().'.person_id'
        )->where($condition)->orderBy(LoanPerson::tableName().'.id desc');
        $countQuery = clone $loan_person;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('user-list', array(
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }

    /**
     * @name 借款管理-用户借款管理-借款列表-查看-聚信立通讯录/actionUserReportView
     */
    public function actionUserReportView(){
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::find()->where(['id' => $id])->one();
        $type = $this->request->get('type',1);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $creditJxl = CreditJxl::findLatestOne(['person_id'=>$id]);
        $queue = CreditJxlQueue::find()->where(['user_id' => $id])->one();
        if(empty($queue)){
            $queueType = 1;
        } else {
            $queueType = $queue->type;
        }
        $data = '';
        if(!is_null($creditJxl) && !empty($creditJxl['data'])){
            $data = json_decode($creditJxl['data'],true);
            if($type == 2){
                if(!empty($data['contact_list'])){
                    $sort = [];
                    foreach($data['contact_list'] as $item){
                        $sort[$item['call_cnt']][] = $item;
                    }
                    krsort($sort);
                    $contact_list = [];
                    foreach($sort as $v){
                        foreach ($v as $j){
                            $contact_list[] = $j;
                        }
                    }
                    $data['contact_list'] = $contact_list;
                }
            }

        }
        //导出数据
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportPocketData($data);
        }
        return $this->render('user-report-view', array(
            'loanPerson' => $loanPerson,
            'creditJxl' => $creditJxl,
            'data' => $data,
            'type' => $type,
            'queueType' => $queueType,
            'id'    => $id,
        ));
    }


    /**
     * @return string
     * @name 借款管理-用户借款管理-借款列表-查看-聚信立通讯录-历史查询
     */

    public function actionOldUserReportView(){
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::find()->where(['id' => $id])->one();
        $type = $this->request->get('type',1);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $creditJxl = CreditJxl::find()->where(['person_id'=>$id,'is_overdue'=>CreditJxl::IS_OVERDUE_1])->orderBy('id desc')->all();
        return $this->render('old-user-report-view', array(
            'loanPerson' => $loanPerson,
            'creditJxl' => $creditJxl,
            'type' => $type,
        ));
    }

    private function _exportPocketData($data){
        Util::cliLimitChange(1024);
//        $check = $this->_canExportData();
        $check = true;
        if(!$check){
            return $this->redirectMessage('无权限', self::MSG_ERROR);
        }else{
            $this->_setcsvHeader('订单列表数据.csv');
            $items = [];
            foreach($data['contact_list'] as $value){
                $items[] = [
                    '手机号' => $value['phone_num'] ?? 0,
                    '通话次数'=>$value['call_cnt']??0,
                ];
            }
            echo $this->_array2csv($items);
        }
        exit;
    }
}

