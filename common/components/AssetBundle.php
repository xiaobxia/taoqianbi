<?php
namespace common\components;

class AssetBundle extends \yii\web\AssetBundle {

	public function init()
	{
		parent::init();
		// 当静态资源放在web可见的目录时，自动在文件末尾添加版本号 即不是第三方时
		if (!$this->sourcePath) {
			foreach ($this->js as $key => $js) {
				$this->js[$key] = $js . '?v=' .filemtime($js);
			}

			foreach ($this->css as $key => $css) {
				$this->css[$key] = $css . '?v=' .filemtime($css);
			}
		}
	}
}
