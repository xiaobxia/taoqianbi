<?php

namespace common\models\info;

use Yii;
use yii\mongodb\ActiveRecord;

class AlipayInfo extends ActiveRecord
{

    public static function getDb(){
        return Yii::$app->get('mongodb_new');
    }

    public static function collectionName(){
        return 'alipay_info';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'antsLines', //花呗消费额度
            'wealth', //总资产
            'antsArrears', //总欠款
            'balance', //余额
            'balanceBao', //余额宝
            'fortuneBao', //招财宝
            'fund', //基金
            'depositBao', //存金宝
            'taobaoFinancial', //淘宝理财
            'bankCards', //银行卡信息
            'dealRecord', //3个月交易记录
            'realName', //实名
            'email', //邮箱
            'mobile', //手机
            'registerTime', //注册时间
            'taobaoName', //淘宝会员名
            'friendsContact', //近期好友联系人
            'tradeContact', //近期交易联系人
            'created_time', 
            'exception',
        ];
    }

}