<?php
namespace backend\controllers;

use common\models\asset\AssetOrder;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Exception;
use yii\web\Response;
use common\models\LoanPerson;
use common\models\CreditBr;
use common\api\br\Brlist;

class BrController extends BaseController
{
    protected $SpecialList_c = 1;
    protected $ApplyLoanStr = 2;
    /**
     * @return array
     * @name 征信管理-用户征信管理-用户征信管理-百融-点击查询最新数据/actionGetInfo
     */
    public function actionGetInfo(){
        try{
            $this->response->format = Response::FORMAT_JSON;
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if(is_null($loanPerson)){
                throw new Exception('用户不存在');
            }
            $service = Yii::$container->get('brService');
            $type = intval($this->request->get('type'));
            $result = $service->getBrInfo($loanPerson,$type);
            if ($result == true){
                return [
                    'code'=>0,
                    'message'=>'获取成功'
                ];
            }else{
                return [
                    'code'=>-1,
                    'message'=>'获取失败'
                ];
            }
        }catch(Exception $e){
            return [
                'code' => -1,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-百融/actionView
     */
    public function actionView(){
        $product_id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($product_id);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $list = CreditBr::find()->where(['person_id'=>$loanPerson->id])->asArray()->all();
        $res = array();
        $Brlist = new Brlist;
        foreach($list as $key => $val){
            switch($val['type']){
                case CreditBr::SPECIAL_LIST:
                    $res['special_list'] = $Brlist->BrList($val['data'],$val['type']);
                    break;
                case CreditBr::APPLY_LOAN_STR:
                    $res['apply_loan_str'] = $Brlist->BrList($val['data'],$val['type']);
                    break;
                case CreditBr::REGISTER_EQUIPMENT:
                    $res['register_equipment'] = $Brlist->BrList($val['data'],$val['type']);
                    break;
                case CreditBr::SIGN_EQUIPMENT:
                    $res['sign_equipment'] = $Brlist->BrList($val['data'],$val['type']);
                    break;
                case CreditBr::LOAN_EQUIPMENT:
                    $res['loan_equipment'] = $Brlist->BrList($val['data'],$val['type']);
                    $res['equipment_check'] = $Brlist->BrList($val['data'],CreditBr::EQUIPMENT_CHECK);
                    break;
            }
        }
        return $this->render('view', array(
            'info' => [
                'loanPerson' => $loanPerson,
                'res' => $res
            ]
        ));
    }

}