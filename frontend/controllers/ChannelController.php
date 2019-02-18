<?php
namespace frontend\controllers;

use Yii;
use frontend\models\Channel;

/**
 * ChannelController
 * 渠道控制器
 * -----------------
 * @author Verdient。
 */
class ChannelController extends BaseController
{
	/**
	 * actionShortUrl()
	 * 生成短链接
	 * ----------------
	 * @author Verident。
	 */
	public function actionShortUrl(){
		$model = new Channel();
		$model->setScenario('shortUrl');
		$model->load(Yii::$app->request->get());
		if($model->validate()){
			$url = Yii::$app->params['channel']['shortUrl']['url'] . '?source_id=' . $model->source_id;
			try{
				$shortUrl = Yii::$app->shortUrl->generate($url);
			}catch(\Exception $e){
				return [
					'code' => -1,
					'message' => '生成短链接失败',
					'data' => [],
				];
			}
			return [
				'code' => 0,
				'message' => 'success',
				'data' => [
					'source_id' => $model->source_id,
					'short_url' => $shortUrl,
					'result' => [
						'url_short' => $shortUrl,
						'url_long' => $url,
						'type' => 0
					]
				]
			];
		}else{
			$error = $model->getFirstErrors();
			return [
				'code' => -1,
				'message' => reset($error),
				'data' => []
			];
		}
	}
}