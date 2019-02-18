<?php
namespace frontend\components;

class ApiUrl extends \yii\helpers\BaseUrl
{
	public static function toRoute($route, $scheme = true)
	{
		$url = parent::toRoute($route, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['frontend/', 'api'.APP_DOMAIN, 'api'.APP_DOMAIN],
			$url
		);
	}
	
	public static function to($url = '', $scheme = true)
	{
		$url = parent::to($url, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['frontend/', 'api'.APP_DOMAIN, 'api'.APP_DOMAIN],
			$url
		);
	}
	public static function toRouteMobile($route, $scheme = true)
	{
	    $url = parent::toRoute($route, $scheme);
	    return str_replace(
	            ['frontend/', 'api'.APP_DOMAIN],
	            ['mobile/', 'm'.APP_DOMAIN],
	            $url
	            );
	}
	
	public static function toMobile($url = '', $scheme = true)
	{
	    $url = parent::to($url, $scheme);
	    return str_replace(
	            ['frontend/', 'api'.APP_DOMAIN],
	            ['mobile/', 'm'.APP_DOMAIN],
	            $url
	            );
	}
	public static function toRouteCredit($route, $scheme = true)
	{
	    $url = parent::toRoute($route, $scheme);
	    return str_replace(
	            ['frontend/', 'api'.APP_DOMAIN],
	            ['credit/', 'credit'.APP_DOMAIN],
	            $url
	            );
	}
	
	public static function toCredit($url = '', $scheme = true)
	{
	    $url = parent::to($url, $scheme);
	    return str_replace(
	            ['frontend/', 'api'.APP_DOMAIN],
	            ['credit/', 'credit'.APP_DOMAIN],
	            $url
	            );
	}
}