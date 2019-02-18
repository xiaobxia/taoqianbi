<?php
/**
 * Created by PhpStorm.
 * User: byl
 * Date: 2017/3/3
 * Time: 19:01
 */
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class CreditBr extends ActiveRecord
{
    const SPECIAL_LIST = 1;
    const APPLY_LOAN_STR = 2;
    const REGISTER_EQUIPMENT = 3;
    const SIGN_EQUIPMENT = 4;
    const LOAN_EQUIPMENT = 5;
    const EQUIPMENT_CHECK = 6;
    public static $product_list = [
        self::SPECIAL_LIST => '特殊名单核查',
        self::APPLY_LOAN_STR => '多次申请核查V2',
        self::REGISTER_EQUIPMENT => '注册设备信息',
        self::SIGN_EQUIPMENT => '登录设备信息',
        self::LOAN_EQUIPMENT => '借款设备信息',
        self::EQUIPMENT_CHECK => '设备信息核查'
    ];

    public static $resultCode = [
        '100001' => '程序错误',
        '100002' => '匹配结果为空',
        '100003'=>'必选key值缺失或不合法',
        '100004' => '商户不存在',
        '100005' => '登录密码错误',
        '100006' => '请求参数格式错误',
        '100007' => 'Tokenid过期',
        '100008' => '客户端api调用码不能为空',
        '100009' => 'ip地址错误',
        '100010' => '超出当天访问次数',
        '100011' => '账户停用',
        '1000015' => '请求参数其他错误',
        '1000016' => '捕获请求json异常，无法解析的错误'
    ];

    public static function tableName()
    {
        return '{{%credit_br}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj_risk');
    }

    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
     * @inheritdoc
     */
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className(),
        ];
    }

    public function rules(){
        return [
            [[ 'id','person_id', 'id_number','data','type','created_at','updated_at'], 'safe'],
        ];
    }

    public static function findLatestOne($params,$dbName = null)
    {
        if(is_null($dbName))
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditMg = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditMg;
    }

}