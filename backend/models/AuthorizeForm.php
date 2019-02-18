<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use common\helpers\CommonHelper;
/**
 * ContactForm is the model behind the contact form.
 */
class AuthorizeForm extends Model
{
    public $name;
    public $idnum;
    public $phone;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // name, email, subject and body are required
            [['name', 'idnum','phone'], 'required']
        ];
    }
}
