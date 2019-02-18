<?php
namespace backend\helpers\repayment;

use common\models\Financial;
use yii\base\Exception;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-8-27
 * Time: 下午6:30
 */
class repaymentFactory{

    /**
     * 路由
     * @param $type
     */
    function route($type){
        $type = intval($type);
        $obj = null;
        switch($type){
            case Financial::TYPE_ALL_REPAYMENT:
                $obj = new allRepayment();
                break;
            case Financial::TYPE_REPAYMENT_DEBX:
                $obj = new debxRepayment;
                break;
            case Financial::TYPE_REPAYMENT_MONTH:
                $obj = new monthRepayment;
                break;
            case Financial::TYPE_REPAYMENT_QUARTER:
                $obj = new quarterRepayment;
                break;
            case Financial::TYPE_REPAYMENT_HALF_YEAR:
                $obj = new halfyearRepayment;
                break;
            case Financial::TYPE_REPAYMENT_YEAR:
                $obj = new yearRepayment;
                break;
            default:
                throw new Exception("您好，未找到还款类型！");
        }
        return $obj;
    }
}
