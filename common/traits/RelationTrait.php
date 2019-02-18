<?php

namespace common\traits;

use \Yii;

trait RelationTrait {

    /**
     * 缓存 hasOne 时取过的数据
     * @param string $class 关联类型
     * @param string $key 关联类型的属性key
     * @param string $related_key 宿主的属性key
     * @return Object|NULL
     */
    public function tHasOne($class, $key, $related_key) {
        static $cache = [];

        $query = $this->hasOne($class, [$key => $related_key]); //make query obj
        $cache_key = "{$class}-{$query->primaryModel->$related_key}"; //make cache key
        if (! isset($cache[ $cache_key ])) {
            $ret = $query->one();
            $cache[ $cache_key ] = $ret;
        }

        return $cache[ $cache_key ];
    }

    /**
     * 缓存 hasMany 时取过的数据
     * @param string $class 关联类型
     * @param string $key 关联类型的属性key
     * @param string $related_key 宿主的属性key
     * @return Object|NULL
     */
    public function tHasMany($class, $key, $related_key) {
        static $cache = [];

        $query = $this->hasMany($class, [$key => $related_key]); //make query obj
        $cache_key = "{$class}-{$query->primaryModel->$related_key}"; //make cache key
        if (! isset($cache[ $cache_key ])) {
            $ret = $query->all();
            $cache[ $cache_key ] = $ret;
        }

        return $cache[ $cache_key ];
    }
}