<?php
namespace common\models;

use Yii;
use yii\base\Exception;

class CreditYxzc
{
    const TYPE_LOAN_INFO = 1;
    const TYPE_RISK_LIST = 2;
    const TYPE_ZC_SCORE = 3;
    const TYPE_QUERY_INFO = 4;
    const NEW_TYPE_LOAN_INFO = 5;

    const IS_OVERDUE_0 = 0;//未过期
    const IS_OVERDUE_1 = 1;//已过期


    public static $type_list = [
        self::TYPE_LOAN_INFO => '借款信息查询',
        self::NEW_TYPE_LOAN_INFO => '新借款信息查询',
        self::TYPE_RISK_LIST => '风险名单查询',
        self::TYPE_ZC_SCORE => '致诚分查询',
        self::TYPE_QUERY_INFO => '被查询情况查询',
    ];

    public static $risk_items_map = [
        'riskItemType' => '命中项',
        'riskItemValue' => '命中内容',
        'riskType' => '风险类别',
        'source' => '风险来源',
        'riskTime' => '风险发生时间(最近)',
    ];

    const PRICE_LOAN_INFO = 0;
    const PRICE_RISK_LIST = 0;
    const PRICE_ZC_SCORE = 0;
    const PRICE_QUERY_INFO = 0;

    public static $price_list = [
        CreditYxzc::TYPE_LOAN_INFO => self::PRICE_LOAN_INFO,
        CreditYxzc::TYPE_RISK_LIST => self::PRICE_RISK_LIST,
        CreditYxzc::TYPE_ZC_SCORE => self::PRICE_ZC_SCORE,
        CreditYxzc::TYPE_QUERY_INFO => self::PRICE_QUERY_INFO,
        CreditYxzc::NEW_TYPE_LOAN_INFO => self::PRICE_LOAN_INFO,
    ];


    private function getData(){
        if(empty($this->data)){
            throw new Exception('');
        }
        $data = json_decode($this->data,true);
        if(empty($data)){
            throw new Exception('');
        }
        return $data;
    }

    public function getRisklist(){
        $type = $this::TYPE_RISK_LIST;
        if($this->type != $type){
            throw new Exception('类型错误');
        }
        try{
            $data = $this->getData();
            $risk = $data['riskItems'];
            return $risk;
        }catch(Exception $e){
            return null;
        }
    }

    public function getOverdueCount(){
        $type = $this::TYPE_LOAN_INFO;
        if($this->type != $type){
            throw new Exception('类型错误');
        }
        try{
            $data = $this->getData();
            $overdue = $data['overdue'];
            $loan_info = $data['loanRecords'];
            $current_overdue_count = 0;
            if(count($loan_info) > 0){
                foreach($loan_info as $v){
                    if($v['currentStatus'] == '逾期'){
                        $current_overdue_count += 1;
                    }
                }
            }
            if(empty($overdue)){
                return [
                    '180overdueTimes' => 0,
                    '90overdueTimes' => 0,
                    'overdueTimes' => 0,
                    'currentOverdueCount' => $current_overdue_count
                ];
            }else{
                $overdue['currentOverdueCount'] = $current_overdue_count;
                return $overdue;
            }
        }catch(Exception $e){
            return null;
        }
    }

    public function getLoanRecords(){
        $type = $this::TYPE_LOAN_INFO;
        if($this->type != $type){
            throw new Exception('类型错误');
        }
        try{
            $data = $this->getData();
            $loanRecords = $data['loanRecords'];
            return $loanRecords;
        }catch(Exception $e){
            return null;
        }
    }

    public static function findLatestOne($params,$dbName = null)
    {
        if(is_null($dbName))
            $creditYxzc = self::findByCondition($params)->orderBy('id Desc')->one();
        else
            $creditYxzc = self::findByCondition($params)->orderBy('id Desc')->one(Yii::$app->get($dbName));
        return $creditYxzc;
    }

}