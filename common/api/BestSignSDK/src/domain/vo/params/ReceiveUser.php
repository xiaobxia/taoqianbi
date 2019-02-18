<?php
namespace BestSignSDK;

class ReceiveUser
{
    public static function buildData($email, $name, $mobile, $usertype = Constants::USER_TYPE_PERSONAL, $needvideo = Constants::CONTRACT_NEEDVIDEO_NONE, $Signimagetype = false)
    {
        $Signimagetype = $Signimagetype ? 1 : 0;
        
        $result = array();
        $params = Utils::getMethodParams(__CLASS__, __FUNCTION__);
        foreach ($params as $build_data_param_name)
        {
            $result[$build_data_param_name] = $$build_data_param_name . "";
        }
        return $result;
    }
}