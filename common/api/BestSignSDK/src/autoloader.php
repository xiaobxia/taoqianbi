<?php
namespace BestSignSDK;

class AutoLoader
{
    public static $class_map = array(
//class_map
'BestSignSDK\Constants' => '/src/integration/Constants.php',
'BestSignSDK\Logger' => '/src/integration/Logger.php',
'BestSignSDK\HttpUtils' => '/src/integration/utils/HttpUtils.php',
'BestSignSDK\Utils' => '/src/integration/utils/Utils.php',
'BestSignSDK\ReceiveUser' => '/src/domain/vo/params/ReceiveUser.php',
'BestSignSDK\SendUser' => '/src/domain/vo/params/SendUser.php',
'BestSignSDK\AutoSignbyCAResult' => '/src/domain/vo/result/AutoSignbyCAResult.php',
'BestSignSDK\CertificateApplyResult' => '/src/domain/vo/result/CertificateApplyResult.php',
'BestSignSDK\Continfo' => '/src/domain/vo/result/Continfo.php',
'BestSignSDK\QueryUserImageUserInfoResult' => '/src/domain/vo/result/QueryUserImageUserInfoResult.php',
'BestSignSDK\UploadUserImageResult' => '/src/domain/vo/result/UploadUserImageResult.php',
//class_map
    );
    
    public static function registAutoload()
    {
        spl_autoload_register(__CLASS__ . '::autoload');
    }

    public static function autoload($class_name)
    {
        $root_dir = dirname(__DIR__);
        if (isset(self::$class_map[$class_name]))
        {
            $file = self::$class_map[$class_name];
            $filepath = $root_dir . '/' . $file;
            require($filepath);
            return;
        }
        /*
        if ('Buzz\\' == substr($class_name, 0, strlen('Buzz\\')))
        {
            $filepath = Constants::$API_DIR . 'libs/' . str_replace('\\', '/', $class_name) . '.php';
            require($filepath);
            //die($filepath);
        }
        else if ('Gitlab\\' == substr($class_name, 0, strlen('Gitlab\\')))
        {
            $filepath = Constants::$API_DIR . 'libs/' . str_replace('\\', '/', $class_name) . '.php';
            require($filepath);
            //die($filepath);
        }
        */
    }
}

