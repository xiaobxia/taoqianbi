<?php

namespace common\assets;

/**
 * CommonAsset
 * 公共资源包
 * -----------
 * @author Verdient。
 */
class CommonAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@common/assets/source';

	public $js = [
		'js/common.js'
	];

	public $css = [
		'css/common.css'
	];

	public $jsOptions = [
		'position' => \yii\web\View::POS_HEAD
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];
}
