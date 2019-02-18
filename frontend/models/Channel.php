<?php
namespace frontend\models;

use common\base\Model;

class Channel extends Model
{

	/**
	 * @var source_id
	 * 渠道编号
	 * --------------
	 * @author Verdient。
	 */
	public $source_id;

	/**
	 * rules()
	 * 校验规则
	 * -------
	 * @inheritdoc
	 * -----------
	 * @author Verdient。
	 */
	public function rules(){
		return [
			['source_id', 'trim', 'on' => ['shortUrl']],
			['source_id', 'required', 'message' => '渠道编号不能为空', 'on' => ['shortUrl']],
			['source_id', 'integer', 'message' => '渠道编号必须为正整数', 'on' => ['shortUrl']],
		];
	}
}