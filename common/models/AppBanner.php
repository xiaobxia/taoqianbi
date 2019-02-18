<?php

namespace common\models;

use Yii;

/**
 * AppBanner
 * App Banner
 * ----------
 * @author Verident。
 */
class AppBanner extends BaseActiveRecord
{
    const JUMP_APP = 13;  //app内部跳转
    const JUMP_URL = 2103; //url链接跳转

    //banner的类型
    const BANNER_TYPE_NORMAL = 0;
    const BANNER_TYPE_URL = 1;
    const BANNER_TYPE_SKIP = 2;

    public static $type_name = [
        self::BANNER_TYPE_NORMAL => '普通',
        self::BANNER_TYPE_URL => '外部链接',
        self::BANNER_TYPE_SKIP => 'App内跳转',
    ];

    const BANNER_TYPE_FLOAT = 1;
    const BANNER_TYPE_LAND = 0;

    public static $type_float = [
        self::BANNER_TYPE_FLOAT => '是',
        self::BANNER_TYPE_LAND => '否'
    ];

    //banner的状态
    const BANNER_STATUS_NO_USE = 0;
    const BANNER_STATUS_USE = 1;
    const BANNER_STATUS_DEL = 2;

    public static $status_name = [
        self::BANNER_STATUS_NO_USE => '未启用',
        self::BANNER_STATUS_USE => '启用',
        self::BANNER_STATUS_DEL => '已删除',
    ];

    //启用App的版本
    const BANNER_START_APP_ALL = 0;
    const BANNER_START_APP_IOS = 1;
    const BANNER_START_APP_ANDROID = 2;

    public static $status_app_type = [
        self::BANNER_START_APP_ALL => '全部启用',
        self::BANNER_START_APP_IOS => 'IOS启用',
        self::BANNER_START_APP_ANDROID => 'Android启用',
    ];

    //是否需要登录
    const BANNER_NEED_LOGIN = 1;
    const BANNER_NO_NEED_LOGIN = 0;

    public static $banner_login_name = [
        self::BANNER_NEED_LOGIN => '需要登录',
        self::BANNER_NO_NEED_LOGIN => '不需要登录',
    ];

    //是否需要做唤醒APP 发送短信
    const BANNER_NEED_WALK_APP = 1;
    const BANNER_NO_NEED_WALK_APP = 0;

    public static $need_walk_app = [
        self::BANNER_NEED_WALK_APP => '需要做短信唤醒',
        self::BANNER_NO_NEED_WALK_APP => '不需要做短信唤醒',
    ];


    //App跳转类型
    const APP_TYPE_REPAYMENT = 1;



    //APP调用原生的方法
    const Return_js_0 = 0;
    const Return_js_1 = 1;//忘记登录密码
    const Return_js_2 = 2;//忘记交易
    const Return_js_3 = 3;//认证中心
    const Return_js_4 = 4;//借款界面
    const Return_js_5 = 5;//老版本跳转QQ客服
    const Return_js_6 = 6;//红包信息
    const Return_js_7 = 7;//返回首页"我的"页面（有用户信息时,保存用户信息）
    const Return_js_8 = 8;//借款记录
    const Return_js_9 = 9;//上传还款凭证
    const Return_js_10 = 10;//用户系统浏览器打开
    const Return_js_11 = 11;//催收投诉
    const Return_js_12 = 12;//新版跳转QQ客服
    const Return_js_13 = 13;//还款界面

    public static $return_jump = [
        self::Return_js_0 => '返回原生页面',
        self::Return_js_1 => '忘记登录密码',
        self::Return_js_2 => '忘记交易密码',
        self::Return_js_3 => '认证中心',
        self::Return_js_4 => '借款界面',
        self::Return_js_5 => '跳转QQ客服(旧版)',
        self::Return_js_6 => '红包信息',
        self::Return_js_7 => '返回首页"我的"页面',
        self::Return_js_8 => '借款记录',
        self::Return_js_9 => '上传还款凭证',
        self::Return_js_10 => '用户系统浏览器打开',
        self::Return_js_11 => '催收投诉',
        self::Return_js_12 => '跳转QQ客服(新版)',
        self::Return_js_13 => '还款界面',
    ];

    public static $return_jump_arr = [
        self::Return_js_0,
        self::Return_js_1,
        self::Return_js_2,
        self::Return_js_3,
        self::Return_js_4,
        self::Return_js_5,
        self::Return_js_6,
        self::Return_js_7,
        self::Return_js_8,
        self::Return_js_9,
        self::Return_js_10,
        self::Return_js_11,
        self::Return_js_12,
        self::Return_js_13,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%app_banner}}';
    }

    public static function getDb(){
        return Yii::$app->get('db_kdkj');
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['image_url', 'type','status', 'source_id'], 'required', 'message' => '不能为空'],
            [['image_url','link_url'], 'string', 'max' => 200, 'message' => '链接不能超过200个字符'],
            [['sub_type', 'name', 'app_type', 'source_id', 'loan_search_public_list_id','is_float'],'safe'],
        ];
    }

    /**
     * 返回对应的banner列表
     * @param $source
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function bannerList($source){
        $bannerQuery = self::find()
            ->select(['id','type','sub_type','link_url','image_url','source_id', 'app_type', 'loan_search_public_list_id','is_float'])
            ->where([
                'status' => self::BANNER_STATUS_USE,
                'source_id' => $source
            ])
            ->orderBy('sub_type ASC');
        return $bannerQuery->asArray()->all();
    }
}
