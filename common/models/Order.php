<?php

namespace common\models;

use common\services\FinancialService;
use yii\behaviors\TimestampBehavior;
use common\helpers\StringHelper;

/**
 * This is the model class for table "{{%order}}".
 */
class Order extends \yii\db\ActiveRecord
{
	const TYPE_INVEST_KDB = 1;
	const TYPE_INVEST_PROJ = 2;
	const TYPE_WITHDRAW = 3;
	const TYPE_CREDIT_CARD = 4;

	const STATUS_NEW = 0;		// 初始创建
	const STATUS_HANDING = 1;	// 处理中
	const STATUS_SUCCESS = 2;	// 处理成功
	const STATUS_FAILED = 3;	// 处理失败
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order}}';
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
    	return [
    		TimestampBehavior::className(),
    	];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
    	return [
    		['order_id', 'unique', 'message' => '订单号不能重复'],
    	];
    }
    
    /**
     * 判断是否可以提交处理，避免重复提交
     */
    public function getCanCommit()
    {
    	if ($this->status == self::STATUS_HANDING || $this->status == self::STATUS_SUCCESS) {
    		return false;
    	} else {
    		return true;
    	}
    }
    
    /**
     * 生成订单号
     */
    public static function generateOrderId($platform = 0, $user_id = 0)
    {
    	$uniqid = StringHelper::generateUniqid();
    	if($platform  == BankConfig::PLATFORM_UMPAY) {//联动优势
    	    return substr(md5($uniqid), 8, 16);
    	}
    	if (\Yii::$app instanceof \yii\web\Application && !\Yii::$app->user->getIsGuest()) {
            $uid = empty($user_id) ? \Yii::$app->user->identity->id : $user_id;
            switch($platform){
                case BankConfig::PLATFORM_99PAY:
                    $order_id = date('Ymd') . $uid . "{$uniqid}";
                    break;
                case BankConfig::PLATFORM_BFPAY:
                    $uniqid = substr($uniqid, -6);
                    $uid = sprintf("%08d", $uid);
                    $order_id = date('ymd') . $uid . "{$uniqid}";
                    break;
                default:
                    $uniqid = "_" . $uniqid;
                    $order_id = date('Ymd') . $uid . "{$uniqid}";
            }
    	} else {
    		$order_id = date('Ymd') . "_{$uniqid}";
    	}
    	return $order_id;
    }

    /**
     * 生成订单号 16 位
     */
    public static function generateOrderId16()
    {
        $uniqid = self::generateOrderId();
        $order_id = substr( md5($uniqid), 8, 16 );
        return $order_id;
    }
    
    /**
     * 生成20位纯数字订单号码
     * @return string
     */
    public static function generateOrderId20() {
    	$time = microtime(true) * 10000;
    	$date = date('ymdHis', substr($time, 0, -4));
    	$micro_second = substr($time, -4);
    	$rand = rand(1000, 9999);
    	$no   = $date . $micro_second . $rand;
    	return $no;
    }
    
    /**
     * 验证签名
     * @param array $params
     * @param string $sign
     * @return boolean
     */
    public static function validateSign($params, $sign)
    {
        /*
    	$key = '**kdlc**';
    	unset($params['sign']);
    	$signStr = http_build_query($params) . $key;
    	return base64_encode($signStr) == $sign;
        */
        return self::getSign($params) == $sign;
    }

    /**
     * 获得签名
     * @param array $params
     * @param string $sign
     * @return boolean
     */
    public static function getSign($params)
    {
        $key = '**kdlc**';
        unset($params['sign']);
        ksort($params);
        $signStr = http_build_query($params) . $key;
        return base64_encode($signStr);
    }
    /**
     * 获得签名
     * @param array $params
     * @param string $sign
     * @return boolean
     */
    public static function getSignNew($params,$merchant_id)
    {
        $key = '**kdpay_'.$merchant_id.'**';
        unset($params['sign']);
        ksort($params);
        $signStr = http_build_query($params) . $key;
        return base64_encode($signStr);
    }
    /**
     * 对外提供接口 新的 获取请求参数
     * @author jellywen
     * @DateTime 2016-12-24T12:33:43+0800
     * @param    [string]                  $key    签名key(项目名称)
     * @param    [array]                   $params 需要签名的参数
     * @return   [string]                           签名
     */
    public static function getPaySign($params, $key){
        $key = 'kdlc**'.$key.'**pay';
        unset($params['sign']);
        ksort($params);
        $signStr = http_build_query($params) . $key;
        return base64_encode($signStr);
    }

    /**
     * 对外提供接口 新的 验证签名
     * @author jellywen
     * @DateTime 2016-12-24T15:27:15+0800
     * @param    [array]                  $params 请求参数
     * @param    [string]                 $sign   参数签名
     * @param    [string]                 $key    项目名
     * @return   boolean                          是否校验通过
     */
    public static function validatePaySign($params, $sign, $key){
        return self::getPaySign($params, $key) == $sign;
    }
    /**
     * 对外提供接口 打款新签名
     * @author jellywen
     * @DateTime 2016-12-24T12:33:43+0800
     * @param    [string]                  $key    签名key(项目名称)
     * @param    [array]                   $params 需要签名的参数
     * @return   [string]                           签名
     */
    public static function getParamsSign($params, $key="**kdlc**"){
        // $key = '**kdlc**';
        unset($params['sign']);
        ksort($params);
        $signStr = http_build_query($params) . $key;
        return base64_encode($signStr);
    }
    /**
     * 对外提供接口 打款新验证签名
     * @author jellywen
     * @DateTime 2016-12-24T15:27:15+0800
     * @param    [array]                  $params 请求参数
     * @param    [string]                 $sign   参数签名
     * @param    [string]                 $key    项目名
     * @return   boolean                          是否校验通过
     */
    public static function validateParamsSign($params, $sign, $merchant_id){
        $key = '**kdpay_'.$merchant_id.'**';
        return self::getParamsSign($params, $key) == $sign;
    }

    /**
     * 对外提供接口 温州贷验证签名
     * @author zhangyuliang
     * @DateTime 2016-12-24T15:27:15+0800
     * @param    [array]                  $params 请求参数
     * @param    [string]                 $sign   参数签名
     * @param    [string]                 $key    项目名
     * @return   boolean                          是否校验通过
     */
    public static function validateWzdParamsSign($params, $sign){
        $priateKey = '**wzdai_api_private_sign**'; //私密
        ksort($params);//对传递的数据按照键值排序
        unset($params['sign']);
        $signStr = http_build_query($params).$priateKey; //将参数url编码后拼接上私密
        $newSign = base64_encode(strtoupper(md5($signStr))); //md5 加密后进行base64编码
        return $newSign == $sign;
    }


    /**
     * 对外提供接口 汇潮支付签名生成
     * @author
     * @DateTime 2016-12-24T15:27:15+0800
     * @param    [array]                  $params 请求参数
     * @param    [string]                 $sign   参数签名
     * @param    [string]                 $key    项目名
     * @return   boolean                          是否校验通过
     */

    public static function genHcSign($params,$key=FinancialService::KD_HC_KEY){
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $value) {
            $signStr .= $k.'='.$value.'&';
        }
        $stringsignTemp = $signStr.'key='.$key;
        //echo $stringsignTemp;exit;
        $sign = md5($stringsignTemp);
        return $sign;
    }

    /**
     * 对外提供接口 验证汇潮支付签名
     * @param $params
     * @param $sign
     * @param string $key
     * @return bool
     */
    public static function validateHcSign($params,$sign,$key=FinancialService::KD_HC_KEY) {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $value) {
            $signStr .= $k.'='.$value.'&';
        }
        $stringsignTemp = $signStr.'key='.$key;
        $sign = md5($stringsignTemp);
        if ($sign === $sign) {
            return true;
        } else {
            return false;
        }
    }






}

