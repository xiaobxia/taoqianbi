<?php

namespace common\api;

use Yii;
use common\helpers\ToolsUtil;

/**
 * redis消息队列
 * 使用方法
 * 推入消息 ： RedisMQ::push()
 * 获取消息 ： RedisMQ::receive()
 */
class RedisMQ {

    /**
     * 获取 redis 连接
     * @return \yii\redis\Connection
     */
    public static function getRedis() {
        return Yii::$app->redis;
    }

    /**
     * 推入消息
     * @param string $key 键值
     * @param string $message 消息体
     * @param integer $time 消费时间
     */
    public static function push($key, $message, $time=null) {
        if($time && $time>time()) {
            $redis = static::getRedis();

            $hash_key = md5(ToolsUtil::randStr(16).'_'.time().'_'.$message);
            $redis->MULTI();
            $redis->ZADD($key.':zset', $time, $hash_key);
            $redis->HSET($key.':hlist', $hash_key, $message);

            $redis->EXPIRE($key.':zset', 864000);//10天后自动过期
            $redis->EXPIRE($key.':hlist', 864000);//10天后自动过期

            return $redis->EXEC();
        } else {
            return RedisQueue::push([$key, $message]);
        }
    }

    /**
     * 获取锁
     * @param string $key 锁键值
     * @param integer $wait_second 等待秒数
     * @return bool
     */
    protected static function getLock($key, $wait_second=0) {
        $ret = true;
        $redis = static::getRedis();

        while( ($val=$redis->INCR($key))!=1) {
            $ret = false;
            if($wait_second-->0) {
                sleep(1);
            } else {
                break;
            }
        }

        $ttl = $redis->TTL($key);
        if($ttl==-1) {
            $redis->EXPIRE($key, 60);
        }

        return $ret;
    }

    /**
     * 解除锁
     * @param string $key 锁键值
     */
    protected static function releaseLock($key) {
        $redis = static::getRedis();
        $redis->DEL($key);
    }

    /**
     * 接收消息
     * @param string $key 队列名称
     */
    public static function receive($key) {
        //先读取错误的记录
        if(static::getLock($key.':lock')) {
            static::syncWaitMessage($key);
            static::releaseLock($key.':lock');
        }
        return RedisQueue::pop([$key]);
    }

    /**
     * 同步等待消息
     * @param string $key 消息队列 key
     */
    public static function syncWaitMessage($key) {
        $redis = static::getRedis();
        $keys = $redis->ZRANGEBYSCORE($key.':zset',0, time());
        if(!$keys) {
            return;
        }
        $message_datas = $redis->executeCommand('HMGET', array_merge([$key.':hlist'], $keys));

        $redis->MULTI();
        foreach($message_datas as $i=>$message_data) {
            $redis->RPUSH($key, $message_data);
            $hash_key = $keys[$i];
            $redis->HDEL($key.':hlist', $hash_key);
            $redis->ZREM($key.':zset', $hash_key);
        }
        $redis->EXEC();
    }

}

