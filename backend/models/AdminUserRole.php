<?php
namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * AdminUserRole model
 */
class AdminUserRole extends \yii\db\ActiveRecord
{
     // 角色分组类型
    const TYPE_SERVICE = 1;//客服
    const TYPE_OPERATE = 2;//运营
    const TYPE_PRODUCT = 3;//产品
    const TYPE_FINANCE = 4;//财务
    const TYPE_DEVELOP = 5;//开发
    const TYPE_TEST = 6;//测试
    const TYPE_OPERATION = 7;//职能
    const TYPE_PROPERTY = 8;//风控
    const TYPE_COLLECTION = 9;//催收
    const TYPE_HOUSE = 10;//房产业务
    const TYPE_COOP = 11;//合作资产业务
    const TYPE_CHANNEL=12;//渠道分销
    const TYPE_CHANNEL_FUND=13;//资方管理
    const TYPE_COLLECTION_FINANCE=14;//催收-财务入账

    public static $status = [
        self::TYPE_SERVICE    => '客服组',
        self::TYPE_OPERATE    => '运营组',
        self::TYPE_PRODUCT    => '产品组',                   
        self::TYPE_FINANCE    => '财务组',
        self::TYPE_DEVELOP    => '开发组',    
        self::TYPE_TEST       => '测试组',    
        self::TYPE_OPERATION  => '业务运维组',
        self::TYPE_CHANNEL_FUND  => '业务资方管理',
        self::TYPE_PROPERTY   => '风控组',
        self::TYPE_COLLECTION => '催收组',
        self::TYPE_HOUSE   => '房产组',
        self::TYPE_COOP => '合作资产组',
        self::TYPE_CHANNEL=>'渠道分销组',
        self::TYPE_COLLECTION_FINANCE=>'催收入账组',
    ];

    private static $extra_condition = ['open_status' => 1];
    /**
     *根据角色分组返回角色信息
     *结果数组以角色标识为下标
     */
    public static function groups($groupId){
        $res = self::find()->where(['groups'=>$groupId])->all(Yii::$app->get('db_kdkj_rd'));
        $result = array();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['name']] = $item;
            }
        }
        return $result;
    }

     public static function groups_array($groupId){
        $res = self::find()->asArray()->where(['groups'=>$groupId])->all(Yii::$app->get('db_kdkj_rd'));
        $result = array();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['name']] = $item['title'];
            }
        }
        return $result;
    }

    /**
     *根据角色标识，返回角色所属分组
     */
    public static function groups_of_roles($roles = ''){
        if(is_string($roles)) {
            $roles = explode(',', $roles);
        }
        //$res = self::find()->select('groups')->where('`name` IN ("'.implode('","', $roles).'")')->all(Yii::$app->get('db_kdkj_rd'));
        $res = self::find()->select('groups')->where(['in', 'name', $roles])->all(Yii::$app->get('db_kdkj_rd'));
        $result = array();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[] = $item['groups'];
            }
        }
        return array_unique($result);
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_user_role}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_kdkj');
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
    public function rules()
    {
    	return [
    		[['name', 'title'], 'required'],
    		['name', 'match', 'pattern' => '/^[0-9A-Za-z_]{1,30}$/i', 'message' => '标识只能是1-30位字母、数字或下划线'],
    		['name', 'unique'],
            [['desc', 'permissions' ,'groups','menu'], 'safe'],
    	];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
    	return [
    		'name' => '标识',
    		'title' => '名称',
    		'desc' => '角色描述',
    		'permissions' => '权限',
            'groups' => '分组',
            'menu' => '菜单',
    	];
    }
    
    public static function findAllSelected()
    {
    	$roles = self::find()->asArray()->all(Yii::$app->get('db_kdkj_rd'));
    	$rolesItems = array();
    	foreach ($roles as $v) {
            $rolesItems[$v['groups']][$v['name']]['title'] = $v['title'];
    		$rolesItems[$v['groups']][$v['name']]['desc'] = $v['desc'];
    	}
    	return $rolesItems;
    }

    /**
     * 合并查询 查询条件增加open_status 返回数组
     */
    public static function addExtraCondition(Array $condition_arr) {
        if(!empty(self::$extra_condition)) {
            if(!empty($condition_arr)) {
                return array_merge($condition_arr, self::$extra_condition);
            }
            return self::$extra_condition;
        }
        return $condition_arr;
    }

    /**
     * 合并查询 查询条件增加open_status 返回sql string
     */
    public static function addExtraConditionSql() {
        $sql = '';
        $condition_arr = self::addExtraCondition([]);
        if(!empty($condition_arr)) {
            foreach ($condition_arr as $key => $value) {
                $sql .= " AND {$key} = {$value}";
            }
        }
        return $sql;
    }
}