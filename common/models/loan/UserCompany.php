<?php
namespace common\models\loan;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\loan\UserSchedule;


/**
 * UserCompany model
 *
 */
class UserCompany extends ActiveRecord 
{

    const XIANJINCARD_2 = '28';//极速钱包2组ID
    
    const USING = 1;//启用状态
    const DELETED = 0;//删除状态
    const IS_SELF_TEAM = 1;//是自营公司
    const IS_NOT_SELF_TEAM = 0;//不是自营公司

    public function rules()
    {
        return [
            [['title','system'], 'required'],
            [['real_title','system'], 'required'],
            ['add_time', 'default', 'value'=>time()],
            ['priority', 'default', 'value'=>1],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_user_company}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_assist');
    }
    public static function rm($company_id){
       
        //(删除只是更改数据状态，不做真实数据库删除):
         $item = self::findOne($company_id);
         $item->status = self::DELETED;
         $item->save();
         UserSchedule::clean_by_company($company_id); //删除公司信息的同时，删除相对应的分配规则
         return true;
    }

    /**
     *返回催单公司信息
     */
    public static function lists($offset = '', $limit = ''){
        $query = self::find()->where(['status'=>self::USING])->asArray();
        if(!empty($offset)) $query->offset($offset);
        if(!empty($limit)) $query->limit($limit);

        $lists = $query->all();
        $res = array();
        if(!empty($lists)){
            foreach ($lists as $key => $item) {
                $res[$item['id']] = $item;
            }
        }
        return $res;
    }
    /**
     *返回指定催单公司信息
     */
    public static function lists_id($id){
        $query = self::find()->where(['status'=>self::USING,'id'=>$id])->asArray();
        $lists = $query->one();
        $res = array();
        if(!empty($lists)){
            $res[$lists['id']] = $lists;
        }
        return $res;
    }
    /**
     *返回所有催单公司信息
     */
    public static function getAll($offset = '', $limit = ''){
        $query = self::find()->asArray();
        if(!empty($offset)) $query->offset($offset);
        if(!empty($limit)) $query->limit($limit);

        $lists = $query->where(['status'=> self::USING])->all();
        $res = array();
        if(!empty($lists)){
            foreach ($lists as $key => $item) {
                $res[$item['id']] = $item;
            }
        }
        return $res;
    }
    public static function getOutsideAll($offset = '', $limit = ''){
        $query = self::find()->asArray();
        if(!empty($offset)) $query->offset($offset);
        if(!empty($limit)) $query->limit($limit);

        $lists = $query->all();
        $res = array();
        if(!empty($lists)){
            foreach ($lists as $key => $item) {
                $res[$item['id']] = $item;
            }
        }
        return $res;
    }

    /**
     *根据公司ID，返回公司信息
     */
    public static function id($companyId){
        return self::find()->where(['id'=>$companyId])->one();
    }

    /**
     *返回自营公司ID
     */
    public static function self_id(){
        return self::find()->where(['system'=>self::IS_SELF_TEAM])->select('id')->scalar();
    }

    /**
     * y验证公司名唯一性
     */
    public static function unique_title($compamyTitle,$companyId=0,$type=0){
        if ($type==1) { //编辑 
            $condition = " id!={$companyId} and title='{$compamyTitle}' and status!=".self::DELETED;
            return self::find()->where($condition)->select('id')->scalar();
        }elseif($type == 2){
            $condition = " id!={$companyId} and real_title='{$compamyTitle}' and status!=".self::DELETED;
            return self::find()->where($condition)->select('id')->scalar();
        }elseif($compamyTitle =='title'){      //添加
            $condition = " title='{$compamyTitle}' and status!=".self::DELETED;
            return self::find()->where($condition)->one();
        }elseif($compamyTitle =='real_title')
        {
            $condition = " real_title='{$compamyTitle}' and status!=".self::DELETED;
            return self::find()->where($condition)->one();
        }
    }

    //返回自营机构的id 包括子机构
    public static function getSelfIds(){
        // $self_ids = self::find()->where(['system'=>self::IS_SELF_TEAM])->all();
        return [1,28];
    } 

    /**
     *判断给定的机构ID，是否是委外机构
     *@param int $companyId 要判断的机构ID
     *@return boolean true:是委外机构
     */
    public static function is_outside($companyId = 0){
        $one = self::find()->where(['id'=>$companyId])->asArray()->one();
        if(empty($one)) return false;
        if($one['system'] == self::IS_SELF_TEAM)    return false;
        return true;
    }
}
