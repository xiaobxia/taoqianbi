<?php

namespace backend\models;

use yii\db\ActiveRecord;
use Yii;
class LoanMessageTemplate extends ActiveRecord{
	public static function tableName(){
		return "{{%loan_message_template}}";
	}
	public function rules(){
		return [
			[['title','content','temp_level','display_scene'],'required'],
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
        return Yii::$app->get('db_kdkj');
    }
    //适用场景
    const LOAN_QUNFA = 1;
    const LOAN_GEREN = 2;
    public static $loan_scene =[
    	self::LOAN_GEREN=>'发本人',
    	self::LOAN_QUNFA=>'发亲友',
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
   public static $change_word = [
   		
   ]; 
}
