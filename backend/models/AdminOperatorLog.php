<?php
namespace backend\models;

use common\api\RedisQueue;
use common\helpers\Util;
use Yii;
use yii\base\Exception;

/**
 * AdminOperatorLog model
 */
class AdminOperatorLog extends \yii\db\ActiveRecord
{
    public static $operator = [
        'other'    =>   '其他',
        'staff-repay/reset-interest'    => '将订单置为生息中',
        'staff-repay/cancel-part-repay'    => '取消订单部分还款',
        'loan/loan-person-log-out-del'    => '注销/删除资料账户',
        'loan/loan-person-update-phone'    => '更改用户手机',
    ];



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_operator_log}}';
    }


    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
    	return [];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'admin_id' => '操作人id',
            'admin_name' => '操作人名称',
            'ip' => 'ip',
            'log_time' => '记录时间',
            'action' => '操作',
            'extra_id' => '额外id（用户id或订单）',
            'note' => '备注',
            'title' => '操作名称',
            'data' => '数据',
        ];
    }

    /**
     * 通用日志插入
     * @param int $extra_id
     * @param string $note
     * @param array $data
     * @param string $action
     * @param string $title
     * @param bool $async
     * @return bool
     */
    public static function log($extra_id=0, $note='', $data=[], $action='', $title='', $async=false)
    {
        $validateData = [
            'action' => $action,
            'title' => $title,
            'note' => $note,
            'extra_id' => $extra_id,
            'data' => $data,
        ];

        try{
            $saveData = self::getStandardData($validateData);

            if($async)
            {
                //缓存中操作日志的条数
                $key = 'admin_operator_log_amount';
                $keyD = 'admin_operator_log_data';
                $amount = RedisQueue::get(['key'=>$key]);
                $cacheData = json_decode(RedisQueue::get(['key'=>$keyD]),true);
                $cacheData[] = $saveData;
                if($amount>1)
                {
                    RedisQueue::del(['key'=>$key]);
                    RedisQueue::del(['key'=>$keyD]);
                    return self::insertBatchLog($cacheData);
                } else {
                    RedisQueue::set(['expire'=>'864000','key'=>$key,'value'=>$amount+1]);
                    RedisQueue::set(['expire'=>'864000','key'=>$keyD,'value'=>json_encode($cacheData,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE)]);
                    return true;
                }

            } else {
                return self::insertLog($saveData);
            }
        } catch (Exception $e) {
            \Yii::error('后台操作日志插入错误，data：'.var_export($validateData,true));
        }

    }

    /*同步插入数据*/
    protected static function insertLog($data)
    {
        $insertData = self::getStandardData($data);
        $log = new self();
        $log-> admin_id = $insertData['admin_id'];
        $log-> admin_name = $insertData['admin_name'];
        $log-> log_time = $insertData['log_time'];
        $log-> ip = $insertData['ip'];
        $log-> action = $insertData['action'];
        $log-> data = $insertData['data'];
        $log-> extra_id = $insertData['extra_id'];
        $log-> note = $insertData['note'];
        $log-> title = $insertData['title'];
        return $log->save();
    }

    /*批量插入数据*/
    protected static function insertBatchLog($data)
    {
        $obj = new  static();
        return self::getDb()->createCommand()->batchInsert(self::tableName(), array_keys($obj->attributeLabels()), $data)->execute();
    }


    /*获取标准的log数据*/
    protected static function getStandardData($data)
    {
        $curUser = Yii::$app->user->identity;
        if($curUser) {
            $ret['admin_id'] =  $curUser->getId() ;
            $ret['admin_name'] = $curUser->username ;
        } else {
            $ret['admin_id'] =  0 ;
            $ret['admin_name'] = 0 ;

        }
        $ret['ip'] = Util::getUserIP();
        $ret['log_time'] = time();
        $ret['action'] = empty($data['action']) ? Yii::$app->request->get('r','other'):$data['action'];
        $ret['extra_id'] = $data['extra_id']??0;
        $ret['note'] = $data['note']??'';
        $ret['title'] = empty($data['title'])?(self::$operator[$ret['action']] ?? ''):$data['title'];

        $ret['data'] = !empty($data['data'])?json_encode($data['data'],JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE):'';

        return $ret;
    }
}