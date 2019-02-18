<?php
namespace BestSignSDK;

class Logger
{
    const DEBUG_LEVEL_NONE = 0;
    const DEBUG_LEVEL_DEBUG = 1;
    const DEBUG_LEVEL_INFO = 2;
    
    private static $_debeg_level = self::DEBUG_LEVEL_NONE;
    private static $_log_dir = '';
    
    public static function setLogDir($path)
    {
        self::$_log_dir = str_replace('\\', '/', $path);
    }
    
    public static function getDebugLevel()
    {
        return self::$_debeg_level;
    }
    
    public static function setDebugLevel($level)
    {
        switch ($level) {
            case self::DEBUG_LEVEL_DEBUG:
            case self::DEBUG_LEVEL_INFO:
                break;
            default:
                $level = self::DEBUG_LEVEL_NONE;
        }
        self::$_debeg_level = $level;
    }
    
    public static function isDebug()
    {
        return self::getDebugLevel() != self::DEBUG_LEVEL_NONE;
    }
    
    public static function addToLog($message)
    {
        if (self::$_debeg_level == self::DEBUG_LEVEL_NONE) {
            return;
        }
        
        if (self::$_debeg_level == self::DEBUG_LEVEL_INFO) {
            self::_infoLog($message);
        }
        else if (self::$_debeg_level == self::DEBUG_LEVEL_DEBUG) {
            self::_debugLog($message);
        }
    }
    
    private static function _infoLog($message)
    {
        if (strlen($message) > 1030) {
            $message = substr($message, 0, 1024) . '......';
        }
        self::_debugLog($message);
    }
    
    private static function _debugLog($message)
    {
        if (empty(self::$_log_dir)) {
            self::$_log_dir = dirname(dirname(__DIR__));
        }
        $log_file = self::$_log_dir . '/bestsign-sdk-log.' . date('Ymd');
        $time = date('Y-m-d H:i:s');
        error_log("[{$time}] {$message}\n", 3, $log_file);
    }
}