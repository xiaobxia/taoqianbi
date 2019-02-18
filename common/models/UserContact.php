<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\base\UserException;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%user_detail}}".
 * 
 * @property string $name 联系人名称
 * @property string $relation 联系人
 * @property string $mobile 联系人手机 
 * @property string $relation_spare 备用联系人关系
 * @property string $name_spare 备用联系人姓名
 * @property string $mobile_spare 备用联系人手机
 */
class UserContact extends \yii\db\ActiveRecord
{
    //获取来源
	const SOURCE_NECESSARY = 1; //用户必填提交
    const SOURCE_SUPPLEMENT = 2;//用户补充提交
    const SOURCE_UPLOAD = 3;    //app自动上传

    public static $sources = [
        self::SOURCE_NECESSARY  => '必填提交',
        self::SOURCE_SUPPLEMENT => '补充提交',        
        self::SOURCE_UPLOAD     => '系统上传',        
    ];

    //关系
    const REALTION_PARENT = 1;
    const REALTION_SPOUSE = 2;    
    const REALTION_RELATIVE = 3;
    const REALTION_GUARDIAN = 4;
    const REALTION_CLASSMATE = 5;
    const REALTION_FRIEND = 6;
    const REALTION_COLLEAGUE = 7;
    const REALTION_KINSFOLK = 8;
    const REALTION_SON = 9;
    const REALTION_DAU = 10;
    const REALTION_OTHER = 100;
    #修正后的关系
    const RELATION_PARENT = 1;
    const RELATION_SPOUSE = 2;    
    const RELATION_RELATIVE = 3;
    const RELATION_GUARDIAN = 4;
    const RELATION_CLASSMATE = 5;
    const RELATION_FRIEND = 6;
    const RELATION_COLLEAGUE = 7;
    const RELATION_KINSFOLK = 8;
    const RELATION_SON = 9;
    const RELATION_DAU = 10;
    const RELATION_BRO = 11;
    const RELATION_SIS = 12;
    const RELATION_OTHER = 100;
    
    //百融规则
    public static $bairong_relation_types = [
        80001 => self::REALTION_PARENT,
        80002 => self::REALTION_RELATIVE,
        80003 => self::REALTION_SPOUSE,
        90001 => self::REALTION_FRIEND,
        90002 => self::REALTION_COLLEAGUE,
        90003 => self::RELATION_BRO,
        90004 => self::RELATION_SIS,
        90005 => self::REALTION_OTHER,
    ];

    public static $relation_types = [
        self::REALTION_PARENT    => '父亲',
        self::REALTION_SPOUSE    => '配偶',        
        self::REALTION_RELATIVE  => '母亲',
        self::REALTION_GUARDIAN  => '子女',
        self::REALTION_CLASSMATE => '同学',
        self::REALTION_FRIEND    => '朋友',
        self::REALTION_COLLEAGUE => '同事',
        self::REALTION_KINSFOLK =>'亲戚',
        self::REALTION_SON    => '儿子',
        self::REALTION_DAU    => '女儿',
        self::RELATION_BRO  => '兄弟',
        self::RELATION_SIS  => '姐妹',
        self::REALTION_OTHER =>'其他',
    ];

    public static $relation_one =[
        self::REALTION_PARENT    => '父亲',
        self::REALTION_RELATIVE  => '母亲',
        self::REALTION_SON    => '儿子',
        self::REALTION_DAU    => '女儿',
        self::RELATION_BRO  => '兄弟',
        self::RELATION_SIS  => '姐妹',
        self::REALTION_SPOUSE    => '配偶',
    ];

    public static $relation_two =[
        self::REALTION_CLASSMATE => '同学',
        self::REALTION_KINSFOLK =>'亲戚',
        self::REALTION_COLLEAGUE => '同事',
        self::REALTION_FRIEND    => '朋友',
        self::REALTION_OTHER =>'其他',

    ];


    //状态
    const STATUS_DEL = 0; //系统删除
    const STATUS_NORMAL = 1;//正常
    const STATUS_USER_DEL = 2;//用户删除
    const STATUS_INVALID = 3;//无效

    public static $status = [
        self::STATUS_DEL      => '系统删除',
        self::STATUS_NORMAL   => '正常',        
        self::STATUS_USER_DEL => '用户删除',
        self::STATUS_INVALID  => '无效',         
    ];

    public static $relation_types_jxl_map = [
        self::REALTION_PARENT    => '1',
        self::REALTION_SPOUSE    => '0',
        self::REALTION_RELATIVE  => '1',
        self::REALTION_GUARDIAN  => '3',
        self::REALTION_CLASSMATE => '5',
        self::REALTION_FRIEND    => '6',
        self::REALTION_COLLEAGUE => '4',
        self::REALTION_KINSFOLK =>'2',
        self::REALTION_SON    => '3',
        self::RELATION_BRO    => '2',
        self::RELATION_SIS    => '2',
        self::REALTION_DAU    => '3',
        self::REALTION_OTHER =>'6',
    ];

    public static $relation_types_yys_map = [
        self::REALTION_PARENT => 'PARENT',
        self::REALTION_GUARDIAN => 'CHILD',
        self::REALTION_CLASSMATE => 'CLASSMATE',
        self::REALTION_SPOUSE =>'COUPLE',
        self::REALTION_COLLEAGUE => 'COLLEAGUE',
        self::REALTION_FRIEND    => 'FRIEND',
        self::RELATION_SIS =>'SIBBING',
        self::RELATION_BRO =>'SIBBING',
    ];
    // 手机验证正则表达式
    const PHONE_PATTERN = '/^1[0-9]{10}$/';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_contact}}';
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
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_NORMAL],
            ['user_id', 'required', 'message' => '请输入用户信息'],           
            ['mobile', 'required', 'message' => '请输入手机号'],
            ['source', 'required', 'message' => '来源不能为空'],
            [['name', 'relation'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'uid' => '用户ID',
            'id_number' => '借款人编号',
            'type' => '借款人类型',
            'name' => '借款人名称',
            'phone' => '联系方式',
            'birthday' => '借款人出生日期',
            'property' => '借款人性质',
            'contact_username' => '紧急联系人',
            'contact_phone' => '紧急联系人手机号',
            'attachment' => '上传的材料',
            'credit_limit' => '授信额度',
            'open_id' => '芝麻信用ID',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'source_id' => '借款人来源：0默认口袋快借，1笨鸟',
            'auth_key' => '',
        ];
    }
    
    /**
     * 通过名称来获取 int型的关系
     * @param string $name 关系名称
     * @return integer
     */
    public static function getRelationByName($name) {
        $relation_key = array_search(trim($name), static::$relation_types);
        if(!$relation_key) {
            $relation_key = static::RELATION_OTHER;
        }
        return $relation_key;
    }
    
    /**
     * 添加数据
     * @param integer $user_id 用户ID
     * @param integer $contact_name 联系人名称
     * @param integer $contact_phone 联系人手机
     * @param integer $relation 关系
     * @param string $contact_name_spare 次要联系人名称
     * @param string $contact_phone_spare 次要联系人电话、
     * @param string $relation_spare 次要联系人关系
     * return static
     */

    public static function add($user_id, $contact_name, $contact_phone, $relation, $contact_name_spare=null, $contact_phone_spare=null, $relation_spare=null) {
        $model = static::find()->where([
            'user_id'=>$user_id,
            'mobile' => $contact_phone
        ])->one();
        if(!$model) {
            $model = new static;
        }
        $model->user_id = (int)$user_id;
        $model->name = $contact_name;
        $model->mobile = $contact_phone;
        $model->relation = $relation;
        $model->name_spare = $contact_name_spare ? $contact_name_spare : '';
        $model->mobile_spare = $contact_phone_spare ? $contact_phone_spare : '';
        $model->relation_spare = $relation_spare ? $relation_spare : '';
        $model->save(false);
        return $model;
    }
    

}