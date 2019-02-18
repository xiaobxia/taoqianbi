<?php
namespace credit\components;

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
			['credit/', 'credit'.APP_DOMAIN, 'credit'.APP_DOMAIN],
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
			['credit/', 'credit'.APP_DOMAIN, 'credit'.APP_DOMAIN],
			$url
		);
	}

	public static function toCredit($url = '', $scheme = true)
	{
		$url = parent::toRoute($url, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		
		return $url;
	}

	public static function toRouteMobile($route, $scheme = true)
	{
	    $url = parent::toRoute($route, $scheme);
	    return str_replace(
	            ['credit/', 'credit'.APP_DOMAIN],
	            ['mobile/', 'm'.APP_DOMAIN],
	            $url
	            );
	}
	
	public static function toMobile($url = '', $scheme = true)
	{
	    $url = parent::to($url, $scheme);
	    return str_replace(
	            ['credit/', 'credit'.APP_DOMAIN],
	            ['mobile/', 'm'.APP_DOMAIN],
	            $url
	            );
	}
	public static function toRouteApi($route, $scheme = true)
	{
	    $url = parent::toRoute($route, $scheme);
	    return str_replace(
	            ['credit/', 'credit'.APP_DOMAIN],
	            ['frontend/', 'api'.APP_DOMAIN],
	            $url
	            );
	}
	
	public static function toApi($url = '', $scheme = true)
	{
	    $url = parent::to($url, $scheme);
	    return str_replace(
	            ['credit/', 'credit'.APP_DOMAIN],
	            ['frontend/', 'api'.APP_DOMAIN],
	            $url
	            );
	}

	public static function toH5($url = '', $scheme = true){
		 $url = parent::toRoute($url, $scheme);
	    return str_replace(
	            ['credit/', 'credit'.APP_DOMAIN],
	            ['h5/', 'h5'.APP_DOMAIN],
	            $url
	        );
	}

	public static function toNewH5($url = '', $scheme = true){
		 $url = parent::to($url, $scheme);
	    return str_replace(
	            ['credit/'],
	            ['newh5/'],
	            $url
	        );
	}

	public static function toRouteNewH5($url = '', $scheme = true){
		 $url = parent::toRoute($url, $scheme);
	    return str_replace(
	            ['credit/'],
	            ['newh5/'],
	            $url
	        );
	}
}