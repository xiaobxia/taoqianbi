<?php

namespace common\models\info;

use Yii;
use yii\mongodb\ActiveRecord;

class TaobaoInfo extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_info_capture');
    }

    public static function collectionName(){
        return 'taobao_info';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'taobaoName', //淘宝会员名
            'loginEmail', //登录邮箱
            'bindingMobile', //淘宝绑定手机
            'growth', //个人成长值
            'alipayEmail', //支付宝绑定邮箱
            'alipayMobile', //支付宝绑定手机
            'accountType', //支付宝账户类型
            'realName', //支付宝实名认证
            'taobaoAddress', //淘宝收货地址
            'dealRecord', //淘宝交易记录
            'creditPoint', //淘宝信誉评分
            'goodRate', //最近1周-最近1个月-最近6个月-6个月前-总计 好评
            'middleRate', //最近1周-最近1个月-最近6个月-6个月前-总计 中评
            'badRate', //最近1周-最近1个月-最近6个月-6个月前-总计 差评
            'tianMaoPoint', //天猫积分
            'tianMaoCreditLevel', //天猫信誉评级
            'tianMaoLevel', //天猫等级
            'tianMaoExperience', //天猫经验值
            'created_time',
            'exception',
        ];
    }



}