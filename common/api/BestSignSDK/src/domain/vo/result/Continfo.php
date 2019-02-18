<?php
namespace BestSignSDK;

class Continfo
{
    public $signid = "";
    public $docid = "";
    public $vatecode = "";
    public $sendemail = "";
    public $email = "";
    public $needVideo = Constants::CONTRACT_NEEDVIDEO_NONE;
    
    public function setData(array $data)
    {
        foreach ($data as $name => $value)
        {
            $this->$name = $value;
        }
    }
}