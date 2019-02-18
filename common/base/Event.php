<?php

namespace common\base;

use yii\base\Event as BaseEvent;

class Event extends BaseEvent {
    
    /**
     * 自定义数据
     * @var array 
     */
    public $custom_data = [];
    
}

