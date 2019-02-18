<?php
namespace common\helpers;
use common\api\RedisQueue;
use Yii;
/**
 * 锁类
 */
class Lock {

    // Lock List
    const LOCK_REG_GET_CODE = "xybt_reg_get_code";  // 注册
    const LOCK_CREDIT_CARD_GET_CODE = "xybt_credit_card_get_code"; // 绑卡
    const LOCK_RESET_PWD_CODE = "xybt_reset_pwd_code"; // 重置密码
    const LOCK_H5_USER_REG_CODE = "xybt_h5_user_reg_code"; // h5注册

    const LOCK_USER_UPLOAD_LOC_PREFIX = 'xybt:user_upload_loc:';

    /**
     * @return \yii\redis\Connection $redis
     */
    public static function getRedis() {
        return Yii::$app->redis;
    }

    /**
     * 获取一个锁
     * @param string $name 锁名
     * @param integer $ttl 锁最大生存周期
     * @param integer $timeout 超时时间
     */
    public static function get($name, $ttl, $timeout = 0) {
        $key = 'lock_' . $name;
        $redis = static::getRedis();
        $value = $redis->incr($key);
        if ($value == 1) {
            $redis->expire($key, $ttl);
            return true;
        }

        if (PHP_SAPI == 'cli') {
            while ($timeout > 0) {
                sleep( 1 );
                $timeout--;
            }
        }
        return false;
    }

    /**
     * 删除锁
     * @param string $name 锁名称
     */
    public static function del($name) {
        $redis = static::getRedis();
        $key = 'lock_' . $name;
        return $redis->del($key);
    }

    public static function lockCode($prefix_key, $rules) { // 设备黑名单列表
        $redis = static::getRedis();
        $device_black_list = ['869435021890973'];

        foreach ($rules as $rule_key => $rule) {
            // 设备黑名单
            if ($rule_key == 'deviceId' && in_array($rule, $device_black_list)) {
                return false;
            }

            // 基本规则 60秒之内3次
            $key = sprintf($prefix_key . '_%s', $rule);
            $rule_cnt = intval($redis->get($key));
            if ($rule_cnt >= 3) {
                return false;
            }

            $redis->setex($key, 60, ++$rule_cnt);

            // 号码次数限制 （每天5次）
            if ($rule_key == 'phone') {
                $phone_key = sprintf($prefix_key . '_phone_cnt_%s', $rule);
                $cnt = intval( $redis->get($phone_key) );
                if ($cnt >= 5) {
                    return false;
                }

                $redis->setex($phone_key, 3600*6, ++$cnt);
            }
        }

        return true;
    }

    public static function lockPicCode($rules) {
        $redis = self::getRedis();
        $source = $rules['source'] ?? "";

        if (!YII_ENV_PROD) {
            $global_key = sprintf('global_pic_unlock_pass_%s_%s', $source, $rules['deviceId']);
            $pass = $redis->get($global_key);
            if ($pass) {
                $redis->del($global_key);
                return true;
            }

            return false;
        }

        if (!isset($rules['deviceId'])) {
            return false;
        }

        foreach ($rules as $rule_key => $rule) {
            if (empty($rule)) {
                continue;
            }

            // 设备号次数限制 3次输验证码
            if ($rule_key == 'deviceId') {
                $phone_key = sprintf('global_pic_lock_device_%s_%s', $source, $rule);
                $cnt = intval($redis->get($phone_key));
                if ($cnt >= 3) {
                    return false;
                }

                $redis->setex($phone_key, 3600*6, ++$cnt);
            }
        }

        return true;
    }

    public static function checkLockStatus($deviceId) {
        $redis = self::getRedis();
        $global_key = 'global_pic_lock_device_' . $deviceId;
        $global_rule = intval($redis->get($global_key));
        return $global_rule < 3;
    }

    public static function unlockCode($source, $deviceId) {
        $redis = self::getRedis();
        $global_key = sprintf('global_pic_lock_device_%s_%s', $source, $deviceId);
        return $redis->del($global_key);
    }

    public static function addSignPass($source, $deviceId, $ttl=60) {
        $redis = self::getRedis();
        $global_key = sprintf('global_pic_unlock_pass_%s_%s', $source, $deviceId);
        return $redis->setex($global_key, $ttl, 1);
    }

}
