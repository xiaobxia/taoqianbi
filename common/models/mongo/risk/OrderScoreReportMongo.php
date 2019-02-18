<?php
namespace common\models\mongo\risk;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * 信用评分报告
 * Class OrderScoreReportMongo
 * @package common\models\mongo\risk
 */
class OrderScoreReportMongo extends ActiveRecord {

    public static function getDb(){
        return Yii::$app->get('mongodb_rule_new');
    }

    public static function collectionName(){
        return 'order_score_report';
    }

    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'order_id',
            'root_ids',
            'identity',
            'is_real', // 0:线上 1:回测
            'reject_roots',
            'reject_detail',
            'basic_report',
            'created_at',
            'updated_at',
            'version',
        ];
    }

    public static function addBasicReport($order, $root_ids, $identity, $rule_id, $result, $is_real, $reject_roots='', $reject_detail = '', $version = ''){
        if(empty($order)){
            return false;
        }

        $order_id = $order->id;
        $user_id = $order->user_id;

        $report = self::find()->where(['identity' => $identity, 'user_id' => $user_id, 'order_id'=>$order_id, 'root_ids' => $root_ids, 'is_real' => $is_real])->one();
        if (empty($report)) {
            $report = new self();
            $report->identity = $identity;
            $report->user_id = $user_id;
            $report->order_id = $order_id;
            $report->root_ids = $root_ids;
            $report->is_real = $is_real;
            $report->created_at = time();
        }

        if ($reject_detail) {
            $report->reject_roots = $reject_roots;
            $report->reject_detail = $reject_detail;
        }

        $report->version = $version;
        $basic_report = empty($report->basic_report) ? [] : $report->basic_report;
        $basic_report[$rule_id] = $result;
        $report->basic_report = $basic_report;
        $report->updated_at = time();

        return $report->save();
    }

    public static function saveBasicReport($order_result, $reject_roots='', $reject_detail = '', $version = '') {
        if (empty($order_result)) {
            return false;
        }
        // $this->identity . "@" . $order->user_id . "@" . $order->id . "@" . $this->root_ids . "@" . $is_real
        foreach ($order_result as $keys => $value) {
            $key_info = explode("@" , $keys);
            if (count($key_info) != 5) {
                return false;
            }

            $identity = $key_info[0];
            $user_id = intval($key_info[1]);
            $order_id = intval($key_info[2]);
            $root_ids = $key_info[3];
            $is_real = intval($key_info[4]);

            $report = self::find()->where([
                'identity' => $identity,
                'user_id' => $user_id,
                'order_id'=> $order_id,
                'root_ids' => $root_ids,
                'is_real' => $is_real,
            ])->one();
            if (empty($report)) {
                $report = new self();
                $report->identity = $identity;
                $report->user_id = $user_id;
                $report->order_id = $order_id;
                $report->root_ids = $root_ids;
                $report->is_real = $is_real;
                $report->created_at = time();
            }

            $basic_report = empty($report->basic_report) ? [] : $report->basic_report;
            if ($reject_detail) {
                $report->reject_roots = $reject_roots;
                $report->reject_detail = $reject_detail;
            }

            foreach ($value as $rule_id => $result) {
                $basic_report[$rule_id] = $result;
            }

            $report->version = $version;
            $report->basic_report = $basic_report;
            $report->updated_at = time();
            if (!$report->save()) {
                return false;
            }
        }

        return true;
    }
}
