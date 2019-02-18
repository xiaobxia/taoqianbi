<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/5/26
 * Time: 19:23
 */

namespace common\helpers;


use common\api\RedisQueue;

class Signature
{

    const REG_SIGN_CODE = 'user.sign.code.rand.%s.%s';
    const GLOBAL_PIC_CODE = 'user.global.sign.code.pic.%s.%s';

    const DEFAULT_SIGN_TYPE = 2;  // 1: RAND 验证，2：PIC CODE 验证

    public static $code;

    public static function checkCodeSign($source, $phone, $sign) {
        $phone = '' . $phone;
        $pre_phone_7 = substr($phone, 0, 7);
        $suf_phone_4 = substr($phone, 7, 4);

        if (!$rand_num = RedisQueue::get(['key' => sprintf(self::REG_SIGN_CODE, $source, $phone)])) {
            return false;
        }

        return $sign == self::createSign($pre_phone_7, intval($rand_num), $suf_phone_4);
    }

    private static function createSign($key1, $key2, $key3) {
        return md5($key1 . md5($key2 . $key3));
    }

    public static function createRandNum() {
        return rand(100000, 999999);
    }

    /** 生成验证码图片
     * @param int $length 验证码长度
     * @param Array $param 參數
     * @return IMG
     */
    public static function createPic($length=4, $param=array()){
        $authnum = self::random($length); //生成验证码字符.
        self::$code = $authnum;

        $width = isset($param['width'])? $param['width'] : 56; //文字宽度
        $height = isset($param['height'])? $param['height'] : 36; //文字高度
        $pnum = isset($param['pnum'])? $param['pnum'] : 100; //干扰象素个数
        $lnum = isset($param['lnum'])? $param['lnum'] : 5; //干扰线条数

        $pw = $width*$length+10;
        $ph = $height+6;

        $im = imagecreate($pw,$ph);   //imagecreate() 新建图像，大小为 x_size 和 y_size 的空白图像。
        ImageColorAllocate($im, 238,238,238); //设置背景颜色

        $values = array(
            mt_rand(0,$pw), mt_rand(0,$ph),
            mt_rand(0,$pw), mt_rand(0,$ph),
            mt_rand(0,$pw), mt_rand(0,$ph),
            mt_rand(0,$pw), mt_rand(0,$ph),
            mt_rand(0,$pw), mt_rand(0,$ph),
            mt_rand(0,$pw), mt_rand(0,$ph)
        );
        imagefilledpolygon($im, $values, 6, ImageColorAllocate($im, mt_rand(170,255),mt_rand(200,255),mt_rand(210,255))); //设置干扰多边形底图
        $grey = imagecolorallocate($im, 128, 128, 128);

        /* 文字 */
        for ($i = 0; $i < strlen($authnum); $i++){
            //$font = ImageColorAllocate($im, mt_rand(0,50),mt_rand(0,150),mt_rand(0,200));//设置文字颜色
            $font = \Yii::getAlias('@common/components/font') . '/OctemberScript.ttf';
            $x = $i/$length * $pw + rand(1, 6); //设置随机X坐标
            $y = rand($ph/2, $ph);   //设置随机Y坐标
            imagettftext($im, 15, 0, $x, $y, $grey, $font, substr($authnum,$i,1));
           // imagestring($im, mt_rand(4,6), $x, $y, substr($authnum,$i,1), $font);
        }

        /* 加入干扰象素 */
        for($i=0; $i<$pnum; $i++){
            $dist = ImageColorAllocate($im, mt_rand(0,255),mt_rand(0,255),mt_rand(0,255)); //设置杂点颜色
            imagesetpixel($im, mt_rand(0,$pw) , mt_rand(0,$ph) , $dist);
        }

        /* 加入干扰线 */
        for($i=0; $i<$lnum; $i++){
            $dist = ImageColorAllocate($im, mt_rand(50,255),mt_rand(150,255),mt_rand(200,255)); //设置线颜色
            imageline($im,mt_rand(0,$pw),mt_rand(0,$ph),mt_rand(0,$pw),mt_rand(0,$ph),$dist);
        }

        return $im;
    }

    /** 产生随机数函数
     * @param int $length 需要随机生成的字符串數
     * @param int $type 1: 数字，2：数字加字符
     * @return String
     */
    private static function random($length, $type = 1){
        $hash = '';
        $chars = $type == 1 ? '0123456789' : 'qwertyuiopasdfghjklzxcvbnm0123456789';
        $max = strlen($chars) - 1;
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    public static function checkSign($source, $phone, $deviceId, $sign) {
        if (self::DEFAULT_SIGN_TYPE == 1) {
            if (self::checkCodeSign($source, $phone, $sign)) {
                return true;
            }

        } else if (self::DEFAULT_SIGN_TYPE == 2) {
            if (self::validateCaptcha($source, $deviceId, $sign)) {
                return true;
            }
        }

        return false;
    }

    public static function validateCaptcha($source, $deviceId, $code) {
        if (!$pic_code = RedisQueue::get(['key' => sprintf(self::GLOBAL_PIC_CODE, $source, $deviceId)])) {
            return false;
        }

        return $pic_code == $code;
    }

    public static function setSign($source, $deviceId) {
        if (self::DEFAULT_SIGN_TYPE == 1) {
            $rand_num = self::createRandNum();
            if (!RedisQueue::set(['expire' => 60, 'key' => sprintf(Signature::REG_SIGN_CODE, $source, $deviceId), 'value' => $rand_num])) {
                return [
                    'code' => -1,
                    'message' => '随机码获取失败',
                ];
            }

            return [
                'code' => 100,
                'message' => '随机码获取成功',
                'data' => $rand_num,
            ];
        } elseif(self::DEFAULT_SIGN_TYPE == 2) {
            return [
                'code' => -3,
                'message' => '请输入验证码',
            ];
        }

        return [
            'code' => -1,
            'message' => '校验状态失败',
        ];
    }

}
