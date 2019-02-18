<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/12
 * Time: 11:01
 */
namespace common\helpers;

use common\models\UserCreditTotal;
use common\helpers\Util;

class IdGeneratorHelper{


    /**
     * 手机信用卡卡号生成器
     */
    public static function genertaor_card_no(){

        for($i = 1; $i<=10;$i++){
            $id = "5178".rand(1000,9999)."".rand(1000,9999)."".rand(1000,9999);
            $user_credit_total = UserCreditTotal::find(['card_no'=>$id]);
            if(empty($user_credit_total)){
                break;
            }
        }

        return $id;
    }

    /**
     * 多卡产品卡号生成规则
     */
    public static function gen_card_no($phone,$prex='6226'){
        if (!Util::verifyPhone($phone)) {
            $phone = sprintf("%s%s%s",mt_rand(100,999),mt_rand(1000,9999),mt_rand(1000,9999));
        }
        return sprintf("%s0%s",$prex,$phone);
    }
}

