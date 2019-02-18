<?php
namespace common\models;

use Yii;
use yii\base\Exception;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class CreditZzc
{
    const TYPE_BLACKLIST = 1;

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期
    
    public static $type_list = [
        self::TYPE_BLACKLIST => '黑名单'
    ];

    public static $blacklist_records_map = [
        'name' => '姓名',
        'pid' => '身份证',
        'mobile' => '手机号',
        'work_address' => '工作地址',
        'loan_type' => '借款类型',
        'address' => '联系地址',
        'confirm_type' => '黑名单类型',
        'applied_at' => '该黑名单申请贷款的时间',
        'confirmed_at' => '次申请被确认为黑名单的时间',
        'confirm_detailsconfirm_details' => '该黑名单的确认细节',
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

    //获取匹配到的黑名单记录条数
    public function getBlacklistCount(){
        try{
            $data = $this->getData();
            $count = $data['count'];
            return $count;
        }catch (Exception $e){
            return null;
        }
    }

    //获取匹配到的黑名单分别来自几家机构
    public function getBlacklistTenantCount(){
        try{
            $data = $this->getData();
            $count = $data['tenant_count'];
            return $count;
        }catch (Exception $e){
            return null;
        }
    }

    //获取黑名单的详细情况数组
    public function getBlacklistRecordsArr(){
        try{
            $data = $this->getData();
            $records = $data['records'];
            return $records;
        }catch (Exception $e){
            return null;
        }
    }
    //获取黑名单的详细情况文字描述
    public function getBlacklistRecordsText(){
        try{
            $data = $this->getData();
            $records = $data['records'];
            if(!empty($records)){
                $list = [];
                foreach($records as $v){
                    $str = '';
                   foreach($v as $key => $val){
                       $str .= $this::$blacklist_records_map[$key].":".$val;
                   }
                    $list[] = $str;
                }
            }
            return $records;
        }catch (Exception $e){
            return null;
        }
    }

    private function getData(){
        if(empty($this->data)){
            throw new Exception('');
        }
        $data = json_decode($this->data,true);
        if(empty($data)){
            throw new Exception('');
        }
        return $data;
    }

    //  public static function creditQueryLogOverdue($params,$dbName = null)
    // {
    //     if(is_null($dbName))
    //         $creditQueryLog = self::findByCondition($params)->orderBy('id Desc')->one();
    //     else
    //         $creditQueryLog = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
    //     //如果状态已过期或者创建时间超过1个月
    //     if(null != $creditQueryLog)
    //     {
    //         if($creditQueryLog->is_overdue == self::IS_OVERDUE_1 || $creditQueryLog->updated_at + 90*86400 < time())
    //         {
    //             if($creditQueryLog->is_overdue != self::IS_OVERDUE_1)
    //             {
    //                 $creditQueryLog->is_overdue = self::IS_OVERDUE_1;
    //                 $creditQueryLog->save();
    //             }

    //         }
    //     }
    //     return $creditQueryLog;
    // }


}