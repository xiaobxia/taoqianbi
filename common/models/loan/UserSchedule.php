<?php
namespace common\models\loan;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\loan\UserCompany;
use common\models\loan\UserGroup;
use common\models\loan\LoanCollection;
/**
 * UserSchedule model
 *
 */
class UserSchedule extends ActiveRecord 
{
    public $company_title;
    public $group_title;
    const NO_SELF_TEAM = 0;//不包含自营团队
    const INCLUDE_SELF_TEAM = 1;//包含自营团队
    const FIRST_DAY = 1;//每月首日，1号
    const SECOND_DAY = 2;
    const ELEVENTH_DAY = 11;

    /**
     *返回指定公司，指定用户组的最大接单量，默认不包括自营团队
     *@param string|array $group 指定用户组，不指定的话，表示所有组
     *@param string $company 指定公司，不指定的话，表示所有公司
     *@param int $system 是否是自营团队，默认不是
     *备注：若当前日期是2号~11号，S2组的每人每天最大接单量参考S1组的
     */
    public function group_max_amount($groups = '', $company = '',$system = self::NO_SELF_TEAM){
        $conditions = ' `system` = '.$system;
        if(!is_array($groups)){
            $groups = array($groups);
        }

        $conditions .= (' AND group_id IN('.implode(',', $groups).')');   
        if(!empty($company)) $conditions .= (' AND company_id = '.$company);     
       
        $scheduleLists = self::find()->where($conditions)->all();
        $total = 0;
        $day_today = date('d');
        if(!empty($scheduleLists)){
            foreach ($scheduleLists as $key => $schedule) {
                if($schedule['group_id'] == LoanCollection::GROUP_S_TWO && $day_today > self::SECOND_DAY && $day_today <= self::ELEVENTH_DAY){
                    $schedule['max_amount'] = self::find()->select('max_amount')->where(['company_id'=>$company, 'group_id'=>LoanCollection::GROUP_S_ONE])->scalar();
                }
                if(empty($schedule['max_amount']) || !is_int($schedule['max_amount']))  $schedule['max_amount'] = 0;
                foreach ($groups as $key => $group) {
                   $userList = LoanCollection::group_user($group, $schedule['company_id']);
                    $total += $schedule['max_amount'] * count($userList);
                }
                
            }
        }
        $count = count($groups);
        return round($total/$count);
    }

 /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_collection_user_schedule}}';
    }
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }

    /**
     *返回分配规则信息
     */
    public static function schedule_lists($offset= '' , $limit = ''){
        $query = self::find()->orderBy(['id'=>SORT_DESC]);
        if(!empty($offset)) $query->offset($offset);
        if(!empty($limit)) $query->limit($limit);
        $list =  $query->asArray()->all();
        if(!empty($list)){
            //将公司ID,分组ID替换成TITLE名称
            $companies = UserCompany::lists();
            $groups = UserGroup::lists();
            foreach ($list as $key => $item) {
                if(!isset($companies[$item['company_id']]) || $companies[$item['company_id']]['status'] == UserCompany::DELETED){
                    unset($list[$key]);continue;
                }
                $list[$key]['company_title'] = isset($companies[$item['company_id']]) ? $companies[$item['company_id']]['title'] : '未知公司';
                $list[$key]['group_title'] = isset($groups[$item['group_id']]) ? $groups[$item['group_id']] : '未知分组';
                $group_users = LoanCollection::group_user($item['group_id'], $item['company_id']);
                $list[$key]['group_amount'] = count($group_users);
            }
        }
        return $list;
    }

    public static function schedule_update($company_id, $group_id, $max_amount){
        $item = self::find()->where(['company_id'=>$company_id, 'group_id'=>$group_id])->one();
        $item->max_amount = $max_amount;
        return $item->save();

    }

    /**
     *根据公司ID，清理分配规则
     */
    public static function clean_by_company($company_id){
        return self::deleteAll(['company_id'=>$company_id]);
    }

    /**
     *根据公司ID，新增分配规则
     *@param array $company
     */
    public static function add_by_company($company){
        $groups = UserGroup::lists();
        if(!empty($groups)){
            foreach ($groups as $key => $group) {
                $_model = new self();
                $_model->company_id = $company['id'];
                $_model->group_id = $key;
                $_model->system = $company['system'];
                $_model->save();
            }
        }
    }
}
