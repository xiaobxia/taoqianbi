<?php
namespace console\controllers;

use common\helpers\CommonHelper;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\UserContactsRecord;
use Yii;
use common\api\RedisQueue;
use common\helpers\GlobalHelper;
use common\models\mongo\statistics\UserPhoneMessageMongo;
use common\models\mongo\statistics\UserInstalledAppsMongo;
use common\base\LogChannel;

class UserController extends BaseController{

    /**
     * 用户通讯录/手机短信/手机app数据等落地
     * 每分钟拉起执行，3分钟自动退出
     * touch /tmp/down-redis-{$type}-contents.tag 关闭
     */
    public function actionDownRedisContents($type) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        $type = \intval($type);
        if (!\in_array($type, [1,2,3])) {
            $this->message('请输入参数type - 1:message,:2:app,3:mobile');
            return self::EXIT_CODE_ERROR;
        }

        $close_tag = "/tmp/down-redis-{$type}-contents.tag";
        $now = \time();

        while(true) {
            pcntl_signal_dispatch();

            if (\file_exists($close_tag)) {
                if (! \unlink($close_tag) ) {
                    CommonHelper::error("delete $close_tag failed.");
                }
                $this->printMessage('检测到标识，关闭当前脚本');
                exit;
            }

            if (time() - $now > 160) {
                $this->printMessage('runing_3mins，close');
                exit;
            }

            if ($type == 1) {
                $key = RedisQueue::LIST_USER_MOBILE_MESSAGES_UPLOAD;
            }
            else if ($type == 2) {
                $key = RedisQueue::LIST_USER_MOBILE_APPS_UPLOAD;
            }
            else {
                $key = RedisQueue::LIST_USER_MOBILE_CONTACTS_UPLOAD;
            }

            $redis_content = RedisQueue::pop([$key]);
            if (!$redis_content) {
                if (time() % 10 == 0) {
                    $this->printMessage("type_{$type} none");
                }
                \sleep(1);
                continue;
            }

            $decode = \json_decode($redis_content, true);
            $content = (array)$decode;
            $json_last_error = \json_last_error();
            if ($json_last_error != 0 || empty($content)) {
                CommonHelper::stderr( \sprintf("type_{$type} %s decode_failed %s, json_error: [%s]%s.\n",
                    $redis_content, \print_r($decode, true), $json_last_error, \json_last_error_msg()) );
                continue;
            }

            GlobalHelper::connectDb('db_kdkj');
            try{
                if ($type == 1) {
                    //$this->_analysisPhomeMessage($content);
                    $this->_analysisPhoneMessageToMongo($content);
                }
                else if ($type == 2) {
                    //$this->_analysisApp($content);
                    $this->_analysisAppToMongo($content);
                }
                else {
                    $this->_analysisMobile($content);
                }
            }
            catch (\Exception $e) {
                CommonHelper::error($e, LogChannel::USER_UPLOAD);

                if (preg_match('/shard keys must be less than/', $e->getMessage())) {
                    continue;
                }
                if ($key == RedisQueue::LIST_USER_MOBILE_CONTACTS_UPLOAD) {
                    $_cnt = \json_encode($content);
                    CommonHelper::error(\sprintf('%s push_back (%s) %s', $key, mb_strlen($_cnt), substr($_cnt, 0, 100)), LogChannel::USER_UPLOAD);
                    RedisQueue::push([$key, $_cnt]);
                }
            }
        }
    }


    private function _analysisMobile($content) {
        $err_mobiles = [];
        foreach($content as $item) {
            if (isset($item['user_id']) && isset($item['mobile']) && isset($item['name'])
                    && $item['name'] && $item['user_id'] && $item['mobile']) {
                $user_id = $item['user_id'];
                $name = $item['name'];
                $mobiles = \explode(':', $item['mobile']);
                foreach($mobiles as $mobile) {
                    $_len = strlen($mobile);
                    if ($_len > 0 && $_len < 20) {
                        UserMobileContactsMongo::addData($user_id, $mobile, $name);
                    }
                    else {
                        $err_mobiles[] = $mobile;
                    }
                }
            }
        }
        if ($err_mobiles) {
            CommonHelper::stderr( \sprintf("_analysisMobile [%s][%s] not_added: %s(%s).\n",
                $user_id, $name, print_r($err_mobiles, true), print_r($content, true)) );
        }

        //保存用户上传日志
        try {
            $user_id = $content[0]['user_id'];
            $res = UserContactsRecord::findOne(['user_id' => $user_id]);
            if (!$res) {
                $contents = new UserContactsRecord();
                $total = \count($content);
                $contents->user_id = $content[0]['user_id'];
                $contents->is_upload = UserContactsRecord::STATUS_ON;//1为已上传
                $contents->upload_time = time();
                $contents->total = $total;
                $_log = $contents->save(false);
                if (! $_log) {
                    CommonHelper::stderr(sprintf("_analysisMobile log_failed %s.\n", $user_id));
                }
                return true;
            }
        }
        catch (\Exception $e) {
            CommonHelper::error(\sprintf('_analysisMobile log_exception [%s] %s', $user_id, $e), LogChannel::USER_UPLOAD);
            return true;
        }
    }

    /*
     * 用户手机短信上报同步更新到mongodb
     * @param array $content
     * @return boolean
     */
    private function _analysisPhoneMessageToMongo($content){
        if (isset($content['params']) && isset($content['data']) && $content['data']) {
            $clientType = isset($content['params']['clientType']) ? $content['params']['clientType'] : '';
            $osVersion = isset($content['params']['osVersion']) ? $content['params']['osVersion'] : '';
            $appVersion = isset($content['params']['appVersion']) ? $content['params']['appVersion'] : '';
            $deviceName = isset($content['params']['deviceName']) ? $content['params']['deviceName'] : '';
            $appMarket = isset($content['params']['appMarket']) ? $content['params']['appMarket'] : '';
            $deviceId = isset($content['params']['deviceId']) ? $content['params']['deviceId'] : '';
            foreach($content['data'] as $item){
                if(isset($item['messageContent']) && $item['messageContent']){
                    $userId = isset($item['userId']) ? $item['userId'] : 0;
                    $messageContent = $item['messageContent'];
                    $phone = isset($item['phone']) ? $item['phone'] : '';
                    $messageDate = isset($item['messageDate']) ? $item['messageDate'] : '';
                    if (!$userId && !$deviceId) {
                        continue;
                    }
                    $data = [
                        'clientType' => $clientType,
                        'osVersion' => $osVersion,
                        'appVersion' => $appVersion,
                        'deviceName' => $deviceName,
                        'appMarket' => $appMarket,
                    ];
                    UserPhoneMessageMongo::addData($userId, $deviceId, $messageContent, $messageDate, $phone, $data);
                }
            }
        }

        return true;
    }

    /**
     * 用户安装app上报同步更新到mongodb
     * @param unknown $content
     * @return boolean
     */
    private function _analysisAppToMongo($content){
        if(isset($content['params']) && isset($content['data']) && $content['data']){
            $clientType = isset($content['params']['clientType']) ? $content['params']['clientType'] : '';
            $osVersion = isset($content['params']['osVersion']) ? $content['params']['osVersion'] : '';
            $appVersion = isset($content['params']['appVersion']) ? $content['params']['appVersion'] : '';
            $deviceName = isset($content['params']['deviceName']) ? $content['params']['deviceName'] : '';
            $appMarket = isset($content['params']['appMarket']) ? $content['params']['appMarket'] : '';
            $deviceId = isset($content['params']['deviceId']) ? $content['params']['deviceId'] : '';
            foreach($content['data'] as $item){
                if(isset($item['appName'])&&isset($item['packageName']) && $item['packageName']){
                    $userId = isset($item['userId']) ? $item['userId'] : 0;
                    $packageName = isset($item['packageName']) ? $item['packageName'] : '';
                    $appName = isset($item['appName']) ? $item['appName'] : '';
                    $versionCode = isset($item['versionCode']) ? $item['versionCode'] : '';
                    if(!$userId && !$deviceId){
                        continue;
                    }
                    $data = [
                        'app_name' => $appName,
                        'version_code' => $versionCode,
                        'clientType' => $clientType,
                        'osVersion' => $osVersion,
                        'appVersion' => $appVersion,
                        'deviceName' => $deviceName,
                        'appMarket' => $appMarket,
                    ];
                    UserInstalledAppsMongo::addData($userId, $deviceId, $packageName, $data);
                }
            }
        }
        return true;
    }


    private function printMessage($string) {
        $pid = function_exists('posix_getpid') ? posix_getpid() : '';
        $date = date('Y-m-d H:i:s');
        $mem = \floor(\memory_get_usage(true) / 1024 / 1024) . 'MB';
        //时间 进程号 内存使用量 日志内容
        echo "{$date} {$pid} $mem {$string} \n";
    }
}
