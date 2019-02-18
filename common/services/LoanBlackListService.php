<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\models\LoanBlackList;
use common\models\LoanBlacklistDetail;

class LoanBlackListService extends Component
{
    public $message = '';

    public function blackStatus($id){
        $result = LoanBlackList::findOne(['user_id'=>$id,'black_status'=>LoanBlackList::STATUS_YES]);
        if(is_null($result)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * $arr格式 [LoanBlacklistDetail::TYPE_ID_NUMBER=>'310110199003050110',LoanBlacklistDetail::TYPE_COMPANY_NAME=>'小凌鱼金服']
     *
     **/
    public function checkBlacklistDetail($arr){
        $hit_list = [];
        foreach($arr as $k=>$v){
            if(in_array($k,array_keys(LoanBlacklistDetail::$type_list))){
                if(!is_null(LoanBlacklistDetail::find()->where(['type'=>$k,'content'=>$v])->one())){
                    $hit_list[] = $k;
                }
            }
        }
        if(count($hit_list) != 0 ){
            return [
                'code' => -1,
                'data'=> $hit_list
            ];
        }else{
            return [
                'code' => 0,
            ];
        }
    }


}