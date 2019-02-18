<?php
namespace common\services;

use yii\base\Component;
use common\models\fund\FundAccount;
use common\models\BankConfig;
/**
 * 扣款路由服务
 */
class RouteDebitService extends Component
{
    public static function getDebitAccount(){
        return [
                FundAccount::ID_REPAY_ACCOUNT_QIANCHENG => [
                        'debit' => [//代扣通道
                                BankConfig::PLATFORM_YEEPAY,
                                BankConfig::PLATFORM_BFPAY,
                                BankConfig::PLATFORM_UMPAY,
                        ],
                        'pay' => [//支付通道
                                BankConfig::PLATFORM_FYPAY
                        ],
                ],
                FundAccount::ID_REPAY_ACCOUNT_KOUDAI => [
                        'debit' => [//代扣通道
                                BankConfig::PLATFORM_YEEPAY,
                                BankConfig::PLATFORM_BFPAY,
                                BankConfig::PLATFORM_UMPAY,
                        ],
                        'pay' => [//支付通道
                                BankConfig::PLATFORM_FYPAY
                        ],
                ],
        ];
    }
    /**
     * 扣款通道变更扣款主体
     * @param unknown $debitService
     * @param unknown $order_id
     * @throws \Exception
     */
    public static function getDebitService($debitService,$order_id){
        $account = self::getDebitAccountOwner($order_id);
        if($debitService instanceof \common\services\YeePayService){//易宝通道
            if(FundAccount::ID_REPAY_ACCOUNT_QIANCHENG == $account){
                $debitService->switchAccount(\common\services\YeePayService::AccountTypeCP_XJK);
            }else if(FundAccount::ID_REPAY_ACCOUNT_KOUDAI == $account){
                $debitService->switchAccount(\common\services\YeePayService::AccountTypeCP);
            }else{
                throw new \Exception('易宝通道暂时不支持该扣款主体');
            }
        }else if($debitService instanceof \common\services\PayService){//联动通道
            if(FundAccount::ID_REPAY_ACCOUNT_KOUDAI != $account){
                throw new \Exception('联动通道暂时只支持口袋主体');
            }
        }else if($debitService instanceof \common\services\pay\FuiouPayService){//富友通道
            if(FundAccount::ID_REPAY_ACCOUNT_QIANCHENG == $account){
                $debitService->switchAccount(\common\services\pay\FuiouPayService::ACCOUNT_XKJ);
            }else if(FundAccount::ID_REPAY_ACCOUNT_KOUDAI == $account){
                $debitService->switchAccount(\common\services\pay\FuiouPayService::ACCOUNT_KD);
            }else{
                throw new \Exception('富友通道暂时不支持该扣款主体');
            }
        }else if($debitService instanceof \common\soa\PaySoa){//宝付通道
            if(FundAccount::ID_REPAY_ACCOUNT_QIANCHENG != $account){
                //throw new \Exception('宝付通道暂时只支持潜橙主体');
            }
        }
        return $debitService;
    }
    /**
     * 获取扣款账号主体
     * @param unknown $order_id
     */
    public static $cache = [];
    public static function getDebitAccountOwner($order_id){
        if(isset(self::$cache[$order_id])){
            return self::$cache[$order_id];
        }
        $fundInfo = \common\models\fund\OrderFundInfo::getByOrderId($order_id);
        $val = FundAccount::ID_REPAY_ACCOUNT_DEFAULT;//默认口袋主体
        if($fundInfo && isset(FundAccount::$ID_REPAY_ACCOUNTS[$fundInfo->repay_account_id])){
            $val = $fundInfo->repay_account_id;
        }
        self::$cache[$order_id] = $val;
        return $val;
    }

    private static function _getBankChannelBlackList($bank_id){
        $config = \common\models\Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
        if(!$config || $config['svalue']){
            return [];
        }
        $list = @json_decode($config->svalue, true);
        if(!$list){
            $list = [];
        }
        if(isset($list[$bank_id])){
            return $list[$bank_id];
        }
        return [];
    }
    /**
     * 获取扣款通道
     *
     */
    public static function getDebitChannel($card,$order_id,$type=0,$params=[]){
        $account = self::getDebitAccountOwner($order_id);
        $allDebitChannels = self::getDebitAccount();
        if($allDebitChannels && isset($allDebitChannels[$account])){
            $blackList = self::_getBankChannelBlackList($card['bank_id']);
            $allDebitChannels = $allDebitChannels[$account];
            foreach($allDebitChannels as $key => $vals){//去除黑名和超出每日支付限制次数通道
                $limited = [];
                foreach($vals as $val){
                    try {
                        \common\models\CardInfo::checkCardDebitTimes($card['card_no'],$val);
                    } catch (\Exception $e) {//操作扣款次数，则走富友通道
                        $limited[] = $val;
                    }
                }
                $allDebitChannels[$key] = array_values(array_diff($vals, $blackList,$limited));
            }
            if($type == 1){
                if($allDebitChannels['debit']){
                    return $allDebitChannels['debit'][0];
                }
            }elseif ($type == 2){
                if($allDebitChannels['pay']){
                    return $allDebitChannels['pay'][0];
                }
            }else{
                if($allDebitChannels['debit'] && $allDebitChannels['pay']){
                    return rand(0,1) ? ['debit',$allDebitChannels['debit'][0]] : ['pay',$allDebitChannels['pay'][0]];
                }else if($allDebitChannels['debit']){
                    return ['debit',$allDebitChannels['debit'][0]];
                }else if($allDebitChannels['pay']){
                    return ['pay',$allDebitChannels['pay'][0]];
                }
            }
        }
        return null;
    }
}
