<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/13
 * Time: 17:36
 */
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
class DailyController extends BaseController {

    /**
     * @name 客服管理-日报数据管理-数据统计/actionDataReport
     */
    public function actionDataReport(){
        $key = 'Daily:DataReport:Variable';
        $lock = \Yii::$app->cache->get($key.'10lock');
        if($lock) {
            echo "请稍后打开，当前页面访问人数较多...";exit();
        }

        $db =Yii::$app->get('db_kdkj_rd_new');
        $add_start = $this->request->get('add_start');
        $add_end = $this->request->get('add_end');
        $condition = ' 1=1 ';
        $condition1 = ' 1=1';
        if (!empty($add_start)) {
            $add_start = strtotime($add_start);
            $condition .= " AND created_at >= '{$add_start}' ";
        }else{
            $add_start = strtotime("today");
            $condition .= " AND created_at >= '{$add_start}' ";
        }
        if (!empty($add_end)) {
            $add_end = strtotime($add_end);
            $condition .= " AND created_at <= '{$add_end}' ";
        }else{
            $add_end = strtotime("today")+86400;
            $condition .= " AND created_at <= '{$add_end}' ";
        }

        $userVerificationTableName = UserVerification::tableName();
        $LoanPersonTableName = LoanPerson::tableName();
        $userLoanOrderRepaymentTableName = UserLoanOrderRepayment::tableName();
        $userLoanOrderTableName = UserLoanOrder::tableName();
        $UserRegisterInfoTableName = UserRegisterInfo::tableName();
        $Rong360LoanOrderTableName = Rong360LoanOrder::tableName();
        $check_info ="'auto shell','backsql','RiskControlController::_csReject','RiskControlController::_fsReject','YgdCheckController::YgdZcCwCheck','YgdRejectController::ApplyToFinancialDebit','机审', 'shell auto', 'self', 'backsql', 'auto_trail', 'auto_review', 'auto shell'";
        $all_reg_num = \Yii::$app->cache->get($key . '10');
//        $all_reg_num = '';
        if(!$all_reg_num) {
            \Yii::$app->cache->set($key.'10lock', 1);
            #总用户数/绑卡用户总数/运营商认证总数/实名认证用户总数/紧急联系人认证总数/芝麻认证用户总数/全要素认证用户数
            $verification_sql = "SELECT COUNT(*) AS all_reg_num,
                                SUM(IF(real_bind_bank_card_status, 1, 0)) AS bind_card_num,
                                SUM(IF(real_jxl_status, 1, 0)) AS yys_num,
                                SUM(IF(real_verify_status, 1, 0)) AS realname_num,
                                SUM(IF(real_contact_status, 1, 0)) AS contacts_list_num,
                                SUM(IF(real_zmxy_status, 1, 0)) AS zmxy_num,
                                SUM(IF(real_verify_status=1 AND real_zmxy_status=1 AND real_jxl_status=1 AND real_contact_status=1 AND real_bind_bank_card_status=1, 1, 0)) as all_verif_num
                                FROM {$userVerificationTableName}
                                WHERE id>=0";
            $all_reg_num = $db->createCommand($verification_sql)->queryOne();

            #今日注册用户数
            $info_sql = "SELECT COUNT(*) AS reg_num FROM {$UserRegisterInfoTableName} WHERE id>=0 AND {$condition}";
            $info = $db->createCommand($info_sql)->queryOne();
            $all_reg_num['reg_num'] = $info['reg_num'];;

            #当日放款笔数/当日放款金额/累计放款笔数/累计放款金额
            $sql = "SELECT
                    SUM(IF(loan_time >= $add_start AND loan_time < $add_end,1,0)) AS loan_num,
                    SUM(IF(loan_time >= $add_start AND loan_time < $add_end,principal,0)) AS loan_money,
                    COUNT(id) AS all_loan_num,
                    sum(principal) AS all_loan_money
                    FROM {$userLoanOrderRepaymentTableName}";
            $loan_money = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['loan_money'] = $loan_money['loan_money'];
            $all_reg_num['loan_num'] = $loan_money['loan_num'];
            $all_reg_num['all_loan_num'] = $loan_money['all_loan_num'];
            $all_reg_num['all_loan_money'] = $loan_money['all_loan_money'];

            #放款中笔数/放款中总金额
            $sql = "SELECT
                    COUNT(o.id) AS pay_num,
                    SUM(o.money_amount) AS pay_money
                    FROM {$userLoanOrderTableName} AS o
                    WHERE o.status = " . UserLoanOrder::STATUS_PAY;
            $pay_money = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['pay_money'] = $pay_money['pay_money'];
            $all_reg_num['pay_num'] = $pay_money['pay_num'];

            #放款失败笔数/放款失败金额
            $sql = "SELECT
                    COUNT(o.id) AS fail_num,
                    SUM(o.money_amount) AS fail_money
                    FROM {$userLoanOrderTableName} AS o
                    WHERE o.status = " . UserLoanOrder::STATUS_PENDING_LOAN_CANCEL;
            $fail_money = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['fail_money'] = $fail_money['fail_money'];
            $all_reg_num['fail_num'] = $fail_money['fail_num'];

            #今日机审订单数
//            $sql = "SELECT
//                    COUNT(id) AS today_check_num
//                    FROM {$userLoanOrderTableName}
//                    WHERE  trail_time >={$add_start}
//                    AND trail_time<{$add_end}
//                    AND operator_name IN($check_info) ";
//            $today_check_num = $db->createCommand($sql)->queryOne();
//            unset($sql);
//            $all_reg_num['today_check_num'] = $today_check_num['today_check_num'];

            #今日机审通过订单数
//            $sql = "SELECT COUNT(a.id) AS pas_today_check_num
//                    FROM {$userLoanOrderRepaymentTableName} AS a
//                    LEFT JOIN {$userLoanOrderTableName} AS b ON a.order_id = b.id
//                    WHERE b.operator_name in($check_info)
//                    AND b.trail_time>={$add_start}
//                    AND b.trail_time<{$add_end}
//                    AND a.created_at>={$add_start}
//                    AND a.created_at<{$add_end}";
//            $pas_today_check_num = $db->createCommand($sql)->queryOne();
//            unset($sql);
//            $all_reg_num['pas_today_check_num'] = $pas_today_check_num['pas_today_check_num'];

            #今日老用户申请数
            $sql = "SELECT
                    COUNT(o.user_id) AS today_old_apply_num
                    FROM {$userLoanOrderTableName} o
                    LEFT JOIN {$LoanPersonTableName} p
                    ON p.id = o.user_id
                    WHERE o.is_first = 0
                    AND p.customer_type = 1
                    AND o.created_at >=$add_start
                    AND o.created_at<$add_end";
            $today_old_apply_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['today_old_apply_num'] = $today_old_apply_num['today_old_apply_num'];

            #今日老用户放款笔数
            $sql = "SELECT COUNT(DISTINCT a.user_id) AS pas_old_check_num
                    FROM {$userLoanOrderRepaymentTableName} AS a
                    INNER JOIN tb_user_loan_order_repayment AS b
                    ON a.user_id = b.user_id
                    WHERE b.created_at <$add_start
                    AND b.status=4
                    AND a.created_at>=$add_start
                    AND a.created_at<$add_end;";
            $pas_old_check_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['pas_old_check_num'] = $pas_old_check_num['pas_old_check_num'];

            #今日新用户申请数
            $sql = "SELECT COUNT(o.user_id) AS today_apply_num
                    FROM {$userLoanOrderTableName} o
                    WHERE o.id > 0
                    AND o.id NOT IN (
                        SELECT order_id
                        FROM {$Rong360LoanOrderTableName}
                        WHERE  status = 80
                    )
                    AND o.created_at >= $add_start
                    AND o.created_at < $add_end";
            $today_apply_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['today_new_apply_num'] = $today_apply_num['today_apply_num'] - $all_reg_num['today_old_apply_num'];

            #今日新用户放款数
            $sql = "SELECT
                    COUNT(o.user_id) AS pas_new_check_num
                    FROM {$userLoanOrderTableName} o
                    LEFT JOIN {$userLoanOrderRepaymentTableName} r
                    ON o.id = r.order_id
                    WHERE  r.created_at>=$add_start
                    AND r.created_at<$add_end
                    AND o.is_first = 1";
            $pas_new_check_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['pas_new_check_num'] = $pas_new_check_num['pas_new_check_num'];

            #今日老用户已审数
            $sql = "SELECT
                    COUNT(DISTINCT user_id) AS today_old_check_num
                    FROM {$userLoanOrderTableName}
                    WHERE operator_name IN($check_info)
                    AND user_id IN(
                      SELECT user_id
                      FROM tb_user_loan_order_repayment
                      WHERE created_at <$add_start
                      AND status=4
                    )
                    AND created_at>=$add_start
                    AND created_at<$add_end";
            $today_old_check_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['today_old_check_num'] = $today_old_check_num['today_old_check_num'];

            #今日新用户已审数
            $sql = "SELECT
                    COUNT(DISTINCT user_id) AS today_new_check_num
                    FROM {$userLoanOrderTableName}
                    WHERE operator_name IN($check_info)
                    AND user_id NOT IN (
                      SELECT user_id
                      FROM tb_user_loan_order
                      WHERE created_at <$add_start
                    )
                    AND created_at>=$add_start
                    AND created_at<$add_end ";
            $today_new_check_num = $db->createCommand($sql)->queryOne();
            unset($sql);
            $all_reg_num['today_new_check_num'] = $today_new_check_num['today_new_check_num'];
            \Yii::$app->cache->set($key . '10', $all_reg_num, 1800);
            \Yii::$app->cache->set($key.'10lock', null);
        }
        return $this->render('data-report', array(
            'all_reg_num' => $all_reg_num,
        ));
    }
    /**
     * @name 客服管理-日报数据管理-日报/actionDailyReport
     */
    public function actionDailyReport(){
        $condition = $this->actionFilter();
        $query = DailyData::find()->where($condition)->orderBy([DailyData::tableName().'.date_time'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd_new'))]);
        $pages->pageSize = 15;
        $daily_data = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd_new'));
        $view = 'daily-report';

        return $this->render($view, array(
            'daily_data' => $daily_data,
            'pages' => $pages,
        ));
    }

    /**
     * @name 客服管理-日报数据管理-数据统计字段词典/actionDailyAttribute
     */
    public function actionDailyAttribute(){
        return $this->render('daily-attribute', array());
    }
    public function actionFilter(){
        $condition = "1 = 1 ";
        if($this->request->get('search_submit')) {        //过滤
            $search = $this->request->get();

            if(!empty($search['begintime'])) {

                $condition .= " AND ".DailyData::tableName().".date_time >= '".$search['begintime']."'";

            }
            if(!empty($search['endtime'])) {

                $condition .= " AND ".DailyData::tableName().".date_time <= '".$search['endtime']."'";

            }
        }
        return $condition;
    }

    /**
     * @name 最近十天逾期滚动率
     */

    public function actionScrollRate()
    {
        ini_set("memory_limit","-1");
        $time = date('Y-m-d',time());
        $time = strtotime($time);
        $loan_time_10 = $time -(23*86400);
        $loan_time = $time - (13*86400);
        $condition = ' l.loan_time>='.$loan_time_10 . ' AND l.loan_time<='.$loan_time;
        $user_loan_order_data = UserLoanOrderRepayment::find()
            ->from(UserLoanOrderRepayment::tableName() . 'as r')
            ->innerJoin(UserLoanOrder::tableName() . 'as l', 'r.order_id=l.id')
            ->where($condition)
            ->select('r.plan_fee_time,r.order_id,r.true_repayment_time,r.created_at,r.status,r.is_overdue,r.overdue_day,l.loan_method,l.loan_term,l.loan_time')
            ->orderBy(['l.loan_time' => SORT_DESC])->asArray()->all();
        $data = [];
        foreach ($user_loan_order_data as $k=>$value)
        {
            $data[date('Y-m-d',$value['loan_time'])][$k] = $value;
            $data[date('Y-m-d',$value['loan_time'])]['all_count'] = count($data[date('Y-m-d',$value['loan_time'])]);
            if(!isset($data[date('Y-m-d',$value['loan_time'])]['no_count']))
            {
                $data[date('Y-m-d',$value['loan_time'])]['no_count'] = 0;
            }
            if($value['status'] != UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
            {
                $data[date('Y-m-d',$value['loan_time'])]['no_count'] +=1;
            }
            $data[date('Y-m-d',$value['loan_time'])]['yes_count'] = $data[date('Y-m-d',$value['loan_time'])]['all_count'] - $data[date('Y-m-d',$value['loan_time'])]['no_count'];
        }
        $time_array = [];
        $time_data = [];
        for ($i=0;$i<=9;$i++)
        {
          $time_array[$i] = date('Y-m-d',$time-($i*86400));
            foreach ($data as $k=>$val)
            {
                $flag = 0;
                $flag2 = 0;
                $flag3 = 0;
                $flag_time = (strtotime($time_array[$i]) -strtotime($k))/86400;
                foreach ($val as $k1=>$val2)
                {
                    if($flag_time >= 14)
                    {
                        if($flag_time == 14 )
                        {
                            if(!empty($val2['true_repayment_time']) && strtotime(date('Y-m-d',$val2['true_repayment_time'])) < (strtotime($time_array[$i])+86400))
                            {
                                $flag2++;
                            }
                            $time_data[$k][$time_array[$i]] = array('all_count'=>$val['all_count'],'yes_count'=>$flag2);
                        }
                        elseif($flag_time > 14)
                        {
                            if (strtotime(date('Y-m-d',$val2['true_repayment_time'])) == strtotime($time_array[$i]) && $val2['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                            {
                                $flag++;
                            }
                            if(!empty($val2['true_repayment_time']) && strtotime(date('Y-m-d',$val2['true_repayment_time'])) < (strtotime($time_array[$i])+86400))
                            {
                                $flag3 ++;
                            }
                            $time_data[$k][$time_array[$i]] = array('all_count'=>$val['all_count'],'yes_count'=>$flag,'today_all_yes_count'=>$flag3);
                        }
                    }
                    else
                    {
                        $time_data[$k][$time_array[$i]] = array('all_count'=>$val['all_count'],'yes_count'=>0);
                    }
                }
            }
        }
        return $this->render('scroll-rate',[
            'time'=>$time,
            'time_data'=>$time_data,
            'time_array'=>$time_array,
        ]);
    }
}