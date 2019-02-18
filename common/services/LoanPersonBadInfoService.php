<?php

namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\models\CreditTd;
use common\models\LoanPerson;
use common\models\CreditZmop;
use common\models\LoanPersonBadInfo;
use common\models\LoanPersonBadInfoLog;

class LoanPersonBadInfoService extends Component
{
    /**
     * @param $list_type
     * @param $person_id
     * @param $name
     * @param $id_number
     * @param $phone
     * @param array $data
     * @return bool
     * 保存用户不良信息
     */
    public function saveBadInfo($list_type,LoanPerson $loanPerson,Array $data,$source,$log_id)
    {
        $person_id = $loanPerson['id'];
        $name = $loanPerson['name'];
        $id_number = $loanPerson['id_number'];
        $phone = $loanPerson['phone'];
        $time = time();
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            foreach ($data as $k => $v) {
                $loanPersonBadInfo = new LoanPersonBadInfo();
                $loanPersonBadInfo->list_type = $list_type;
                $loanPersonBadInfo->log_id = $log_id;
                $loanPersonBadInfo->person_id = $person_id;
                $loanPersonBadInfo->name = $name;
                $loanPersonBadInfo->id_number = $id_number;
                $loanPersonBadInfo->phone = $phone;
                $loanPersonBadInfo->source = $source;
                $loanPersonBadInfo->rule_type = $k;
                $loanPersonBadInfo->value = $v['value'];
                $loanPersonBadInfo->desc = isset($v['desc']) ? $v['desc'] : '';
                $loanPersonBadInfo->create_time = $time;
                $ret = $loanPersonBadInfo->save();
                if (!$ret) {
                    throw new Exception('保存失败');
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error([
                'line' => __LINE__,
                'method' => __METHOD__,
                'class' => __CLASS__,
                'message' => $e
            ]);
            return false;
        }
    }

    /**
     * 匹配同盾灰名单规则
     * @param CreditTd $data
     */
    public function getTdGrayListInfo(CreditTd $data){
        $gray_list = [];
        $data = json_decode($data['data'],true);
        foreach($data['risk_items'] as $v){
            switch($v['item_id']){
                case 1447346:
                    //身份证归属地位于高风险较为集中地区
                    $gray_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $gray_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447496:
                    //3个月内申请人在多个平台申请借款

                    if($v['item_detail']['platform_count'] >= 3){
                        $gray_list[$v['item_id']]['value'] = $v['item_detail']['platform_count'];
                        $gray_list[$v['item_id']]['desc'] = $v['item_name'];
                    }
                    break;
                case 1447528:
                    //第一联系人手机号命中同盾虚假号码或通信小号库_近亲
                    $gray_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $gray_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447546:
                    //第一联系人手机号命中网贷黑名单_一般联系人
                    $gray_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $gray_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447548:
                    //第一联系人手机号命中贷款黑中介库_一般联系人
                    $gray_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $gray_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
            }
        }
        return $gray_list;
    }
    /**
     * 匹配同盾黑名单规则
     * @param CreditTd $data
     */
    public function getTdBlacklistInfo($data){
        $black_list = [];
        $data = json_decode($data['data'],true);
        if($data['final_score'] == 100){
            $black_list[1000000]['value'] = 100;
            $black_list[1000000]['desc'] = '同盾分';
        }
        foreach($data['risk_items'] as $v){
            switch($v['item_id']){
                case 1447348:
                    //身份证命中法院失信名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447350:
                    //身份证命中犯罪通缉名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447352:
                    //身份证命中法院执行名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447354:
                    //身份证号对应人存在助学贷款逾期历史
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447356:
                    //身份证命中网贷黑名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447408:
                    //手机号命中网贷黑名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447422:
                    //邮箱命中网贷黑名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447360:
                    //身份证命中汽车租赁黑名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447362:
                    //身份证命中法院结案名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447366:
                    //身份证命中失联名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447378:
                    //身份证命中公司法人失信名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447386:
                    //身份证命中失信还款名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447382:
                    //身份证命中欠税名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447358:
                    //身份证命中同盾欺诈高级灰名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447392:
                    //手机号命中同盾虚假号码库
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447394:
                    //手机号命中同盾通信小号库
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447396:
                    //手机号命中同盾诈骗骚扰库
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447402:
                    //手机号命中同盾欺诈高级灰名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447404:
                    //手机号命中贷款黑中介库
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447410:
                    //手机号命中汽车租赁黑名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447412:
                    //手机号命中公司法人失信名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447414:
                    //手机号命中失信还款名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447416:
                    //手机号命中失联名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447424:
                    //邮箱命中同盾欺诈高级灰名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447430:
                    //邮箱命中失信还款名单
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447468:
                    //地址信息命中失信名单证据库
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447454:
                    //单位名称疑似中介关键词
                    $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                    $black_list[$v['item_id']]['desc'] = $v['item_name'];
                    break;
                case 1447480:
                    //1个月内设备或身份证或手机号申请次数过多
                    if(isset($v['item_detail']['platform_count'])){
                        if($v['item_detail']['platform_count'] >= 10){
                            $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                            $black_list[$v['item_id']]['desc'] = $v['item_name'];
                        }
                    }

                    break;
                case 1447482:
                    //1天内设备使用过多的身份证或手机号进行申请
                    if(isset($v['item_detail']['platform_count'])){
                        if($v['item_detail']['platform_count'] >= 10){
                            $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                            $black_list[$v['item_id']]['desc'] = $v['item_name'];
                        }
                    }

                    break;
                case 1447490:
                    //1个月内身份证使用过多设备进行申请
                    if(isset($v['item_detail']['platform_count'])){
                        if($v['item_detail']['platform_count'] >= 10){
                            $black_list[$v['item_id']]['value'] = json_encode($v['item_detail']);
                            $black_list[$v['item_id']]['desc'] = $v['item_name'];
                        }
                    }
                    break;
                case 1447494:
                    //1个月内申请人在多个平台申请借款
                    if(isset($v['item_detail']['platform_count'])){
                        if($v['item_detail']['platform_count'] >= 20){
                            $black_list[$v['item_id']]['value'] = $v['item_detail']['platform_count'];
                            $black_list[$v['item_id']]['desc'] = $v['item_name'];
                        }
                    }

                    break;
            }
        }
        return $black_list;
    }
    /**
     * @param array $data
     * @return array
     * 匹配芝麻信用黑名单信息
     */
    public function getZmBlacklistInfo(CreditZmop $data){
        $black_list = [];
        //行业关注名单
        if($data['watch_matched'] == 2){
            $watch = json_encode($data['watch_info'],true);
            if(!empty($watch)){
                $black_list[2000]['value'] = count($watch);
                $black_list[2000]['desc'] = '黑名单数量';
            }
        }

        if(!empty($data['das_info'])){
            $das = json_decode($data['das_info'],true);
            foreach($das as $v){
                switch($v['key']) {
                    case 'ovd_order_cnt_3m_m1_status':
                        if ($v['value'] == 'Y') {
                            $black_list[2201]['value'] = 1;
                            $black_list[2201]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_6m_m1_status':
                        //最近六个月 M1 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2202]['value'] = 1;
                            $black_list[2202]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_12m_m1_status':
                        //最近一年 M1 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2203]['value'] = 1;
                            $black_list[2203]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_12m_m3_status':
                        //最近一年 M3 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2204]['value'] = 1;
                            $black_list[2204]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_12m_m6_status':
                        //最近一年 M6 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2205]['value'] = 1;
                            $black_list[2205]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_2y_m3_status':
                        //最近两年 M3 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2206]['value'] = 1;
                            $black_list[2206]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_2y_m6_status':
                        //最近两年 M6 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2207]['value'] = 1;
                            $black_list[2207]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_5y_m3_status':
                        //最近五年 M3 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2208]['value'] = 1;
                            $black_list[2208]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_5y_m6_status':
                        //最近五年 M6 状态
                        if ($v['value'] == 'Y') {
                            $black_list[2209]['value'] = 1;
                            $black_list[2209]['desc'] = '有';
                        }
                        break;

                    case 'ovd_order_cnt_1m':
                        //最近一个月逾期总笔数
                        if ($v['value'] != '01') {
                            $black_list[2210]['value'] = $v['value'];
                            $black_list[2210]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                        }
                        break;

                    case 'ovd_order_amt_1m':
                        //最近一个月逾期总金额
                        if ($v['value'] != '01') {
                            $black_list[2211]['value'] = $v['value'];
                            $black_list[2211]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                        }
                        break;

                    case 'ovd_order_cnt_3m':
                        //最近三个月逾期总笔数
                        if (!in_array($v['value'], ['01', '02'])) {
                            $black_list[2212]['value'] = $v['value'];
                            $black_list[2212]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                        }
                        break;

                    case 'ovd_order_amt_3m':
                        //最近三个月逾期总金额
                        if (!in_array($v['value'], ['01', '02'])) {
                            $black_list[2213]['value'] = $v['value'];
                            $black_list[2213]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                        }
                        break;

                    case 'ovd_order_cnt_6m':
                        //最近六个月逾期总笔数
                        if (!in_array($v['value'], ['01', '02'])) {
                            $black_list[2214]['value'] = $v['value'];
                            $black_list[2214]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                        }
                        break;

                    case 'ovd_order_amt_6m':
                        //最近六个月逾期总金额
                        if (!in_array($v['value'], ['01', '02'])) {
                            $black_list[2215]['value'] = $v['value'];
                            $black_list[2215]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                        }
                        break;

                    case 'adr_stability_days':
                        //地址稳定天数
                        if (in_array($v['value'], ['01', '02'])) {
                            $black_list[2216]['value'] = $v['value'];
                            $black_list[2216]['desc'] = CreditZmop::$adr_stability_days_map[$v['value']];
                        }
                        break;

                    case 'use_mobile_2_cnt_1y':
                        //最近一年使用手机号码数
                        if (!in_array($v['value'], ['01', '02', '#'])) {
                            $black_list[2217]['value'] = $v['value'];
                            $black_list[2217]['desc'] = CreditZmop::$use_mobile_2_cnt_1y_map[$v['value']];
                        }
                        break;

                    case 'mobile_fixed_days':
                        //手机号稳定天数
                        if (in_array($v['value'], ['01', '02'])) {
                            $black_list[2218]['value'] = $v['value'];
                            $black_list[2218]['desc'] = CreditZmop::$mobile_fixed_days_map[$v['value']];
                        }
                        break;
                }
            }
        }

        return $black_list;
    }

    /**
     * @param array $data
     * @return array
     * 匹配芝麻信用灰名单信息
     */
    public function getZmGraylistInfo(CreditZmop $data){
        $gray_list = [];
        //DAS
        $das = json_decode($data['das_info'],true);
        //最近三个月 M1 状态
        foreach($das as $v){
            switch($v['key']){
                case 'ovd_order_cnt_3m_m1_status':
                    if($v['value'] == 'Y'){
                        $gray_list[2201]['value'] = 1;
                        $gray_list[2201]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_6m_m1_status':
                    //最近六个月 M1 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2202]['value'] = 1;
                        $gray_list[2202]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_12m_m1_status':
                    //最近一年 M1 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2203]['value'] = 1;
                        $gray_list[2203]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_12m_m3_status':
                    //最近一年 M3 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2204]['value'] = 1;
                        $gray_list[2204]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_12m_m6_status':
                    //最近一年 M6 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2205]['value'] = 1;
                        $gray_list[2205]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_2y_m3_status':
                    //最近两年 M3 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2206]['value'] = 1;
                        $gray_list[2206]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_2y_m6_status':
                    //最近两年 M6 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2207]['value'] = 1;
                        $gray_list[2207]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_5y_m3_status':
                    //最近五年 M3 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2208]['value'] = 1;
                        $gray_list[2208]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_5y_m6_status':
                    //最近五年 M6 状态
                    if($v['value'] == 'Y'){
                        $gray_list[2209]['value'] = 1;
                        $gray_list[2209]['desc'] = '有';
                    }
                    break;

                case 'ovd_order_cnt_1m':
                    //最近一个月逾期总笔数
                    if($v['value'] != '01'){
                        $gray_list[2210]['value'] = $v['value'];
                        $gray_list[2210]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                    }
                    break;

                case 'ovd_order_amt_1m':
                    //最近一个月逾期总金额
                    if($v['value'] != '01'){
                        $gray_list[2211]['value'] = $v['value'];
                        $gray_list[2211]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                    }
                    break;

                case 'ovd_order_cnt_3m':
                    //最近三个月逾期总笔数
                    if(!in_array($v['value'],['01','02'])){
                        $gray_list[2212]['value'] = $v['value'];
                        $gray_list[2212]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                    }
                    break;

                case 'ovd_order_amt_3m':
                    //最近三个月逾期总金额
                    if(!in_array($v['value'],['01','02'])){
                        $gray_list[2213]['value'] = $v['value'];
                        $gray_list[2213]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                    }
                    break;

                case 'ovd_order_cnt_6m':
                    //最近六个月逾期总笔数
                    if(!in_array($v['value'],['01','02'])){
                        $gray_list[2214]['value'] = $v['value'];
                        $gray_list[2214]['desc'] = CreditZmop::$ovd_order_cnt_map[$v['value']];
                    }
                    break;

                case 'ovd_order_amt_6m':
                    //最近六个月逾期总金额
                    if(!in_array($v['value'],['01','02'])){
                        $gray_list[2215]['value'] = $v['value'];
                        $gray_list[2215]['desc'] = CreditZmop::$ovd_order_amt_map[$v['value']];
                    }
                    break;

                case 'adr_stability_days':
                    //地址稳定天数
                    if(in_array($v['value'],['01','02'])){
                        $gray_list[2216]['value'] = $v['value'];
                        $gray_list[2216]['desc'] = CreditZmop::$adr_stability_days_map[$v['value']];
                    }
                    break;

                case 'use_mobile_2_cnt_1y':
                    //最近一年使用手机号码数
                    if(!in_array($v['value'],['01','02','#'])){
                        $gray_list[2217]['value'] = $v['value'];
                        $gray_list[2217]['desc'] = CreditZmop::$use_mobile_2_cnt_1y_map[$v['value']];
                    }
                    break;

                case 'mobile_fixed_days':
                    //手机号稳定天数
                    if(in_array($v['value'],['01','02'])){
                        $gray_list[2218]['value'] = $v['value'];
                        $gray_list[2218]['desc'] = CreditZmop::$mobile_fixed_days_map[$v['value']];
                    }
                    break;

            }
        }

//        $score = $data['zm_score'];
//        if($score <= 600){
//            $gray_list[2219]['value'] = $score;
//        }

        return $gray_list;
    }

    /**
     * @param $data
     * @return array
     * 获取用户蜜罐黑名单信息
     */
    public function getMgBlacklistInfo($data,$user_name){
        $black_list = [];
        $user_blacklist = $data['user_blacklist'];  //用户黑名单
        //检查是否有被标记黑名单分类
        if(!empty($user_blacklist['blacklist_category'])){
            $black_list[1000]['value'] = count($user_blacklist['blacklist_category']);
            $black_list[1000]['desc'] = '被标记的黑名单分类数';
        }
        //检查手机和姓名是否在黑名单中
        if($user_blacklist['blacklist_name_with_phone']){
            $black_list[1001]['value'] = 1;
            $black_list[1001]['desc'] = '手机和姓名在黑名单中';
        }
        //身份证和姓名是否在黑名单
        if($user_blacklist['blacklist_name_with_idcard']){
            $black_list[1002]['value'] = 1;
            $black_list[1002]['desc'] = '身份证和姓名在黑名单中';
        }
        //机构查询历史
        $user_searched_history_by_orgs = $data['user_searched_history_by_orgs'];
        if(!empty($user_searched_history_by_orgs)){
            $this_month = date('Y-m',time());
            $last_month = date('Y-m',strtotime('last month'));
            $org_query_desc = [];
            foreach($user_searched_history_by_orgs as $v){
                //匹配非本机构查询，且查询时间在本月和上月的数据
                if( !$v['org_self'] && (preg_match("/^$this_month/",$v['searched_date']) || preg_match("/^$last_month/",$v['searched_date'])) )
                {
                    $org_query_desc[] = $v['searched_org'];
                }
            }
            if(count($org_query_desc) >= 15 ){
                $black_list[1003]['value'] = count($org_query_desc);
                $black_list[1003]['desc'] = '近1个月内被机构查询次数';
            }
        }
        //身份证存疑
        $user_idcard_suspicion = $data['user_idcard_suspicion'];
        if(!empty($user_idcard_suspicion['idcard_with_other_names'])){
            $idcard_with_other_names_desc = [];
            foreach($user_idcard_suspicion['idcard_with_other_names'] as $v){
                if(!preg_match("/$user_name/",$v['susp_name'])){
                    $idcard_with_other_names_desc[] = $v['susp_name'];
                }
            }
            if(!empty($idcard_with_other_names_desc)){
                $black_list[1004]['value'] = count($idcard_with_other_names_desc);
                $black_list[1004]['desc'] = '身份证被使用次数';
            }

        }
        return $black_list;
    }

    /**
     * @param $data
     * @return array
     * 匹配用户蜜罐灰名单信息
     */
    public function getMgGraylistInfo($data){
        $gray_list = [];
        //用户注册机构信息
        $user_register_orgs = $data['user_register_orgs'];
        if( 2 <= $user_register_orgs['register_cnt']){
            $gray_list[1201]['value'] = $user_register_orgs['register_cnt'];
            $gray_list[1201]['desc'] = '注册数量';
        }

        //直接联系人在黑名单数量
        $user_gray = $data['user_gray'];
        if(isset($user_gray['contacts_class1_blacklist_cnt']) && $user_gray['contacts_class1_blacklist_cnt'] >= 1){
            $gray_list[1202]['value'] = $user_gray['contacts_class1_blacklist_cnt'];
            $gray_list[1202]['desc'] = '联系人数量';
        }

        //机构查询历史
        $user_searched_history_by_orgs = $data['user_searched_history_by_orgs'];
        if(!empty($user_searched_history_by_orgs)){
            $this_month = time();
            $org_query_desc = [];
            foreach($user_searched_history_by_orgs as $v){
                //匹配非本机构查询，且查询时间在进3个月内
                if(!$v['org_self'])
                {
                    if( (86400 * 30 * 3) <= ($this_month - strtotime($v['searched_date']))){
                        $org_query_desc[] = $v['searched_org'];
                    }
                }
            }
            if(count($org_query_desc) >= 3 ){
                $black_list[1203]['value'] = count($org_query_desc);
                $black_list[1203]['desc'] = '被查询次数';
            }
        }

        //手机存疑
        $user_phone_suspicion = $data['user_phone_suspicion'];  //手机号码存疑
        //存在使用过此手机的其他姓名且最后使用时间在三个月内
        if(!empty($user_phone_suspicion['phone_with_other_names'])){
            $phone_with_other_names_desc = [];
            $time = time();
            foreach($user_phone_suspicion['phone_with_other_names'] as $v){
                if(($time - strtotime($v['susp_updt'])) <= (86400 * 30 * 3)){
                    $phone_with_other_names_desc[] = $v['susp_name'];
                }
            }
            if(count($phone_with_other_names_desc) >= 1){
                $gray_list[1204]['value'] = count($phone_with_other_names_desc);
                $gray_list[1204]['desc'] = '被使用次数';
            }
        }
        //存在使用过此手机的其他身份证且最后使用时间在三个月内
        if(!empty($user_phone_suspicion['phone_with_other_idcards'])){
            $phone_with_other_idcards_desc = [];
            $time = time();
            foreach($user_phone_suspicion['phone_with_other_idcards'] as $v){
                if(($time - strtotime($v['susp_updt'])) <= (86400 * 30 * 3)){
                    $phone_with_other_idcards_desc[] =  $v['susp_name'];
                }
            }
            if(count($user_phone_suspicion) >= 1){
                $gray_list[1205]['value'] = count($user_phone_suspicion);
                $gray_list[1205]['desc'] = '被使用次数';
            }
        }

        //身份证存疑
        $user_idcard_suspicion = $data['user_idcard_suspicion'];
        if(!empty($user_idcard_suspicion['idcard_with_other_names'])){
            $idcard_with_other_names_desc = [];
            $time = time();
            foreach($user_idcard_suspicion['idcard_with_other_names'] as $v){
                if(($time - strtotime($v['susp_updt'])) <= (86400 * 30 * 3)){
                    $idcard_with_other_names_desc[] = $v['susp_name'];
                }
            }
            if(count($idcard_with_other_names_desc) >= 2){
                $gray_list[1206]['value'] = count($idcard_with_other_names_desc);
                $gray_list[1206]['desc'] = '被使用次数';
            }
        }
        return $gray_list;
    }

    //获取中智诚黑名单信息
    public function getZzcBlacklistInfo($data){
        $black_list = [];
        $count = $data['count'];
        if($count > 0){
            $black_list[3000]['value'] = $count;
            $black_list[3000]['desc'] = '黑名单记录大于0条';
        }
        return $black_list;
    }

    //宜信致诚黑名单信息
    public function getYxzcBlacklistInfo($data){
        $black_list = [];
        //风险黑名单
        $info = $data['riskItems'];
        if(count($info) > 0){
            $black_list[4000]['value'] = 1;
            $black_list[4000]['desc'] = '命中风险黑名单';
        }
        return $black_list;
    }

    //宜信致诚逾期信息
    public function getYxzcOverdueInfo($data){
        $black_list = [];
        //当前状态逾期
        $loan_info = $data['loanRecords'];
        $current_overdue_count = 0;
        if(count($loan_info) > 0){
            foreach($loan_info as $v){
                if($v['currentStatus'] == '逾期'){
                    $current_overdue_count += 1;
                }
            }
        }
        if($current_overdue_count > 0 ){
            $black_list[4001]['value'] = 1;
            $black_list[4001]['desc'] = '当前状态逾期';
        }
        return $black_list;
    }

    //华道逾期平台详情
    public function getHdOverduePlatDetail($data){
        $black_list = [];
        //逾期平台详情
        $overdue = $data['DATA'];
        $count = count($overdue);
        if($count > 0){
            $black_list[5000]['value'] = $count;
            $black_list[5000]['desc'] = '逾期数量大于0笔';
        }
        return $black_list;
    }

    //华道黑名单查询
    public function getHdBlacklistInfo($data){
        $black_list = [];
        //黑名单查询
        if(!empty($data['IS_BLACK'])){
            $black_list[5001]['value'] = 1;
            $black_list[5001]['desc'] = "被命中的手机号大于0";
        }
        return $black_list;
    }

    public function createLog($person_id){
        $log = new LoanPersonBadInfoLog();
        $log->gray_count = 0;
        $log->black_count = 0;
        $log->person_id = intval($person_id);
        if($log->save()){
            return $log->id;
        }else{
            return false;
        }

    }

    public function updateLog($id,array $count_list){
        $log = LoanPersonBadInfoLog::findOne($id);
        if(is_null($log)){
            return false;
        }
        $log->gray_count += $count_list['gray_count'];
        $log->black_count += $count_list['black_count'];
        return $log->save();

    }

}
