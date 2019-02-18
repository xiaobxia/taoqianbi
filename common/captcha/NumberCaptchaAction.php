<?php
/**
 * Created by PhpStorm.
 * User: clz
 * Date: 2017/9/15
 * Time: 14:42
 * 落地页验证码
 */
namespace common\captcha;
use yii\captcha\CaptchaAction as DefaultCaptchaAction;
use Yii;
use yii\web\Response;

class NumberCaptchaAction extends DefaultCaptchaAction
{
    public $autoRegenerate = true;

    /**
     * 重写验证码 改为纯数字
     * @return string
     */
    protected function generateVerifyCode()
    {
        if ($this->minLength > $this->maxLength) {
            $this->maxLength = $this->minLength;
        }
        if ($this->minLength < 3) {
            $this->minLength = 3;
        }
        if ($this->maxLength > 8) {
            $this->maxLength = 8;
        }
        $length = mt_rand($this->minLength, $this->maxLength);
        $digits = '0123456789';
        $code = '';
        for ($i = 0; $i < $length; ++$i) {
            $code .= $digits[mt_rand(0, 9)];
        }
        return $code;
    }

    /**
     * 每次自动刷新
     * @return array|string
     */
    public function run()
    {
        if ($this->autoRegenerate && Yii::$app->request->getQueryParam(self::REFRESH_GET_VAR) === null) {
            $this->setHttpHeaders();
            Yii::$app->response->format = Response::FORMAT_RAW;
            return $this->renderImage($this->getVerifyCode(true));
        }
        return parent::run();
    }

}
