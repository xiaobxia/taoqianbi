<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/7/17
 * Time: 16:19
 */

namespace common\helpers;


use common\models\BankConfig;
use common\models\UserWithdraw;
use common\services\LLPayService;
use common\services\YeePayService;
use Yii;
use common\services\PayService;

class UserPayHelper
{
    /**
     *连连绑卡常量
     */
    const LL_BIND_CARD = "LL_BIND_CARD";
    const LD_BIND_CARD = "LD_BIND_CARD";


    /**
     * 计算支付手续费
     * @param $mer_id           商户号
     * @param $third_platform   第三方支付平台（联动，连连）
     * @param $pay_amount       支付金额
     * @return string
     * @author hezhuangzhuang@koudailc.com
     */
    public static function calPayFee($mer_id, $third_platform, $pay_amount, $pay_src='')
    {

        if ($third_platform == BankConfig::PLATFORM_UMPAY) {
            // 联动支付
            switch ($mer_id) {
                case PayService::LDPay_OID_PARTNER :
                    return sprintf("%.2f", 2);
                    break;
                default:
                    return "未知的商户号";
            }
        } elseif ($third_platform == BankConfig::PLATFORM_LLPAY) {
            // 连连支付
//             switch ($mer_id) {
//                 case LLPayService::LLPAY_OID_PARTNER :
//                     if ($pay_amount <= 50000) {
//                         return sprintf("%.2f", 1);
//                     } else {
//                         $feePer = 0.002;
//                         return round($pay_amount * $feePer / 100, 2);
//                     }
//                     break;
//                case LLPayService::LLPAY_XLY_OID_PARTNER :
                   if($pay_src == 'web') {
                       $pay_fee = round($pay_amount * 0.0012 / 100, 2);
                       return $pay_fee = $pay_fee <= 0.1 ? 0.1 : $pay_fee;
                   }
                   else {//app
                       $pay_fee = round($pay_amount * 0.002 / 100, 2);
                       return $pay_fee = $pay_fee <= 1 ? 1 : $pay_fee;
                   }
//                    break;
//                 case LLPayService::LLPAY_WEB_OID_PARTNER :
//                     $feePer = 0.0026;
//                     return round($pay_amount * $feePer / 100, 2);
//                     break;
//                 case LLPayService::LLPAY_WAP_OID_PARTNER :
//                     if ($pay_amount <= 50000) {
//                         return sprintf("%.2f", 1);
//                     } else {
//                         $feePer = 0.002;
//                         return round($pay_amount * $feePer / 100, 2);
//                     }
//                     break;
//                 case self::LL_BIND_CARD :
//                     return sprintf("%.2f", 1);//连连绑卡手续费1元
//                     break;
//                 default:
//                     return "未知的商户号";
//             }
        } elseif ($third_platform == BankConfig::PLATFORM_YEEPAY) {
            // 易宝支付
            switch ($mer_id) {
                case YeePayService::MerchantAccount :
                    //$feePer = 0.0014;//口袋理财
                    $feePer = 0.0018;//口袋快借这边值
                    return round($pay_amount * $feePer / 100, 2);
                    break;
                default:
                    return "未知的商户号";
            }
        } elseif ($third_platform == BankConfig::PLATFORM_UMPAY4) {
            // 新联动优势支付
            switch ($mer_id) {
                case 9364:
                    if ($pay_amount <= 66600) {
                        return sprintf("%.2f", 2);
                    } elseif(($pay_amount > 66600) && ($pay_amount <= 1666600)) {
                        $feePer = 0.003;
                        return round($pay_amount * $feePer / 100, 2);
                    }elseif($pay_amount > 1666600){
                        return sprintf("%.2f", 50);
                    }else{
                        return "待计算";
                    }
                    break;
                case self::LD_BIND_CARD:
                    return sprintf("%.2f", 2);//新连动绑卡手续费1元
                    break;
                default:
                    if ($pay_amount <= 66600) {
                        return sprintf("%.2f", 2);
                    } elseif(($pay_amount > 66600) && ($pay_amount <= 1666600)) {
                        $feePer = 0.003;
                        return round($pay_amount * $feePer / 100, 2);
                    }elseif($pay_amount > 1666600){
                        return sprintf("%.2f", 50);
                    }else{
                        return "待计算";
                    }
                    break;
            }
        } else if($third_platform == BankConfig::PLATFORM_99PAY) {
            $feePer = 0.0025;
            $fee = $pay_amount * $feePer;
            $fee = $fee < 200 ? 200 : $fee; //手续费最低2元
            
            return round($fee / 100, 2);
            
        } else if($third_platform == BankConfig::PLATFORM_BFPAY) {
            if($pay_src == 'web') {
                $feePer = 0.001;
                $fee = $pay_amount * $feePer;
            }
            else {//app
                $feePer = 0.001;
                $fee = $pay_amount * $feePer;
                $fee = $fee < 100 ? 100 : $fee; //手续费最低2元
            }
            return round($fee / 100, 2);
        } else if($third_platform == BankConfig::PLATFORM_BYPAY) {
            $feePer = 0.001;
            if($pay_amount >= 5000000) {//手续费最高50元
                $fee = 5000;//手续费最高50元
            } else {
                $fee = $pay_amount * $feePer;
                $fee = $fee < 100 ? 100 : $fee; //手续费最低1元
            }
            return round($fee / 100, 2);
        }
        else if($third_platform == BankConfig::PLATFORM_FYPAY) {
            $feePer = 0.001;
            
            $fee = $pay_amount * $feePer;
            $fee = $fee < 200 ? 200 : $fee; //手续费最低2元
            
            return round($fee / 100, 2);
        }
        else if($third_platform == BankConfig::PLATFORM_JYTPAY) {
            $feePer = 0.001;
            
            $fee = $pay_amount * $feePer;
            $fee = $fee < 200 ? 200 : $fee; //手续费最低2元
            $fee = $fee > 1000 ? 1000 : $fee; //手续费最低2元
            
            return round($fee / 100, 2);
        }
        else {
            return "待计算";
        }
    }
    
    /**
     * 消费类手续费
     * 
     * @param int $amount
     * @param int $platform
     */
    public static function consumePayFee($amount, $platform) {
        if($platform == BankConfig::PLATFORM_LLPAY) {
            $fee = floatval(bcmul($amount, 0.004, 2));
            $fee = $fee < 10 ? 10 : $fee;     //手续费最低1毛
            
            return intval(round($fee));
        }
        
    }
    

    /**
     * 获取商户号
     * @param $pay_result       第三方平台回复的支付结果
     * @param $third_platform   第三方支付平台（联动、连连）
     * @return string
     * @author hezhuangzhuang@koudailc.com
     */
    public static function getMerId($pay_result, $third_platform) {
        if (!empty($pay_result)) {
            switch($third_platform) {
                case BankConfig::PLATFORM_UMPAY:
                    $arg  = "mer_id";
                    break;
                case BankConfig::PLATFORM_LLPAY:
                    if(strval($pay_result) == "连连绑卡支付1元"){
                        return self::LL_BIND_CARD;
                    }
                    $arg  = "oid_partner";
                    break;
                case BankConfig::PLATFORM_YEEPAY:
                    $arg = "merchantaccount";
                    break;
                case BankConfig::PLATFORM_UMPAY4:
                    if(strval($pay_result) == "联动绑卡支付1元"){
                        return self::LD_BIND_CARD;
                    }
                    $bank_number = 9364;
                    return $bank_number;
                    break;
                default:
                    return "";
            }
            $json_str = json_decode($pay_result, true);
            if (!empty($json_str[$arg])) {
                return $json_str[$arg];
            }
        }
        return "";
    }


    /**
     * 获取打款渠道(兼容以前版本)
     * @param $payment_type     打款类型
     * @param $review_result    审核结果
     * @param $third_platform   第三方支付平台
     * @param  int $type        提现类型
     * @return int
     * @author hezhuangzhuang@koudailc.com
     */
    public static function getPaymentType($payment_type, $review_result, $third_platform, $type=0) {
        switch ($review_result) {
            case UserWithdraw::REVIEW_STATUS_NO:
                return 0;
            case UserWithdraw::REVIEW_STATUS_APPROVE:
                if ($payment_type == 0) {
                    if($type == UserWithdraw::TYPE_FAST || $type == UserWithdraw::TYPE_CREDIT) {
                        return UserWithdraw::PAYMENT_TYPE_CMB;
                    } else {
                        switch($third_platform) {
                            case BankConfig::PLATFORM_UMPAY:
                                return UserWithdraw::PAYMENT_TYPE_LD;
                            case BankConfig::PLATFORM_LLPAY:
                                return UserWithdraw::PAYMENT_TYPE_LL;
                            case BankConfig::PLATFORM_YEEPAY:
                                return UserWithdraw::PAYMENT_TYPE_YEE;
                            case BankConfig::PLATFORM_FYPAY:
                                return UserWithdraw::PAYMENT_TYPE_FUIOU;
                            default:
                                return 0;
                        }
                    }
                } else {
                    return $payment_type;
                }
            case UserWithdraw::REVIEW_STATUS_REJECT:
                return 0;
            case UserWithdraw::REVIEW_STATUS_MANUAL:
                return UserWithdraw::PAYMENT_TYPE_MANUAL;
            case UserWithdraw::REVIEW_STATUS_NORMAL:
                return UserWithdraw::PAYMENT_TYPE_LL;
            case UserWithdraw::REVIEW_STATUS_FAST:
                return UserWithdraw::PAYMENT_TYPE_CMB;
            case UserWithdraw::REVIEW_STATUS_CMB_FAILED:
                return UserWithdraw::PAYMENT_TYPE_CMB;
            case UserWithdraw::REVIEW_STATUS_FORCE_TO_LL:
                return UserWithdraw::PAYMENT_TYPE_LL;
            case UserWithdraw::REVIEW_STATUS_FORCE_TO_YEE:
                return UserWithdraw::PAYMENT_TYPE_YEE;
            case UserWithdraw::REVIEW_STATUS_FORCE_TO_FUIOU:
                return UserWithdraw::PAYMENT_TYPE_FUIOU;
            default:
                return 0;
        }
    }

    /**
     * 获取默认打款渠道
     * @param $type             提现类型
     * @param $third_platform   第三方支付平台
     * @param $bank_name        银行名称
     * @return int               打款渠道编号（0表示未知打款渠道）
     * @author hezhuangzhuang@koudailc.com
     */
    public static function getDefaultPaymentType($type, $third_platform, $bank_name){
        switch ($type) {
            case UserWithdraw::TYPE_NORMAL:
                switch ($third_platform) {
                    case BankConfig::PLATFORM_UMPAY:
                        return UserWithdraw::PAYMENT_TYPE_LD;
                    case BankConfig::PLATFORM_LLPAY:
                        if ($bank_name == "招商银行") {
                            return UserWithdraw::PAYMENT_TYPE_CMB;
                        } else {
                            return UserWithdraw::PAYMENT_TYPE_LL;
                        }
                    case BankConfig::PLATFORM_YEEPAY:
                        return UserWithdraw::PAYMENT_TYPE_YEE;
                    default:
                        return 0;
                }
            case UserWithdraw::TYPE_FAST:
                return UserWithdraw::PAYMENT_TYPE_CMB;
            case UserWithdraw::TYPE_KDB_NORMAL:
                return 0;
            default:
                return 0;
        }
    }


}