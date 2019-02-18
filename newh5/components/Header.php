<?php
namespace newh5\components;
use yii\base\Widget;
class Header extends Widget{
    public function run(){
        return $this->render('header');
    }
}