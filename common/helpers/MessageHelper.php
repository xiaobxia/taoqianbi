<?php

namespace common\helpers;

use common\models\LoanPerson;
use Yii;
use common\api\RedisQueue;
use common\base\LogChannel;

class MessageHelper {

	const TYPE_MOBILE = 'mobile'; // 移动
	const TYPE_UNICOM = 'unicom'; // 联通
	const TYPE_TELECOM = 'telecom'; // 电信
	const TYPE_PATTERNS = [
//        self::TYPE_MOBILE    => '/^1(3[4-9]|5[012789]|8[23478]|4[7]|7[8])\d{8}$/', // 匹配移动手机号
//        self::TYPE_UNICOM    => '/^1(3[0-2]|5[56]|8[56]|4[5]|7[6])\d{8}$/', // 匹配联通手机号
//        self::TYPE_TELECOM   => '/^1(3[3])|(8[019])\d{8}$/', // 匹配电信手机号

		# http://blog.csdn.net/chinaplan/article/details/8862060
		self::TYPE_MOBILE    => '/^1(3[4-9]|4[7]|5[012789]|7[8]|8[23478])\d{8}$/', // 移动
		self::TYPE_UNICOM    => '/^1(3[0-2]|4[5]|5[56]|7[56]|8[56])\d{8}$/', // 联通
		self::TYPE_TELECOM   => '/^1([35]3|7[0137]|8[019])\d{8}$/', // 电信
	];
	# 145 联通数据卡; 147 移动数据卡; 170/171 虚拟运营商;
	# 移动178卡; 移动182卡; 移动183卡;
	# 联通175卡; 联通176卡;
	# 电信173卡; 电信177卡; 181 电信CDMA卡;

	// 联通130  131  132  145  155  156  176  185  186
	// 电信133  153  177  180  181  189
	// 移动134  135  136  137  138  139  147  150  151  152  157 158  159  178  182  183  184  187  188
    // 新增加了170 171 手机号段

	// 所有短信接口超时时间
	public static $timeout = 5;
	public static $ctx_params = array(
		'http' => array(
			'timeout' => 5
		)
	);

	//目前可用的通道
	static $channels = [
//		'smsServiceXQB_XiAo',
		'smsService_TianChang_HY',
		//'smsService_TianChang_CS',
		'smsService_TianChang_TZ',
		'smsService_TianChang_SXD',
		'smsService_TianChang_SXD_YX',
		'smsService_TianChang_WZD',
		'smsService_TianChang_WZD_YX',
		'smsService_TianChang_HBQB',
		'smsService_TianChang_HBQB_YX',
		'smsService_TianChang_JZGJ',
		'smsService_TianChang_JZGJ_YX',
		'smsService_TianChang_KDJZ',
		'smsService_TianChang_KDJZ_YX',
		'smsService_MengWang',
		'smsService_MengWang_CS',
		'smsService_MengWang_Repayment',
		'smsService_MengWang_WZDRepayment',
		'smsService_WeiWang_YX',
		'smsService_WeiWang_CS',
	];

	//权重配置
	static $channelsAll = [
		'YZM' => [
			// 'smsServiceXQB_XiAo_KDJZ',
			// 'smsServiceXQB_XiAo_JBGJ',
			// 'smsServiceXQB_XiAo_HBQB',
			// 'smsServiceXQB_XiAo_WZD',
			'smsServiceXQB_XiAo' => [
				'weight' => 10
			],
			'smsService_ChuangLan' => [
				'weight' => 0
			],
			'smsService_CongYu' => [
				'weight' => 0
			],
			'smsService_MengWang' => [
				'weight' => 0
			],
		],
		'YX' => [
			'smsServiceXQB_XiAo_YX' => [
				'weight' => 10
			],
			'smsService_ChuangLan_YX' => [
				'weight' => 10
			],
		],
		'CS' => [
			'smsService_MengWang_CS' => [
				'weight' => 10
			],
		],
	];

	static $channel_default = 'smsService_TianChang_HY'; #验证码短信默认通道 smsServiceXQB_XiAo
	static $channel_yx_default = 'smsServiceXQB_XiAo_YX'; #营销短信默认通道

	/**
	 * 获取手机号类型
	 * @param $phone
	 *
	 * @return bool|int|string
	 */
	static function getType($phone) {
		if (strlen($phone) != 11) {
			return false;
		}

		foreach(self::TYPE_PATTERNS as $type => $pattern) {
			if (preg_match($pattern, $phone)) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * 发送内部短信，仅供开发调试用
	 */
	public static function sendInternalSms($phone, $msg) {
		$first = self::_sendTianChang('smsService_TianChang_HY', $phone, $msg, false);
		if ($first) {
			return true;
		}

//		$second = self::_sendCongYu('smsService_CongYu', $phone, $msg, false);
//		if ($second) {
//			return true;
//		}

		MailHelper::sendQueueMail(sprintf('[%s]sendInternalSms_failed(%s): %s', date('ymd H:i:s'), $phone, $msg), '', NOTICE_MAIL);
		return false;
	}

	/**
	 * 发送短信
	 * @param int $phone 手机号
	 * @param string $message 信息
	 * @param string $smsServiceUse 渠道名 smsService_TianChang_HY smsServiceXQB_XiAo
	 * @param string $boolUseBack 使用备用通道？
	 * @param string $returnService
	 * @return boolean
	 */
	public static function sendSMS($phone, $message, $smsServiceUse = 'smsService_TianChang_HY', $source_id = '', $boolUseBack=true, &$returnService = '') {
		try {
			if (empty($source_id)) {
				$source_id = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;

				//记录异常
				$key =  'message_helper_sendsms';
				if (!Yii::$app->cache->get($key)) {
					Yii::info( sprintf('sendSMS_empty_source_id: %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)));
					Yii::$app->cache->set($key, 1, 300);
				}
			}

			switch($smsServiceUse) {
//                case 'smsService_DaHan_TZ':
//                    return self::_sendDaHan($smsServiceUse, $phone, $message, $boolUseBack);
//                case 'smsService_DaHan_YZM':
//                    return self::_sendDaHan($smsServiceUse, $phone, $message, $boolUseBack);

				case 'smsServiceXQB_XiAo':
					return self::_sendXiAo($smsServiceUse, $phone, $message, $boolUseBack);

//                case 'smsService_MengWang':
//                    return self::_sendMengWang($smsServiceUse, $phone, $message, $boolUseBack);

				case 'smsService_TianChang_HY':
//                case 'smsService_TianChang_XY':
				default:
					return self::_sendTianChang($smsServiceUse, $phone, $message, $boolUseBack);
			}
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('sendSMS_exception %s: %s', $phone, $e), LogChannel::SMS_GENERAL);
		}

		return false;
	}

	/**
	 * 发送短信 希奥除了极速荷包签名的营销短信
	 * @param int $phone 手机号
	 * @param string $message 信息
	 * @param string $smsServiceUse 渠道名
	 * @param string $boolUseBack 使用备用通道？
	 * @param string $returnService
	 * @return boolean
	 */
	public static function sendSMSOther($phone, $message, $smsServiceUse = 'smsServiceXQB_XiAo_YX_Other', $source_id = '', $boolUseBack=true, &$returnService = '') {
		try {
			if (empty($source_id)) {
				$source_id = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
				//记录异常
				$key =  'message_helper_sendsms';
				if (!Yii::$app->cache->get($key)) {
					Yii::warning( sprintf('sendSMS_empty_source_id: %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)));
					Yii::$app->cache->set($key, 1, 300);
				}
			}
			new LoanPerson();
			$sign = '【'.LoanPerson::$person_source[$source_id].'】';
			$message = $sign.$message;
			$returnService = $smsServiceUse;
			return self::_sendXiAo($smsServiceUse, $phone, $message, $boolUseBack);
		} catch (\Exception $e) {
			Yii::error(\sprintf('sendSMS_exception %s: %s', $phone, $e), LogChannel::SMS_GENERAL);
			return false;
		}
	}


	/**
	 * 发送行业短信
	 * 天畅
	 * @param int $phone 手机号
	 */
	public static function sendSMSHY($phone, $message, $smsServiceUse = 'smsService_TianChang_HY', $source_id = '', $boolUseBack = true)
	{
		return self::sendAll($phone, $message, $smsServiceUse, $source_id, $boolUseBack);
	}


	/**
	 * 发送营销短信
	 * 创蓝 速盾  微网
	 * @param int $phone 手机号
	 */
	public static function sendSMSYX($phone, $message, $smsServiceUse = 'smsService_YunFeng', $source_id = '', $boolUseBack = true)
	{
		return self::sendAll($phone, $message, $smsServiceUse, $source_id, $boolUseBack);
	}

	/**
	 * 发送短信 | 催收
	 * 天畅 梦网 微网
	 * smsService_TianChang
	 */
	public static function sendSMSCS($phone, $message, $smsServiceUse = 'smsService_MengWang_CS', $source_id = '', $boolUseBack = true)
	{
		return self::sendAll($phone, $message, $smsServiceUse, $source_id, $boolUseBack);
	}

	/**
	 * [sendAll 发送短信总方法]
	 * 催收 | 天畅 梦网
	 * 营销 | 创蓝 速盾
	 * @param int $phone 手机号
	 * @param string $message 信息
	 * @param string $smsServiceUse 渠道名
	 * @param string $boolUseBack 使用备用通道？
	 * @param string $returnService
	 * @return boolean
	 */
	public static function sendAll($phone, $message, $smsServiceUse = 'smsService_TianChang_HY', $source_id ='', $boolUseBack = true) {
		$sms_name = isset(LoanPerson::$channel_msg_list[$source_id])
			? LoanPerson::$channel_msg_list[$source_id]
			: LoanPerson::$channel_msg_list[LoanPerson::PERSON_SOURCE_MOBILE_CREDIT];

		if (!isset(Yii::$app->params[$smsServiceUse])) {
			throw new \Exception("未在params下找到:{$smsServiceUse}对应配置。", 3000);
		}
		if (!isset(explode('_', $smsServiceUse)[1])) {
			throw new \Exception("格式配置错误，请按照对应格式配置:{$smsServiceUse}", 3001);
		}

		$className = explode('_', $smsServiceUse)[1];
		$path = "\common\helpers\messages";
		$class = "{$path}\\{$className}Sms";
		if (!class_exists($class)) {
			throw new \Exception("请在:{$class}下实现对应短信实现类。", 3002);
		}
		try {
			if (empty($source_id)) {
				$source_id = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
			}
			new LoanPerson();
			$name = isset(LoanPerson::$person_source[$source_id])
				? LoanPerson::$person_source[$source_id]
				: LoanPerson::$person_source[LoanPerson::PERSON_SOURCE_MOBILE_CREDIT];

			$params = Yii::$app->params[$smsServiceUse];
			$extArr = $params;
			$model = new $class($params['url'], $params['account'], $params['password'], $extArr, $smsServiceUse);
			return $model->sendSMS($phone, $message, $name);
		}
		catch (\Exception $ex) {
			Yii::error(\sprintf('send %s, and the exception is %s', $phone, $ex->getMessage()), LogChannel::SMS_GENERAL);
			return false;
		}
	}

	/**
	 * 是否验证码类型
	 * @param unknown $message
	 */
	private static function _isCaptcha($message){
		return preg_match('/^(您的验证码为:)/', $message);
	}

	/*
	* 大汉   验证码
	*/
	private static function _sendDaHan($smsServiceUse, $phone, $message, $boolUseBack) {
		return true;
		try {
			$class  = '\common\helpers\messages\DaHanSms';
			$params = Yii::$app->params[$smsServiceUse];
			$extArr = $params;

			$model = new $class($params['url'], $params['account'], $params['password'], $extArr, $smsServiceUse);
			$result = $model->sendSMS($phone, $message, APP_NAMES);

			$result = json_decode($result,true);
			if($result['result'] == 0){
				return true;
			}
			Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		}catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}
		return false;
	}

	/*
	* 发短信 http://www.sioo.com.cn/APIdoc/smssend.html
	* @param string $smsServiceUse
	* @param int $phone
	* @param string $message
	* @param bool $boolUseBack
	* @return boolean
	*/
	private static function _sendXiAo($smsServiceUse, $phone, $message, $boolUseBack) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$msg = urlencode($message);
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['uid'];
		$auth = md5($smsServiceParams['code'] . $smsServiceParams['password']);
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?uid={$uid}&auth={$auth}&mobile={$phone}&msg={$msg}&expid=0&encode=utf-8", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		// 返回值要是0这种格式才成功，后面是短信id
		if ($result && strpos($result, ',') !== false) {
			list($resCode, $resMsg) = \explode(',', $result);
			if ($resCode == '0') {
				return $resMsg;
			}
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse.'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse.'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);

		return false;
	}

	/*
	* 梦网
	* @param string $smsServiceUse
	* @param int $phone
	* @param string $message
	* @param bool $boolUseBack
	* @return boolean
	*/
	private static function _sendMengWang($smsServiceUse, $phone, $message, $boolUseBack) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$msg = urlencode($message);
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?userId={$uid}&password={$pwd}&pszMobis={$phone}&pszMsg={$msg}&iMobiCount=1&pszSubPort=*", false, $ctx);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}
		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result[0]) && $result[0] > 0) {
			return $result[0];
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse.'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse.'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}

	/*
	* 天畅
	* @param string $smsServiceUse
	* @param int $phone
	* @param string $message
	* @param bool $boolUseBack
	* @return boolean
	*/
	private static function _sendTianChang($smsServiceUse, $phone, $message, $boolUseBack) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$msg = urlencode($message);
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?un={$uid}&pw={$pwd}&da={$phone}&sm={$msg}&dc=15&tf=3&rf=2", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		$ret = json_decode($result,true);
		if ($ret['success']) {
			return true;
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse.'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse.'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}

	/**
	 * 聪裕科技短信渠道
	 * @param string $smsServiceUse
	 * @param integer $phone
	 * @param string $message
	 * @param boolean $boolUseBack
	 * @return boolean
	 */
	private static function _sendCongYu($smsServiceUse, $phone, $message, $boolUseBack, $name = APP_NAMES) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['url'];
		$user_id = $smsServiceParams['userid'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];
		$timestamp = date('YmdHis');
		$sign = MessageHelper::_createCongYuSign($account, $password, $timestamp);
		$prefix_message = '【'.$name.'】 ';
		$suffix_message = ($smsServiceUse == 'smsService_CongYu_YX') ? ', 退订回N' : '';

		$post_data = [
			'userid' => $user_id,
			'timestamp' => $timestamp,
			'sign' => $sign,
			'mobile' => $phone,
			'content' => $prefix_message . $message . $suffix_message,
			'action' => 'send',
			'extno' => '',     // 扩展子号
			'sendTime' => '', // 定时发送 格式：2017-04-28 09:08:10
		];

		$xml_result = '';
		try {
			$xml_result = MessageHelper::_post($url, $post_data);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		$result = simplexml_load_string($xml_result);
		if ($result !== false) {
			$result_arr = (array)$result;
			if (isset($result_arr['returnstatus']) && $result_arr['returnstatus'] == 'Success') {
				return true;
			}
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse . 'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse . 'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$xml_result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}

	/*
	* 速盾
	* @param string $smsServiceUse
	* @param int $phone
	* @param string $message
	* @param bool $boolUseBack
	* @return boolean
	*/
	private static function _sendSuDun($smsServiceUse, $phone, $message, $boolUseBack) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		// $msg = urlencode($message);
		$msg = $message;
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['uid'];
		$account = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$result = '';
		try {
			$data = ['mobile' => $phone, 'content' => $msg];
			$query = http_build_query($data);
			$post = [
				'http' => [
					'timeout' => 5,
					'method' => 'POST',
					'header' => 'Content-type:application/x-www-form-urlencoded',
					'content' => $query
				]
			];
			$ctx = stream_context_create($post);
			// echo "{$url}&userid={$uid}&account={$account}&password={$pwd}&mobile={$phone}&content={$msg}";die;//&mobile={$phone}&content={$msg}
			$result = \file_get_contents("{$url}&userid={$uid}&account={$account}&password={$pwd}", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result['returnstatus']) == 'Success') {
			return true;
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse.'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse.'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}


	/*
	* 沃淘
	* @param string $smsServiceUse
	* @param int $phone
	* @param string $message
	* @param bool $boolUseBack
	* @return boolean
	*/
	private static function _sendWoTao($smsServiceUse, $phone, $message, $boolUseBack) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		// $msg = urlencode($message);
		$msg = $message;
		$url = $smsServiceParams['url'];
		$account = $smsServiceParams['account'];
		$pwd = md5($smsServiceParams['password']);
		$smstype = $smsServiceParams['smstype'];
		$result = '';
		try {
			$msg = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>" . "<MtMessage><content>" . $msg . "</content><phoneNumber>" . $phone . "</phoneNumber><subCode></subCode></MtMessage>";
			$data = [
				'message' => $msg,
				'account' => $account,
				'password' => $pwd,
				'smsType' => $smstype,
			];

			$query = http_build_query($data);

			$post = [
				'http' => [
					'timeout' => 5,
					'method' => 'POST',
					'header' => 'Content-type:application/x-www-form-urlencoded',
					'content' => $query
				]
			];

			$ctx = stream_context_create($post);
			$result = \file_get_contents("{$url}", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result['returnstatus']) == 'Success') {
			return true;
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse.'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse.'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}


	/**
		* 收集各种短信上行
		*/
	public static function collectMesage() {
//        $resxa = self::_collectXiAo();
//        $rescy = self::_collectCongYu();
//        $rescl = self::_collectChuangLan();
//        return array_merge($resxa, $rescy, $rescl);
		return [];
	}

	/**
		* 收集聪裕短信上行
		* @param  string $smsServiceUse
		* @return [string]
		*/
	public static function _collectCongYu($smsServiceUse = 'smsService_CongYu') {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['collurl'];
		$user_id = $smsServiceParams['userid'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];
		$timestamp = date('YmdHis');
		$sign = MessageHelper::_createCongYuSign($account, $password, $timestamp);

		$post_data = [
			'userid' => $user_id,
			'timestamp' => $timestamp,
			'sign' => $sign,
			'action' => 'query',
		];

		$xml_result = '';
		try {
			$xml_result = MessageHelper::_post($url, $post_data);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s collect exception %s', $smsServiceUse, $e), LogChannel::SMS_COLLECT);
		}

		$result = simplexml_load_string($xml_result, null, LIBXML_NOCDATA); //去除CDATA格式
		if ($result !== false) {
			$result_arr = (array)$result;
			if (isset($result_arr['errorstatus'])) {
				Yii::error("获取聪裕上行短信异常，result:{$xml_result},smsService:{$smsServiceUse}", LogChannel::SMS_COLLECT);
				return [];
			}

			if (!isset($result_arr['callbox'])) {
				return [];
			}

			$result = $result_arr['callbox'];
			$res = [];
			$tmp = [];
			if (is_array($result)) {
				foreach ($result as $key => $val) {
					$val = (array)$val;
					$tmp['phone'] = $val['mobile'];
					$tmp['expid'] = $val['taskid'];
					$tmp['message'] = $val['content'];
					$tmp['send_time'] = strtotime($val['receivetime']);
					$tmp['type'] = \common\models\message\MessageCollectLog::TYPE_CONGYU;
					$tmp['type_channel'] = $smsServiceUse;
					$res[] = $tmp;
				}
			} else {
				$result = (array)$result;
				$tmp['phone'] = $result['mobile'];
				$tmp['expid'] = $result['taskid'];
				$tmp['message'] = $result['content'];
				$tmp['send_time'] = strtotime($result['receivetime']);
				$tmp['type'] = \common\models\message\MessageCollectLog::TYPE_CONGYU;
				$tmp['type_channel'] = $smsServiceUse;
				$res[] = $tmp;
			}
			return $res;
		}

		Yii::error("获取聪裕上行短信异常，result:{$xml_result},smsService:{$smsServiceUse}", LogChannel::SMS_COLLECT);
		return [];
	}

	/**
		* 收集希奥短信上行
		* @param  string $smsServiceUse
		* @return [string]
		*/
	public static function _collectXiAo($smsServiceUse = 'smsServiceXQB_XiAo') {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['collurl'];
		$uid = $smsServiceParams['uid'];
		$auth = md5($smsServiceParams['code'] . $smsServiceParams['password']);
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?uid={$uid}&auth={$auth}", false, $ctx);
		} catch (\Exception $e) {
			Yii::error(\sprintf('获取希奥上行短信异常 %s exception %s', $smsServiceUse, $e), LogChannel::SMS_COLLECT);
		}

		if (!empty($result)) {
			$result = explode("\n", $result);
			$res = [];
			$tmp = [];
			foreach ($result as $key => $val) {
				$d = explode('##', $val);
				if (isset($d[3])) {
					$d[3] = iconv("GBK","UTF-8", urldecode($d[3]));

					$tmp['phone'] = $d[2];
					$tmp['expid'] = $d[1];
					$tmp['message'] = $d[3];
					$tmp['send_time'] = strtotime($d[0]);
					$tmp['type'] = \common\models\message\MessageCollectLog::TYPE_XIAO;
					$tmp['type_channel'] = $smsServiceUse;
					$res[] = $tmp;
				}
			}
		} else {
			$res = [];
		}
		return $res;
	}

	/**
		* 收集创蓝短信上行
		* @param  string $smsServiceUse
		* @return [string]
		*/
	public static function _collectChuangLan($smsServiceUse = 'smsService_ChuangLan')
	{
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['collurl'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];
		$post_data = [
			'account' => $account,
			'password' => $password,
			'count' => 100,
		];

		try {
			$result = MessageHelper::_post($url, $post_data, true);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s exception %s', $smsServiceUse, $e), LogChannel::SMS_COLLECT);
		}
		if($output = json_decode($result, true)) {
			if(isset($output['ret']) && $output['ret'] == '0') {
				$result = $output['result'];
				$res = [];
				$tmp = [];
				foreach ($result as $key => $val) {
					$moTime = $val['moTime'];
					$date = "20" . $moTime[0].$moTime[1].'-'.$moTime[2].$moTime[3].'-'.$moTime[4].$moTime[5]. ' ' .$moTime[6].$moTime[7].':'.$moTime[8].$moTime[9];
					$tmp['phone'] = $val['mobile'];
					$tmp['expid'] = $val['spCode'];
					$tmp['message'] = $val['messageContent'];
					$tmp['send_time'] = strtotime($date);
					$tmp['type'] = \common\models\message\MessageCollectLog::TYPE_CHUANGLAN;
					$tmp['type_channel'] = $smsServiceUse;
					$res[] = $tmp;
				}
				return $res;
			}
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse}", LogChannel::SMS_COLLECT);
		return [];
	}

	private static function _post($url, $data, $is_json = false) {
		if ($is_json) {
			$header = 'Content-Type: application/json; charset=utf-8';
			$data = json_encode($data);
		} else {
			$header = 'Content-type: application/x-www-form-urlencoded';
			$data = http_build_query($data);
		}

		$options = ['http' =>
			[
				'method'  => 'POST',
				'header'  => $header,
				'content' => $data,
				'timeout' => self::$ctx_params['http']['timeout'],
			]
		];
		$context = stream_context_create($options);

		$result = file_get_contents($url, false, $context);

		return $result;
	}

	/**
		* 生成聪裕短信sign
		* @param string $user
		* @param string $password
		* @param string $timestamp YmdHis
		* @return string
		*/
	private static function _createCongYuSign($user, $password, $timestamp) {
		return md5($user . $password . $timestamp);
	}

	/**
		* 创蓝短信渠道
		* @param string $smsServiceUse
		* @param integer $phone
		* @param string $message
		* @param boolean $boolUseBack
		* @return boolean
		*/
	private static function _sendChuangLan($smsServiceUse, $phone, $message, $boolUseBack, $name=APP_NAMES) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['url'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];
		$prefix_message = '【'.$name.'】 ';
		$suffix_message = $smsServiceUse == 'smsService_ChuangLan' ? '' : ', 退订回TD';
		$post_data = [
			'account' => $account,
			'password' => $password,
			'phone' => $phone,
			'msg' => urlencode($prefix_message . $message . $suffix_message),
			'report' => 'false',
		];

		try {
			$result = MessageHelper::_post($url, $post_data, true);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsServiceUse, $phone, $e), LogChannel::SMS_GENERAL);
		}

		if ($output = json_decode($result, true)) {
			if(isset($output['code'])  && $output['code']=='0'){
				return true;
			}
		}

		if ($boolUseBack && isset(Yii::$app->params[$smsServiceUse . 'Back']) && self::_isCaptcha($message) && Yii::$app->params[$smsServiceUse.'Back']) { //走备用通道
			return self::sendSyncSMS($phone, $message, $smsServiceUse . 'Back', 0, false);
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}

	/**
		* 检查短信渠道状态
		* @param $smsService
		* @return mixed
		*/
	public static function checkMsgStatus($smsService) {
		$method = self::getMsgMethod($smsService, "status");
		return self::$method($smsService);
	}

	/**
		* [statusAll 状态检查]
		* @return [type] [description]
		*/
	public static function statusAll($smsService)
	{
		return self::checkMsgStatus($smsService);
	}

	private static function getMsgMethod($smsService, $preMethod = 'status') {
		switch ($smsService) {
//			case 'smsService_CongYu':
//			case 'smsService_CongYu_YX':
//				$method = 'CongYu';
//				break;
//			case 'smsService_ChuangLan':
//			case 'smsService_ChuangLan_YX':
//			case 'smsService_ChuangLan_P2P':
//				$method = 'ChuangLan';
//				break;
			case 'smsService_TianChang':
			case 'smsService_TianChang_HY':
			//case 'smsService_TianChang_CS':
			case 'smsService_TianChang_TZ':
			case 'smsService_TianChang_SXD':
			case 'smsService_TianChang_SXD_YX':
			case 'smsService_TianChang_WZD':
			case 'smsService_TianChang_WZD_YX':
			case 'smsService_TianChang_HBQB':
			case 'smsService_TianChang_HBQB_YX':
			case 'smsService_TianChang_JZGJ':
			case 'smsService_TianChang_JZGJ_YX':
			case 'smsService_TianChang_KDJZ':
			case 'smsService_TianChang_KDJZ_YX':
				$method = 'TianChang';
				break;
			case 'smsService_MengWang':
			case 'smsService_MengWang_CS':
			case 'smsService_MengWang_Repayment':
			case 'smsService_MengWang_WZDRepayment':
				$method = 'MengWang';
				break;
			case 'smsService_SuDun':
				$method = 'SuDun';
				break;
			case 'smsService_WoTao':
				$method = 'WoTao';
				break;
			case 'smsService_YunFeng':
				$method = 'YunFeng';
				break;
			case 'smsService_WeiWang_CS':
			case 'smsService_WeiWang_YX':
				$method = 'WeiWang';
				break;
			default :
				$method = 'XiAo';
		}

		return $preMethod . $method;
	}

	/**
		* 聪裕科技状态检查
		* @param string $smsService
		* @return mixed
		*/
	public static function statusCongYu($smsService) {
		$smsServiceParams = Yii::$app->params[$smsService];
		$url = $smsServiceParams['url'];
		$user_id = $smsServiceParams['userid'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];
		$timestamp = date('YmdHis');
		$sign = MessageHelper::_createCongYuSign($account, $password, $timestamp);

		$post_data = [
			'userid' => $user_id,
			'timestamp' => $timestamp,
			'sign' => $sign,
			'action' => 'overage',
		];

		try {
			$xml_result = MessageHelper::_post($url, $post_data);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsService, 'status', $e), LogChannel::SMS_GENERAL);
			return 0;
		}

		$result = simplexml_load_string($xml_result);
		if ($result !== false) {
			$result_arr = (array)$result;
			if (isset($result_arr['returnstatus']) && $result_arr['returnstatus'] == 'Sucess') {
				return intval($result_arr['overage']);
			}
		}

		Yii::error("状态检查异常，result:{$result},smsService:{$smsService}", LogChannel::SMS_GENERAL);
		return 0;
	}

	/**
		* 创蓝状态检查
		* @param string $smsService
		* @return mixed
		*/
	public static function statusChuangLan($smsService) {
		$smsServiceParams = Yii::$app->params[$smsService];
		$url = $smsServiceParams['balance_url'];
		$account = $smsServiceParams['account'];
		$password = $smsServiceParams['password'];

		$post_data = [
			'account' => $account,
			'password' => $password,
		];

		try {
			$result = MessageHelper::_post($url, $post_data, true);
		} catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsService, 'status', $e), LogChannel::SMS_GENERAL);
			return 0;
		}

		if($output = json_decode($result, true)){
			if(isset($output['balance'])){
				return $output['balance'];
			}
		}

		Yii::error("状态检查异常，result:{$output},smsService:{$smsService}", LogChannel::SMS_GENERAL);
		return 0;
	}

	/**
		* 希奥状态检查
		*/
	public static function statusXiAo($smsService) {
		$smsServiceParams = Yii::$app->params[$smsService];
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['uid'];
		$auth = \md5($smsServiceParams['code'] . $smsServiceParams['password']);
		$result = '';
		try {
			$ctx = \stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}m?uid={$uid}&auth={$auth}&encode=utf-8", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception %s', $smsService, 'status', $e), LogChannel::SMS_GENERAL);
			return false;
		}

		if ($result && \is_numeric($result)) { #大于0是条数；其他是状态码
			return \intval($result);
		}

		Yii::error("状态检查异常，result:{$result},smsService:{$smsService}", LogChannel::SMS_GENERAL);
		return false;
	}

	/*
	* 天畅状态检查
	*/
	public static function statusTianChang($smsServiceUse) {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['balance_url'];
		$uid = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?un={$uid}&pw={$pwd}&rf=2", false, $ctx); //&da={$phone}&sm={$msg}&dc=15&tf=3&rf=2
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s exception %s', $smsServiceUse, $e), LogChannel::SMS_GENERAL);
			return false;
		}
		$ret = json_decode($result,true);
		if ($ret['success']) {
			return \intval($ret['bl']);
		}

		Yii::error(\sprintf('[%s] result: %s', $smsServiceUse, json_encode($result)), LogChannel::SMS_GENERAL);
		return false;
	}

	/**
		* 梦网状态检查
		* @param string $smsService
		* @return mixed
		*/
	public static function statusMengWang($smsServiceUse = 'smsService_MengWang') {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['balance_url'];
		$uid = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?userId={$uid}&password={$pwd}", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('[%s] exception: %s', $smsServiceUse, $e), LogChannel::SMS_GENERAL);
			return false;
		}

		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result[0]) && $result[0] > 0) {
			return $result[0];
		}
		$result = implode(',', $result);
		Yii::error(\sprintf('[%s] result: %s', $smsServiceUse, json_encode($result)), LogChannel::SMS_GENERAL);
		return false;
	}

	/**
		* 微网状态检查
		* @param string $smsService
		* @return mixed
		*/
	public static function statusWeiWang($smsServiceUse = 'smsService_WeiWang_CS') {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['balanceUrl'];
		$uid = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$sprdid = $smsServiceParams['prdid'];
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}?sname={$uid}&spwd={$pwd}&scorpid=&sprdid={$sprdid}", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception', $smsServiceUse, $e), LogChannel::SMS_GENERAL);
			return FALSE;
		}

		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result['State']) && $result['State'] == 0) {
			return $result["Remain"];
		}

		Yii::error(\sprintf('[%s] result: %s', $smsServiceUse, json_encode($result)), LogChannel::SMS_GENERAL);
		return false;
	}

	/**
		* 速盾状态检查
		* @param string $smsService
		* @return mixed
		*/
	public static function statusSuDun($smsServiceUse = 'smsService_SuDun') {
		$smsServiceParams = Yii::$app->params[$smsServiceUse];
		$url = $smsServiceParams['url'];
		$uid = $smsServiceParams['uid'];
		$account = $smsServiceParams['account'];
		$pwd = $smsServiceParams['password'];
		$result = '';
		try {
			$ctx = stream_context_create(self::$ctx_params);
			$result = \file_get_contents("{$url}&userid={$uid}&account={$account}&password={$pwd}", false, $ctx);
		}
		catch (\Exception $e) {
			Yii::error(\sprintf('%s:%s exception', $smsServiceUse, $e), LogChannel::SMS_GENERAL);
			return false;
		}

		$result = simplexml_load_string($result, null, LIBXML_NOCDATA); //去除CDATA格式
		$result = (array)$result;
		if (isset($result['returnstatus']) == 'Success') {
			return true;
		}

		$result = implode(',', $result);
		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} ", LogChannel::SMS_GENERAL);
		return false;
	}
	/**
		* 异步发送短信的操作
		*
		* @param string $phone 手机号码
		* @param string $message 短信内容
		* @param string $smsServiceUse 短信方式
		* @param integer $dbLog 是否记录到数据库 0或1
		*/
	public static function sendSyncSMS($phone, $message, $smsServiceUse = 'smsService', $dbLog=1, $auto=true){
		if (($auto && Yii::$app instanceof Yii\web\Application) && Yii::$app->id == 'app-credit') {
			$smsServiceUse = Util::t('app_msg_name') ?? 'smsService_TianChang_HY';
		}

		$msg  = \urlencode($message);
		$data = array(
			"phone" => $phone,
			"content" => $msg,
			"type" => $smsServiceUse,
			"db_log" => $dbLog
		);

		$result = RedisQueue::push([RedisQueue::LIST_USER_GET_PHONE_CAPTCHA, json_encode($data)]);
		if ($result) {
			return true;
		}

		Yii::error("发送短信失败，result:{$result},smsService:{$smsServiceUse} mobile:{$phone} msg:{$message}", LogChannel::SMS_GENERAL);
		return false;
	}

	/**
		* 限制手机号发送短信,1分钟一次
		*/
	public static function limitSendSmsByPhone($phone){
		if(!empty($phone)) {
			$key = "limited-times-{$phone}";
			$ret = Yii::$app->redis->executeCommand('GET', [$key]);
			if(empty($ret)) {
				Yii::$app->redis->executeCommand('SET', [$key, 1]);
				Yii::$app->redis->executeCommand('EXPIRE', [$key, 60]);
			}else{
				return false;
			}
		}
		return true;
	}

	/**
		* 记录手机号发送次数
		*/
	public static function addTimesSendSmsByPhone($phone){
		if(!empty($phone)) {
			$date = date('Ymd');
			$key = "limited-day-times-{$date}:{$phone}";
			$ret = Yii::$app->redis->executeCommand('HINCRBY', [$key, 'count', 1]);
			if(intval($ret) == 1) {
				Yii::$app->redis->executeCommand('EXPIRE', [$key, 24*60*60]);
			}
		}
		return $ret;
	}

	/**
		* 限制手机号发送短信,1天10次
		*/
	public static function limitDaySendSmsByPhone($phone, $limit_times = 10){
		if(!empty($phone)) {
			$date = date('Ymd');
			$key = "limited-day-times-{$date}:{$phone}";
			$ret = Yii::$app->redis->executeCommand('HGET', [$key, 'count']);
			if($ret > $limit_times){
				return false;
			}
		}
		return true;
	}
}
