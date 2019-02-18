<?php

namespace console\controllers;

use common\models\CreditJsqb;
use common\models\CreditYx;
use common\services\WLService;
use common\services\Yxservice;
use Yii;
use common\api\RedisQueue;
use common\helpers\CurlHelper;
use common\helpers\MailHelper;
use common\helpers\MessageHelper;
use common\models\Channel;
use common\models\CreditBqs;
use common\models\CreditBr;
use common\models\CreditJxl;
use common\models\CreditJxlQueue;
use common\models\CreditMg;
use common\models\LoanPerson;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\Rule;
use common\models\UserContact;
use common\models\UserCreditData;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\UserVerification;
use common\models\WeixinUser;
use common\services\CreditCheckService;
use common\services\fundChannel\JshbService;
use common\services\JxlService;
use common\services\RiskControlCheckService;
use common\services\RiskControlService;
use console\soa\UserLoanOrder;
use Exception;
use yii\base\ErrorException;
use common\models\UserCreditDetail;

class SupplementController extends BaseController{

    /**
     * 计算额度 补充脚本
     */
    public function actionEdu(){

        $time = time() - 2400;

        $where='credit_status = 1 AND credit_total < 5  and created_at < '.$time;

        $list = UserCreditDetail::find()->where($where)->asArray()->all();

        if($list){

            foreach($list as $v){

                $credit = UserCreditDetail::findOne(['id'=>$v['id']]);

                $credit->credit_total = $credit->credit_total + 1 ;

                if($credit->save() && RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $v['user_id']])){
                    echo 'ID '.$v['id'].'  更改成功   '."\r\n";
                }else{
                    echo 'ID '.$v['id'].'  更改失败   '."\r\n";
                }

            }
        }
    }


}