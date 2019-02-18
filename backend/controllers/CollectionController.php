<?php

namespace backend\controllers;

use common\models\DailyData;
use common\models\LoanPerson;
use common\models\OrderRejectRank;
use common\models\StatisticsVerification;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use Yii;
use common\helpers\Url;
use yii\web\Response;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;
use yii\base\Exception;
use backend\models\AdminUserRole;

class CollectionController extends BaseController {
    /**
     * @return string
     * @name 借款管理-借款列表-(逾期用户)查看-催收详情/actionCollectionRecordList
     */
    public function actionCollectionRecordList(){
        //查询db
        $db_assist = Yii::$app->db_assist;
        $sql="select b.user_loan_order_id,a.contact_name,a.relation,a.remark,a.created_at from tb_loan_collection_record_new a join tb_loan_collection_order b on a.order_id=b.id";
        $loan_person_id=$this->request->get('loan_person_id','');
        $order_id=$this->request->get('order_id','');
        $sql.=" where 1 = 1";

        if (!empty($loan_person_id)){
            $sql.=" and b.user_id ={$loan_person_id} ";
        }
        if(!empty($order_id)){
            $sql.=" and b.user_loan_order_id={$order_id} ";
        }
        $sql.=" order by a.id desc";
        $collection_data=$db_assist->createCommand($sql)->queryAll();
        $view = 'collection-record-list';

        return $this->render($view, array(
            'collection_data' => $collection_data
        ));
    }
}