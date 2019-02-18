<?php

namespace backend\controllers;

use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\CreditYys;
use common\models\CreditJxl;
use Yii;
use yii\db\Query;
use yii\web\Response;
use yii\data\Pagination;

/**
 * 运营商接口
 */
class YysController extends BaseController
{

    /**
     * user gaokuankuan
     * @name 借款管理-用户借款管理-借款列表-查看-运营商报告/actionView
     */
    public function actionView(){
        $id = intval($this->request->get('id'));
         //$id =  4000;
        $loanPerson = LoanPerson::find()->where(['id' => $id])->one();
        $type = $this->request->get('type',1);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该借款人不存在',self::MSG_ERROR);
        }
        $creditYys = CreditYys::find()->where(['person_id'=>$id])->one();
        $data = '';

        if(!is_null($creditYys) && !empty($creditYys['data'])){
            $data = json_decode($creditYys['data'],true);
            if($type == 2){
                if(!empty($data['contact_list'])){
                    $sort = [];
                    foreach($data['contact_list'] as $item){
                        $sort[$item['call_cnt']][] = $item;
                    }
                    krsort($sort);
                    $contact_list = [];
                    foreach($sort as $v){
                        foreach ($v as $j){
                            $contact_list[] = $j;
                        }
                    }
                    $data['contact_list'] = $contact_list;
                }
            }

        }else{
            
            $creditYys = CreditJxl::findLatestOne(['person_id'=>$id]);

            if(!is_null($creditYys) && !empty($creditYys['data'])){
            $data = json_decode($creditYys['data'],true);
            if($type == 2){
                if(!empty($data['contact_list'])){
                    $sort = [];
                    foreach($data['contact_list'] as $item){
                        $sort[$item['call_cnt']][] = $item;
                    }
                    krsort($sort);
                    $contact_list = [];
                    foreach($sort as $v){
                        foreach ($v as $j){
                            $contact_list[] = $j;
                        }
                    }
                    $data['contact_list'] = $contact_list;
                }
            }
        }
    }
        return $this->render('view', array(
            'loanPerson' => $loanPerson,
            'creditJxl' => $creditYys,
            'data' => $data,
            'type' => $type
        ));
    }
}

