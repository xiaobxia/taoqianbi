<?php
namespace common\services;

use yii\base\Component;
use common\helpers\Util;

class AppEventService extends Component
{
    const EVENT_SUCCESS_REGISTER        = 'onSuccessRegister';          //注册成功
    const EVENT_SUCCESS_BIND_CARD       = 'onSuccessBindCard';          //绑卡通过
    const EVENT_SUCCESS_APPLY           = 'onSuccessApply';             //借款成功
    const EVENT_SUCCESS_POCKET          = 'onSuccessPocket';            //放款成功
    const EVENT_SUCCESS_REPAY           = 'onSuccessRepay';             //还款成功

    /**
     * 执行事件方法
     * @param string $event_name    事件名
     * @param data $param
     */
    public static function OnEvent($event_name, $param = null) {
        $eventList = Util::loadConfig('@common/event')[$event_name];
        if (is_array($eventList) && !empty($eventList)) {
            $res = [];
            foreach($eventList as $key => $value) {
                $className = $value[0];
                $methodName = $value[1];
                if (class_exists($className) && method_exists($className, $methodName)) {
                    $obj = new $className;
                    $res[] = $obj->$methodName($param);
                } else {
                    \Yii::error('AppEventService Class Not Exists. ClassName:' . $className . ' MethodName:' . $methodName);
                }
            }
            return $res;
        }
        return false;
    }

    /**
     * 事件处理统一返回值
     * @param int $code         0成功 其他失败
     * @param string $message
     */
    public static function response($code, $message){
        return ['code' => $code, 'message' => $message];
    }
}