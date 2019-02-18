<?php
namespace BestSignSDK;

class AutoSignbyCAResult
{
    public $code = 0;
    public $fsdId = "";
    public $fmid = "";
    public $docID = "";
    public $returnurl = "";
    public $vmsg = "";
    
    public function setData(array $data)
    {
        foreach ($data as $name => $value)
        {
            $this->$name = $value;
        }
    }
}