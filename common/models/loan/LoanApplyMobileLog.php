<?php

namespace common\models\loan;

use Yii;

/**
 * This is the model class for table "{{%loan_collection}}".
 *
 * @property integer $id
 * @property integer $admin_user_id
 * @property string $username
 * @property string $phone
 * @property integer $group
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $operator_name
 * @property integer $status
 */
class LoanApplyMobileLog extends \yii\db\ActiveRecord
{
    public $real_name;
    /**
     * @inheritdoc
     */

    static $connect_name = "";

    public function __construct($name = "")
    {
        static::$connect_name = $name;
    }

    public static function tableName()
    {
        return '{{%loan_apply_mobile_log}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get( !empty(static::$connect_name) ? static::$connect_name : 'db_assist');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['loan_order_id', 'loan_admin_id', 'created_at', 'updated_at'], 'integer'],
            ['apply_reason', 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'loan_order_id' => Yii::t('app', '催收订单ID'),
            'loan_admin_id' => Yii::t('app', '申请人ID'),
            'apply_reason' => Yii::t('app', '申请原因'),
            'overdue_day' => Yii::t('app', '逾期天数'),
            'overdue_level'=>Yii::t('app','逾期限制级别'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    const LIMIT_LEVEL_ONE = 1;
    const LIMIT_LEVEL_TWO = 2;
    const LIMIT_LEVEL_THREE = 3;
    const LIMIT_LEVEL_FOUR = 4;
    public static $level_overdue_day = [
        self::LIMIT_LEVEL_ONE=>'1',
        self::LIMIT_LEVEL_TWO=>'2~3',
        self::LIMIT_LEVEL_THREE=>'4~5',
        self::LIMIT_LEVEL_FOUR=>'6+',
    ];
    public static function getLevel($overdue_day){
        if ((int)$overdue_day==1) {
            return self::LIMIT_LEVEL_ONE;
        }
        if ((int)$overdue_day<=3) {
            return self::LIMIT_LEVEL_TWO;
        }
        if ((int)$overdue_day<=5) {
            return self::LIMIT_LEVEL_THREE;
        }
        return self::LIMIT_LEVEL_FOUR;
    }
    //未申请前三个级别可以查看的联系人限制
    public static $un_apply_limit = [
        self::LIMIT_LEVEL_ONE=>1,
        self::LIMIT_LEVEL_TWO=>3,
        self::LIMIT_LEVEL_THREE=>9,
    ];

    //前三个级别可以查看的联系人限制
    public static $apply_limit = [
        self::LIMIT_LEVEL_ONE=>3,
        self::LIMIT_LEVEL_TWO=>9,
        self::LIMIT_LEVEL_THREE=>999,
        self::LIMIT_LEVEL_FOUR=>999,
    ];
    //查询是否申请 返回限制条数
    public static function getLimitCount($overdue_day,$loan_collection_order_id,$loan_collection_id,$sub_type=true){
        $overdue_level = self::getLevel($overdue_day);
        if ($overdue_level>=4) {
            return ['apply'=>true,'count'=>self::$apply_limit[self::LIMIT_LEVEL_FOUR]];
        }
        if($sub_type)
        {
            $apply = self::find()->where(['overdue_level'=>$overdue_level,'loan_order_id'=>$loan_collection_order_id,'loan_admin_id'=>$loan_collection_id])->one();

        }else{
            $apply = self::find()->where(['overdue_level'=>$overdue_level,'loan_order_id'=>$loan_collection_order_id,'loan_admin_id'=>$loan_collection_id])
                ->one(Yii::$app->get('db_mhk_assist'));
        }
        if (empty($apply)) {
            // $apply = self::find()->where(['loan_order_id'=>$loan_collection_order_id,'loan_admin_id'=>$loan_collection_id])->one();
            // if (!empty($apply)) {
            //     return self::$apply_limit[$overdue_level];
            // }
            return ['apply'=>false,'count'=>self::$un_apply_limit[$overdue_level]]; //没有申请过开放限制
        }
        return ['apply'=>true,'count'=>self::$apply_limit[$overdue_level]];
    }
    //每天申请次数限制
    public static $level_count = [
        self::LIMIT_LEVEL_ONE=>5,
        self::LIMIT_LEVEL_TWO=>5,
        self::LIMIT_LEVEL_THREE=>3,
    ];
    //月底每天申请次数限制
    public static $level_count_new = [
        self::LIMIT_LEVEL_ONE=>10,
        self::LIMIT_LEVEL_TWO=>10,
        self::LIMIT_LEVEL_THREE=>10,
    ];
    //查询当前催收人今天申请次数是否超过限制
    public static function getApplyCount($overdue_day,$loan_collection_id,$sub_type=true){
        $overdue_level = self::getLevel($overdue_day);
        if ($overdue_level >= self::LIMIT_LEVEL_FOUR) {
            return false;   //如果是级别4的单子就不需要申请了 直接返回超过
        }
        $condition = " overdue_level={$overdue_level} and loan_admin_id={$loan_collection_id} and created_at>=".strtotime('today');
        if($sub_type === false){
            $apply_count = self::find()->where($condition)->count('*',Yii::$app->get('db_mhk_assist'));
        }else{
            $apply_count = self::find()->where($condition)->count();
        }

        if( \common\api\RedisQueue::get(['key'=>'limit']) != 1 )
        {
            if ($apply_count>=self::$level_count_new[$overdue_level]) {
                return false;  //超过次数
            }
        }
        else
        {
            if ($apply_count>=self::$level_count[$overdue_level]) {
                return false;  //超过次数
            }
        }

        return ['has_count'=>self::$level_count_new[$overdue_level]-$apply_count];    //可再次申请
    }

    public static function list_where($condition){
        return self::find()->where($condition)->orderBy(['id'=>SORT_DESC])->all(self::getDb());
    }

    
    public static function one_by_orderId_admin($orderId){
        return self::find()->where(['loan_order_id'=>$orderId,'loan_admin_id'=>Yii::$app->user->id])->one(self::getDb());

    }

    public static function queryCondition($condition,$order=true,$orderBy=['id'=>SORT_DESC]){
        if ($order) {
            return self::find()->where($condition)->orderBy($orderBy);
        }
        return self::find()->where($condition);
    }
}
//alter table tb_loan_apply_mobile_log add column overdue_level int default 0 comment '逾期限制级别';
