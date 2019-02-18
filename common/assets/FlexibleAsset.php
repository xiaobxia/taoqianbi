<?php
namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * FlexibleAsset
 * Flexible资源包
 * -------------
 * @author Verdient。
 */
class FlexibleAsset extends AssetBundle
{
	public $sourcePath = '@common/assets/source';

	public $jsOptions = ['position' => View::POS_HEAD];

	public $js = [
		'js/flexible.js'
	];
}