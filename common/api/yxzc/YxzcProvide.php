<?php
/**
 * 与宜信至诚接口对接
 *
 */
namespace common\api\yxzc;

use common\helpers\ToolsUtil;
use common\models\JyzxApiLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\YxzcApiLog;
use yii\base\Exception;

class YxzcProvide
{

    const LOAN_TYPE = 21;
    private $private_key = '3a3cbbba635a86ff';


    //解密数据
    public function decodeResult($result)
    {
        $result = urldecode($result);
        $result = base64_decode($result);
        $result = ToolsUtil::rc4($this->private_key,$result);

        return $result;
    }

    //加密数据结果
    public function encodeResult($params)
    {
        $params = json_encode($params);
        $params = ToolsUtil::rc4($this->private_key, $params);
        $params = base64_encode($params);
        $params = urlencode($params);

        return $params;
    }

    public static function getLoanAmount($amount)
    {

        if ($amount > 0 && $amount <= 1000) {
            return '(0,1000]';
        } elseif ($amount > 1000 && $amount <= 5000) {
            return '(1000,5000]';
        } elseif ($amount > 5000 && $amount <= 10000) {
            return '(5000,10000]';
        } elseif ($amount > 10000 && $amount <= 20000) {
            return '(10000,20000]';
        } elseif ($amount > 20000 && $amount <= 50000) {
            return '(20000,50000]';
        } elseif ($amount > 50000 && $amount <= 100000) {
            return '(50000,100000]';
        } elseif ($amount > 100000) {
            return '(100000,100000+)';
        }


    }

    public static function getResultCode($status)
    {
        if (in_array($status, [UserLoanOrder::STATUS_CHECK, 1])) {
            return '201';
        } elseif (in_array($status, [UserLoanOrder::STATUS_PENDING_LOAN,UserLoanOrder::STATUS_PAY,UserLoanOrder::STATUS_LOAN_COMPLETE,UserLoanOrder::STATUS_LOAN_COMPLING,UserLoanOrder::STATUS_REPAYING,UserLoanOrder::STATUS_REPAYING,
            UserLoanOrder::STATUS_PARTIALREPAYMENT,UserLoanOrder::STATUS_REPAY_COMPLETE])) {
            return '202';
        } elseif (in_array($status,[UserLoanOrder::STATUS_CANCEL,UserLoanOrder::STATUS_REPEAT_CANCEL]) ) {
            return '203';
        }else{
            return '202';
        }

    }

    public static function getStatusCode($status, $days)
    {
        if ($status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            return '303';
        }
        if ($days > 10) {
            return '302';
        } else {
            if ($status == UserLoanOrderRepayment::STATUS_OVERDUE) {
                return '302';
            } else {
                return '301';
            }
        }
    }

    public static function getOverdueStatus($day)
    {
        if ($day == 0) {
            return '';
        } else {
            $m = $day / 30;
            if ($m < 1) {
                return "";
            } elseif ($m >= 1 && $m < 2) {
                return 'M1';
            } elseif ($m >= 2 && $m < 3) {
                return 'M2';
            } elseif ($m >= 3 && $m < 4) {
                return 'M3';
            } elseif ($m >= 4 && $m < 5) {
                return 'M4';
            } elseif ($m >= 5 && $m < 6) {
                return 'M5';
            } elseif ($m >= 6 & $m < 7) {
                return 'M6';
            } else {
                return 'M6+';
            }
        }
    }

    public static function addlog($data)
    {

        $yxzcLog = new YxzcApiLog();

        $yxzcLog->code = 201;
        $yxzcLog->name = $data['data']['name'] ? $data['data']['name']: "";
        $yxzcLog->message = '查询';
        $yxzcLog->id_number = $data['data']['idNo'] ?$data['data']['idNo']: "";
        $yxzcLog->created_at = time();
        $yxzcLog->save();

    }
    public static function addJyLog($data,$status)
    {
        $jyzxLog = new JyzxApiLog();
        $jyzxLog->code = 3001;
        $jyzxLog->name = $data['realName'];
        $jyzxLog->message = '查询';
        $jyzxLog->id_number = $data['idCard'];
        $jyzxLog->company_code = $data['companyCode'];
        $jyzxLog->status = $status;
        $jyzxLog->created_at = time();
        $jyzxLog->save();
    }
}