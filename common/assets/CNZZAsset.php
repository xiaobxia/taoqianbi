<?php
namespace common\assets;

use yii\web\AssetBundle;

/**
 * CNZZAsset
 * CNZZ资源包
 * ---------
 * @author Verdient。
 */
class CNZZAsset extends AssetBundle
{
	public $sourcePath = '@common/assets/source';

	public $js = [
		'https://s22.cnzz.com/z_stat.php?id=1274363233&web_id=1274363233',
		'js/cnzz.js'
	];
}