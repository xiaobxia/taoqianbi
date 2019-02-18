<?php
/**
 * Created by PhpStorm.
 * User: byl
 * Date: 2017/3/3
 * Time: 19:03
 */

namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\api\br\Config;
use common\api\br\Core;
use common\models\CreditBr;
use common\models\CreditBrLog;

//require_once Yii::getAlias('@common/api/br/config.php');

/**
 * 百融接口
 */
class BrService extends Component
{
    private $account, $password, $apicode;

    protected $loanPerson = null;
    protected $data = null;
    protected $huaxiang = null;
    protected $type,$arr_json;

    public function __construct() {
        parent::__construct();
        $this->account = Yii::$app->params['br']['account'];
        $this->password = Yii::$app->params['br']['password'];
        $this->apicode = Yii::$app->params['br']['apicode'];
    }

    //获取特殊名单列表
    public function getBrInfo($loanPerson,$type,$br_json = array()){
        $request = array();
        if ($type != CreditBr::REGISTER_EQUIPMENT && $type != CreditBr::SIGN_EQUIPMENT){
            $request['name'] = $loanPerson->name;
            $request['cell'] = $loanPerson->phone;
            $request['id'] = $loanPerson->id_number;
        }elseif ($type == CreditBr::REGISTER_EQUIPMENT){
            $request['cell'] = $loanPerson->phone;
        }

        if (!empty($br_json)){
            $request['event'] = $br_json['event'];
            $request['af_swift_number'] = $br_json['af_swift_number'];
        }
        switch($type){
            case CreditBr::SPECIAL_LIST:
                $huaxiang = ['SpecialList_c'];
                break;
            case CreditBr::APPLY_LOAN_STR:
                $huaxiang = ['ApplyLoanStr'];
                break;
            case CreditBr::REGISTER_EQUIPMENT:
                $huaxiang = ['RegisterEquipment'];
                break;
            case CreditBr::SIGN_EQUIPMENT:
                $huaxiang = ['SignEquipment'];
                break;
            case CreditBr::LOAN_EQUIPMENT:
                $huaxiang = ['LoanEquipment','EquipmentCheck'];
                break;
            default:
                return [
                    'code' => -1,
                    'message' => '未知的产品类型'
                ];
                break;
        }
        $this->loanPerson = $loanPerson;
        $this->type = $type;
        $headerTitle = ['huaxiang' => $huaxiang];
        $core = Core::getInstance($this->account, $this->password, $this->apicode, Config::$querys);
        if (!($core->pushTargetList($request,$type))){
            throw new Exception("百融数据缺少必要参数，user_id：{$loanPerson->id} type：{$type} ".print_r($request,true),3003);
        }
        $arr_json = $core->mapping($headerTitle);
        $this->arr_json = $arr_json;
        $arr = json_decode($arr_json,true);
        if (is_null($arr)){
            throw new Exception("百融获取数据失败，user_id：{$loanPerson->id} type：{$type}",3003);
        }
        if($arr['code'] != '00' && $arr['code'] != '100002'){ // 00 => success   100002 => success with empty matches
            throw new Exception("百融错误信息，user_id：{$loanPerson->id} type：{$type} ".print_r($arr,true),3003);
        }
        $result = $this->saveData();
        return $result;
    }

    public function saveData(){
        $loanPerson = $this->loanPerson;
        $type = $this->type;
        $arr_json = $this->arr_json;
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $credit_br_log = new CreditBrLog();
            $credit_br_log->person_id = $loanPerson->id;
            $credit_br_log->price = 0;
            $credit_br_log->type = $type;
            $credit_br_log->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
            if(!$credit_br_log->save()){
                throw new Exception("credit_br_log保存失败");
            }
            $credit_br = CreditBr::find()->where(['person_id'=>$loanPerson->id,'type'=>$type])->one();
            if(!$credit_br){
                $credit_br =  new CreditBr();
                $credit_br->person_id = $loanPerson->id;
            }
            $credit_br->id_number = $loanPerson->id_number;
            $credit_br->data = $arr_json;
            $credit_br->type = $type;
            if(!$credit_br->save()){
                throw new Exception("credit_br保存失败");
            }
            $transaction->commit();
            return true;
        }catch (Exception $e){
            $transaction->rollback();
            Yii::error($e);
            throw $e;
        }
    }
}
