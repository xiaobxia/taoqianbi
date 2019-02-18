<?php

namespace console\controllers;

use common\models\Channel;
use common\models\LoanPerson;
use common\models\ChannelStatistic;
use Exception;

class ChannelCheckController extends BaseController
{

    /**
     * 渠道数量统计脚本
     */
    public function actionSourceStatistic(){
        try{
            $read_db = \Yii::$app->db_kdkj_rd_new;
            //不统计渠道id
            $not_in = [21];
            $channel = Channel::find()
                ->where(['not in', 'source_id',$not_in])
                ->andWhere(['status'=>1])
                ->select('*')
                ->asArray()
                ->all();

            $start_time = strtotime(date('Y-m-d',time()));
            $end_time = $start_time + 24 * 60 * 60;
            foreach ($channel as $val){
                $source_id=trim($val['source_id']);

                //当天申请借款
                $apply_count = 0;
                $sql1="select count('a.id') as apply from `tb_user_loan_order` a join `tb_user_register_info` b on a.user_id=b.user_id where b.source='{$source_id}'  ";
                $sql1.=" and b.created_at >={$start_time} and b.created_at <={$end_time} ";
                $apply_count = $read_db->createCommand($sql1)->queryScalar();
                //当天成功借款
                $loan_count = 0;
//                $sql2="select count('a.id') as loan from `tb_user_loan_order_repayment` a join `tb_user_register_info` b on a.user_id=b.user_id where b.source='{$source_id}'  ";
//                $sql2.=" and b.created_at >={$start_time} and b.created_at <={$end_time} ";
//                $sql2="select min(a.created_at) as created_at from `tb_user_loan_order_repayment` a join `tb_user_register_info` b on a.user_id=b.user_id where b.source='{$source_id}' group by a.user_id ";
//                $sql2="select count(1) as loan from ({$sql2}) aa where aa.created_at >={$start_time} and aa.created_at <={$end_time}";
                $sql2="select count(1) as loan from `tb_user_loan_order_repayment` a join `tb_user_loan_order` b on a.order_id=b.id join `tb_user_register_info` c on a.user_id=c.user_id where c.source='{$source_id}' ";
                $sql2.=" and b.is_first=1 and  c.created_at >={$start_time} and c.created_at <={$end_time}";
                $loan_count = $read_db->createCommand($sql2)->queryScalar();

                $statistic = ChannelStatistic::find()
                    ->select('*')
                    ->where(['parent_id' => $val['source_id'],'time' => $start_time])
                    ->orderBy('id')
                    ->one();

                if (empty($statistic)){
                    $statistic = new ChannelStatistic();
                    $statistic->parent_id = $val['source_id'];
                    $statistic->subclass_id = 0;
                    $statistic->time = $start_time;
                    $statistic->created_at = time();
                    $statistic->pv = 0;//当天注册
                }

                $statistic->apply_all = intval($apply_count) ?? 0;//当天申请借款
                $statistic->loan_all = intval($loan_count) ?? 0;//当天成功借款
                $statistic->updated_at = time();

                if (!$statistic->save()){
                    throw new Exception('数据更新失败');
                }
                echo '已更新：'.$val['source_id'].
                    '，注册:'.$statistic->pv.'申请:'.$statistic->apply_all.
                    '借款:'.$statistic->loan_all.'还款:'.$statistic->repayment_all.
                    '，日期：'.date('Y-m-d H:i:s',time())."\r\n";
            }

            //2、更新贷超渠道还款笔数（只更新2小时内）
            $start_time = time()-2 * 60 * 60;
            $end_time = time();
            $sql3="select FROM_UNIXTIME(a.created_at,'%Y-%m-%d') as loandate,b.source from `tb_user_loan_order_repayment` a join `tb_user_register_info` b on a.user_id=b.user_id ";
            $sql3.=" where a.status=4 and b.source <> '' and b.source <> 21 and b.source is not null";
            $sql3.=" and a.true_repayment_time > {$start_time} and a.true_repayment_time <={$end_time}";
            $sql3="select * from ({$sql3}) aa group by loandate,source";
            $repayment_data = $read_db->createCommand($sql3)->queryAll();
            if($repayment_data){
                foreach($repayment_data as $item){
                    $loandate_begin=strtotime($item['loandate']);
                    $loandate_end=$loandate_begin+24*60*60;
                    $source=$item['source'];

                    $sql="select count('a.id') as loan from `tb_user_loan_order_repayment` a join `tb_user_register_info` b on a.user_id=b.user_id ";
                    $sql.=" where a.status=4 and b.source={$source} and a.created_at>={$loandate_begin} and a.created_at<{$loandate_end}";
                    $repayment_count = $read_db->createCommand($sql)->queryScalar();

                    $statistic = ChannelStatistic::find()
                        ->select('*')
                        ->where(['parent_id' => $source,'time' => $loandate_begin])
                        ->orderBy('id')
                        ->one();

                    if (empty($statistic)){
                        $statistic = new ChannelStatistic();
                        $statistic->parent_id = $source;
                        $statistic->subclass_id = 0;
                        $statistic->time = $loandate_begin;
                        $statistic->created_at = time();
                        $statistic->pv = 0;//当天注册
                    }

                    $statistic->repayment_all = intval($repayment_count) ?? 0;//当天成功还款
                    $statistic->updated_at = time();

                    if (!$statistic->save()){
                        throw new Exception('数据更新失败');
                    }
                    echo '已更新：'.$source.'还款:'.$statistic->repayment_all.
                        '，日期：'.date('Y-m-d H:i:s',time())."\r\n";
                }
            }
        } catch(Exception $e){
            $msg = "数据更新失败，原因：".$e->getMessage()."\r\n";
            \Yii::error($msg, 'channelstatistic');
            echo $msg;
        }
    }

    /**
     * 更新	2018-11-03 至 2018-11-11 借款数笔数
    **/
    public function actionSourceStatisticLoan(){
        $read_db = \Yii::$app->db_kdkj_rd_new;
        //初始化日期
        $init_date=strtotime('2018-11-03');
        for($i=0;$i<=9;++$i){
            $start_time=strtotime('+'.$i.' day',$init_date);
            $enddate=strtotime('+'.($i+1).' day',$init_date);

            //排除掉渠道21
            $sql2="select count(1) as loan,c.source from `tb_user_loan_order_repayment` a join `tb_user_loan_order` b on b.id=a.order_id join `tb_user_register_info` c on a.user_id=c.user_id where ";
            $sql2.=" b.is_first=1 and  a.created_at >={$start_time} and a.created_at < {$enddate} and c.source <> 21 group by c.source";
            $repayment_data = $read_db->createCommand($sql2)->queryAll();
            if($repayment_data) {
                foreach ($repayment_data as $item) {
                    //渠道id
                    $source=$item['source'];
                    //借款笔数
                    $loan=$item['loan'];

                    $statistic = ChannelStatistic::find()
                        ->select('*')
                        ->where(['parent_id' => $source,'time' => $start_time])
                        ->orderBy('id')
                        ->one();

                    if (empty($statistic)){
                        $statistic = new ChannelStatistic();
                        $statistic->parent_id = $source;
                        $statistic->subclass_id = 0;
                        $statistic->time = $start_time;
                        $statistic->created_at = time();
                        $statistic->pv = 0;//当天注册
                    }

                    $statistic->loan_all = intval($loan) ?? 0;//借款数
                    $statistic->updated_at = time();

                    if (!$statistic->save()){
                        echo date('Y-m-d',$start_time)."，更新借款数：{$loan}失败\n\r";
                    }else{
                        echo date('Y-m-d',$start_time)."，更新借款数：{$loan}成功\n\r";
                    }
                }
            }else{
                echo '抱歉，暂无借款笔数';
            }
        }
    }
}