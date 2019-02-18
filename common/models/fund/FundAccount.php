<?php

namespace common\models\fund;

use Yii;
use common\models\UserLoanOrder;
use common\helpers\Util;

/**
 * 资金账号
 * This is the model class for table "{{%fund_account}}".
 *
 * @property string $id
 * @property string $name 名称
 * @property string $account 账户
 * @property integer $status 状态
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class FundAccount extends \yii\db\ActiveRecord {

    const STATUS_ENABLE = 1;//启用
    const STATUS_DISABLE = 0;//弃用

    const TYPE_PAY            = 1;// 放款
    const TYPE_REPAY    = 2;// 还款

    /**
     * 账户类型
     * @var array
     * @author czd
     */
    const ACCOUNT_TYPE = [
        self::TYPE_REPAY    =>  '还款',
        self::TYPE_PAY            =>  '放款'
    ];

    const STATUS_LIST = [
        self::STATUS_DISABLE=>'禁用',
        self::STATUS_ENABLE=>'启用',
    ];

    const ID_PAY_ACCOUNT_WUYI = YII_ENV==='prod' ? 1 : 8;//51放款主体ID
    const ID_REPAY_ACCOUNT_WUYI =  YII_ENV==='prod' ? 2 : 9;//51还款主体ID
    const ID_PAY_ACCOUNT_QIANCHENG = YII_ENV==='prod' ? 3 : 4;//凌融放款主体ID
    const ID_REPAY_ACCOUNT_QIANCHENG = YII_ENV==='prod' ? 4 :  5;//凌融还款主体ID
    const ID_PAY_ACCOUNT_KOUDAI = YII_ENV==='prod' ? 2 :  6;//口袋放款主体ID
    const ID_REPAY_ACCOUNT_KOUDAI = YII_ENV==='prod' ? 6 : 7;//口袋还款主体ID
    const ID_PAY_ACCOUNT_FEISHANG = 7;//婓尚放款主体ID
    const ID_REPAY_ACCOUNT_FEISHANG = 8;//婓尚还款主体ID

    const ID_REPAY_ACCOUNT_DEFAULT = self::ID_REPAY_ACCOUNT_KOUDAI;//默认还款主体

    public static $ID_REPAY_ACCOUNTS = [//所有还款主体
        self::ID_PAY_ACCOUNT_WUYI=>'51放款主体',
        self::ID_REPAY_ACCOUNT_WUYI=>'51还款主体',
        self::ID_REPAY_ACCOUNT_QIANCHENG=>'凌融放款主体ID',
        self::ID_REPAY_ACCOUNT_QIANCHENG=>'凌融还款主体ID',
        self::ID_PAY_ACCOUNT_KOUDAI=>'口袋放款主体',
        self::ID_REPAY_ACCOUNT_KOUDAI=>'口袋还款主体',
        self::ID_PAY_ACCOUNT_FEISHANG=>'婓尚放款主体',
        self::ID_REPAY_ACCOUNT_FEISHANG=>'婓尚还款主体',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fund_account}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }

    public function behaviors() {
        return [
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account'],'default','value'=>''],
            [['name', 'account_type'], 'required'],
            [['account_type', 'status'], 'integer'],
            [['account'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 32],
            [['name','account_type'], 'unique','targetAttribute' => ['name','account_type']]
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account' => '账号',
            'name' => '名称',
            'account_type' => '账户类型',
         //   'lending_quotas_type' =>'放款额度类型',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 获取选择状态
     * @return []
     */
    public static function getSelectOptions($type) {
        $rows = static::find()
            ->select('`id`,`name`')
            ->where(['status' => self::STATUS_ENABLE, 'account_type' => $type])
            ->asArray(true)->all();
        $options = [];
        foreach($rows as $row) {
            $options[$row['id']] = $row['name'];
        }
        return $options;
    }

    /**
     * 通过账号ID获取所有资方ID
     * @param integer $account_id 账号ID
     * @param integer $type 账号类型 pay 或 repay
     * @return []
     */
    public static function getAllFundIds($account_id, $type) {
        $funds = static::getAllFunds($account_id, $type);
        $fund_ids = [];
        foreach($funds as $fund) {
            $fund_ids[] = $fund->id;
        }
        return $fund_ids;
    }

    /**
     * 通过账号ID获取所有资方模型
     * @param integer $account_id 账号ID
     * @param integer $type 账号类型 pay 或 repay
     * @return LoanFund[]
     */
    public static function getAllFunds($account_id, $type) {
        if($type==='pay') {
            $condition = 'pay_account_id='.(int)$account_id;
        } else if($type==='repay') {
            $condition = 'repay_account_id='.(int)$account_id;
        } else {
            throw new \Exception('无效账号类型');
        }
        return LoanFund::find()->where($condition)->all();
    }

    /**
     * [getAccountId description]
     * @Author   ZhangDaomin
     * @DateTime 2017-02-07T17:12:39+0800
     * @param    [type]                   $fund_id [description]
     * @return   [type]                            [description]
     */
    public static function getAccountId($fund_id, $type='pay') {
        $key = $type.'_account_id';
        $result = static::getOneFund($fund_id, $type);
        return $result[$key];
    }

    /**
     * [getOneFund description]
     * @Author   ZhangDaomin
     * @DateTime 2017-02-07T17:24:12+0800
     * @param    [type]                   $fund_id [description]
     * @return   [type]                            [description]
     */
    public static function getOneFund($fund_id)
    {
        return LoanFund::findOne($fund_id);
    }
}
