<?php
namespace BestSignSDK;

class UploadUserImageResult
{
    public $useracount = '';
    public $status = 0;
    
    public function setData(array $data)
    {
        foreach ($data as $name => $value)
        {
            $this->$name = $value;
        }
    }
}