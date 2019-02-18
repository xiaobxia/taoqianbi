<?php
/**
 * 用户操作日志
 */

namespace common\models;

use common\helpers\Util;

class UserLog extends \yii\mongodb\ActiveRecord {
    
    const LogStatusSuccess  = 1;     //操作成功
    const LogStatusFailed   = 2;     //操作失败
    
    const LogTypeBindCard   = 1;    //用户绑卡
    const LogTypeUnBindCard = 2;    //用户解绑卡
    const LogTypeRealVerify = 3;    //用户实名
    const LogChangePlatform = 4;    //用户切换银行卡通道
    const LogTypeCharge     = 5;    //用户充值
    const LogTypeChargeCall = 6;    //用户充值回调
    const LogTypeWithdraw   = 7;    //用户提现
    const LogTypeBindCardCall = 8;  //用户绑卡回调
    const LogTypeDeductMoney = 9;   //用户代扣
    const LogTypeFund       = 10;   //基金
    const LogTypeInsurance  = 11;   //保险
    
    const LogDealYes    = 1;    // 客服已处理
    const LogDealNo     = 2;    // 客服未处理
    
    public static $Log_Type = [
        self::LogTypeBindCard => "绑卡",
        self::LogTypeUnBindCard => "解绑卡",
        self::LogTypeRealVerify => "实名",
        self::LogChangePlatform => "切换通道",
        self::LogTypeCharge => "充值",
        self::LogTypeWithdraw => "提现",
        self::LogTypeDeductMoney => "后台扣款",
    ];    
    
    public static $err_code   = 0;    //日志错误码
    public static $err_msg    = '';   //日志错误说明
    public static $log_status = 1;    //日志状态
    
    //日志类型值描述
    private static $_log_type_desc = [
        self::LogTypeBindCard   => "用户绑卡",
        self::LogTypeUnBindCard => "用户解绑卡",
        self::LogTypeRealVerify => "用户实名",
        self::LogChangePlatform => "用户切换银行卡通道",
        self::LogTypeCharge     => "用户充值",
        self::LogTypeChargeCall => "用户充值回调",
        self::LogTypeWithdraw   => "用户提现",
        self::LogTypeBindCardCall => "用户绑卡回调",
        self::LogTypeDeductMoney => "后台扣款",
        self::LogTypeFund        => "基金",
        self::LogTypeInsurance   => "保险",
    ];
    
    // 客服处理情况
    public static $deal_info = [
        self::LogDealNo => '未处理',
        self::LogDealYes => '已处理',
    ];
    
    private static $_user   = [];     //用户信息
    private static $_params = [];     //接口参数
    private static $_log    = [];     //日志
    private static $_log_cc = [];     //日志详细内容
    
    private static $_log_begin = false;//日志记录开始
    private static $_log_type  = 0;
    private static $_log_title = '';

    public static function collectionName()
    {
        return 'user_log';
    }


    /**
     * 日志记录开始
     * @param mixed $type   绑卡类型
     */
    public static function begin($type) {

        register_shutdown_function(function(){
            UserLog::end();
        });
        
//         if(!extension_loaded('mongo')) {//没有安装mongo扩展
//             return false;
//         }

        self::_set_log_type($type);
        if(!self::$_log_type) {
            return false;
        }
        
        self::$_log_begin = true;   //日志记录开始
        
        //日志 日志类型，操作人，开始时间，结束时间，设备类型，IP，日志详细内容
        self::$_log = [
                'type' => intval(self::$_log_type),
                'title'=> self::$_log_title,
                'begin_time' => time(),
        ];
        
        //日志详细
        $log_detail = [
                'time' => time(),
                'title'=> self::$_log['title'] . '-开始',
        ];
        
        if(array_key_exists("pay_password", $_REQUEST)) {
            unset($_REQUEST['pay_password']);  //删除支付密码
        }
        if(array_key_exists("password", $_REQUEST)) {
            unset($_REQUEST['password']);  //删除密码
        }
        
        //接口请求相关参数
        $log_detail = array_merge($log_detail, $_REQUEST);
        
        array_push(self::$_log_cc, $log_detail);
    }
    
    /**
     * 日志记录结束
     */
    public static function end($result = '') {
        if(!self::$_log_begin || empty(self::$_log)) {
            return false;
        }
        
        if (empty(self::$_user)) {  //设置用户信息
            self::setUser(\Yii::$app->user->identity);
        }
        
        self::$_log['err_code'] = self::$err_code;
        self::$_log['err_msg']  = self::$err_msg;
        self::$_log['end_time'] = time(); //结束时间
        self::$_log = array_merge(self::$_log, self::$_user);
        
        $log_data = [];
        if(is_string($result)) {
            $log_data['result'] = $result;
        }
        else if(is_array($result)) {
            $log_data = array_merge($log_data, $result);
        }
        
        self::addLogDetail(self::$_log['title'] . '-结束', $log_data);
        
        self::$_log['details'] = self::$_log_cc;
        
        try {
            $collection = self::getCollection();
            $rs = $collection->insert(self::$_log);
        }
        catch (\Exception $e) {//Mongodb 异常
            //写入文件系统
//             \Yii::info($e->getCode() . "#" . $e->getMessage() , "user.log.*");
//             \Yii::info(var_export(self::$_log, true), "user.log.*");
        }
        
        self::$_log_begin = false;
        self::$_log = [];
        self::$_log_cc = [];
        self::$_log_title = '';
        self::$_log_type = 0;
    }
    
    /**
     * 添加日志
     * @param string $title     日志标题
     * @param array $content    日志内容
     */
    public static function addLogDetail($title, $log_data='') {
        
        if(!self::$_log_begin) return false;
        if(empty($title)) return false;
        
        self::_is_error($log_data);
        
        $log_detail = [];
        $log_detail['title'] = $title;
        if(is_array($log_data)) {
            
            if(isset($log_data['order_id'])) {
                self::$_log['order_id'] = $log_data['order_id'];
            }
            if(isset($log_data['platform'])) {
                self::$_log['platform'] = $log_data['platform'];
            }
            
            //用户提现
            if(self::$_log_type == self::LogTypeWithdraw) {
                if(array_key_exists("pay_password", $log_data)) {
                    unset($log_data['pay_password']);  //删除支付密码
                }
            }
            //用户实名
            else if(self::$_log_type ==  self::LogTypeRealVerify) {
                if (array_key_exists("idcard", $log_data)) {
                    self::$_log['id_card'] = $log_data['idcard'];
                }
                if (array_key_exists("is_real_verify", $log_data)) {
                    self::$_log['is_real_verify'] = $log_data['is_real_verify'];
                }
            }
            
            $log_detail = array_merge($log_detail, $log_data);
        }
        else {
            $log_detail['result'] = $log_data;
        }
        
        $log_detail['time'] = time();
        array_push(self::$_log_cc, $log_detail);
    }
    
    
    /**
     * 异常日志
     * @param Exception $exception
     */
    public static function addExceptionLog($exception) {
        
        if(!self::$_log_begin) return false;
        
        $code = $exception->getCode();
        $message = $exception->getMessage();
        
        $log_data = [];
        $log_data['err_code'] = $code;
        $log_data['err_msg']  = $message;
        $log_data['file']     = $exception->getFile();
        $log_data['line']     = $exception->getLine();
        
        self::addLogDetail(self::$_log['title'] . '异常', $log_data);
        self::end();
    }
    
    
    public static function getAllLog() {
        
        $collection = self::getCollection();
        $cursor = $collection->find(['uid' => 359]);
        
        $rows = [];
        foreach ($cursor as $row) {
            $rows[] = $row;
        }
        
        print_r($rows);
    }
    
    public static function findLike(array $like)
    {
        $collection = self::getCollection();
        $res = $collection->buildLikeCondition('', $like);
                     
        return $res;
    }
    
    /**
     * 设置用户信息
     * @param object $user
     */
    public static function setUser($user) {
        
        if(empty($user)) {
            return false;
        }
        
        if(is_array($user)) {
            self::$_user['uid'] = intval($user['id']);
            self::$_user['phone'] = strval($user['phone']);
        }
        else if(is_object($user)) {
            self::$_user['uid'] = intval($user->id);
            self::$_user['phone'] = strval($user->phone);
        }
        
        self::$_user['client'] = Util::getClientType();
        self::$_user['ip'] = Util::getUserIP();
        
        return true;
    }
    
    /**
     * 更新mongo记录
     * @param MongoId $id
     * @param array   $arr=['op_state', 'op_memo']
     */
    public static function adminUpdate($id, $arr) {
        
        if(!is_array($arr)) {
            return false;
        }
        
        $collection = self::getCollection();
        
        return $collection->update(['_id' => $id], $arr, ['multiple' => true]);
    }
    
    
    /**
     * 日志是否出错
     * @param array $log_data
     */
    private static function _is_error($log_data) {
        
        if(is_string($log_data) || empty($log_data)) {
            return false;
        }
        
        if(array_key_exists('err_code', $log_data) || array_key_exists('err_msg', $log_data)) {
            self::$err_code   = $log_data['err_code'];  //日志错误码
            self::$err_msg    = $log_data['err_msg'];   //日志错误说明
        }
    }
    
    /**
     * 日志类型
     */
    private static function _set_log_type($type) {
        
        if(is_int($type)) {
            self::$_log_type = $type;
            self::$_log_title = self::$_log_type_desc[$type];
        }
        else if(is_object($type)) {

            if(!property_exists($type, 'actionMethod')) {//属性不存在
                return false;
            }
            
            $method = $type->actionMethod;

            if($method == 'actionBindCard') {//用户绑卡
                self::$_log_type = self::LogTypeBindCard;
                self::$_log_title = '用户绑卡';
            }
            else if($method == 'actionLianLianBindNotify') {
                self::$_log_type = self::LogTypeBindCard;
                self::$_log_title = '连连绑卡回调';
            }
            else if($method == 'actionUmpayBindNotify') {
                self::$_log_type = self::LogTypeBindCard;
                self::$_log_title = '联动绑卡回调';
            }
            else if($method == 'actionUnBindCard') {//用户解绑
                self::$_log_type = self::LogTypeUnBindCard;
                self::$_log_title = '用户解绑卡';
            }
            else if($method == 'actionCharge') {//用户充值
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '用户充值';
            }
            else if($method == 'actionLianLianChargeNotify') {
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '连连充值回调';
            }
            else if($method == 'actionUmpayChargeNotify') {
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '联动充值回调';
            }
            else if($method == 'actionYeepayNotify') {
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '易宝充值回调';
            }
            else if($method == 'actionJytNotify') {
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '金运通充值回调';
            }
            else if($method == 'actionBill99Notify') {
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '快钱充值回调';
            }
            else if($method == 'actionRealVerify') {
                self::$_log_type = self::LogTypeRealVerify;
                self::$_log_title = '用户实名认证';
            }
            else if($method == 'actionChangePlatform') {
                self::$_log_type = self::LogChangePlatform;
                self::$_log_title = '切换银行卡通道';
            }
            else if($method == 'actionWithdraw') {
                self::$_log_type = self::LogTypeWithdraw;
                self::$_log_title = '用户提现';
            }
            else if($method == 'actionAddDebit') {
                self::$_log_type = self::LogTypeDeductMoney;
                self::$_log_title = '后台扣款';
            }
            else if($method == 'actionFundTrade') {
                self::$_log_type = self::LogTypeFund;
                self::$_log_title = '基金购买';
            }
            else if($method == 'actionYeeWithdrawNotify') {
                self::$_log_type = self::LogTypeWithdraw;
                self::$_log_title = '易宝提现回调';
            }
            else if($method == 'actionWebCharge'){
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '宝付PC充值请求';
            }
            else if($method == 'actionPcBaofuNotify'){
                self::$_log_type = self::LogTypeCharge;
                self::$_log_title = '宝付PC充值回调';
            }
        }
    }
    
    public static function getDb() {
        
        return \Yii::$app->get('mongodb_log');
    }
    
}
