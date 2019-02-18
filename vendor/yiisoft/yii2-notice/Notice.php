<?php

namespace yii\notice;

use yii\base\Component;

abstract class Notice extends Component{
    /**
     * @param params 发送告警需要的参数
     */
    abstract public function send($params);
}