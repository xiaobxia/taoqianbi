<?php
namespace console\server;

class Module extends \yii\base\Module
{
    public function init()
    {
        parent::init();
        // ... other configurations for the module ...
        \Yii::configure($this, require(__DIR__ . '/config.php'));
        
    }
}