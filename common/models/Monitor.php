<?php

namespace common\models;

use Yii;
use common\services\MonitorService;
use common\helpers\ToolsUtil;

/**
 * 监控
 * This is the model class for table "{{%monitor}}".
 *
 * @property string $id
 * @property integer $type 类型
 * @property string $name 名称
 * @property string $config 配置 JSON格式
 * @property string $recent_log 最近日志
 * @property string $check_interval 检查间隔（秒）
 * @property integer $status 状态值 
 * @property integer $last_notify_time 最近通知时间
 * @property integer $last_check_time 上次系统自动检查的时间
 * @property integer $next_check_time 下次检查的时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Monitor extends \yii\db\ActiveRecord
{
    const TYPE_REDIS_QUEUE = 1;//redis队列
    const TYPE_MYSQL_COUNT = 2;//mysql计数
    
    const STATUS_REMOVED = -1;//删除状态 不会进行监控
    const STATUS_NORMAL = 0;//正常状态

    const RECENT_LOG_MAX_COUNT = 20;//最多记录日志数量
    
    const TYPE_LIST = [
        self::TYPE_REDIS_QUEUE=>'REDIS队列',
        self::TYPE_MYSQL_COUNT=>'MYSQL计数',
    ];
    
    const STATUS_LIST = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_REMOVED => '删除'
    ];
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%monitor}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }
    

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'name', 'config', 'check_interval', 'status'], 'required'],
            [['type'],'in','range'=> array_keys(self::TYPE_LIST)],
            [['type', 'check_interval', 'status', 'last_notify_time', 'check_interval', 'next_check_time'], 'integer'],
            [['config', 'recent_log'], 'string'],
            [['name'], 'string', 'max' => 64, 'encoding'=>'utf-8'],
            [['name'], 'unique'],
            [['config'], 'checkConfig'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '监控类型',
            'name' => '监控名称',
            'config' => '监控配置',
            'recent_log' => '最近日志',
            'check_interval' => '监控时间间隔（秒）',
            'notify_condition' => '通知条件，JSON格式',
            'notify_admin_ids' => '通知的管理员ID，以英文逗号分隔',
            'status' => '状态',
            'last_check_time' => '最近检查时间',
            'last_notify_time' => '最近通知时间',
            'next_check_time' => '下次检查时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
    
    /**
     * 获取配置模板
     * @param type $type
     */
    public static function getConfigTemplate($type) {
        $ret = '';
        switch ($type) {
            case static::TYPE_REDIS_QUEUE:
                $ret = '{
    "connection_id":"",
    "key":"",
    "notify_condition":{
        "length":{
            "$gte":
        }
    },
    "occur_times_trigger_notify":2,
    "notify_interval":86400,
    "notify_phones":[],
    "notify_emails":[]
}';
                
                break;
            case static::TYPE_MYSQL_COUNT:
                $ret = '{
    "connection_id":"",
    "sql":"",
    "notify_condition":{
    },
    "occur_times_trigger_notify":2,
    "notify_interval":86400,
    "notify_phones":[],
    "notify_emails":[]
}';
            default:
                break;
        }
        
        return $ret;
    }
    
    /**
     * @inheritdoc
     */
    public function attributeHints() {
        return [
            'config' => '<pre>JSON格式字符串，类似于mongo查询的语法。不同的监控类型添加不同的配置。
                RedisQueue配置： 
                {
                    "connection_id":"",//redis连接组件ID
                    "key":"",//队列的key
                    "notify_condition":{//通知的条件
                        "length":{//检查队列的长度
                            "$gte":8//暂时只支持 $gte或 >= 
                        }
                    },
                    "occur_times_trigger_notify":2,//连续发生多少次错误触发通知
                    "notify_interval":86400,//持续错误时通知的间隔 秒
                    "notify_phones":[],//通知的手机号数组
                    "notify_emails":[]//通知的邮箱数组
               }
               MysqlCount配置： 
                {
                    "connection_id":"",//mysql连接组件ID
                    "sql":"",//查询的SQL语句
                    "notify_condition":{//通知的条件
                        "SQL查询结果KEY值":{//检查队列的长度
                            "$gte":8//暂时只支持 $gte或 >=  $lte或<=
                        }
                    },
                    "occur_times_trigger_notify":2,//连续发生多少次错误触发通知
                    "notify_interval":86400,//持续错误时通知的间隔 秒
                    "notify_phones":[],//通知的手机号数组
                    "notify_emails":[]//通知的邮箱数组
               }
                </pre>',
            'recent_log' => 'JSON格式字符串，如：[["time":12345667,"data":{},"warning":0],["time":12345667,"data":{},"warning":0]]',
        ];
    }
    
    
    
    /**
     * 运行检查 
     */
    public function runCheck() {
        if($this->status<static::STATUS_NORMAL) {
            return;
        }
        switch ($this->type) {
            case static::TYPE_REDIS_QUEUE:
                $this->runCheckRedisQueue();
                break;
            case static::TYPE_MYSQL_COUNT:
                $this->runCheckMysqlCount();
                break;
            default:
                break;
        }
    }
    
    /**
     * 获取检查的数据 主要用于界面展示给管理员
     * @return array
     */
    public function getCheckData() {
        if($this->status<static::STATUS_NORMAL) {
            return [];
        }
        switch ($this->type) {
            case static::TYPE_REDIS_QUEUE:
                $ret = $this->getCheckRedisQueueData();
                break;
            case static::TYPE_MYSQL_COUNT:
                $ret = $this->getCheckMysqlCountData();
                break;
            default:
                $ret = [];
                break;
        }
        return $ret;
    }
    
    /**
     * 获取REDIS队列检查的数据
     * @return array 如 ： [ 'length':15 ]
     */
    public function getCheckRedisQueueData() {
        $config = json_decode($this->config, true);
        $redis = Yii::$app->get($config['connection_id']);
        $queue_key = $config['key'];

        /* @var $monitor_service MonitorService */
        $data = [];

        foreach($config['notify_condition'] as $check_key=>$check_condition) {
            switch ($check_key) {
                case 'length':
                    $queue_length = $redis->executeCommand('LLEN', [$queue_key]);
                    $data['length'] = $queue_length;
                    break;
                default:
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * 获取MysqlCount检查的数据
     * @return array 获取SQL执行返回的结果
     */
    public function getCheckMysqlCountData() {
        $config = json_decode($this->config, true);
        $db = Yii::$app->get($config['connection_id']);
        $sql = $config['sql'];

        /* @var $monitor_service MonitorService */
        /* @var $db \yii\db\Connection */
        $data = $db->createCommand($sql)->queryOne();
        return $data;
    }
    
    /**
     * 获取最近系统自动检查的日志  存储最多20个最近系统自动检查的记录 按检查的时间倒序排列
     * @return array [ ["time":1234566767,"warning":1,"data":{}] ]
     */
    public function getRecentLogs() {
        return $this->recent_log ? json_decode($this->recent_log, true) : [];
    }
    
    /**
     * 获取最近错误的次数 不超过最近日志保存的记录（见getRecentLogs说明）
     * @return integer
     */
    public function getRecentWarningCount() {
        $recentLogs = $this->getRecentLogs();
        $count = 0;
        foreach($recentLogs as $row) {
            if($row['warning']) {
                $count++;
            } else {
                break;
            }
        }
        return $count;
    }
    
    /**
     * redis队列运行检查 
     * 配置格式 ：
     * {
     *      'connection_id':'',//redis连接组件ID
     *      'key':'',//队列的key
     *      'notify_condition':{//通知的条件
     *          "length":{
     *              "$gte":8
     *          }
     *      },
     *      'occur_times_trigger_notify':2,//连续发生多少次数触发通知
     *      'notify_interval':86400,//连接错误通知时间间隔 秒
     *      'notify_phones':[],//通知的手机号
     *      'notify_emails':[]//通知的邮箱地址
     * }
     */
    public function runCheckRedisQueue() {
        $config = json_decode($this->config, true);
        $redis = Yii::$app->get($config['connection_id']);
        $queue_key = $config['key'];
        $monitor_service = Yii::$container->get('monitorService');
        /* @var $monitor_service MonitorService */
        $recent_logs = $this->getRecentLogs();
        $recent_warning_count = $this->getRecentWarningCount();
        $time = time();
        $data_snapshot = $this->getCheckRedisQueueData();
        $current_log = [
            'time'=>$time,
            'warning'=>0,
            'data'=>$data_snapshot
        ];
        $notify_content = null;
        foreach($config['notify_condition'] as $check_key=>$check_condition) {
            switch (trim($check_key)) {
                case 'length':
                    $queue_length = $query_value = $data_snapshot['length'];
                    foreach($check_condition as $condition_key=>$value) {
                        switch (trim($condition_key)) {
                            case '$gte':
                            case '>=':
                                if($queue_length>=$value ) {
                                    if( ($time - $this->last_notify_time>$config['notify_interval']) && $recent_warning_count>=$config['occur_times_trigger_notify'] ) {
                                        //报警
                                        $notify_content = "警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key}  结果值为 {$query_value} 大于等于{$value}。时间为：".date('Y-m-d H:i:s');
                                    }
                                    $current_log['warning'] = 1;
                                    goto MONITOR_END;
                                } else {
                                    $current_log['warning'] = 0;
                                    if($recent_warning_count>=$config['occur_times_trigger_notify']) {
                                        //通知用户恢复正常
                                        $notify_content = "解除警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key}  结果值为 {$query_value} 恢复正常了，时间为:".date('Y-m-d H:i:s');
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        
        MONITOR_END:
            array_unshift($recent_logs, $current_log);
        
        if(count($recent_logs)>static::RECENT_LOG_MAX_COUNT) {
            array_pop($recent_logs);
        }
        $update_attributes = [
            'recent_log'=> json_encode($recent_logs, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE),
            'last_check_time'=>$time,
            'next_check_time'=>$time+$this->check_interval
        ];
        
        $this->updateAttributes($update_attributes);
        
        if($notify_content) {
            try {
                if($monitor_service->notifyPhones($config['notify_phones'], $notify_content) ) {
                    $this->updateAttributes([
                        'last_notify_time'=>$current_log['warning'] ? time() : 0
                    ]);
                } else {
                    Yii::error("监控#{$this->id} {$this->name} 通知手机". implode(',', $config['notify_phones'])."失败");
                }

                if($monitor_service->notifyEmails($config['notify_emails'], $notify_content) ) {
                    $this->updateAttributes([
                        'last_notify_time'=>$current_log['warning'] ? time() : 0
                    ]);
                } else {
                    Yii::error("监控#{$this->id} {$this->name} 通知邮箱". implode(',', $config['notify_emails'])."失败");
                }
            } catch (\Exception $ex) {
                Yii::error("监控#{$this->id} {$this->name} 报警失败: 文件 {$ex->getFile()} 第 {$ex->getLine()} 行 错误 {$ex->getMessage()}");
            }
            
        }
    }
    
    /**
     * MYSQL计数 
     * 配置格式 ：
     * {
     *      'connection_id':'',//redis连接组件ID
     *      'key':'',//队列的key
     *      'notify_condition':{//通知的条件
     *          "SQL查询结果KEY值":{//检查队列的长度
     *                       "$gte":8//暂时只支持 $gte或 >=  $lte或<=
     *                   }
     *      },
     *      'occur_times_trigger_notify':2,//连续发生多少次数触发通知
     *      'notify_interval':86400,//连接错误通知时间间隔 秒
     *      'notify_phones':[],//通知的手机号
     *      'notify_emails':[]//通知的邮箱地址
     * }
     */
    public function runCheckMysqlCount() {
        $config = json_decode($this->config, true);
        $db = Yii::$app->get($config['connection_id']);
        $sql = $config['sql'];
        $monitor_service = Yii::$container->get('monitorService');
        /* @var $monitor_service MonitorService */
        $recent_logs = $this->getRecentLogs();
        $recent_warning_count = $this->getRecentWarningCount();
        $time = time();
        $data_snapshot = $this->getCheckMysqlCountData();
        $current_log = [
            'time'=>$time,
            'warning'=>0,
            'data'=>$data_snapshot
        ];
        $notify_content = null;
        foreach($config['notify_condition'] as $check_key=>$check_condition) {
            $query_value = $data_snapshot[$check_key];
            foreach($check_condition as $condition_key=>$value) {
                switch (trim($condition_key)) {
                    case '$gte':
                    case '>=':
                        if($query_value>=$value ) {
                            if( ($time - $this->last_notify_time>$config['notify_interval']) && $recent_warning_count>=$config['occur_times_trigger_notify'] ) {
                                //报警
                                $notify_content = "警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key} 结果值为 {$query_value} 大于等于{$value}。时间为：".date('Y-m-d H:i:s');
                            }
                            $current_log['warning'] = 1;
                            goto MONITOR_END;
                        } else {
                            $current_log['warning'] = 0;
                            if($recent_warning_count>=$config['occur_times_trigger_notify']) {
                                //通知用户恢复正常
                                $notify_content = "解除警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key}  结果值为 {$query_value} 恢复正常了，时间为:".date('Y-m-d H:i:s');
                            }
                        }
                        break;
                    case '$lte':
                    case '<=':
                        if($query_value<=$value ) {
                            if( ($time - $this->last_notify_time>$config['notify_interval']) && $recent_warning_count>=$config['occur_times_trigger_notify'] ) {
                                //报警
                                $notify_content = "警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key}  结果值为 {$query_value} 小于等于{$value}。时间为：".date('Y-m-d H:i:s');
                            }
                            $current_log['warning'] = 1;
                            goto MONITOR_END;
                        } else {
                            $current_log['warning'] = 0;
                            if($recent_warning_count>=$config['occur_times_trigger_notify']) {
                                //通知用户恢复正常
                                $notify_content = "解除警报: ".static::TYPE_LIST[$this->type]." {$this->name} {$check_key}  结果值为 {$query_value} 恢复正常了，时间为:".date('Y-m-d H:i:s');
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        
        MONITOR_END:
            array_unshift($recent_logs, $current_log);
        
        if(count($recent_logs)>static::RECENT_LOG_MAX_COUNT) {
            array_pop($recent_logs);
        }
        $update_attributes = [
            'recent_log'=> json_encode($recent_logs, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE),
            'last_check_time'=>$time,
            'next_check_time'=>$time+$this->check_interval
        ];
        
        $this->updateAttributes($update_attributes);
        
        if($notify_content) {
            try {
                if($monitor_service->notifyPhones($config['notify_phones'], $notify_content) ) {
                    $this->updateAttributes([
                        'last_notify_time'=>$current_log['warning'] ? time() : 0
                    ]);
                } else {
                    Yii::error("监控#{$this->id} {$this->name} 通知手机". implode(',', $config['notify_phones'])."失败");
                }

                if($monitor_service->notifyEmails($config['notify_emails'], $notify_content) ) {
                    $this->updateAttributes([
                        'last_notify_time'=>$current_log['warning'] ? time() : 0
                    ]);
                } else {
                    Yii::error("监控#{$this->id} {$this->name} 通知邮箱". implode(',', $config['notify_emails'])."失败");
                }
            } catch (\Exception $ex) {
                Yii::error("监控#{$this->id} {$this->name} 报警失败: 文件 {$ex->getFile()} 第 {$ex->getLine()} 行 错误 {$ex->getMessage()}");
            }
            
        }
    }
    
    /**
     * 获取格式化的配置
     * @return array
     */
    public function getConfig() {
        $config_lines = explode("\n", $this->config);
        $config_json = '';
        foreach($config_lines as $line) {
            $config_json .= trim($line);
        }
        $config_json = str_replace([
            "\n","\r\n", "\t"
        ],'',$config_json);
        return json_decode($config_json, true);
    }
    
    /**
     * 检查配置是否合法
     * @return boolean
     */
    public function checkConfig() {
        $config = $this->getConfig();
        if(!$config) {
            $this->addError('config', '配置JSON解析出错！');
            return false;
        }
        
        if( ($invalid_keys = ToolsUtil::checkParamsType($config, ['occur_times_trigger_notify', 'notify_interval'], 'integer', false))) {
            $this->addError('config', '配置里键值'. implode(',', $invalid_keys).'必须为整数且大于0！');
            return false;
        }
        
        if($config['occur_times_trigger_notify']<1 || $config['notify_interval']<1) {
            $this->addError('config', '配置里键值'. implode(',', $invalid_keys).'必须为整数且大于0！');
            return false;
        }
        
        if( ($invalid_keys = ToolsUtil::checkParamsType($config, ['notify_emails', 'notify_phones'], 'array', true))) {
            $this->addError('config', '配置里键值'. implode(',', $invalid_keys).'必须为数组！');
            return false;
        }
        
        if(empty($config['notify_emails']) && empty($config['notify_phones']) ) {
            $this->addError('config', '配置里至少添加通知手机，通知邮箱其中一项！');
            return false;
        }
        
        if( ($invalid_keys = ToolsUtil::checkParamsType($config, ['notify_condition'], 'array', false))) {
            $this->addError('config', '配置里键值'. implode(',', $invalid_keys).'必须为数组！');
            return false;
        }
        
        foreach($config['notify_emails'] as $email) {
            if(!is_string($email) || !ToolsUtil::checkEmail($email)) {
                $this->addError('config', '配置notify_emails里'.$email.'不是合法的邮箱');
                return false;
            }
        }
        
        foreach($config['notify_phones'] as $phone) {
            if(!is_numeric($phone) && !ToolsUtil::checkMobile($phone)) {
                $this->addError('config', '配置notify_phones里'.$phone.'不是合法的手机');
                return false;
            }
        }
        
        switch($this->type) {
            case self::TYPE_REDIS_QUEUE:
                return $this->checkRedisQueueConfig($config);
            case self::TYPE_MYSQL_COUNT:
                return $this->checkMysqlCountConfig($config);
            default :
                break;
        }
    }
    
    /**
     * 检查redis配置
     * @param array $config 配置
     */
    public function  checkRedisQueueConfig($config) {
        if(empty($config['connection_id']) || !is_string($config['connection_id'])) {
            $this->addError('config', '配置connection_id无效');
            return false;
        }
        if(empty($config['key']) || !is_string($config['key'])) {
            $this->addError('config', '配置key无效');
            return false;
        }
    }
    
    /**
     * 检查MysqlCount配置
     * @param array $config 配置
     */
    public function  checkMysqlCountConfig($config) {
        if(empty($config['connection_id']) || !is_string($config['connection_id'])) {
            $this->addError('config', '配置connection_id无效');
            return false;
        }
        if(empty($config['sql']) || !is_string($config['sql'])) {
            $this->addError('config', '配置sql无效');
            return false;
        }
        
        $db = Yii::$app->get($config['connection_id']);
        try {
            $data = $db->createCommand($config['sql'])->queryOne();
        } catch (\Exception $ex) {
            $this->addError('config', '配置sql执行错误，'.$ex->getMessage());
            return false;
        }
        
        foreach($config['notify_condition'] as $check_key=>$check_condition) {
            if(!isset($data[trim($check_key)])) {
                $this->addError('config', '配置sql执行结果值为'.var_export($data, 1).', 不包括KEY:'.$check_key);
                return false;
            }
        }
        
        return true;
    }
    
}
