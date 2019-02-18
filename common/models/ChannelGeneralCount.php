<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/3
 * Time: 16:08
 */
namespace common\models;


use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class ChannelGeneralCount extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%channel_general_count}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
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
     * 根据渠道获取一条数据
     * @param $sourceTag
     * @return array|ActiveRecord|null
     */
    public function getInfoBySource($sourceTag)
    {
        return self::find()->where(['channel_name' => $sourceTag, 'date' => date('Y-m-d', time())])->one();
    }

    /**
     * 记录渠道点击断链接的数量
     * @param $sourceTag
     */
    public function saveClick($sourceTag)
    {
        $data = $this->getInfoBySource($sourceTag);
        if($data){
            $data->click_num += 1;
            $data->save();
        }else{
            $this->click_num = 1;
            $this->channel_name = $sourceTag;
            $this->date = date('Y-m-d', time());
            $this->save();
        }
    }

    /**
     * 获取验证码数量
     * @param $sourceTag
     */
    public function saveGetCode($sourceTag)
    {
        $data = $this->getInfoBySource($sourceTag);
        if($data){
            $data->get_code_num += 1;
            $data->save();
        }else{
            $this->get_code_num = 1;
            $this->channel_name = $sourceTag;
            $this->date = date('Y-m-d', time());
            $this->save();
        }
    }

    /**
     * 点击注册数量
     * @param $sourceTag
     */
    public function saveReg($sourceTag)
    {
        $data = $this->getInfoBySource($sourceTag);
        if($data){
            $data->reg_num += 1;
            $data->save();
        }else{
            $this->reg_num = 1;
            $this->channel_name = $sourceTag;
            $this->date = date('Y-m-d', time());
            $this->save();
        }
    }

}
