<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class BankConfig extends ActiveRecord
{
    const PLATFORM_UMPAY  = 1; //联动支付
    const PLATFORM_LLPAY  = 2; //连连支付
    const PLATFORM_YEEPAY = 3; //易宝支付
    const PLATFORM_UMPAY4 = 4; //新联动支付
    const PLATFORM_JYTPAY = 5; //金运通支付
    const PLATFORM_99PAY  = 6; //快钱支付
    const PLATFORM_BFPAY  = 7; //宝付支付
    const PLATFORM_BYPAY  = 8; //宝易
    const PLATFORM_SDOPAY = 9; //盛付通
    const PLATFORM_FYPAY  = 10;//富友支付
    const PLATFORM_KUAIJIETONG = 11; //快捷通支付
    const PLATFORM_ZLPAY = 12; //直连代扣
    const PLATFORM_KJTPAY  = 13;  //快捷通
    const PLATFORM_TLPAY  = 14;  //通联
    const PLATFORM_RBPAY  = 15;  //融宝
    const PLATFORM_YEEPAY_NEW = 16; //新易宝
    const PLATFORM_LKL = 20; //拉卡拉
    const PLATFORM_YMTF = 17; //益码通付
    const PLATFORM_UNSPAY = 21; //银生宝
    const PLATFORM_CHANPAY = 22; //畅捷支付
    const PLATFORM_ECPSS = 23; //汇潮支付
    const PLATFORM_HELIPAY = 24; //合利宝

    public static $platform = [
        self::PLATFORM_ZLPAY =>  "直连代扣",
        self::PLATFORM_UMPAY  => "联动优势",
        self::PLATFORM_LLPAY  => "连连银通",
        self::PLATFORM_YEEPAY => "易宝支付",
        self::PLATFORM_SDOPAY => "盛付通",
        self::PLATFORM_UMPAY4 => "新联动优势",
        self::PLATFORM_JYTPAY => "金运通支付",
        self::PLATFORM_99PAY  => "快钱支付",
        self::PLATFORM_BFPAY  => "宝付支付",
        self::PLATFORM_FYPAY  => '富友支付',
        self::PLATFORM_KUAIJIETONG => '快捷通支付',
        self::PLATFORM_ZLPAY => '直连代扣',
        self::PLATFORM_KJTPAY => '快捷通',
        self::PLATFORM_TLPAY => '通联',
        self::PLATFORM_RBPAY => '融宝',
        self::PLATFORM_YEEPAY_NEW => '新易宝支付',
        self::PLATFORM_LKL => '拉卡拉',
        self::PLATFORM_YMTF => '益码通付',
        self::PLATFORM_UNSPAY => '银生宝',
        self::PLATFORM_CHANPAY => '畅捷支付',
        self::PLATFORM_ECPSS => '汇潮支付',
        self::PLATFORM_HELIPAY => '合利宝',
    ];

    public static $platform_name = [
        'unspay' => self::PLATFORM_UNSPAY,
        'chanpay' => self::PLATFORM_CHANPAY,
        'helipay' => self::PLATFORM_HELIPAY,
    ];

    public static $use_platform = [
        self::PLATFORM_UMPAY  => "联动优势",
        self::PLATFORM_YEEPAY => "易宝支付",
        self::PLATFORM_FYPAY  => '富友支付',
        self::PLATFORM_BFPAY  => "宝付支付",
        self::PLATFORM_KUAIJIETONG => '快捷通支付',
        self::PLATFORM_YEEPAY_NEW => '新易宝支付',
        self::PLATFORM_LKL => '拉卡拉',
        self::PLATFORM_UNSPAY => '银生宝',
        self::PLATFORM_CHANPAY => '畅捷支付',
        self::PLATFORM_ECPSS => '汇潮支付',
        self::PLATFORM_HELIPAY => '合利宝',
    ];

    const USE_FOR_APP = "app";
    const USE_FOR_WEB = "web";
    const USE_FOR_WAP = "wap";

    const STATUS_EFFECTIVE = 0; // 生效
    const STATUS_NOT_EFFECTIVE = 1; // 失效

    const STATUS_CHANGE_PLAT = 1;    //允许切换
    const STATUS_NOT_CHANGE_PLAT = 0;  //不允许切换

    public static $change_plat = [
        self::STATUS_CHANGE_PLAT => "允许",
        self::STATUS_NOT_CHANGE_PLAT => "不允许",
    ];

    public static $status = [
        self::STATUS_EFFECTIVE => "生效中",
        self::STATUS_NOT_EFFECTIVE => "失效",
    ];

    // 支持放款银行列表
    public static $bankInfo = [
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
        "24" => "东亚银行",
        "25" => "北京农商银行",
        "26" => "杭州银行",
        "27" => "浙商银行",
        "28" => "江苏银行",
    ];

    //支持扣款银行的列表
    public static $debit_bankInfo = [
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
    ];

    // bank_id => bank_name
    public static $bankCMBNo = [
        "1" => "102100099996",
        "2" => "103100000026",
        "3" => "303100000006",
        "4" => "403100000004",
        "5" => "309391000011",
        "6" => "307584007998",
        "7" => "105100000017",
        "8" => "308584000013",
        "9" => "104100000004",
        "10" => "310290000013",
        "11" => "307584007998",
        "12" => "304100040000",
        "13" => "302100011000",
        "14" => "301290000007",
        "15" => "305100000013",
        "16" => "306581000003",
        "17" => "313100000013",
        "18" => "313290000017",
    ];

    //银行编号
    public static $bankCode = [
        "1" => "ICBC",
        "2" => "ABC",
        "3" => "CEB",
        "4" => "POST",
        "5" => "CIB",
        "6" => "SDB",
        "7" => "CCB",
        "8" => "CMBCHINA",
        "9" => "BOC",
        "10" => "SPDB",
        "11" => "SDB",
        "12" => "HXB",
        "13" => "ECITIC",
        "14" => "BOCO",
        "15" => "CMBC",
        "16" => "GDB",
        "17" => "BCCB",
        "18" => "SHB",
        "21" => "CBHB",
    ];

    // 温州贷银行列表 bank_id => bank_name
    public static $WZD_bank_info = array(
        '471' => '上海浦东发展银行',
        '472' => '中信银行',
        '473' => '中国光大银行',
        '303' => '中国农业银行',
        '300' => '中国工商银行',
        '302' => '中国建设银行',
        '469' => '中国民生银行',
        '1596' => '中国邮政储蓄银行',
        '301' => '中国银行',
        '463' => '交通银行',
        '468' => '兴业银行',
        '470' => '华夏银行',
        '467' => '平安银行',
        '465' => '广东发展银行',
        '466' => '招商银行',
    );

    public static $WZD_bank_no = array(
        '471' => '310290000013',
        '472' => '302100011000',
        '473' => '303100000006',
        '303' => '103100000026',
        '300' => '102100099996',
        '302' => '105100000017',
        '469' => '305100000013',
        '1596' => '403100000004',
        '301' => '104100000004',
        '463' => '301290000007',
        '468' => '309391000011',
        '470' => '304100040000',
        '467' => '307584007998',
        '465' => '306581000003',
        '466' => '308584000013',
    );

    public static $use_for = [
        "app" => "App手机支付",
        "web" => "Web充值支付"
    ];

    /**
     * 绑卡文案
     */
    public static $bind_card_tips = [
            self::PLATFORM_LLPAY  => "需要验证您的身份信息、卡号和预留手机的一致性，过程中会扣款1元，成功后1元返还至您的账户余额",
            self::PLATFORM_UMPAY  => "需要验证您的身份信息、卡号，过程中会扣款0.01元，成功后0.01元返还至您的账户余额",
            self::PLATFORM_YEEPAY => "需要验证您的身份信息、卡号和预留手机的一致性，过程中不会产生任何费用",
            self::PLATFORM_UMPAY4 => "需要验证您的身份信息、卡号和预留手机的一致性，过程中会扣款1元，成功后1元返还至您的账户余额",
            self::PLATFORM_JYTPAY => "需要验证您的身份信息、卡号和预留手机的一致性，过程中会扣款1元，成功后1元返还至您的账户余额",
            self::PLATFORM_99PAY  => "需要验证您的身份信息、卡号和预留手机的一致性，过程中会扣款1元，成功后1元返还至您的账户余额",
            self::PLATFORM_BFPAY  => "需要验证您的身份信息、卡号和预留手机的一致性，过程中不会产生任何费用",
    ];

    /**
     * 主副卡介绍
     */
    public static $card_intro = [
            ['1 什么是主卡', '为保障您的资金安全，您首次绑定银行卡将作为您的主卡；您在本平台的所有收益只能由此卡提出。需要注意的是，您通过口袋官网网银充值的金额，领取的红包，体验金产生的收益等亦通过主卡提出。'],
            ['2 什么是普通卡', '除主卡之外绑定的银行卡均可称之为普通卡，该卡可提取的限额严格等于该卡充值的金额（同卡进出的原则）。该卡充值金额产生的收益只能由主卡提取出。'],
            ['3 主卡解绑了怎么办？', '我们是按照您绑卡的时间顺序来确定主卡及普通卡，主卡默认是您绑定的最早的银行卡，若主卡被解绑，则第二绑定的卡将成为主卡，以此类推。']
    ];

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%bank_config}}';
    }

    public function rules()
    {
        return [
            [[
                'bank_id',
                'bank_name',
                'third_platform',
                'abbreviation',
                'status',
                'sml',
                'dml',
                'dtl',
                'use_for',
            ], 'required'],
            [ ['pay_limit_desc',
	            'change_plat'] ,'safe'],
        ];
    }

    /**
     * 是否维护中
     *
     */
    public static function isMaintain($bank) {

        $begin_time = $bank['maintain_begin_time'];
        $end_time   = $bank['maintain_end_time'];
        $time       = time();

        if($time > $begin_time && $time < $end_time) {//当前时间小于维护结束时间
            $date_str = date("m月d日H:i", $end_time);
            return "银行通道维护中，将于{$date_str}后恢复正常";
        }

        return "";
    }

    /**
     * 根据$bank_id获取银行卡信息
     *
     * @param int $bank_id
     * @param int $third_platform
     */
    public static function getBankConf($bank_id, $third_platform=self::PLATFORM_LLPAY) {

        $client_type = self::getUseFor();
        $bank = self::findOne(['bank_id' => $bank_id, 'use_for' => $client_type, 'third_platform' => $third_platform]);

        //是否维护中
        if(!empty($bank)) {
            $begin_time = $bank['maintain_begin_time'];
            $end_time   = $bank['maintain_end_time'];
            $time       = time();

            if($time > $begin_time && $time < $end_time) {//当前时间小于维护结束时间
                $date_str = date("m月d日H:i", $end_time);
                $bank['maintain_end_time'] = "银行通道维护中，将于{$date_str}后恢复正常";
            }
            else {
                $bank['maintain_end_time'] = '';
            }
        }
        else {
            $bank['maintain_end_time'] = '';
        }

        return $bank;
    }

    /**
     * 获取银行使用的客户端类型
     */
    public static function getUseFor() {

        $client_type = \Yii::$app->request->get("clientType");
        if(in_array($client_type, ['ios', 'android', 'wap'])) {
            return 'app';
        }
        else if(in_array($client_type, ['pc'])){
            return 'web';
        }
        else {
            return 'app';
        }
    }


    /**
     * 限额提示
     */
    public static function payLimitTip($sml, $dml, $dtl) {

        $sml = intval(bcdiv($sml, 100));
        $dml = intval(bcdiv($dml, 100));

        if($sml >= 10000) {
            $sml = ($sml/10000) . "万";
        }
        if($dml >= 10000) {
            $dml = ($dml/10000) . "万";
        }

        return "单笔{$sml}元，单日{$dml}元，每月不限额";
    }

    /**
     * 限额信息
     */
    public static function getLimitInfo($status = self::STATUS_EFFECTIVE, $use_for = self::USE_FOR_APP)
    {
        $sql = "SELECT `bank_id`, `bank_name`, `sml`, `dml`, `pay_limit_desc`, `dtl`
                FROM " . self::tableName() . "
                WHERE `status`={$status} AND `use_for`='{$use_for}'";
        $query = \Yii::$app->db->createCommand($sql)->queryAll();
        return $query;
    }


    /**
     * 强制切换支付通道
     * 用于已绑定某个通道的银行不能正常充值的情况
     * $new_platform == 0  返回设置的结果  -1 删除
     */
    public static function forceChangePlatform($bank_id=0, $platform=0, $new_platform=0) {

        $cache_key = "force-change-platform";

        if(empty($new_platform)) {

            if($bank_id && $platform) {//取单条记录
                return \Yii::$app->redis->get($cache_key . "-" . $bank_id . "-" . $platform);
            }
            else {//取所有记录
                $list = [];

                foreach (self::$bankInfo as $k => $v) {
                    foreach (self::$platform as $key => $val) {

                        $new_platform = \Yii::$app->redis->get($cache_key . "-" . $k . "-" . $key);
                        if(!empty($new_platform)) {//设置过

                            $list[] = [
                                    'bank_id' => $k,
                                    'platform' => $key,
                                    'new_platform' => $new_platform,
                            ];
                        }
                    }
                }

                return $list;
            }
        }
        else {

            $cache_key = $cache_key . "-" . $bank_id . "-" . $platform;
            if($new_platform == -1) {//删除
                \Yii::$app->redis->del($cache_key);
            }
            else {
                if($platform != $new_platform) {
                    \Yii::$app->redis->set($cache_key, $new_platform);
                }
            }
        }
    }

    public static function searchBankIdByBankName($bankName){
        if(!$bankName){
            return 0;
        }
        foreach(self::$bankInfo as $bankId => $name){
            if(preg_match('/'.$name.'/', $bankName)){
                return $bankId;
            }
        }
        return 0;
    }
}
