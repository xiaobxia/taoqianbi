<?php
namespace common\services\risk_control;

use common\models\credit_line\PhoneCreditLog;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Component;
use common\services\credit_line\CreditLineService;
use common\models\UserLoanOrderRepayment;
use common\services\CreditCheckService;

class CreditService extends Component
{
    const NORMAL_CREDIT_MONEY = 1000;
    const RULE_ID = 268;

    public function __construct(){

    }

    /**
     * 复贷用户的额度
     * @param $mobile
     * @return array
     *
     */
    public function getDuplicateCreditLine($mobile, $again = 0){
            $code_1 = ['code'=>-1, 'message'=>'获取用户数据失败'];
            $code_2 = ['code'=>-2, 'message'=>'手机号格式不正确'];
            $code_3 = ['code'=>-3, 'message'=>'获取金额数据失败'];
            if(empty($mobile)){
                $this->addLog($mobile, $code_1);
                return $code_1;
            }elseif(!preg_match("/^1[34578]{1}\d{9}$/",$mobile)){
                $this->addLog($mobile, $code_2);
                return $code_2;
            };

            $credit_money = 0;
            $is_new = 0;

            //判断是否是新用户
            $loan_person = LoanPerson::findOne(['phone'=>$mobile]);
            if(false === $loan_person){
                $this->addLog($mobile, $code_1);
                return $code_1;
            }

            if(empty($loan_person)){
                $credit_money = self::NORMAL_CREDIT_MONEY;
                $is_new = 1;
            }else{
                //查询是否下单过
                $user_id = $loan_person->id;
                $user_loan_order = UserLoanOrder::findOne(['user_id'=>$user_id]);
                if(false === $user_loan_order){
                    $this->addLog($mobile, $code_1);
                    return $code_1;
                }
                if(empty($user_loan_order)){
                    $credit_money = self::NORMAL_CREDIT_MONEY;
                }else{
                    $rule_id = self::RULE_ID;
                    $credit = new CreditLineService();
                    $result = $credit->createCreditLine($loan_person,$rule_id);
                    if(isset($result['credit_line'])){
                        $credit_money = $result['credit_line'];
                    }else{
                        $this->addLog($mobile, $code_3);
                        $again_run = $again+1;
                        if($again_run == 1){ //如果第一次请求失败，再请求一次
                            return $this->getDuplicateCreditLine($mobile, 1);
                        }else{
                            return $code_3;
                        }
                    }
                }
            }

            $code_4= [
                'code'=>0,
                'message'=>'success',
                'data'=>[
                    'credit_money'=>$credit_money,
                    'is_new'=>$is_new?:'0'
                ]
            ];
            $this->addLog($mobile, $code_4);
            return $code_4;
    }

    /**
     * 根据电话号码增加额度日志
     * @param $mobile
     * @param $code
     */
    public function addLog($mobile, $code){
        $result= PhoneCreditLog::getDb()->createCommand()
            ->insert(PhoneCreditLog::tableName(),[
                'phone'=>$mobile,
                'code'=>$code['code'],
                'message'=>$code['message'],
                'data'=> !empty($code['data']) ? json_encode($code['data']) : '',
                'created_at'=>time(),
                'updated_at'=>time()
            ]);
        return $result->execute();

    }

    /**
     * 判断是否为老客户
     * @return 1为老客户
     */
    public function checkRegular($loan_person_id,$product_id,$order_id){
        $loanPerson = LoanPerson::findById($loan_person_id);
        $credit = new CreditCheckService();
        $result = $credit->checkRegular($loanPerson, $product_id, $order_id);
        return $result;
    }

}