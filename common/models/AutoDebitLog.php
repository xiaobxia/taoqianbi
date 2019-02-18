<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/3
 * Time: 11:04
 */
namespace common\models;
use yii;
use yii\db\ActiveRecord;
use yii\base\UserException;

class AutoDebitLog extends ActiveRecord{

    const STATUS_DEFAULT = 0;
    const STATUS_WAIT = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_REJECT = -1;
    const STATUS_FAILED = -2;
    const STATUS_CANCEL = -3;

    const DEBIT_TYPE_CALLBACK_ACTIVE  = 1; //主动还款扣款回调
    const DEBIT_TYPE_CALLBACK_SYSTEM  = 2; //系统代扣扣款回调

    public static $status_list = [
        self::STATUS_DEFAULT => '默认',
        self::STATUS_WAIT => '回调中',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_REJECT => '请求失败',
        self::STATUS_FAILED => '失败',
        self::STATUS_CANCEL => '对账拒绝',
    ];
    const DEBIT_TYPE_SYS = 1;
    const DEBIT_TYPE_COLLECTION = 2;
    const DEBIT_TYPE_BACKEND = 3;
    const DEBIT_TYPE_LITTLE = 4;
    const DEBIT_TYPE_ACTIVE = 5;
    const DEBIT_TYPE_ACTIVE_YMT = 6;
    const DEBIT_TYPE_ACTIVE_HC = 7;

    public static $type_list = [
        self::DEBIT_TYPE_SYS => '系统代扣',
        self::DEBIT_TYPE_COLLECTION => '催收扣款',
        self::DEBIT_TYPE_BACKEND => '后台代扣',
        self::DEBIT_TYPE_LITTLE => '小额代扣',
        self::DEBIT_TYPE_ACTIVE => '主动还款',
        self::DEBIT_TYPE_ACTIVE_YMT => '益码通支付',
        self::DEBIT_TYPE_ACTIVE_HC => '汇潮支付',
    ];
    public function behaviors()
    {
        return [
            yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public static function tableName(){
        return '{{%auto_debit_log}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    public function getCardInfo()
    {
        return $this->hasOne(CardInfo::className(), ['id' => 'card_id']);
    }

    public function getUserLoanOrderRepayment()
    {
        return $this->hasOne(UserLoanOrderRepayment::className(), ['order_id' => 'order_id']);
    }

    public static function saveRecord($params){
        $record = new static();
        foreach($params as $name => $val){
            $record->$name = $val;
        }
        $ret = $record->save();
        if($record->hasErrors()){
            throw new UserException(current($record->getErrors())[0]);
        }
        return $ret ? $record : false;
    }

    public static function updateDebitResult($id, $status, $operator_money, $remark='') {
        $attrs = ['status' => $status, 'updated_at' => time()];
        if ($remark) {
            $attrs['remark'] = $remark;
        }

        return self::updateAll($attrs, [
            'id' => $id,
            'status'=>[self::STATUS_DEFAULT,self::STATUS_WAIT],
            'operator_money'=>$operator_money,
        ]);
    }

}