<?php
namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * WeChatAsset
 * 微信资源包
 * -----------
 * @author Verdient。
 */
class WeChatAsset extends AssetBundle
{
	public $sourcePath = '@common/assets/source';

	public $jsOptions = ['position' => View::POS_HEAD];

	public $depends = [
		'common\assets\FlexibleAsset',
		'common\assets\CommonAsset'
	];
}