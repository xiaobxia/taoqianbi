<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use common\base\LogChannel;
use yii\db\ActiveRecord;

class CreditZmop extends ActiveRecord {
    const APPID = "300001467"; //极速荷包线上

    public static $appids = [
        self::APPID => [
            'privateKeyFile' => '@common/config/cert/zmop/jshb_prod_private_key.pem',
            'zmPublicKeyFile' => '@common/config/cert/zmop/jshb_prod_public_key_zm.pem',
        ],
    ];

    /**
     * 根据租户和环境选择芝麻应用的appid
     * @param integer $source_id
     * @param string $env
     *
     * @return string
     */
    public static function getAppId($source_id) {
        return self::APPID;
    }

    const PRODUCT_RRBX = 1; //人人保险
    const PRODUCT_XJK_SHANDAI = 2;//极速荷包 闪贷
    const PRODUCT_XJK_M = 3;//极速荷包 M版
    public static $product_list = [
        self::PRODUCT_RRBX => '人人保险',
        self::PRODUCT_XJK_SHANDAI => APP_NAMES.'-闪贷',
        self::PRODUCT_XJK_M => APP_NAMES.'-M版',
    ];

    const TYPE_H5 = 1;//H5授权
    const TYPE_MESSAGE = 2;//短信授权
    const TYPE_CLIENT = 3;//客户端授权

    const STATUS_0 = 0;//表示未授权
    const STATUS_1 = 1;//表示已经授权
    const STATUS_2 = 2;//授权被取消

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期

    const WATCH_FALSE = 1;//行业关注名单未匹配
    const WATCH_TURE = 2;//行业关注名单匹配

    const ZM_TYPE_SCORE = 1;
    const ZM_TYPE_RAIN = 2;
    const ZM_TYPE_WATCH = 3;
    const ZM_TYPE_IVS = 4;
    const ZM_TYPE_DAS = 5;

    public static $status_list = [
        self::STATUS_0 => '未授权',
        self::STATUS_1 => '已授权',
        self::STATUS_2 => '已取消',
    ];
    public static $zm_type_list = [
        self::ZM_TYPE_SCORE => '芝麻积分',
        self::ZM_TYPE_RAIN => '手机rain分',
        self::ZM_TYPE_WATCH => '行业关注名单',
        self::ZM_TYPE_IVS => 'ivs积分',
        self::ZM_TYPE_DAS => 'das',
    ];

    //DAS信息的map
    public static $das_keys = [
        'company_name' => '所在公司',
        'occupation' => '职业类型',
        'mobile_fixed_days' => '手机号稳定天数',
        'adr_stability_days' => '地址稳定天数',
        'consume_steady_byxs_1y' => '消费稳定度(最近一年的月消费水平的持续性、稳定性水平)',
        'use_mobile_2_cnt_1y' => '最近一年使用手机号码数',
        'activity_area_stability' => '活动区域个数(通过最近一年使用的地址信息对应的县级单位的个数来进行评估)',
        'last_1y_total_active_biz_cnt' => '最近一年支付活跃场景数(最近一年的支付相关业务场景的活跃情况)',
        'flightcount' => '乘机次数(最近一年总共乘机次数)',
        'domesticbuscount' => '国内商务舱乘机次数(最近一年总共国内商务舱乘机次数)',
        'domesticfirstcount' => '国内头等舱乘机次数(最近一年总共国内头等舱乘机次数 )',
        'flightintercount' => '国际乘机次数(最近一年总共国际乘机次数)',
        'avgdomesticdiscount' => '平均国内折扣(最近一年平均国内乘机购票的折扣比例)',
        'have_car_flag' => '是否有车',
        'have_fang_flag' => '是否有房',
        'last_6m_avg_asset_total' => '最近六个月流动资产日均值(单位：元)',
        'last_3m_avg_asset_total' => '最近三个月流动资产日均值(单位：元)',
        'last_1m_avg_asset_total' => '最近一个月流动资产日均值(单位：元)',
        'last_1y_avg_asset_total' => '最近一年流动资产日均值(单位：元)',
        'tot_pay_amt_6m' => '最近六个月支付总金额(单位：元)',
        'tot_pay_amt_3m' => '最近三个月支付总金额(单位：元)',
        'tot_pay_amt_1m' => '最近一个月支付总金额(单位：元)',
        'ebill_pay_amt_6m' => '最近六个月消费总金额(单位：元)',
        'ebill_pay_amt_3m' => '最近三个月消费总金额(单位：元)',
        'ebill_pay_amt_1m' => '最近一个月消费总金额(单位：元)',
        'avg_puc_sdm_last_1y' => '生活缴费层次(最近一年月均水电煤缴费金额（单位：元))',
        'xfdc_index' => '消费档次(根据近一年的消费金额、类目等推断整体消费档次并分10层)',
        'pre_1y_pay_cnt' => '最近一年手机充值支付总笔数',
        'pre_1y_pay_amount' => '最近一年手机充值支付总金额(单位：元)',
        'auth_fin_last_1m_cnt' => '最近一个月主动查询金融机构数',
        'auth_fin_last_3m_cnt' => '最近三个月主动查询金融机构数',
        'auth_fin_last_6m_cnt' => '最近六个月主动查询金融机构数',
        'credit_pay_amt_1m' => '最近一个月本人贷款和信用卡还款总金额（单位：元)',
        'credit_pay_amt_3m' => '最近三个月本人贷款和信用卡还款总金额（单位：元)',
        'credit_pay_amt_6m' => '最近六个月本人贷款和信用卡还款总金额（单位：元)',
        'credit_pay_amt_1y' => '最近一年本人贷款和信用卡还款总金额(单位：元)',
        'credit_pay_months_1y' => '信贷类产品还款最近一年还款月份数',
        'credit_total_pay_months' => '信贷类产品还款历史月份数',
        'credit_duration' => '信用账户历史时长天数',
        'positive_biz_cnt_1y' => '最近一年履约场景个数，履约场景主要指借贷、免押服务等',
        'ovd_order_cnt_1m' => '最近一个月逾期总笔数',
        'ovd_order_amt_1m' => '最近一个月逾期总金额(单位：元)',
        'ovd_order_cnt_3m' => '最近三个月逾期总笔数',
        'ovd_order_amt_3m' => '最近三个月逾期总金额(单位：元)',
        'ovd_order_cnt_6m' => '最近六个月逾期总笔数',
        'ovd_order_amt_6m' => '最近六个月逾期总金额(单位：元)',
        'ovd_order_cnt_12m' => '最近一年逾期总笔数',
        'ovd_order_amt_12m' => '最近一年逾期总金额(单位：元)',
        'ovd_order_cnt_3m_m1_status' => '最近三个月 M1 状态',
        'ovd_order_cnt_6m_m1_status' => '最近六个月 M1 状态',
        'ovd_order_cnt_12m_m1_status' => '最近一年 M1 状态',
        'ovd_order_cnt_12m_m3_status' => '最近一年 M3 状态',
        'ovd_order_cnt_12m_m6_status' => '最近一年 M6 状态',
        'ovd_order_cnt_2y_m3_status' => '最近两年 M3 状态',
        'ovd_order_cnt_2y_m6_status' => '最近两年 M6 状态',
        'ovd_order_cnt_5y_m3_status' => '最近五年 M3 状态',
        'ovd_order_cnt_5y_m6_status' => '最近五年 M6 状态',
        'relevant_stability' => '近1年人脉圈稳定度(反映一年前的亲密联系人与现在的亲密联系人的重合百分比（0%-100%）)',
        'sns_pii' => '社交影响力指数(根据资金往来关系，按照影响力算法推断的用户的社会交往及社会关系的影响能力指数)',
    ];

    public static $ovd_order_amt_12m_map = [
        '01' => '0',
        '02' => '0-500',
        '03' => '500-1000',
        '04' => '1000-2000',
        '05' => '2000-3000',
        '06' => '3000-4000',
        '07' => '4000-6000',
        '08' => '6000-8000',
        '09' => '8000-10000',
        '10' => '10000-15000',
        '11' => '15000-20000',
        '12' => '20000-25000',
        '13' => '25000-30000',
        '14' => '30000-40000',
        '15' => '40000以上',
    ];
    public static $ovd_order_cnt_12m_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-4',
        '05' => '4-6',
        '06' => '6-8',
        '07' => '8-10',
        '08' => '10-12',
        '09' => '12-15',
        '10' => '15以上',
    ];
    public static $ovd_order_amt_1m_map = [
        '01' => '0',
        '02' => '0-500',
        '03' => '500-1000',
        '04' => '1000-2000',
        '05' => '2000-3000',
        '06' => '3000-4000',
        '07' => '4000-6000',
        '08' => '6000-8000',
        '09' => '8000-10000',
        '10' => '10000-15000',
        '11' => '15000-20000',
        '12' => '20000-25000',
        '13' => '25000-30000',
        '14' => '30000-40000',
        '15' => '40000以上',
    ];
    public static $ovd_order_cnt_1m_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-4',
        '05' => '4-6',
        '06' => '6-8',
        '07' => '8-10',
        '08' => '10-12',
        '09' => '12-15',
        '10' => '15以上',
    ];

    public static $ovd_order_amt_3m_map = [
        '01' => '0',
        '02' => '0-500',
        '03' => '500-1000',
        '04' => '1000-2000',
        '05' => '2000-3000',
        '06' => '3000-4000',
        '07' => '4000-6000',
        '08' => '6000-8000',
        '09' => '8000-10000',
        '10' => '10000-15000',
        '11' => '15000-20000',
        '12' => '20000-25000',
        '13' => '25000-30000',
        '14' => '30000-40000',
        '15' => '40000以上',
    ];
    public static $ovd_order_cnt_3m_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-4',
        '05' => '4-6',
        '06' => '6-8',
        '07' => '8-10',
        '08' => '10-12',
        '09' => '12-15',
        '10' => '15以上',
    ];
    public static $ovd_order_amt_6m_map = [
        '01' => '0',
        '02' => '0-500',
        '03' => '500-1000',
        '04' => '1000-2000',
        '05' => '2000-3000',
        '06' => '3000-4000',
        '07' => '4000-6000',
        '08' => '6000-8000',
        '09' => '8000-10000',
        '10' => '10000-15000',
        '11' => '15000-20000',
        '12' => '20000-25000',
        '13' => '25000-30000',
        '14' => '30000-40000',
        '15' => '40000以上',
    ];
    public static $ovd_order_cnt_6m_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-4',
        '05' => '4-6',
        '06' => '6-8',
        '07' => '8-10',
        '08' => '10-12',
        '09' => '12-15',
        '10' => '15以上',
    ];
    public static $positive_biz_cnt_1y_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-3',
        '05' => '3-4',
        '06' => '4-5',
        '07' => '5-6',
        '08' => '6-8',
        '09' => '8-10',
        '10' => '10以上',
    ];
    public static $credit_pay_amt_6m_map = [
        '01' => '0',
        '02' => '0-5500',
        '03' => '5500-15000',
        '04' => '15000-39000',
        '05' => '39000-54000',
        '06' => '54000-78000',
        '07' => '78000-108000',
        '08' => '108000-138000',
        '09' => '138000-180000',
        '10' => '180000以上',
    ];
    public static $credit_duration_map = [
        '01' => '0',
        '02' => '0-183',
        '03' => '183-548',
        '04' => '548-730',
        '05' => '730-913',
        '06' => '913-1095',
        '07' => '1095-1278',
        '08' => '1278-1460',
        '09' => '1460-1643',
        '10' => '1643以上',
    ];
    public static $credit_total_pay_months_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-3',
        '04' => '3-6',
        '05' => '6-10',
        '06' => '10-15',
        '07' => '15-20',
        '08' => '20-24',
        '09' => '24-36',
        '10' => '36以上',
    ];
    public static $credit_pay_months_1y_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-2',
        '04' => '2-3',
        '05' => '3-5',
        '06' => '5-7',
        '07' => '7-9',
        '08' => '9-10',
        '09' => '10-11',
        '10' => '11-12',
    ];
    public static $credit_pay_amt_1m_map = [
        '01' => '0',
        '02' => '0-300',
        '03' => '300-2000',
        '04' => '2000-6600',
        '05' => '6600-9000',
        '06' => '9000-13000',
        '07' => '13000-18000',
        '08' => '18000-23000',
        '09' => '23000-30000',
        '10' => '30000以上',
    ];
    public static $credit_pay_amt_3m_map = [
        '01' => '0',
        '02' => '0-2500',
        '03' => '2500-7500',
        '04' => '7500-20000',
        '05' => '20000-27000',
        '06' => '27000-39000',
        '07' => '39000-54000',
        '08' => '54000-69000',
        '09' => '69000-90000',
        '10' => '90000以上',
    ];
    public static $credit_pay_amt_1y_map = [
        '01' => '0',
        '02' => '0-10000',
        '03' => '10000-28000',
        '04' => '28000-71000',
        '05' => '71000-108000',
        '06' => '108000-156000',
        '07' => '156000-216000',
        '08' => '216000-276000',
        '09' => '276000-360000',
        '10' => '360000以上',
    ];
    public static $auth_fin_last_1m_cnt_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-3',
        '04' => '3-7',
        '05' => '7以上',
    ];
    public static $auth_fin_last_3m_cnt_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-3',
        '04' => '3-7',
        '05' => '7以上',
    ];

    public static $auth_fin_last_6m_cnt_map = [
        '01' => '0',
        '02' => '0-1',
        '03' => '1-3',
        '04' => '3-7',
        '05' => '7以上',
    ];
    public static $tot_pay_amt_6m_map = [
        '01' => '0',
        '02' => '0-6100',
        '03' => '6100-11000',
        '04' => '11000-18000',
        '05' => '18000-27000',
        '06' => '27000-40000',
        '07' => '40000-61000',
        '08' => '61000-99000',
        '09' => '99000-190000',
        '10' => '190000-300000',
        '11' => '300000-360000',
        '12' => '360000-420000',
        '13' => '420000-480000',
        '14' => '480000-600000',
        '15' => '600000以上',
    ];
    public static $pre_1y_pay_amount_map = [
        '01' => '0',
        '02' => '0-150',
        '03' => '150-400',
        '04' => '400-700',
        '05' => '700-1000',
        '06' => '1000-1300',
        '07' => '1300-1700',
        '08' => '1700-2400',
        '09' => '2400-3600',
        '10' => '3600以上',
    ];
    public static $pre_1y_pay_cnt_map = [
        '01' => '0',
        '02' => '0-2',
        '03' => '2-7',
        '04' => '7-11',
        '05' => '11-16',
        '06' => '16-21',
        '07' => '21-27',
        '08' => '27-35',
        '09' => '35-50',
        '10' => '50以上',
    ];
    public static $avg_puc_sdm_last_1y_map = [
        '01' => '0',
        '02' => '0-100',
        '03' => '100-200',
        '04' => '200-400',
        '05' => '400-600',
        '06' => '600-1000',
        '07' => '1000-1500',
        '08' => '1500-2000',
        '09' => '2000-3000',
        '10' => '3000以上',
    ];
    public static $ebill_pay_amt_1m_map = [
        '01' => '0',
        '02' => '0-120',
        '03' => '120-340',
        '04' => '340-600',
        '05' => '600-900',
        '06' => '900-1300',
        '07' => '1300-1900',
        '08' => '1900-2900',
        '09' => '2900-5300',
        '10' => '5300-10000',
        '11' => '10000-15000',
        '12' => '15000-22000',
        '13' => '22000-30000',
        '14' => '30000-40000',
        '15' => '40000以上',
    ];
    public static $ebill_pay_amt_3m_map = [
        '01' => '0',
        '02' => '0-900',
        '03' => '900-1600',
        '04' => '1600-2400',
        '05' => '2400-3400',
        '06' => '3400-4600',
        '07' => '4600-6400',
        '08' => '6400-9000',
        '09' => '9000-15000',
        '10' => '15000-30000',
        '11' => '30000-45000',
        '12' => '45000-66000',
        '13' => '66000-90000',
        '14' => '90000-120000',
        '15' => '120000以上',
    ];

    public static $ebill_pay_amt_6m_map = [
        '01' => '0',
        '02' => '0-1500',
        '03' => '1500-3000',
        '04' => '3000-5000',
        '05' => '5000-7500',
        '06' => '7500-10000',
        '07' => '10000-13000',
        '08' => '13000-18000',
        '09' => '18000-30000',
        '10' => '30000-60000',
        '11' => '60000-90000',
        '12' => '90000-132000',
        '13' => '132000-180000',
        '14' => '180000-240000',
        '15' => '240000以上',
    ];
    public static $tot_pay_amt_1m_map = [
        '01' => '0',
        '02' => '0-430',
        '03' => '430-1000',
        '04' => '1000-2000',
        '05' => '2000-3500',
        '06' => '3500-5500',
        '07' => '5500-8800',
        '08' => '8800-15000',
        '09' => '15000-33000',
        '10' => '33000-50000',
        '11' => '50000-60000',
        '12' => '60000-70000',
        '13' => '70000-80000',
        '14' => '80000-100000',
        '15' => '100000以上',
    ];
    public static $tot_pay_amt_3m_map = [
        '01' => '0',
        '02' => '0-2300',
        '03' => '2300-4800',
        '04' => '4800-8200',
        '05' => '8200-13000',
        '06' => '13000-20000',
        '07' => '20000-30000',
        '08' => '30000-49000',
        '09' => '49000-96000',
        '10' => '96000-150000',
        '11' => '150000-180000',
        '12' => '180000-210000',
        '13' => '210000-240000',
        '14' => '240000-300000',
        '15' => '300000以上',
    ];
    public static $last_6m_avg_asset_total_map = [
        '01' => '0',
        '02' => '0-15',
        '03' => '15-55',
        '04' => '55-150',
        '05' => '150-400',
        '06' => '400-1000',
        '07' => '1000-2500',
        '08' => '2500-6000',
        '09' => '6000-18000',
        '10' => '18000-30000',
        '11' => '30000-45000',
        '12' => '45000-60000',
        '13' => '60000-80000',
        '14' => '80000-100000',
        '15' => '100000以上',
    ];
    public static $last_1m_avg_asset_total_map = [
        '01' => '0',
        '02' => '0-15',
        '03' => '15-55',
        '04' => '55-150',
        '05' => '150-400',
        '06' => '400-1000',
        '07' => '1000-2500',
        '08' => '2500-6000',
        '09' => '6000-18000',
        '10' => '18000-30000',
        '11' => '30000-45000',
        '12' => '45000-60000',
        '13' => '60000-80000',
        '14' => '80000-100000',
        '15' => '100000以上',
    ];
    public static $last_3m_avg_asset_total_map = [
        '01' => '0',
        '02' => '0-15',
        '03' => '15-55',
        '04' => '55-150',
        '05' => '150-400',
        '06' => '400-1000',
        '07' => '1000-2500',
        '08' => '2500-6000',
        '09' => '6000-18000',
        '10' => '18000-30000',
        '11' => '30000-45000',
        '12' => '45000-60000',
        '13' => '60000-80000',
        '14' => '80000-100000',
        '15' => '100000以上',
    ];
    public static $last_1y_avg_asset_total_map = [
        '01' => '0',
        '02' => '0-15',
        '03' => '15-55',
        '04' => '55-150',
        '05' => '150-400',
        '06' => '400-1000',
        '07' => '1000-2500',
        '08' => '2500-6000',
        '09' => '6000-18000',
        '10' => '18000-30000',
        '11' => '30000-45000',
        '12' => '45000-60000',
        '13' => '60000-80000',
        '14' => '80000-100000',
        '15' => '100000以上',
    ];
    public static $have_fang_flag_map = [
        '01' => '无房概率较高',
        '02' => '可能有房',
        '03' => '肯定有房'
    ];
    public static $have_car_flag_map = [
        '01' => '无车概率较高',
        '02' => '可能有车',
        '03' => '肯定有车'
    ];
    public static $avgdomesticdiscount_map = [
        '#' => '缺失',
        '01' => '0-5',
        '02' => '5-8',
        '03' => '8-9',
        '04' => '9-11',
        '05' => '11-13',
        '06' => '13-16',
        '07' => '16-20',
        '08' => '20-22',
        '09' => '22以上',
    ];
    public static $domesticfirstcount_map = [
        '#' => '缺失',
        '01' => '0次',
        '02' => '0-1次',
        '03' => '1-3次',
        '04' => '3次以上',
    ];
    public static $domesticbuscount_map = [
        '#' => '缺失',
        '01' => '0次',
        '02' => '0-1次',
        '03' => '1-3次',
        '04' => '3次以上',
    ];
    public static $flightintercount_map = [
        '#' => '缺失',
        '01' => '0-1次',
        '02' => '1-3次',
        '03' => '3-5次',
        '04' => '5次以上',
    ];

    public static $flightcount_map = [
        '#' => '缺失',
        '01' => '0-1次',
        '02' => '1-3次',
        '03' => '3-5次',
        '04' => '5次以上',
    ];
    public static $last_1y_total_active_biz_cnt_map = [
        '01' => '0个',
        '02' => '0-3个',
        '03' => '3-6个',
        '04' => '6-8个',
        '05' => '8-10个',
        '06' => '10-12个',
        '07' => '12-14个',
        '08' => '14-16个',
        '09' => '16-18个',
        '10' => '18个以上',
    ];
    public static $activity_area_stability_map = [
        '01' => '0个',
        '02' => '0-1个',
        '03' => '1-2个',
        '04' => '2-3个',
        '05' => '3-4个',
        '06' => '4-5个',
        '07' => '5-6个',
        '08' => '6-7个',
        '09' => '7-10个',
        '10' => '10个以上',
    ];
    public static $consume_steady_byxs_1y_map = [
        '01' => '0',
        '02' => '0-0.46',
        '03' => '0.46-0.56',
        '04' => '0.56-0.65',
        '05' => '0.65-0.73',
        '06' => '0.73-0.82',
        '07' => '0.82-0.93',
        '08' => '0.93-1.08',
        '09' => '1.08-1.32',
        '10' => '1.32以上',
    ];
    public static $relevant_stability_map = [
        '#' => '缺失',
        '01' => '0%',
        '02' => '0%-16%',
        '03' => '16%-25%',
        '04' => '25%-33%',
        '05' => '33%-42%',
        '06' => '42%-52%',
        '07' => '52%-66%',
        '08' => '66%-89%',
        '09' => '89%-100%',
    ];

    public static $sns_pii_map = [
        '#' => '缺失',
        '01' => '1-27',
        '02' => '27-36',
        '03' => '36-43',
        '04' => '43-48',
        '05' => '48-54',
        '06' => '54-60',
        '07' => '60-68',
        '08' => '68-81',
        '09' => '81-1000',
    ];
    //逾期笔数map
    public static $ovd_order_cnt_map = [
        '01' => '逾期0笔',
        '02' => '逾期0-1笔',
        '03' => '逾期1-2笔',
        '04' => '逾期2-4笔',
        '05' => '逾期4-6笔',
        '06' => '逾期6-8笔',
        '07' => '逾期8-10笔',
        '08' => '逾期10-12笔',
        '09' => '逾期12-15笔',
        '10' => '逾期15笔以上',
    ];


    //逾期金额map
    public static $ovd_order_amt_map = [
        '01' => '逾期金额0元',
        '02' => '逾期金额0-500元',
        '03' => '逾期金额500-1000元',
        '04' => '逾期金额1000-2000元',
        '05' => '逾期金额2000-3000元',
        '06' => '逾期金额3000-4000元',
        '07' => '逾期金额4000-6000元',
        '08' => '逾期金额6000-8000元',
        '09' => '逾期金额8000-10000元',
        '10' => '逾期金额10000-15000元',
        '11' => '逾期金额15000-20000元',
        '12' => '逾期金额20000-25000元',
        '13' => '逾期金额25000-30000元',
        '14' => '逾期金额30000-40000元',
        '15' => '逾期金额40000元以上',
    ];

    //最近一年使用手机号码数map
    public static $use_mobile_2_cnt_1y_map = [
        '#'  => '缺失',
        '01' => '手机号0-1个',
        '02' => '手机号1-3个',
        '03' => '手机号3-5个',
        '04' => '手机号5个以上',
    ];

    //手机号稳定天数map
    public static $mobile_fixed_days_map = [
        '01' => '手机号使用0天',
        '02' => '手机号使用0-270天',
        '03' => '手机号使用270-540天',
        '04' => '手机号使用540-720天',
        '05' => '手机号使用720-900天',
        '06' => '手机号使用900-1080天',
        '07' => '手机号使用1080-1260天',
        '08' => '手机号使用1260-1440天',
        '09' => '手机号使用1440-1800天',
        '10' => '手机号使用1800天以上',
    ];
    //地址稳定天数map
    public static $adr_stability_days_map = [
        '01' => '0天',
        '02' => '0-540天',
        '03' => '540-720天',
        '04' => '720-1080天',
        '05' => '1080-1260天',
        '06' => '1260-1440天',
        '07' => '1440-1800天',
        '08' => '1800-2160天',
        '09' => '2160-2520天',
        '10' => '2520天以上',
    ];

    public static $map = [
        'adr_stability_days'=> 'adr_stability_days_map',
        'mobile_fixed_days'=> 'mobile_fixed_days_map',
        'use_mobile_2_cnt_1y'=> 'use_mobile_2_cnt_1y_map',
        'ovd_order_amt'=> 'ovd_order_amt_map',
        'ovd_order_cnt'=> 'ovd_order_cnt_map',
        'sns_pii'=> 'sns_pii_map',
        'relevant_stability'=> 'relevant_stability_map',
        'consume_steady_byxs_1y'=> 'consume_steady_byxs_1y_map',
        'activity_area_stability'=> 'activity_area_stability_map',
        'last_1y_total_active_biz_cnt'=> 'last_1y_total_active_biz_cnt_map',
        'flightcount'=> 'flightcount_map',
        'flightintercount'=> 'flightintercount_map',
        'domesticbuscount'=> 'domesticbuscount_map',
        'domesticfirstcount'=> 'domesticfirstcount_map',
        'avgdomesticdiscount'=> 'avgdomesticdiscount_map',
        'have_car_flag'=> 'have_car_flag_map',
        'have_fang_flag'=> 'have_fang_flag_map',
        'last_1y_avg_asset_total'=> 'last_1y_avg_asset_total_map',
        'last_3m_avg_asset_total'=> 'last_3m_avg_asset_total_map',
        'last_1m_avg_asset_total'=> 'last_1m_avg_asset_total_map',
        'last_6m_avg_asset_total'=> 'last_6m_avg_asset_total_map',
        'tot_pay_amt_3m'=> 'tot_pay_amt_3m_map',
        'tot_pay_amt_1m'=> 'tot_pay_amt_1m_map',
        'ebill_pay_amt_6m'=> 'ebill_pay_amt_6m_map',
        'ebill_pay_amt_3m'=> 'ebill_pay_amt_3m_map',
        'ebill_pay_amt_1m'=> 'ebill_pay_amt_1m_map',
        'avg_puc_sdm_last_1y'=> 'avg_puc_sdm_last_1y_map',
        'pre_1y_pay_cnt'=> 'pre_1y_pay_cnt_map',
        'pre_1y_pay_amount'=> 'pre_1y_pay_amount_map',
        'tot_pay_amt_6m'=> 'tot_pay_amt_6m_map',
        'auth_fin_last_6m_cnt'=> 'auth_fin_last_6m_cnt_map',
        'auth_fin_last_3m_cnt'=> 'auth_fin_last_3m_cnt_map',
        'auth_fin_last_1m_cnt'=> 'auth_fin_last_1m_cnt_map',
        'credit_pay_amt_1y'=> 'credit_pay_amt_1y_map',
        'credit_pay_amt_3m'=> 'credit_pay_amt_3m_map',
        'credit_pay_amt_1m'=> 'credit_pay_amt_1m_map',
        'credit_pay_months_1y'=> 'credit_pay_months_1y_map',
        'credit_total_pay_months'=> 'credit_total_pay_months_map',
        'credit_duration'=> 'credit_duration_map',
        'credit_pay_amt_6m'=> 'credit_pay_amt_6m_map',
        'positive_biz_cnt_1y'=> 'positive_biz_cnt_1y_map',
        'ovd_order_cnt_6m'=> 'ovd_order_cnt_6m_map',
        'ovd_order_amt_6m'=> 'ovd_order_amt_6m_map',
        'ovd_order_cnt_3m'=> 'ovd_order_cnt_3m_map',
        'ovd_order_amt_3m'=> 'ovd_order_amt_3m_map',
        'ovd_order_cnt_1m'=> 'ovd_order_cnt_1m_map',
        'ovd_order_amt_1m'=> 'ovd_order_amt_1m_map',
        'ovd_order_cnt_12m'=> 'ovd_order_cnt_12m_map',
        'ovd_order_amt_12m'=> 'ovd_order_amt_12m_map',
    ];
    public static $watch_status = [
        self::WATCH_FALSE => '未匹配到行业关注名单',
        self::WATCH_TURE => '匹配到行业关注名单'
    ];

    public static $iwatch_type = [
        'S001' => '金融（信贷类）',
        'S002' => '公检法',
        'S003' => '金融（支付类）',
        'S005' => '租车行业',
        'S006' => '酒店行业',
        'S007' => '电商行业',
        'S008' => '租房行业',
    ];

    public static $risk_type = [
        'R001' => '借贷逾期',
        'R005' => '套现',
        'R010' => '失信被执行人',
        'R011' => '盗卡者/盗卡者同伙',
        'R012' => '欺诈者/欺诈同伙',
        'R013' => '盗用操作/盗用者同伙',
        'R014' => '盗用支出/盗用者同伙',
        'R015' => '骗赔',
        'R032' => '案件库黑名单',
        'R016' => '逾期未支付',
        'R017' => '超期未还车',
        'R018' => '逾期未支付',
        'R019' => '虚假交易',
        'R021' => '涉嫌售假',
        'R022' => '房租逾期',
        'R023' => '杂费逾期',
        'R024' => '租客违约拒赔',
    ];

    public static $risk_code = [
        '-' => '暂无详情',
        'R00101' => '逾期1-30天',
        'R00102' => '逾期31-60天',
        'R00103' => '逾期61-90天',
        'R00104' => '逾期91-120天',
        'R00105' => '逾期121-150天',
        'R00106' => '逾期151-180天',
        'R00107' => '逾期180天以上',
        'R00121' => '逾期1期',
        'R00122' => '逾期2期',
        'R00123' => '逾期3期',
        'R00124' => '逾期4期',
        'R00125' => '逾期5期',
        'R00126' => '逾期6期',
        'R00127' => '逾期6期以上',
        'R00501' => '严重逾期且套现',
        'R01001' => '失信被执行人',
        'R01101' => '盗卡者/盗卡者同伙',
        'R01201' => '欺诈者/欺诈同伙',
        'R01301' => '盗用操作/盗用者同伙',
        'R01401' => '盗用支出/盗用者同伙',
        'R01501' => '骗赔',
        'R03201' => '营销作弊黑名单',
        'R01601' => '逾期1-7天',
        'R01602' => '逾期8-14天',
        'R01603' => '逾期15-30天',
        'R01604' => '逾期31天以上',
        'R01701' => '超期1-7天',
        'R01702' => '超期8-14天',
        'R01703' => '超期15-30天',
        'R01704' => '超期31天以上',
        'R01801' => '逾期1-7天',
        'R01802' => '逾期8-14天',
        'R01803' => '逾期15-30天',
        'R01804' => '逾期31天以上',
        'R01901' => '炒信',
        'R02101' => '涉嫌售假',
        'R02201' => '逾期1-7天',
        'R02202' => '逾期8-14天',
        'R02203' => '逾期15-30天',
        'R02204' => '逾期31天以上',
        'R02301' => '逾期1-7天',
        'R02302' => '逾期8-14天',
        'R02303' => '逾期15-30天',
        'R02304' => '逾期31天以上',
        'R02401' => '损坏房屋结构、家电等财产',
        'R02402' => '提前退租、转租',
        'R02403' => '逾期不归还房屋',
        'R02404' => '利用房屋进行违法违纪活动或多次扰民',
        'R02405' => '入住人数超过合同约定人数',
        'R02406' => '特殊违约拒赔行为',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%credit_zmop}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb() {
        return Yii::$app->get('db');
    }

    public function rules(){
        return [
            [[ 'id','status', 'id_number', 'open_id','person_id', 'created_at','updated_at','zm_score','rain_score','rain_info','ivs_score','ivs_info','watch_matched','watch_info','type','is_overdue'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * 获取最新的芝麻信用记录
     * @param array/string $params
     * @param string $dbName
     * @return mixed null / self
     */
    public static function gainCreditZmopLatest($params, $dbName = null) {
        $db = empty($dbName) ? null : \yii::$app->get($dbName);

        $creditZmop = self::findByCondition($params)->orderBy('id desc')->one($db);
        if (empty($creditZmop)) {
            return NULL;
        }

        //如果状态已过期或者创建时间超过1个月
        if ($creditZmop->is_overdue == self::IS_OVERDUE_1 || $creditZmop->updated_at + 30 * 86400 < time()) {
            if ($creditZmop->is_overdue != self::IS_OVERDUE_1) {
                $creditZmop->is_overdue = self::IS_OVERDUE_1;
                $creditZmop->save();
            }

            $now = \time();
            $newCreditZmop = new CreditZmop();
            $newCreditZmop->app_id = $creditZmop->app_id;
            $newCreditZmop->person_id = $creditZmop->person_id;
            $newCreditZmop->id_number = $creditZmop->id_number;
            $newCreditZmop->open_id = $creditZmop->open_id;
            $newCreditZmop->zm_score = $creditZmop->zm_score;
            $newCreditZmop->rain_info = $creditZmop->rain_info;
            $newCreditZmop->rain_score = $creditZmop->rain_score;
            $newCreditZmop->ivs_info = $creditZmop->ivs_info;
            $newCreditZmop->ivs_score = $creditZmop->ivs_score;
            $newCreditZmop->das_info = $creditZmop->das_info;
            $newCreditZmop->type = $creditZmop->type;
            $newCreditZmop->status = self::STATUS_1;
            $newCreditZmop->created_at = $now;
            $newCreditZmop->updated_at = $now;
            $newCreditZmop->is_overdue = self::IS_OVERDUE_0;
            if ($newCreditZmop->save()) {
                $creditZmop = self::findByCondition($params)->orderBy('id Desc')->one($db);
            }
            else {
                \yii::error(\sprintf('[%s][%s] record save failed.', \basename(__FILE__), __LINE__), LogChannel::CREDIT_ZMXY);
            }
        }

        return $creditZmop;
    }

    /**
     * 获取最新一条记录
     * @param unknown $params
     * @param string $dbName
     */
    public static function findLatestOne($params, $dbName = null) {
        $db = empty($dbName) ? null : \yii::$app->get($dbName);
        return self::findByCondition($params)
            ->orderBy('id desc')
            ->limit(1)->one($db);
    }
}
