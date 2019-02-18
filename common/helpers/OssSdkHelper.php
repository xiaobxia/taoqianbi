<?php
namespace common\helpers;
use Yii;
require_once Yii::getAlias('@common').'/api/aliyun-oss/autoload.php';

class OssSdkHelper{
    private static $accessKeyId = 'LTAI9jYLQylTWyoT';
    private static $accessKeySecret = 'rkcT7wqGpZ8PpOy2UIM9OoYDpXZgN8';
    private static $endpoint = "oss-cn-hangzhou-internal.aliyuncs.com";

    public static function putFile($file,$filename){
        try {
            $ossClient = new \OSS\OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endpoint);
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
        $bucket = 'kdkj-attach';
        $object = $filename;
        try {
            $ossClient->putObject($bucket, $object, $file);
            return [
                'code'=>0,
                'message'=>'上传成功'
            ];
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
    }

    public static function getFile($filename){
        try {
            $ossClient = new \OSS\OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endpoint);
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
        $bucket = 'kdkj-attach';
        $object = $filename;
        try {
            return [
                'code'=>0,
                'data'=>$ossClient->getObject($bucket, $object)
            ];
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
    }

    public static function delFile($filename){
        try {
            $ossClient = new \OSS\OssClient(self::$accessKeyId, self::$accessKeySecret, self::$endpoint);
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
        $bucket = 'kdkj-attach';
        $object = $filename;
        try {
            $ossClient->deleteObject($bucket, $object);
            return [
                'code'=>0,
                'data'=>'删除成功'
            ];
        } catch (\OSS\Core\OssException $e) {
            return [
                'code'=>-1,
                'message'=>$e->getMessage()
            ];
        }
    }
}