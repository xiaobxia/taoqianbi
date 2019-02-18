<?php
namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\ScoreSetting;
use common\models\ScoreStatistics;
use common\models\risk\Rule;
use common\models\UserLoanOrder;
use common\models\risk\RuleCheckReport;
use common\models\mongo\risk\RuleReportMongo;

/**
 *

 *
 * 评分规则
 *
 */
class RiskControlScoreService extends Component
{
    /**
     *

     *
     * 年龄
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['score'=>"100", 'detail' => "描述"]
     */
    public function scoreUserAge($data, $params){

        $loan_person = $data['loan_person'];

        //通过身份证判断年龄
        $now = intval(date('Y',time()));
        $id_card_len = strlen($loan_person->id_number);
        if($id_card_len == 18){
            $year = (int)substr($loan_person->id_number,6,4);
            $age = $now - $year;
        }elseif($id_card_len == 15){
            $year = "19" . substr($loan_person->id_number,6,2);
            $age = $now - $year;
        }else{
            $age = -1;
        }

        $score = $this->getScore($age, $params);

        return ['score' => $score, 'detail' => $age > 0 ? '身份证年龄为' . $age : '身份证未能解析年龄' . $loan_person->id_number];
    }

    /**
     *

     *
     * 性别
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['score'=>"100", 'detail' => "描述"]
     */
    public function scoreUserGender($data, $params){

        $loan_person = $data['loan_person'];

        //通过身份证判断性别
        $id_card_len = strlen($loan_person->id_number);
        if($id_card_len == 18){
            $n = (int)substr($loan_person->id_number,-2,1);
            if ($n % 2 == 1) {
                $gender = "男";
            }else{
                $gender = "女";
            }
        }elseif($id_card_len == 15){
            $n = (int)substr($loan_person->id_number,-1,1);
            if ($n % 2 == 1) {
                $gender = "男";
            }else{
                $gender = "女";
            }
        }else{
            $gender = -1;
        }

        $score = $this->getScore($gender, $params);

        return ['score' => $score, 'detail' => $gender != -1 ? '身份证性别为' . $gender : '身份证未能解析性别' . $loan_person->id_number];
    }

    /**
     *

     *
     * 统一的获取得分函数
     *
     * param    string/int/...            target
     * param    array                     params
     * return   int                       score
     */
    private function getScore($target, $params){

        if (empty($params['items'])) {
            return $params['else_score'];
        }

        $compare = isset( $params['compare'] ) ? $params['compare'] : 'equal';

        foreach ($params['items'] as $item) {
            if($this->inCodition($params['type'], $target, $item['condition'], $compare)){
                return $item['score'];
            }
        }

        return $params['else_score'];

    }

    private function inCodition($type, $target, $condition, $compare){
        if($type == Rule::P_TYPE_SECTION){
            return $condition[0] <= $target && $condition[1] >= $target;
        }else if($type == Rule::P_TYPE_ARRAY){
            if ($compare == 'equal') {
                return in_array($target, $condition);
            }elseif($compare == 'inside'){
                foreach ($condition as $value) {
                    if (strpos($value, $target) !== false) {
                        return true;
                    }
                }
                return false;
            }elseif($compare == 'include'){
                foreach ($condition as $value) {
                    if (strpos($target, $value) !== false) {
                        return true;
                    }
                }
                return false;
            }elseif($compare == 'similar'){
                foreach ($condition as $value) {
                    if (strpos($target, $value) !== false || strpos($value, $target) !== false) {
                        return true;
                    }
                }
                return false;
            }
            return false;
        }else{
            return false;
        }
    }

    public function score($r_id, $p_id, $params){
        $report = RuleCheckReport::find()->where(['r_id' => $r_id, 'o_id' => $p_id, 'type' => RuleCheckReport::TYPE_VALUE])->orderBy("id desc")->limit(1)->one();
        if (empty($report)) {
            return ['score' => -1, 'detail' => "未获得该项特征", 'value' => -1];
        }
        $score = $this->getScore($report->value, $params);
        return ['score' => $score, 'value' => $report->value];
    }

    public function scoreMongo($r_id, $params, $report){
        if (empty($report) || empty($report['basic_report'][$r_id])) {
            return ['score' => -1, 'detail' => "未获得该项特征", 'value' => -1];
        }
        $value = isset($report['basic_report'][$r_id]['value']) ? $report['basic_report'][$r_id]['value'] : -1;
        $score = $this->getScore( $value, $params);
        return ['score' => $score, 'value' => $value];
    }
    //  每日更新评分数据
    public function supplementaryDataDaily($tree_id, $set_id, $date, $rate)
    {
        $orders = UserLoanOrder::find()
            ->select('user_id,created_at')->distinct()
            ->where(['>', 'created_at', strtotime($date)])
            ->andwhere(['<', 'created_at', strtotime($date) + 86400])
            ->asArray()
            ->all(Yii::$app->get('db_kdkj_rd'));
        // 信用评分
        $credit_score = [];
        // 欺诈评分
        $fake_score = [];
        if ($tree_id == 1) {
            foreach ($orders as $order) {
                $score = RuleReportMongo::getScore($order['user_id']);
                if ($score[2] != 0 && $score[1] != 0) {
                    $credit_score[] = $score[1];
                }
            }
            arsort($credit_score);
            $credit_score = array_values($credit_score);
            $credit_count = count($credit_score);
            $credit_index = round($credit_count * ($rate / 100));
            $credit_new = array_slice($credit_score, $credit_index-1);
            if (isset($credit_new[0])) {
                $credit = $credit_new[0];
            }
            foreach($credit_score as $k =>$v){
                if($v==$credit){
                    $start_index_credit = $k+1;
                    break;
                }
            }
            foreach ($credit_score as $key => $item) {
                if ($item < $credit) {
                    $end_index_credit = $key;
                    break;
                }
            }
            // 最小占比：
            $small_rate = round($start_index_credit / $credit_count, 4);
            // 最大占比：
            $big_rate = round($end_index_credit / $credit_count, 4);
            $repeat = ScoreStatistics::find()->where(['tree_id'=>$tree_id,'set_id'=>$set_id,'date'=>$date])->one();
            if(!empty($repeat)){
                $repeat->tree_id=$tree_id;
                $repeat->set_id=$set_id;
                $repeat->date=$date;
                $repeat->score =$credit;
                $repeat->small_rate=$small_rate;
                $repeat->big_rate=$big_rate;
                $repeat->save();
            }else{
                $scores = new ScoreStatistics();
                $scores->tree_id = $tree_id;
                $scores->set_id = $set_id;
                $scores->score = $credit;
                $scores->small_rate = $small_rate;
                $scores->big_rate = $big_rate;
                $scores->date = $date;
                $scores->save();
            echo '每日信用评分数据导入完成';
            }
        } elseif ($tree_id == 2) {
            foreach ($orders as $order) {
                $score = RuleReportMongo::getScore($order['user_id']);
                if ($score[2] != 0 && $score[1] != 0) {
                    $fake_score[] = $score[2];
                }
            }
            asort($fake_score);
            $fake_score = array_values($fake_score);
            $fake_count = count($fake_score);
            $fake_index = round($fake_count * ($rate / 100));
            $fake_new = array_slice($fake_score, $fake_index);
            if (isset($fake_new[0])) {
                $fake = $fake_new[0];
            }
            foreach($fake_score as $k => $v){
                if($v == $fake){
                    $start_index_fake =$k+1;
                    break;
                }
            }
            foreach ($fake_score as $key => $item) {
                if ($item > $fake) {
                    $end_index_fake =$key;
                    break;
                }
            }
            // 最小占比：
            $small_rate = round($start_index_fake / $fake_count, 4);
            // 最大占比：
            $big_rate = round($end_index_fake / $fake_count, 4);
            $repeat = ScoreStatistics::find()->where(['tree_id'=>$tree_id,'set_id'=>$set_id,'date'=>$date])->one();
            if(!empty($repeat)){
                $repeat->tree_id=$tree_id;
                $repeat->set_id=$set_id;
                $repeat->date=$date;
                $repeat->score =$fake;
                $repeat->small_rate=$small_rate;
                $repeat->big_rate=$big_rate;
                $repeat->save();
            }else{
                $scores = new ScoreStatistics();
                $scores->tree_id = $tree_id;
                $scores->set_id = $set_id;
                $scores->score = $fake;
                $scores->small_rate = $small_rate;
                $scores->big_rate = $big_rate;
                $scores->date = $date;
                $scores->save();
            echo '每日评分欺诈评分数据';
            }

        }
    }
    // 评分树补充数据
    public function supplementaryData($tree_id, $set_id, $date, $days, $rate)
    {
        echo '补充数据';
        $today_date=time();
        for($a = 0; $a < $days; $a++){
            $max_date =strtotime($date)+86400*$a;
            if($max_date > $today_date){
               break;
            }else{
                $this->supplementaryDataDaily($tree_id, $set_id,date("Y-m-d",strtotime($date) + 86400 * $a), $rate);
            }
        }
        $supplement = ScoreSetting::find()->where(['id' =>$set_id])->one();
        if($supplement){
            $supplement->supplement_status =0;
            $supplement->save();
            if($supplement->save()){
                echo '更新数据完成';
            }
        }

    }
}
