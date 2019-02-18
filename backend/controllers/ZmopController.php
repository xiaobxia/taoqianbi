<?php
namespace backend\controllers;

use Yii;
use yii\web\Response;
use yii\data\Pagination;
use common\models\CreditZmop;
use common\models\CreditZmopLog;
use common\models\LoanPerson;
use common\models\UserVerification;
use common\models\ErrorMessage;
use common\models\UserCreditOnoff;
use common\models\loan\UserCompany;
class ZmopController extends BaseController
{
    //单条数据价格
    public $price = 0;

    /**
     * @return array
     * @name 芝麻信用信息获取接口
     */
    public function actionGetZmopInfo(){
        $this->response->format = Response::FORMAT_JSON;
        $loan_person_id =  intval(Yii::$app->request->get("id"));
        $loan_person = LoanPerson::findOne($loan_person_id);
        $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$loan_person_id]);
        if(is_null($loan_person)){
            return [
                'code' => -1,
                'message' => '借款人不存在'
            ];
        }
        if(empty($creditZmop) || $creditZmop['status'] != CreditZmop::STATUS_1){
            return [
                'code' => -1,
                'message' => '此借款人芝麻信用未授权'
            ];
        }
        // $zmopService = new ZmopService();
        $zmopService = Yii::$container->get('zmopService');
        $zmopService->setAppId($creditZmop->app_id);
        $open_id = $creditZmop['open_id'];
        $phone = $loan_person['phone'];
        $id_number = $loan_person['id_number'];
        //根据获取类型，获取不同的芝麻信用产品
        $type = intval($this->request->get('type'));
        switch($type){
            case CreditZmop::ZM_TYPE_SCORE:
                $result = $zmopService->getScore($open_id);
                $type = CreditZmopLog::PRODUCT_ZM;
                break;
            case CreditZmop::ZM_TYPE_RAIN:
                $result = $zmopService->getRain($phone);
                $type = CreditZmopLog::PRODUCT_RAIN;
                break;
            case CreditZmop::ZM_TYPE_WATCH:
                $result = $zmopService->getWatch($open_id);
                $type = CreditZmopLog::PRODUCT_WATCH;
                break;
            case CreditZmop::ZM_TYPE_IVS:
                $result = $zmopService->getIvs($phone,$id_number);
                $type = CreditZmopLog::PRODUCT_IVS;
                break;
            case CreditZmop::ZM_TYPE_DAS:
                $result = $zmopService->getDas($open_id,$phone);
                $type = CreditZmopLog::PRODUCT_DAS;
                break;
            default:
                return [
                    'code' => -1,
                    'message' => '未知的产品类型'
                ];
                break;
        }
        if( ! $result['success']) {
            if ($result['error_code'] == 'ZMCREDIT.authentication_fail') {
                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    $userVerification = UserVerification::find()->where(['user_id'=>$loan_person_id])->one();
                    $userVerification->real_zmxy_status = 1;
                    $ret =$userVerification->save();
                    if(!$ret){
                        throw new \Exception('用户步骤验证表保存失败');
                    }
                    $creditZmop->status = CreditZmop::STATUS_2;
                    $ret = $creditZmop->save();
                    if(!$ret){
                        throw new \Exception('芝麻信用表保存失败');
                    }
                    $transaction->commit();
                }catch(\Exception $e){
                    $transaction->rollBack();
                    return [
                        'code' => -1,
                        'message' => '用户取消授权，请联系管理员'
                    ];
                }

            }
            return [
                'code' => -1,
                'message' => $result['error_message']
            ];
        }else{
            $product_code = $zmopService->product_code;
            $this->_insertZmopLog($result, $type, $loan_person);
            $this->_updateZmop($result, $product_code, $loan_person);

            return [
                'code' => 0,
                'message' => '数据获取成功'
            ];
        }
    }

    /**
     * @return string
     * @name 征信管理-用户征信管理/actionUserZmopList
     */
    public function actionUserZmopList(){
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
        ])->where($condition)->orderBy(LoanPerson::tableName().'.id desc');

        $countQuery = clone $loan_person;
        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
        }, 3600);
        
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('loan-person-list', array(
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }


    /**
     * @return string
     * @name 信用管理-用户信用管理-芝麻信用/actionUserZmopView
     */
    public function actionUserZmopView(){
        $id = intval($this->request->get('id'));
        $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$id]);
        $loanPerson = LoanPerson::find()->where(['id'=>$id])->asArray()->one();
         if(is_null($loanPerson)){
             return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
         }
        if(!empty($creditZmop)){
            $zmScoreTime = CreditZmopLog::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_ZM])->orderBy('id desc')->one();
            $zmScoreTime = $zmScoreTime ? date('Y-m-d H:i:s',$zmScoreTime->created_at) : '';
            $rainTime = CreditZmopLog::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_RAIN])->orderBy('id desc')->one();
            $rainTime = $rainTime ? date('Y-m-d H:i:s',$rainTime->created_at) : '';
            $ivsTime = CreditZmopLog::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_IVS])->orderBy('id desc')->one();
            $ivsTime = $ivsTime ? date('Y-m-d H:i:s',$ivsTime->created_at) : '';
            $watchTime = CreditZmopLog::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_WATCH])->orderBy('id desc')->one();
            $watchTime = $watchTime ? date('Y-m-d H:i:s',$watchTime->created_at) : '';
            $dasTime = CreditZmopLog::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_DAS])->orderBy('id desc')->one();
            $dasTime = $dasTime ? date('Y-m-d H:i:s',$dasTime->created_at) : '';
        }else{
            $zmScoreTime = '';
            $rainTime = '';
            $ivsTime = '';
            $watchTime = '';
            $dasTime = '';
        }

        return $this->render('user-zmop-view',array(
            'info' => $creditZmop,
            'loanPerson' => $loanPerson,
            'zmScoreTime' => $zmScoreTime,
            'rainTime' => $rainTime,
            'ivsTime' => $ivsTime,
            'watchTime' => $watchTime,
            'dasTime' => $dasTime,
            'id'    =>$id,
        ));
    }



    /**
     * @return string
     * @name 信用管理-用户信用管理-芝麻信用-历史查询展示
     */
    public function actionOldUserZmopView(){

        $id = intval($this->request->get('id'));
        $creditZmop = creditZmop::find()->where(['person_id'=>$id,'type'=>CreditZmopLog::PRODUCT_ZM,'is_overdue'=>creditZmop::IS_OVERDUE_1])->asArray()->orderBy('id desc')->all();
        $loanPerson = LoanPerson::find()->where(['id'=>$id])->asArray()->one();
        // if(empty($loanPerson)){
        //     return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        // }

        return $this->render('old-user-zmop-view',array(
                'creditZmop' => $creditZmop,
                'loanPerson'=>$loanPerson,
            ));
    }

    /**
     * 插入芝麻信用日志表
     */
    private function _insertZmopLog($arr, $type, $loan_person){
        $admin_username = Yii::$app->user->identity->username;
        $loan_person_zmop = new CreditZmopLog();
        $loan_person_zmop->person_id = $loan_person['id'];
        $loan_person_zmop->biz_no = $arr['biz_no'];
        $loan_person_zmop->type = $type;
        $loan_person_zmop->price = $this->price;
        $loan_person_zmop->admin_username = $admin_username;
        return $loan_person_zmop->save();
    }

    /**
     * 更新芝麻信用记录表
     */
    private function _updateZmop($arr, $product_code, $loan_person ){

        $credit_zmop = CreditZmop::gainCreditZmopLatest(['person_id' => $loan_person['id']]);

        $credit_zmop->status = CreditZmop::STATUS_1;
        switch ($product_code){
            case 'w1010100100000000001':            //芝麻积分
                $credit_zmop->zm_score = $arr['zm_score'];
                break;
            case 'w1010100000000000105':            //rain积分
                $credit_zmop->rain_score = $arr['rain_score'];
                $credit_zmop->rain_info = json_encode($arr['info_codes']);
                break;
            case 'w1010100100000000022':            //watch
                $watch_matched = $arr['is_matched'];
                if( $watch_matched ){
                    $credit_zmop->watch_info = json_encode($arr['details']);
                    $credit_zmop->watch_matched = 2;
                }else{
                    $credit_zmop->watch_matched = 1;
                }
                break;
            case 'w1010100000000000103':            //ivs
                $credit_zmop->ivs_score = $arr['ivs_score'];
                $credit_zmop->ivs_info = json_encode($arr['ivs_detail']);
                break;
            case 'w1010100200000000001':            //das
                $credit_zmop->das_info = json_encode($arr['vars']);
                break;
        }
        return $credit_zmop->save();
    }


    /**
     * 用户短信授权
     * @param integer $id 用户id
     */
    public function actionBatchFeedback(){
        $this->response->format = Response::FORMAT_JSON;
        $id = intval(Yii::$app->request->get("id"));
        $loan_person = LoanPerson::find()->where(['id' => $id])->one();
        if(empty($loan_person)){
            return [
                'code' => -1,
                'message' => '借款人不存在'
            ];
        }
        $zmopService = Yii::$container->get('zmopService');
        $arr = $zmopService->batchFeedback($loan_person['name'],$loan_person['id_number'],$loan_person['phone'],$id);
        if($arr['success'] == true && $arr['biz_success'] == true){
            return [
                'code' => 0,
                'message' => '发送成功'
            ];
        }else{
            return [
                'code' => -1,
                'message' => $arr['error_message']
            ];
        }
    }

    /**
     * @name 错误信息--信息列表/actionErrorMessageList
     */
    public function actionErrorMessageList(){
        if ($this->request->get('submitcsv') == 'exportcsv') {
            return $this->_exportAdminWorkInfos('id','desc');
        }

        $request = Yii::$app->request;
        if($request->isPost){
            $id=$request->post('id');
            if($id){
                $error_message=ErrorMessage::findOne($id);
                if($error_message){
                    $error_message->status=ErrorMessage::STATUS_SUCCESS;
                    if($error_message->save()){
                        return json_encode(['result'=>1]);
                    }else{
                        return json_encode(['result'=>0]);
                    }
                }
            }
        }

        $where = $this->getAdminWorkListFilter();
        $query = ErrorMessage::find()->where($where)->orderBy('id desc');
        $count = clone $query;
        $pages = New Pagination(['totalCount'=> $count->count()]);
        $pages->pageSize=20;
        $message = $query->offset($pages->offset)->limit($pages->limit)->all();
        $outsid = UserCompany::lists();
        $outsideinfo = [];
        foreach ($outsid as $lue) {
            $outsideinfo[$lue['id']] = $lue['title'];
        }

        return $this->render('error-message-list', array(
            'message' => $message,
            'pages' => $pages
        ));
    }
    private function _exportAdminWorkInfos($sort_key='id',$sort_type='desc'){
        if ($sort_type == 'desc') {
            $sort = [$sort_key=>SORT_DESC];
        }else{
            $sort = [$sort_key=>SORT_ASC];
        }
        $this->_setcsvHeader('接口错误.csv');
        $where = $this->getAdminWorkListFilter();
        $datas = ErrorMessage::find()->where($where)->orderBy($sort)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $outsid = UserCompany::lists();
        $outsideinfo = [];
        foreach ($outsid as $lue) {
            $outsideinfo[$lue['id']] = $lue['title'];
        }
        $items = [];
        foreach($datas as $value){
            $items[] = [
                'ID' => $value['id'],
                '用户ID' => $value['user_id'],
                '错误信息' => $value['message'],
                '错误类型' => ErrorMessage::$source[$value['error_source']],
                '错误时间' => date('Y-m-d H:i:s', $value['error_time']),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }
    //接口错误  过滤条件
    private function getAdminWorkListFilter()
    {
        $add_start = \strtotime($this->request->get('add_start'));
        $add_end = \strtotime($this->request->get('add_end') . "+1 day");
        $source = $this->request->get('error_source', -1);
        $user_id = $this->request->get('user_id');
        $status = $this->request->get('status');
        $where = '1=1';
        if ($add_start) {
            $where = $where . ' and error_time>="' . $add_start . '"';
        } else {
            $add_start = time() - 7 * 24 * 3600;
            $where = $where . ' and error_time >= ' . $add_start;
        }
        if ($add_end) {
            $where = $where . ' and error_time <= ' . $add_end;
        } else {
            $where = $where . ' and error_time <= ' . time();
        }
        if($user_id!=''&&!empty($user_id)){
            $where = $where . ' and user_id = ' . $user_id;
        }
        if ($source != -1) {
            $where = $where . ' and error_source="' . $source . '"';
        }
        if (!empty($status)&&$status!='') {
            $where = $where . ' and status="' . $status . '"';
        }

        return $where;
    }
    /**
     * @return array
     * @name 用户征信开关
     */
    public function actionUserCreditOnoff()
    {
        $userCreditOnoff = UserCreditOnoff::find();
        $countQuery = clone $userCreditOnoff;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $userCreditOnoff = $userCreditOnoff->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('user-credit-onoff',
                ['userCreditOnoff' => $userCreditOnoff,'pages' => $pages]
            );
    }

    /**
     * @return [type] [description]
     * @name 添加征信开关管理
     */
    public function actionUserCreditOnoffAdd()
    {
        $model = new UserCreditOnoff();
        return $this->render('user-credit-onoff-add',
                ['model' => $model,'operate_name'=>'添加']
            );
    }

    /**
     * @return [type] [description]
     * @name 添加征信开关管理
     */
    public function actionDoAddUserCreditOnoff()
    {
        $model = new UserCreditOnoff();
        $params = Yii::$app->request->post();
        if ($model->load($params))
        {
            if($model->validate() && $model->save())
            {
                return $this->redirect(['user-credit-onoff']);
            }
            else
            {
                return $this->redirect(['error','message'=>'数据验证失败']);
            }
        }
        return $this->redirect(['error','message'=>'数据加载失败']);
    }

    /**
     * @return [type] [description]
     * @name 错误页面
     */
    public function actionError()
    {
        $message = Yii::$app->request->get('message');
        return $this->render('error',['message'=>$message]);
    }

    /**
     * @return [type] [description]
     * @name 修改征信开关管理
     */
    public function actionUserCreditOnoffUpdate()
    {
        $model = UserCreditOnoff::find()->where(['id'=>Yii::$app->request->get('id')])->one();
        return $this->render('user-credit-onoff-add',
                ['model' => $model,'operate_name'=>'修改']
            );
    }

    /**
     * @return [type] [description]
     * @name 删除征信开关
     */
    public function actionUserCreditOnoffDelete()
    {
        $model = UserCreditOnoff::find()->where(['id'=>Yii::$app->request->get('id')])->one();
        if($model)
        {
            $model->delete();
        }
        return $this->redirect(['user-credit-onoff']);
    }

    /**
     * @return [type] [description]
     * @name 执行修改征信开关管理
     */
    public function actionDoUpdateUserCreditOnoff()
    {
        $params = Yii::$app->request->post();
        if(isset($params['UserCreditOnoff']['id']) && intval($params['UserCreditOnoff']['id']) > 0)
        {
            $model = UserCreditOnoff::findOne((int)$params['UserCreditOnoff']['id']);
            if ($model && $model->load($params))
            {
                if($model->validate() && $model->save())
                {
                    return $this->redirect(['user-credit-onoff']);
                }
                else
                {
                    return $this->redirect(['error','message'=>'数据验证失败']);
                }
            }
            return $this->redirect(['error','message'=>'数据加载失败']);
        }
        else
        {
            return $this->redirect(['error','message'=>'缺少必要的参数']);
        }

    }
}