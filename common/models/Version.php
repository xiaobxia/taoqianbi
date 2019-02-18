<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "{{%version}}".
 */
class Version extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'new_version', 'new_features','new_ios_version', 'ard_url', 'ard_size','versions'], 'required', 'message' => '不能为空'],
//            [['type', 'new_version', 'new_features','new_ios_version', 'ios_url', 'ard_url', 'ard_size','versions'], 'required', 'message' => '不能为空'],
            ['has_upgrade', 'default', 'value' => 0],
            ['is_force_upgrade', 'default', 'value' => 0],
        ];
    }

    const HAS_UPGRADE_SUCCESS = 1;//要提示升级
    const HAS_UPGRADE_FALSE = 0;//不要提示升级

    const FORCE_UPGRADE_SUCCESS = 1;//要强制升级
    const FORCE_UPGRADE_FALSE = 0;//不要强制升级

    //安卓提示更新的地址
    //http://qbres.wzdai.com/apk/" + obj + "-latest.apk
    const ARD_XYBT_UPDATE_URL = APP_DOWNLOAD_URL;
    //const ARD_HBQB_UPDATE_URL = 'http://qbres.wzdai.com/hbqb_apk/hbqb-latest.apk';
    const ARD_WZDAI_UPDATE_URL = 'http://qbres.wzdai.com/wzdai_apk/wzdai_loan-latest.apk';
    const ARD_XYBT_FUND_UPDATE_URL = 'http://qbres.wzdai.com/xybt_fund_apk/xybt_fund-latest.apk';
    public static $app_url = [
        self::ARD_XYBT_UPDATE_URL   => APP_NAMES,
        self::ARD_WZDAI_UPDATE_URL  => '温州贷',
        self::ARD_XYBT_FUND_UPDATE_URL => APP_NAMES.'福利版本',
    ];
    //IOS提示更新的地址
    const IOS_XYBT_UPDATE_URL = APP_IOS_DOWNLOAD_URL;//xybt TODO clark id1221186366
    const IOS_WZDAI_UPDATE_URL = 'http://itunes.apple.com/app/id1235438496?mt=8';//wzdai TODO clark id1221186366
    const IOS_HBQB_UPDATE_URL = 'http://itunes.apple.com/app/id1239756949';//hbqb
    const IOS_XYBT_FUND_UPDATE_URL = 'http://itunes.apple.com/app/id1248726833?mt=8';//极速荷包公积金版
    const IOS_XYBT_FULI_UPDATE_URL = 'http://itunes.apple.com/app/id1235438496?mt=8';//极速荷包福利版

    public static $url_android_arr = [
        LoanPerson::APPMARKET_XYBT =>self::ARD_XYBT_UPDATE_URL,
        LoanPerson::APPMARKET_WZD_LOAN =>self::ARD_WZDAI_UPDATE_URL,
        LoanPerson::APPMARKET_XYBTFUND =>self::ARD_XYBT_FUND_UPDATE_URL,
        LoanPerson::APPMARKET_XYBTFULI =>self::ARD_XYBT_FUND_UPDATE_URL,
    ];

    public static $url_ios_arr = [
        LoanPerson::APPMARKET_IOS_XYBT => self::IOS_XYBT_UPDATE_URL,
        LoanPerson::APPMARKET_IOS_WZD_LOAN => self::IOS_WZDAI_UPDATE_URL,
        LoanPerson::APPMARKET_IOS_HBQB =>  self::IOS_HBQB_UPDATE_URL,
        LoanPerson::APPMARKET_IOS_XYBTFUND => self::IOS_XYBT_FUND_UPDATE_URL,
        LoanPerson::APPMARKET_IOS_XYBTFULI => self::IOS_XYBT_FULI_UPDATE_URL,
    ];
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '类型',
            'has_upgrade' => 'has_upgrade',
            'is_force_upgrade' => 'is_force_upgrade',
            'new_version' => 'Android版本号',
            'new_ios_version' => 'IOS版本号',
            'new_features' => '新版本描述',
            'ios_url' => 'ios下载地址',
            'ard_url' => '现在地址',
            'ard_size' => '大小',
        ];
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%version}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

}
