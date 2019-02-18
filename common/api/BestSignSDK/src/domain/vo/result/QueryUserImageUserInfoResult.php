<?php
namespace BestSignSDK;

class QueryUserImageUserInfoResult
{
    public $useracount = "";
    public $image = '';
    public $Createtime = "";
    public $usertype = 0;
    public $status = 0;
    
    public function setData(array $data)
    {
        foreach ($data as $name => $value)
        {
            $this->$name = $value;
        }
    }
}