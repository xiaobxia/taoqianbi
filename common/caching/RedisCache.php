<?php
namespace common\caching;

use Yii;
use yii\base\InvalidConfigException;
use yii\redis\Cache;

/**
 * Redis Cache
 * Redis 缓存
 * -----------
 * @author Verdient。
 */
class RedisCache extends Cache
{
    /**
     * @var $randomDuration
     * 随机有效期
     * --------------------
     * @author Verdient。
     */
    public $randomDuration = 0;

    /**
     * init()
     * 初始化
     * ------
     * @inheritdoc
     * -----------
     * @author Verdient。
     */
    public function init(){
        parent::init();
        if(!is_int($this->randomDuration)){
            throw new InvalidConfigException('randomDuration must be an integer');
        }
        if($this->randomDuration < 0){
            throw new InvalidConfigException('randomDuration can not smaller than 0');
        }
    }

    /**
     * setValue(String $key, String $value, Integer $duration)
     * 设置值
     * -------------------------------------------------------
     * @param String $key 名称
     * @param String $value 内容
     * @param Integer $duration 有效期
     * ------------------------------
     * @return Boolean
     * @author Verdient。
     */
    protected function setValue($key, $value, $duration){
        $duration = $this->_normalizeDuration($duration);
        return parent::setValue($key, $value, $duration);
    }

    /**
     * setValues(Array $data, Integer $duration)
     * 批量设置值
     * -----------------------------------------
     * @param String $data 要设置的数据
     * @param Integer $duration 有效期
     * ------------------------------
     * @return Array
     * @author Verdient。
     */
    protected function setValues($data, $duration){
        $duration = $this->_normalizeDuration($duration);
        return parent::setValues($data, $duration);
    }

    /**
     * addValue(String $key, String $value, Integer $duration)
     * 添加值
     * -------------------------------------------------------
     * @param String $key 名称
     * @param String $value 内容
     * @param Integer $duration 有效期
     * ------------------------------
     * @return Boolean
     * @author Verdient。
     */
    protected function addValue($key, $value, $duration){
        $duration = $this->_normalizeDuration($duration);
        return parent::addValue($key, $value, $duration);
    }

    /**
     * deleteValue(String $key)
     * 删除值
     * ------------------------
     * @param String $key 名称
     * ----------------------
     * @return Boolean
     * @author Verdient。
     */
    protected function deleteValue($key){
        return (bool) $this->getRedis()->executeCommand('DEL', [$key]);
    }

    /**
     * flushValues()
     * 刷新值
     * -------------
     * @return Boolean
     * @author Verdient。
     */
    protected function flushValues(){
        return $this->getRedis()->executeCommand('FLUSHDB');
    }

    /**
     * _normalizeDuration(Integer $duration)
     * 格式化有效期
     * -------------------------------------
     * @param Integer $duration 有效期
     * ------------------------------
     * @return Integer
     * @author Verdient。
     */
    protected function _normalizeDuration($duration){
        if($this->randomDuration > 0 && $duration > 0){
            $min = $duration - $this->randomDuration;
            if($min < 1){
                $min = 1;
            }
            $max = $duration + $this->randomDuration;
            if(function_exists('random_int')){
                return random_int($min, $max);
            }
            return mt_rand($min, $max);
        }
        return $duration;
    }
}