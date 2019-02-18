<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/24
 * Time: 10:08
 */
namespace common\services\statistics;

use yii;
use yii\base\Exception;
use yii\base\Component;

use common\models\UserLoanOrderRepayment;
use common\models\loan\OrderStatisticsByStatus;
use common\models\loan\OrderStatisticsByGroup;
// use common\models\loan\OrderStatisticsByDay;
// use common\models\loan\OrderStatisticsByRate;

use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
class LoanCollectionStatisticsService extends Component
{

    public static function statistic_bygroup_bystatus($loanOrders){
        try{
            $loanOrders_amount = count($loanOrders);
            // echo count($loanOrders);exit;
            $statisticsStatus = array();
            $records = array();
            $repayIds = array_column($loanOrders, 'user_loan_order_repayment_id');
            $repayOrders = UserLoanOrderRepayment::ids($repayIds);
            foreach ($loanOrders as $key => $order) {
                echo ($key+1)."/".$loanOrders_amount."\r\n";//进度查看
                // $repayment = UserLoanOrderRepayment::id_rd($order['user_loan_order_repayment_id']);
                $repayment = $repayOrders[$order['user_loan_order_repayment_id']];

                //订单概览记录：(根据订单状态分类)
                $statisticsStatus[$order['status']]['status'] = $order['status'];
                $statisticsStatus[$order['status']]['title'] = LoanCollectionOrder::$status[$order['status']];

                isset($statisticsStatus[$order['status']]['amount']) ? ($statisticsStatus[$order['status']]['amount'] ++) : $statisticsStatus[$order['status']]['amount'] =1;//订单数

                isset($statisticsStatus[$order['status']]['principal']) ? ($statisticsStatus[$order['status']]['principal'] += $repayment['principal']): $statisticsStatus[$order['status']]['principal'] =$repayment['principal'];//本金

                if(!isset($statisticsStatus[$order['status']]['true_late_fee'])){
                    $statisticsStatus[$order['status']]['true_late_fee'] = 0;
                }
                if($repayment['true_repayment_time'] > 0 ){
                    //若有还款，则累加实际滞纳金：
                    $statisticsStatus[$order['status']]['true_late_fee'] += ($repayment['true_total_money'] - $repayment['principal']);//实际滞纳金
                }

                isset($statisticsStatus[$order['status']]['late_fee']) ? ($statisticsStatus[$order['status']]['late_fee'] += $repayment['late_fee']): $statisticsStatus[$order['status']]['late_fee'] =0;//应还滞纳金

                //订单分布记录：(根据订单所属催收分组分类)
                $records[$order['status']]['status'] = $order['status'];
                $records[$order['status']]['groups'][$order['current_overdue_group']]['id'] = $order['current_overdue_group'];

                isset($records[$order['status']]['groups'][$order['current_overdue_group']]['amount']) ? ($records[$order['status']]['groups'][$order['current_overdue_group']]['amount'] ++) : $records[$order['status']]['groups'][$order['current_overdue_group']]['amount'] =1;//订单数

                isset($records[$order['status']]['groups'][$order['current_overdue_group']]['principal']) ? ($records[$order['status']]['groups'][$order['current_overdue_group']]['principal'] += $repayment['principal']): ($records[$order['status']]['groups'][$order['current_overdue_group']]['principal'] =$repayment['principal']);//本金

            }
            $res = OrderStatisticsByStatus::collection_input_statistics($statisticsStatus);
            $res2 = OrderStatisticsByGroup::collection_input_statistics($records);
            if(!$res || !$res2){
                throw new Exception("根据订单状态、分组更新统计数据时发生失败");
                return false;
            }

        }catch(Exception $e){
            throw new Exception($e->getMessage());
            return false;
        }
        return true;
    }
}