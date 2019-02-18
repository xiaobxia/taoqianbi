<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/1/10
 * Time: 10:26
 */
namespace common\services\statistics;

use Yii;
use yii\base\Exception;
use yii\base\Component;

use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserStatsAnalysis;

class UserStatisticsAnalysisService extends Component
{
    /**
     * @param $data
     * @param $id
     * @return mixed
     */
    public function inputData($data,$id){
        //有log_id则进行更新操作   无log_id则新建一条记录
        if($id&&$log = UserStatsAnalysis::find()->where(['uid'=>$id])->one()){
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
        }else{
            $log = new UserStatsAnalysis();
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
        }
    }

    /**
     * @throws yii\base\InvalidConfigException
     * 获取注册用户信息
     */
    public function getUserData()
    {
        //$stats_start_time = strtotime(date('Y-m-d'),time()-86400);
        //$stats_end_time = strtotime(date('Y-m-d'),time());
        $stats_start_time = 0;
        $stats_end_time = 1483977600;
        //注册用户数据统计
        $register_user_data_total = LoanPerson::find()->select('id,id_number')
            ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time . ' AND id_number REGEXP "^.{18}$"')
            ->count('id' );
        echo "注册用户数量：".$register_user_data_total;

        try{
            $page_start = 0;
            $page_size = 1000;
            while ($register_user_data_total > $page_start) {
                $register_user_data_tmp = LoanPerson::find()->select('id,id_number')
                    ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time . ' AND id_number REGEXP "^.{18}$"')
                    ->limit($page_size)->offset($page_start)
                    ->asArray()->all();
                foreach ($register_user_data_tmp as $item){
                    $inputData['uid'] = $item['id'];
                    $inputData['id_number'] = $item['id_number'];
                    $inputData['province'] = substr($item['id_number'],0,2);
                    $inputData['city'] = substr($item['id_number'],2,2);
                    if(substr($item['id_number'],16,1)%2==1){
                        $inputData['sex'] = 0;
                    }else{
                        $inputData['sex'] = 1;
                    }
                    $inputData['age'] = (2017-substr($item['id_number'],6,4)+1);
                    $this->inputData($inputData,$item['id']);
                }
                $page_start += $page_size;
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            exit;
        }
    }

    /**
     * @throws yii\base\InvalidConfigException
     * 为申请借款用户添加标记is_apply
     */
    public function getApplyLoanUser(){
        $stats_start_time = 0;
        $stats_end_time = 1483977600;
        //申请借款用户统计
        $application_loan_person_total = UserLoanOrder::find()->select('user_id ')
            ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time)
            ->count('distinct user_id');
        echo "申请借款用户数量：".$application_loan_person_total;

        $page_start = 0;
        $page_size = 1000;
        try {
            while($application_loan_person_total > $page_start){
                $application_loan_person_data_tmp = UserLoanOrder::find()->select('user_id')->distinct()
                    ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time)
                    ->limit($page_size)->offset($page_start)
                    ->asArray()->all();
                $update_data_arr = array();
                if(!empty($application_loan_person_data_tmp)){
                    foreach ($application_loan_person_data_tmp as $item){
                        $update_data_arr[] = $item['user_id'];
                    }
                }
                UserStatsAnalysis::updateAll(['is_apply'=>1],['uid'=>$update_data_arr]);
                var_dump($page_start);
                $page_start += $page_size;
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            exit;
        }
    }

    /**
     * @throws yii\base\InvalidConfigException
     * 为成功借款用户添加标记
     */
    public function  getSuccessLoanUser(){
        $stats_start_time = 0;
        $stats_end_time = 1483977600;
        //成功借款用户统计
        $success_loan_person_total = UserLoanOrderRepayment::find()->select('user_id ')
            ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time)
            ->count('distinct user_id');
        echo "成功借款用户数量：".$success_loan_person_total;

        $page_start = 0;
        $page_size = 1000;
        try {
            while($success_loan_person_total > $page_start){
                $success_loan_person_data_tmp = UserLoanOrderRepayment::find()->select('user_id')->distinct()
                    ->where('created_at > '.$stats_start_time. ' AND created_at <= ' . $stats_end_time)
                    ->limit($page_size)->offset($page_start)
                    ->asArray()->all();
                $update_data_arr = array();
                if(!empty($success_loan_person_data_tmp)){
                    foreach ($success_loan_person_data_tmp as $item){
                        $update_data_arr[] = $item['user_id'];
                    }
                }
                UserStatsAnalysis::updateAll(['is_loan'=>1],['uid'=>$update_data_arr]);
                var_dump($page_start);
                $page_start += $page_size;
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
            exit;
        }
    }
}