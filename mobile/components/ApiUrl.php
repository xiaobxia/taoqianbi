<?php
namespace mobile\components;

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
			['mobile/'],
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
			['mobile/'],
			['frontend/'],
			$url
		);
	}

	public static function toRouteCredit($route, $scheme = true)
	{
	    $url = parent::toRoute($route, $scheme);
	    return str_replace(
	            ['mobile/'],
	            ['credit/'],
	            $url
	            );
	}

	public static function toH5($url = '', $scheme = true){
		$url = parent::toRoute($url, $scheme);
	    return str_replace(
	            ['mobile/'],
	            ['h5/'],
	            $url
	        );
	}
    public static function toNewh5($url = '', $scheme = true){
        $url = parent::toRoute($url, $scheme);
        return str_replace(
            ['mobile/'],
            ['newh5/'],
            $url
        );
    }

	public static function toCredit($url = '', $scheme = true)
	{
	    $url = parent::to($url, $scheme);
	    return str_replace(
	            ['mobile/'],
	            ['credit/'],
	            $url
	            );
	}
}