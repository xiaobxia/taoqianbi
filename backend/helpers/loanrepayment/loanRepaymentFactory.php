<?php
namespace backend\helpers\loanrepayment;

use common\models\LoanRecordPeriod;
use common\models\LoanRepaymentPeriod;
use yii\base\Exception;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-8-27
 * Time: 下午6:30
 */
class loanRepaymentFactory{

    /**
     * 路由
     * @param $type
     */
    function route($type){
        $type = intval($type);
        $obj = null;
        switch($type){
            //一次性还款
            case LoanRecordPeriod::REPAY_TYPE_ALL:
                $obj = new allRepayment();
                break;
            //按月付息
            case LoanRecordPeriod::REPAY_TYPE_MONTH:
                $obj = new monthRepayment();
                break;
            //按季付息
            case LoanRecordPeriod::REPAY_TYPE_AJFX:
                $obj = new ajfxRepayment();
                break;
            //按半年付息
            case LoanRecordPeriod::REPAY_TYPE_ABNFX:
                $obj = new abnfxRepayment();
                break;
            //按年付息
            case LoanRecordPeriod::REPAY_TYPE_ANFX:
                $obj = new anfxRepayment();
                break;
            //等额本息
            case LoanRecordPeriod::REPAY_TYPE_DEBX:
                $obj = new debxRepayment();
                break;
            //等本等息
            case LoanRecordPeriod::REPAY_TYPE_DBDX:
                $obj = new dbdxRepayment();
                break;
            //先息后本
            case LoanRecordPeriod::REPAY_TYPE_XXHB:
                $obj = new xxhbRepayment();
                break;
            default:
                throw new Exception("您好，未找到还款类型！");
        }
        return $obj;
    }
}
