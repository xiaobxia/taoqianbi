<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/25
 * Time: 18:04
 */
namespace console\models;

class MobileCity{

    const CHINA_UNICOM = 1; //中国联通
    const CHINA_MOBILE = 2; //中国移动
    const CHINA_TELECOMMUNICATIONS = 3; //中国电信

    public static $operator = [
        self::CHINA_UNICOM=>'中国联通',
        self::CHINA_MOBILE=>'中国移动',
        self::CHINA_TELECOMMUNICATIONS=>'中国电信',
    ];

    const CHINA_UNICOM_CHINA = 1;

    public static $sub_china_unicom = [
        self::CHINA_UNICOM_CHINA=>'中国联通'
    ];

    const CHINA_TELECOMMUNICATIONS_SC = 1;
    const CHINA_TELECOMMUNICATIONS_FJ = 2;
    const CHINA_TELECOMMUNICATIONS_AH = 3;
    const CHINA_TELECOMMUNICATIONS_HN = 4;
    const CHINA_TELECOMMUNICATIONS_HAIN = 5;
    const CHINA_TELECOMMUNICATIONS_TJ = 6;
    const CHINA_TELECOMMUNICATIONS_HLJ = 7;
    const CHINA_TELECOMMUNICATIONS_SX = 8;
    const CHINA_TELECOMMUNICATIONS_GS = 9;
    const CHINA_TELECOMMUNICATIONS_YN = 10;
    const CHINA_TELECOMMUNICATIONS_XJ = 11;
    const CHINA_TELECOMMUNICATIONS_SD = 12;
    const CHINA_TELECOMMUNICATIONS_QH = 13;
    const CHINA_TELECOMMUNICATIONS_HEN = 14;
    const CHINA_TELECOMMUNICATIONS_NMG = 15;
    const CHINA_TELECOMMUNICATIONS_ZJ = 16;
    const CHINA_TELECOMMUNICATIONS_JS = 17;
    const CHINA_TELECOMMUNICATIONS_SAX = 18;
    const CHINA_TELECOMMUNICATIONS_CQ = 19;
    const CHINA_TELECOMMUNICATIONS_GZ = 20;
    const CHINA_TELECOMMUNICATIONS_SH = 21;
    const CHINA_TELECOMMUNICATIONS_GD = 22;
    const CHINA_TELECOMMUNICATIONS_LN = 23;
    const CHINA_TELECOMMUNICATIONS_JX = 24;
    const CHINA_TELECOMMUNICATIONS_JL = 25;
    const CHINA_TELECOMMUNICATIONS_BJ = 26;
    const CHINA_TELECOMMUNICATIONS_XZ = 27;
    const CHINA_TELECOMMUNICATIONS_GX = 28;
    const CHINA_TELECOMMUNICATIONS_HB = 29;
    const CHINA_TELECOMMUNICATIONS_HUB = 30;
    const CHINA_TELECOMMUNICATIONS_NX = 31;

    public static $china_telecommunications_sub = [
        self::CHINA_TELECOMMUNICATIONS_SC=>'四川电信',
        self::CHINA_TELECOMMUNICATIONS_FJ=>'福建电信',
        self::CHINA_TELECOMMUNICATIONS_AH=>'安徽电信',
        self::CHINA_TELECOMMUNICATIONS_HN=>'湖南电信',
        self::CHINA_TELECOMMUNICATIONS_HAIN=>'海南电信',
        self::CHINA_TELECOMMUNICATIONS_TJ=>'天津电信',
        self::CHINA_TELECOMMUNICATIONS_HLJ=>'黑龙江电信',
        self::CHINA_TELECOMMUNICATIONS_SX=>'陕西电信',
        self::CHINA_TELECOMMUNICATIONS_GS=>'甘肃电信',
        self::CHINA_TELECOMMUNICATIONS_YN=>'云南电信',
        self::CHINA_TELECOMMUNICATIONS_XJ=>'新疆电信',
        self::CHINA_TELECOMMUNICATIONS_SD=>'山东电信',
        self::CHINA_TELECOMMUNICATIONS_QH=>'青海电信',
        self::CHINA_TELECOMMUNICATIONS_HEN=>'河南电信',
        self::CHINA_TELECOMMUNICATIONS_NMG=>'内蒙古电信',
        self::CHINA_TELECOMMUNICATIONS_ZJ=>'浙江电信',
        self::CHINA_TELECOMMUNICATIONS_JS=>'江苏电信',
        self::CHINA_TELECOMMUNICATIONS_SAX=>'山西电信',
        self::CHINA_TELECOMMUNICATIONS_CQ=>'重庆电信',
        self::CHINA_TELECOMMUNICATIONS_GZ=>'贵州电信',
        self::CHINA_TELECOMMUNICATIONS_SH=>'上海电信',
        self::CHINA_TELECOMMUNICATIONS_GD=>'广东电信',
        self::CHINA_TELECOMMUNICATIONS_LN=>'辽宁电信',
        self::CHINA_TELECOMMUNICATIONS_JX=>'江西电信',
        self::CHINA_TELECOMMUNICATIONS_JL=>'吉林电信',
        self::CHINA_TELECOMMUNICATIONS_BJ=>'北京电信',
        self::CHINA_TELECOMMUNICATIONS_XZ=>'西藏电信',
        self::CHINA_TELECOMMUNICATIONS_GX=>'广西电信',
        self::CHINA_TELECOMMUNICATIONS_HB=>'河北电信',
        self::CHINA_TELECOMMUNICATIONS_HUB=>'湖北电信',
        self::CHINA_TELECOMMUNICATIONS_NX=>'宁夏电信',
    ];

    public static  $china_telecommunications_sub_name = [
        '四川电信'=>self::CHINA_TELECOMMUNICATIONS_SC,
        '福建电信'=>self::CHINA_TELECOMMUNICATIONS_FJ,
        '安徽电信'=>self::CHINA_TELECOMMUNICATIONS_AH,
        '湖南电信'=>self::CHINA_TELECOMMUNICATIONS_HN,
        '海南电信'=>self::CHINA_TELECOMMUNICATIONS_HAIN,
        '天津电信'=>self::CHINA_TELECOMMUNICATIONS_TJ,
        '黑龙江电信'=>self::CHINA_TELECOMMUNICATIONS_HLJ,
        '陕西电信'=>self::CHINA_TELECOMMUNICATIONS_SX,
        '甘肃电信'=>self::CHINA_TELECOMMUNICATIONS_GS,
        '云南电信'=>self::CHINA_TELECOMMUNICATIONS_YN,
        '新疆电信'=>self::CHINA_TELECOMMUNICATIONS_XJ,
        '山东电信'=>self::CHINA_TELECOMMUNICATIONS_SD,
        '青海电信'=>self::CHINA_TELECOMMUNICATIONS_QH,
        '河南电信'=>self::CHINA_TELECOMMUNICATIONS_HEN,
        '内蒙古电信'=>self::CHINA_TELECOMMUNICATIONS_NMG,
        '浙江电信'=>self::CHINA_TELECOMMUNICATIONS_ZJ,
        '江苏电信'=>self::CHINA_TELECOMMUNICATIONS_JS,
        '山西电信'=>self::CHINA_TELECOMMUNICATIONS_SAX,
        '重庆电信'=>self::CHINA_TELECOMMUNICATIONS_CQ,
        '贵州电信'=>self::CHINA_TELECOMMUNICATIONS_GZ,
        '上海电信'=>self::CHINA_TELECOMMUNICATIONS_SH,
        '广东电信'=>self::CHINA_TELECOMMUNICATIONS_GD,
        '辽宁电信'=>self::CHINA_TELECOMMUNICATIONS_LN,
        '江西电信'=>self::CHINA_TELECOMMUNICATIONS_JX,
        '吉林电信'=>self::CHINA_TELECOMMUNICATIONS_JL,
        '北京电信'=>self::CHINA_TELECOMMUNICATIONS_BJ,
        '西藏电信'=>self::CHINA_TELECOMMUNICATIONS_XZ,
        '广西电信'=>self::CHINA_TELECOMMUNICATIONS_GX,
        '河北电信'=>self::CHINA_TELECOMMUNICATIONS_HB,
        '湖北电信'=>self::CHINA_TELECOMMUNICATIONS_HUB,
        '宁夏电信'=>self::CHINA_TELECOMMUNICATIONS_NX
    ];

    const CHINA_MOBILE_SC = 1;
    const CHINA_MOBILE_FJ = 2;
    const CHINA_MOBILE_AH = 3;
    const CHINA_MOBILE_HN = 4;
    const CHINA_MOBILE_HAIN = 5;
    const CHINA_MOBILE_TJ = 6;
    const CHINA_MOBILE_HLJ = 7;
    const CHINA_MOBILE_SX = 8;
    const CHINA_MOBILE_GS = 9;
    const CHINA_MOBILE_YN = 10;
    const CHINA_MOBILE_XJ = 11;
    const CHINA_MOBILE_SD = 12;
    const CHINA_MOBILE_QH = 13;
    const CHINA_MOBILE_HEN = 14;
    const CHINA_MOBILE_NMG = 15;
    const CHINA_MOBILE_ZJ = 16;
    const CHINA_MOBILE_JS = 17;
    const CHINA_MOBILE_SAX = 18;
    const CHINA_MOBILE_CQ = 19;
    const CHINA_MOBILE_GZ = 20;
    const CHINA_MOBILE_SH = 21;
    const CHINA_MOBILE_GD = 22;
    const CHINA_MOBILE_LN = 23;
    const CHINA_MOBILE_JX = 24;
    const CHINA_MOBILE_JL = 25;
    const CHINA_MOBILE_BJ = 26;
    const CHINA_MOBILE_XZ = 27;
    const CHINA_MOBILE_GX = 28;
    const CHINA_MOBILE_HB = 29;
    const CHINA_MOBILE_HUB = 30;
    const CHINA_MOBILE_NX = 31;

    public static $china_mobile_sub = [
        self::CHINA_MOBILE_SC=>'四川移动',
        self::CHINA_MOBILE_FJ=>'福建移动',
        self::CHINA_MOBILE_AH=>'安徽移动',
        self::CHINA_MOBILE_HN=>'湖南移动',
        self::CHINA_MOBILE_HAIN=>'海南移动',
        self::CHINA_MOBILE_TJ=>'天津移动',
        self::CHINA_MOBILE_HLJ=>'黑龙江移动',
        self::CHINA_MOBILE_SX=>'陕西移动',
        self::CHINA_MOBILE_GS=>'甘肃移动',
        self::CHINA_MOBILE_YN=>'云南移动',
        self::CHINA_MOBILE_XJ=>'新疆移动',
        self::CHINA_MOBILE_SD=>'山东移动',
        self::CHINA_MOBILE_QH=>'青海移动',
        self::CHINA_MOBILE_HEN=>'河南移动',
        self::CHINA_MOBILE_NMG=>'内蒙古移动',
        self::CHINA_MOBILE_ZJ=>'浙江移动',
        self::CHINA_MOBILE_JS=>'江苏移动',
        self::CHINA_MOBILE_SAX=>'山西移动',
        self::CHINA_MOBILE_CQ=>'重庆移动',
        self::CHINA_MOBILE_GZ=>'贵州移动',
        self::CHINA_MOBILE_SH=>'上海移动',
        self::CHINA_MOBILE_GD=>'广东移动',
        self::CHINA_MOBILE_LN=>'辽宁移动',
        self::CHINA_MOBILE_JX=>'江西移动',
        self::CHINA_MOBILE_JL=>'吉林移动',
        self::CHINA_MOBILE_BJ=>'北京移动',
        self::CHINA_MOBILE_XZ=>'西藏移动',
        self::CHINA_MOBILE_GX=>'广西移动',
        self::CHINA_MOBILE_HB=>'河北移动',
        self::CHINA_MOBILE_HUB=>'湖北移动',
        self::CHINA_MOBILE_NX=>'宁夏移动',
    ];

    public static  $china_mobile_sub_name = [
        '四川移动'=>self::CHINA_MOBILE_SC,
        '福建移动'=>self::CHINA_MOBILE_FJ,
        '安徽移动'=>self::CHINA_MOBILE_AH,
        '湖南移动'=>self::CHINA_MOBILE_HN,
        '海南移动'=>self::CHINA_MOBILE_HAIN,
        '天津移动'=>self::CHINA_MOBILE_TJ,
        '黑龙江移动'=>self::CHINA_MOBILE_HLJ,
        '陕西移动'=>self::CHINA_MOBILE_SX,
        '甘肃移动'=>self::CHINA_MOBILE_GS,
        '云南移动'=>self::CHINA_MOBILE_YN,
        '新疆移动'=>self::CHINA_MOBILE_XJ,
        '山东移动'=>self::CHINA_MOBILE_SD,
        '青海移动'=>self::CHINA_MOBILE_QH,
        '河南移动'=>self::CHINA_MOBILE_HEN,
        '内蒙古移动'=>self::CHINA_MOBILE_NMG,
        '浙江移动'=>self::CHINA_MOBILE_ZJ,
        '江苏移动'=>self::CHINA_MOBILE_JS,
        '山西移动'=>self::CHINA_MOBILE_SAX,
        '重庆移动'=>self::CHINA_MOBILE_CQ,
        '贵州移动'=>self::CHINA_MOBILE_GZ,
        '上海移动'=>self::CHINA_MOBILE_SH,
        '广东移动'=>self::CHINA_MOBILE_GD,
        '辽宁移动'=>self::CHINA_MOBILE_LN,
        '江西移动'=>self::CHINA_MOBILE_JX,
        '吉林移动'=>self::CHINA_MOBILE_JL,
        '北京移动'=>self::CHINA_MOBILE_BJ,
        '西藏移动'=>self::CHINA_MOBILE_XZ,
        '广西移动'=>self::CHINA_MOBILE_GX,
        '河北移动'=>self::CHINA_MOBILE_HB,
        '湖北移动'=>self::CHINA_MOBILE_HUB,
        '宁夏移动'=>self::CHINA_MOBILE_NX
    ];






}