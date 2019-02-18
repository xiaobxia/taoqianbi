<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class AccumulationFund extends ActiveRecord {

    const CHANNEL_JXL = 'JXL';

    const STATUS_INIT = 0; //等待获取token
    const STATUS_GET_TOKEN = 1; //等待获取详情
    const STATUS_SUCCESS = 10; //成功获取详情
    const STATUS_FAILED = -1; //获取详情失败

    public static $status = [
        self::STATUS_INIT => "待获取token",
        self::STATUS_GET_TOKEN => "待获取详情",
        self::STATUS_SUCCESS => "请求成功",
        self::STATUS_FAILED => "获取失败",
    ];

    public static function tableName() {
        return 'tb_accumulation_fund';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb() {
        return \yii::$app->db_kdkj;
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    public function attributeLabels() {
        return [
            'id' => 'ID',
            'channel' => '渠道',
            'token' => '令牌',
            'user_id' => '用户ID',
            'city' => '城市',
            'status' => '状态',
            'params' => '请求参数',
            'data' => '详情',
            'message' => '信息',

            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 获取用户信息
     * @return \yii\db\ActiveQuery
     */
    public function getLoanPerson()
    {
        return $this->hasOne(LoanPerson::className(), array('id' => 'user_id'));
    }

    /**
     * 获取用户最新的公积金数据
     * @param $condition
     * @return array|null|ActiveRecord
     */
    public static function findLatestOne($condition)
    {
        return self::find()->where($condition)->orderBy('id desc')->limit(1)->one();
    }

    /**
     * 验证用户公积金状态是否有效
     * @param LoanPerson $loanPerson
     * @return bool
     */
    public static function validateAccumulationStatus(LoanPerson $loanPerson)
    {
        if ($accumulation = self::findLatestOne([
            'user_id' => $loanPerson->id,
            'status' => self::STATUS_SUCCESS,
        ])) {
            if ($accumulation->pay_months >= 9 && $accumulation->average_amt >= 5000) {
                $data = \json_decode($accumulation->data, true);
                if ($data && self::gjjInfoMatch($data, $loanPerson)
                    && isset($data['housing_fund_status'])
                    && ($data['housing_fund_status'] == '正常' || $data['housing_fund_status'] == '缴存')) {

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 公司名称过滤，减少匹配误差
     * @param $str
     * @return mixed
     */
    public static function replaceString($str)
    {
        $str = str_replace('有限责任公司', '', $str);
        $str = str_replace('有限公司', '', $str);
        $str = str_replace('集团公司', '', $str);
        $str = str_replace('集团', '', $str);
        $str = str_replace('分公司', '', $str);
        $str = str_replace('公司', '', $str);
        $str = str_replace('（', '', $str);
        $str = str_replace('）', '', $str);

        return $str;
    }

    /**
     * 公积金信息匹配
     * @param $report
     * @param LoanPerson $loan_person
     * @return bool
     */
    public static function gjjInfoMatch($report, LoanPerson $loan_person)
    {
        $gjj_name = $report['real_name'] ?? '';
        $gjj_id_card =  $report['id_card'] ?? '';

        $gjj_name = \str_replace('＊', '*', trim($gjj_name));

        $name_match_rate = self::nameMatch($gjj_name, $loan_person->name);
        if ($name_match_rate == 1) {
            return true;
        }
        if ($name_match_rate >= 0.5 && (mb_stripos($gjj_name, '*') !== false || mb_stripos($gjj_name, ' ') !== false)) {
            return true;
        }

        $id_card_match_rate = self::idCardMatch($gjj_id_card, $loan_person->id_number);
        if ($id_card_match_rate == 1) {
            return true;
        }
        if ($name_match_rate >= 0.3 && (stripos($gjj_id_card, '*') !== false && $id_card_match_rate > 0.6)) {
            return true;
        }

        return false;
    }

    /**
     * 公积金姓名匹配度
     * @param $gjj_name
     * @param $real_name
     * @return float
     */
    public static function nameMatch($gjj_name, $real_name)
    {
        if (empty($gjj_name) || empty($real_name)) {
            return 0;
        }

        $match_count = 0;
        for ($i = 0; $i < mb_strlen($real_name); $i++) {
            if (mb_substr($real_name, $i, 1) == mb_substr($gjj_name, $i, 1)) {
                $match_count++;
            }
        }
        return $match_count / mb_strlen($real_name);
    }

    /**
     * 公积金身份证匹配度
     * @param $gjj_id_card
     * @param $user_id_card
     * @return float|int
     */
    public static function idCardMatch($gjj_id_card, $user_id_card)
    {
        if (empty($gjj_id_card) || empty($user_id_card)) {
            return 0;
        }
        $gjj_id_card = trim($gjj_id_card);

        $match_count = 0;
        for ($i = 0; $i < strlen($user_id_card); $i++) {
            if (substr($user_id_card, $i, 1) == substr($gjj_id_card, $i, 1)) {
                $match_count++;
            }
        }

        return $match_count / strlen($user_id_card);
    }

}
