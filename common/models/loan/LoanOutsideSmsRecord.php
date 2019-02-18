<?php

namespace common\models\loan;

use Yii;
use common\api\RedisQueue;
use common\models\loan\LoanCollectionOption;
/**
 * This is the model class for table "{{%loan_outside_sms_record}}".
 * 各个机构发送短信数量统计
 */
class LoanOutsideSmsRecord extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_outside_sms_record}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['outside', 'send_message_num', 'created_at', 'updated_at','send_channel'], 'integer'],
            [['username'],'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'outside' => Yii::t('app', '催收机构'),
            'admin_user_id'=>Yii::t('app','催收人ID'),
            'username'=> Yii::t('app', '催收人姓名'),
            'send_person_num'=>Yii::t('app','发送人次'),
            'send_message_num' => Yii::t('app', '当月发送的短信数量'),
            'send_channel' => Yii::t('app','发送通道'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
    /**
     * 记录催收机构的短信发送数量
     * @param  [type] $operator       [当前操作人]
     * @param  [type] $content        [短信内容]
     * @param  [type] $send_phone_num [发送的手机号数量]
     * @return [type]                 [description]
     */
    public static function send_num_record($operator,$content,$send_phone_num,$overdue_day=1){
        //发送通道可以缓存
        $key = "LoanCollectionSmsRecord";
        $send_channel = RedisQueue::get(['key'=>$key]);
        if (empty($send_channel)) {
            $way = LoanCollectionOption::getMsgWayInfo();
            $send_channel = LoanCollectionOption::getMsgWayNum($way);
            //设置缓存
            RedisQueue::set(['key'=>$key,'value'=>$send_channel,'expire'=>7200]);
        }
        $thisDayTime = strtotime('today');
        $sms_length = mb_strlen($content);
        if ($operator === '系统') {
            $outside = 99999;
            $user_id = $overdue_day;
            $username = 'admin';
        }else{
            $outside = $operator->outside;
            $user_id = $operator->admin_user_id;
            $username = $operator->username;
        }
        $condition = " outside={$outside} and admin_user_id={$user_id} and created_at>={$thisDayTime} and send_channel={$send_channel}";
        $outside_record = self::find()->where($condition)->limit(1)->one();
        if (!$outside_record) {
            $outside_record = new self;
            $outside_record->created_at = time();
            $outside_record->outside = $outside;
            $outside_record->admin_user_id = $user_id;
            $outside_record->username = $username;
        }
        if ($sms_length <= 70) {
            $wai_sms_num = $send_phone_num;
        }else{
            $once_num = ceil($sms_length/67);
            $wai_sms_num = $send_phone_num*$once_num;
        }
        $outside_record->send_person_num += $send_phone_num;
        $outside_record->send_message_num += $wai_sms_num;
        $outside_record->send_channel = $send_channel;
        $outside_record->updated_at = time();
        $outside_record->save();
    }
}
