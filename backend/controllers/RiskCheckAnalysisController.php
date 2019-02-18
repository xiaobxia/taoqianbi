<?php
namespace backend\controllers;

use common\models\CreditJsqb;
use common\models\DailyRegisterAndLoanData;
use common\models\DailyRegisterLoanData;
use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\data\Pagination;
use yii\db\Query;
use common\models\UserOrderLoanCheckLog;
use common\models\UserLoanOrder;
use common\models\CreditJxlQueue;
use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\OrderCheckCount;

class RiskCheckAnalysisController extends BaseController {

    /**
     * @name 信息统计 --信息列表/actionMessageList
     */
    public function actionMessageList() {
        $data = $this->getAdminWorkList();
        $fail_count = UserOrderLoanCheckLog::find()->where($data['where'])
            ->andWhere(['before_status' => 0])
            ->andWhere(['after_status' => -3])
            ->count('*', Yii::$app->get('db_kdkj_rd'));
        $success_count = UserOrderLoanCheckLog::find()->select('order_id')->distinct()->where($data['where'])
            ->andWhere(['before_status' => 0])
            ->andWhere(['after_status' => 7])->count('*', Yii::$app->get('db_kdkj_rd'));
        $count = UserOrderLoanCheckLog::find()->select('order_id')->distinct()->count('*', Yii::$app->get('db_kdkj_rd'));

        return $this->render('message-list', array(
            'success_count' => $success_count,
            'fail_count' => $fail_count,
            'count' => $count,
            'add_start' => $data['add_start'],
            'add_end' => $data['add_end'],
            'source' => $data['source']
        ));
    }

    //过滤条件
    private function getAdminWorkList() {
        $add_start = strtotime($this->request->get('add_start'));
        $add_end = strtotime($this->request->get('add_end') . "+1 day");
        $source = $this->request->get('sub_order_type', -1);
        $where = '1=1';
        if ($add_start) {
            $where = $where . " and updated_at>='" . $add_start . "'";
        } else {
            $add_start = time() - 7 * 86400;
            $where = $where . " and updated_at>='" . $add_start . "'";
        }
        if ($add_end) {
            $where = $where . " and updated_at<='" . $add_end . "'";
        } else {
            $add_end = time();
            $where = $where . " and updated_at<='" . $add_end . "'";
        }
        if ($source != -1) {
            ini_set('memory_limit', '2048M');
            $arr = [];
            $id_arr = UserLoanOrder::find()
                ->select('id')
                ->where(['sub_order_type' => $source])
                ->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            foreach ($id_arr as $item) {
                $arr[] = $item['id'];
            }
            if (!empty($arr)) {
                $str = implode(",", $arr);
                $where = $where . ' and order_id in (' . $str . ')';
            } else {
                $where = $where . " and order_id in ('')";
            }
        }
        $data = [];
        $data['add_start'] = $add_start;
        $data['add_end'] = $add_end;
        $data['source'] = $source;
        $data['where'] = $where;
        return $data;
    }
    /**
     * @name 拒绝原因 --拒绝列表/actionRfyyList
     **/
    public function actionRfyyList(){
        //查询条件
        $add_start = strtotime($this->request->get('add_start'));
        $add_end = strtotime($this->request->get('add_end'));
        $where = '  `after_status` =-3 and `remark` <>\'非白名单用户直接拒接\'  and `remark` <>\'\' and  `remark` is not null ';
        if (!$add_start) {
            $add_start = strtotime(date('Y-m-d',time()));
        }
        $where = $where . " and updated_at>='" . $add_start . "'";
        if (!$add_end) {
            $add_end=time();
        }
        $add_end=strtotime(date('Y-m-d 23:59:59',$add_end));
        $where = $where . " and updated_at<='" . $add_end . "'";
        //获得列表数据
        $info = UserOrderLoanCheckLog::find()->select('remark,count(1) as id')
            ->groupBy('remark')->orderBy('count(1) desc')
            ->where($where)->asArray()->all();

        //获得总记录数
        $where.=' and `remark` <>\'0点到6点的订单全部拒绝\' ';
        $count = UserOrderLoanCheckLog::find()->select('id')->where($where)->count('*', Yii::$app->get('db_kdkj_rd'));
        return $this->render('rfyy-list',array('info'=>$info,'count'=>$count));
    }

    /**
     * @name 注册申请 --注册申请统计/actionRegisterLoanList
     **/
    public function actionRegisterLoanList(){
        //更新数据
        self::changeRegisgerLoadData(time());
        //查询条件
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $where=' 1=1 ';
        if (!$add_start) {
            $add_start = date('Y-m-d',time() - 7 * 86400);
        }
        $where = $where . " and date>='" . $add_start . "'";
        if (!$add_end) {
            $add_end=date('Y-m-d',time());
        }
        $where .= " and date<='" . $add_end . "'";
        $query=DailyRegisterLoanData::find()
            ->select('*')
            ->where($where)
            ->orderBy(' date desc ');
        $count1 = clone $query;
        $pages = New Pagination(['totalCount' => $count1->count()]);
        $pages->pageSize = 20;
        $res = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('register-loan-list',array('info'=>$res,'pages'=>$pages));
    }

    /**
     * @name 更新日统计 --更新日统计/changeRegisgerLoadData
     **/
    private function changeRegisgerLoadData($date=null){
        if($date){
            $date=strtotime(date('Y-m-d',$date));
        }else{
            $date=strtotime(date('Y-m-d',time()));
        }
        //查询今日统计数据
        $todaytime=strtotime(date('Y-m-d',$date));
        $sql="select aa.mydate,register,IFNULL(register_white,0) as register_white,IFNULL(loan_white,0) as loan_white,IFNULL(payment_white,0) as payment_white,IFNULL(loan,0) as loan from (
            select mydate,count(1) as register from (
             select FROM_UNIXTIME(created_at,'%Y-%m-%d') as mydate from tb_user_register_info where created_at>{$todaytime}
             ) aa GROUP BY mydate
            ) aa

            left join (
            select count(1) as register_white,mydate from (
            SELECT FROM_UNIXTIME(a.created_at,'%Y-%m-%d') as mydate  FROM `tb_user_register_info` as a LEFT JOIN `tb_credit_jsqb` as b on a.`user_id` = b.`person_id`  where b.`is_white`  = 1 and a.created_at>{$todaytime}
            ) aa group by mydate
            ) bb on aa.mydate=bb.mydate

            left join (

            select count(1) as loan_white,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o LEFT JOIN `tb_credit_jsqb` as j on o.`user_id` = j.`person_id`  where j.`is_white`  = 1 and o.`created_at`>{$todaytime} GROUP BY o.`user_id`
            ) aa GROUP BY mydate


            ) cc on aa.mydate=cc.mydate

            left join (

            select count(1) as payment_white,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o LEFT JOIN `tb_credit_jsqb` as j on o.`user_id` = j.`person_id`  where j.`is_white`  = 1 and o.`status` > 1 and o.`created_at`>{$todaytime}
            ) aa group by mydate

            ) dd on aa.mydate=dd.mydate

            left join (

            select count(1) as loan,mydate from (
            SELECT FROM_UNIXTIME(o.`created_at`,'%Y-%m-%d') as mydate FROM `tb_user_loan_order` as o where o.`created_at` > {$todaytime} GROUP BY o.`user_id`
            ) aa GROUP BY mydate

            ) ee on aa.mydate=ee.mydate";
        $connection = Yii::$app->db;
        $mydata = $connection->createCommand($sql)->queryAll();
        if(!empty($mydata)){
            $register=$mydata[0]['register'];
            $register_white=$mydata[0]['register_white'];
            $loan_white=$mydata[0]['loan_white'];
            $payment_white=$mydata[0]['payment_white'];
            $loan=$mydata[0]['loan'];
            $DailyRegisterLoanData=DailyRegisterLoanData::findOne(['date'=>date('Y-m-d',$todaytime)]);
            if(!$DailyRegisterLoanData){
                $DailyRegisterLoanData=new DailyRegisterLoanData();
                $DailyRegisterLoanData->date=date('Y-m-d',$todaytime);
            }
            $DailyRegisterLoanData->register=$register;
            $DailyRegisterLoanData->register_white=$register_white;
            $DailyRegisterLoanData->loan_white=$loan_white;
            $DailyRegisterLoanData->payment_white=$payment_white;
            $DailyRegisterLoanData->loan=$loan;
            $DailyRegisterLoanData->save();
        }
        unset($mydata);
    }

    /**
     * @name 信息统计 --子页面/actionMessageLista
     */
    public function actionMessageLista() {
        $where = $this->getAdminWorkLista();
        $query = UserOrderLoanCheckLog::find()
            ->select('order_id,user_id,remark,reason_remark,updated_at')
            ->where($where)
            ->andWhere(['before_status' => 0])
            ->andWhere(['after_status' => -3]);
        $count1 = clone $query;
        $pages = New Pagination(['totalCount' => $count1->count()]);
        $pages->pageSize = 20;
        $message = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('message-lista', array(
            'message' => $message,
            'pages' => $pages,
        ));
    }

    /*子页面查询*/
    private function getAdminWorkLista() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end') + 86400;
        $source = $this->request->get('error_source');
        $where = '1=1';
        if ($add_start) {
            $where = $where . " and updated_at>={$add_start} ";
        } else {
            $add_start = time() - 7 * 24 * 3600;
            $where = $where . " and updated_at>={$add_start} ";
        }
        if ($add_end) {
            $where = $where . " and updated_at<={$add_end} ";
        } else {
            $add_end = time();
            $where = $where . " and updated_at<={$add_end} ";
        }

        if ($source != -1) {
            $arr = [];
            $id_arr = UserLoanOrder::find()
                ->select('id')
                ->where(['sub_order_type' => $source])
                ->asArray()
                ->all(Yii::$app->get('db_kdkj_rd'));
            foreach ($id_arr as $item) {
                $arr[] = $item['id'];
            }
            if (!empty($arr)) {
                $str = \implode(",", $arr);
                $where = $where . " and order_id in ({$str})";
            } else {
                $where = $where . " and order_id in ('')";
            }
        }

        return $where;
    }

    /**
     * @name 新的数据统计
     */
    public function actionDataList(){
        $where = $this->getInfoWorkList();
        $query = DailyRegisterAndLoanData::find()
            ->where($where);
        $count1 = clone $query;
        $pages = New Pagination(['totalCount' => $count1->count()]);
        $pages->pageSize = 20;
        $message = $query->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('daily-data', array(
            'message' => $message,
            'pages' => $pages,
        ));
    }

    /*子页面查询*/
    private function getInfoWorkList() {
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        if(is_string($add_start) || is_string($add_end)){
            $add_start = strtotime($add_start);
            $add_end = strtotime($add_end);
        }
        $add_end_res = 0;
        $where = '1=1';
        if ($add_start) {
            $where = $where . " and credit_at>={$add_start} ";
        } else {
            $add_start = time() - 7 * 24 * 3600;
            $where = $where . " and credit_at>={$add_start} ";
        }
        if ($add_end) {
            $where = $where . " and credit_at<={$add_end} ";
        } else {
            $add_end = time();
            $where = $where . " and credit_at<={$add_end} ";
        }

        return $where;

    }

}
