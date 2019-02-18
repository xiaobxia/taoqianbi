<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/3
 * Time: 16:08
 */
namespace common\models;

use common\base\LogChannel;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * 银行卡信息
 * @property integer $id 自增ID
 * @property integer $user_id 用户ID
 * @property integer $bank_id 银行ID
 * @property string $bank_name 银行名称
 * @property string $card_no 卡号
 * @property bigint $credit_mount 信用卡额度
 * @property integer $valid_period 信用卡有效期限
 * @property int  $type 银行卡类型(1:信用卡   2:借记卡,3:对公账号)
 * @property bigint $phone 手机号
 * @property integer $status 状态
 * @property integer $main_card 是否为主卡
 * @property integer $created_at 创建时间
 * @property integer $updated_at 更新时间
 * @property string $name 用户姓名
 * @property string $bank_address 开户行地址
 */
class CardInfo extends ActiveRecord
{
    const TYPE_CREDIT_CARD = 1;
    const TYPE_DEBIT_CARD = 2;
    const TYPE_UNDEFINE = 3;

    public static $type = [
        self::TYPE_CREDIT_CARD => "信用卡",
        self::TYPE_DEBIT_CARD => "储蓄卡",
        self::TYPE_UNDEFINE => "未知",
    ];
    public static $bankInfo = [
        "1" => "工商银行",
        "2" => "农业银行",
        "3" => "光大银行",
//        "4" => "邮政储蓄银行",
        "5" => "兴业银行",
//        "6" => "深圳发展银行",
        "7" => "建设银行",
        "8" => "招商银行",
        "9" => "中国银行",
        "10" => "浦发银行",
        "11" => "平安银行",
        "12" => "华夏银行",
        "13" => "中信银行",
        "14" => "交通银行",
        "15" => "民生银行",
        "16" => "广发银行",
        "17" => "北京银行",
        "18" => "上海银行",
        "19" => "上海农商银行",
//        "20" => "成都银行",
//        "21" => "渤海银行",
        "22" => "南京银行",
//        "23" => "宁波银行",
//        "49" => "江西银行",
    ];

    /**
     * 打款到账需要用到银行信息，不要注释掉，
     * 否则打款查询状态会出现问题
     **/
    public static $loan_bankInfo = [
        "1" => "工商银行",
        "2" => "农业银行",
        "3" => "光大银行",
        "4" => "邮政储蓄银行",
        "5" => "兴业银行",
        "6" => "深圳发展银行",
        "7" => "建设银行",
        "8" => "招商银行",
        "9" => "中国银行",
        "10" => "浦发银行",
        "11" => "平安银行",
        "12" => "华夏银行",
        "13" => "中信银行",
        "14" => "交通银行",
        "15" => "民生银行",
        "16" => "广发银行",
        "17" => "北京银行",
        "18" => "上海银行",
        "19" => "上海农商银行",
        "20" => "成都银行",
        "21" => "渤海银行",
        "22" => "南京银行",
        "23" => "宁波银行",
        "49" => "江西银行",
    ];

    /**
     * 支持地的银行列表
     * @var array
     */
    public static $bankList = [
        'ICBC' => '1',
        'ABC' => '2',
        'CEB' => '3',
        'PSBC' => '4',
        'CIB' => '5',
        'SDB' => '6',
        'CCB' => '7',
        'CMB' => '8',
        'BOC' => '9',
        'SPDB' => '10',
        'PINGAN' => '11',
        'HXB' => '12',
        'CITIC' => '13',
        'BOCOM' => '14',
        'COMM' => '14',
        'CMBC' => '15',
        'GDB' => '16',
        'BCCB' => '17',
        'BOS'     => '18',
        'SRCB'     => '19',
        // 'BOS'     => '20',
        'CBHB'     => '21',
        // 'HZCB'    => '杭州银行',
        'NJCB'    => '22',
        // 'NJCB'    => '23',
        // 'NJCB'    => '49',
    ];


    public static $creditInfo = [
        "1" => "工商银行",
        "2" => "农业银行",
        "3" => "光大银行",
        "4" => "邮政储蓄银行",
        "5" => "兴业银行",
        "6" => "深圳发展银行",
        "7" => "建设银行",
        "8" => "招商银行",
        "9" => "中国银行",
        "10" => "浦发银行",
        "11" => "平安银行",
        "12" => "华夏银行",
        "13" => "中信银行",
        "14" => "交通银行",
        "15" => "民生银行",
        "16" => "广发银行",
        "17" => "北京银行",
        "18" => "上海银行",
        "19" => "上海农商银行",
        "22" => "南京银行",
    ];

    //百融银行code码
    public static $bairong_bankList = [
        "C10102" => "1",
        "C10103" => "2",
        "C10403" => "3",
        "C10404" => "4",
        "C10309" => "5",
        // "深圳发展银行" => "6",
        "C10105" => "7",
        "C10308" => "8",
        "C10104" => "9",
        "C10310" => "10",
        "C10402" => "11",
        "C10406" => "12",
        "C10302" => "13",
        "C10401" => "14",
        "C10305" => "15",
        "C10306" => "16",
        "C10405" => "17",
        "C10408" => "18",
    ];


    public static $debitbankInfo = [1,2,3,4,5,7,8,9,10,11,12,13,14,15,16,17,18];

    public static $creditBankInfo = [1,2,3,4,5,7,8,9,10,11,12,13,14,15,16];

    const STATUS_DELETE = -1; //已删除
    const STATUS_FALSE = 0; //失效
    const STATUS_SUCCESS = 1; //生效

    public static $status = [
        self::STATUS_SUCCESS => "生效",
        self::STATUS_FALSE => "失效",
        self::STATUS_DELETE => "已删除",
    ];

    const MAIN_CARD_NO = 0; //副卡
    const MAIN_CARD = 1; //主卡

    public static $mark = [
        self::MAIN_CARD_NO => "副卡",
        self::MAIN_CARD => "主卡",
    ];

    //支付渠道
    const HELIPAY = 2;

    //支付渠道简称
    public static $channel_abbreviation = [
        self::HELIPAY => 'helipay'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%card_info}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户ID',
            'bank_id' => '银行ID',
            'bank_name' => '银行名称',
            'card_no' => '银行卡号',
            'valid_period' => 'valid_period',
            'type' => '类型',
            'phone' => '预留手机号',
            'status' => '状态',
            'main_card' => '是否主卡',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
            'name' => '持卡人姓名',
            'bank_address' => '开户行地址',
            'source_id'=>'渠道id',
        ];
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
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

    public static function saveRecord($params){
//         if(!isset($params['bank_id']) || !isset(self::$bankInfo[$params['bank_id']])){
//             return false;
//         }
        $record = new CardInfo();
        foreach($params as $name => $val){
            $record->$name = $val;
        }
        if(!isset($record->bank_name) && isset(self::$bankInfo[$params['bank_id']])){
            $record->bank_name = self::$bankInfo[$params['bank_id']];
        }
        if(!isset($record->type)){
            $record->type = self::TYPE_DEBIT_CARD;
        }
        $record->status = self::STATUS_SUCCESS;
        return $record->save() ? $record : false;
    }

    /**
     * 判断用户是否可以重新绑定主卡
     * @param unknown $user_id
     */
    public static function checkCanRebind($user_id){
        $ret = UserLoanOrder::checkHasUnFinishedOrder($user_id);
        return !$ret;
    }

    /**
     * 判断卡号是否已使用
     * @param unknown $card_no
     */
    public static function checkCardIsUsed($card_no,$source = null){
//        if($source != null){
//            $key =  'cardinfo_checkCardIsUsed';
//            if (!Yii::$app->cache->get($key)) { //记录异常
//                \yii::warning( sprintf('source mssing in %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES)), LogChannel::CHANNEL_USER_LOGIN );
//                \yii::error("card_no：{$card_no}，source_id：{$source}",LogChannel::CHANNEL_USER_LOGIN);
//                \yii::$app->cache->set($key, 1, 300);
//            }
//            $where = ["card_no" => $card_no,'source_id'=>$source];
//        }else{
//            $where = ["card_no" => $card_no];
//        }
        $where = ["card_no" => $card_no];
        $user_ids = CardInfo::find()->where($where)->select('user_id')->asArray()->column();
        if($user_ids){
            $verification = UserVerification::find()->where(['user_id'=>$user_ids])->limit(1)->select('id')->one();
            if($verification){
                return $user_ids;
            }
        }
        return false;
    }

    /**
     * $type 银行卡类型(1:信用卡 2:借记卡,3:对公账号)
     * @param number $type
     */
    public static function getCardConfigList($type=2){
        $baseUrl = \Yii::$app->getRequest()->getAbsoluteBaseUrl();
        $array = self::$debitbankInfo;
        $bank_list = self::$bankInfo;
        $info = [];
        foreach ($bank_list as $k=>$v){
            if(in_array($k,$array)){
                $info[] = [
                    'bank_id' => $k,
                    'bank_name' => CardInfo::$bankInfo[$k],
                    'is_supprot_withhold'=>1,
                    'url' => $baseUrl."/image/bank/bank_".$k.".png",
                ];
            }else{
                $info[] = [
                    'bank_id' => $k,
                    'bank_name' => $bank_list[$k],
                    'is_supprot_withhold'=>0,
                    'url' => $baseUrl."/image/bank/bank_".$k.".png",
                ];
            }
        }
        return $info;
    }
    const CARD_DEBIT_MAX_TIMES = 30;
    public static function checkCardDebitTimes($card_no,$platform=BankConfig::PLATFORM_YEEPAY){
        $times = self::getCardDebitTimes($card_no,$platform);
        if($times && $times > self::CARD_DEBIT_MAX_TIMES){
            throw new \Exception("今天该平台扣款次数已超过".self::CARD_DEBIT_MAX_TIMES.'次');
            //return false;
        }
        return true;
    }
    public static function getCardDebitTimes($card_no,$platform=BankConfig::PLATFORM_YEEPAY){
        $day = date('d');
        $ret = \Yii::$app->redis->executeCommand('GET', ["debit_card_times_{$platform}_{$day}_{$card_no}"]);
        if(!$ret){
            $ret = 0;
        }
        return $ret;
    }
    public static function addCardDebitTime($card_no,$platform=BankConfig::PLATFORM_YEEPAY){
        $day = date('d');
        $key = "debit_card_times_{$platform}_{$day}_{$card_no}";
        if(\Yii::$app->redis->executeCommand('INCRBY', [$key, 1]) > self::CARD_DEBIT_MAX_TIMES){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 86400]);
            //return false;
            throw new \Exception("今天该平台扣款次数已超过".self::CARD_DEBIT_MAX_TIMES.'次');
        }else{
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 86400]);
        }
        return true;
    }
}
