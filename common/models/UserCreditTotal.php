<?php

namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class UserCreditTotal extends BaseUserCreditTotalChannel {

    const AMOUNT = 100000;

    public static function getDb() {
        return \yii::$app->get('db_kdkj');
    }

    public static function tableName() {
        return 'tb_user_credit_total';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [[ 'card_no'], 'unique'],
        ];
    }
    /**
     * 初始化额度记录
     * @param int $user_id
     */
    public static function initUserCreditTotal($user_id) {
        $now_ts = time();
        $user_credit_total = new UserCreditTotal();
        $user_credit_total->card_type = UserCreditTotal::CARD_TYPE_ONE;
        $user_credit_total->card_title = UserCreditTotal::$normal_card_info[UserCreditTotal::CARD_TYPE_ONE]['card_title'];
        $user_credit_total->card_subtitle = UserCreditTotal::$normal_card_info[UserCreditTotal::CARD_TYPE_ONE]['card_subtitle'];
        $user_credit_total->card_no = \common\helpers\IdGeneratorHelper::genertaor_card_no();
        $user_credit_total->user_id = $user_id;
        $user_credit_total->amount = UserCreditTotal::$normal_card_info[UserCreditTotal::CARD_TYPE_ONE]['card_amount'];
        $user_credit_total->used_amount =0 ;
        $user_credit_total->locked_amount = 0;
        $user_credit_total->updated_at = $now_ts;
        $user_credit_total->created_at = $now_ts;
        $user_credit_total->operator_name = $user_id;
        $user_credit_total->pocket_apr = UserCreditTotal::$normal_card_info[UserCreditTotal::CARD_TYPE_ONE]['card_apr'];
        $user_credit_total->house_apr = UserCreditTotal::HOUSE_APR;
        $user_credit_total->installment_apr = UserCreditTotal::INSTALLMENT_APR;
        $user_credit_total->pocket_late_apr = UserCreditTotal::$normal_card_info[UserCreditTotal::CARD_TYPE_ONE]['card_late_apr'];
        $user_credit_total->house_late_apr = UserCreditTotal::HOUSE_LATE_APR;
        $user_credit_total->installment_late_apr = UserCreditTotal::INSTALLMENT_LATE_APR;
        $user_credit_total->pocket_min = UserCreditTotal::POCKET_MIN;
        $user_credit_total->pocket_max = UserCreditTotal::POCKET_MAX;
        $user_credit_total->house_min = UserCreditTotal::HOUSE_MIN;
        $user_credit_total->house_max = UserCreditTotal::HOUSE_MAX;
        $user_credit_total->installment_min = UserCreditTotal::INSTALLMENT_MIN;
        $user_credit_total->installment_max = UserCreditTotal::INSTALLMENT_MAX;
        return $user_credit_total->save();
    }

    /**
     * 新增用户额度方法
     * @param unknown $mount
     * @param unknown $user_id
     * @param number $type
     * @param array $params
     * @return boolean|unknown
     */
    public static function addCreditAmount($mount, $user_id, $type=0, $params=[]){
        if (!$type) {
            $type = UserCreditLog::TRADE_TYPE_LQD_ADMIN;
        }
        $credit_total = self::find()->where(['user_id'=>$user_id])->limit(1)->one();
        if (!$credit_total) {
            return false;
        }

        $ret = self::getDb()->createCommand('update '.self::tableName().'
            set amount=amount+:amount,
                repayment_credit_add=repayment_credit_add+:amount,
                increase_time=:now,
                updated_at=:now
          where id=:id ', [
              ':now' => time(),
              ':amount'=>$mount,
              ':id' => $credit_total->id
          ])->execute();
        if ($ret) {
            $user_credit_log = new UserCreditLog();
            $user_credit_log->user_id = $user_id;
            $user_credit_log->type = $type;
            $user_credit_log->type_second = UserCreditLog::TRADE_TYPE_SECOND_NORMAL;
            $user_credit_log->operate_money = $mount;
            $user_credit_log->remark = isset($params['remark']) ? $params['remark'] : "";
            $user_credit_log->created_at = time();
            $user_credit_log->total_money=$credit_total->amount;
            $user_credit_log->used_money=$credit_total->used_amount;
            $user_credit_log->unabled_money=$credit_total->locked_amount;
            $log_ret = $user_credit_log->save();
            if (!$log_ret) {
                \yii::error( \sprintf('[%s][%s] failed %s', __CLASS__, __FUNCTION__, $user_id) );
            }
        }

        return $ret;
    }
}
