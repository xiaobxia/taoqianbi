<?php

namespace common\models\credit_line;

use Yii;

class PhoneCreditLog extends CreditLineActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%phone_credit_log}}';
    }

}