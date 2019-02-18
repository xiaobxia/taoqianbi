<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;

/**
 * WeixinUser model
 *
 */
class WeixinUser extends BaseActiveRecord
{

    // 手机验证正则表达式
    const PHONE_PATTERN = '/^1[0-9]{10}$/';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%weixin_user}}';
    }

    /**
     * 加上下面这行，数据库中的created_at和updated_at会自动在创建和修改时设置为当时时间戳
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
            // ['phone', 'required', 'message' => '手机号不能为空'],
            ['phone', 'match', 'pattern' => self::PHONE_PATTERN, 'message' => '手机号格式错误'],
            ['phone', 'checkKdWeiXinUnique', 'message' => '已经存在该号码'],
            ['openid', 'required', 'message' => '用户名不能为空'],
            ['openid', 'unique', 'message' => '已经存在该用户'],
            [['uid','created_at','updated_at', 'unsubscribe_time','bind_time', 'status'],'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone' => '手机号',
            'uid' => '被邀请用户id',
            'openid' => '',
            'nickname' => '昵称',
            'headimgurl' => '头像',
            'expect_update_time' => '',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'unsubscribe_time' => '取消关注时间'
        ];
    }

    public function checkKdWeiXinUnique($attr, $param) {
        $info = static::find()->where(['phone'=>$this->$attr])->limit(1)->select(['id'])->one();
        if ($info) {
            $this->addError($attr, '已经存在该号码');
        }
    }
    public static function getUserInfo($openid)
    {
        $userinfo = static::findOne(['openid' => $openid]);
        return $userinfo;
    }
    
    /**
     * 通过微信号获取口袋账号
     */
    public function getKdUser(){
        if($this->uid){
            return \common\models\LoanPerson::findOne(['id'=>$this->uid]);
        }
        if($this->phone){
            return \common\models\LoanPerson::findOne(['phone'=>$this->phone]);
        }
        return null;
    }
    public function bindKdUser($user){
        if($user && !$this->uid && !$this->phone){
            $this->uid = $user->id;
            $this->phone = $user->phone;
            return $this->save(false,['uid','phone']);
        }
        return null;
    }
}
