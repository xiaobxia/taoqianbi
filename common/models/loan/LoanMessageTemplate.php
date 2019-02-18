<?php

namespace common\models\loan;

use yii\db\ActiveRecord;
use Yii;
class LoanMessageTemplate extends ActiveRecord{
	public static function tableName(){
		return "{{%loan_message_template}}";
	}
	public function rules(){
		return [
			[['title','content','temp_level','display_scene','need_callback'],'required'],
		];
	}
    public function attrributeLables(){
        return [
            'id'=>Yii::t('app','ID'),
            'title'=>Yii::t('app','短信标题'),
            'content'=>Yii::t('app','短信内容'),
            'temp_level'=>Yii::t('app','适用分组'),
            'display_scene'=>Yii::t('app','适用人群'),
            'updated_at'=>Yii::t('app','UPDATED_AT'),
        ];
    }
	/**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_assist');
    }
    //适用场景
    const LOAN_OWN = 1;
    const LOAN_OTHER = 2;
    public static $loan_scene =[
    	self::LOAN_OWN=>'本人',
    	self::LOAN_OTHER=>'亲友',
    ];
    public static $is_need_callback = [
        1=>'是',
        2=>'否'
    ];
    const GROUP_S_ONE = 1;
    const GROUP_S_THREE = 3;
    const GROUP_S_FOUR = 4;
    const GROUP_S_FIVE = 5;
    const GROUP_S_OUT = 10;

    const CHANGE_FROM =1;	//
    const CHANGE_USERNAME =2;	//
    const CHANGE_ID_CARD =3;	//
    const CHANGE_PHONE =4;		//
    const CHANGE_TOTAL_MONEY =5;		//
    const CHANGE_OVERDUE_DAY =6;		//
    public static $replace_word = [
        '[#名字#]'=>'名字',
        '[#先生/女士#]'=>'先生/女士',
        '[#手机号#]'=>'手机号',
        '[#身份证号#]'=>'身份证号',
        '[#逾期天数#]'=>'逾期天数',
        '[#借款来源#]'=>'借款来源',
        '[#本息总额#]'=>'本息总额',
   		'[#回电电话#]'=>'回电电话',
    ];
    private static $replace_word_temp = [
        '[#名字#]'=>'user_name',
        '[#先生/女士#]'=>'user_sex',
        '[#手机号#]'=>'user_phone',
        '[#身份证号#]'=>'card_id',
        '[#逾期天数#]'=>'overdue_days',
        '[#借款来源#]'=>'from',
        '[#本息总额#]'=>'total_money',
        '[#回电电话#]'=>'call_this_phone',
    ];

    //获取指定短信模板
    public static function getAllTemp($condition = "1=1",$offset,$limit){
        return self::find()->where($condition)->offset($offset)->limit($limit)->all();
    }
    //获取总条数
    public static function array_list_amount($condition = "1=1"){
        $query = self::find()->where($condition);
        return $query->count('id');
    }
    //通过ID获得模板
    public static function getTempById($id){
        return self::find()->where(['id'=>$id])->limit(1)->one();
    }
    //删除模板
    public static function delTempById($id){
        $oneTemp = self::find()->where(['id'=>$id])->limit(1)->one();
        return $oneTemp->delete();
    }

    //通过催收订单逾期级别获取该级别的短信模板
    public static function getTempByLevel($order_level){
        $temps = self::find()->where(['<=','temp_level',$order_level])->orderBy(['id'=>SORT_DESC])->asArray()->all();
        $show_temps = [];
        if (!empty($temps)) {
            foreach ($temps as $key => $value) {
                $show_temps['title'][$value['id']] = $value['title'];
                $show_temps['content'][$value['id']] = $value['content'];
                if ($value['need_callback'] == 1) {
                    $show_temps['need_callback'][$value['id']] = $value['id'];
                }
                if (!isset($show_temps['need_callback'])) {
                    $show_temps['need_callback'] = [];
                }
            }
        }
        return $show_temps;
    }
    //通过当前催收人的分组确定出个人的短信模板
    public static function getOwnTemp($group){
        $condition = "temp_level<={$group} and display_scene=".self::LOAN_OWN;
        $temps = self::find()->where($condition)->orderBy(['temp_level'=>SORT_DESC])->asArray()->all();
        $show_temps = [];
        if (!empty($temps)) {
            foreach ($temps as $key => $value) {
                $show_temps['title'][$value['id']] = $value['title'];
                $show_temps['content'][$value['id']] = $value['content'];
                if ($value['need_callback'] == 1) {
                    $show_temps['need_callback'][$value['id']] = $value['id'];
                }
                if (!isset($show_temps['need_callback'])) {
                    $show_temps['need_callback'] = [];
                }
            }
        }
        return $show_temps;
    }
    //替换短信内容
    public static function replace_temp_word($user_info,$temp_content){
        foreach (self::$replace_word_temp as $key => $value) {
            $temp_content = str_replace($key,$user_info[$value],$temp_content);
        }
        return $temp_content;
    }
// create table tb_loan_message_template(
//     id int primary key auto_increment,
//     title varchar(56) not null default '' comment '短信标题',
//     content text comment '短信内容',
//     display_scene tinyint default 0 comment '发送对象',
//     temp_level tinyint default 0 comment '适用分组',
//     need_callback tinyint default 0 comment '是否需回电',
//     author int not null default 0 comment '作者',
//     created_at int ,
//     updated_at int
//     )charset=utf8;
}