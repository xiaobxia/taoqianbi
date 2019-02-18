<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%user_credit_money_log}}".
 */
class UserCreditMoneyLog extends BaseActiveRecord
{
    const TYPE_PLAY = 1;
    const TYPE_DEBIT = 2;
    const DEBIT_REDIS_KEY_PREFIX = 'ygd_order_status';
    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }

    public static $type = [
        self::TYPE_PLAY=>'支付',
        self::TYPE_DEBIT=>'代扣',
    ];


    const PAYMENT_TYPE_AUTO = 1;
    const PAYMENT_TYPE_CUNSTOMER_BANK_TRANS = 2;
    const PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS = 3;
    const PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT = 4;
    const PAYMENT_TYPE_DELAY = 5;
    const PAYMENT_TYPE_CUNSTOMER_ZFB_APP = 6;
    const PAYMENT_TYPE_COLLECTION = 7;
    const PAYMENT_TYPE_BACKEND = 8;
    const PAYMENT_TYPE_CUNSTOMER_WEIXIN_TRANS = 9;
    const PAYMENT_TYPE_CUNSTOMER_HC = 23;
    const PAYMENT_TYPE_CUNSTOMER_UNSPAY = 21;
    const PAYMENT_TYPE_CUNSTOMER_CHANPAY = 22;
    const PAYMENT_TYPE_CUNSTOMER_HELIPAY = 24;

    public static $payment_type = [
        self::PAYMENT_TYPE_AUTO=>'系统代扣',
        self::PAYMENT_TYPE_CUNSTOMER_BANK_TRANS=>'银行卡转账',
        self::PAYMENT_TYPE_CUNSTOMER_ZFB_TRANS=>'支付宝转账',
        self::PAYMENT_TYPE_CUNSTOMER_BANK_DEBIT=>'银行卡主动还款',
        self::PAYMENT_TYPE_DELAY=>'客户主动延期',
        self::PAYMENT_TYPE_CUNSTOMER_ZFB_APP=>'益码通付款',
        self::PAYMENT_TYPE_COLLECTION => '催收代扣',
        self::PAYMENT_TYPE_BACKEND => '后台代扣',
        self::PAYMENT_TYPE_CUNSTOMER_WEIXIN_TRANS => '微信转账',
        self::PAYMENT_TYPE_CUNSTOMER_HC => '汇潮支付宝付款',
        self::PAYMENT_TYPE_CUNSTOMER_UNSPAY => '银生宝',
        self::PAYMENT_TYPE_CUNSTOMER_CHANPAY => '畅捷支付',
        self::PAYMENT_TYPE_CUNSTOMER_HELIPAY => '合利宝'
    ];

    const STATUS_PRE = 6;
    const STATUS_CANCEL = -2;
    const STATUS_FAILED = -1;
    const STATUS_NORMAL = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_ING = 2;
    const STATUS_APPLY = -5;
    const STATUS_MANUAL = 7;

    public static $status = [
        self::STATUS_PRE=>'生成',
        self::STATUS_CANCEL=>'作废',
        self::STATUS_FAILED=>'失败',
        self::STATUS_NORMAL=>'默认',
        self::STATUS_SUCCESS=>'成功',
        self::STATUS_ING=>'进行中',
        self::STATUS_APPLY=>'扣款进行中',
        self::STATUS_MANUAL=>'人工处理',
    ];

    const PlatformUmpay1 = 1;   //老联动(三要素)
    const PlatformLlpay  = 2;   //连连
    const PlatformYeepay = 3;   //易宝
    const PlatformUmpay  = 4;   //新联动
    const PlatformJytPay = 5;   //金运通
    const Platform99bill = 6;   //快钱
    const PlatformBfpay  = 7;   //宝付
    const PlatformBypay  = 8;   //宝易
    const PlatformSdopay = 9;   //盛付通
    const PlatformFypay  = 10;  //富友支付
    const PlatformKjtpay  = 13;  //快捷通
    const PlatformTlpay  = 14;  //通联
    const PlatformRbpay  = 15;  //融宝
    const PlatformYeepayNEW  = 16;  //新易宝
    const PlatformYmatpay  = 17;  //益码通付
    const PlatformLkl  = 20;  //拉卡拉
    const Platformzfbsapy  = 201;  //支付宝
    const Platformwxsapy  = 202;  //微信
    const PlatformUnsapy  = 21;  //银生宝
    const PlatformChanpay = 22; //畅捷支付
    const Platformhc  = 23;  //汇潮支付
    const PlatformHelipay  = 24;  //汇潮支付

    public static $third_platform_name = [
        self::PlatformUmpay1 => '联动优势',
        self::PlatformLlpay  => '连连支付',
        self::PlatformYeepay => '易宝支付',
        self::PlatformUmpay  => '新联动优势',
        self::PlatformJytPay => '金运通支付',
        self::Platform99bill => '快钱支付',
        self::PlatformBfpay  => '宝付支付',
        self::PlatformBypay  => '宝易支付',
        self::PlatformSdopay => '盛付通支付',
        self::PlatformFypay  => '富友支付',
        self::PlatformUnsapy  => '银生宝',
        self::PlatformKjtpay  => '快捷通',
        self::PlatformTlpay  => '通联',
        self::PlatformRbpay  => '融宝',
        self::PlatformYeepayNEW  => '新易宝',
        self::PlatformYmatpay  => '益码通付',
        self::Platformzfbsapy  => '支付宝',
        self::Platformwxsapy  => '微信',
        self::PlatformLkl  => '拉卡拉',
        self::PlatformChanpay => '畅捷支付',
        self::Platformhc  => '汇潮支付',
        self::PlatformHelipay  => '合利宝',


    ];

    const FORCE_FAILED_YES = 1; //是
    const FORCE_FAILED_NO  = 0; //否

    public static $error_code = [
        300315=>'支付失败，请更换银行卡再试或支付宝还款',
        301037=>'支付失败，请更换银行卡再试或支付宝还款',
        300305=>'支付失败，请更换银行卡再试或支付宝还款',
        300116=>'系统繁忙，请稍后再试',
        300102=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        300010=>'系统繁忙，请稍后再试',
        300306=>'支付失败，请更换银行卡再试或支付宝还款',
        300329=>'支付失败，请更换银行卡再试或支付宝还款',
        301030=>'系统繁忙，请稍后再试',
        300053=>'支付失败，请更换银行卡再试或支付宝还款',
        300147=>'系统繁忙，请稍后再试',
        300106=>'系统繁忙，请稍后再试',
        300098=>'支付失败，请更换银行卡再试或支付宝还款',
        300156=>'支付失败，请更换银行卡再试或支付宝还款',
        300146=>'支付失败，请更换银行卡再试或支付宝还款',
        300301=>'支付失败，请更换银行卡再试或支付宝还款',
        300119=>'支付失败，请更换银行卡再试或支付宝还款',
        300047=>'支付失败，请更换银行卡再试或支付宝还款',
        300097=>'支付失败，请更换银行卡再试或支付宝还款',
        300136=>'支付失败，请更换银行卡再试或支付宝还款',
        300020=>'支付失败，请更换银行卡再试或支付宝还款',
        300045=>'支付失败，请更换银行卡再试或支付宝还款',
        300323=>'支付失败，请更换银行卡再试或支付宝还款',
        301039=>'支付失败，请更换银行卡再试或支付宝还款',
        300320=>'支付失败，请更换银行卡再试或支付宝还款',
        301029=>'系统繁忙，请稍后再试',
        300048=>'支付失败，请更换银行卡再试或支付宝还款',
        300309=>'支付失败，请更换银行卡再试或支付宝还款',
        300000=>'系统繁忙，请稍后再试',
        300310=>'支付失败，请更换银行卡再试或支付宝还款',
        300096=>'支付失败，请更换银行卡再试或支付宝还款',
        301036=>'支付失败，请更换银行卡再试或支付宝还款',
        300316=>'支付失败，请更换银行卡再试或支付宝还款',
        300326=>'支付失败，请更换银行卡再试或支付宝还款',
        300046=>'支付失败，请更换银行卡再试或支付宝还款',
        300304=>'支付失败，请更换银行卡再试或支付宝还款',
        300100=>'支付失败，请更换银行卡再试或支付宝还款',
        300051=>'支付失败，请更换银行卡再试或支付宝还款',
        301033=>'支付失败，请更换银行卡再试或支付宝还款',
        300050=>'系统繁忙，请稍后再试',
        300135=>'支付失败，请更换银行卡再试或支付宝还款',
        300123=>'支付失败，请更换银行卡再试或支付宝还款',
        300041=>'支付失败，请更换银行卡再试或支付宝还款',
        300124=>'支付失败，请更换银行卡再试或支付宝还款',
        300093=>'支付失败，请更换银行卡再试或支付宝还款',
        300095=>'支付失败，请更换银行卡再试或支付宝还款',
        300043=>'支付失败，请更换银行卡再试或支付宝还款',
        300121=>'支付失败，请更换银行卡再试或支付宝还款',
        700100=>'系统繁忙，请稍后再试',
        700101=>'支付失败，请更换银行卡再试或支付宝还款',
        700102=>'支付失败，请更换银行卡再试或支付宝还款',
        700103=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        700104=>'支付失败，请更换银行卡再试或支付宝还款',
        700105=>'支付失败，请更换银行卡再试或支付宝还款',
        700106=>'支付失败，请更换银行卡再试或支付宝还款',
        700107=>'支付失败，请更换银行卡再试或支付宝还款',
        700108=>'支付失败，请更换银行卡再试或支付宝还款',
        700109=>'支付失败，请更换银行卡再试或支付宝还款',
        700110=>'支付失败，请更换银行卡再试或支付宝还款',
        700111=>'支付失败，请更换银行卡再试或支付宝还款',
        700112=>'支付失败，请更换银行卡再试或支付宝还款',
        700113=>'支付失败，请更换银行卡再试或支付宝还款',
        700114=>'支付失败，请更换银行卡再试或支付宝还款',
        700115=>'支付失败，请更换银行卡再试或支付宝还款',
        700116=>'支付失败，请更换银行卡再试或支付宝还款',
        700117=>'支付失败，请更换银行卡再试或支付宝还款',
        700118=>'支付失败，请更换银行卡再试或支付宝还款',
        700119=>'支付失败，请更换银行卡再试或支付宝还款',
        700120=>'支付失败，请更换银行卡再试或支付宝还款',
        700121=>'支付失败，请更换银行卡再试或支付宝还款',
        700122=>'系统繁忙，请稍后再试',
        700123=>'系统繁忙，请稍后再试',
        700124=>'系统繁忙，请稍后再试',
        700125=>'系统繁忙，请稍后再试',
        700126=>'支付失败，请更换银行卡再试或支付宝还款',
        700127=>'系统繁忙，请稍后再试',
        700128=>'系统繁忙，请稍后再试',
        700129=>'系统繁忙，请稍后再试',
        700130=>'系统繁忙，请稍后再试',
        700131=>'系统繁忙，请稍后再试',
        700132=>'系统繁忙，请稍后再试',
        700133=>'系统繁忙，请稍后再试',
        700134=>'支付失败，请更换银行卡再试或支付宝还款',
        700135=>'支付失败，请更换银行卡再试或支付宝还款',
        700136=>'系统繁忙，请稍后再试',
        700190=>'系统繁忙，请稍后再试',
        700214=>'系统繁忙，请稍后再试',
        709999=>'系统繁忙，请稍后再试',
        600001=>'支付失败，请更换银行卡再试或支付宝还款',
        600002=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        600003=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        600004=>'支付失败，请更换银行卡再试或支付宝还款',
        600005=>'系统繁忙，请稍后再试',
        600006=>'支付失败，请更换银行卡再试或支付宝还款',
        600007=>'支付失败，请更换银行卡再试或支付宝还款',
        600008=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        600009=>'系统繁忙，请稍后再试',
        600010=>'系统繁忙，请稍后再试',
        600011=>'支付失败，请更换银行卡再试或支付宝还款',
        180537=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        190362=>'支付失败，请更换银行卡再试或支付宝还款',
        180550=>'支付失败，请更换银行卡再试或支付宝还款',
        154035=>'系统繁忙，请稍后再试',
        160340=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        100029=>'系统繁忙，请稍后再试',
        160402=>'支付失败，请更换银行卡再试或支付宝还款',
        180008=>'支付失败，请更换银行卡再试或支付宝还款',
        160116=>'支付失败，请更换银行卡再试或支付宝还款',
        160999=>'系统繁忙，请稍后再试',
        180534=>'支付失败，请更换银行卡再试或支付宝还款',
        152008=>'系统繁忙，请稍后再试',
        180543=>'支付失败，请更换银行卡再试或支付宝还款',
        160403=>'支付失败，请更换银行卡再试或支付宝还款',
        170004=>'支付失败，请更换银行卡再试或支付宝还款',
        160710=>'支付失败，请更换银行卡再试或支付宝还款',
        160544=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        152007=>'系统繁忙，请稍后再试',
        153035=>'系统繁忙，请稍后再试',
        400073=>'支付失败，请更换银行卡再试或支付宝还款',
        480537=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        460875=>'支付失败，请更换银行卡再试或支付宝还款',
        480557=>'支付失败，请更换银行卡再试或支付宝还款',
        480550=>'支付失败，请更换银行卡再试或支付宝还款',
        400006=>'支付失败，请更换银行卡再试或支付宝还款',
        460700=>'支付失败，请更换银行卡再试或支付宝还款',
        453043=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        410077=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        400027=>'支付失败，请更换银行卡再试或支付宝还款',
        452010=>'支付失败，请更换银行卡再试或支付宝还款',
        460550=>'支付失败，请更换银行卡再试或支付宝还款',
        455999=>'支付失败，请更换银行卡再试或支付宝还款',
        460544=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        400012=>'支付失败，请更换银行卡再试或支付宝还款',
        460553=>'支付失败，请更换银行卡再试或支付宝还款',
        410116=>'系统繁忙，请稍后再试',
        400010=>'支付失败，请更换银行卡再试或支付宝还款',
        400014=>'系统繁忙，请稍后再试',
        400029=>'系统繁忙，请稍后再试',
        480545=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        453026=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        460761=>'系统繁忙，请稍后再试',
        453023=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        460999=>'系统繁忙，请稍后再试',
        480559=>'支付失败，请更换银行卡再试或支付宝还款',
        453035=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        460781=>'系统繁忙，请稍后再试',
        420000=>'系统繁忙，请稍后再试',
        410028=>'支付失败，请更换银行卡再试或支付宝还款',
        452005=>'支付失败，请更换银行卡再试或支付宝还款',
        480554=>'支付失败，请更换银行卡再试或支付宝还款',
        460925=>'支付失败，请更换银行卡再试或支付宝还款',
        410061=>'系统繁忙，请稍后再试',
        460751=>'系统繁忙，请稍后再试',
        460780=>'系统繁忙，请稍后再试',
        452008=>'支付失败，请更换银行卡再试或支付宝还款',
        621003=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        700257=>'支付失败，请更换银行卡再试或支付宝还款',
        621004=>'支付失败，请更换银行卡再试或支付宝还款',
        480560=>'支付失败，请更换银行卡再试或支付宝还款',
        710000=>'支付失败，请更换银行卡再试或支付宝还款',
        621005=>'支付失败，请更换银行卡再试或支付宝还款',
        190363=>'支付失败，请更换银行卡再试或支付宝还款',
        301040=>'支付失败，请更换银行卡再试或支付宝还款',
        480561=>'支付失败，请更换银行卡再试或支付宝还款',
        190365=>'系统繁忙，请稍后再试',
        621006=>'系统繁忙，请稍后再试',
        301041=>'支付失败，请更换银行卡再试或支付宝还款',
        480562=>'支付失败，请更换银行卡再试或支付宝还款',
        480563=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        621007=>'支付失败，请更换银行卡再试或支付宝还款',
        480564=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        301042=>'系统繁忙，请稍后再试',
        190366=>'支付失败，请更换银行卡再试或支付宝还款',
        710001=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        480565=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        710002=>'支付失败，请更换银行卡再试或支付宝还款',
        480566=>'支付失败，请更换银行卡再试或支付宝还款',
        190367=>'支付失败，请更换银行卡再试或支付宝还款',
        710003=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        710004=>'支付失败，请更换银行卡再试或支付宝还款',
        710005=>'支付失败，请更换银行卡再试或支付宝还款',
        621008=>'支付失败，请更换银行卡再试或支付宝还款',
        480567=>'支付失败，请更换银行卡再试或支付宝还款',
        190368=>'支付失败，请更换银行卡再试或支付宝还款',
        710006=>'支付失败，请更换银行卡再试或支付宝还款',
        480568=>'支付失败，请更换银行卡再试或支付宝还款',
        710007=>'支付失败，请更换银行卡再试或支付宝还款',
        710008=>'支付失败，请更换银行卡再试或支付宝还款',
        621009=>'支付失败，请更换银行卡再试或支付宝还款',
        480569=>'系统繁忙，请稍后再试',
        480570=>'支付失败，请更换银行卡再试或支付宝还款',
        710009=>'支付失败，请更换银行卡再试或支付宝还款',
        710010=>'银行维护中，请稍后再试',
        190369=>'支付失败，请更换银行卡再试或支付宝还款',
        301043=>'支付失败，请更换银行卡再试或支付宝还款',
        621010=>'系统繁忙，请稍后再试',
        710011=>'支付失败，请更换银行卡再试或支付宝还款',
        480571=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        190370=>'系统繁忙，请稍后再试',
        480572=>'系统繁忙，请稍后再试',
        190371=>'支付失败，请更换银行卡再试或支付宝还款',
        800001=>'支付失败，请更换银行卡再试或支付宝还款',
        800002=>'支付失败，请更换银行卡再试或支付宝还款',
        800003=>'支付失败，请更换银行卡再试或支付宝还款',
        621011=>'支付失败，请更换银行卡再试或支付宝还款',
        800004=>'支付失败，请更换银行卡再试或支付宝还款',
        621012=>'支付失败，请更换银行卡再试或支付宝还款',
        621013=>'支付失败，请更换银行卡再试或支付宝还款',
        800005=>'支付失败，请更换银行卡再试或支付宝还款',
        621014=>'支付失败，请更换银行卡再试或支付宝还款',
        621015=>'支付失败，请更换银行卡再试或支付宝还款',
        621016=>'支付失败，请更换银行卡再试或支付宝还款',
        800006=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        621017=>'支付失败，请更换银行卡再试或支付宝还款',
        800007=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800008=>'系统繁忙，请稍后再试',
        621018=>'支付失败，请更换银行卡再试或支付宝还款',
        621019=>'支付失败，请更换银行卡再试或支付宝还款',
        800009=>'支付失败，请更换银行卡再试或支付宝还款',
        710012=>'支付失败，请更换银行卡再试或支付宝还款',
        621020=>'支付失败，请更换银行卡再试或支付宝还款',
        621021=>'支付失败，请更换银行卡再试或支付宝还款',
        621022=>'支付失败，请更换银行卡再试或支付宝还款',
        621023=>'支付失败，请更换银行卡再试或支付宝还款',
        621024=>'支付失败，请更换银行卡再试或支付宝还款',
        621025=>'支付失败，请更换银行卡再试或支付宝还款',
        621026=>'支付失败，请更换银行卡再试或支付宝还款',
        621027=>'支付失败，请更换银行卡再试或支付宝还款',
        710013=>'支付失败，请更换银行卡再试或支付宝还款',
        621028=>'支付失败，请更换银行卡再试或支付宝还款',
        190372=>'支付失败，请更换银行卡再试或支付宝还款',
        190373=>'支付失败，请更换银行卡再试或支付宝还款',
        621029=>'支付失败，请更换银行卡再试或支付宝还款',
        190374=>'支付失败，请更换银行卡再试或支付宝还款',
        621030=>'支付失败，请更换银行卡再试或支付宝还款',
        621031=>'系统繁忙，请稍后再试',
        190375=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        621032=>'支付失败，请更换银行卡再试或支付宝还款',
        621033=>'支付失败，请更换银行卡再试或支付宝还款',
        190376=>'系统繁忙，请稍后再试',
        190377=>'系统繁忙，请稍后再试',
        621034=>'支付失败，请更换银行卡再试或支付宝还款',
        621035=>'支付失败，请更换银行卡再试或支付宝还款',
        800010=>'支付失败，请更换银行卡再试或支付宝还款',
        710014=>'支付失败，请更换银行卡再试或支付宝还款',
        621036=>'支付失败，请更换银行卡再试或支付宝还款',
        190378=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        710015=>'支付失败，请更换银行卡再试或支付宝还款',
        190379=>'支付失败，请更换银行卡再试或支付宝还款',
        190380=>'支付失败，请更换银行卡再试或支付宝还款',
        190381=>'支付失败，请更换银行卡再试或支付宝还款',
        621037=>'支付失败，请更换银行卡再试或支付宝还款',
        800011=>'支付失败，请更换银行卡再试或支付宝还款',
        800012=>'支付失败，请更换银行卡再试或支付宝还款',
        1000001=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        1000002=>'支付失败，请更换银行卡再试或支付宝还款',
        800013=>'支付失败，请更换银行卡再试或支付宝还款',
        1000003=>'支付失败，请更换银行卡再试或支付宝还款',
        1000004=>'支付失败，请更换银行卡再试或支付宝还款',
        190382=>'系统繁忙，请稍后再试',
        190383=>'系统繁忙，请稍后再试',
        1000005=>'支付失败，请更换银行卡再试或支付宝还款',
        621038=>'支付失败，请更换银行卡再试或支付宝还款',
        1000006=>'支付失败，请更换银行卡再试或支付宝还款',
        710016=>'支付失败，请更换银行卡再试或支付宝还款',
        1000007=>'支付失败，请更换银行卡再试或支付宝还款',
        1000008=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        190384=>'支付失败，请更换银行卡再试或支付宝还款',
        621039=>'支付失败，请更换银行卡再试或支付宝还款',
        1000009=>'支付失败，请更换银行卡再试或支付宝还款',
        480573=>'支付失败，请更换银行卡再试或支付宝还款',
        800014=>'支付失败，请更换银行卡再试或支付宝还款',
        1000010=>'支付失败，请更换银行卡再试或支付宝还款',
        1000011=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        1000012=>'支付失败，请更换银行卡再试或支付宝还款',
        1000013=>'支付失败，请更换银行卡再试或支付宝还款',
        1000014=>'系统繁忙，请稍后再试',
        1000015=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        1000016=>'支付失败，请更换银行卡再试或支付宝还款',
        1000017=>'支付失败，请更换银行卡再试或支付宝还款',
        800015=>'支付失败，请更换银行卡再试或支付宝还款',
        800016=>'支付失败，请更换银行卡再试或支付宝还款',
        710017=>'支付失败，请更换银行卡再试或支付宝还款',
        190385=>'支付失败，请更换银行卡再试或支付宝还款',
        800017=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800018=>'支付失败，请更换银行卡再试或支付宝还款',
        190386=>'支付失败，请更换银行卡再试或支付宝还款',
        480574=>'系统繁忙，请稍后再试',
        800019=>'支付失败，请更换银行卡再试或支付宝还款',
        800020=>'支付失败，请更换银行卡再试或支付宝还款',
        1000018=>'支付失败，请更换银行卡再试或支付宝还款',
        710018=>'支付失败，请更换银行卡再试或支付宝还款',
        1000019=>'支付失败，请更换银行卡再试或支付宝还款',
        800021=>'支付失败，请更换银行卡再试或支付宝还款',
        1000020=>'支付失败，请更换银行卡再试或支付宝还款',
        190387=>'支付失败，请更换银行卡再试或支付宝还款',
        1000021=>'系统繁忙，请稍后再试',
        1000022=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800022=>'支付失败，请更换银行卡再试或支付宝还款',
        621040=>'支付失败，请更换银行卡再试或支付宝还款',
        621041=>'支付失败，请更换银行卡再试或支付宝还款',
        1000023=>'支付失败，请更换银行卡再试或支付宝还款',
        480575=>'支付失败，请更换银行卡再试或支付宝还款',
        1000024=>'支付失败，请更换银行卡再试或支付宝还款',
        800023=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        1000025=>'支付失败，请更换银行卡再试或支付宝还款',
        1000026=>'支付失败，请更换银行卡再试或支付宝还款',
        1000027=>'系统繁忙，请稍后再试',
        190388=>'支付失败，请更换银行卡再试或支付宝还款',
        710019=>'支付失败，请更换银行卡再试或支付宝还款',
        800024=>'支付失败，请更换银行卡再试或支付宝还款',
        1000028=>'支付失败，请更换银行卡再试或支付宝还款',
        621042=>'支付失败，请更换银行卡再试或支付宝还款',
        1000029=>'支付失败，请更换银行卡再试或支付宝还款',
        190389=>'支付失败，请更换银行卡再试或支付宝还款',
        1000030=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        190390=>'支付失败，请更换银行卡再试或支付宝还款',
        621043=>'系统繁忙，请稍后再试',
        1000031=>'支付失败，请更换银行卡再试或支付宝还款',
        710020=>'系统繁忙，请稍后再试',
        1000032=>'支付失败，请更换银行卡再试或支付宝还款',
        710021=>'支付失败，请更换银行卡再试或支付宝还款',
        1000033=>'支付失败，请更换银行卡再试或支付宝还款',
        800025=>'支付失败，请更换银行卡再试或支付宝还款',
        190391=>'支付失败，请更换银行卡再试或支付宝还款',
        1000034=>'支付失败，请更换银行卡再试或支付宝还款',
        710022=>'支付失败，请更换银行卡再试或支付宝还款',
        1000035=>'支付失败，请更换银行卡再试或支付宝还款',
        1000036=>'支付失败，请更换银行卡再试或支付宝还款',
        800026=>'支付失败，请更换银行卡再试或支付宝还款',
        710023=>'支付失败，请更换银行卡再试或支付宝还款',
        800027=>'支付失败，请更换银行卡再试或支付宝还款',
        1000037=>'支付失败，请更换银行卡再试或支付宝还款',
        1000038=>'支付失败，请更换银行卡再试或支付宝还款',
        710024=>'支付失败，请更换银行卡再试或支付宝还款',
        800028=>'支付失败，请更换银行卡再试或支付宝还款',
        800029=>'支付失败，请更换银行卡再试或支付宝还款',
        301044=>'支付失败，请更换银行卡再试或支付宝还款',
        190392=>'支付失败，请更换银行卡再试或支付宝还款',
        800030=>'支付失败，请更换银行卡再试或支付宝还款',
        710025=>'支付失败，请更换银行卡再试或支付宝还款',
        500001=>'支付失败，请更换银行卡再试或支付宝还款',
        500002=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        500003=>'系统繁忙，请稍后再试',
        710026=>'支付失败，请更换银行卡再试或支付宝还款',
        190393=>'支付失败，请更换银行卡再试或支付宝还款',
        500004=>'支付失败，请更换银行卡再试或支付宝还款',
        621044=>'支付失败，请更换银行卡再试或支付宝还款',
        190394=>'系统繁忙，请稍后再试',
        500005=>'支付失败，请更换银行卡再试或支付宝还款',
        500006=>'支付失败，请更换银行卡再试或支付宝还款',
        500007=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        190395=>'支付失败，请更换银行卡再试或支付宝还款',
        621045=>'支付失败，请更换银行卡再试或支付宝还款',
        500008=>'支付失败，请更换银行卡再试或支付宝还款',
        500009=>'支付失败，请更换银行卡再试或支付宝还款',
        500010=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        1000039=>'支付失败，请更换银行卡再试或支付宝还款',
        500011=>'支付失败，请更换银行卡再试或支付宝还款',
        301045=>'支付失败，请更换银行卡再试或支付宝还款',
        190396=>'支付失败，请更换银行卡再试或支付宝还款',
        500012=>'支付失败，请更换银行卡再试或支付宝还款',
        500013=>'支付失败，请更换银行卡再试或支付宝还款',
        190397=>'系统繁忙，请稍后再试',
        500014=>'支付失败，请更换银行卡再试或支付宝还款',
        500015=>'支付失败，请更换银行卡再试或支付宝还款',
        1000040=>'支付失败，请更换银行卡再试或支付宝还款',
        1000041=>'支付失败，请更换银行卡再试或支付宝还款',
        190398=>'支付失败，请更换银行卡再试或支付宝还款',
        1000042=>'支付失败，请更换银行卡再试或支付宝还款',
        190399=>'支付失败，请更换银行卡再试或支付宝还款',
        800031=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        190400=>'支付失败，请更换银行卡再试或支付宝还款',
        800032=>'支付失败，请更换银行卡再试或支付宝还款',
        710027=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        190401=>'系统繁忙，请稍后再试',
        710028=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        500016=>'支付失败，请更换银行卡再试或支付宝还款',
        710029=>'支付失败，请更换银行卡再试或支付宝还款',
        500017=>'支付失败，请更换银行卡再试或支付宝还款',
        500018=>'支付失败，请更换银行卡再试或支付宝还款',
        710030=>'支付失败，请更换银行卡再试或支付宝还款',
        1000043=>'支付失败，请更换银行卡再试或支付宝还款',
        190402=>'支付失败，请更换银行卡再试或支付宝还款',
        800033=>'支付失败，请更换银行卡再试或支付宝还款',
        1000044=>'支付失败，请更换银行卡再试或支付宝还款',
        1000045=>'支付失败，请更换银行卡再试或支付宝还款',
        710031=>'支付失败，请更换银行卡再试或支付宝还款',
        500019=>'支付失败，请更换银行卡再试或支付宝还款',
        500020=>'支付失败，请更换银行卡再试或支付宝还款',
        1000046=>'支付失败，请更换银行卡再试或支付宝还款',
        1000047=>'支付失败，请更换银行卡再试或支付宝还款',
        500021=>'支付失败，请更换银行卡再试或支付宝还款',
        1000048=>'支付失败，请更换银行卡再试或支付宝还款',
        500022=>'支付失败，请更换银行卡再试或支付宝还款',
        800034=>'支付失败，请更换银行卡再试或支付宝还款',
        190403=>'支付失败，请更换银行卡再试或支付宝还款',
        500023=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        500024=>'支付失败，请更换银行卡再试或支付宝还款',
        500025=>'支付失败，请更换银行卡再试或支付宝还款',
        500026=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        500027=>'支付失败，请更换银行卡再试或支付宝还款',
        480576=>'支付失败，请更换银行卡再试或支付宝还款',
        301046=>'支付失败，请更换银行卡再试或支付宝还款',
        1000049=>'支付失败，请更换银行卡再试或支付宝还款',
        500028=>'支付失败，请更换银行卡再试或支付宝还款',
        190404=>'支付失败，请更换银行卡再试或支付宝还款',
        480577=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        500029=>'系统繁忙，请稍后再试',
        301047=>'支付失败，请更换银行卡再试或支付宝还款',
        500030=>'支付失败，请更换银行卡再试或支付宝还款',
        1000050=>'支付失败，请更换银行卡再试或支付宝还款',
        190405=>'支付失败，请更换银行卡再试或支付宝还款',
        710032=>'支付失败，请更换银行卡再试或支付宝还款',
        301048=>'支付失败，请更换银行卡再试或支付宝还款',
        301049=>'支付失败，请更换银行卡再试或支付宝还款',
        301050=>'支付失败，请更换银行卡再试或支付宝还款',
        301051=>'支付失败，请更换银行卡再试或支付宝还款',
        301052=>'支付失败，请更换银行卡再试或支付宝还款',
        190406=>'支付失败，请更换银行卡再试或支付宝还款',
        190407=>'支付失败，请更换银行卡再试或支付宝还款',
        480578=>'系统繁忙，请稍后再试',
        710033=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        710034=>'支付失败，请更换银行卡再试或支付宝还款',
        500031=>'支付失败，请更换银行卡再试或支付宝还款',
        480579=>'系统繁忙，请稍后再试',
        500032=>'支付失败，请更换银行卡再试或支付宝还款',
        301053=>'支付失败，请更换银行卡再试或支付宝还款',
        190408=>'支付失败，请更换银行卡再试或支付宝还款',
        190409=>'支付失败，请更换银行卡再试或支付宝还款',
        1000051=>'支付失败，请更换银行卡再试或支付宝还款',
        1000052=>'支付失败，请更换银行卡再试或支付宝还款',
        500033=>'支付失败，请更换银行卡再试或支付宝还款',
        1000053=>'支付失败，请更换银行卡再试或支付宝还款',
        710035=>'支付失败，请更换银行卡再试或支付宝还款',
        710036=>'支付失败，请更换银行卡再试或支付宝还款',
        301054=>'支付失败，请更换银行卡再试或支付宝还款',
        480580=>'系统繁忙，请稍后再试',
        710037=>'支付失败，请更换银行卡再试或支付宝还款',
        1000054=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        1=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800035=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800036=>'支付失败，请更换银行卡再试或支付宝还款',
        800037=>'支付失败，请更换银行卡再试或支付宝还款',
        800038=>'系统繁忙，请稍后再试',
        800039=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800040=>'支付失败，请更换银行卡再试或支付宝还款',
        800041=>'支付失败，请更换银行卡再试或支付宝还款',
        800042=>'支付失败，请更换银行卡再试或支付宝还款',
        800043=>'支付失败，请更换银行卡再试或支付宝还款',
        800044=>'支付失败，请更换银行卡再试或支付宝还款',
        800045=>'支付失败，请更换银行卡再试或支付宝还款',
        800046=>'支付失败，请更换银行卡再试或支付宝还款',
        800047=>'支付失败，请更换银行卡再试或支付宝还款',
        800048=>'支付失败，请更换银行卡再试或支付宝还款',
        800049=>'支付失败，请更换银行卡再试或支付宝还款',
        800050=>'支付失败，请更换银行卡再试或支付宝还款',
        800051=>'支付失败，请更换银行卡再试或支付宝还款',
        800052=>'支付失败，请更换银行卡再试或支付宝还款',
        800053=>'支付失败，请更换银行卡再试或支付宝还款',
        800054=>'支付失败，请更换银行卡再试或支付宝还款',
        800055=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800056=>'支付失败，请更换银行卡再试或支付宝还款',
        800057=>'支付失败，请更换银行卡再试或支付宝还款',
        800058=>'支付失败，请更换银行卡再试或支付宝还款',
        800059=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800060=>'支付失败，请更换银行卡再试或支付宝还款',
        800061=>'支付失败，请更换银行卡再试或支付宝还款',
        800062=>'支付失败，请更换银行卡再试或支付宝还款',
        800063=>'支付失败，请更换银行卡再试或支付宝还款',
        800064=>'支付失败，请更换银行卡再试或支付宝还款',
        800065=>'支付失败，请更换银行卡再试或支付宝还款',
        800066=>'支付失败，请更换银行卡再试或支付宝还款',
        800067=>'支付失败，请更换银行卡再试或支付宝还款',
        800068=>'支付失败，请更换银行卡再试或支付宝还款',
        800069=>'支付失败，请更换银行卡再试或支付宝还款',
        800070=>'支付失败，请更换银行卡再试或支付宝还款',
        800071=>'支付失败，请更换银行卡再试或支付宝还款',
        800072=>'支付失败，请更换银行卡再试或支付宝还款',
        800073=>'支付失败，请更换银行卡再试或支付宝还款',
        800074=>'系统繁忙，请稍后再试',
        800075=>'支付失败，请更换银行卡再试或支付宝还款',
        800076=>'支付失败，请更换银行卡再试或支付宝还款',
        800077=>'支付失败，请更换银行卡再试或支付宝还款',
        800078=>'支付失败，请更换银行卡再试或支付宝还款',
        800079=>'支付失败，请更换银行卡再试或支付宝还款',
        800080=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800081=>'支付失败，请更换银行卡再试或支付宝还款',
        800082=>'支付失败，请更换银行卡再试或支付宝还款',
        800083=>'支付失败，请更换银行卡再试或支付宝还款',
        800084=>'支付失败，请更换银行卡再试或支付宝还款',
        800085=>'支付失败，请更换银行卡再试或支付宝还款',
        800086=>'支付失败，请更换银行卡再试或支付宝还款',
        800087=>'支付失败，请更换银行卡再试或支付宝还款',
        800088=>'支付失败，请更换银行卡再试或支付宝还款',
        800089=>'支付失败，请更换银行卡再试或支付宝还款',
        800090=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800091=>'银行维护中，请稍后再试',
        800092=>'系统繁忙，请稍后再试',
        800093=>'支付失败，请更换银行卡再试或支付宝还款',
        800094=>'支付失败，请更换银行卡再试或支付宝还款',
        800095=>'支付失败，请更换银行卡再试或支付宝还款',
        800096=>'支付失败，请更换银行卡再试或支付宝还款',
        800097=>'支付失败，请更换银行卡再试或支付宝还款',
        800098=>'支付失败，请更换银行卡再试或支付宝还款',
        800099=>'支付失败，请更换银行卡再试或支付宝还款',
        800100=>'支付失败，请更换银行卡再试或支付宝还款',
        800101=>'支付失败，请更换银行卡再试或支付宝还款',
        800102=>'支付失败，请更换银行卡再试或支付宝还款',
        800103=>'支付失败，请更换银行卡再试或支付宝还款',
        800104=>'支付失败，请更换银行卡再试或支付宝还款',
        800105=>'支付失败，请更换银行卡再试或支付宝还款',
        800106=>'支付失败，请更换银行卡再试或支付宝还款',
        800107=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800108=>'系统繁忙，请稍后再试',
        800109=>'您的银行卡余额不足，请充值后还款或支付宝还款',
        800110=>'支付失败，请更换银行卡再试或支付宝还款',
        800111=>'支付失败，请更换银行卡再试或支付宝还款',
        800112=>'支付失败，请更换银行卡再试或支付宝还款',
        800113=>'您的支付金额超过银行卡限制，请更换银行卡再试或支付宝还款',
        800114=>'支付失败，请更换银行卡再试或支付宝还款',
        800115=>'系统繁忙，请稍后再试',
        800116=>'支付失败，请更换银行卡再试或支付宝还款',
        800117=>'支付失败，请更换银行卡再试或支付宝还款',
        800118=>'支付失败，请更换银行卡再试或支付宝还款',
        800119=>'支付失败，请更换银行卡再试或支付宝还款',
        800120=>'银行维护中，请稍后再试',
        800121=>'支付失败，请更换银行卡再试或支付宝还款',
        800122=>'支付失败，请更换银行卡再试或支付宝还款',
        800123=>'支付失败，请更换银行卡再试或支付宝还款',
        800124=>'支付失败，请更换银行卡再试或支付宝还款',
        800125=>'支付失败，请更换银行卡再试或支付宝还款',
        800126=>'支付失败，请更换银行卡再试或支付宝还款',
        800127=>'支付失败，请更换银行卡再试或支付宝还款',
        800128=>'支付失败，请更换银行卡再试或支付宝还款',
        800130=>'支付失败，请更换银行卡再试或支付宝还款',
        800129=>'支付失败，请更换银行卡再试或支付宝还款',
        800131=>'支付失败，请更换银行卡再试或支付宝还款'
    ];
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
        ];
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_credit_money_log}}';
    }
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_kdkj');
    }


    
    /**
     * 获得还款订单信息
     * @return \yii\db\ActiveQuery
     */
    public function getUserLoanOrderRepayment() {
        return $this->hasOne(UserLoanOrderRepayment::className(), array('order_id' => 'order_id'));
    }

    public static function updateDebitResult($id, $status, $operator_money, $remark=''){
        $attrs = ['status' => $status, 'updated_at' => time(), ];
        if ($remark) {
            $attrs['remark'] = $remark;
        }

        return self::updateAll($attrs, [
            'id' => $id,
            'status'=>[self::STATUS_ING,self::STATUS_NORMAL],
            'operator_money'=>$operator_money,
        ]);
    }
    
    public static function getDebitStatus($user_id,$order_id){
        $ret = \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_{$user_id}_{$order_id}"]);
        if($ret === null){
            return -2;
        }
        return $ret;
    }
    public static function getDebitStatusDay($user_id,$card_id){
        $ret = \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_{$user_id}_{$card_id}"]);
        if($ret === null){
            return -2;
        }
        return $ret;
    }

    public static function addDebitLock($user_id,$order_id){
        $key = self::DEBIT_REDIS_KEY_PREFIX."_lock_{$user_id}_{$order_id}";
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 10]);
            return true;
        }else{
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 10]);
        }
        return false;
    }
    public static function setDebitStatusDay($user_id,$card_id,$status){
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_{$user_id}_{$card_id}", $status, 'EX', 86400]);
    }
    public static function setDebitStatus($user_id,$order_id,$status){
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_{$user_id}_{$order_id}", $status, 'EX', 600]);
    }
    public static function setAliyDebitResponse($user_id,$order_id,$res)
    {
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_res_{$user_id}_{$order_id}", $res, 'EX', 600]);
    }

    public static function setCreditMoneyLogId($user_id,$order_id,$userCreditMoneyLogId)
    {
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_credit_money_log_{$user_id}_{$order_id}", $userCreditMoneyLogId, 'EX', 86400]);
    }

    public static function getCreditMoneyLogId($user_id,$order_id)
    {
        return \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_credit_money_log_{$user_id}_{$order_id}"]);
    }

    public static function getAliyDebitResponse($user_id,$order_id){
        $ret = \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_res_{$user_id}_{$order_id}"]);
        return $ret;
    }
    public static function getDebitErrorMsg($user_id,$order_id){
        $ret = \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_msg_{$user_id}_{$order_id}"]);
        return $ret;
    }

    public static function setDebitErrorMsg($user_id,$order_id,$msg){
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_msg_{$user_id}_{$order_id}", $msg, 'EX', 600]);
    }
    public static function delDebitErrorMsg($user_id,$order_id){
        return \Yii::$app->redis->executeCommand('DEL', [self::DEBIT_REDIS_KEY_PREFIX."_msg_{$user_id}_{$order_id}"]);
    }
    public static function clearDebitStatus($user_id,$order_id){
        \Yii::$app->redis->executeCommand('DEL', [self::DEBIT_REDIS_KEY_PREFIX."_lock_{$user_id}_{$order_id}"]);
        \Yii::$app->redis->executeCommand('DEL', [self::DEBIT_REDIS_KEY_PREFIX."_{$user_id}_{$order_id}"]);
        \Yii::$app->redis->executeCommand('DEL', [self::DEBIT_REDIS_KEY_PREFIX."_msg_{$user_id}_{$order_id}"]);
        \Yii::$app->redis->executeCommand('DEL', [self::DEBIT_REDIS_KEY_PREFIX."_alipay_{$user_id}_{$order_id}"]);
        return true;
    }

    public static function setAliPayStatus($user_id,$order_id,$status) {
        return \Yii::$app->redis->executeCommand('SET', [self::DEBIT_REDIS_KEY_PREFIX."_alipay_{$user_id}_{$order_id}", $status, 'EX', 600]);
    }

    public static function getAliPayStatus($user_id,$order_id) {
        $ret = \Yii::$app->redis->executeCommand('GET', [self::DEBIT_REDIS_KEY_PREFIX."_alipay_{$user_id}_{$order_id}"]);
        if($ret === null){
            return false;
        }
        return $ret;
    }

    public static function addMutexDebitLock($order_uuid) {
        $key = "UserCreditMoneyLog_callback_lock_".$order_uuid;
        if(1 == \Yii::$app->redis->executeCommand('INCRBY', [$key, 1])){
            \Yii::$app->redis->executeCommand('EXPIRE', [$key, 120]);
            return true;
        }
        return false;
    }
    public static function clearMutexDebitLock($order_uuid){
        $key = "UserCreditMoneyLog_callback_lock_".$order_uuid;
        \Yii::$app->redis->executeCommand('DEL', [$key]);
    }


    /**
     * 获得扣款订单信息
     * @return \yii\db\ActiveQuery
     */
    public function getUserLoanOrder() {
        return $this->hasOne(UserLoanOrder::className(), array('id' => 'order_id'));
    }

    public function getLoanPerson(){
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }
    
    public function beforeSave($insert){
    
        if (parent::beforeSave($insert)) {
            if($insert) {
    
                $repayment_info = UserLoanOrderRepayment::find()->where(['order_id'=>$this->order_id])->one();
    
                $list = UserCreditMoneyLog::find()->where(['order_id'=>$this->order_id,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->orderBy('id')->asArray()->all();
    
                $yh_interests = array_sum(array_column($list,'operator_interests')); // 已还利息
                $yh_principal = array_sum(array_column($list,'operator_principal')); // 已还本金
                $yh_late_fee  = array_sum(array_column($list,'operator_late_fee'));  // 已还滞纳金
    
                $dh_interests = $repayment_info->interests - $yh_interests;   // 待还  利息
                $dh_principal = $repayment_info->principal - $yh_principal;   // 剩余待还   本金
                $dh_late_fee  = $repayment_info->late_fee  - $yh_late_fee;    // 剩余待还   滞纳金
    
    
                $money = $this-> operator_money ; // 此次还款金额
    
                if($dh_interests > 0){
                    $this->operator_interests = ($dh_interests >= $money ) ? $money : $dh_interests ;
                    $money = $money - $this->operator_interests;
                }
                
                if($dh_principal > 0 ){
                    $this->operator_principal = ($dh_principal >= $money ) ? $money : $dh_principal ;
                    $money = $money - $this->operator_principal;
                }
                
                if($dh_late_fee > 0){
                    $this->operator_late_fee = ($dh_late_fee >= $money ) ? $money : $dh_late_fee ;
                    $money = $money - $this->operator_late_fee;
                }
             
                $this->operator_overflow = $money;
                
            }
            return true;
        } else {
            return false;
        }
    }
      
}