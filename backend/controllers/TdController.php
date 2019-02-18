<?php
namespace backend\controllers;

use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Response;
use yii\data\Pagination;
use common\models\CreditTd;
use common\models\LoanPerson;
use common\helpers\StringHelper;
use yii\db\Query;
use common\models\ErrorMessage;


class TdController extends BaseController
{
    /**
     * @return array
     * @name 征信 --同盾/actionGetReportId;
     */
    public function actionGetReportId($id){
        $this->response->format = Response::FORMAT_JSON;
        $id = intval($id);
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return [
                'code' => -1,
                'message'=>'用户不存在'
            ];
        }
        $service = Yii::$app->tdService->getReportId($loanPerson);
        if($service->getResult()){
            return [
                'code'=>0,
                'message'=>'提交成功，请更新用户信息'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>$service->getMessage()
            ];
        }

    }

    /**
     * @return array
     * @name 征信 --同盾/actionGetInfo;
     */
    public function actionGetInfo(){
        $this->response->format = Response::FORMAT_JSON;
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return [
                'code' => -1,
                'message'=>'用户不存在'
            ];
        }
        $service = Yii::$app->tdService->getReportContent($loanPerson);
        if($service->getResult()){
            return [
                'code'=>0,
                'message'=>'获取成功'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>$service->getMessage()
            ];
        }
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
            'id', 'type', 'name', 'phone', 'property',
        ])->where($condition)->orderBy(LoanPerson::tableName().'.id desc');
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
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-同盾/actionUserView
     */
    public function actionUserView(){
        $id = intval($this->request->get('id'));
        $where = [
            'person_id'=>$id
        ];
        $creditTd = CreditTd::find()->where(['person_id'=>$id])->asArray()->orderBy('id desc')->one();
        $loanPerson = LoanPerson::find()->where(['id'=>$id])->asArray()->orderBy('id desc')->one();
        $data = '';
        if(!empty($creditTd['data'])){
            $data = json_decode($creditTd['data'],true);
        }
        return $this->render('user-view',array(
            'info' => $creditTd,
            'loanPerson' => $loanPerson,
            'data'=>$data,
            'id' => $id,
        ));
    }


    /**
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-同盾-历史查询
     */
    public function actionOldUserView(){
        $id = intval($this->request->get('id'));
        $creditTd = CreditTd::find()->where(['person_id'=>$id,'is_overdue'=>creditTd::IS_OVERDUE_1])->asArray()->orderBy('id desc')->all();
        $loanPerson = LoanPerson::find()->where(['id'=>$id])->asArray()->one();
        return $this->render('old-user-view',array(
            'creditTd' => $creditTd,
            'loanPerson' => $loanPerson,
        ));
    }

}