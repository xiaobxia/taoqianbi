<?php
namespace common\helpers;
use yii\helpers\Url as BaseUrl;

/**
 * Url
 * Url助手
 * ------
 * @author Verdient。
 */
class Url extends BaseUrl
{
    /**
     * toStatic(String $url)
     * 生成静态文件地址
     * ---------------------
     * @param String $url url地址
     * -------------------------
     * @return String
     * @author Verdient。
     */
    public static function toStatic($url){
        if(static::isRelative($url)){
            if(mb_substr($url, 0, 1) === '/'){
                return static::base() . static::to($url);
            }
            return static::to($url);
        }
        return $url;
    }
}