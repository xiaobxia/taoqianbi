<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "{{%alipay_repayment_log}}".
 */
class AlipayRepaymentLog extends BaseActiveRecord
{
    
    const STATUS_ING = 0;
    const STATUS_FINISH = 1;
    const STATUS_LOCK = 2;
    const STATUS_FAILED = 3;
    const STATUS_BACK = 4;
    const STATUS_WAIT = 5;

    const SOURCE_UNKONW = 0;
    const SOURCE_BT = 1;
    const SOURCE_MHK = 2;
    const SOURCE_SXD = 3;

    public static $source_list = [
        self::SOURCE_UNKONW => '未知',
        self::SOURCE_BT => '',
    ];

    public static $status = [
        self::STATUS_ING => '未处理',
        self::STATUS_FINISH => '已处理',
        self::STATUS_LOCK => '处理中',
        self::STATUS_FAILED => '需人工处理',
        self::STATUS_BACK => '退回',
        self::STATUS_WAIT => '等待处理',
    ];
    const TYPE_1 = 0;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;
    const TYPE_5 = 5;
    const TYPE_6 = 6;
    const TYPE_7 = 7;
    const TYPE_8 = 8;
    const TYPE_9 = 9;
    public static $types = [
        self::TYPE_1 => '正常',
        self::TYPE_2 => '用户不存在',
        self::TYPE_3 => '订单不存在或已还款',
        self::TYPE_4 => '更改状态失败',
        self::TYPE_5 => '还款锁定冲突中',
        self::TYPE_6 => '代扣还款冲突中',
        self::TYPE_7 => '主动还款冲突中',
        self::TYPE_8 => '支付流水号重复',
        self::TYPE_9 => '扣款进行中'
    ];
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%alipay_repayment_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public static function insertIgnore($params){
        if(!isset($params['sign']) || !$params['sign'] || !isset($params['data']) || !$params['data'] || !isset($params['timestamp'])){
            return false;
        }
//        $sign = strtolower($params['sign']);
        $str_data = $params['data'];
//        $timestamp = $params['timestamp'];
        $type = isset($params['type']) ? $params['type'] : 0;
        /*if($sign != strtolower(md5($timestamp.'#abc!@#'))){
            return false;
        }*/
        $datas = explode('@@@@@@', $str_data);
        $values = [];
        $column_params = [];
        $i = 0;
        foreach($datas as $data){
            $line_data = explode('******', $data);
            if(count($line_data) != 6 && count($line_data) != 7){
                continue;
            }
            $money = trim($line_data[2]);
            $money = intval(str_replace(['+','.',','], '', $money));
            if($money <= 0){
                continue;
            }
            $values[] = '(:oid'.$i.',:account'.$i.',:name'.$i.',:money'.$i.',:date'.$i.',:remark'.$i.',unix_timestamp(),unix_timestamp(),:type'.$i.',:source'.$i.',:is_extend'.$i.')';
            $column_params[':oid'.$i] = trim($line_data[1]);
            $column_params[':account'.$i] = trim($line_data[3]);
            $column_params[':name'.$i] = trim($line_data[4]);
            $column_params[':money'.$i] = $money;
            $column_params[':date'.$i] = trim($line_data[0]);
            $column_params[':remark'.$i] = trim($line_data[5]);
            $column_params[':type'.$i] = $type;
            $column_params[':source'.$i] = $params['source'];
            $column_params[':is_extend'.$i] = trim($line_data[6]);
            $i++;
        }
        if(!$values){
            return false;
        }
        $sql = 'insert ignore into '.self::tableName().'(`alipay_order_id`,`alipay_account`,`alipay_name`,`money`,`alipay_date`,`remark`,`created_at`,`updated_at`,`type`,`source`,`is_extend`) 
                values'.implode(',', $values);
        var_dump($sql);exit;
        self::getDb()->createCommand($sql,$column_params)->execute();
        return true;
    }
}