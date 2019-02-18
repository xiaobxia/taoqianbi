<?php
/**
 * @author: 周闽强
 * @date:   2017-05-18 11:11:21
 * @email: 1254109699@qq.com
 */

namespace common\services;

use common\external\JPush;
use common\models\LoanPerson;
use common\exceptions\JPushException;
use yii\base\Exception;
use yii\base\Component;

class JPushService extends Component
{

	//信用白条
	const APP_KEY = '20a0bd7ec568a087236091bd';
	const MASTER_SECRET = '2c797c3d9032533e9bfd6152';
//	//信用白条福利
//	const APP_KEY_FULI = 'dea321ac773fa13d6ca82886';
//	const MASTER_SECRET_FULI = '413c3f8f53d29442d7798db5';
//	//汇邦钱包
//	const APP_KEY_HUIBANG = '282520b0907d760fd46cd15d';
//	const MASTER_SECRET_HUIBANG = 'de33d6eb3ead96db863e0c08';
//	//温州贷借款
//	const APP_KEY_WZD = 'cc1fc531f45a14a40c7fab6f';
//	const MASTER_SECRET_WZD = 'c5d0ab2749bc6890028a68a4';
//	//信用白条公积金
//	const APP_KEY_GJJ = 'd558401e99dfb13d32121264';
//	const MASTER_SECRET_GJJ = 'b75c2052a1376b39069d65b9';
//
//	//秒换卡
//	const APP_KEY_MHK = '4cffe3a01eb6c80a08def7c7';
//	const MASTER_SECRET_MHK = '5a62928e21826e2a91767c96';
//
//    //开心借
//	const APP_KEY_KXJIE = '022c6b28c9f126e12087d350';
//    const MASTER_SECRET_KXJIE = '5249fbbee495db702313578c';

	const CHANNEL_XYBT = 'xybt';
	const CHANNEL_XYBTFL = 'xybtfl';
	const CHANNEL_HBQB = 'hbqb';
	const CHANNEL_WZD = 'wzd';
	const CHANNEL_GJJ = 'gjj';

	const CHANNEL_KXJIE = 'kaixinjie';

	const CHANNEL_ALL = 'all';
	const PUAH_ALL = 'all';
	const ANDROID = 'android';
	const IOS = 'ios';

	const REPAYMENT_TEXT = '您有一笔借款即将到期，及时还清有机会提额哦，戳我立即还款！'; //还款前一天下午14：00发送推送，文案：
	const REPAYMENT_TODAY_TEXT = '您有一笔借款今天到期，及时还清立享提额，最高可提2000元，戳我立即还款！';
	const REPAYMENT_ONE_TEXT = '您有一笔借款已逾期，将产生滞纳费，请及时还清，戳我立即还款！';
	const REPAYMENT_THREE_TEXT = '您有一笔借款已逾期，将产生滞纳费，请及时还清，戳我立即还款！';
	const REPAYMENT_THE_TEXT = '您有一笔借款已逾期，将产生滞纳费，请及时还清，戳我立即还款！';
	const REPAYMENT_ACTIVITY_TEXT = '哥 热么？送你5000 爽一下！';

	private $pushers = null;

	// 通道
	public static $channel_type = [
		self::CHANNEL_XYBT => APP_NAMES,
		self::CHANNEL_XYBTFL => '信用白条福利版',
		self::CHANNEL_HBQB => '汇邦钱包',
		self::CHANNEL_WZD => '温州贷',
		self::CHANNEL_GJJ => '信用白条公积金版',
		self::CHANNEL_KXJIE => '开心借',
	];

	public $extras = [];

	public $message = [];

	public $options = [];

	/**
	 * 向所有通道所有平台推送所有用户
	 * @return [boolean]
	 */
	public function pushAllChannelPlatform($content)
	{
		$puahAll = self::PUAH_ALL;
		$channelAll = self::CHANNEL_ALL;
		return $this->push($content, $puahAll, $channelAll);
	}

	/**
	 * 向所有通道推送所有用户
	 * @return [boolean]
	 */
	public function pushAllChannel($content, $platform)
	{
		$puahAll = self::PUAH_ALL;
		$channelAll = self::CHANNEL_ALL;
		return $this->push($content, $puahAll, $channelAll, $platform);
	}

	/**
	 * 向单/个通道推送所有用户
	 * @return [type] [description]
	 */
	public function pushAll($content, $channel, $platform)
	{
		$puahAll = self::PUAH_ALL;
		return $this->push($content, $puahAll, $channel, $platform);
	}

	/**
	 * 安卓所有用户推送
	 * @return [mixed]
	 */
	public function pushAllAndroid($content)
	{
		$puahAll = self::PUAH_ALL;
		$channelAll = self::CHANNEL_ALL;
		$platform = self::ANDROID;
		return $this->push($content, $puahAll, $channelAll, $platform);
	}

	/**
	 * 安卓单个用户推送
	 * @return [mixed]
	 */
	public function pushAndroid($content, $userId)
	{
		$channelAll = self::CHANNEL_ALL;
		$platform = self::ANDROID;
		return $this->push($content, $userId, $channelAll, $platform);
	}

	/**
	 * ios所有用户推送
	 * @return [mixed]
	 */
	public function pushAllIos($content)
	{
		$puahAll = self::PUAH_ALL;
		$channelAll = self::CHANNEL_ALL;
		$platform = self::IOS;
		return $this->push($content, $puahAll, $channelAll, $platform);
	}

	/**
	 * ios单个用户推送
	 * @return [mixed]
	 */
	public function pushIos($content, $userId)
	{
		$channelAll = self::CHANNEL_ALL;
		$platform = self::IOS;
		return $this->push($content, $userId, $channelAll, $platform);
	}

	/**
	 * 向单个用户推送 | 动态选择通道 | 推送总方法
	 * @param  [mixed] $content  [推送内容]
	 * @param  [string|array] $userId   [推动用户 | 不写为全部]
	 * @param  [string|array] $channel  [推送通道]
	 * @param  string | array  $platform [推送平台]
	 * @param  [string] $title    [标题]
	 * @param  array  $message  [透传消息]
	 * @param  array  $options  [可选参数]
	 * @return [mixed]           [description]
	 */
	public function push($content, $userId, $channel, $platform = '', $title = '', $message = [], $options = [])
	{
		//动态选择通道推送
		// if (empty($channel)) {

		// }
		if (!$channel || $channel == 'all') {
			$channels = $this->getChannel();
		} else if (is_array($channel)) {
			$channels = [];
			foreach ($channel as $ch) {
				if (!isset($this->getChannel()[$ch])) {
					throw new JPushException(JPushException::$ERROR_MSG[JPushException::NO_EXIST_CHINNAL] . "channel: {$channel}", JPushException::NO_EXIST_CHINNAL);
				}
				$channels[] = $this->getChannel()[$ch];
			}
		} else if (is_string($channel)) {
			if (!isset($this->getChannel()[$channel])) {
				throw new JPushException(JPushException::$ERROR_MSG[JPushException::NO_EXIST_CHINNAL] . "channel: {$channel}", JPushException::NO_EXIST_CHINNAL);
			}
			$channels = [$this->getChannel()[$channel]];
		} else {
			throw new JPushException("channel: {$channel} 参数传入错误");
		}

		if (!$content) {
			throw new JPushException('通知内容不能为空!');
		}

		$pushers = [];
		foreach ($channels as $key => $channel) {
			$pushers[] = new JPush($channel[0], $channel[1]);
		}


		//add by guoxiaoyong 2017-07-27
		$message = $this->message;
		$options = $this->options;

		//推送
		$responses = [];
		foreach ($pushers as $push) {
            $push->extras = $this->extras;
            $responses[] = $push->push($userId, $content, $title, $platform, $message, $options);
		}
		return $responses;
	}

	/**
	 * 创建定时任务
	 * @return [type]
	 */
	public function pushSchedule()
	{

	}

	//获取所有通道
	public function getChannel()
	{
		return [
			self::CHANNEL_XYBT => [self::APP_KEY, self::MASTER_SECRET],
//			self::CHANNEL_XYBTFL => [self::APP_KEY_FULI, self::MASTER_SECRET_FULI],
//			self::CHANNEL_HBQB => [self::APP_KEY_HUIBANG, self::MASTER_SECRET_HUIBANG],
//			self::CHANNEL_WZD => [self::APP_KEY_WZD, self::MASTER_SECRET_WZD],
//			self::CHANNEL_GJJ => [self::APP_KEY_GJJ, self::MASTER_SECRET_GJJ],
//            self::CHANNEL_KXJIE => [self::APP_KEY_KXJIE, self::MASTER_SECRET_KXJIE],
		];
	}

	/**
	 * 动态猜想当前用户通道
	 * @return [string]
	 */
	public function getGuessChannel($user_id)
	{
        $last_login = \common\models\UserLoginLog::find()->select(['id','user_id', 'source'])->where(['id' => $user_id])->orderBy(['id' => SORT_DESC])->one();
        $source = unserialize($last_login['source']);
        $clientType = $source['clientType'];
        $appMarket = $source['appMarket'];

        $channel = self::getChannelByMarket($clientType, $appMarket);
        return $channel;
	}

	//依据appmarket 返回短信通道
	public static function getChannelByMarket($clientType , $market)
	{
        if ($clientType == self::ANDROID) {
            if (strpos($market, LoanPerson::APPMARKET_WZD_LOAN) !== false) {
                $channel = self::CHANNEL_WZD;
            } else if (strpos($market, LoanPerson::APPMARKET_HBQB) !== false) {
                $channel = self::CHANNEL_HBQB;

            } else if (strpos($market, LoanPerson::APPMARKET_XYBTFULI) !== false) {
                $channel = self::CHANNEL_XYBTFL;
            } else if (strpos($market, LoanPerson::APPMARKET_KXJIE) !== false) {
                $channel = self::CHANNEL_KXJIE;
            }else {
                $channel = self::CHANNEL_XYBT;
            }

        } else if ($clientType == self::IOS) {
            if (strpos($market, LoanPerson::APPMARKET_IOS_WZD_LOAN) !== false) {
                $channel = self::CHANNEL_WZD;
            } else if (strpos($market, LoanPerson::APPMARKET_IOS_HBQB) !== false) {
                $channel = self::CHANNEL_HBQB;
            } else if (strpos($market, LoanPerson::APPMARKET_IOS_XYBTFULI) !== false) {
                $channel = self::CHANNEL_XYBTFL;
            } else if (strpos($market, LoanPerson::APPMARKET_KXJIE) !== false) {
                $channel = self::CHANNEL_KXJIE;
            }else {
                $channel = self::CHANNEL_XYBT;
            }
        } else {
            $channel = LoanPerson::USER_AGENT_XYBT;
        }

        return $channel;
	}

	public function getPusher()
	{
		return $this>$pushers;
	}

}