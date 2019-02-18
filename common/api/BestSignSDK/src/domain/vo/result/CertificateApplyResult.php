<?php
namespace BestSignSDK;

class CertificateApplyResult
{
    public $isResult = false;
    public $cerNo = '';
    public $msg = '';
    
    public function setData(array $data)
    {
        foreach ($data as $name => $value)
        {
            $this->$name = $value;
        }
    }
}