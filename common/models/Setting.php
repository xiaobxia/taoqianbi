<?php

namespace common\models;
use yii\db\ActiveRecord;
use common\api\RedisQueue;
use Yii;

/**
 * This is the model class for table "{{%setting}}".
 *
 * @property integer $id
 * @property string $skey
 * @property string $svalue
 * @property string $stext
 */
class Setting extends ActiveRecord
{
    public $comment;
    const KEY_PREFIX = "page_";
    /**
     * 为了避免配置key的管理混乱，增加配置项都要在此添加key，并注释
     */

    const APP_PROJECT_NAVIGATION = 1;//项目产品列表
    const APP_FUND_NAVIGATION = 2;//基金产品列表
    const APP_FIND_NAVIGATION = 3;//发现产品列表
    const APP_INSURANCE_NAVIGATION = 4;//保险产品列表
    const APP_TRANSFER_NAVIGATION = 5;//转让产品列表

    const APP_H5_TYPE = 1;//h5页面
    const APP_PROJECT_TYPE = 2;//项目列表
    const APP_DETAIL_TYPE = 3;//项目详情


    public static $app_navigations = [
        self::APP_PROJECT_NAVIGATION => '项目产品列表',
        self::APP_FUND_NAVIGATION => '基金产品列表',
        self::APP_INSURANCE_NAVIGATION => '保险产品列表',
        self::APP_TRANSFER_NAVIGATION => '转让产品列表',
    ];

    public static $app_project_type = [
        self::APP_H5_TYPE => "h5页面",
        self::APP_PROJECT_TYPE => "项目列表",
        self::APP_DETAIL_TYPE => "项目详情",
    ];

    // 配置开关
    const GLOBAL_CONFIG_ON = 1;
    const GLOBAL_CONFIG_OFF = 0;
    public static $global_config = array(
        self::GLOBAL_CONFIG_ON => '开启',
        self::GLOBAL_CONFIG_OFF => '关闭',
    );

    public static $keys = [
        'project_index',                // 首页项目配置
        'page_last_day_profits',        // 昨日收益帮助页面id
        'page_jsx',                     // 节生息页面id
        'page_tsx',                     // 即生息页面id
        'page_company_cer',             // 营业执照页面id
        'page_three_service',           // 贴心服务页面id
        'page_invest_awards',           // 投资奖励页面id
        'page_risk_control',            // 风险保证页面id
        'page_packet_and_reward',       // 红包及奖励页面id
        'page_shake_rule_intro',        // 摇一摇规则介绍id
        'page_withdraw_qa',             // 提现常见问题介绍id
        'page_repayment_qa',            // 平台还款介绍页面id
        'page_recharge_and_withdraw',   // 充值、提现服务协议id
        'invite_type_grade',            // 邀请分档配置
        'withdraw_fast',                // 快速提现配置
        'shake_global_config',          // 天天摇一摇全局配置
        'shake_award_yes_config',       // 摇一摇中奖配置
        'shake_award_no_config',        // 摇一摇没中奖配置
        'auto_publish',                 // 自动发布项目配置
        'auto_withdraw',                // 自动审核提现配置
        'auto_withdraw_cmb',            // 自动审核直连打款配置
        'app_new_index',                // App首页baner图配置
        'app_new_index_icon',           // App首页icon配置
        'app_max_daily_quota',          // APP首页每日待抢金额
        'app_enlarge_ratio',            // APP首页待抢金额放大系数
        'app_project_banner',           // App列表页banner配置
        'app_time_stamp',               // 设置全局下发时间戳
        'pc_banner',                    // PC首页banner图配置
    ];

    public static $comments = [
        'project_index' => '首页项目配置',
        'page_last_day_profits' => '昨日收益帮助页面id',
        'page_jsx' => '节生息页面id',
        'page_tsx' => '即生息页面id',
        'page_company_cer' => '营业执照页面id',
        'page_three_service' => '贴心服务页面id',
        'page_invest_awards' => '投资奖励页面id',
        'page_risk_control' => '风险保证页面id',
        'page_packet_and_reward' => '红包及奖励页面id',
        'page_shake_rule_intro' => '摇一摇规则介绍id',
        'page_withdraw_qa' => '提现常见问题介绍id',
        'page_repayment_qa' => '平台还款介绍页面id',
        'page_recharge_and_withdraw' => '充值、提现服务协议id',
        'invite_type_grade' => '邀请分档配置',
        'withdraw_fast' => '快速提现配置',
        'shake_global_config' => '天天摇一摇全局配置',
        'shake_award_yes_config' => '摇一摇中奖配置',
        'shake_award_no_config' => '摇一摇没中奖配置',
        'auto_publish' => '项目自动发布配置',
        'auto_withdraw' => '提现自动审核配置',
        'auto_withdraw_cmb' => '提现直连审核配置',
        'auto_withdraw_big_money' => '大额提现审核配置',
        'auto_send_withdraw_cmb' => '提现直连打款配置',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%setting}}';
    }

    /**
     * Find by key
     * @param $key
     * @return static
     */
    public static function findByKey($key)
    {
        $db_read = Yii::$app->db_kdkj;
        return Setting::find()->where(['skey' => $key])->one($db_read);
    }

    /**
     * 更新配置，如果不存在则创建
     * @param string $key
     * @param string $value
     * @param string $text
     * @return bool
     */
    public static function updateSetting($key, $value, $text='')
    {
        // if (!in_array($key, static::$keys)) return false;
        $setting = static::findByKey($key);
        if (!$setting) {
            $setting = new Setting();
        }
        $setting->skey = $key;
        $setting->svalue = $value;
        if($text){
            $setting->stext = $text;
        }
        return $setting->save();
    }

    /**
     * 获取APP寻宝-理财干货帖子列表
     * @param bool $cache 是否使用缓存
     * @return array
     */
    public static function getBbsQuestion($cache=true)
    {
        $data = [];
        // APP寻宝 理财干货
        $key = 'app_bbs_question';
        $info_redis = Yii::$app->redis->executeCommand('GET', [$key]);

        if(!empty($info_redis) && $cache){
            $data = json_decode($info_redis,true);
        }else{
            $setting = static::findByKey($key);
            if ($setting && isset($setting->svalue)) {
                $question_ids = [];
                //理财干货
                $setting_svalue = unserialize($setting->svalue);
                $data_temp = [];
                if(is_array($setting_svalue['list'])){
                    foreach ($setting_svalue['list'] as $ssl) {
                        $question_ids[] = $ssl['question_id'];
                        $data_temp[$ssl['question_id']] = $ssl['other_field'];
                    }

                    $sql = "select question_id,question_content,cover
                        from aws_question
                        where question_id in(".implode(',', $question_ids).") order by question_id desc";
                    $data['list'] = Yii::$app->db_bbs->createCommand($sql)->queryAll();
                    foreach ($data['list'] as &$dl) {
                        $dl['other_field'] = $data_temp[$dl['question_id']];
                    }
                    $data['updated_at'] = $setting_svalue['updated_at'];
                }
            }
        }

        return $data;
    }

    // 控制APP的放款期限
    const APP_LOAN_TERM_NORMAL   = 0;
    const APP_LOAN_TERM_SEVEN    = 1;
    const APP_LOAN_TERM_FOURTEEN = 2;
    const APP_LOAN_TERM_TWENTY   = 3;

    /**
     * 处理 放款期限
     */
    public static function handleLoanTerm(){
        $now  = time();
        $flag = self::APP_LOAN_TERM_NORMAL;

        if ($now >= strtotime("2017-01-11 00:00:00") && $now <= strtotime("2017-01-16 23:59:59")) {
            // 7
            $flag = self::APP_LOAN_TERM_SEVEN;
        }elseif ($now >= strtotime("2017-01-17 00:00:00") && $now <= strtotime("2017-01-19 23:59:59")) {
            // 21
            $flag = self::APP_LOAN_TERM_TWENTY;
        }elseif ($now >= strtotime("2017-01-20 00:00:00") && $now <= strtotime("2017-01-27 23:59:59")) {
            // 14
            $flag = self::APP_LOAN_TERM_FOURTEEN;
        }
        // // 测试线下逻辑
        if (!YII_ENV_PROD) {
            $flag = self::APP_LOAN_TERM_NORMAL;
        }

        return $flag;
    }

    /**
     * 处理白卡的今日待抢额度
     */
    public static function getAppCardAmount(){
        $max_loan_cache_key = sprintf('%s:%s', RedisQueue::USER_TODAY_LOAN_MAX_AMOUNT, date('Ymd'));
        $todayAmount = RedisQueue::get(['key' => $max_loan_cache_key]);
        if ($todayAmount) {
            if (intval($todayAmount) < 0) {
                $todayAmount = 0;
            }
            if (intval($todayAmount) > 9999999999) {
                $todayAmount = 9999999999;
            }
        }
        else {
            $param_key_quota = 'app_max_daily_quota';
            $setting_obj = Setting::findByKey($param_key_quota);
            $todayAmount = empty($setting_obj) ? 0 : $setting_obj->svalue; // 2500000000 [clarkyu]取不到则置为0
            $expire_time = \strtotime(date('Y-m-d 23:59:59')) - time();
            RedisQueue::set([
                'expire' => $expire_time,
                'key' => $max_loan_cache_key,
                'value' => $todayAmount,
            ]);
        }

        return $todayAmount;
    }

    /**
     * 处理金卡的额度_getAppGlodenAmount
     */
    public static function getAppGlodenAmount(){
        $max_golden_cache_key = sprintf("%s:%s",RedisQueue::USER_TODAY_LOAN_GOLDEN_AMOUNT,date("Ymd"));
        $todayAmount = RedisQueue::get(["key"=>$max_golden_cache_key]);

        if ($todayAmount != false) {
            if ($todayAmount <= 0) {
                $todayAmount = 0;
            }
        }else{
            $param_golden_quota = "app_golden_daily_quota";
            $setting_golden = static::findByKey($param_golden_quota);

            if(false == $setting_golden){
                $setting_golden = new Setting();
                $setting_golden->skey   = $param_golden_quota;
                $setting_golden->svalue = 200000000;
                $setting_golden->stext  = "发薪卡的待抢额度";
                $setting_golden->save();
            }else{
                $setting_golden->svalue = $setting_golden->svalue;
            }
            // 设置缓存
            $todayAmount = $setting_golden->svalue;
            $expire_time = strtotime(date('Y-m-d 23:59:59', time())) - time();
            RedisQueue::set(["expire" => $expire_time,"key"=>$max_golden_cache_key,"value"=> $todayAmount]);
        }
        return $todayAmount;
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    /**
     * 获取白卡/发薪卡报警额度限制
     * @param number $type 1白卡|2发薪卡
     */
    public static function getCardWarnQuota($type=1){
        $key = ($type == 2 ? 'golden_card_warn_quota' : 'white_card_warn_quota');
        $record = Setting::find()->where(['skey'=>$key])->one();
        if($record && $record['svalue']){
            return $record['svalue'];
        }
        return $type == 2 ? 500000 : 300000;
    }

    /**
     * [checkSendWithdrawCmb description]
     * @Author   ZhangDaomin
     * @DateTime 2017-02-07T16:32:44+0800
     * @param    string                   $key [description]
     * @return   [type]                        [description]
     */
    public static function checkSendWithdrawCmb($key='auto_send_withdraw_cmb')
    {
        $res = Setting::find()->where(['skey'=>$key])->one();
        if (!$res || !isset($res['svalue']) || !$res['svalue']) {
            return false;
        }
        return true;
    }
}
