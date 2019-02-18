<?php
namespace console\controllers;

use common\models\ChannelLoanCount;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use Yii;

/**
 * 渠道相关统计脚本
 * Class ChannelCountController
 * @package console\controllers
 */

class ChannelCountController extends BaseController
{


    /**
     * 统计渠道成本脚本
     * 数据存入channle_loan_count表中
     * @param $date 时间，格式 2018-12-12
     */
    public function actionChannelLoanCount($date='')
    {
        $date = $date == '' ? date('Y-m-d', strtotime('-1 day')) : $date;
        $startTime = strtotime($date);
        $endTime = strtotime($date.' 23:59:59');
        $condition = 'created_at >= '.$startTime.' and created_at <= '.$endTime;
        //查询用户的注册数量
        $regCountList = (new LoanPerson())->getCountUser($condition);
        $userLoanOrderModel = new UserLoanOrder();
        //查询成功借款的数量
        if(!empty($regCountList)){
            $condition .= ' and `status` in (2,3,4,5)';
            foreach ($regCountList as $item) {
                $newCondition = $condition.' and user_id in('.trim($item['user_id'],',').')';
                $channelLoanCountModel = new ChannelLoanCount();
                $data = $channelLoanCountModel->getData('date_time = "'.$date.'" and source_id = '.$item['source_id']);
                if($data){
                    $data->loan_num = $userLoanOrderModel->getCountLoan($newCondition);//查询渠道当天用户成功借款的数量
                    $data->loan_money = $userLoanOrderModel->getLoanTotalMoney($newCondition);//获取借款总金额
                    $data->reg_num = $item['reg_num'];
                    $data->source_id = $item['source_id'];
                    $data->save();
                }else{
                    $channelLoanCountModel->loan_num = $userLoanOrderModel->getCountLoan($newCondition);//查询渠道当天用户成功借款的数量
                    $channelLoanCountModel->loan_money = $userLoanOrderModel->getLoanTotalMoney($newCondition);//获取借款总金额
                    $channelLoanCountModel->reg_num = $item['reg_num'];
                    $channelLoanCountModel->source_id = $item['source_id'];
                    $channelLoanCountModel->date_time = $date;
                    $channelLoanCountModel->save();
                }
            }
        }
    }
}
