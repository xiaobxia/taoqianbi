<?php
namespace common\widgets;
use yii\base\Widget;

class CheckPayPwd extends Widget{
    public $success_url;// 密码成功后挑战地址
    public $js_callback;// 密码成功后执行的js函数
    public $header = '';// 密码框头部样式
    public function init(){
    }
    public function run(){
        return $this->render('check_pay_pwd',[
                'success_url' => $this->success_url,
                'header' => $this->header,
                'js_callback'=>$this->js_callback,
        ]);
    }

}