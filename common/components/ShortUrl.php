<?php
namespace common\components;

use yii\base\InvalidConfigException;
use common\base\RESTComponent;

/**
 * ShortUrl
 * 短链接
 * --------
 * @version 1.0.0
 * @author Verdient。
 */
class ShortUrl extends RESTComponent
{
	/**
	 * @var $appKey
	 * App密钥
	 * ------------
	 * @author Verdient。
	 */
	public $appKey = null;

	/**
	 * generate(String $url)
	 * 生成新的短链接
	 * ---------------------
	 * @param String $url 链接
	 * ----------------------
	 * @return String
	 * @author Verdient。
	 */
	public function generate($url){
		$cUrl = new CUrl($this->_requestUrl['generate']);
		$cUrl->setQuery([
			'source' => $this->appKey,
			'url_long' => $url
		]);
		$result = $cUrl->get();
		return (isset($result[0]) && isset($result[0]['url_short'])) ? $result[0]['url_short'] : false;
	}
}