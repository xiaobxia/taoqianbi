<?php
namespace common\models;


use console\models\NetUtil;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\db\Query;
use common\models\UserDetail;
use common\models\mongo\risk\RuleReportMongo;
use yii\base\UserException;

class UserOperateApplication extends  ActiveRecord 
{

   
    
    //客服人员操作
    const OPERATE_DEL_PERSON_LOGOUT=1;
    const OPERATE_PERSON_BIND_BANK =2;
    const OPERATE_UPDATE_PERSON_PHONE=3;
    const OPERATE_DEL_PERSON_PROOF =4;
    const OPERATE_REFRESH_JXL = 5;
    
    
    public static $type =[
        self::OPERATE_DEL_PERSON_LOGOUT =>'用户列表-注销/删除资料用户',
        self::OPERATE_PERSON_BIND_BANK  =>'用户列表-重新绑定银行卡',
        self::OPERATE_UPDATE_PERSON_PHONE=>'用户列表-新旧号码更改',
        self::OPERATE_DEL_PERSON_PROOF=>'用户列表-删除用户照片',
        self::OPERATE_REFRESH_JXL=>'用户列表-重置聚信力'
        
    ];
    
    // 单据审批状态
    const APPROVED_NO = 1;
    const APPROVED_YES= 2;
    const APPROVED_REFUSE=3;
    
    public static $status=[
        self::APPROVED_NO=>'未同意',
        self::APPROVED_YES=>'已同意',
        self::APPROVED_REFUSE=>'已拒绝'
    ];
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_operate_application}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'operate_id' => '单据ID',
            'type' => '执行操作',
            'name' => '借款人名称',
            'remark' => '操作内容',
            'status' => '审批状态',
            'created_id' => '申请人',
            'approved_id' => '审批人',
            'contact_phone' => '紧急联系人手机号',
            'created_at' => '申请时间',
            'updated_at' => '审批时间'
        ];
    }
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
    
}