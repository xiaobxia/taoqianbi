<?php
namespace backend\controllers;

use common\models\CreditSauron;
use Yii;
use yii\web\Response;
use common\models\LoanPerson;


class HuluController extends BaseController
{

    /**
     * @return array
     * @name 征信 --葫芦/actionGetReportId;
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
        $result = Yii::$container->get('huluService')->getBadInfo($loanPerson->name, $loanPerson->id_number, $loanPerson->phone, $id);
        if($result['code'] == 0){
            return [
                'code'=>0,
                'message'=>'提交成功，请更新用户信息'
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>$result['message']
            ];
        }

    }

    /**
     * @return string
     * @name 征信管理 -用户征信管理-用户征信管理-葫芦/actionUserView
     */
    public function actionUserView()
    {
        $id = intval($this->request->get('id'));

        $creditTd = CreditSauron::find()->where(['person_id' => $id])->asArray()->orderBy('id desc')->one();
        $loanPerson = LoanPerson::find()->where(['id' => $id])->asArray()->orderBy('id desc')->one();
        $data = '';
        if (!empty($creditTd['data'])) {
            $data = json_decode($creditTd['data'], true);
        }

        return $this->render('user-view', array(
            'info' => $creditTd,
            'loanPerson' => $loanPerson,
            'data' => $data,
            'id' => $id,
        ));
    }
}