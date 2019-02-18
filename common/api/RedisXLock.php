<?php

namespace common\api;

use Yii;
use yii\redis\Connection;

/**
 * 基于Redis原子操作实现的分布式乐观锁
 *
 * if (RedisXLock::lock($key_for_user_id)) {
 *    // 需要加锁的代码
 *    RedisXLock::unlock($key_for_user_id); //释放锁
 * }else{
 *    // 获取锁失败
 * }
 *
 * @package common\api
 */
class RedisXLock {
    const COLLECTION_LOCK_FOR_ADD_DEBIT_DETAIL = 'collection_lock_for_add_debit_detail';//催收发起扣款加锁

    protected static function getToken() {
        static $tmp = null;
        if (null == $tmp) {
            $tmp = \uniqid();
        }

        return $tmp;
    }

    /**
     * 加分布式锁
     *
     * @param $key
     * @param int $timeout
     * @return array|bool|null|string
     */
    public static function lock($key, $timeout = 60) {
        return \yii::$app->redis->executeCommand('SET', ['XLOCK:' . $key, self::getToken(), "EX", $timeout, "NX"]);
    }

    /**
     * 释放锁
     *
     * @param $key
     * @return array|bool|null|string
     */
    public static function unlock($key) {
        $script = <<<EOF
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end
EOF;
        return \yii::$app->redis->executeCommand('EVAL', [$script, 1, 'XLOCK:' . $key, self::getToken()]);
    }

    /**
     * 设置过期时间
     *
     * @param $key
     * @param int $expire
     */
    public static function expire($key, $expire = 30) {
        return \yii::$app->redis->executeCommand('EXPIRE', [$key, $expire]);
    }
}
