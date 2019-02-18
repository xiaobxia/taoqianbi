<?php

namespace backend\controllers;

use Yii;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\helpers\Url;
use yii\helpers\VarDumper;

use common\helpers\StringHelper;

// require_once Yii::getAlias('@common/api/oss') . '/sdk_xjk.class.php';
require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';

/**
 * AttachmentController controller
 */
class AttachmentController extends BaseController
{
	public $bucket = DEFAULT_OSS_BUCKET;

	public $ossService;

	public function init()
	{
		$this->ossService = new \ALIOSS();
		$this->ossService->set_debug_mode(true);
	}

	/**
	 * @name 内容管理-附件管理-附件列表 (actionList)
	 */
	public function actionList() {
		$prefix = $this->request->get('prefix', '');

		$options = array(
			'delimiter' => '/',
			'prefix' => $prefix,
			'max-keys' => 200,
			//'marker' => 'myobject-1330850469.pdf',
		);

		$response = $this->ossService->list_object($this->bucket, $options);
		if ($response->isOK()) {
			$sxe = new \SimpleXMLElement($response->body);
			$contents = $sxe->Contents;
			$prefixes = $sxe->CommonPrefixes;
		} else {
			$contents = [];
			$prefixes = [];
		}
		return $this->render('list', [
			'contents' => $contents,
			'prefixes' => $prefixes,
			'prefix' => $prefix
		]);
	}

	/**
	 * @name 内容管理-附件管理-添加附件/actionAdd
	 */
	public function actionAdd($defaultType = 'xjk_yy') {
		if ($this->request->isPost) {
			$type = $this->request->post('type', 'article');
			$file = UploadedFile::getInstanceByName('attach');
			$fileExtension = $file->extension;
			$validator = new FileValidator();
			$xlsExtensions = ['xls','xlsx','csv'];
			$imgExtensions = ['jpg', 'png', 'gif'];
			$validator->extensions = array_merge($imgExtensions, $xlsExtensions);
			if(!in_array($fileExtension, $xlsExtensions)){
				$validator->maxSize = 1024 * 1024;
			}
			$validator->checkExtensionByMimeType = false;
			$error = '';
			if (!$validator->validate($file, $error)) {
				return $this->redirectMessage('文件不符合要求：' . $error, self::MSG_ERROR);
			}
			if(in_array($type, ['asset_plat_order']) && !in_array($fileExtension, $xlsExtensions)){
				return $this->redirectMessage('文件不符合要求：必须为excel/csv文件', self::MSG_ERROR);
			}
			if(!in_array($type, ['asset_plat_order']) && !in_array($fileExtension, $imgExtensions)){
				return $this->redirectMessage('文件不符合要求：必须为图片文件', self::MSG_ERROR);
			}

			if($type == 'loan_record') {
				$object = $type . '/shop/' . StringHelper::generateUniqid() . '.' . $fileExtension;
			} elseif($type) {
				$object = $type . '/' . StringHelper::generateUniqid() . '.' . $fileExtension;
			} else {
				$object = date('Ymd', time()) . '/' . StringHelper::generateUniqid() . '.' . $fileExtension;
			}
			$file_path = $file->tempName;
			$object = date('Ymd', time()) . '/' . StringHelper::generateUniqid() . '.' . $fileExtension;
			$response = $this->ossService->upload_file_by_file($this->bucket, $object, $file_path);
			if ($response->isOK()) {
				$file_url = OSS_RES_URL . $object;
				return $this->redirectMessage('上传成功，文件地址：' . $file_url, self::MSG_SUCCESS);
			} else {
				return $this->redirectMessage('上传失败', self::MSG_ERROR);
			}
		}
		return $this->render('add', [
			'defaultType' => $defaultType
		]);
	}

	/**
	 * 删除附件
	 */
	public function actionDelete($key)
	{
		$response = $this->ossService->delete_object($this->bucket, $key);
		if ($response->isOK()) {
			return $this->redirectMessage('成功', self::MSG_SUCCESS);
		} else {
			return $this->redirectMessage('删除失败', self::MSG_ERROR);
		}
	}
}