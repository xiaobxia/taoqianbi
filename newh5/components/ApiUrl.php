<?php
namespace newh5\components;

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
			['newh5/'],
			['frontend/'],
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
			['newh5/'],
			['frontend/'],
			$url
		);
	}

	public static function toRouteMobile($route, $scheme = true)
	{
		$url = parent::toRoute($route, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['mobile/'],
			$url
		);
	}

	public static function toMobile($url = '', $scheme = true)
	{
		$url = parent::to($url, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['mobile/'],
			$url
		);
	}

	public static function toRouteCredit($route, $scheme = true)
	{
		$url = parent::toRoute($route, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['credit/'],
			$url
		);
	}

	public static function toCredit($url = '', $scheme = true)
	{
		$url = parent::to($url, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['credit/'],
			$url
		);
	}

	public static function toRouteH5Mobile($route, $scheme = true)
	{
		$url = parent::toRoute($route, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['h5/mobile/'],
			$url
		);
	}

	public static function toH5Mobile($url = '', $scheme = true)
	{
		$url = parent::to($url, $scheme);
		if (strpos($url, '?') === false) {
			$url .= "?clientType=wap";
		} else {
			$url .= "&clientType=wap";
		}
		return str_replace(
			['newh5/'],
			['h5/mobile/'],
			$url
		);
	}
}