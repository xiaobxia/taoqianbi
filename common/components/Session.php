<?php
namespace common\components;

use common\helpers\StringHelper;
use common\helpers\ToolsUtil;

/**
 * 定制化的Session组件，增加了session的前缀
 *
 * yii\web\Session 类默认存储session数据为文件到服务器上，Yii提供以下session类实现不同的session存储方式：
 *
 *    yii\web\DbSession: 存储session数据在数据表中
 *    yii\web\CacheSession: 存储session数据到缓存中，缓存和配置中的缓存组件相关
 *    yii\redis\Session: 存储session数据到以redis 作为存储媒介中
 *    yii\mongodb\Session: 存储session数据到MongoDB.
 *
 * 所有这些session类支持相同的API方法集，因此，切换到不同的session存储介质不需要修改项目使用session的代码。
 *
 * @author Skilly
 */
class Session extends \yii\redis\Session {
    const SKEY = 'user';

    /**
     * 根据参数生成短信防刷的key
     * @param string $ip
     *
     * @return string
     */
    public static function getSmsKey($ip='') {
        if (empty($ip)) {
            $ip = ToolsUtil::getIp();
        }

        return StringHelper::auto_encrypt(sprintf('%s|%s', ip2long($ip), \time()));
    }

    /**
     * 验证提交的短信防刷key
     * @param        $key
     * @param string $ip
     *
     * @return bool
     */
    public static function validSmsKey($key, $ip ='', $timeout=60) {
        if (empty($ip)) {
            $ip = ToolsUtil::getIp();
        }

        $input = StringHelper::auto_decrypt($key);
        if (strpos($input, '|') === FALSE) {
            return false;
        }

        list($long, $ts) = explode('|', $input);
        if ($long != ip2long($ip)) {
            return false;
        }
        if ((time()-$ts) > $timeout) {
            return false;
        }

        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id) {
        $data = $this->redis->executeCommand('GET', [$this->calculateKey($id)]);

        return $data === false || $data === null ? '' : $data;
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return bool whether session write is successful
     */
    public function writeSession($id, $data) {
        if (\Yii::$app->user->isGuest) {
            return (bool) $this->redis->executeCommand('SET', [$this->calculateKey($id), $data, 'EX', $this->getTimeout()]);
        }

        $userId = $this->calculateUserKey();
        $sessionId = $this->calculateKey($id);

        //只允许同一个session id存在
        if ($this->redis->executeCommand('EXISTS', [$userId])) {
            $oldSessionId = $this->redis->executeCommand('GET', [$userId]);
            $this->destroyUserSession($userId, $oldSessionId);
        }
        $flag = (bool) $this->redis->executeCommand('SET', [$sessionId, $data, 'EX', $this->getTimeout()]);
        $flagU = (bool) $this->redis->executeCommand('SET', [$userId, $sessionId, 'EX', $this->getTimeout()]);
        return ($flag && $flagU);
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return bool whether session is destroyed successfully
     */
    public function destroySession($id) {
        $userId = \Yii::$app->session->get($this->keyPrefix . self::SKEY);
        $this->destroyUserSession($userId, $this->calculateKey($id));
        // @see https://github.com/yiisoft/yii2-redis/issues/82
        return true;
    }

    /**
     * Generates a unique key used for storing session data in cache.
     * @param string $id session variable name
     * @return string a safe cache key associated with the session variable name
     */
    protected function calculateUserKey() {
        return $this->keyPrefix . (\Yii::$app->user->identity->id); //md5
    }

    /**
     * 删除关联session
     * @return [type] [description]
     */
    protected function destroyUserSession($userId, $sessionId) {
        $this->redis->executeCommand('DEL', [$userId]);
        $this->redis->executeCommand('DEL', [$sessionId]);
    }

}
