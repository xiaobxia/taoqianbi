<?php
/**
 * Created by PhpStorm.
 * User: guofan
 * Date: 2019/2/2
 * Time: 11:51
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class CreditLzf extends ActiveRecord
{

    public static $resultCode = [
        '9031001' => '系统异常',
        '2031001' => '服务异常',
        '2031002'=>'消息队列服务异常',
        '2031103' => '参数xxx不正确',
        '2031204' => '该商户信息不存在',
        '2031205' => '商户姓名与商户ID不匹配，您无权获取该商户的密钥信息',
        '2031206' => '用户三要素信息保存失败',
        '2031207' => '请求信息保存失败，请稍后重试。',
        '2031208' => '认证失败, 请检查参数是否正确',
        '2031209' => 'ip地址参数非法',
        '2031210' => '目前还没有生成相关报告，请稍后查询！',
        '2031211' => '未查询到该请求信息，请确认id或者requestCustomerId参数是否正确',
        '2031212' => '该请求的时间戳非法或已失效',
        '2031213' => 'id或者customerRequestId必须传一个',
        '2031214' => '发送消息到消息队列失败',
        '2031215' => '该节点不存在或者未开启',
        '2031216' => '该请求的时间戳非法或已失效',
        '2031217' => '该商户已停用',
        '2031218' => '该商户已注销',
        '2031219' => '该产品已停用',
        '2031220' => '该产品已注销',
    ];

    public static function tableName()
    {
        return '{{%credit_lzf}}';
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
            [[ 'id','person_id', 'id_number','data','score','type','created_at','updated_at'], 'safe'],
        ];
    }
}