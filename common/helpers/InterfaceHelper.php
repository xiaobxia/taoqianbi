<?php

namespace common\helpers;

use Exception;

/**
 * 接口帮助类
 */
class InterfaceHelper {
    
    /**
     * 判断参数的类型是否合法
     * @param array $params 参数名称
     * @param array $keys 要检查的键值
     * @param string $type 参数类型 目前只支持string/array
     * @param bool $allowEmpty 是否允许为空 注意 数值0 将被视为非empty
     * @return array 返回不合法的键值
     * @throws Exception
     */
    public static function checkParamsType($params, $keys, $type, $allowEmpty) {
        $invalid_keys = [];
        foreach($keys as $key) {
            if(!array_key_exists($key, $params)) {
                $invalid_keys[] = $key;
                continue;
            } else if(!$allowEmpty) {
                if(empty($params[$key]) && $params[$key]!==0) {
                    $invalid_keys[] = $key;
                    continue;
                }
            }
            switch ($type) {
                case 'string':
                    if(is_array($params[$key]) || is_object($params[$key]) ) {
                        $invalid_keys[] = $key;
                    }
                    break;
                case 'array':
                    if(!is_array($params[$key])) {
                        $invalid_keys[] = $key;
                    }
                    break;
                default:
                    throw new Exception('未设定的参数判断类型'.$type);
            }
        }
        return $invalid_keys;
    }
}


