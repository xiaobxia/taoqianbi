<?php
/**
 * @author: 周闽强
 * @date:   2017-05-17 16:04:07
 * @email: 1254109699@qq.com
 *
 * 集成极光推送
 */

namespace common\external;

use common\models\LoanPerson;
use Yii;
use JPush\Exceptions\APIConnectionException;
use JPush\Exceptions\APIRequestException;
use common\Exceptions\JPushException;
use JPush\Client;

class JPush {

	public $logPath = '';
	private $client = null;
	private $pusher = null;

    /**
     * @var array guoxiaoyong  增加了extras 参数传递
     */
	public $extras = [];


	public function __construct($appKey, $secret, $logPath = '')
	{
		$this->logPath = $logPath;
		$this->logPath || $this->logPath = Yii::getAlias('@runtime/') . 'jpush.log';
        $this->client = new Client($appKey, $secret, $this->logPath);
        $this->pusher = $this->client->push();
	}

	public function pushAll($content, $title, $platform = [], $message = [], $options = [])
	{
		$type = 'all';
		return $this->push($type, $content, $title, $platform = [], $message = [], $options = []);
	}
	
	public function pushAndroid($userId, $content, $title, $message = [], $options = [])
	{
		$platform = ['android'];
		return $this->push($userId, $content, $title, $platform, $message, $options);
	}

	public function pushAllAndroid($content, $title, $message = [], $options = [])
	{
		$platform = ['android'];
		$type = 'all';
		return $this->push($type, $content, $title, $platform, $message, $options);
	}

	public function pushIos($userId, $content, $title, $message = [], $options = [])
	{
		$platform = ['ios'];
		return $this->push($userId, $content, $title, $platform, $message, $options);
	}

	public function pushAllIos($content, $title, $message = [], $options = [])
	{
		$platform = ['ios'];
		$type = 'all';
		return $this->push($type, $content, $title, $platform, $message, $options);
	}


    public function push($userId, $content, $title, $platform = [], $message = [], $options = [])
    {
        $platform || $platform = array('ios', 'android');


        $ios_notification = array(
            'content-available' => true, //推送唤醒
            'extras' => $this->extras, //扩展参数 guoxiaoyong
        );
        $android_notification = array(
            'title' => $title,
            'extras' => $this->extras, //扩展参数 guoxiaoyong
        );


        try {
            //无忧白条不推送  dev255-btwu 2018-02-10 begin
            if(!$userId) return false;
            $last_login = \common\models\UserLoginLog::find()->select(['id','user_id', 'source'])->where(['id' => $userId])->orderBy(['id' => SORT_DESC])->one();
            $source = unserialize($last_login['source']);
            $appMarket = $source['appMarket'];
            if($appMarket == LoanPerson::APPMARKET_IOS_XYBTWUYOU){
                return false;
            }
            //end

            if ($userId && $userId != 'all') {
                $this->pusher->addAlias($userId);
            } else {
                //$this->pusher->addAllAudience();
	            return false;
            }
            $options['apns_production'] = YII_ENV_PROD;

            $response = $this->pusher->setPlatform($platform)
            ->iosNotification($content, $ios_notification)
            ->androidNotification($content, $android_notification)
            ->message($content, $message) //透传消息
            ->options($options)
            ->send();
            return $response;
        } catch (APIConnectionException $e) {
            // try something here
            // print $e . JPushException::$ERROR_MSG[$e->getCode()];
            return $e->getCode();
        } catch (APIRequestException $e) {
            // try something here throw new APIRequestException("Error Processing Request", 1);
            // print $e . JPushException::$ERROR_MSG[$e->getCode()];
            return $e->getCode();
        }
    }

}