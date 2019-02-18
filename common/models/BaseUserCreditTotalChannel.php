<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class BaseUserCreditTotalChannel extends ActiveRecord
{

    //是否记录过初始值：考虑到初始额度为0的情况；0、没有；1、有 is_already_initial_amount
    const TYPE_INITIAL_AMOUNT_NO = 0;
    const TYPE_INITIAL_AMOUNT_YES = 1;

    public static $is_already_initial_amount = [
        self::TYPE_INITIAL_AMOUNT_NO => '没有',
        self::TYPE_INITIAL_AMOUNT_YES => '有',
    ];

    //默认总额，单位分,为零钱包、房租宝、分期商城的总和
    const  AMOUNT = 100000;
    //首次借款收取费用
    const  FIRST_FEE = 2000;
    const  COUNTER_FEE_RATE = 30;

    const POCKET_APR = 1.4;//日利率
    const HOUSE_APR = 0.75;
    const INSTALLMENT_APR = 0.75;
    const POCKET_LATE_APR = 3;//日利率
    const HOUSE_LATE_APR = 15;
    const INSTALLMENT_LATE_APR = 15;
    const POCKET_MIN = 1;
    const POCKET_MAX = 30;
    const HOUSE_MIN = 1;
    const HOUSE_MAX = 3;
    const INSTALLMENT_MIN = 1;
    const INSTALLMENT_MAX = 3;

    //零钱包借款天数
    const POCKET_SEVEN_DAY = 7;

    public static $day_list = [
        self::POCKET_SEVEN_DAY => '7天',
    ];

    const CARD_TYPE_ALL = -1;
    const CARD_TYPE_ONE = 1;
    const CARD_TYPE_TWO = 2;
    const CARD_TYPE_THREE = 3;

    public static $card_type = [
        self::CARD_TYPE_ALL => '全部',
        self::CARD_TYPE_ONE => '白卡',
        self::CARD_TYPE_TWO => '发薪卡',
    ];
    public static $card_types = [
        self::CARD_TYPE_ALL => '全部',
        self::CARD_TYPE_ONE => '白卡',
        self::CARD_TYPE_TWO => '发薪卡',
    ];

    //卡信息
    public static $normal_card_info = [
        self::CARD_TYPE_ONE => [
            'card_title' => APP_NAMES,
            'card_subtitle' => 'CASH CARD',
            'card_amount' => 100000,
            'card_no' => 'XXXX 1314 0800 0000',
            'card_apr' => 1.4,//日利率
            'card_late_apr' => 3,
            'card_money_min' => 50000,
            'card_money_max' => 100000,
            'card_period_min' => 7,
            'card_period_max' => 30,
            'card_color' => '#62c132',
        ],
        self::CARD_TYPE_TWO => [
            'card_title' => '发薪卡',
            'card_subtitle' => 'GLODEN CARD',
            'card_amount' => 200000,
            'card_no' => 'XXXX 1314 0800 0000',
            'card_apr' => 0.5,//日利率
            'card_late_apr' => 3,//逾期日利率
            'card_money_min' => 50000,
            'card_money_max' => 200000,
            'card_period_min' => 7,
            'card_period_max' => 30,
            'card_color' => '#62c132',
        ],
    ];

    /**
     * 多卡产品信息
     */
    public static $multi_card_info = [
        self::CARD_TYPE_ONE => [
            'card_title' => APP_NAMES,
            'card_type' => self::CARD_TYPE_ONE,
            'card_subtitle' => '期限7-14天 资料真实即可申请 10分钟放款',
            'card_amount' => 300000,
            'card_apr' => 1.4,
            'card_late_apr' => 3,
            'card_money_min' => 50000,
            'card_money_max' => 300000,
            'card_period' => [7, 14],
            'card_color' => '#62c132',
            'card_no' => '6226 0000 **** 0000',
            'card_validity' => 'VALID THRU 31/07/2017',
            'card_bg_img' => 'image/tag/spring_1/bg_card_1.png',
            'card_centor_img' => 'image/tag/spring_1/bg_card_0.png',
        ],
        self::CARD_TYPE_TWO => [
            'card_title' => '发薪卡',
            'card_type' => self::CARD_TYPE_TWO,
            'card_subtitle' => '期限30天 低费率 每日限量放出 优质用户专享',
            'card_amount' => 500000,
            'card_apr' => 0.5,//日利率
            'card_late_apr' => 3,
            'card_money_min' => 100000,
            'card_money_max' => 500000,
            'card_period' => [14],
            'card_color' => '#62c132',
            'card_no' => '6226 0000 **** 0000',
            'card_validity' => 'VALID THRU 31/07/2017',
            'card_bg_img' => 'image/tag/spring_1/bg_card_2.png',
            'card_centor_img' => 'image/tag/spring_1/bg_card_0.png',
        ],
    ];

    /**
     * 未激活 发薪卡显示
     */
    public static function getFirstCard()
    {
        return [
            self::CARD_TYPE_ONE => self::$multi_card_info[self::CARD_TYPE_ONE]
        ];
    }

    /**
     * 未登录
     */
    public static function getNoLoginCard()
    {
        $multi_card_arr = self::$multi_card_info;

        $multi_card_arr[self::CARD_TYPE_ONE]["card_money_max"] = $multi_card_arr[self::CARD_TYPE_TWO]["card_money_max"];
        $multi_card_arr[self::CARD_TYPE_ONE]["card_period"] = [7, 14, 30];

        $multi_card_arr[self::CARD_TYPE_TWO]["card_money_min"] = $multi_card_arr[self::CARD_TYPE_ONE]["card_money_min"];
        $multi_card_arr[self::CARD_TYPE_TWO]["card_period"] = [7, 14, 30];

        return $multi_card_arr;
    }

    /**
     * 根据卡的类型获取当前用户的卡
     */
    public static function getUserCardList($card_type = 1)
    {
        $multi_card_arr = self::$multi_card_info;
        foreach (self::$multi_card_info as $card_key => &$card_row) {
            if ($card_type == 1) {
                if ($card_row["card_type"] != $card_type) {
                    $card_row["card_amount"] = 0;
                }
            }
            // 根据card_type排序
            // $card_type_arr[$card_key] = $card_row['card_type'];
        }
        // $sort_type = $card_type == 1 ? SORT_ASC : SORT_DESC ;
        // array_multisort($card_type_arr,$sort_type,$multi_card_arr);
        return $multi_card_arr;
    }


    const COMMON_CARD = 1;

    const LOAN_TYPE_DAY = 0;//按天
    const LOAN_TYPE_MONTH = 1;//按月
    const LOAN_TYPE_YEAR = 2;//按年

    public static $list_card = [
        self::COMMON_CARD => '小钱包普通卡',
    ];

    const MOTH_ONE = 1;
    const MOTH_TWO = 2;
    const MOTH_THREE = 3;
    const MOTH_FOUR = 4;

    public static $period_moth_list = [
        self::MOTH_TWO => '二个月',
        self::MOTH_THREE => '三个月',
    ];

}