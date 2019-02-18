<?php
namespace BestSignSDK;

class SendUser
{
    public static function buildData($email, $name, $mobile, $sxdays = 3, $selfsign = false, $usertype = Constants::USER_TYPE_PERSONAL, $Signimagetype = false, $emailtitle = 'emailtitle', $emailcontent = '')
    {
        $Signimagetype = $Signimagetype ? 1 : 0;
        $selfsign = $selfsign ? 1 : 0;
        
        $result = array();
        $params = Utils::getMethodParams(__CLASS__, __FUNCTION__);
        foreach ($params as $build_data_param_name)
        {
			$result[$build_data_param_name] = $$build_data_param_name . "";
        }
		$result['UserfileType'] = 1; //"用户使用文件类型，1表示本地文件上传、2、表示使用云文件上传发送合同"
        return $result;
    }
}