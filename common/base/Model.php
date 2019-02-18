<?php
namespace common\base;

use yii\base\Model AS BaseModel;

/**
 * Model
 * 基础模型
 * -------
 * @author Verdient。
 */
class Model extends BaseModel
{
    /**
     * load(Array $data, String $formName)
     * 载入数据到模型
     * -----------------------------------
     * @inheritdoc
     * -----------
     * @return Boolean
     * @author Verdient。
     */
    public function load($data, $formName = ''){
        return parent::load($data, $formName);
    }
}