<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/9 0009
 * Time: 下午 5:58
 */

namespace common\services\statistics;

use Yii;
use common\models\UserOrderLoanCheckLog;
use common\models\StatsGreyAnalysis;
use yii\base\Component;

class GreyAnalysisService extends Component
{

    /**
     * @param string $date 查询日期
     * @param string $tree 策略树名称
     */
    public function index($date, $tree)
    {
        //==============订单金额维度统计===========
        $data['apply_auto']  = UserOrderLoanCheckLog::getLoan($date, $tree, 1, false)['money_amount']; //按金额 自动处理(总量)(单位：元)
        $data['apply_man']   = UserOrderLoanCheckLog::getLoan($date, $tree, 0, false)['money_amount']; //按金额 人工处理(总量)(单位：元)
        $data['apply_total'] = $data['apply_auto'] + $data['apply_man'];                               //按金额 处理总量(单位：元)

        $data['pass_auto']   = UserOrderLoanCheckLog::getLoan($date, $tree, 1, true)['money_amount']; //按金额 机审(通过量)
        $data['pass_man']    = UserOrderLoanCheckLog::getLoan($date, $tree, 0, true)['money_amount']; //按金额 人审(通过量)
        $data['pass_total']  = $data['pass_auto'] + $data['pass_man'];                                //按金额 总量(通过量)
        $data['pass_rate']      = $data['apply_total']>0 ? number_format($data['pass_total']/$data['apply_total']*100, 2).'%' : 0;    //通过率
        $data['pass_rate_auto'] = $data['apply_auto']>0 ? number_format($data['pass_auto']/$data['apply_auto']*100, 2).'%' : 0;    //机审通过率
        $data['pass_rate_man']  = $data['apply_man']>0 ? number_format($data['pass_man']/$data['apply_man']*100, 2).'%' : 0;      //人审通过率

        $data['overdue3_rate']  = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue3_amount_rate'];     //按金额 逾期率3+
        $data['overdue10_rate'] = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue10_amount_rate'];   //按金额 逾期率10+
        $data['overdue30_rate'] = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue30_amount_rate'];  //按金额 逾期率30+
        $data['profit']         = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['profit'];                 //纯利润
        $data['risk_controlling'] = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['risk_controlling'];    //风控系数

        //==============订单数量维度统计============
        $data2['apply_auto']  = UserOrderLoanCheckLog::getLoan($date, $tree, 1, false)['order_count']; //自动处理(总量)
        $data2['apply_man']   = UserOrderLoanCheckLog::getLoan($date, $tree, 0, false)['order_count']; //人工处理(总量)
        $data2['apply_total'] = $data2['apply_auto'] + $data2['apply_man'];                            //申请总量
        $data2['pass_auto']   = UserOrderLoanCheckLog::getLoan($date, $tree, 1, true)['order_count']; //机审(通过量)
        $data2['pass_man']    = UserOrderLoanCheckLog::getLoan($date, $tree, 0, true)['order_count']; //人审(通过量)
        $data2['pass_total']  = $data2['pass_auto'] + $data2['pass_man'];                             //通过总量

        $data2['pass_rate']      = $data2['apply_total']>0 ? number_format($data2['pass_total']/$data2['apply_total']*100, 2).'%' : 0;      //通过率
        $data2['pass_rate_auto'] = $data2['apply_auto']>0 ? number_format($data2['pass_auto']/$data2['apply_auto']*100, 2).'%' : 0;  //机审通过率
        $data2['pass_rate_man']  = $data2['apply_man']>0 ? number_format($data2['pass_man']/$data2['apply_man']*100, 2).'%' : 0;     //人审通过率
        $data2['overdue3_rate']  = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue3_count_rate'];    //按订单 逾期率3+
        $data2['overdue10_rate'] = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue10_count_rate'];   //按订单 逾期率10+
        $data2['overdue30_rate'] = UserOrderLoanCheckLog::getLoan($date, $tree, 2, true)['overdue30_count_rate'];   //按订单 逾期率30+
        $condition = ['stats_date'=> $date, 'version'=>$tree];
        $record = StatsGreyAnalysis::find()->select('id')->where($condition)->exists();

        if($record){
            $attr['stats_amount'] = json_encode($data);   //订单金额维度统计
            $attr['stats_number'] = json_encode($data2);  //订单数量维度统计
            $attr['updated_at']   = time();
            return StatsGreyAnalysis::updateAll($attr, $condition);
        } else {
            $grey = new StatsGreyAnalysis();
            $grey->version = $tree;
            $grey->stats_date = $date;
            $grey->stats_amount = json_encode($data);   //订单金额维度统计
            $grey->stats_number = json_encode($data2);  //订单数量维度统计
            $grey->created_at = $grey->updated_at = time();
            return $grey->insert();
        }

    }
}