<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\ToolsUtil;
use common\models\CardInfo;
use common\models\CreditBr;
use common\models\CreditFaceIdCard;
use common\models\CreditFacePlus;
use common\models\CreditJsqbBlacklist;
use common\models\CreditYx;
use common\models\CreditZmop;
use common\models\loan\LoanCollectionOrder;
use common\models\LoanBlackList;
use common\models\LoanBlacklistDetail;
use common\models\LoanPerson;
use common\models\mongo\mobileInfo\UserPhoneMessageMongo;
use common\models\mongo\risk\MobileContactsReportMongo;
use common\models\risk\Rule;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserLoginUploadLog;
use common\models\UserMobileContacts;
use common\models\UserPhoneMessage;
use common\models\UserQuotaPersonInfo;
use common\models\CreditJxl;
use common\api\RedisQueue;

/**
 * 检验规则
 */
class RiskControlCheckService extends Component {
    const LOW_RISK = 0;
    const MEDIUM_RISK = 1;
    const HIGH_RISK = 2;

    const OTHER = 2;
    const THREE = 3;

    const YES = 1;
    const NO = 0;
    const NULL = -1;

    /**
     * 同盾分
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdScore($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $low = $params['low'];
        $high = $params['high'];
        $score = 0;
        if (isset($td['result_desc']['ANTIFRAUD']['final_score'])) {
            $score = \intval( $td['result_desc']['ANTIFRAUD']['final_score'] );
        }
        return [
            'risk' => ($score > $high) ? self::HIGH_RISK : ($score >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '同盾分为' . $score,
            'value' => $score,
        ];
    }

    /**
     * 1个月内身份证关联设备数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTd1447490($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "1个月内身份证使用过多设备进行申请";
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132964:
                    $detail = $v['risk_detail'];
                    foreach ($detail as $_detail) {
                        if (isset($_detail[ 'platform_count' ])) {
                            $count = $_detail['platform_count'];
                            break;
                        }
                    }
                    break;

                default:
                    break;
            }
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => $detail,
            'value' => $count,
        ];
    }

    /**
     * 7天内设备使用过多的身份证或手机号进行申请 3358067
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTd1447484($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "7天内设备使用过多的身份证或手机号进行申请";
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
            case 21132934:
                $detail = $v['risk_detail'];
                foreach ($detail as $_detail) {
                    foreach ($_detail['frequency_detail_list'] as $value) {
                        if (isset($value['detail']) && preg_match( '/：(\d+)/', $value['detail'], $preg )) {
                            if ($preg[1] > 0) {
                                $count += $preg[1];
                            }
                        }
                    }
                }
                break;

            default:
                break;
            }
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => $detail,
            'value' => $count,
        ];
    }

    /**
     * 7天内身份证关联设备数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTd1447488($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "7天内身份证关联设备数";
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
            case 21132954:
                $detail = $v['risk_detail'];
                foreach ($detail as $_detail) {
                    foreach ($_detail['frequency_detail_list'] as $value) {
                        if (isset($value['detail']) && preg_match( '/7天内身份证关联设备数：(\d+)/', $value['detail'], $preg )) {
                            if ($preg[1] > 0) {
                                $count = $preg[1];
                            }
                            $detail = $value['detail'];
                        }
                    }
                }
                break;

            default:
                break;
            }
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => $detail,
            'value' => intval($count),
        ];
    }

    /**
     * 3个月内申请信息关联多个身份证
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTd1447464($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "3个月内申请信息关联多个身份证";
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132804:
                    $detail = $v['risk_detail'];
                    foreach ($detail as $_detail) {
                        foreach ($_detail['frequency_detail_list'] as $value) {
                            if (preg_match('/3个月家庭地址关联身份证数：(\d+)/', $value['detail'], $preg)) {
                                if ($preg[1] > $count) {
                                    $count = $preg[1];
                                }
                            }

                            if (preg_match('/3个月内邮箱关联身份证数：(\d+)/', $value['detail'], $preg)) {
                                if ($preg[1] > $count) {
                                    $count = $preg[1];
                                }
                            }

                            if (preg_match('/3个月手机号关联身份证数：(\d+)/', $value['detail'], $preg)) {
                                if ($preg[1] > $count) {
                                    $count = $preg[1];
                                }
                            }

                        }
                    }
                    break;

                default:
                    break;
            }
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => $detail,
            'value' => $count,
        ];
    }

    /**
     * 3个月内身份证关联多个申请信息
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTd1447462($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "3个月内身份证关联多个申请信息";
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                    case 21132784:
                    $detail = $v['risk_detail'];
                    foreach ($detail as $_detail) {
                        foreach ($_detail['frequency_detail_list'] as $value) {
                            if (preg_match( '/3个月身份证关联家庭地址数：(\d+)/', $value[ 'detail' ], $preg )) {
                                if ($preg[ 1 ] > $count) {
                                    $count = $preg[ 1 ];
                                }
                            }
                            if (preg_match( '/3个月身份证关联邮箱数：(\d+)/', $value[ 'detail' ], $preg )) {
                                if ($preg[ 1 ] > $count) {
                                    $count = $preg[ 1 ];
                                }
                            }
                            if (preg_match( '/3个月身份证关联手机号数：(\d+)/', $value[ 'detail' ], $preg )) {
                                if ($preg[ 1 ] > $count) {
                                    $count = $preg[ 1 ];
                                }
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => $detail,
            'value' => $count,
        ];
    }

    /**
     * 1个月内申请人在多个平台申请借款 3358103
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdLoansPerMonth($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $data = $td['result_desc']['ANTIFRAUD'];
        $low = $params['low'];
        $high = $params['high'];

        $detail = "1个月内申请人在多个平台申请借款数为0";
        $count = 0;
        $check = false;
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21133084:
                    $detail = $v['risk_detail'];
                    foreach ($detail as $_detail) {
                        if (isset($_detail['platform_count'])) {
                            $count = $_detail['platform_count'];
                            break;
                        }
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => json_encode($detail),
            'value' => $count,
        ];
    }

    /**
     * 7天内申请人在多个平台申请借款 3358101
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdLoansPerWeek($data, $params) {

        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $data = $td['result_desc']['ANTIFRAUD'];
        $low = $params['low'];
        $high = $params['high'];

        $detail = "7天内申请人在多个平台申请借款数为0";
        $count = 0;
        $check = false;
        foreach ($data['risk_items'] as $v) {

            switch ($v['rule_id']) {
            case 21133074:
                $detail = $v['risk_detail'];

                foreach ($detail as $_detail) {
                    if (isset($_detail['platform_count'])) {
                        $count = $_detail['platform_count'];
                        break;
                    }
                }
                break;
            default:
                break;
            }

            if ($check) {
                break;
            }
        }


        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => json_encode($detail),
            'value' => $count,
        ];
    }

    /**
     * 3个月内身份证关联多个申请信息 3358041
     * 3个月身份证关联家庭地址数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdIdCardHomesThreeMonth($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $low = $params['low'];
        $high = $params['high'];

        $check = false;
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132784:
                    $check = true;
                    //3个月内身份证关联家庭住址数
                    foreach ($v['risk_detail'][0]['frequency_detail_list'] as $value) {
                        if (preg_match('/3个月身份证关联家庭地址数：(\d+)/', $value['detail'], $preg)) {
                            $count = $preg[1];
                        }
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => "3个月身份证关联家庭地址数：" . $count,
            'value' => $count,
        ];
    }

    /**
     * 3个月身份证关联手机数
     * 3个月内身份证关联多个申请信息 3358041
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdIdCardPhonesThreeMonth($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $low = $params['low'];
        $high = $params['high'];

        $check = false;
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132784:
                    $check = true;
                    //3个月内身份证关联家庭住址数
                    foreach ($v['risk_detail'][0]['frequency_detail_list'] as $value) {
                        if (preg_match('/3个月身份证关联手机号数：(\d+)/', $value['detail'], $preg)) {
                            $count = $preg[1];
                        }
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => "3个月身份证关联家庭地址数：" . $count,
            'value' => $count,
        ];
    }
    /**---------20180329新规则-------**/
    public function checkTdIdcardFayuanList($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $check = false;
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 714650:
                    $check = true;
                    //身份证命中法院失信名单
                    if(isset($v['risk_detail'][0]['court_details'])){
                        $check = true;
                        $count = count($v['risk_detail'][0]['court_details']);
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => "身份证命中法院失信名单：" . $count,
            'value' => $count,
        ];
    }



    /**
     * @name 身份证命中法院执行名单 714686
     */
    public function checkTdIdcardFayuanBcak($data){
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $check = false;
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 714686:
                    //身份证命中法院执行名单
                    if(isset($v['risk_detail'][0]['court_details'])){
                        $check = true;
                        $arr = $v['risk_detail'][0]['court_details'];
                        $count = count($arr);
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' =>  self::MEDIUM_RISK ,
            'detail' => "身份证命中法院执行名单：" . $count,
            'value' => $count,
        ];
    }

    /**
 * @name 身份证命中信贷逾期名单 714696
 */
    public function checkTdIdcardHdangerList($data){
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $check = false;
        $count = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 714696:
                    //身份证命中法院执行名单
                    if(isset($v['risk_detail'][0]['overdue_details'])){
                        $check = true;
                        $count = count($v['risk_detail'][0]['overdue_details']);
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' =>  self::MEDIUM_RISK ,
            'detail' => "身份证命中信贷逾期名单：" . $count,
            'value' => $count,
        ];
    }

    /**
     * @name 身份证命中高风险关注名单 714702
     */
    public function checkTdGetCourtDishonesty($data){
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $check = false;
        $result = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 714702:
                    //身份证命中高风险关注名单
                    if(isset($v['risk_detail'][0]['grey_list_details'])){
                        $arr = $v['risk_detail'][0]['grey_list_details'];
                        $check = true;
                        $result = isset($arr[0]['risk_level'])?1:0;
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' =>  self::MEDIUM_RISK ,
            'detail' => "身份证命中高风险关注名单：" . $result,
            'value' => $result,
        ];
    }

    /**
     * @name 身份证归属地位于高风险较为集中地区 714702
     */
    public function checkTdIdCardWhere($data){
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $check = false;
        $result = 0;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 714622:
                    //身份证归属地位于高风险较为集中地区
                    if(isset($v['risk_detail'][0]['high_risk_areas'])){
                        $arr = $v['risk_detail'][0]['high_risk_areas'];
                        $check = true;
                        $result = isset($arr)?1:0;
                    }
                    break;
                default:
                    break;
            }
            if ($check) {
                break;
            }
        }

        return [
            'risk' =>  self::MEDIUM_RISK ,
            'detail' => "身份证归属地位于高风险较为集中地区：" . $result,
            'value' => $result,
        ];
    }



    /**---------20180329新规则-------**/

    /**
     * 近6个月月均话费
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPhoneBill($data, $params) {
        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息'];
        if (!empty($data['bill_list'])) {
            $m = 0;
            foreach ($data['bill_list'] as $key => $value) {
                if (!empty($value['amount']) && is_numeric($value['amount'])) {
                    $m += $value['amount'];
                }
            }
            $r = round($m / count($data['bill_list']), 2);
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '近6个月月均话费' . $r . '元', 'value' => $r];
        }

        return $result;
    }

    /**
     * 实名时间限制规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkRealNameTime($data, $params)
    {
        $data = $data['yys'];
        $month = $params['month'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL,
        ];
        if (!empty($data['real_name_time'])) {
            $real_name_month = round((time() - strtotime($data['real_name_time'])) / 86400 / 31, 2);
            $result = [
                'risk' => $real_name_month < $month ? self::HIGH_RISK : self::LOW_RISK,
                'detail' => '运营商：实名绑定时间' . $real_name_month . '月',
                'value' => $real_name_month,
            ];
        }
        return $result;
    }

    /**
     *

     *
     * 黑名单限制规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkBlackList($data, $params)
    {

        $data = $data['jxl'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '聚信立:没有相关信息', 'value' => self::NULL];
        if (!empty($data['application_check'])) {
            $flag = true;
            $hit = "";
            $miss = "";

            if (isset($data['application_check']['0']['check_points'])) {
                foreach ($data['application_check'] as $v) {
                    if ($v['app_point'] == 'id_card') {
                        if ($v['check_points']['financial_blacklist']['arised']) {
                            $flag = false;
                            $hit .= "申请人姓名+身份证是否出现在金融服务类机构黑名单,";
                        } else if ($v['check_points']['court_blacklist']['arised']) {
                            $flag = false;
                            $hit .= "申请人姓名+身份证是否出现在法院黑名单,";
                        }
                    } else if ($v['app_point'] == 'cell_phone') {
                        if ($v['check_points']['financial_blacklist']['arised']) {
                            $flag = false;
                            $hit .= "申请人姓名+手机号码是否出现在金融服务类机构黑名单,";
                        }
                    }
                }
            } else {
                foreach ($data['application_check'] as $v) {
                    if ($v['check_point'] == "申请人姓名+身份证是否出现在法院黑名单"
                        || $v['check_point'] == "申请人姓名+身份证是否出现在金融服务类机构黑名单"
                        || $v['check_point'] == "申请人姓名+手机号码是否出现在金融服务类机构黑名单"
                    ) {
                        if ($v['result'] != "未出现") {
                            $flag = false;
                            $hit .= $v['check_point'] . ",";
                        } else {
                            $miss .= $v['check_point'] . ",";
                        }
                    }
                }
            }

            if ($flag) {
                $result = ['risk' => self::LOW_RISK, 'detail' => '聚信立:未命中以下黑名单:' . $miss, 'value' => self::NO];
            } else {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '聚信立:命中以下黑名单:' . $hit, 'value' => self::YES];
            }
        }
        return $result;
    }

    /**
     *停用

     *
     * 联系人通话检验规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTopContacts($data, $params)
    {

        $data = $data['jxl'];
        $max_fail = $params['max_fail'];
        $limit = $params['limit'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '聚信立:没有相关信息'];

        // To satisfy JXL report version 4.2.
        if (isset($data['application_check']['0']['check_points'])) {
            $fail = 0;
            $success = 0;
            foreach ($data['application_check'] as $v) {
                if ($v['app_point'] == 'contact') {
                    if (preg_match('/按时长计算排名第(\d+)位$/', $v['evidence'], $ranking)) {
                        if (!isset($ranking[1]) || intval($ranking[1]) > $limit) {
                            $fail += 1;
                        } else {
                            $success += 1;
                        }
                    } else {
                        $fail += 1;
                    }
                    break;
                }
            }
            if ($fail <= $max_fail) {
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '联系人通话时长排名' . $limit . '名:超出' . $fail . '人次，达标' . $success . '人次，允许超出' . $max_fail . '人次'
                ];
            } else {
                $result = [
                    'risk' => self::HIGH_RISK,
                    'detail' => $v['evidence']
                ];
            }
        } else

            if (!empty($data['behavior_check'])) {
                $fail = 0;
                $success = 0;
                foreach ($data['behavior_check'] as $v) {
                    if ($v['check_point'] == '与联系人互动情况') {
                        if (preg_match('/按时长计算排名第(\d+)位$/', $v['evidence'], $ranking)) {
                            if (!isset($ranking[1]) || intval($ranking[1]) > $limit) {
                                $fail += 1;
                            } else {
                                $success += 1;
                            }
                        } else {
                            $fail += 1;
                        }
                        break;
                    }
                }
                if ($fail <= $max_fail) {
                    $result = [
                        'risk' => self::LOW_RISK,
                        'detail' => '联系人通话时长排名' . $limit . '名:超出' . $fail . '人次，达标' . $success . '人次，允许超出' . $max_fail . '人次'
                    ];
                } else {
                    $result = [
                        'risk' => self::HIGH_RISK,
                        'detail' => $v['evidence']
                    ];
                }
            }
        return $result;
    }

    /**
     *

     *
     * 第一联系人通话排名
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */

    public function checkFirstContactsRank($data, $params)
    {
        $yys = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $rank = -1;
            $detail = '运营商：未找到与第一联系人互动情况相关信息';
            $info = $data['user_contact'];
            if ($info) {
                $mobile_list = explode(":", $info['mobile']);
                foreach ($yys['contact_list'] as $k => $v) {
                    foreach ($mobile_list as $mobile) {
                        if ($v['phone'] == $mobile && ($rank == -1 || $rank > ($k + 1))) {
                            $detail = "第一联系人通话排名第" . ($k + 1) . "位";
                            $rank = $k + 1;
                        }
                    }
                }
            }
            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $rank
            ];
        }
        return $result;
    }


    /**
     *

     *
     * 第二联系人通话排名
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSecondContactsRank($data, $params)
    {
        $yys = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $rank = -1;
            $detail = '运营商：未找到与第二联系人互动情况相关信息';
            $info = $data['user_contact'];
            if ($info) {
                $mobile_list = explode(":", $info['mobile_spare']);
                foreach ($yys['contact_list'] as $k => $v) {
                    foreach ($mobile_list as $mobile) {
                        if ($v['phone'] == $mobile && ($rank == -1 || $rank > ($k + 1))) {
                            $detail = "第二联系人通话排名第" . ($k + 1) . "位";
                            $rank = $k + 1;
                        }
                    }
                }
            }
            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $rank
            ];
        }
        return $result;
    }

    /**
     *

     *
     * 第一联系人通话次数
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkFirstContactFrequency($data, $params)
    {
        $loan_person = $data['loan_person'];
        $yys = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $t = -1;
            $detail = '运营商：未找到与第一联系人互动情况相关信息';
            if (!empty($yys['common_contactors'][0]['phone'])) {
                $common_contactors = $yys['common_contactors'];
                foreach ($yys['contact_list'] as $k => $v) {
                    if ($v['phone'] == $common_contactors[0]['phone']) {
                        $detail = "第一联系人通话次数为" . $v['talk_cnt'] . '次';
                        $t = $v['talk_cnt'];
                        break;
                    }
                }
            } else {
                // $info = UserContact::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
                $info = $data['user_contact'];
                if ($info) {
                    foreach ($yys['contact_list'] as $k => $v) {
                        if ($v['phone'] == $info['mobile']) {
                            $detail = "第一联系人通话次数为" . $v['talk_cnt'] . '次';
                            $t = $v['talk_cnt'];
                            break;
                        }
                    }
                }
            }
            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $t
            ];
        }
        return $result;

    }

    /**
     *

     *
     * 第二联系人通话次数
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSecondContactsFrequency($data, $params)
    {
        $loan_person = $data['loan_person'];
        $yys = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $t = -1;
            $detail = '运营商：未找到与第二联系人互动情况相关信息';
            if (!empty($yys['common_contactors'][1]['phone'])) {
                $common_contactors = $yys['common_contactors'];
                foreach ($yys['contact_list'] as $k => $v) {
                    if ($v['phone'] == $common_contactors[1]['phone']) {
                        $detail = "第二联系人通话次数为" . $v['talk_cnt'] . '次';
                        $t = $v['talk_cnt'];
                        break;
                    }
                }
            } else {
                // $info = UserContact::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
                $info = $data['user_contact'];
                if ($info) {
                    foreach ($yys['contact_list'] as $k => $v) {
                        if ($v['phone'] == $info['mobile_spare']) {
                            $detail = "第二联系人通话次数为" . $v['talk_cnt'] . '次';
                            $t = $v['talk_cnt'];
                            break;
                        }
                    }
                }
            }
            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $t
            ];
        }
        return $result;

    }

    /**
     *

     *
     * 详单互通个数 第一联系人通话时间
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkFirstContactsTime($data, $params)
    {

        $data = $data['jxl'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];

        $t = -1;
        $detail = '聚信立：未找到与联系人互动情况相关信息';
//        if (isset($data['application_check']['0']['check_points'])) {
//            foreach ($data['application_check'] as $v) {
//                if ($v['app_point'] == "contact") {
//                    if (preg_match('/共([\d|\.]+)分钟/', $v['check_points']['check_mobile'], $time)) {
//                        if (isset($time[1])) {
//                            $t = $time[1];
//                        }
//                        $detail = $v['check_points']['check_mobile'];
//                    }
//                    break;
//                }
//            }
//            $result = [
//                'risk' => self::LOW_RISK,
//                'detail' => $detail,
//                'value' => $t
//            ];
//        } else

            if (!empty($data['behavior_check'])) {
                foreach ($data['behavior_check'] as $v) {
                    if ($v['check_point'] == 'contact_each_other') {
                        if (preg_match('/有([\d|\.]+)个/', $v['evidence'], $time)) {
                            if (isset($time[1])) {
                                $t = $time[1];
                            }
                            $detail = $v['evidence'];
                        }
                        break;
                    }
                }
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => $detail,
                    'value' => intval($t)
                ];
            }

        return $result;
    }

    /**
     *魔蝎停用

     *
     * 第二联系人通话时间
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSecondContactsTime($data, $params)
    {

        $data = $data['jxl'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];

        $t = -1;
        $detail = '聚信立:未找到与联系人互动情况相关信息';
        $flag = 0; // 用于找第二条数据
        if (isset($data['application_check']['0']['check_points'])) {
            foreach ($data['application_check'] as $v) {
                if ($v['app_point'] == "contact") {
                    $flag++;
                    if ($flag == 1) {
                        continue;
                    }
                    if (preg_match('/共([\d|\.]+)分钟/', $v['check_points']['check_mobile'], $time)) {
                        if (isset($time[1])) {
                            $t = $time[1];
                        }
                        $detail = $v['check_points']['check_mobile'];
                    }
                }
                break;
            }
            if ($flag == 1) {
                $detail = '聚信立:未找到与第二联系人互动情况相关信息';
            }

            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $t
            ];
        } else

            if (!empty($data['behavior_check'])) {
                foreach ($data['behavior_check'] as $v) {
                    if ($v['check_point'] == '与联系人互动情况') {
                        $flag++;
                        if ($flag == 1) {
                            continue;
                        }
                        if (preg_match('/共([\d|\.]+)分钟/', $v['evidence'], $time)) {
                            if (isset($time[1])) {
                                $t = $time[1];
                            }
                            $detail = $v['evidence'];
                        }
                        break;
                    }
                }
                if ($flag == 1) {
                    $detail = '聚信立:未找到与第二联系人互动情况相关信息';
                }

                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => $detail,
                    'value' => $t
                ];
            }

        return $result;
    }

    /**
     *

     *
     * 第一联系人最晚沟通时间
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkFirstContactsLast($data, $params)
    {
        $loan_person = $data['loan_person'];
        $yys = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：未找到相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $value = 1000;
            $detail = "未出现在联系人名单上";
            if (!empty($yys['common_contactors'][0]['phone'])) {
                $common_contactors = $yys['common_contactors'];
                foreach ($yys['contact_list'] as $k => $v) {
                    if ($common_contactors[0]['phone'] == $v['phone']) {
                        $value = round((time() - strtotime($v['last_contact_date'])) / 86400, 2);
                        $detail = '和' . $common_contactors[0]['name'] . '最晚沟通时间是' . $v['last_contact_date'];
                        break;
                    }
                }
            } else {
                // $info = UserContact::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
                $info = $data['user_contact'];
                if ($info) {
                    foreach ($yys['contact_list'] as $k => $v) {
                        if ($v['phone'] == $info['mobile']) {
                            $value = round((time() - strtotime($v['last_contact_date'])) / 86400, 2);
                            $detail = '和' . $info['name'] . "最晚沟通时间为" . $v['last_contact_date'];
                            break;
                        }
                    }
                }
            }

            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $value
            ];

        }
        return $result;
    }

    /**
     *

     *
     * 第二联系人最晚沟通时间
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSecondContactsLast($data, $params)
    {

        $loan_person = $data['loan_person'];
        $yys = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：未找到相关信息', 'value' => self::NULL];
        if (!empty($yys['contact_list'])) {
            $value = 1000;
            $detail = "未出现在联系人名单上";
            if (!empty($yys['common_contactors'][1]['phone'])) {
                $common_contactors = $yys['common_contactors'];
                foreach ($yys['contact_list'] as $k => $v) {
                    if ($common_contactors[1]['phone'] == $v['phone']) {
                        $value = round((time() - strtotime($v['last_contact_date'])) / 86400, 2);
                        $detail = '和' . $common_contactors[1]['name'] . '最晚沟通时间是' . $v['last_contact_date'];
                        break;
                    }
                }
            } else {
                // $info = UserContact::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
                $info = $data['user_contact'];
                if ($info) {
                    foreach ($yys['contact_list'] as $k => $v) {
                        if ($v['phone'] == $info['mobile_spare']) {
                            $value = round((time() - strtotime($v['last_contact_date'])) / 86400, 2);
                            $detail = '和' . $info['name_spare'] . "最晚沟通时间为" . $v['last_contact_date'];
                            break;
                        }
                    }
                }
            }

            $result = [
                'risk' => self::LOW_RISK,
                'detail' => $detail,
                'value' => $value
            ];

        }
        return $result;
    }

    /**
     *

     * modify by wolfbian on 2016-10-05 to add the value
     *
     * 通话检验是否有澳门电话
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkAomenContacts($data, $params)
    {

        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (!empty($data['contact_list'])) {
            foreach ($data['contact_list'] as $v) {
                if (strpos($v['phone_label'], "赌") !== false || strpos($v['phone_label'], "博") !== false) {
                    $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：存在' . $v['phone_label'] . '通话情况', 'value' => "有"];
                } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '运营商：不存在澳门通话情况', 'value' => "无"];

                }
            }
        }

        return $result;
    }


    /**
     *

     *
     * 号码使用时间评分
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPhoneUsableTime($data, $params)
    {

        $data = $data['jxl'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息'];
        if (!empty($data['behavior_check'])) {
            foreach ($data['behavior_check'] as $v) {
                if ($v['check_point'] == 'phone_used_time' || $v['check_point_cn'] == '号码使用时间') {
                    if ($v['score'] == '2') {
                        $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：号码使用时间评分过高' . $v['score'], 'value' => $v['score']];
                    } else {
                        $result = ['risk' => self::LOW_RISK, 'detail' => '运营商：号码使用时间评分' . $v['score'], 'value' => $v['score']];
                    }
                }
            }
        }

        return $result;
    }

    /**
     *

     *
     * 互通过电话的号码数量检验
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactsCount($data, $params)
    {
        $jxldata = $data['jxl'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];

        if (!empty($jxldata)) {
            if(array_key_exists('behavior_check',$jxldata)){
                $str='';
                foreach($jxldata['behavior_check'] as $k=>$v){
                    if(array_key_exists('check_point',$v)){
                        $check_point=trim($v['check_point']);
                        if($check_point=='contact_each_other'){
                            $str=trim($v['evidence']);
                            break;
                        }
                    }
                }
                $count = 0;
                if($str!=''){
                    if(preg_match('/\d+/',$str,$arr)){
                        $count=intval($arr[0]);
                    }
                }
                $detail = "互通过电话的号码数量为" . $count;
                $result = ['risk' => self::LOW_RISK, 'detail' => $detail, 'value' => $count];
            }
        }
        return $result;
    }

    /**
     *

     * modify by wolfbian on 2016-10-05 to add the value
     *
     * 关机情况检验
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPhoneClose($data, $params)
    {
        $data = $data['jxl'];

        $max = 0;
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '聚信立:没有相关信息', 'value' => self::NULL];
        if (!empty($data['behavior_check'])) {
            foreach ($data['behavior_check'] as $v) {
                if ($v['check_point'] == '关机情况') {
                    $result = ['risk' => self::LOW_RISK, 'detail' => $v['evidence'], 'value' => 0];
                    if (preg_match('/连续三天以上关机\d+次[：:]{1}(.*?)$/', $v['evidence'], $phone_shutdown)) {
                        if (isset($phone_shutdown[1]) && preg_match_all('/(\d+)天/', $phone_shutdown[1], $day)) {
                            foreach ($day[1] as $k => $v) {
                                if ($v > $max) {
                                    $max = $v;
                                }
                            }
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上关机最长天数' . $max, 'value' => intval($max)];
                        }
                    }
                    break;
                } else if ($v['check_point'] == "phone_silent") {
                    $result = ['risk' => self::LOW_RISK, 'detail' => $v['evidence'], 'value' => 0];
                    if (preg_match('/连续三天以上无通话记录\d+次[：:]{1}(.*?)$/', $v['evidence'], $phone_shutdown)) {

                        if (isset($phone_shutdown[1]) && preg_match_all('/(\d+)天/', $phone_shutdown[1], $day)) {
                            foreach ($day[1] as $k => $v) {
                                if ($v > $max) {
                                    $max = $v;
                                }
                            }
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上无通话记录最长天数' . $max, 'value' => intval($max)];

                        }
                    } else if (preg_match('/连续三天以上关机\d+次[：:]{1}(.*?)$/', $v['evidence'], $phone_shutdown)) {
                        if (isset($phone_shutdown[1]) && preg_match_all('/(\d+)天/', $phone_shutdown[1], $day)) {
                            foreach ($day[1] as $k => $v) {
                                if ($v > $max) {
                                    $max = $v;
                                }
                            }
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上关机最长天数' . $max, 'value' => intval($max)];

                        }
                    }
                    break;
                }
            }
        }
        return $result;
    }

    /**
     *

     *
     * 贷款类号码联系情况
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanContacts($data, $params)
    {

        $data = $data['jxl'];
        $call_out_count = $params['call_out_count'];
        $call_out_time = $params['call_out_time'];
        $call_in_count = $params['call_in_count'];
        $call_in_time = $params['call_in_time'];

        $detail = '聚信立:没有相关信息';
        $flag = true;
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => self::NULL];
        if (!empty($data['behavior_check'])) {
            foreach ($data['behavior_check'] as $v) {
                if ($v['check_point'] == 'contact_loan' || $v['check_point_cn'] == '贷款类号码联系情况') {
                    $detail = $v['evidence'];
                    if (preg_match_all('/主叫([1-9]\d*)次共(.*?)分钟/', $v['evidence'], $phone_call)) {
                        if (isset($phone_call[1]) && isset($phone_call[2])) {
                            if (intval($phone_call[1]) > $call_out_count || intval($phone_call[2]) > $call_out_time) {
                                $flag = false;
                            }
                        }

                    }
                    if (preg_match_all('/被叫([1-9]\d*)次共.*?分钟/', $v['evidence'], $phone_called)) {
                        if (isset($phone_called[1]) && isset($phone_called[2])) {
                            if (count($phone_called[1]) > $call_in_count || intval($phone_called[2]) > $call_in_time) {
                                $flag = false;
                            }
                        }
                    }
                    break;
                }
            }

            $result = ['risk' => self::LOW_RISK, 'detail' => $detail, 'value' => self::NO];
            if ($flag == false){
                $result = ['risk' => self::HIGH_RISK, 'detail' => $detail, 'value' => self::YES];
            }

        }

        return $result;
    }


    /**
     *

     *
     * 主叫贷款类号码联系情况
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkActiveLoanContacts($data, $params)
    {
        $data = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息'];
        if (!empty($data['contact_list'])) {
            $count = 0;
            foreach ($data['contact_list'] as $v) {
                if (strpos($v['phone_label'], "贷") !== false || strpos($v['phone_label'], "款") !== false) {
                    $count = $count + isset($v['call_cnt']) ? $v['call_cnt'] : 1;
                }
            }
            $detail = '主叫贷款类号码次数为' . $count . '次';

            $result = ['risk' => self::LOW_RISK, 'detail' => $detail, 'value' => $count];
        }

        return $result;
    }

    /**
     *

     *
     * 被叫贷款类号码联系情况
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPositiveLoanContacts($data, $params)
    {

        $data = $data['yys'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息'];
        if (!empty($data['contact_list'])) {
            $count = 0;
            foreach ($data['contact_list'] as $v) {
                if (strpos($v['phone_label'], "贷") !== false || strpos($v['phone_label'], "款") !== false) {
                    $count = $count + isset($v['called_cnt']) ? $v['called_cnt'] : 1;
                }
            }
            $detail = '被叫贷款类号码次数为' . $count . '次';

            $result = ['risk' => self::LOW_RISK, 'detail' => $detail, 'value' => $count];
        }

        return $result;
    }

    /**
     *暂不使用

     *
     * 最晚联系校验
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLastContact($data, $params)
    {

        $data = $data['jxl'];
        $max_days = $params['max_days'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '聚信立:没有相关信息'];
        if (!empty($data['collection_contact'])) {
            $contact_count = 0;
            foreach ($data['collection_contact'] as $v) {
                if (isset($v['contact_details'][0]['trans_end']) && !empty($v['contact_details'][0]['trans_end'])) {
                    if ((time() - strtotime($v['contact_details'][0]['trans_end'])) > 86400 * $max_days) {
                        $contact_count += 1;
                    }
                } else {
                    $contact_count += 1;
                }
            }

            $detail = "聚信立:与联系人最晚沟通时间超过" . $max_days . "天的出现" . $contact_count . "人次";

            $risk = self::LOW_RISK;

            if ($contact_count == 2) {
                $risk = self::HIGH_RISK;
            }

            $result = ['risk' => $risk, 'detail' => $detail];
        }

        return $result;
    }


    /**
     *

     *
     * 通过身份证判断年龄
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserAge($data, $params)
    {

        $loan_person = $data['loan_person'];

        $start = $params['start'];
        $end = $params['end'];

        //通过身份证判断年龄
        $now = intval(date('Y', time()));
        $id_card_len = strlen($loan_person['id_number']);

        if ($id_card_len == 18) {
            $year = (int)substr($loan_person['id_number'], 6, 4);
            $month = substr($loan_person['id_number'], 10, 2);
            $day = substr($loan_person['id_number'], 12, 2);

        } elseif ($id_card_len == 15) {
            $year = "19" . substr($loan_person['id_number'], 6, 2);
            $month = substr($loan_person['id_number'], 8, 2);
            $day = substr($loan_person['id_number'], 10, 2);
        } else {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '身份证解析失败' . $loan_person['id_number'], 'value' => self::NULL];
        }

        $age = $now - $year;
        if (date('m') < $month) {
            $age -= 1;
        } else {
            if (date('m') < $month) {
                $age -= 1;
            } elseif (date('m') == $month && date('d') < $day) {
                $age -= 1;
            }
        }

        if ($age < $start || $age > $end) {
            return ['risk' => self::HIGH_RISK, 'detail' => '身份证年龄为' . ($age), 'value' => $age];
        }

        return ['risk' => self::LOW_RISK, 'detail' => '身份证年龄为' . ($age), 'value' => $age];

    }

    /**
     *

     *
     * 通过身份证判断性别
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserGender($data, $params)
    {

        $loan_person = $data['loan_person'];

        //通过身份证判断性别
        $now = intval(date('Y', time()));
        $id_card_len = strlen($loan_person['id_number']);
        if ($id_card_len == 18) {
            $n = (int)substr($loan_person['id_number'], -2, 1);
            if ($n % 2 == 1) {
                $gender = "男";
            } else {
                $gender = "女";
            }
        } elseif ($id_card_len == 15) {
            $n = (int)substr($loan_person['id_number'], -1, 1);
            if ($n % 2 == 1) {
                $gender = "男";
            } else {
                $gender = "女";
            }
        } else {
            $gender = -1;
        }

        return ['risk' => self::LOW_RISK, 'detail' => $gender, 'value' => $gender];

    }

    /**
     *

     *
     * 学历
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserDegree($data, $params)
    {

        $loan_person = $data['loan_person'];

        // $person_degree = LoanPersonDegree::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $person_degree = $data['loan_person_degree'];

        if (empty($person_degree)) {
            $degree = "暂无";
        } else {
            $degree = $person_degree['highest_degree'];
            if (empty($degree)) {
                $degree = "暂无";
            }
        }

        return ['risk' => self::LOW_RISK, 'detail' => $degree, 'value' => $degree];

    }

    /**
     *

     *
     * 户籍地址
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserAddress($data, $params)
    {

        $loan_person = $data['loan_person'];

        $id_number_address = "暂无";
        $id_number = $loan_person['id_number'];
        if (!empty($id_number) && ToolsUtil::checkIdNumber($id_number)) {
            $id_number_address = ToolsUtil::get_addr($id_number);
        }

        if (empty($id_number_address)) {
            $id_number_address = "暂无";
        }

        return ['risk' => self::LOW_RISK, 'detail' => $id_number_address, 'value' => $id_number_address];

    }

    /**
     *

     *
     * 户籍地址重复次数(待修正)
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserAddressCount($data, $params)
    {

        $loan_person = $data['loan_person'];

        // $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $person_relation = $data['user_quota_person_info'];

        if (empty($person_relation)) {
            $address = "暂无";
            return ['risk' => self::HIGH_RISK, 'detail' => $address, 'value' => self::NULL];
        } else {
            $address = $person_relation['address_distinct'];
            if (empty($address)) {
                $address = "暂无";
                return ['risk' => self::HIGH_RISK, 'detail' => $address, 'value' => self::NULL];
            }
        }

        $count = UserQuotaPersonInfo::find()->where(['address_distinct' => $address])->count('*', Yii::$app->get('db_kdkj_rd'));

        return ['risk' => self::LOW_RISK, 'detail' => $address, 'value' => $count - 1];

    }

    /**
     *

     *
     * 信用卡
     *
     * param    array            数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserCreditCard($data, $params)
    {

        $loan_person = $data['loan_person'];

        // $cards = CardInfo::find()->where(['user_id' => $loan_person->id])->all(Yii::$app->get('db_kdkj_rd'));
        $cards = $data['card_infos'];

        if (empty($cards)) {
            $result = "无";
            $detail = "无绑卡信息";
        } else {
            $result = "无";
            $detail = "有储蓄卡信息";
            foreach ($cards as $key => $card) {
                if ($card['type'] == CardInfo::TYPE_CREDIT_CARD) {
                    $result = "有";
                    $detail = "有信用卡" . $card['card_no'];
                    break;
                }
            }
        }

        return ['risk' => self::LOW_RISK, 'detail' => $detail, 'value' => $result];

    }

    /**
     *

     *
     * 客户端版本校验
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkClientVersion($data, $params)
    {

        $loan_person = $data['loan_person'];

        $ios_version = $params['ios_version'];
        $android_version = $params['android_version'];

        // $log = UserLoginUploadLog::find()->where(['user_id' => $loan_person->id])->orderBy('id desc')->limit(1)->one(Yii::$app->get('db_kdkj_rd'));
        $log = $data['user_login_upload_log'];
        $risk = self::HIGH_RISK;
        $detail = '未获取到用户上传信息';
        if (!is_null($log)) {
            switch ($log['clientType']) {
                case 'android':
                    if (version_compare($log['appVersion'], $android_version, 'ge')) {
                        $risk = self::LOW_RISK;
                    }
                    break;
                case 'ios':
                    if (version_compare($log['appVersion'], $ios_version, 'ge')) {
                        $risk = self::LOW_RISK;
                    }
                    break;
                default:
                    return true;
                    break;
            }

            $detail = "系统为" . $log['clientType'] . "，版本为" . $log['appVersion'];
        }

        return ['risk' => $risk, 'detail' => $detail];
    }

    /**
     *

     *
     * 判断设备数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkDeviceCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];
        // $count = UserLoginUploadLog::find()
        //     ->select(['deviceId'])
        //     ->where(['user_id' => $loan_person->id])
        //     ->andWhere(['not', ['deviceId' => null]])
        //     ->distinct()->count('*', Yii::$app->get('db_kdkj_rd'));

        $count = 0;
        $user_login_upload_logs = $data['user_login_upload_logs'];
        if ($user_login_upload_logs) {
            $map = [];
            foreach ($user_login_upload_logs as $key => $value) {
                if ($value['deviceId']) {
                    $map[$value['deviceId']] = 1;
                }
            }
            $count = count($map);
        }

        if ($count > $high) {
            return ['risk' => self::HIGH_RISK, 'detail' => '登录设备数量' . $count, 'value' => $count];
        } elseif ($count >= $low) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '登录设备数量' . $count, 'value' => $count];
        } else {
            return ['risk' => self::LOW_RISK, 'detail' => '登录设备数量' . $count, 'value' => $count];
        }
    }

    /**
     *

     * modify 2016-10-05
     *
     * 判断通讯录手机号数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];
        // $user_mobile_contacts = UserMobileContacts::getContactData($loan_person->id);
        $user_mobile_contacts = $data['user_mobile_contacts'];
        $count = 0;
        if ($user_mobile_contacts) {
            $count = count($user_mobile_contacts);
        }

        return [
            'risk' => ($count < $low) ? self::HIGH_RISK : ($count <= $high ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '通讯录手机号数量' . $count,
            'value' => $count,
        ];

    }

    /**
     *

     * modify 2016-10-05
     *
     * 设备曾用手机号
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkDeviceUserCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];
        // $device = UserLoginUploadLog::find()
        //     ->select(['deviceId'])
        //     ->where(['user_id' => $loan_person->id])
        //     ->andWhere(['<>', 'deviceId', null])
        //     ->andWhere(['<>', 'deviceId', ""])
        //     ->distinct()->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        $device = [];
        $user_login_upload_logs = $data['user_login_upload_logs'];

        foreach ($user_login_upload_logs as $key => $value) {
            if (!empty($value['deviceId']) && $value['deviceId'] != '000000000000000') {
                $device[] = $value['deviceId'];
            }
        }

        $device = array_unique($device);

        $count = 0;
        if (!empty($device)) {
            $count = UserLoginUploadLog::find()->select(['user_id'])->where(['deviceId' => $device])
                ->distinct()->count('*', Yii::$app->get('db_kdkj_rd'));
        }

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '设备曾用手机号数量' . $count,
            'value' => $count
        ];

    }

    /**
     *

     * modify 2016-10-05
     *
     * 常住地址出现次数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkHomeAddressCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        if ($loan_person->source_id == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        $low = $params['low'];
        $high = $params['high'];

        // $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $person_relation = $data['user_quota_person_info'];
        if (empty($person_relation)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => self::NULL];
        }

        $address = isset($person_relation['address']) ? $person_relation['address'] : null;
        if (empty($address)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的住址信息为空', 'value' => self::NULL];
        }

        $count = UserQuotaPersonInfo::find()->where(['address' => $address])->count('*', Yii::$app->get('db_kdkj_rd'));

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '常住地址重复出现次数' . ($count - 1),
            'value' => $count - 1
        ];

    }

    /**
     *

     *
     * 常住地址区县重复出现次数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkHomeAddressDistinctCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];

        // $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $person_relation = $data['user_quota_person_info'];
        if (is_null($person_relation)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => self::NULL];
        }

        $address_distinct = $person_relation['address_distinct'];
        if (empty($address_distinct)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的住址区县信息为空', 'value' => self::NULL];
        }

        $count = UserQuotaPersonInfo::find()->where(['address_distinct' => $address_distinct])->count('*', Yii::$app->get('db_kdkj_rd'));

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '常住地址区县重复出现次数' . ($count - 1),
            'value' => $count - 1
        ];

    }

    public function checkCompanyPhone($data, $params)
    {
        //return ['risk' => self::LOW_RISK, 'detail' => '暂停校验', 'value' => 0];
        $loan_person = $data['loan_person'];
        if ($loan_person['source_id'] == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        // $equipment = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $equipment = $data['user_detail'];
        if (is_null($equipment)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => 0];
        }

        $company_phone = $equipment['company_phone'];
        if (empty($company_phone)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的公司电话为空', 'value' => 0];
        }

        $count = UserDetail::find()->where(['company_phone' => $company_phone])
            ->andWhere(['<>', 'company_name', $equipment['company_name']])
            ->andWhere(['<>', 'company_address', $equipment['company_address']])
            ->count('*', Yii::$app->get('db_kdkj_rd'));

        return ['risk' => self::LOW_RISK, 'detail' => '', 'value' => $count];
    }

    public function checkCompanyName($data, $params)
    {
        return ['risk' => self::LOW_RISK, 'detail' => '暂停校验', 'value' => 0];
        $loan_person = $data['loan_person'];
        if ($loan_person['source_id'] == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        // $equipment = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $equipment = $data['user_detail'];
        if (is_null($equipment)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => 0];
        }

        $company_name = $equipment['company_name'];
        if (empty($company_name)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的公司名称为空', 'value' => 0];
        }

        $count = UserDetail::find()->where(['company_name' => $company_name])
            ->andWhere(['<>', 'company_phone', $equipment['company_phone']])
            ->andWhere(['<>', 'company_address', $equipment['company_address']])
            ->count('*', Yii::$app->get('db_kdkj_rd'));

        return ['risk' => self::LOW_RISK, 'detail' => '', 'value' => $count];
    }

    public function checkCompanyAddress($data, $params)
    {

        return ['risk' => self::LOW_RISK, 'detail' => '暂停校验', 'value' => 0];
        $loan_person = $data['loan_person'];
        if ($loan_person['source_id'] == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        // $equipment = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $equipment = $data['user_detail'];
        if (is_null($equipment)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => 0];
        }

        $company_address = $equipment['company_address'];
        if (empty($company_address)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的公司地址为空', 'value' => 0];
        }

        $count = UserDetail::find()->where(['company_address' => $company_address])
            ->andWhere(['<>', 'company_phone', $equipment['company_phone']])
            ->andWhere(['<>', 'company_name', $equipment['company_name']])
            ->count('*', Yii::$app->get('db_kdkj_rd'));

        return ['risk' => self::LOW_RISK, 'detail' => '', 'value' => $count];
    }

    /**
     *

     * modify 2016-10-05
     *
     * 公司名出现次数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCompanyNameCount($data, $params)
    {

        //return ['risk' => self::LOW_RISK, 'detail' => '暂停校验', 'value' => 0];
        $loan_person = $data['loan_person'];
        if ($loan_person['source_id'] == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        $low = $params['low'];
        $high = $params['high'];

        // $equipment = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $equipment = $data['user_detail'];

        if (is_null($equipment)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => 0];
        }
        $company_name = $equipment['company_name'];
        if (empty($company_name)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的公司名为空', 'value' => 0];
        }

        $count = UserDetail::find()->where(['company_name' => $company_name])->count('*', Yii::$app->get('db_kdkj_rd'));

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '公司名重复出现次数' . ($count - 1),
            'value' => $count - 1
        ];

    }

    /**
     *

     * modify 2016-10-05
     *
     * 公司地址出现次数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCompanyAddressCount($data, $params)
    {

        //return ['risk' => self::LOW_RISK, 'detail' => '暂停校验', 'value' => 0];
        $loan_person = $data['loan_person'];
        if ($loan_person['source_id'] == LoanPerson::PERSON_SOURCE_RONGSLL) {
            return ['risk' => self::LOW_RISK, 'detail' => '融360用户', 'value' => 0];
        }
        $low = $params['low'];
        $high = $params['high'];

        // $equipment = UserDetail::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $equipment = $data['user_detail'];

        if (is_null($equipment)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '未找到该用户信息', 'value' => 0];
        }
        $company_addr = $equipment['company_address'];
        if (empty($company_addr)) {
            return ['risk' => self::HIGH_RISK, 'detail' => '该用户的公司地址为空', 'value' => 0];
        }

        $count = UserDetail::find()->where(['company_address' => $company_addr])->count('*', Yii::$app->get('db_kdkj_rd'));

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '公司地址重复出现次数' . ($count - 1),
            'value' => $count - 1
        ];

    }

    /**
     *

     * modify 2016-10-05
     *
     * 登录地址出现次数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoginAddressCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];

        // $login_log = UserLoginUploadLog::find()
        //     ->select(['address'])
        //     ->where(['user_id' => $loan_person->id])
        //     ->andWhere(['not', ['address' => null]])
        //     ->distinct()->all(Yii::$app->get('db_kdkj_rd'));

        $user_login_upload_logs = $data['user_login_upload_logs'];

        $log_address_list = [];
        if (!empty($user_login_upload_logs)) {
            foreach ($user_login_upload_logs as $v) {
                if (!empty(trim($v['address']))) {
                    $log_address_list[] = $v->address;
                }
            }
        }
        $log_address_list = array_unique($log_address_list);
        $count = UserLoginUploadLog::find()->select(['user_id'])->where(['address' => $log_address_list])->distinct()->count('*', Yii::$app->get('db_kdkj_rd'));

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '登录地址重复出现次数' . ($count - 1),
            'value' => $count - 1
        ];

    }

    /**
     *

     *
     * 直接联系人申请数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactApplyCount($data, $params)
    {

        $loan_person = $data['loan_person'];
        $low = $params['low'];
        $high = $params['high'];

        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $tmp_phone = str_replace('+86', '', $v['mobile']);
                $tmp_phone = str_replace('-', '', $tmp_phone);
                if (strlen($tmp_phone) == 11 && intval($tmp_phone) != 0) {
                    $mobile_contact_list[] = $tmp_phone;
                }
            }
        }

        $jxl = $data['yys'];
        if (!empty($jxl['contact_list'])) {
            foreach ($jxl['contact_list'] as $item) {
                if (strlen($item['phone']) == 11 && intval($item['phone']) != 0) {
                    $mobile_contact_list[] = $item['phone'];
                }
            }
        }

        $count = 0;
        if (!empty($mobile_contact_list)) {
            $mobile_contact_list = array_unique($mobile_contact_list);
            $count = LoanPerson::find()->where(['phone' => $mobile_contact_list])->andWhere(['<>', 'id', $loan_person->id])->count('*', Yii::$app->get('db_kdkj_rd'));
        }

        return [
            'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
            'detail' => '直接联系人申请数' . $count,
            'value' => $count
        ];

    }

    /**
     *

     *
     * 直接联系人订单非最终状态人数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactApplyStatusCount($data, $params)
    {

        $loan_person = $data['loan_person'];


        $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact_list = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $tmp_phone = str_replace('+86', '', $v['mobile']);
                $tmp_phone = str_replace('-', '', $tmp_phone);
                if (strlen($tmp_phone) == 11 && intval($tmp_phone) != 0) {
                    $mobile_contact_list[] = $tmp_phone;
                }
            }
        }

        $jxl = $data['yys'];
        if (!empty($jxl['contact_list'])) {
            foreach ($jxl['contact_list'] as $item) {
                if (strlen($item['phone']) == 11 && intval($item['phone']) != 0) {
                    $mobile_contact_list[] = $item['phone'];
                }
            }
        }

        $count = 0;
        $status_arr = [UserLoanOrder::STATUS_PENDING_CANCEL, UserLoanOrder::STATUS_REPEAT_CANCEL, UserLoanOrder::STATUS_CANCEL, UserLoanOrder::STATUS_REPAY_COMPLETE];
        if (!empty($mobile_contact_list)) {
            $mobile_contact_list = array_unique($mobile_contact_list);
            $person = LoanPerson::find()->where(['phone' => $mobile_contact_list])->andWhere(['<>', 'id', $loan_person->id])->all(Yii::$app->get('db_kdkj_rd'));
            if ($person) {
                foreach ($person as $item) {
                    $orders = UserLoanOrder::find()->select('status')->where(['user_id' => $item['id']])->andWhere(['not in', 'status', $status_arr])->all(Yii::$app->get('db_kdkj_rd'));
                    if ($orders) {
                        $count++;
                    }
                }
            }

        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => '直接联系人订单非最终状态人数为' . $count, 'value' => $count];

    }

    /**
     *

     *
     * 还款状态
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkRepaymentStatus($data, $params)
    {

        $loan_person = $data['loan_person'];
        // $count = UserLoanOrderRepayment::find()
        //     ->where(['user_id' => $loan_person->id, 'status' => [UserLoanOrderRepayment::STATUS_OVERDUE, UserLoanOrderRepayment::STATUS_BAD_DEBT]])
        //     ->count('*', Yii::$app->get('db_kdkj_rd'));

        // $repayments = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person->id])->all(Yii::$app->get('db_kdkj_rd'));
        $repayments = $data['user_loan_order_repayments'];

        $count = 0;
        if ($repayments) {
            foreach ($repayments as $key => $repayment) {
                if (in_array($repayment['status'], [UserLoanOrderRepayment::STATUS_OVERDUE, UserLoanOrderRepayment::STATUS_BAD_DEBT])) {
                    $count++;
                }
            }
        }

        return [
            'risk' => ($count > 0) ? self::HIGH_RISK : self::LOW_RISK,
            'detail' => '还款出现已逾期和已坏账的次数' . $count
        ];

    }

    /**
     * author 王成

     *
     * 宜信借款信息
     *
     * param    array            信息借款信息报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkYxzcLoanOverdue($data, $params)
    {

        if (empty($data = $data['yx'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '宜信:没有相关信息', 'value' => '没有相关信息'];
        } else {
            $overdue = $data['overdue'];

            if (empty($overdue)) {
                $info = [
                    '180overdueTimes' => 0,
                    '90overdueTimes' => 0,
                    'overdueTimes' => 0
                ];
            } else {
                $info = $overdue;
            }

            $result = [
                'risk' => self::LOW_RISK,
                'detail' => "宜信:逾期{$info['overdueTimes']}笔、逾期180天{$info['180overdueTimes']}笔、逾期90天{$info['90overdueTimes']}笔",
                'value' => $info['180overdueTimes'] > 0 ? '存在逾期180天' :
                    ($info['90overdueTimes'] > 0 ? '存在逾期90天' :
                        ($info['overdueTimes'] > 0 ? '存在逾期' : '无逾期记录'))
            ];
        }
        return $result;

    }


    /**
     *

     *
     * 芝麻分
     *
     * param    array            芝麻信用一条数据库数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZmOverdue($data, $params)
    {
        if (is_null($zm = $data['zm']) || empty($zm['watch_matched'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:没有相关信息', 'value' => self::NULL];
        } else {
            $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻:未命中行业关注名单', 'value' => '未命中行业关注名单'];
            if ($zm['watch_matched'] == CreditZmop::WATCH_TURE && !empty($zm['watch_info'])) {
                $code = '';
                foreach (json_decode($zm['watch_info'], true) as $v) {
                    $code = $v['code'];
                }
                if (empty($code)) {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻:未命中行业关注名单', 'value' => '未命中行业关注名单'];
                } else {
                    $d = isset(CreditZmop::$risk_code[$code]) ? CreditZmop::$risk_code[$code] : "";
                    $result = ['risk' => self::LOW_RISK, 'detail' => $d, 'value' => $d];
                }
            }
        }
        return $result;

    }

    /**
     *

     *
     * 芝麻分
     *
     * param    array            芝麻信用一条数据库数据
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZmScore($data, $params)
    {
        if (is_null($zm = $data['zm']) || empty($zm['zm_score'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:没有相关信息', 'value' => self::NULL];
        } else {
            $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻分:' . $zm['zm_score'], 'value' => $zm['zm_score']];
        }
        return $result;

    }

    /**
     * 索伦-葫芦分
     * @param $data
     * @param $params
     * @return array
     */
    public function checkSauronSnScore($data, $params)
    {
        $low = $params['low'];
        $high = $params['high'];

        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦SN:没有相关信息', 'value' => self::NULL];
        } else {
            if (is_array($data['risk_social_network'])) {
                $s = $data['risk_social_network']['sn_score'];
                $result = [
                    'risk' => ($s <= $high) ? self::HIGH_RISK : ($s <= $low ? self::MEDIUM_RISK : self::LOW_RISK),
                    'detail' => '索伦SN:葫芦分' . $s,
                    'value' => $s
                ];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦SN:无葫芦分', 'value' => self::NULL];
            }
        }
        return $result;
    }

    /**

     * date 2017-03-11
     *
     * 芝麻信用-IVS评分
     * @param $data
     * @param $params
     * @return array
     */
    public function checkZmopIvs($data, $params)
    {
        $low = $params['low'];
        $high = $params['high'];
        if (is_null($zm = $data['zm']) || empty($zm['ivs_score'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻信用:IVS评分没有相关信息', 'value' => self::NULL];
        } else {
            $s = $zm['ivs_score'];
            $result = [
                'risk' => ($s >= $high) ? self::LOW_RISK : ($s <= $low ? self::HIGH_RISK : self::MEDIUM_RISK),
                'detail' => '芝麻信用:IVS评分' . $s,
                'value' => $s
            ];
        }
        return $result;
    }

    /**

     * date 2017-03-06
     *
     * 百融-特殊名单列表
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBaiRongSpecialList($data)
    {
        if (is_null($br = $data['br'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '百融:没有相关信息', 'value' => self::NULL];
        } else {
            $apply_loan = null;
            foreach ($br as $key => $val) {
                if ($val['type'] == CreditBr::SPECIAL_LIST) {
                    $apply_loan = json_decode($val['data'], true);
                }
            }
            if (is_null($apply_loan))
                return ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
            if ($apply_loan['code'] == 100002) {
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融:暂未查询到用户的不良记录',
                    'value' => 0
                ];
            } else {
                $res = $this->brlist($apply_loan);
                if ($res['code'] == 0) {
                    $result = [
                        'risk' => self::HIGH_RISK,
                        'detail' => $res['msg'],
                        'value' => 0
                    ];
                } else {
                    $result = [
                        'risk' => self::MEDIUM_RISK,
                        'detail' => $res['msg'],
                        'value' => 0
                    ];
                }
            }
        }
        return $result;
    }


    /**

     * date 2017-03-06
     *
     * 百融-特殊名单列表
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBrSpecialList($data)
    {
        if (is_null($br = $data['br'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '百融:没有相关信息', 'value' => self::NULL];
        } else {
            $apply_loan = null;
            foreach ($br as $key => $val) {
                if ($val['type'] == CreditBr::SPECIAL_LIST) {
                    $apply_loan = json_decode($val['data'], true);
                }
            }
            if (is_null($apply_loan))
                return ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
            if ($apply_loan['code'] == 100002) {
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融:暂未查询到用户的不良记录',
                    'value' => 0
                ];
            } else {
                $res = $this->brlist1($apply_loan);
                $result = [
                    'risk' => self::MEDIUM_RISK,
                    'detail' => $res['msg'],
                    'value' => $res['count'],
                ];
            }
        }
        return $result;
    }

    /*
     * 百融特殊名单列表
     */
    private function brlist($arr)
    {
        $speciallist = ['sl_id_abnormal' => '通过身份证号查询高危行为',
            'sl_id_phone_overdue' => '通过身份证号查询电信欠费',
            'sl_id_court_bad' => '通过身份证号查询法院失信人',
            'sl_id_court_executed' => '通过身份证号查询法院被执行人',
            'sl_id_bank_bad' => '通过身份证号查询银行(含信用卡)不良',
            'sl_id_bank_overdue' => '通过身份证号查询银行(含信用卡)短时逾期',
            'sl_id_bank_fraud' => '通过身份证号查询银行(含信用卡)资信不佳',
            'sl_id_bank_lost' => '通过身份证号查询银行(含信用卡)失联',
            'sl_id_p2p_bad' => '通过身份证号查询非银(含全部非银类型)不良',
            'sl_id_p2p_overdue' => '通过身份证号查询非银(含全部非银类型)短时逾期',
            'sl_id_p2p_fraud' => '通过身份证号查询非银(含全部非银类型)资信不佳',
            'sl_id_p2p_lost' => '通过身份证号查询非银(含全部非银类型)失联',
            'sl_id_nbank_p2p_bad' => '通过身份证号查询非银-P2P不良',
            'sl_id_nbank_p2p_overdue' => '通过身份证号查询非银-P2P短时逾期',
            'sl_id_nbank_p2p_fraud' => '通过身份证号查询非银-P2P资信不佳',
            'sl_id_nbank_p2p_lost' => '通过身份证号查询非银-P2P失联',
            'sl_id_nbank_mc_bad' => '通过身份证号查询非银-小贷不良',
            'sl_id_nbank_mc_overdue' => '通过身份证号查询非银-小贷短时逾期',
            'sl_id_nbank_mc_fraud' => '通过身份证号查询非银-小贷资信不佳',
            'sl_id_nbank_mc_lost' => '通过身份证号查询非银-小贷失联',
            'sl_id_nbank_ca_bad' => '通过身份证号查询非银-现金类分期不良',
            'sl_id_nbank_ca_overdue' => '通过身份证号查询非银-现金类分期短时逾期',
            'sl_id_nbank_ca_fraud' => '通过身份证号查询非银-现金类分期资信不佳',
            'sl_id_nbank_ca_lost' => '通过身份证号查询非银-现金类分期失联',
            'sl_id_nbank_com_bad' => '通过身份证号查询非银-代偿类分期不良',
            'sl_id_nbank_com_overdue' => '通过身份证号查询非银-代偿类分期短时逾期',
            'sl_id_nbank_com_fraud' => '通过身份证号查询非银-代偿类分期资信不佳',
            'sl_id_nbank_com_lost' => '通过身份证号查询非银-代偿类分期失联',
            'sl_id_nbank_cf_bad' => '通过身份证号查询非银-消费类分期不良',
            'sl_id_nbank_cf_overdue' => '通过身份证号查询非银-消费类分期短时逾期',
            'sl_id_nbank_cf_fraud' => '通过身份证号查询非银-消费类分期资信不佳',
            'sl_id_nbank_cf_lost' => '通过身份证号查询非银-消费类分期失联',
            'sl_id_nbank_other_bad' => '通过身份证号查询非银-其他不良',
            'sl_id_nbank_other_overdue' => '通过身份证号查询非银-其他短时逾期',
            'sl_id_nbank_other_fraud' => '通过身份证号查询非银-其他资信不佳',
            'sl_id_nbank_other_lost' => '通过身份证号查询非银-其他失联',
            'sl_cell_abnormal' => '通过手机号查询高危行为',
            'sl_cell_phone_overdue' => '通过手机号查询电信欠费',
            'sl_cell_bank_bad' => '通过手机号查询银行(含信用卡)不良',
            'sl_cell_bank_overdue' => '通过手机号查询银行(含信用卡)短时逾期',
            'sl_cell_bank_fraud' => '通过手机号查询银行(含信用卡)资信不佳',
            'sl_cell_bank_lost' => '通过手机号查询银行(含信用卡)失联',
            'sl_cell_p2p_bad' => '通过手机号查询非银(含全部非银类型)不良',
            'sl_cell_p2p_overdue' => '通过手机号查询非银(含全部非银类型)短时逾期',
            'sl_cell_p2p_fraud' => '通过手机号查询非银(含全部非银类型)资信不佳',
            'sl_cell_p2p_lost' => '通过手机号查询非银(含全部非银类型)失联',
            'sl_cell_nbank_p2p_bad' => '通过手机号查询非银-P2P不良',
            'sl_cell_nbank_p2p_overdue' => '通过手机号查询非银-P2P短时逾期',
            'sl_cell_nbank_p2p_fraud' => '通过手机号查询非银-P2P资信不佳',
            'sl_cell_nbank_p2p_lost' => '通过手机号查询非银-P2P失联',
            'sl_cell_nbank_mc_bad' => '通过手机号查询非银-小贷不良',
            'sl_cell_nbank_mc_overdue' => '通过手机号查询非银-小贷短时逾期',
            'sl_cell_nbank_mc_fraud' => '通过手机号查询非银-小贷资信不佳',
            'sl_cell_nbank_mc_lost' => '通过手机号查询非银-小贷失联',
            'sl_cell_nbank_ca_bad' => '通过手机号查询非银-现金类分期不良',
            'sl_cell_nbank_ca_overdue' => '通过手机号查询非银-现金类分期短时逾期',
            'sl_cell_nbank_ca_fraud' => '通过手机号查询非银-现金类分期资信不佳',
            'sl_cell_nbank_ca_lost' => '通过手机号查询非银-现金类分期失联',
            'sl_cell_nbank_com_bad' => '通过手机号查询非银-代偿类分期不良',
            'sl_cell_nbank_com_overdue' => '通过手机号查询非银-代偿类分期短时逾期',
            'sl_cell_nbank_com_fraud' => '通过手机号查询非银-代偿类分期资信不佳',
            'sl_cell_nbank_com_lost' => '通过手机号查询非银-代偿类分期失联',
            'sl_cell_nbank_cf_bad' => '通过手机号查询非银-消费类分期不良',
            'sl_cell_nbank_cf_overdue' => '通过手机号查询非银-消费类分期短时逾期',
            'sl_cell_nbank_cf_fraud' => '通过手机号查询非银-消费类分期资信不佳',
            'sl_cell_nbank_cf_lost' => '通过手机号查询非银-消费类分期失联',
            'sl_cell_nbank_other_bad' => '通过手机号查询非银-其他不良',
            'sl_cell_nbank_other_overdue' => '通过手机号查询非银-其他短时逾期',
            'sl_cell_nbank_other_fraud' => '通过手机号查询非银-其他资信不佳',
            'sl_cell_nbank_other_lost' => '通过手机号查询非银-其他失联'
        ];
        foreach ($arr as $key => $val) {
            foreach ($speciallist as $key1 => $val1) {
                if ($key == $key1 && $val == 0) {
                    return $result = ['code' => 0, 'msg' => $val1];
                }
            }
        }
        return $result = ['code' => 1001, 'msg' => '百融：暂未发现匹配数据'];
    }

    /*
     * 百融特殊名单列表
     */
    private function brlist1($arr)
    {
        $speciallist = [
            'sl_id_abnormal' => '通过身份证号查询高危行为',
            'sl_id_court_bad' => '通过身份证号查询法院失信人',
            'sl_id_court_executed' => '通过身份证号查询法院被执行人',
            'sl_id_bank_fraud' => '通过身份证号查询银行(含信用卡)资信不佳',
            'sl_id_bank_lost' => '通过身份证号查询银行(含信用卡)高风险',
            'sl_id_p2p_fraud' => '通过身份证号查询非银(含全部非银类型)资信不佳',
            'sl_id_p2p_lost' => '通过身份证号查询非银(含全部非银类型)高风险',
            'sl_id_nbank_p2p_fraud' => '通过身份证号查询非银-P2P资信不佳',
            'sl_id_nbank_p2p_lost' => '通过身份证号查询非银-P2P高风险',
            'sl_id_nbank_mc_fraud' => '通过身份证号查询非银-小贷资信不佳',
            'sl_id_nbank_mc_lost' => '通过身份证号查询非银-小贷高风险',
            'sl_id_nbank_ca_fraud' => '通过身份证号查询非银-现金类分期资信不佳',
            'sl_id_nbank_ca_lost' => '通过身份证号查询非银-现金类分期高风险',
            'sl_id_nbank_com_fraud' => '通过身份证号查询非银-代偿类分期资信不佳',
            'sl_id_nbank_com_lost' => '通过身份证号查询非银-代偿类分期高风险',
            'sl_id_nbank_cf_fraud' => '通过身份证号查询非银-消费类分期资信不佳',
            'sl_id_nbank_cf_lost' => '通过身份证号查询非银-消费类分期高风险',
            'sl_id_nbank_other_fraud' => '通过身份证号查询非银-其他资信不佳',
            'sl_id_nbank_other_lost' => '通过身份证号查询非银-其他高风险',
            'sl_cell_abnormal' => '通过手机号查询高危行为',
            'sl_cell_bank_fraud' => '通过手机号查询银行(含信用卡)资信不佳',
            'sl_cell_bank_lost' => '通过手机号查询银行(含信用卡)高风险',
            'sl_cell_p2p_fraud' => '通过手机号查询非银(含全部非银类型)资信不佳',
            'sl_cell_p2p_lost' => '通过手机号查询非银(含全部非银类型)高风险',
            'sl_cell_nbank_p2p_fraud' => '通过手机号查询非银-P2P资信不佳',
            'sl_cell_nbank_p2p_lost' => '通过手机号查询非银-P2P高风险',
            'sl_cell_nbank_mc_fraud' => '通过手机号查询非银-小贷资信不佳',
            'sl_cell_nbank_mc_lost' => '通过手机号查询非银-小贷高风险',
            'sl_cell_nbank_ca_fraud' => '通过手机号查询非银-现金类分期资信不佳',
            'sl_cell_nbank_ca_lost' => '通过手机号查询非银-现金类分期高风险',
            'sl_cell_nbank_com_fraud' => '通过手机号查询非银-代偿类分期资信不佳',
            'sl_cell_nbank_com_lost' => '通过手机号查询非银-代偿类分期高风险',
            'sl_cell_nbank_cf_fraud' => '通过手机号查询非银-消费类分期资信不佳',
            'sl_cell_nbank_cf_lost' => '通过手机号查询非银-消费类分期高风险',
            'sl_cell_nbank_other_fraud' => '通过手机号查询非银-其他资信不佳',
            'sl_cell_nbank_other_lost' => '通过手机号查询非银-其他高风险'
        ];
        $count = 0;
        foreach ($arr as $key => $val) {
            foreach ($speciallist as $key1 => $val1) {
                if ($key == $key1 && $val == 0) {
                    $count ++;
                }
            }
        }
        $msg = '百融：匹配到数据';
        if($count == 0){
            $msg = '百融：暂未发现匹配数据';
        }
        return $result = ['code' => 1001,'count'=>$count ,'msg' => $msg];
    }

    /**

     * date 2017-03-06
     *
     * 百融-多次申请核查(近一周)
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBaiRongApplyLoanWeek($data, $params)
    {
        if (is_null($br = $data['br'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
        } else {
            $apply_loan = null;
            foreach ($br as $key => $val) {
                if ($val['type'] == CreditBr::APPLY_LOAN_STR) {
                    $apply_loan = json_decode($val['data'], true);
                }
            }
            if (is_null($apply_loan))
                return ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
            if ($apply_loan['code'] == 100002) {
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融-多次申请核查:暂未查询到用户信息',
                    'value' => 0
                ];
            } else {
                $num = array_key_exists('als_m1_id_nbank_orgnum', $apply_loan) ? $apply_loan['als_m1_id_nbank_orgnum'] : 0;
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融-多次申请核查:命中数' . $num,
                    'value' => $num
                ];

            }
        }
        return $result;
    }

    /**

     * date 2017-03-06
     *
     * 百融-多次申请核查V2(近一个月)
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBaiRongApplyLoanOneMonth($data, $params)
    {
        if (is_null($br = $data['br'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
        } else {
            $apply_loan = null;
            foreach ($br as $key => $val) {
                if ($val['type'] == CreditBr::APPLY_LOAN_STR) {
                    $apply_loan = json_decode($val['data'], true);
                }
            }
            if (is_null($apply_loan))
                return ['risk' => self::MEDIUM_RISK, 'detail' => '百融-多次申请核查:没有相关信息', 'value' => self::NULL];
            if ($apply_loan['code'] == 100002) {
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融-多次申请核查:暂未查询到用户信息',
                    'value' => 0
                ];
            } else {
                $num = array_key_exists('als_m1_id_nbank_orgnum', $apply_loan) ? $apply_loan['als_m1_id_nbank_orgnum'] : 0;
                $result = [
                    'risk' => self::LOW_RISK,
                    'detail' => '百融-多次申请核查:命中数' . $num,
                    'value' => $num
        ];
                }
            }
        return $result;
    }

    /**

     * date 2017-03-07
     *
     * 白骑士-决策信息
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBqsInfo($data, $params)
    {
        if (empty($data = $data['bqs'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '白骑士:没有相关信息', 'value' => self::NULL];
        } else {
            $value = self::NULL;
            if($data['finalDecision'] == 'Reject'){
                $value = 1;
            }elseif($data['finalDecision'] == 'Accept'){
                $value = 0;
            }else if($data['finalDecision'] == 'Review'){
                $value = 2;
            }
            $result = [
                'risk' => ($data['finalDecision'] == 'Accept') ? self::LOW_RISK : (($data['finalDecision'] == 'Review') ? self::MEDIUM_RISK : self::HIGH_RISK),
                'detail' => '白骑士决策信息：' . $data['finalDecision'],
                'value' => $value
            ];
        }
        return $result;
    }

    /**

     * date 2017-03-07
     *
     * 白骑士-决策信息
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBqsDecisionInfo($data)
    {
        if (empty($data = $data['bqs'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '白骑士:没有相关信息', 'value' => self::NULL];
        } else {

            $result = [
                'risk' => ($data['finalDecision'] == 'Accept') ? self::LOW_RISK : (($data['finalDecision'] == 'Review') ? self::MEDIUM_RISK : self::HIGH_RISK),
                'detail' => '白骑士决策信息：' . $data['finalDecision'],
                'value' =>  $data['finalDecision']
            ];
        }
        return $result;
    }

    /**
     * author 王成

     *
     * 蜜罐黑中介分
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgIntermediaryScore($data, $params)
    {

        $low = $params['low'];
        $high = $params['high'];

        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息', 'value' => self::NULL];
        } else {
            if (!empty($data['user_gray'])) {
                $score = $data['user_gray']['phone_gray_score'];
                $result = [
                    'risk' => ($score <= $high) ? self::HIGH_RISK : ($score <= $low ? self::MEDIUM_RISK : self::LOW_RISK),
                    'detail' => '蜜罐:黑中介分' . $score,
                    'value' => $score
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:无黑中介分', 'value' => 0];
            }
        }
        return $result;

    }

    /**
     * author 王成

     *
     * 蜜罐近1个月查询次数
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgSearchedHistory($data, $params)
    {
        $low = $params['low'];
        $high = $params['high'];

        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息', 'value' => self::NULL];
        } else {
            if (!empty($data['user_searched_history_by_orgs'])) {

                $this_month = date('Y-m', time());
                $last_month = date('Y-m', strtotime('last month'));
                $org_query_desc = [];
                foreach ($data['user_searched_history_by_orgs'] as $v) {
                    //匹配非本机构查询，且查询时间在本月和上月的数据
                    if (!$v['org_self'] && (preg_match("/^$this_month/", $v['searched_date']) || preg_match("/^$last_month/", $v['searched_date']))) {
                        $org_query_desc[] = $v['searched_org'];
                    }
                }
                $count = count($org_query_desc);
                $result = [
                    'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
                    'detail' => '蜜罐:近1个月内被机构查询' . $count . '次',
                    'value' => $count,
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:近1个月内未被机构查询', 'value' => 0];
            }
        }
        return $result;

    }

    /**
     *

     *
     * 蜜罐近7天查询次数
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgSearchedHistoryLastWeek($data, $params)
    {
        $low = $params['low'];
        $high = $params['high'];

        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息', 'value' => self::NULL];
        } else {
            if (!empty($data['user_searched_history_by_orgs'])) {

                $seven_day_before = date('Y-m-d', strtotime('-7 days'));
                $org_query_desc = [];
                foreach ($data['user_searched_history_by_orgs'] as $v) {
                    //匹配非本机构查询，且查询时间在7天内的数据
                    if (!$v['org_self'] && $v['searched_date'] >= $seven_day_before) {
                        $org_query_desc[] = $v['searched_org'];
                    }
                }
                $count = count($org_query_desc);
                $result = [
                    'risk' => ($count > $high) ? self::HIGH_RISK : ($count >= $low ? self::MEDIUM_RISK : self::LOW_RISK),
                    'detail' => '蜜罐:近7天内被其他机构查询' . $count . '次',
                    'value' => $count,
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:近7天内未被机构查询', 'value' => 0];
            }
        }
        return $result;

    }

    /**
     * author 王成

     *
     * 蜜罐身份证和姓名在黑名单
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgIdcardAndName($data, $params)
    {
        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息'];
        } else {
            if (!empty($data['user_blacklist']) && $data['user_blacklist']['blacklist_name_with_idcard']) {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '蜜罐:身份证和姓名在黑名单中'];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:身份证和姓名未在黑名单中'];
            }
        }
        return $result;
    }

    /**
     * author 王成

     *
     * 蜜罐手机和姓名在黑名单
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgPhoneAndName($data, $params)
    {
        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息'];
        } else {
            if (!empty($data['user_blacklist']) && $data['user_blacklist']['blacklist_name_with_phone']) {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '蜜罐:手机和姓名在黑名单'];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:手机和姓名未在黑名单'];
            }
        }
        return $result;
    }


    /**


     *
     * 还款逾期天数判断
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPaymentOverdueDays($data, $params)
    {
        $repayments = $data['user_loan_order_repayments'];
        if (empty($repayments)) {
            return ['risk' => self::LOW_RISK, 'detail' => '没有借款记录', 'value' => self::NULL];
        }

        $loan_data = '';
        foreach ($repayments as $key => $value) {
            if ($value['status'] != 0) {
                $loan_data = $value;
                break;
            }
        }

        if (empty($loan_data)) {
            return ['risk' => self::LOW_RISK, 'detail' => '没有借款记录', 'value' => self::NULL];
        }


        if ($loan_data['overdue_day'] > 10) {
            return ['risk' => self::HIGH_RISK, 'detail' => '还款逾期天数' . $loan_data['overdue_day'], 'value' => $loan_data['overdue_day']];
        } else if ($loan_data['overdue_day'] >= 1) {
            return ['risk' => self::LOW_RISK, 'detail' => '还款逾期天数' . $loan_data['overdue_day'], 'value' => $loan_data['overdue_day']];
        } else if ($loan_data['overdue_day'] == 0) {
            // $loan = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person->id])->andWhere(['<>', 'status', 0])->orderBy('created_at desc')->limit(3)->all(Yii::$app->get('db_kdkj_rd'));
//            $loan = [];
//            foreach ($repayments as $key => $value) {
//                if ($value['status'] != 0) {
//                    $loan[] = $value;
//                }
//                if (count($loan) >= 3) {
//                    break;
//                }
//            }
//
//            $time = 0;
//            foreach ($loan as $v) {
//                if ($v['overdue_day'] != 0) {
//                    break;
//                } else {
//                    if ($v['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
//                        $time++;
//                    }
//                }
//            }
            return ['risk' => self::LOW_RISK, 'detail' => '正常还款', 'value' => 0];
//            return ['risk' => self::LOW_RISK, 'detail' => '正常还款', 'value' => '连续' . $time . '次正常还款'];
        }
        return ['risk' => self::LOW_RISK, 'detail' => '没有借款记录', 'value' => self::NULL];
    }

    /**


     *
     * 拒单机构占比
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkJyInstitutionsProportion($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mg = CreditJy::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $mg = $data['credit_jy'];
        if (empty($mg)) {
            return ['risk' => self::LOW_RISK, 'detail' => '未获取对应征信信息', 'value' => self::NULL];
        }
        $loan_data = json_decode($mg['data'], true);
        if (empty($loan_data)) {
            return ['risk' => self::LOW_RISK, 'detail' => '查询到信息，但信息为空', 'value' => self::NULL];
        }
        $num = 0;

        if (count($loan_data) < 5) {
            return ['risk' => self::LOW_RISK, 'detail' => '拒单机构占比', 'value' => 0];
        }

        foreach ($loan_data as $v) {
            if ($v['borrowState'] == 1) {
                $num++;
            }
        }
        $v = round($num / count($loan_data), 2);

        return ['risk' => self::LOW_RISK, 'detail' => '拒单机构占比', 'value' => $v];
    }

    /**


     *
     * 还款状态
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkJyPaymentStatus($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mg = CreditJy::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $mg = $data['credit_jy'];
        if (empty($mg)) {
            return ['risk' => self::LOW_RISK, 'detail' => '未获取对应征信信息', 'value' => '无数据'];
        }
        $loan_data = json_decode($mg['data'], true);
        if (empty($loan_data)) {
            return ['risk' => self::LOW_RISK, 'detail' => '查询到信息，但信息为空', 'value' => '无数据'];
        }
        $num1 = 0;
        $num2 = 0;
        foreach ($loan_data as $v) {
            if ($v['repayState'] == 1) {
                $num1++;
            }
            if ($v['repayState'] == 9) {
                $num2++;
            }
        }
        if ($num2 > 0) {
            return ['risk' => self::LOW_RISK, 'detail' => '还款状态', 'value' => '存在已还清'];
        } else if ($num1 > 0) {
            return ['risk' => self::LOW_RISK, 'detail' => '还款状态', 'value' => '存在正常还款'];
        } else {
            return ['risk' => self::HIGH_RISK, 'detail' => '还款状态', 'value' => '仅存在未知'];
        }
    }


    /* 以下为禁止项规则 */


    /**
     * author 王成

     *
     * 宜信风险名单
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkYxzcRiskBlacklist($data, $params)
    {
        if (empty($data = $data['yx'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '宜信:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (empty($data['riskItems'])) {
                $result = ['risk' => self::LOW_RISK, 'detail' => '宜信:风险项0条', 'value' => self::LOW_RISK];
            } else {
                $info = $data['riskItems'];
                $count = count($info);
                if ($count > 0) {
                    $result = ['risk' => self::HIGH_RISK, 'detail' => '宜信:风险项' . $count . "条", 'value' => self::HIGH_RISK];
                } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '宜信:风险项0条', 'value' => self::LOW_RISK];
                }
            }

        }
        return $result;
    }


    /**
     *

     *
     * 宜信借款逾期
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkYxzcBlackLoanOverdue($data, $params)
    {

        if (empty($data = $data['yx'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '宜信:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            $info = $data['loanRecords'];
            $current_overdue_count = 0;

            if (count($info) > 0) {
                foreach ($info as $v) {
                    if ($v['currentStatus'] == '逾期') {
                        $current_overdue_count += 1;
                    }
                }
            }

            if ($current_overdue_count > 0) {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '宜信:逾期' . $current_overdue_count . '次', 'value' => self::HIGH_RISK];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '宜信:逾期' . $current_overdue_count . '次', 'value' => self::LOW_RISK];
            }
        }
        return $result;

    }


    /**
     * author 王成

     *
     * 芝麻信用行业关注名单逾期未处理
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZmWatchList($data, $params)
    {
        //是否需要匹配当前逾期未处理
        if (is_null($zm = $data['zm']) || empty($zm['watch_matched'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻:未命中行业关注名单', 'value' => self::LOW_RISK];
            if ($zm['watch_matched'] == CreditZmop::WATCH_TURE && !empty($zm['watch_info'])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:命中行业关注名单，但不存在逾期未处理订单。', 'value' => self::MEDIUM_RISK];
                foreach (json_decode($zm['watch_info'], true) as $v) {
                    foreach ($v['extend_info'] as $item) {
                        if ($item['description'] == '当前逾期状态' && $item['value'] == '逾期待处理') {
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '芝麻:芝麻:命中行业关注名单，且存在逾期未处理订单。', 'value' => self::HIGH_RISK];
                        }
                    }
                }
            }
        }
        return $result;
    }


    /**
     *

     *
     * 芝麻信用行业关注名单黑名单
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZmBlackList($data, $params)
    {
        //是否需要匹配当前逾期未处理
        if (is_null($zm = $data['zm']) || empty($zm['watch_matched'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻:未命中行业关注名单', 'value' => self::LOW_RISK];
            if ($zm['watch_matched'] == CreditZmop::WATCH_TURE && !empty($zm['watch_info'])) {
                $black_list = ['R00501', 'R01001', 'R01101', 'R01201', 'R01301', 'R01401', 'R01501', 'R03201', 'R01901', 'R02101', 'R02403', 'R02404', 'R02406',
                    'R00102', 'R00103', 'R00104', 'R00105', 'R00106', 'R00107',
                    'R00122', 'R00123', 'R00124', 'R00125', 'R00126', 'R00127'
                ];
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻:命中行业关注名单，但未命中黑名单', 'value' => self::MEDIUM_RISK];
                foreach (json_decode($zm['watch_info'], true) as $v) {
                    if (in_array($v['code'], $black_list)) {
                        $result = ['risk' => self::HIGH_RISK, 'detail' => '芝麻:命中行业关注名单，且命中黑名单', 'value' => self::HIGH_RISK];
                    }
                }
            }
        }
        return $result;
    }


    /**
     *

     *
     * 中智诚黑名单
     *
     * param    array
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZzcBlacklist($data, $params)
    {

        if (empty($data = $data['zzc'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '中智诚:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            $count = $data['count'];
            $result = [
                'risk' => $count > 0 ? self::HIGH_RISK : self::LOW_RISK,
                'detail' => '中智诚:匹配到' . $count . '条黑名单记录',
                'value' => $count > 0 ? self::HIGH_RISK : self::LOW_RISK,
            ];

        }
        return $result;
    }


    public function checkZzcHighRisklist($data, $params)
    {

        return [
            'risk' => self::LOW_RISK, 'detail' => '等待实现', 'value' => self::LOW_RISK
        ];
    }

    public function checkJsqbBlacklist($data, $params)
    {
        if (empty($data = $data['jsqb'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '极速钱包:没有相关信息', 'value' => self::NULL];
        } else {
            if (isset($data['is_blacklist']) && $data['is_blacklist']) {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '极速钱包:被标记黑名单', 'value' => self::YES];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '极速钱包:未被标记黑名单', 'value' => self::NO];
            }
        }

        return $result;
    }

    public function checkJsqbWhitelist($data, $params)
    {
        if (empty($data = $data['jsqb'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '极速钱包:没有相关信息', 'value' => self::NULL];
        } else {
            if (isset($data['is_whitelist']) && $data['is_whitelist']) {
                $result = ['risk' => self::LOW_RISK, 'detail' => '极速钱包:被标记白名单', 'value' => self::YES];
            } else {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '极速钱包:未被标记白名单', 'value' => self::NO];
            }
        }

        return $result;
    }

    /**
     * author 王成

     *
     * 蜜罐被标记黑名单判断
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgBlacklist($data, $params)
    {
        if (empty($data = $data['mg'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (!empty($data['user_blacklist']) && !empty($data['user_blacklist']['blacklist_category'])) {
                $result = ['risk' => self::HIGH_RISK, 'detail' => '蜜罐:被标记黑名单', 'value' => self::HIGH_RISK];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '蜜罐:未被标记黑名单', 'value' => self::LOW_RISK];
            }
        }

        return $result;
    }

    /**
     * 葫芦索伦被标记黑名单判断
     * param    array            索伦报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSauronBlackList($data, $params)
    {
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
        } else {
            if (is_array($data['risk_blacklist'])) {
                $keys = array("in_court_blacklist", "in_p2p_blacklist", "idcard_in_blacklist", "phone_in_blacklist", "in_bank_blacklist");
                $c = false;
                foreach ($keys as $key) {
                    $c = $c || boolval($data['risk_blacklist'][$key]);
                }
                if ($c) {
                    $result = ['risk' => self::HIGH_RISK, 'detail' => '索伦:被标记黑名单', 'value' => self::HIGH_RISK];
                } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:未被标记黑名单', 'value' => self::LOW_RISK];
                }
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }

    /**
     * 直接联系人在黑名单中数量
     */
    public function checkContactsBlack($data){
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (isset($data['risk_social_network'])) {
                $res = $data['risk_social_network'];
                $keys = isset($res['sn_order1_blacklist_contacts_cnt']);
                if ($keys != false) {
                    $result = ['risk' => self::HIGH_RISK, 'detail' => '索伦:直接联系人在黑名单中数量', 'value' => $res['sn_order1_blacklist_contacts_cnt']];
                } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:直接联系人在黑名单中数量', 'value' => self::LOW_RISK];
                }
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }

    /**
     * 最近7天查询次数
     * param    array            索伦报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSauronCheck7($data, $params)
    {
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (isset($data['history_search']['search_cnt_recent_7_days'])){
                $count = $data['history_search']['search_cnt_recent_7_days'];
                $result = [
                    'risk' => self::HIGH_RISK,
                    'detail' => '索伦:最近7天查询次数',
                    'value' => $count
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }
    /**
     * 最近14天查询次数
     * param    array            索伦报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSauronCheck14($data, $params)
    {
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (isset($data['history_search']['search_cnt_recent_14_days'])){
                $count = $data['history_search']['search_cnt_recent_14_days'];
                $result = [
                    'risk' => self::HIGH_RISK,
                    'detail' => '索伦:最近14天查询次数',
                    'value' => $count
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }
    /**
     * 最近30天查询次数
     * param    array            索伦报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSauronCheck30($data, $params)
    {
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (isset($data['history_search']['search_cnt_recent_30_days'])){
                $count = $data['history_search']['search_cnt_recent_30_days'];
                $result = [
                    'risk' => self::HIGH_RISK,
                    'detail' => '索伦:最近30天查询次数',
                    'value' => $count
                ];
            } else {
                $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }
    /**
     * 最近90天查询次数
     * param    array            索伦报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSauronCheck90($data, $params)
    {
        if (empty($data = $data['sauron'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::MEDIUM_RISK];
        } else {
            if (isset($data['history_search']['search_cnt_recent_90_days'])){
                $count = $data['history_search']['search_cnt_recent_90_days'];
                    $result = [
                        'risk' => self::HIGH_RISK,
                        'detail' => '索伦:最近90天查询次数',
                        'value' => $count
                    ];
            } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '索伦:没有相关信息', 'value' => self::LOW_RISK];
            }
        }
        return $result;
    }


    /**
     * 同盾手机网贷黑名单
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdPhoneBlackList($data, $params) {
        $td = $data['td'];
        if (empty($td)  || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "未命中同盾手机网贷黑名单";
        $risk = self::LOW_RISK;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132514:
                    //手机号命中信贷逾期名单
                    $detail = json_encode($v['item_detail']);
                    $risk = self::HIGH_RISK;
                    break;
                default:
                    break;
            }
            if ($risk == self::HIGH_RISK) {
                break;
            }
        }

        return [
            'risk' => $risk,
            'detail' => $detail,
            'value' => $risk
        ];
    }

    /**
     *

     *
     * 同盾身份证网贷黑名单
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkTdIdCardBlackList($data, $params)
    {

        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $detail = "未命中身份证网贷黑名单";
        $risk = self::LOW_RISK;
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132764:
                    //身份证明中信贷黑名单
                    $detail = json_encode($v['item_detail']);
                    $risk = self::HIGH_RISK;
                    break;
                default:
                    break;
            }
            if ($risk == self::HIGH_RISK) {
                break;
            }
        }

        return [
            'risk' => $risk,
            'detail' => $detail,
            'value' => $risk
        ];

    }

    public function checkTdBlackList($data, $params)
    {
        $td = $data['td'];
        if (empty($td)  || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case 21132024:
                    //身份证命中法院失信名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中法院失信名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132154:
                    //身份证命中犯罪通缉名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中犯罪通缉名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132204:
                    //身份证命中法院执行名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中法院执行名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132244:
                    //身份证对应人存在助学贷款欠费历史
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证对应人存在助学贷款欠费历史', 'value' => self::HIGH_RISK];
                    break;
                case 21132764:
                    //身份证命中信贷逾期后还款名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中信贷逾期后还款名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132514:
                    //手机号命中信贷逾期后还款名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中信贷逾期后还款名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132434:
                    //身份证命中车辆租赁违约名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中车辆租赁违约名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132484:
                    //身份证命中法院结案名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中法院结案名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132664:
                    //身份证命中欠款公司法人代表名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中欠款公司法人代表名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132744:
                    //身份证命中欠税公司法人代表名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中欠税公司法人代表名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132714:
                    //身份证命中欠税名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中欠税名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132294:
                    //身份证命中中风险关注名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '身份证命中高风险关注名单', 'value' => self::HIGH_RISK];
                    break;
                case 21131714:
                    //手机号命中同盾虚假号码库
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中虚假号码库', 'value' => self::HIGH_RISK];
                    break;
                case 21131864:
                    //手机号命中通信小号库
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中通信小号库', 'value' => self::HIGH_RISK];
                    break;
                case 21132014:
                    //手机号命中诈骗骚扰库
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中诈骗骚扰库', 'value' => self::HIGH_RISK];
                    break;
                case 21132224:
                    //手机号命中高风险关注名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中高风险关注名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132314:
                    //手机号命中信贷逾期名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中信贷逾期名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132454:
                    //手机号命中车辆租赁违约名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中车辆租赁违约名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132494:
                    //手机号命中欠款公司法人代表名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '手机号命中欠款公司法人代表名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132184:
                    //邮箱命中信贷逾期后还款名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '邮箱命中信贷逾期后还款名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132854:
                    //地址信息命中信贷逾期名单
                    return ['risk' => self::HIGH_RISK, 'detail' => '地址信息命中信贷逾期名单', 'value' => self::HIGH_RISK];
                    break;
                case 21132694:
                    //单位名称疑似中介关键词
                    return ['risk' => self::HIGH_RISK, 'detail' => '单位名称疑似中介关键词', 'value' => self::HIGH_RISK];
                    break;

            }
        }

        return ['risk' => self::LOW_RISK, 'detail' => '未命中黑名单', 'value' => self::LOW_RISK];

    }


    /**
     *

     *
     * 91逾期
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkJyOverdue($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mg = CreditJy::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $mg = $data['credit_jy'];
        if (empty($mg)) {
            return ['risk' => self::LOW_RISK, 'detail' => '未获取对应征信信息', 'value' => self::LOW_RISK];
        }
        $loan_data = json_decode($mg['data'], true);
        if (empty($loan_data)) {
            return ['risk' => self::LOW_RISK, 'detail' => '查询到信息，但信息为空', 'value' => self::LOW_RISK];
        }
        $num = 0;
        foreach ($loan_data as $v) {
            if (in_array($v['repayState'], [2, 3, 4, 5, 6, 7, 8])) {
                $num++;
            }
        }
        if ($num > 0) {
            return ['risk' => self::HIGH_RISK, 'detail' => '逾期' . $num . '次', 'value' => self::HIGH_RISK];
        } else {
            return ['risk' => self::LOW_RISK, 'detail' => '还款状态', 'value' => self::LOW_RISK];
        }
    }

    /**

     * date 2017-03-06
     *
     * 白骑士决策信息
     *
     * param    array            白骑士报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkWhileRideBlackList($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '白骑士决策信息', 'value' => '等待实现'];
        return $result;
    }

    /**
     *

     *
     * 实名认证规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkRealName($data, $params)
    {
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::MEDIUM_RISK];
        if (isset($data['real_name_status'])) {
            if ($data['real_name_status'] == 0) {
                $is_jxl_realname=false;$detail='';
                $creditJxl = CreditJxl::findLatestOne(['person_id'=>$loan_person->id]);
                if(!is_null($creditJxl) && !empty($creditJxl['data'])){
                    $jxldata = json_decode($creditJxl['data'],true);
                    if(strstr($jxldata['application_check'][2]['check_points']['check_name'],'匹配成功') || strstr($jxldata['application_check'][2]['check_points']['check_idcard'],'匹配成功')){
                        $is_jxl_realname=true;
                        if(strstr($jxldata['application_check'][2]['check_points']['check_name'],'匹配成功')){
                            $detail='用户姓名匹配成功';
                        }
                        if(strstr($jxldata['application_check'][2]['check_points']['check_idcard'],'匹配成功')){
                            if($detail!=''){
                                $detail.='，';
                            }
                            $detail.='用户身份证号匹配成功';
                        }
                    }
                }
                unset($creditJxl);
                if($is_jxl_realname){
                    $result = ['risk' => self::THREE, 'detail' => '运营商：'.$detail, 'value' => self::THREE];
                }else{
                    $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：用户未实名', 'value' => self::HIGH_RISK];
                }
            } else {
                if (isset($data['real_name_name']) && $data['real_name_id_card']) {
                    if ($data['real_name_name'] == $loan_person->name && strtoupper($data['real_name_id_card']) == strtoupper($loan_person->id_number)) {
                        $result = ['risk' => self::LOW_RISK, 'detail' => '运营商：用户实名认证，身份证和姓名已校验', 'value' => self::LOW_RISK];
                    } else {
                        $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：用户已实名，但是身份证和姓名不对应', 'value' => self::HIGH_RISK];
                    }
                } else {
                    $result = ['risk' => self::LOW_RISK, 'detail' => '运营商：用户实名认证，身份证和姓名未校验', 'value' => self::LOW_RISK];
                }
            }
        }
        return $result;
    }

    /**


     *
     * 支付宝实名认证规则
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkZfbIsRealName($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    public function dateSwitch($date)
    {
        $date = str_replace('年', '-', $date);
        $date = str_replace('月', '-', $date);
        $date = str_replace('日', '', $date);
        return $date;

    }

    /**


     *
     * 注册天数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkRegisterDays($data, $params)
    {
        return $result = ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];

        return $result;
    }

    /**


     *
     * 花呗额度
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkHbHilt($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:用户未开通花呗', 'value' => self::NULL];
    }

    /**


     *
     * 花呗可用额度
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkHbUsableAmount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:用户未开通花呗', 'value' => self::NULL];
    }

    /**


     *
     * 花呗欠款
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkHbArrears($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:用户未开通花呗', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月是否缴纳过水/电/煤费
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */

    public function checkAccountNumber($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近一个月三项缴纳金额合计
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */

    public function checkOneMonthMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月三项缴纳金额合计
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */

    public function checkThreeMonthMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月三项平均单笔缴纳金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkEverPayMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 整理数据为数组
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function organizeGroupData($info)
    {
        $arr = explode('详情', $info);
        $a = [];
        $result = [];
        foreach ($arr as $k => $value) {
            if (!$value) {
                continue;
            }
            if ($k == 0) {
                $value = "\n" . $value;
            }

            $x = explode("\n", $value);

            $a['date'] = str_replace('.', '-', isset($x[1]) ? $x[1] : "");
            $a['name'] = isset($x[3]) ? $x[3] : "";
            $a['orderNo'] = isset($x[4]) ? $x[4] : "";
            $a['other_party'] = isset($x[5]) ? $x[5] : "";
            $patterns = "/\d+/";
            preg_match_all($patterns, isset($x[6]) ? $x[6] : "", $s);
            $hilt = isset($s[0][0]) ? $s[0][0] : "";
            $a['deal_amount'] = $hilt;
            $a['status'] = isset($x[7]) ? $x[7] : "";
            $result[] = $a;
        }
        return $result;
    }

    /**


     *
     * 整理数据为数组
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkConsumeItem($info)
    {
        $arr = explode('详情', $info);
        $result = [];
        foreach ($arr as $k => $value) {
            if (!$value) {
                continue;
            }
            if ($k == 0) {
                $value = "\n" . $value;
            }
            $x = explode("\n", $value);

            $a['deal_amount'] = isset($x[6]) ? $x[6] : "";
            $a['date'] = str_replace('.', '-', isset($x[1]) ? $x[1] : "");
            $result[] = $a;
        }
        return $result;
    }

    /**


     *
     * 三个月转账次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthNum($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月转账金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthTransferMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月转账次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthNum($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月转账金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthTransferMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近3个月月均转账金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkAvaMonthTransferMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月购买火车票次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthTicket($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月购买火车票金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthTicketMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月购买火车票次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthBuyTicket($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内购买火车票金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthTicketMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月购买电影票次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthFilm($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月购买电影票金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthFilmMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月购买电影票次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthBuyFilm($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内购买电影票金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthFilmMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 电影票地址对应情况
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMatchAddress($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];

    }

    /**


     *
     * 获取用户常登录地址
     *
     * param    array            支付宝信息
     * param    array            默认配置
     */
    public function userNomalAddress($data)
    {

        // $address = UserQuotaPersonInfo::find()->where(['user_id' => $user_id])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        // $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        $address = $data['user_quota_person_info'];

        $detail_address = $address['address_distinct'];
        $privince = mb_substr($detail_address, 0, 2);
        $city = mb_substr($detail_address, 5, 2);
        $data = [
            'privince' => $privince,
            'city' => $city
        ];

        return $data;

    }

    /**


     *
     * 三个月滴滴消费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthDidi($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];

    }

    /**


     *
     * 近三个月滴滴出行金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthDidiMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];

    }

    /**


     *
     * 一个月滴滴出行次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthBuyDidi($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内滴滴出行金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthDidiMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月便利店消费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthBuyStore($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内便利店消费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthStoreMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月便利店消费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthStore($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月便利店消费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthStoreMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 近三个月便利店消费地址对应
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthStoreEqual($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月手机缴费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthMobileFee($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内手机缴费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthMobileCount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月手机缴费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthMobileFee($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月内手机缴费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthMobileCount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月内手机缴费单笔缴费平均金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthMobileAvage($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月房租缴费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthRent($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月内房租缴费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthRentCount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月房租缴费次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthRentFee($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月内房租缴费金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthRentCount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月支出次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthExpendCount($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月支出金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthExpendMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月支出次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthExpend($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月支出金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthExpendMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月月均支出金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthAvageMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月收入次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthIncome($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月收入金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthIncomeMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月收入次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthIncome($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月收入金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthIncomeMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月月均收入金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthAvageIncome($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 总资产
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkAllWealth($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月信用卡还款次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthCreditCards($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 一个月信用卡还款金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOneMonthCreditCardsMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月信用卡还款次数
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthCreditCards($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝:没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 三个月信用卡还款金额
     *
     * param    array            支付宝信息
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthCreditCardsMoney($data, $params)
    {
        return ['risk' => self::MEDIUM_RISK, 'detail' => '支付宝：没有相关信息', 'value' => self::NULL];
    }

    /**


     *
     * 1个月内逾期短信中逾期天数超过3天
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserOverdueMessage($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '逾期短信：一个月内没有逾期超过3天的短信', 'value' => self::NULL];
        $user = UserPhoneMessage::find()->where(['user_id' => $loan_person->id])->orderBy('message_date desc')->asArray()->limit(1)->one(Yii::$app->get('db_kdkj_rd'));
        $new_date = $user['message_date'];
        $start = date('Y-m-d', strtotime("$new_date-1 month"));
        $where = 'user_id=' . $loan_person->id;
        $where = $where . ' and message_date>="' . $start . '"';
        $message = UserPhoneMessage::find()->where($where)->asArray()->orderBy('message_date')->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($message as $item) {
            $content = TemplateList::overdueTemplate($item, 3);
            if ($content) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => $content, 'value' => self::YES];
                break;
            }
        }
        return $result;
    }

    /**


     *
     * 1个月之前逾期短信中逾期天数超过30天
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkOverdueMonthMessage($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '逾期短信：一个月之前没有逾期超过30天的短信', 'value' => self::NULL];
        $user = UserPhoneMessage::find()->where(['user_id' => $loan_person->id])->orderBy('message_date desc')->asArray()->one(Yii::$app->get('db_kdkj_rd'));
        $new_date = $user['message_date'];
        $start = date('Y-m-d', strtotime("$new_date-1 month"));
        $where = 'user_id=' . $loan_person->id;
        $where = $where . ' and message_date<="' . $start . '"';
        $message = UserPhoneMessage::find()->where($where)->asArray()->orderBy('message_date')->all(Yii::$app->get('db_kdkj_rd'));
        foreach ($message as $item) {
            $content = TemplateList::overdueTemplate($item, 30);
            if ($content) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => $content, 'value' => self::YES];
                break;
            }
        }
        return $result;
    }

    /**
     *

     *
     * 用户还款所增加的额度
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkAddCreditLines($data, $params)
    {

        $loan_person = $data['loan_person'];
        // if (empty($data['order'])) {
        //     $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有额度记录表', 'value' => 0];
        //     return $result;
        // }
        // $order = $data['order'];
        // $userCreditTotal = UserCreditTotal::find()->where(['user_id'=>$loan_person->id])->one(Yii::$app->get('db_kdkj_rd'));
        // $creditChannelService = \Yii::$app->creditChannelService;
        // $userCreditTotal = $creditChannelService->getCreditTotalByUserAndOrder($loan_person->id, $order->id);
        $userCreditTotal = $data['user_credit_total'];

        if (empty($userCreditTotal)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有额度记录表', 'value' => 0];
        } else {
            $addCreditLines = $userCreditTotal['repayment_credit_add'] / 100;
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '增加额度为' . $addCreditLines, 'value' => $addCreditLines];
        }
        return $result;

    }

    /**
     *

     *
     * 用户是否是老用户
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsOldUser($data)
    {
        $loan_person = $data['loan_person'];
        $repayments = $data['user_loan_order_repayments'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '不是老用户', 'value' => self::NO];
        if (!empty($repayments) && isset($repayments[0])) {
            $repayment = $repayments[0];
            $time = time();
            //没有还款记录或者最近还款记录大于一个月
            if ($loan_person->customer_type==LoanPerson::CUSTOMER_TYPE_OLD && (($time - $repayment['updated_at']) <= 31 * 86400) && $repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '老用户', 'value' => self::YES];
            }
        }

        return $result;

    }

    /**
     *

     *
     * 催收建议
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserLoanCollection($data)
    {
        $collection = $data['loan_collection_order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '催收建议审核，,或无建议', 'value' => self::OTHER];
        if (!empty($collection)) {
            if (in_array($collection['next_loan_advice'], [LoanCollectionOrder::RENEW_REJECT])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '催收建议拒绝', 'value' => self::NO];
            } elseif (in_array($collection['next_loan_advice'], [LoanCollectionOrder::RENEW_DEFAULT, LoanCollectionOrder::RENEW_CHECK])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '催收建议审核，,或无建议', 'value' => self::OTHER];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '催收建议通过', 'value' => self::YES];
            }

        }

        return $result;

    }

    /**
     *

     *
     * 逾期天数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserLoanOrderRepayment($data, $params)
    {
        $repayments = $data['user_loan_order_repayments'];

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有还款单', 'value' => 0];
        if (!empty($repayments) && !isset($repayments[0])) {
            $repayment = $repayments[0];
            if (!empty($repayment) && isset($repayment['overdue_day'])) {
                $overdue_day = $repayment['overdue_day'];
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '逾期天数' . $overdue_day, 'value' => $overdue_day];
            }
        }

        return $result;
    }

    /**
     *

     *
     * 身份证照片数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserProofMateria($data, $params)
    {

        $loan_person = $data['loan_person'];

        // $img = UserProofMateria::find()->where(['user_id' => $loan_person->id, 'status' => UserProofMateria::STATUS_NORMAL])
        //     ->andWhere(['in', 'type', [
        //         UserProofMateria::TYPE_ID_CAR,
        //         UserProofMateria::TYPE_ID_CAR_Z,
        //         UserProofMateria::TYPE_ID_CAR_F,
        //         UserProofMateria::TYPE_FACE_RECOGNITION,
        //     ]])->all();

        $img = $data['user_proof_materia'];

        if (empty($img)) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '身份证照片数量为0', 'value' => 0];
        }

        return ['risk' => self::MEDIUM_RISK, 'detail' => '身份证照片数量为' . count($img), 'value' => count($img)];

    }

    public function checkUserLoanAmount($data, $params)
    {
        $loan_person = $data['loan_person'];

        $borrowingAmount = 0;

        // $result = UserLoanOrder::find()->where(['user_id' => $loan_person->id])
        //     ->andWhere(['not in', 'status', [
        //         UserLoanOrder::STATUS_REPAY_REPEAT_CANCEL,
        //         UserLoanOrder::STATUS_REPAY_CANCEL,
        //         UserLoanOrder::STATUS_PENDING_CANCEL,
        //         UserLoanOrder::STATUS_REPEAT_CANCEL,
        //         UserLoanOrder::STATUS_CANCEL,
        //         UserLoanOrder::STATUS_REPAY_COMPLETE,
        //         10000,
        //         10001]])->all();

        $result = $data['usable_user_loan_orders'];

        if (!empty($result)) {
            foreach ($result as $value) {
                $borrowingAmount += $value['money_amount'] / 100;
            }
        }

        return ['risk' => self::MEDIUM_RISK, 'detail' => '借款总金额' . $borrowingAmount, 'value' => $borrowingAmount];
    }

    // 当前用户借款中的数量
    public function checkUserUsableLoanOrderCount($data, $params)
    {
        $count = 0;

        $result = $data['usable_user_loan_orders'];

        if (!empty($result)) {
            $count = count($result);
        }

        return ['risk' => self::MEDIUM_RISK, 'detail' => '当前进行中的借款数量：' . $count, 'value' => $count];
    }

    public function checkUserName($data, $params)
    {
        $loan_person = $data['loan_person'];
        return ['risk' => self::MEDIUM_RISK, 'detail' => $loan_person->name, 'value' => $loan_person->name];
    }

    public function checkCity($data, $params)
    {
        $loan_person = $data['loan_person'];

        $id_number_address = "暂无";
        $id_number = $loan_person['id_number'];
        if (!empty($id_number) && ToolsUtil::checkIdNumber($id_number)) {
            $id_number_address = ToolsUtil::get_addr($id_number);
        }

        if (empty($id_number_address)) {
            $id_number_address = "暂无";
        }

        $condition = [
            [
                "condition" => ["上海", "北京", "广州", "深圳", "天津", "杭州", "南京", "济南", "重庆", "青岛", "大连", "宁波", "厦门"],
                "score" => 100
            ],
            [
                "condition" => ["三亚", "海口", "金华", "唐山", "九江", "宜昌", "赣州", "泰安", "榆林", "许昌", "新乡", "舟山", "慈溪", "南阳", "聊城", "东营", "黄石", "淄博", "漳州", "保定", "沧州", "丹东", "宜兴", "绍兴", "湖州", "揭阳", "江阴", "营口", "衡阳", "郴州", "鄂尔多斯", "泰州", "义乌", "汕头", "宜昌", "大同", "鞍山", "湘潭", "盐城", "马鞍山", "襄樊", "长治", "日照", "襄阳", "常熟", "安庆", "吉林", "乌鲁木齐", "兰州", "秦皇岛", "肇庆", "西宁", "介休", "滨州", "台州", "廊坊", "邢台", "株洲", "常德", "德阳", "绵阳", "双流", "平顶山", "龙岩", "银川", "芜湖", "晋江", "连云港", "张家港", "锦州", "岳阳", "长沙县", "济宁", "邯郸", "江门", "齐齐哈尔", "昆山", "柳州", "绍兴县", "运城", "齐河"],
                "score" => 50
            ]
        ];

        foreach ($condition as $item) {
            if ($this->inCodition(Rule::P_TYPE_ARRAY, $id_number_address, $item['condition'], 'similar')) {
                return ['risk' => self::LOW_RISK, 'detail' => $id_number_address, 'value' => $item['score']];
            }
        }

        return ['risk' => self::LOW_RISK, 'detail' => $id_number_address, 'value' => 0];
    }

    private function inCodition($type, $target, $condition, $compare)
    {
        if ($type == Rule::P_TYPE_SECTION) {
            return $condition[0] <= $target && $condition[1] >= $target;
        } else if ($type == Rule::P_TYPE_ARRAY) {
            if ($compare == 'equal') {
                return in_array($target, $condition);
            } elseif ($compare == 'inside') {
                foreach ($condition as $value) {
                    if (strpos($value, $target) !== false) {
                        return true;
                    }
                }
                return false;
            } elseif ($compare == 'include') {
                foreach ($condition as $value) {
                    if (strpos($target, $value) !== false) {
                        return true;
                    }
                }
                return false;
            } elseif ($compare == 'similar') {
                foreach ($condition as $value) {
                    if (strpos($target, $value) !== false || strpos($value, $target) !== false) {
                        return true;
                    }
                }
                return false;
            }
            return false;
        } else {
            return false;
        }
    }

    public function checkFaceDetection($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        $face = CreditFacePlus::find()->where(['user_id' => $loan_person->id])->one();
        if (!is_null($face)) {
            if ($face['confidence'] > $face['1e-5']) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '人脸识别通过', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '人脸识别不通过', 'value' => self::NO];
            }
        }
        return $result;

    }

    public function checkFaceDetectionScore($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        $face = CreditFacePlus::find()->where(['user_id' => $loan_person->id])->one();
        if (!is_null($face)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '人脸识别通过', 'value' => $face['confidence']];
        }
        return $result;

    }


    public function checkOrderSource($data, $params)
    {
        if (empty($data['order'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '', 'value' => -1];
            return $result;
        }
        $order = $data['order'];

        $from_app = $order->from_app;

        $result = ['risk' => self::MEDIUM_RISK,
            'detail' => '订单来源' . (isset(UserLoanOrder::$from_apps[$from_app]) ? UserLoanOrder::$from_apps[$from_app] : "未知"),
            'value' => $from_app];

        return $result;
    }

    public function checkPersonSource($data, $params)
    {
        $loan_person = $data['loan_person'];

        $from_app = $loan_person['source_id'];

        new LoanPerson();
        $result = ['risk' => self::MEDIUM_RISK,
            'detail' => '用户来源' . (isset(LoanPerson::$person_source[$from_app]) ? LoanPerson::$person_source[$from_app] : "未知"),
            'value' => $from_app];

        return $result;
    }

    /**


     *
     *每个月话费是否都大于100元
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkEverMonthMobileFee($data, $params)
    {
        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (isset($data['bill_list'])) {
            $flag = 1;
            foreach ($data['bill_list'] as $item) {
                if ($item['amount'] < 100) {
                    $flag = 0;
                    break;
                }
            }
            if ($flag) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：每个月话费都大于100元', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：不是每个月话费都大于100元', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     *通讯录重合并且通话时间10分钟以上的人数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactReclose($data, $params)
    {
        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];
        if (isset($data['contact_list']) && isset($data['common_contactors'])) {
            $count = 0;
            $mobiles = [];
            foreach ($data['common_contactors'] as $contactor) {
                $mobiles[] = $contactor['phone'];
            }

            foreach ($data['contact_list'] as $item) {
                if (in_array($item['phone'], $mobiles) && $item['talk_seconds'] > 10) {
                    $count++;
                }
            }
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：通讯录重合并且通话时间10分钟以上的人数为' . $count . '人', 'value' => $count];
        }
        return $result;
    }


    /**
     *
     * 判断通讯录手机号去重数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkDistinctContactCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        // $user_mobile_contacts = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $mobile_contact_list[$v['mobile']] = $v['mobile'];
            }
        }
        $count = count($mobile_contact_list);
        return ['risk' => self::MEDIUM_RISK, 'detail' => '通讯录号码去重数量' . $count, 'value' => $count];
    }

    /**
     *
     * 判断通讯录姓名数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactName($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_name_list = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $mobile_contact_name_list[] = $v['name'];
            }
        }
        if (isset($params['distinct']) && $params['distinct']) {
            $count = count(array_unique($mobile_contact_name_list));
            $detail = '通讯录姓名去重数量' . $count;
        } else {
            $count = count($mobile_contact_name_list);
            $detail = '通讯录姓名数量' . $count;
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录号码类型数量
     *
     * param    array            data
     * param    array            默认配置(type 1:手机,2:固话,3:短号,默认:1)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactTypeCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $phones = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $phone = str_replace('+86', '', $v['mobile']);
                $phones[$phone] = $phone;
            }
        }
        $ret = $this->getPhoneTypeCount($phones);
        $count = $ret['mobile_count'];
        $detail = "通讯录手机号数量";
        if (isset($params['type'])) {
            if ($params['type'] == 2) {
                $count = $ret['fixed_count'];
                $detail = "通讯录固话数量";
            } else if ($params['type'] == 3) {
                $count = $ret['short_count'];
                $detail = "通讯录短号数量";
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录号码标签类型数量
     *
     * param    array            data
     * param    array            默认配置(tiptype null:未定义,网络电话,小贷,高危关键词...   ,默认null)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactTipTypeCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        $count = 0;
        $detail = "无通讯录号码相关信息";
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $mobile_contact_list[] = $v['mobile'];
            }
            $phones = array_unique($mobile_contact_list);
            $ret = $this->getCategoryCount($phones, $params);
            $detail = "通讯录号码" . $ret['detail'];
            $count = $ret['value'];
        }

        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * param    array            号码列表
     * param    array            默认配置(tiptype null:未定义,网络电话,小贷,高危关键词...   ,默认null)
     */
    private function getCategoryCount($phones, $params)
    {
        $query = MobileContactsReportMongo::find()->select(['mobile']);
        if (isset($params['tiptype']) && !empty($params['tiptype'])) {
            $tips = explode('/', $params['tiptype']);
            foreach ($tips as $tip) {
                $query->andWhere(['like', 'category', $tip]);
            }
            $count = count($query->andWhere(['in', 'mobile', $phones])->distinct('mobile'));
            $detail = "命中标签:" . $params['tiptype'] . "的号码数";
        } else {
            $count = count($phones) - count($query->where(['in', 'mobile', $phones])->distinct('mobile'));
            $detail = "其他未能定义标签的号码数";
        }
        return ['detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录号码有电话联系数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactCallCount($data, $params)
    {
        $count = 0;
        $detail = "通讯录号码有电话联系数量";
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $mobile_contact_list[$v['mobile']] = $v;
            }
            if (!empty($data['contact_list'])) {
                foreach ($data['contact_list'] as $value) {
                    if (isset($mobile_contact_list[$value['phone']])) {
                        $count++;
                        continue;
                    }
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录号码有联系的手机号码数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactCallMobileCount($data, $params)
    {
        $count = 0;
        $detail = "通讯通讯录号码有联系的手机号码数量";
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $tmp_phone = str_replace('+86', '', $v['mobile']);
                $tmp_phone = str_replace('-', '', $tmp_phone);
                if (preg_match($isMobile, $tmp_phone)) {
                    $mobile_contact_list[$tmp_phone] = $v;
                }
            }
            if (!empty($data['contact_list'])) {
                foreach ($data['contact_list'] as $value) {
                    if (isset($mobile_contact_list[$value['phone']])) {
                        $count++;
                        continue;
                    }
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录第一第二联系人有联系的数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkFSContactCallCount($data, $params)
    {
        $count = 0;
        $detail = "第一第二联系人有联系的数量";
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $common_contactors = $data['common_contactors'];
        $firstphone = isset($common_contactors[0]['phone']) ? $common_contactors[0]['phone'] : -1;
        $secondphone = isset($common_contactors[1]['phone']) ? $common_contactors[1]['phone'] : -1;
        if (!empty($data['contact_list'])) {
            foreach ($data['contact_list'] as $value) {
                if (isset($first) && isset($second))
                    break;
                if ($value['phone'] == $firstphone) {
                    $first = true;
                    continue;
                }
                if ($value['phone'] == $secondphone) {
                    $second = true;
                    continue;
                }
            }
        }
        if (isset($first))
            $count += 1;
        if (isset($second))
            $count += 1;
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录手机号码有短信联系的数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactMsgCount($data, $params)
    {
        $count = 0;
        $detail = "通讯录号码有短信联系数量";
        $loan_person = $data['loan_person'];
        // $mobile_contact = UserMobileContacts::getContactData($loan_person->id);
        $mobile_contact = $data['user_mobile_contacts'];
        $mobile_contact_list = [];
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $tmp_phone = str_replace('+86', '', $v['mobile']);
                $tmp_phone = str_replace('-', '', $tmp_phone);
                if (preg_match($isMobile, $tmp_phone)) {
                    $mobile_contact_list[] = $tmp_phone;
                }
            }
            $mobile_contact_list = array_unique($mobile_contact_list);
            $count = count(UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->andWhere(['in', 'phone', $mobile_contact_list])->distinct('phone'));
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录第一第二联系人有短信联系的数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkFSContactMsgCount($data, $params)
    {
        $count = 0;
        $detail = "第一第二联系人有短信联系的数量";
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $common_contactors = $data['common_contactors'];
        $fs_contact = [];
        if (isset($common_contactors[0]['phone'])) {
            $fs_contact[] = $common_contactors[0]['phone'];
        }
        if (isset($common_contactors[1]['phone'])) {
            $fs_contact[] = $common_contactors[1]['phone'];
        }
        if (!empty($fs_contact)) {
            $count = count(UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->andWhere(['in', 'phone', $fs_contact])->distinct('phone'));
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }


    private function getIDsByPhones($list, $except)
    {
        $userids = [];
        $mobile_list = [];
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        foreach ($list as $v) {
            $tmp_phone = str_replace('+86', '', $v);
            $tmp_phone = str_replace('-', '', $tmp_phone);
            if (preg_match($isMobile, $tmp_phone)) {
                $mobile_list[] = $tmp_phone;
            }
        }
        if (!empty($mobile_list)) {
            $mobile_list = array_unique($mobile_list);
            $users = LoanPerson::find()->where(['in', 'phone', $mobile_list])->andWhere(['not in', 'id', $except])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
            if (!empty($users)) {
                foreach ($users as $u) {
                    $userids[] = $u['id'];
                }
            }
        }
        return $userids;
    }

    /**
     * param    array            用户id列表
     * param    array            默认配置(hittype 0:黑名单,1:拒绝名单,2:逾期名单,3:正常还款名单,4:申请中名单,5:通过未还款名单;默认0;)
     */
    private function getBackendHitCount($userids, $params)
    {
        $count = 0;
        if (isset($params['hittype']) && $params['hittype'] != 0) {
            if ($params['hittype'] == 1) {
                $detail = "命中后台拒绝名单数量";
                $query = UserLoanOrder::find()->select(['user_id'])->distinct()->where(['in', 'status', [UserLoanOrder::STATUS_PENDING_CANCEL, UserLoanOrder::STATUS_REPEAT_CANCEL, UserLoanOrder::STATUS_CANCEL, 10000, 10001]]);
            } else if ($params['hittype'] == 2) {
                $detail = "命中后台逾期名单数量";
                $query = UserLoanOrderRepayment::find()->select(['user_id'])->distinct()->where(['is_overdue' => UserLoanOrderRepayment::OVERDUE_YES]);
            } else if ($params['hittype'] == 3) {
                $detail = "命中后台正常还款名单数量";
                $query = UserLoanOrderRepayment::find()->select(['user_id'])->distinct()->where(['status' => UserLoanOrderRepayment::STATUS_REPAY_COMPLETE, 'is_overdue' => UserLoanOrderRepayment::OVERDUE_NO]);
            } else if ($params['hittype'] == 4) {
                $detail = "命中后台申请中名单数量";
                $query = UserLoanOrder::find()->select(['user_id'])->distinct()->where(['in', 'status', [UserLoanOrder::STATUS_CHECK, UserLoanOrder::STATUS_REPEAT_TRAIL]]);
            } else if ($params['hittype'] == 5) {
                $detail = "命中后台通过未还款名单数量";
                $query = UserLoanOrderRepayment::find()->select(['user_id'])->distinct()->where(['in', 'status', [UserLoanOrderRepayment::STATUS_REPAY_CANCEL, UserLoanOrderRepayment::STATUS_DEBIT_FALSE, UserLoanOrderRepayment::STATUS_REPAY_REPEAT_CANCEL, UserLoanOrderRepayment::STATUS_CANCEL, UserLoanOrderRepayment::STATUS_NORAML, UserLoanOrderRepayment::STATUS_CHECK, UserLoanOrderRepayment::STATUS_PASS, UserLoanOrderRepayment::STATUS_REPAY_COMPLEING, UserLoanOrderRepayment::STATUS_WAIT]]);
            }
        } else {
            $detail = "命中后台黑名单数量";
            //todo...
            return ['detail' => $detail, 'value' => $count];
        }
        $count = $query->andWhere(['in', 'user_id', $userids])->count('*', Yii::$app->get('db_kdkj_rd'));
        return ['detail' => $detail, 'value' => $count];
    }


    /**
     * 判断通讯录命中后台名单数量
     *
     * param    array            data
     * param    array            默认配置(hittype 0:黑名单,1:拒绝名单,2:逾期名单,3:正常还款名单,4:申请中名单,5:通过未还款名单;默认0;)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactHitCount($data, $params) {
        $loan_person = $data['loan_person'];
        $mobile_contact = $data['user_mobile_contacts'];

        $userids = [];
        if (!empty($mobile_contact)) {
            $list = [];
            foreach ($mobile_contact as $v) {
                $list[] = $v['mobile'];
            }
            $userids = $this->getIDsByPhones($list, [$loan_person->id]);
        }
        $count = 0;
        $detail = "通讯录未命中注册用户名单";
        if (!empty($userids)) {
            $ret = $this->getBackendHitCount($userids, $params);
            $detail = "通讯录" . $ret['detail'];
            $count = $ret['value'];
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断通话记录近期通话数量
     *
     * param    array            data
     * param    array            默认配置(month 1:近一个月,3:近三个月,6:近六个月)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallCount($data, $params) {
        $data = $data['yys'];
        $count = 0;
        if (!isset($params['month'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => "没有指定时间", 'value' => $count];
        }
        $month = intval($params['month']);
        $detail = "近" . $month . "个月通话详单号码数量";
        if (!empty($data['contact_list'])) {
            foreach ($data['contact_list'] as $value) {
                if ($value['last_contact_date'] > date("Y-m", strtotime("-" . $month . " month"))) {
                    $count++;
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 实名使用天数
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkRealNameDay($data, $params)
    {
        $data = $data['yys'];
        $detail = '运营商：没有相关信息';
        $count = 0;
        if (!empty($data['real_name_time'])) {
            $count = round((time() - strtotime($data['real_name_time'])) / 86400);
            $detail = '运营商：实名绑定时间' . $count . '天';
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 法院黑名单限制规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCourtBlackList($data, $params) {
        $data = $data['jxl'];
        $count = self::NO;
        $detail = '聚信立:没有相关信息';
        if (!empty($data['behavior_check'])) {
            foreach ($data['behavior_check'] as $v) {
                if ($v['check_point'] == "contact_court" && strstr($v['result'], '无') == null) {
                    $count = self::YES;
                    $detail = '聚信立:命中法院黑名单';
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 金融机构黑名单限制规则
     *
     * param    array            聚信立报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkBankBlackList($data, $params) {
        $data = $data['jxl'];
        $count = 0;
        $detail = '聚信立:没有相关信息';
        if (!empty($data['application_check'])) {
            foreach ($data['application_check'] as $v) {
                if ($v['check_point'] == "申请人姓名+身份证是否出现在金融服务类机构黑名单"
                    || $v['check_point'] == "申请人姓名+手机号码是否出现在金融服务类机构黑名单"
                ) {
                    $count = 1;
                    $detail = '聚信立:命中金融服务类机构黑名单';
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断通话记录命中后台名单数量
     *
     * param    array            data
     * param    array            默认配置(hittype 0:黑名单,1:拒绝名单,2:逾期名单,3:正常还款名单,4:申请中名单,5:通过未还款名单;默认0;)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallHitCount($data, $params) {
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $userids = [];
        if (!empty($data['contact_list'])) {
            $list = [];
            foreach ($data['contact_list'] as $value) {
                $list[] = $value['phone'];
            }
            $userids = $this->getIDsByPhones($list, [$loan_person->id]);
        }
        $count = 0;
        $detail = "通话记录未命中注册用户名单";
        if (!empty($userids)) {
            $ret = $this->getBackendHitCount($userids, $params);
            $detail = "通话记录号码" . $ret['detail'];
            $count = $ret['value'];
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断通话记录号码标签类型数量
     *
     * param    array            data
     * param    array            默认配置(tiptype null:未定义,网络电话,小贷,高危关键词...   ,默认null)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallTipTypeCount($data, $params) {
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $mobile_contact_list = [];
        $count = 0;
        $detail = "无通话记录号码相关信息";
        if (!empty($data['contact_list'])) {
            foreach ($data['contact_list'] as $value) {
                $mobile_contact_list[] = $value['phone'];
            }
            $phones = array_unique($mobile_contact_list);
            $ret = $this->getCategoryCount($phones, $params);
            $detail = "通话记录号码" . $ret['detail'];
            $count = $ret['value'];
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断通话记录号码类型数量
     *
     * param    array            data
     * param    array            默认配置(type 1:手机,2:固话,3:短号,默认:1)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallTypeCount($data, $params) {
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $mobile_contact = $data['contact_list'];
        $phones = [];
        if (!empty($mobile_contact)) {
            foreach ($mobile_contact as $v) {
                $phone = str_replace('+86', '', $v['phone']);
                $phones[] = $phone;
            }
        }
        $ret = $this->getPhoneTypeCount($phones);
        $count = $ret['mobile_count'];
        $detail = "通话记录号码手机号数量";
        if (isset($params['type'])) {
            if ($params['type'] == 2) {
                $count = $ret['fixed_count'];
                $detail = "通话记录号码固话数量";
            } else if ($params['type'] == 3) {
                $count = $ret['short_count'];
                $detail = "通话记录号码短号数量";
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    private function getPhoneTypeCount($phones) {
        $mobile_count = 0;
        $fixed_count = 0;
        $short_count = 0;
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        $isFixed = "/^([0-9]{3,4}-)?[0-9]{7,8}$/";
        foreach ($phones as $phone) {
            $tmp_phone = str_replace('+86', '', $phone);
            if (preg_match($isMobile, $tmp_phone)) {
                $mobile_count++;
            } else if (preg_match($isFixed, $tmp_phone)) {
                $fixed_count++;
            } else if (strlen($tmp_phone) <= 6 && strlen($tmp_phone) >= 4 && intval($tmp_phone) != 0) {
                $short_count++;
            }
        }
        return ['mobile_count' => $mobile_count, 'fixed_count' => $fixed_count, 'short_count' => $short_count];
    }

    /**
     * 判断短信数量
     *
     * param    array            data
     * param    array            默认配置(distinct true:去重,默认:不去重)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgCount($data, $params) {
        $loan_person = $data['loan_person'];
        $query = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)]);
        $count = 0;
        $detail = "短信";
        if (isset($params['distinct']) && !empty($params['distinct'])) {
            $count = count($query->distinct('phone'));
            $detail .= "去重数量";
        } else {
            $count = $query->count();
            $detail .= "数量";
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断短信记录号码标签类型数量
     *
     * param    array            data
     * param    array            默认配置(tiptype null:未定义,网络电话,小贷,高危关键词...   ,默认null)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgTipTypeCount($data, $params) {
        $loan_person = $data['loan_person'];
        $msgphones = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->distinct(['phone']);
        $mobile_contact_list = [];
        $count = 0;
        $detail = "无短信记录号码相关信息";
        if (!empty($msgphones)) {
            foreach ($msgphones as $value) {
                $mobile_contact_list[] = $value['phone'];
            }
            $phones = array_unique($mobile_contact_list);
            $ret = $this->getCategoryCount($phones, $params);
            $detail = "短信记录号码" . $ret['detail'];
            $count = $ret['value'];
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断短信记录命中后台名单数量
     *
     * param    array            data
     * param    array            默认配置(hittype 0:黑名单,1:拒绝名单,2:逾期名单,3:正常还款名单,4:申请中名单,5:通过未还款名单;默认0;)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgHitCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $msgphones = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->distinct('phone');
        $userids = [];
        if (!empty($msgphones)) {
            $list = [];
            foreach ($msgphones as $value) {
                $list[] = $value['phone'];
            }
            $userids = $this->getIDsByPhones($list, [$loan_person->id]);
        }
        $count = 0;
        $detail = "短信记录未命中注册用户名单";
        if (!empty($userids)) {
            $ret = $this->getBackendHitCount($userids, $params);
            $detail = "短信记录号码" . $ret['detail'];
            $count = $ret['value'];
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     * 判断短信记录号码有电话联系数量
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgCallCount($data, $params)
    {
        $count = 0;
        $detail = "短信记录号码有电话联系数量";
        $loan_person = $data['loan_person'];
        $data = $data['yys'];
        $msgphones = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->distinct('phone');
        $mobile_contact_list = [];
        if (!empty($msgphones)) {
            foreach ($msgphones as $v) {
                $mobile_contact_list[$v['phone']] = $v['phone'];
            }
            if (!empty($data['contact_list'])) {
                foreach ($data['contact_list'] as $value) {
                    if (isset($mobile_contact_list[$value['phone']])) {
                        $count++;
                        continue;
                    }
                }
            }
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录手机号同归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactSameLocationCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $person_phone = $loan_person->phone;
        // $contact_list = UserMobileContacts::getContactData($loan_person->id);
        $contact_list = $data['user_mobile_contacts'];

        $mobile_list = [];
        $count = 0;
        $detail = "通讯录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['mobile'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "通讯录手机号同城数量";
            } else {
                $type = 'province';
                $detail = "通讯录手机号同省数量";
            }
            $count = $this->getSamePhoneLocationCount($person_phone, $mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通讯录手机号归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkContactLocationCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        // $contact_list = UserMobileContacts::getContactData($loan_person->id);
        $contact_list = $data['user_mobile_contacts'];
        $mobile_list = [];
        $count = 0;
        $detail = "通讯录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['mobile'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "通讯录手机号所在城市数量";
            } else {
                $type = 'province';
                $detail = "通讯录手机号所在省份数量";
            }
            $count = $this->getPhoneLocationCount($mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }


    private function getSamePhoneLocationCount($phone, $list, $type = 'province')
    {
        $count = 0;
        $mobile_list = [];
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        foreach ($list as $key => $v) {
            $tmp_phone = str_replace('+86', '', $v);
            $tmp_phone = str_replace('-', '', $tmp_phone);
            if (preg_match($isMobile, $tmp_phone)) {
                $code = substr($tmp_phone, 0, 7);
                $mobile_list[$tmp_phone] = $code;
            }
        }
        if (empty($mobile_list)) {
            return $count;
        }
        $db = Yii::$app->get('db_kdkj_rd');
        $table = $table = '{{%phone_city}}';
        $phone = substr($phone, 0, 7);
        $tomatch = (new \yii\db\Query())->select($type)->from($table)->where(['match_code' => $phone]);
        $match_codes = (new \yii\db\Query())->select(['match_code'])->from($table)->where([$type => $tomatch])->createCommand($db)->queryAll();
        $codes = [];
        if (!empty($match_codes)) {
            foreach ($match_codes as $key => $value) {
                $codes[$value['match_code']] = '';
            }
            unset($match_codes);
            if (!empty($codes)) {
                foreach ($mobile_list as $key => $value) {
                    if (isset($codes[$value])) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    private function getPhoneLocationCount($list, $type = 'province')
    {
        $count = 0;
        $mobile_list = [];
        $isMobile = "/^1[3-5,8]{1}[0-9]{9}$/";
        foreach ($list as $key => $v) {
            $tmp_phone = str_replace('+86', '', $v);
            $tmp_phone = str_replace('-', '', $tmp_phone);
            if (preg_match($isMobile, $tmp_phone)) {
                $code = substr($tmp_phone, 0, 7);
                $mobile_list[$tmp_phone] = $code;
            }
        }
        if (empty($mobile_list)) {
            return $count;
        }
        $db = Yii::$app->get('db_kdkj_rd');
        $table = $table = '{{%phone_city}}';
        $locations = (new \yii\db\Query())->select($type)->distinct()->from($table)->where(['in', 'match_code', $mobile_list])->createCommand($db)->queryAll();
        return count($locations);
    }

    //!!!!!!!!!!!!!!需要手机归属地数据库!!!!!!!!!!!!!!!!!!!!!!
    /**
     *
     * 判断通话记录手机号同归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallSameLocationCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $person_phone = $loan_person['phone'];
        $data = $data['yys'];
        $contact_list = $data['contact_list'];
        $mobile_list = [];
        $count = 0;
        $detail = "通话记录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['phone'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "通话记录手机号同城数量";
            } else {
                $type = 'province';
                $detail = "通话记录手机号同省数量";
            }
            $count = $this->getSamePhoneLocationCount($person_phone, $mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断通话记录手机号归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCallLocationCount($data, $params)
    {
        $data = $data['yys'];
        $contact_list = $data['contact_list'];
        $mobile_list = [];
        $count = 0;
        $detail = "通话记录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['phone'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "通话记录手机号所在城市数量";
            } else {
                $type = 'province';
                $detail = "通话记录手机号所在省份数量";
            }
            $count = $this->getPhoneLocationCount($mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断短信记录手机号同归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgSameLocationCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $person_phone = $loan_person['phone'];
        $contact_list = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->distinct('phone');
        $mobile_list = [];
        $count = 0;
        $detail = "短信记录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['phone'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "短信记录手机号同城数量";
            } else {
                $type = 'province';
                $detail = "短信记录手机号同省数量";
            }
            $count = $this->getSamePhoneLocationCount($person_phone, $mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }

    /**
     *
     * 判断短信记录手机号归属地数量
     *
     * param    array            data
     * param    array            默认配置(location =>province:省份,city:城市,默认:省份)
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMsgLocationCount($data, $params)
    {
        $loan_person = $data['loan_person'];
        $contact_list = UserPhoneMessageMongo::find()->select(['phone'])->where(['user_id' => strval($loan_person->id)])->distinct('phone');
        $mobile_list = [];
        $count = 0;
        $detail = "短信记录：无相关信息";
        if (!empty($contact_list)) {
            foreach ($contact_list as $v) {
                $mobile_list[] = $v['phone'];
            }
            if (isset($params['location']) && $params['location'] == 'city') {
                $type = 'city';
                $detail = "短信记录手机号所在城市数量";
            } else {
                $type = 'province';
                $detail = "短信记录手机号所在省份数量";
            }
            $count = $this->getPhoneLocationCount($mobile_list, $type);
        }
        return ['risk' => self::MEDIUM_RISK, 'detail' => $detail, 'value' => $count];
    }
    //!!!!!!!!!!!!!!需要手机归属地数据库!!!!!!!!!!!!!!!!!!!!!!

    /* 获取51公积金稳定性分类

      param    array            data
      param    array            默认配置
      return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
    */
    public function checkWySteadyCategory($data, $params)
    {

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        $start_time = '';
        $end_time = time();
        if (isset($data['gjj_brief']) && $data['gjj_detail']) {
            $k = count($data['gjj_detail']);

            if (isset($data['gjj_detail'][$k - 1]['record_date'])) {
                $start_time = $data['gjj_detail'][$k - 1]['record_date'];
            }

            $account_time = 0;

            if ($start_time && $end_time) {
                $account_time = round((strtotime($start_time) - $end_time) / 3600 / 24 / 365, 2);

            }
            $month = [];
            foreach ($data['gjj_detail'] as $item) {
                if ($item['record_month']) {
                    $m = substr($item['record_month'], -2);
                } else {
                    $arr = explode('-', $item['record_date']);
                    $m = $arr[1];
                }
                $month[] = $m;

            }
            $constant_month = $this->getConstantMonth($month);

            if ($account_time < 2 && $constant_month < 3) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：稳定性为低', 'value' => self::YES];
            } elseif (($account_time < 2 && $constant_month >= 3 && $constant_month <= 6) || ($account_time >= 2 && $constant_month < 3)) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：稳定性为中', 'value' => self::OTHER];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：稳定性为高', 'value' => self::THREE];
            }
        }
        return $result;
    }

    /* 获取51公积金月缴额

       param    array            data
       param    array            默认配置
       return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMonthBaseAmount($data, $params)
    {

        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        $data = $data['wy'];
        if (isset($data['gjj_brief']['base'])) {
            $base_amount = $data['gjj_brief']['base'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：月缴额为' . $base_amount, 'value' => $base_amount];
        }
        return $result;

    }


    public function getConstantMonth($month)
    {
        if (empty($month)) {
            $constant_month = 0;
        } else {
            $k = count($month);
            $constant_month = 1;
            for ($i = 0; $i < $k - 1; $i++) {
                if (($month[$i] - $month[$i + 1] == 1) || ($month[$i + 1] - $month[$i] == -11)) {
                    $constant_month++;
                } else {
                    break;
                }
            }
        }
        return $constant_month;
    }

    /**


     *
     * 获取城市分类
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */


    public function checkCityCategory($data, $params)
    {
        $data = $data['wy'];
        $first_city = ['上海', '北京', '广州', '深圳', '天津'];
        $secord_city = ['杭州', '南京', '济南', '重庆', '青岛', '大连', '宁波', '厦门', '成都', '武汉', '哈尔滨', '沈阳', '西安', '长春', '长沙', '福州', '郑州', '石家庄', '苏州', '佛山', '东莞', '无锡', '烟台', '太原', '合肥', '南昌', '南宁', '昆明', '温州', '淄博', '唐山'];
        $third_city = ['徐州', '常州', '南通', '连云港', '淮安', '盐城', '扬州', '镇江', '泰州', '宿迁', '宁波', '嘉兴', '湖州', '绍兴', '金华', '衢州', '舟山', '台州', '丽水', '芜湖', '蚌埠', '淮南', '马鞍山', '莆田', '三明', '泉州', '漳州', '南平', '龙岩', '宁德', '烟台', '济宁'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        if (isset($data['gjj_brief']['location'])) {
            if (in_array($first_city, $data['gjj_brief']['location'])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：城市分类为一线城市', 'value' => self::YES];
            } elseif (in_array($secord_city, $data['gjj_brief']['location'])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：城市分类为二线城市', 'value' => self::OTHER];
            } elseif (in_array($third_city, $data['gjj_brief']['location'])) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：城市分类为华东地区城市', 'value' => self::THREE];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：城市分类为其他城市', 'value' => self::FOUR];
            }
        }
        return $result;
    }

    /**


     *
     * 获取学历分类
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkUserCreditLevel($data, $params)
    {

        $data = $data['wy'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        if (isset($data['gjj_brief'])) {
            $category = 0;
            $qualifications = $data['gjj_brief']['xueli'];
            $college = College::find()->where(['name' => $qualifications])->one();
            if ($college) {
                $category = $college['category'];
            }
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => $category];
        }

        return $result;
    }

    /**


     *
     * 是否为白名单
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsBlankList($data, $params)
    {
        $loan_person = $data['loan_person'];
        $repayment = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person->id])->all();
        $flag = false;
        foreach ($repayment as $item) {
            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLEING) {
                $flag = true;
                break;
            }
        }
        if ($flag) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：征信结果为白名单', 'value' => self::YES];
        } else {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        }
        return $result;
    }

    /**


     *
     * 是否为灰名单
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsGreyList($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        // $apply_decord = UserLoanOrder::find()->where(['user_id' => $loan_person->id])->all();
        $apply_decord = $data['user_loan_orders'];
        // $repayment = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person->id])->all();
        $repayment = $data['user_loan_order_repayments'];
        $flag = false;
        foreach ($repayment as $item) {
            if ($item['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLEING) {
                $flag = true;
                break;
            }
        }
        if (!$flag && $apply_decord) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：征信结果为灰名单', 'value' => self::YES];
        }

        return $result;
    }

    /**


     *
     * 是否为白户
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsBlankUser($data, $params)
    {

        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：没有相关信息', 'value' => self::NULL];
        // $apply_decord = UserLoanOrder::find()->where(['user_id' => $loan_person->id])->all();
        $apply_decord = $data['user_loan_orders'];
        if (is_null($apply_decord)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '51公积金：征信结果为白户', 'value' => self::YES];
        }
        return $result;
    }

    /**


     *
     * 获取订单号
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkGetOrderId($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $order_id = $order['id'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $order_id];
        }
        return $result;
    }

    /**


     *
     * 获取订单金额
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkGetOrderAmount($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $order_amount = $order['money_amount'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $order_amount];
        }
        return $result;
    }

    /**


     *
     * 获取还款金额
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkGetOrderRepayAmount($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayment) {
                $res = $repayment['total_money'];
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
            }

        }
        return $result;
    }

    /**


     *
     * 获取逾期状态
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsOverdue($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayment) {
                if ($repayment['is_overdue']) {
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::YES];
                } else {
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NO];
                }

            }

        }
        return $result;
    }

    /**


     *
     * 获取当前逾期天数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkGetOverdueDay($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayment) {
                $res = $repayment['overdue_day'];

                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
            }

        }
        return $result;
    }

    /**


     *
     * 获取历史最大逾期天数
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkGetOverdueDays($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $arr = [];
            $repayments = UserLoanOrderRepayment::find()->where(['user_id' => $order['user_id']])->all(Yii::$app->get('db_kdkj_rd'));
            if ($repayments) {
                foreach ($repayments as $record) {
                    $arr[] = $record['overdue_day'];
                }
            }
            rsort($arr);
            $res = isset($arr[0]) ? $arr[0] : "";
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
        }

        return $result;
    }

    /**


     *
     * 获取贷款开始时间
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanStartTime($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {
            $repayments = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayments) {
                $res = date('Y-m-d H:i:s', $repayments['created_at']);
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
            }
        }

        return $result;
    }

    /**


     *
     * 获取贷款结束时间
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanEndTime($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {

            $repayments = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayments) {
                $res = date('Y-m-d H:i:s', $repayments['true_repayment_time']);
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
            }
        }

        return $result;
    }

    /**


     *
     * 获取贷款期限
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanTime($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {

            $res = $order['loan_term'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];

        }

        return $result;
    }

    /**


     *
     * 获取贷款利率
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanFee($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {

            $res = $order['apr'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];

        }

        return $result;
    }

    /**


     *
     * 获取身份证号
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanIdNumber($data, $params)
    {

        $person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($person) {

            $res = $person['id_number'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];

        }

        return $result;
    }

    /**


     *
     * 获取手机号
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanMobile($data, $params)
    {

        $person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($person) {

            $res = $person['phone'];
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];

        }

        return $result;
    }

    /**


     *
     * 获取还款方式
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险,    1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkLoanRepayWay($data, $params)
    {

        $order = $data['order'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        if ($order) {

            $repayments = UserLoanOrderRepayment::find()->where(['order_id' => $order['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if ($repayments) {
                $res = 1;
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => $res];
            }
        }

        return $result;
    }

    /**


     *
     * 蜜罐特征中的间接联系人在黑名单中的数量
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkMgContactHitBlackList($data, $params)
    {
        $data = $data['mg'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '蜜罐:没有相关信息', 'value' => self::NULL];

        if (!empty($data['user_gray']) && $data['user_gray']['contacts_class2_blacklist_cnt']) {
            $res = $data['user_gray']['contacts_class2_blacklist_cnt'];
            $result = ['risk' => self::HIGH_RISK, 'detail' => '蜜罐:身份证和姓名在黑名单中', 'value' => $res];

        }
        return $result;
    }

    /**


     *
     * 7天内不正常还款（3天内还款的）
     *
     * param    array            data
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSevenDayAbnormalOrder($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关信息', 'value' => self::NULL];
        $cnt = 0;
        if (isset($loan_person['id']) && $loan_person['id'] > 0) {
            $orders = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person['id']])->andwhere(['status' => UserLoanOrderRepayment::STATUS_REPAY_COMPLETE])->andwhere(['>', 'true_repayment_time', strtotime(date('Y-m-d', strtotime('-7 days')))])->all(Yii::$app->get('db_kdkj_rd'));
            if (count($orders) > 0) {
                $result['detail'] = '';
                foreach ($orders as $value) {
                    if ($value['plan_repayment_time'] - $value['true_repayment_time'] > 86400 * 4) {
                        $result['detail'] .= '订单' . $value['id'] . '不正常----放款时间：' . date('Y-m-d H:i:s', $value['loan_time']) . ',实际还款时间：' . date('Y-m-d H:i:s', $value['true_repayment_time']) . "<br>";
                        $cnt++;
                    }
                }
                $result['detail'] = ($result['detail'] == '') ? '没有相关信息' : $result['detail'];
                $result['value'] = $cnt;
            } else
                $result['detail'] = '该借款人7天内没有还款记录';

        } else
            $result['detail'] = '没有该借款人';

        return $result;
    }

    /**
     *
     * @author Shayne Song
     * @function Blacklist
     *
     */
    public function checkUserBlacklist($data, $params)
    {
        $loanPerson = $data['loan_person'];
        $blacklist = LoanBlacklistDetail::find()->where(['user_id' => $loanPerson->id])->one();
        if (empty($blacklist)) {
            return [
                'risk' => self::LOW_RISK,
                'value' => self::NO,
                'detail' => "未出现在用户黑名单中",

            ];
        } else {
            return [
                'risk' => self::HIGH_RISK,
                'value' => self::YES,
                'detail' => "出现在用户黑名单中",
            ];
        }
    }

    /**


     *
     * 是否是连续3个月全额还款用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsFullRepaymentThree($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (!empty($data['lstNetBankCreditBillInfo']) && isset($data['lstNetBankCreditBillInfo'])) {
            $months = [];
            foreach ($data['lstNetBankCreditBillInfo'] as $value) {
                $month = substr($value['month'], -2);
                $months[] = $month;
            }
            $const = $this->getConstantMonth($months);
            if ($const >= 3) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:连续3个月全额还款', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     *  是否是连续6个月全额还款用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsFullRepaymentSix($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (!empty($data['lstNetBankCreditBillInfo']) && isset($data['lstNetBankCreditBillInfo'])) {
            $months = [];
            foreach ($data['lstNetBankCreditBillInfo'] as $value) {
                $month = substr($value['month'], -2);
                $months[] = $month;
            }
            $const = $this->getConstantMonth($months);
            if ($const >= 6) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:连续3个月全额还款', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     *  是否是提额用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsA3($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_a3'])) {
            if ($data['is_a3'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是提额用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是提额用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否是有车用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsCz($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_cz'])) {
            if ($data['is_cz'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是有车用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是有车用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否是海外购物用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsHw($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_hw'])) {
            if ($data['is_hw'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是海外购物用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是海外购物用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否是商旅用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsLy($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_ly'])) {
            if ($data['is_ly'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是商旅用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是商旅用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  每月收入金额（工资收入）
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkSalary($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDepositBillInfo']) && !empty($data['lstNetBankDepositBillInfo'])) {
            $sum = 0;
            $count = count($data['lstNetBankDepositBillInfo']);
            foreach ($data['lstNetBankDepositBillInfo'] as $value) {
                $sum += $value['month_income_amt'];
            }
            $res = round($sum / $count, 2);
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:每月收入金额（工资收入）为' . $res, 'value' => $res];
        }
        return $result;
    }


    /**


     *
     *  每月固定支出金额
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkNomalExpAmount($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDepositBillInfo']) && !empty($data['lstNetBankDepositBillInfo'])) {
            $sum = 0;
            $count = count($data['lstNetBankDepositBillInfo']);
            foreach ($data['lstNetBankDepositBillInfo'] as $value) {
                $sum += $value['normal_exp_amt'];
            }
            $res = round($sum / $count, 2);

            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:每月固定支出为' . $res, 'value' => $res];

        }
        return $result;
    }


    /**


     *
     *  每月固定收入金额
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkNomalInconmeAmount($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDepositBillInfo']) && !empty($data['lstNetBankDepositBillInfo'])) {
            $sum = 0;
            $count = count($data['lstNetBankDepositBillInfo']);
            foreach ($data['lstNetBankDepositBillInfo'] as $value) {
                $sum += $value['normal_income_amt'];
            }
            $res = round($sum / $count, 2);

            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:每月固定收入金额为' . $res, 'value' => $res];

        }
        return $result;
    }

    /**


     *
     *  每月利息交易
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkAccrual($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $sum = 0;
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {
                if (isset($value['transTypeList']) && !empty($value['transTypeList'])) {
                    foreach ($value['transTypeList'] as $item) {
                        if (($item['trans_type'] == '利息交易')) {
                            $sum += $item['trans_amt'];
                        }
                    }
                }
            }
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:每月利息交易为' . $sum, 'value' => $sum];
        }
        return $result;
    }

    /**


     *
     *  卡片级别
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCardLevel($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankCreditCardInfo'])) {
            if (isset($data['lstNetBankCreditCardInfo'][0]['card_level'])) {
                $level = $data['lstNetBankCreditCardInfo'][0]['card_level'];
                $credit = $this->getCardLevelCredit($level);
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:卡片级别为' . $credit, 'value' => $credit];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:卡片级别为空', 'value' => self::NULL];
            }
        }
        return $result;
    }

    private function getCardLevelCredit($level)
    {
        if (substr_count($level, '普卡')) {
            return self::YES;
        } elseif (substr_count($level, '金卡')) {
            return self::OTHER;
        } elseif (substr_count($level, '白金')) {
            return self::THREE;
        } else {
            return self::NULL;
        }
    }

    /**


     *
     *  卡片额度
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkCardLimit($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankCreditCardInfo'])) {
            if (isset($data['lstNetBankCreditCardInfo'][0]['credit_limit'])) {
                $credit = $data['lstNetBankCreditCardInfo'][0]['credit_limit'];

                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:卡片额度为' . $credit, 'value' => $credit];
            }
        }
        return $result;
    }


    /**


     *
     *  近12个月平均账单金额
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkNewCharges($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];
        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $sum = 0;
            $month = count($data['lstNetBankDetailSummaryInfo']);
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {
                if (isset($value['new_charges'])) {
                    $sum = $sum + $value['new_charges'];
                }
            }
            $res = round($sum / $month, 2);

            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:近12个月平均账单金额为' . $res, 'value' => $res];
        }
        return $result;
    }

    /**


     *
     *  当期积分
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkPointsAvailable($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankCreditCardInfo'])) {
            if (isset($data['lstNetBankCreditCardInfo'][0]['pointsavailable'])) {
                $credit = $data['lstNetBankCreditCardInfo'][0]['pointsavailable'];

                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:当前积分为' . $credit, 'value' => $credit];
            }
        }
        return $result;
    }

    /**


     *
     * 3个月月均账单金额
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkThreeMonthCharges($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];
        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $sum = 0;
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {
                if (time() - strtotime($value['month']) < 86400 * 90) {
                    if (isset($value['new_charges'])) {
                        $sum = $sum + $value['new_charges'];
                    }
                }

            }
            $res = round($sum / 3, 2);

            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:近3个月平均账单金额为' . $res, 'value' => $res];
        }
        return $result;
    }

    /**


     *
     * 最近12个月是否都有工资收入
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsOneYearIncome($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDepositBillInfo']) && !empty($data['lstNetBankDepositBillInfo'])) {
            $flag = false;
            $count = count($data['lstNetBankDepositBillInfo']);
            if ($count >= 12) {
                foreach ($data['lstNetBankDepositBillInfo'] as $value) {
                    if ($value['month_income_amt'] == 0) {
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:最近12个月不是都有工资收入', 'value' => self::NO];
                } else {
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:最近12个月都有工资收入', 'value' => self::YES];
                }
            }


        }
        return $result;
    }

    /**


     *
     * 最近12个月收支情况
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIncomeOutCondition($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDepositBillInfo']) && !empty($data['lstNetBankDepositBillInfo'])) {
            $exp = 0;
            $income = 0;
            $count = count($data['lstNetBankDepositBillInfo']);
            if ($count >= 3) {
                foreach ($data['lstNetBankDepositBillInfo'] as $value) {
                    $exp += $value['month_exp_amt'];
                    $income += $value['month_income_amt'];
                }
                $ava_exp = round($exp / $count, 2);
                $ava_income = round($income / $count, 2);
                $res = $ava_income - $ava_exp;
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:12个月平均收支差为' . $res, 'value' => $res];
            }

        }
        return $result;
    }

    /**


     *
     * 是否有教育消费
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsEduExp($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $flag = false;
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {

                if (isset($value['transTypeList']) && !empty($value['transTypeList'])) {
                    foreach ($value['transTypeList'] as $item) {
                        if (time() - strtotime($item['month']) <= 86400 * 180) {
                            if (($item['trans_type'] == '教育')) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($flag) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:有教育消费', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有教育消费', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     * 是否有理财消费
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsFinancingp($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $flag = false;
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {

                if (isset($value['transTypeList']) && !empty($value['transTypeList'])) {
                    foreach ($value['transTypeList'] as $item) {
                        if (time() - strtotime($item['month']) <= 86400 * 180) {
                            if (($item['trans_type'] == '理财')) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($flag) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:有理财消费', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有理财消费', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     * 是否有置业消费
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsHouse($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['lstNetBankDetailSummaryInfo']) && !empty($data['lstNetBankDetailSummaryInfo'])) {
            $flag = false;
            foreach ($data['lstNetBankDetailSummaryInfo'] as $value) {

                if (isset($value['transTypeList']) && !empty($value['transTypeList'])) {
                    foreach ($value['transTypeList'] as $item) {
                        if (($item['trans_type'] == '置业')) {
                            $flag = true;
                            break;
                        }
                    }
                }
            }
            if ($flag) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:有置业消费', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有置业消费', 'value' => self::NO];
            }

        }
        return $result;
    }

    /**


     *
     *  是否是网购用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsWg($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_wg'])) {
            if ($data['is_wg'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是网购用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是网购用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否账常态高额度用卡
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsA1($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_a1'])) {
            if ($data['is_a1'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是常态高额度用卡', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是常态高额度用卡', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否账单波动过大
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsB1($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_b1'])) {
            if ($data['is_b1'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户账单波动过大', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是账单波动过大', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否频繁取现
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsC1($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_c1'])) {
            if ($data['is_c1'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是账单波动过大是频繁取现', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是频繁取现', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否还款能力不足
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsLackRepayment($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_lack_repayment'])) {
            if ($data['is_lack_repayment'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户还款能力不足', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是还款能力不足', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否是严重逾期用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsSeriouslyOverdue($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_seriously_overdue'])) {
            if ($data['is_seriously_overdue'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是严重逾期用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是严重逾期用户', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否是轻微逾期用户
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsSlightlyOverdue($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_slightly_overdue'])) {
            if ($data['is_slightly_overdue'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是轻微逾期用户', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是轻微逾期用户', 'value' => self::NO];
            }
        }
        return $result;
    }


    /**


     *
     *  是否网络套现
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsT1($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_t1'])) {
            if ($data['is_t1'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是网络套现', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是网络套现', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  是否POS套现
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkIsT2($data, $params)
    {
        $data = $data['online_bank'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:没有相关信息', 'value' => self::NULL];

        if (isset($data['is_t2'])) {
            if ($data['is_t2'] == 1) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户是POS套现', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '网银:用户不是POS套现', 'value' => self::NO];
            }
        }
        return $result;
    }

    /**


     *
     *  聚信立和通讯录匹配程度占比
     *
     * param    array            蜜罐报告
     * param    array            默认配置
     * return   array            ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     */
    public function checkJxlMatchContactRate($data, $params)
    {
        $person = $data['loan_person'];

        $data = $data['yys'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：没有相关信息', 'value' => self::NULL];

        if (isset($data['contact_list']) && isset($data['common_contactors'])) {
            $contacts = UserMobileContacts::getContactData($person->id);
            $mobile_arr = [];
            if ($contacts) {
                foreach ($contacts as $contact) {
                    $mobile_arr[] = $contact['mobile'];
                }
            }

            $count = count($mobile_arr);
            $repeat_count = 0;
            foreach ($data['common_contactors'] as $contactor) {
                if (in_array($contactor['phone'], $mobile_arr)) {
                    $repeat_count++;
                }
            }
            $res = empty($count) ? 0 : round($repeat_count / $count, 2);

            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '运营商：聚信立和通讯录匹配程度百分比' . $res, 'value' => $res];
        }
        return $result;
    }

    /**
     * @function Number of valid distinct contacts.
     * @time 2017-01-05
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     */
    public function checkValidMobileContactCount($data, $params) {
        $loan_person = $data['loan_person'];
        $mobile_contact = $data['user_mobile_contacts'];
        if (empty($mobile_contact)) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '通讯录有效号码数量', 'value' => self::NULL];
        }
        $list = $this->array_unset($mobile_contact, 'name');
        $preg = "/^(13[0-9]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/";
        $mobile_contact_list = $this->validContactCount($list, $preg);
        $count = \count( $mobile_contact_list );
        return ['risk' => self::MEDIUM_RISK, 'detail' => '通讯录有效号码数量', 'value' => $count];
    }

    protected function array_unset($arr, $key)
    {   //$arr->传入数组   $key->判断的key值
        //建立一个目标数组
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if (isset($res[$value[$key]])) {
                //有：销毁
                unset($value[$key]);
            } else {
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }


    protected function validContactCount($contacts, $preg = '') {
        if (empty($contacts)) {
            return [];
        }

        $mobile_contact_list = [];
        foreach ($contacts as &$v) {
            $phone = \str_replace(['+86', '-'], '', $v['mobile']);
            if (empty($preg)) {
                $mobile_contact_list[] = $phone;
            }
            else if (\preg_match($preg, $phone)) {
                $mobile_contact_list[] = $phone;
            }
        }

        return \array_unique($mobile_contact_list);
    }

    /**
     *
     * @author liu bingbing
     * @function 连续关机3天以上次数
     * @time 2017-01-22
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkCountPhoneClose($data, $params)
    {
        $data = $data['jxl'];
        $count = 0;
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '聚信立:没有相关信息', 'value' => self::NULL];
        if (!empty($data['behavior_check'])) {
            foreach ($data['behavior_check'] as $v) {
                if ($v['check_point'] == '关机情况') {
                    $result = ['risk' => self::LOW_RISK, 'detail' => $v['evidence'], 'value' => 0];
                    if (preg_match('/连续三天以上关机(\d+)次/', $v['evidence'], $phone_shutdown)) {
                        if (isset($phone_shutdown[1])) {
                            $count = $phone_shutdown[1];
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上关机次数' . $count, 'value' => $count];
                        }
                    }
                    break;
                } else if ($v['check_point'] == "phone_silent") {
                    $result = ['risk' => self::LOW_RISK, 'detail' => $v['evidence'], 'value' => 0];
                    if (preg_match('/连续三天以上无通话记录(\d+)次/', $v['evidence'], $phone_shutdown)) {
                        if (isset($phone_shutdown[1])) {
                            $count = $phone_shutdown[1];
                            $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上关机次数' . $count, 'value' => $count];
                        }
                    } else if (preg_match('/连续三天以上关机(\d+)次/', $v['evidence'], $phone_shutdown)) {
                        if (isset($phone_shutdown[1])) {
                            if (isset($phone_shutdown[1])) {
                                $count = $phone_shutdown[1];
                                $result = ['risk' => self::HIGH_RISK, 'detail' => '运营商：连续三天以上关机次数' . $count, 'value' => $count];
                            }
                        }
                    }
                    break;
                }
            }
        }
        return $result;
    }

    /**
     *
     * @author Shayne Song
     * @function Number of overdue debt in Zmop credit report watch info.
     * @time 2017-02-04
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkNumberOfZmOverdue($data, $params)
    {
        if (is_null($zm = $data['zm']) || empty($zm['watch_info'])) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '芝麻历史逾期次数:没有相关信息', 'value' => self::NULL];
        } else {
            $i = 0;
            $info = json_decode($zm['watch_info'], true);
            foreach ($info as $v) {
                if (in_array($v['type'], ['R001', 'R016', 'R017', 'R018', 'R022', 'R023'])) {
                    $i++;
                }
            }
            $result = ['risk' => self::LOW_RISK, 'detail' => '芝麻历史逾期次数', 'value' => $i];
        }
        return $result;
    }

    /**
     *
     * @author Shayne Song
     * @function Number of debt phone number in user's contact.
     * @time 2017-02-04
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkDebtNumberInContact($data, $params)
    {
        $loan_person = $data['loan_person'];
        $mobile_contact = $data['user_mobile_contacts'];
        if (empty($mobile_contact)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '通讯录贷款号码数量', 'value' => self::NULL];
        } else {
            $count = 0;
            $query = MobileContactsReportMongo::find()->Where(['category' => '贷款号码'])->asArray()->all();
            $loan_phone = [];
            if (isset($query)) {
                foreach ($query as $v) {
                    $tmp = str_replace('+86', '', $v['mobile']);
                    $tmp = str_replace('-', '', $tmp);
                    $loan_phone[$tmp] = $tmp;
                }
            }

            //Filter duplicate numbers.
            $contacts = [];
            foreach ($mobile_contact as $v) {
                $v = str_replace('+86', '', $v['mobile']);
                $v = str_replace('-', '', $v);
                $contacts[$v] = $v;
            }

            foreach ($contacts as $v) {
                if (in_array($v, $loan_phone)) {
                    $count++;
                }
            }
            $result = ['risk' => self::LOW_RISK, 'detail' => '通讯录贷款号码数量', 'value' => $count];
        }
        return $result;
    }

    /**
     *
     * @author Shayne Song
     * @function Number of overdue days of last order in backend.
     * @time 2017-02-16
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkGetOverdueDaysInLastOrder($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '上笔借款逾期天数', 'value' => self::NULL];
        if ($loan_person) {
            $repayment = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person['id']])->orderBy('order_id desc')->one(Yii::$app->get('db_kdkj_rd'));
            if (!empty($repayment)) {
                $result['value'] = $repayment->overdue_day;
            }
        }
        return $result;
    }

    /**
     *
     * @author Shayne Song
     * @function Number of passed orders in backend.
     * @time 2017-02-16
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkGetPassedOrderNumber($data, $params)
    {
        $loan_person = $data['loan_person'];
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '借款总次数', 'value' => self::NULL];
        if ($loan_person) {
            $repayment = UserLoanOrderRepayment::find()->where(['user_id' => $loan_person['id']])->all(Yii::$app->get('db_kdkj_rd'));
            $result['value'] = count($repayment);
        }
        return $result;
    }

    /**
     *
     * @author Justin zhou
     * @function 获取子订单类型
     * @time 2017-02-16
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkSubOrderType($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '获取子订单类型', 'value' => self::NULL];
        if (isset($data['order']) && !empty($data['order'])) {
            $order = $data['order'];
            $userLoanOrder = UserLoanOrder::find()->where(['id' => $order['id']])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
            if (!empty($userLoanOrder))
                $result['value'] = $userLoanOrder['sub_order_type'];
        }
        return $result;
    }

    /**
     *
     * @author Justin zhou
     * @function 获取融360订单类型
     * @time 2017-02-16
     *
     * param    array            data
     * param    array            default
     * return   array            ['risk'=>"0:low_risk, 1:medium_risk, 2:high_risk", 'detail' => "description", 'value' => 'target number',]
     *
     */
    public function checkRong360Platform($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '获取融360订单类型', 'value' => self::NULL];
        if (isset($data['order']) && !empty($data['order'])) {
            $order = $data['order'];
            $rong360LoanOrder = Rong360LoanOrder::find()->where(['order_id' => $order['id']])->asArray()->one(Yii::$app->get('db_kdkj_rd'));
            if (!empty($rong360LoanOrder)) {
                $result['value'] = $rong360LoanOrder['platform'];
            }
        }
        return $result;
    }

    /**
     * 口袋记账连续记账月份
     * @param $data
     * @param $params
     * @return array
     */
    public function checkKdjzRecordMonth($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关记账信息', 'value' => self::NULL];
        if (isset($data['external_account']['code'])) {
            switch ($data['external_account']['code']) {
                case 0 :
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '口袋记账连续记账月份', 'value' => 0];
                    break;
                case 1 :
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '口袋记账连续记账月份', 'value' => $data['external_account']['data']['latest_keep_mouth_recent_year'] ?? 0];
                    break;
                case -1 :
                    $result = ['risk' => self::MEDIUM_RISK, 'detail' => '口袋记账连续记账月份', 'value' => -1];
                    break;
                default :
                    break;
            }
        }

        return $result;
    }

    /**
     * 公积金数据-最近一年缴纳月数
     * @param $data
     * @param $params
     * @return array
     */
    public function checkAccumulationFundPayMonths($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关公积金信息', 'value' => self::NULL];
        if (isset($data['accumulation_fund']['pay_months'])) {

            $result = ['risk' => self::MEDIUM_RISK,
                'detail' => '公积金最近一年缴纳月数',
                'value' => $data['accumulation_fund']['pay_months']
            ];

        }

        return $result;
    }

    /**
     * 公积金数据-最近一年缴纳平均金额
     * @param $data
     * @param $params
     * @return array
     */
    public function checkAccumulationFundAverageAmt($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关公积金信息', 'value' => self::NULL];
        if (isset($data['accumulation_fund']['avarage_amt'])) {
            $result = [
                'risk' => self::MEDIUM_RISK,
                'detail' => '公积金最近一年缴纳平均金额',
                'value' => $data['accumulation_fund']['avarage_amt']
            ];
        }

        return $result;
    }

    /**
     * 公积金数据-缴纳城市
     * @param $data
     * @param $params
     * @return array
     */
    public function checkAccumulationFundDataSource($data, $params)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '没有相关公积金信息', 'value' => self::NULL];
        if (isset($data['accumulation_fund']['data_source'])) {
            $result = [
                'risk' => self::MEDIUM_RISK,
                'detail' => '公积金缴纳城市',
                'value' => $data['accumulation_fund']['data_source']
            ];
        }

        return $result;
    }

    /**
     * 后台黑名单
     * @param $data
     * @return array
     */
    public function checkLoanBlackList($data)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '不在黑名单', 'value' => self::NO];
        $loan_person = $data['loan_person'];
        if ($loan_person && LoanBlackList::isInBlacklist($loan_person)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '存在于黑名单', 'value' => self::YES];
        }

        return $result;
    }

    /**
     * 注册号码、第一联系人、第二联系人是否相同
     * @param $data
     * @return array
     */
    public function checkContactIsSame($data)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '注册号码、第一联系人、第二联系人均不相同', 'value' => self::NO];
        $contact = $data['user_contact'];
        $loan_person = $data['loan_person'];
        if ($contact) {
            if ($loan_person['phone'] == $contact['mobile']) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '注册号码与第一联系人相同', 'value' => self::YES];
            }

            if ($loan_person['phone'] == $contact['mobile_spare']) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '注册号码与第二联系人相同', 'value' => self::YES];
            }

            if ($contact['mobile'] == $contact['mobile_spare']) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '第一联系人与第二联系人相同', 'value' => self::YES];
            }
        }

        return $result;
    }

    /**
     * 身份证识别是否涉嫌欺诈
     * @param $data
     * @return array
     */
    public function checkFaceIdCardFraud($data)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '身份证识别不涉嫌欺诈', 'value' => self::NO];
        if (isset($data['face_id_card']) && !empty($data['face_id_card']['data']['legality']) && ($data['face_id_card']['data']['legality']['ID Photo'] < 0.75 || $data['face_id_card']['data']['legality']['Photocopy'] >= 0.9 || $data['face_id_card']['data']['legality']['Edited'] >= 0.9)) {
            $result = ['risk' => self::MEDIUM_RISK, 'detail' => '身份证识别涉嫌欺诈', 'value' => self::YES];
        }

        return $result;
    }


    /**
     * 1.如果用户在“秒还卡”有状态为借款中的订单，则拒绝
     * 2.如果用户在“秒还卡”的历史订单逾期大于等于10天，则拒绝
     * 3.如果用户在“秒还卡”的催收建议为拒绝，则拒绝
     * @param $data
     * @return array
     */
    public function checkMhkOrder($data)
    {
        if (isset($data['mhk_order']['loan']) && $data['mhk_order']['loan']) {
            return [
                'risk' => self::MEDIUM_RISK,
                'detail' => '有在秒还卡借款订单拒绝',
                'value' => 0
            ];
        }
        if (isset($data['mhk_order']['repay']) && $data['mhk_order']['repay']) {
            return [
                'risk' => self::MEDIUM_RISK,
                'detail' => '在秒还卡历史订单逾期大于等于10天，拒绝',
                'value' => 0
            ];
        }
        if (isset($data['mhk_order']['collection']) && $data['mhk_order']['collection']) {
            return [
                'risk' => self::MEDIUM_RISK,
                'detail' => '在秒还卡的催收建议为拒绝',
                'value' => 0
            ];
        }

        return [
            'risk' => self::LOW_RISK,
            'detail' => '可以通过',
            'value' => 1
        ];
    }

    /**
     * 钱包黑名单-V2
     * @param $data
     * @return array
     */
    public function checkJsqbBlacklistNew($data)
    {
        $result = ['risk' => self::MEDIUM_RISK, 'detail' => '黑名单信息不存在', 'value' => self::NULL];
        if (isset($data['jsqb_blacklist']['is_in'])) {
            if ($data['jsqb_blacklist']['is_in'] == CreditJsqbBlacklist::IN_BLACKLIST) {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '命中钱包黑名单', 'value' => self::YES];
            } else {
                $result = ['risk' => self::MEDIUM_RISK, 'detail' => '未命中钱包黑名单', 'value' => self::NO];
            }
        }

        return $result;
    }

    /**
     * 身份证性别
     * @param $data
     * @param $params
     * @return array
     */
    public function checkIdCardGender($data, $params)
    {

        $loan_person = $data['loan_person'];

        //通过身份证判断性别
        $id_card_len = strlen($loan_person['id_number']);
        if ($id_card_len == 18) {
            $n = (int)substr($loan_person['id_number'], -2, 1);
            $gender = $n % 2;
        } elseif ($id_card_len == 15) {
            $n = (int)substr($loan_person['id_number'], -1, 1);
            $gender = $n % 2;
        } else {
            $gender = -1;
        }

        return ['risk' => self::LOW_RISK, 'detail' => $gender, 'value' => $gender];
    }

    /**
     * 同盾信息
     * @param $data
     * @param $params
     * @return array
     */
    public function checkTdLoansWithParams($data, $params) {
        $td = $data['td'];
        if (empty($td) || empty($td['result_desc']) || empty($td['result_desc']['ANTIFRAUD'])) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到同盾信息', 'value' => self::MEDIUM_RISK];
        }

        $item_id = $params['item_id'] ?? '';
        $type = $params['type'] ?? '';

        $value = self::NULL;
        $item_name = '';
        $data = $td['result_desc']['ANTIFRAUD'];
        foreach ($data['risk_items'] as $v) {
            switch ($v['rule_id']) {
                case $item_id:
                    $item_name = $v['risk_name'];
                    if ($type == '总数') {
                        $value = isset($v['risk_detail'][0]['platform_count']) ? $v['risk_detail'][0]['platform_count'] : self::NULL;
                    }
                    else {
                        if (isset($v['risk_detail'][0]['frequency_detail_list'])) {
                            foreach ($v['risk_detail'][0]['frequency_detail_list'] as $v2) {
                                if (isset($v2['detail'])) {
                                    list($k3, $v3) = explode(':', $v2['detail']);
                                    if ($k3 == $type) {
                                        $value = (int)$v3;
                                        break;
                                    }
                                }
                            }
                        }
                        else {
                            throw new \Exception('响应结构异常 ' . __LINE__);
                        }
                    }

                    break 2;
                default:
                    break;
            }
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => "$item_name-$type:$value",
            'value' => $value,
        ];
    }

    /**
     * 白骑士信息
     * @param $data
     * @param $params
     * @return array
     */
    public function checkBqsLoansWithParams($data, $params) {
        $bqs = $data['bqs'];
        if (empty($bqs)) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到白骑士信息', 'value' => self::NULL];
        }

        $item_id = $params['item_id'] ?? '';
        $type = $params['type'] ?? '';
        $value = self::NULL;
        $item_name = '';
        if (isset($bqs['strategySet']) && !empty($bqs['strategySet'])) {
            foreach ($bqs['strategySet'] as $k1 => $v1) {
                if ($v1['riskType'] == 'multipleLoan' && isset($v1['hitRules']) && !empty($v1['hitRules'])) {
                    foreach ($v1['hitRules'] as $k2 => $v2) {
                        if ($v2['ruleId'] == $item_id) {
                            $item_name = $v2['ruleName'];
                            $detail_arr = explode(',', $v2['memo']);
                            foreach ($detail_arr as $k3 => $v3) {
                                list($k4, $v4) = explode(':', $v3);
                                if ($k4 == $type) {
                                    $value = (int)$v4;
                                }
                            }
                            break 2;
                        }
                    }
                }
            }
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => "$item_name-$type:$value",
            'value' => $value,
        ];
    }

    /**
     * 聚信立报告
     * @param $data
     * @param $params
     * @return array
     */
    public function checkJxlWithParams($data, $params)
    {

        $data = $data['jxl'];
        if (empty($data)) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到聚信立信息', 'value' => self::NULL];
        }

        $item_id = $params['item_id'] ?? '';
        $type = $params['type'] ?? '';
        $value = self::NULL;

        switch ($type) {
            case 'value' :
                if (!empty($item_id)) {
                    $paths = explode('-', $item_id);
                    $value = $data;
                    foreach ($paths as $path) {
                        if (!isset($value[$path])) {
                            $value = self::NULL;
                            break;
                        }
                        $value = $value[$path];
                    }
                }
                break;

            case 'behavior_check':
                $behavior_check = $data['behavior_check'] ?? '';
                if (!empty($behavior_check)) {
                    foreach ($behavior_check as $behavior) {
                        if (isset($behavior['check_point']) && $behavior['check_point'] == $item_id) {
                            $value = $behavior['result'] ?? self::NULL;
                            break;
                        }
                    }
                }
                break;
            case 'cell_behavior':
                $cell_behavior = $data['cell_behavior'][0]['behavior'] ?? '';
                if (!empty($cell_behavior)) {
                    $total = 0;
                    $count = 0;
                    foreach ($cell_behavior as $index => $behavior) {
                        if ($index > 0 && isset($behavior[$item_id])) {
                            $total += $behavior[$item_id];
                            $count ++;
                        }
                    }

                    if ($count > 0) {
                        $value = $total / $count;
                    }
                }
                break;
            case 'cell_behavior_latest':
                $value = $data['cell_behavior'][0]['behavior'][0][$item_id] ?? self::NULL;
                break;
            case 'contact_list':
                $contact_list = $data['contact_list'] ?? '';
                if (!empty($contact_list)) {
                    $count = 0;
                    $isset = false;
                    foreach ($contact_list as $contact) {
                        if (isset($contact[$item_id])) {
                            $count += $contact[$item_id];
                            $isset = true;
                        }
                    }
                    if ($isset) {
                        $value = $count;
                    }
                }
                break;
            case 'application_check':
                $application_check = $data['application_check'] ?? '';
                if (!empty($application_check)) {
                    list($k1, $k2) = explode('-', $item_id);
                    foreach ($application_check as $application) {
                        if (isset($application['app_point']) && $application['app_point'] == $k1) {
                            $value = $application['check_points'][$k2] ?? self::NULL;
                            break;
                        }
                    }
                }
                break;
            default :
                break;
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' =>$value,
            'value' => $value,
        ];
    }

    /**
     * 蜜罐信息
     * @param $data
     * @param $params
     * @return array
     */
    public function checkMgWithParams($data, $params)
    {
        $data = $data['mg'];
        if (empty($data)) {
            return ['risk' => self::MEDIUM_RISK, 'detail' => '未获取到蜜罐信息', 'value' => self::NULL];
        }

        $item_id = $params['item_id'] ?? '';
        $type = $params['type'] ?? '';

        $value = self::NULL;

        switch ($type) {
            case 'count':
                if (!empty($item_id)) {
                    $paths = explode('-', $item_id);
                    $array = $data;
                    foreach ($paths as $path) {
                        if (!isset($array[$path])) {
                            $value = self::NULL;
                            break 2;
                        }
                        $array = $array[$path];
                    }
                    $value = count($array);
                }
                break;
            default:
                break;
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $value,
            'value' => $value,
        ];
    }

    /**
     * @name 详单排名前10的短号个数
     */
    public function checkJxlInfo($data){
        $loan_person = $data['jxl_phone_shot'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单排名前10与通讯录的匹配个数(去除短号)
     */
    public function checkJxlInfoMatch($data){
        $loan_person = $data['jxl_phone_match'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单与通讯录的匹配个数
     */
    public function checkJxlInfoMatchAll($data){
        $loan_person = $data['jxl_phone_match_all'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单通话时长>30分钟的号码个数（短号除外）
     */
    public function checkJxlInfoThirty($data){
        $loan_person = $data['jxl_phone_wten'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单通话时长>10分钟的号码个数（短号除外）
     */
    public function checkJxlInfoTen($data){
        $loan_person = $data['jxl_phone_ten'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单异常号码个数
     */
    public function checkJxlInfoMatchError($data){
        $loan_person = $data['all_jxl_phone_match_error'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }
    /**
     * @name 详单异常号码个数
     */
    public function checkJxlInfoError($data){
        $loan_person = $data['all_jxl_phone_error'];
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => $loan_person,
            'value' => $loan_person,
        ];
    }

    /**
     * @name 高危民族
     * return $count 是否命中
     */
    public function checkDangerPersonNation($data){
        $loan_person = $data['loan_person'];
        $id_card = $loan_person->id_number;
        $count = 0;
        $face_data = CreditFaceIdCard::findLatestOne(['user_id'=>$loan_person->id,'type'=>1]);
        if($face_data){
            $face_data_res =json_decode($face_data->data,true);
            if(isset($face_data_res['data']['address'])){
            $person_where = $face_data_res['data']['race'];
                foreach (LoanPerson::$nation_danger_list as $v){
                    if(strstr($person_where,$v)){
                        $count = 1;
                    }
                }
            }
        }
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => '高危险名族',
            'value' => $count,
        ];
    }

    /**
     * @name 高危民族
     * return $count 是否命中
     */
    public function checkDangerIdCardNation($data){
        $loan_person = $data['loan_person'];
        $id_card = $loan_person->id_number;
        $count = 0;

        //取身份证的前2位
        $id_card_before_two=substr($id_card,0,2);
        if(in_array($id_card_before_two,LoanPerson::$id_card_area)){
            $count = 1;
        }else{
            $face_data = CreditFaceIdCard::findLatestOne(['user_id'=>$loan_person->id,'type'=>1]);
            if($face_data){
                $face_data_res =json_decode($face_data->data,true);
                if(isset($face_data_res['data']['address'])){
                    $person_where = $face_data_res['data']['address'];
                    foreach (LoanPerson::$id_card_name as $v){
                        if(strstr($person_where,$v)){
                            $count = 1;
                        }
                    }
                }
            }
        }

        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => '高风险进件区域',
            'value' => $count,
        ];
    }

    /**
     *高危公司
     */
    public function CheckDangerCompanyName($data){
        $loan_person = $data['loan_person'];
        $info = UserDetail::find()->where(['user_id' => $loan_person->id])->asArray()->one();
        $count = 0;
        //查询填写的工作信息
        if(isset($info['company_name'])){
            $person = new LoanPerson();
            $person_danger_hangye_list = $person->danger_hangye_list;
            $name = $info['company_name'];
            foreach ($person_danger_hangye_list as $v){
                if(strstr($name,$v)){
                    $count = 1;
                }
            }
        }
        return [
            'risk' => self::MEDIUM_RISK,
            'detail' => '高危公司',
            'value' => $count,
        ];
    }
    /**
     *@name 宜信第三方----逾期次数
     */
    public function CheckYxOverdue($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) &&  $data_arr['errorCode'] == '0000') {
            if (isset($data_arr['params']['data']['loanRecords'])) {//借款记录存在
                $loan_info = $data_arr['params']['data']['loanRecords'];
                $count = 0;
                foreach ($loan_info as $k=>$v){
                    $count += intval($v['overdueTotal']);
                }
                if (isset($count)) {
                    return [
                        'risk' => self::MEDIUM_RISK,
                        'detail' => '宜信第三方--历史逾期次数',
                        'value' => $count,
                    ];
                }
            }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方--历史逾期次数(无数据)',
                    'value' => 1000,
                ];
            }
        }

    }
    /**
     *@name 宜信第三方----逾期M3次数
     */
    public function CheckYxOverdueM3($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000') {
            if (isset($data_arr['params']['data']['loanRecords'])) {//借款记录存在
                $loan_info = $data_arr['params']['data']['loanRecords'];
                if (isset($loan_info)) {
                    $count = 0;
                    foreach ($loan_info as $k=>$v){
                        $count += intval($v['overdueM3']);
                    }
                    return [
                        'risk' => self::MEDIUM_RISK,
                        'detail' => '宜信第三方--历史逾期M6次数',
                        'value' => $count,
                    ];
                } else {
                    return [
                        'risk' => self::LOW_RISK,
                        'detail' => '宜信第三方--历史逾期M6次数(无数据)',
                        'value' => 0,
                    ];
                }
            }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方--历史逾期M6次数(无数据)',
                    'value' => 1000,
                ];
            }
        }

    }
    /**
     *@name 宜信第三方----逾期M6次数
     */
    public function CheckYxOverdueM6($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000') {
            if (isset($data_arr['params']['data']['loanRecords'])) {//借款记录存在
                $loan_info = $data_arr['params']['data']['loanRecords'];
                if (isset($loan_info)) {
                    $count = 0;
                    foreach ($loan_info as $k=>$v){
                        $count += intval($v['overdueM6']);
                    }
                    return [
                        'risk' => self::MEDIUM_RISK,
                        'detail' => '宜信第三方--历史逾期总次数',
                        'value' => $count,
                    ];
                } else {
                    return [
                        'risk' => self::LOW_RISK,
                        'detail' => '宜信第三方--历史逾期总次数(无数据)',
                        'value' => 0,
                    ];
                }
            }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方--历史逾期总次数(无数据)',
                    'value' => 1000,
                ];
            }
        }

    }

    /**
     * @name 宜信第三方----风险类型
     */
    public function CheckYxFenxian($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000') {
            if (isset($data_arr['params']['data']['riskResults'])) {//风险记录存在
                $risk_info = $data_arr['params']['data']['riskResults'];
                if(isset($risk_info['riskTypeCode'])){
                    return [
                        'risk' => self::LOW_RISK,
                        'detail' => '宜信第三方-风险记录',
                        'value' => isset(CreditYx::$info_error[$risk_info['riskTypeCode']])?CreditYx::$info_error[$risk_info['riskTypeCode']]:0,
                    ];
                }else{
                    return [
                        'risk' => self::LOW_RISK,
                        'detail' => '宜信第三方-风险记录(无数据)',
                        'value' => 0,
                    ];
                }
            }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方-风险记录(无数据)',
                    'value' => 1000,
                ];
            }
        }
    }

    /**
     * @name 宜信第三方-逾期记录M1
     */
    public function CheckYxOverdueM1($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000') {
            if (isset($data_arr['params']['data']['loanRecords'])) {//借款记录存在
                $loan_info = $data_arr['params']['data']['loanRecords'];
                $count = 0;
                foreach ($loan_info as $k=>$v){
                    if($v['overdueStatus'] == 'M1'){
                        $count ++;
                    }
                }
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方-逾期记录M1',
                    'value' => $count,
                ];
            }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方-逾期记录M1(无数据)',
                    'value' =>1000,
                ];
            }
        }
    }
    /**
     * @name 宜信第三方-逾期记录M2
     */
    public function CheckYxOverdueM2($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) &&  $data_arr['errorCode'] == '0000' && isset($data_arr['params']['data']['loanRecords'])) {
                $loan_info = $data_arr['params']['data']['loanRecords'];
                $count = 0;
                foreach ($loan_info as $k=>$v){
                    if(in_array($v['overdueStatus'],CreditYx::$overdueStatusList)) {
                        $count++;
                    }
                }
                $res = 0;
                if($count > 1){
                    $res = 1;
                }
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信第三方-逾期记录M2以上',
                    'value' => $res,
                ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信第三方-逾期记录M2以上(无数据)',
                'value' => 1000,
            ];
        }
    }
    /**
     * 宜信-5次拒绝记录
     */
    public function CheckYxLatelyLoan($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000' && isset($data_arr['params']['data']['loanRecords'])) {
            $arr = $data_arr['params']['data']['loanRecords'];
            $all_count = count($arr);
            $count = 0;
            if($all_count > 5){
                for($i =0;$i<5;$i++){
                    if($arr[$i]['approvalStatusCode'] == '203'){
                        $count ++;
                    }
                }
                $all_count = 5;
                $ret = sprintf(($count/$all_count)*100);
            }else if($all_count == 0){
                $ret = 0;
            }else{
                foreach ($arr as $k=>$v){
                    if($v['approvalStatusCode'] == '203'){
                        $count ++;
                    }
                }
                $ret = sprintf(($count/$all_count)*100);
            }
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信第三方-5次通过记录',
                'value' => intval($ret),
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信第三方-5次通过记录(无数据)',
                'value' => 1000,
            ];
        }
    }
    /**
     *  宜信-风险名单
     */
    public function CheckYxDangerInfo($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000' && isset($data_arr['params']['data']['riskResults'])) {
            $detail = $data_arr['params']['data']['riskResults'];
            $count = 0;
            foreach ($detail as $k1=>$v1){
                foreach (CreditYx::$danger_string_list as $k=>$v){
                    if(strstr($v1['riskDetail'],$v)){
                        $count ++;
                    }
                }
                if(strstr($v1['riskDetail'],'亿美平台逾期')){
                    $count --;
                }
            }
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-风险名单',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-风险名单(无数据)',
                'value' => 1000,
            ];
        }
    }

    /**
     * 宜信-至诚评分
     */
    public function CheckZcFen($data){
        $data_arr = $data['yx'];
        if(isset($data_arr['errorCode']) && $data_arr['errorCode'] == '0000' && isset($data_arr['params']['data']['zcCreditScore'])) {
            $fen = $data_arr['params']['data']['zcCreditScore'];
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-至诚评分',
                'value' => $fen,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-至诚评分-默认平分',
                'value' => 1000,
            ];
        }
    }
    /**-----------宜信阿福start-----------**/
    /**
     * @name 风险明细
     * @return array
     */
    public function CheckAfDetail($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['resultList'])) {
            $info = $data_arr['data']['resultList'];
            if(is_array($info)){
                foreach ($info as $k=>$v){
                    if(in_array($v['riskDetail'],Yxservice::$risk_detail)){
                        return [
                            'risk' => self::HIGH_RISK,
                            'detail' => $v['riskDetail'],
                            'value' => 1,
                        ];
                        break;
                    }
                }
            }
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-风险明细',
                'value' => 0,
            ];
        }
    }
    /**
     * @name 欺诈等级
     * @return array
     */
    public function CheckAfLevelCode($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['fraudLevelCode'])) {
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-欺诈等级',
                'value' => $data_arr['data']['fraudLevelCode'],
            ];
        }else{
                return [
                    'risk' => self::LOW_RISK,
                    'detail' => '宜信-阿福-未命中',
                    'value' => 0,
                ];

        }
    }
    /**
     * @name 一阶联系人黑名单个数
     */
    public function CheckOneBlackNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['firstOrderBlackCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['firstOrderBlackCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-一阶联系人黑名单个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }
    /**
     * @name 一阶联系人逾期个数
     */
    public function CheckFirstOverNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['firstOrderOverdueCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['firstOrderOverdueCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-一阶联系人逾期个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 一阶联系人逾期m3+个数
     */
    public function CheckFirstM3OverNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['firstOrderM3Cnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['firstOrderM3Cnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-一阶联系人逾期m3+个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 二阶联系人黑名单个数
     */
    public function CheckSecondBlackNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['secondOrderBlackCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['secondOrderBlackCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-二阶联系人黑名单个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 二阶联系人逾期个数
     */
    public function CheckSecondOverNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['secondOrderOverdueCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['secondOrderOverdueCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-二阶联系人逾期个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 二阶联系人逾期m3+个数
     */
    public function CheckSecondM3OverNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['secondOrderM3Cnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['secondOrderM3Cnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-二阶联系人逾期m3+个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 主叫联系人黑名单个数
     */
    public function CheckContactsBlackNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['activeCallBlackCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['activeCallBlackCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-主叫联系人黑名单个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 主叫联系人逾期个数
     */
    public function CheckCallBlackNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['activeCallOverdueCnt'])) {
            $count = $data_arr['data']['behaviorFeatures']['activeCallOverdueCnt'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-主叫联系人逾期个数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 与法院通话次数
     */
    public function CheckCallCourtNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['courtCallNum'])) {
            $count = $data_arr['data']['behaviorFeatures']['courtCallNum'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-与法院通话次数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }

    /**
     * @name 与律师通话次数
     */
    public function CheckCallLawyerNum($data){
        $data_arr = $data['yx_af'];
        if(isset($data_arr['errorcode']) && $data_arr['errorcode'] == '0000' && isset($data_arr['data']['behaviorFeatures']['lawyerCallNum'])) {
            $count = $data_arr['data']['behaviorFeatures']['lawyerCallNum'];
            return [
                'risk' => self::HIGH_RISK,
                'detail' => '宜信-阿福-与律师通话次数',
                'value' => $count,
            ];
        }else{
            return [
                'risk' => self::LOW_RISK,
                'detail' => '宜信-阿福-未命中',
                'value' => 0,
            ];
        }
    }
    /**-----------宜信阿福end-----------20180509**/

    /**-----------新增加6条规则begin----20181019**/

    /**
     *110通话次数
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
    **/
    public function checkPoliceCallQuantity($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['police_quantity'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '与110通话次数', 'value' => $raw_data['police_quantity']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '与110通话次数没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     *110通话时长
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     **/
    public function checkPoliceCallTime($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['police_time'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '与110通话时长', 'value' => $raw_data['police_time']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '与110通话时长没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     *近7天通话次数
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     **/
    public function checkSevenCallQuantity($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['seven_quantity'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '近7天通话次数', 'value' => $raw_data['seven_quantity']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '近7天通话次数没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     *近7天通话时长
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     **/
    public function checkSevenCallTime($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['seven_time'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '近7天通话时长', 'value' => $raw_data['seven_time']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '近7天通话时长没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     *近15天通话次数
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     **/
    public function checkFifteenCallQuantity($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['fifteen_quantity'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '近15天通话次数', 'value' => $raw_data['fifteen_quantity']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '近15天通话次数没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     *近15天通话时长
     * @param array $data
     * @param array $params
     * @return array ['risk'=>"0:低风险, 1:中风险, 2:高风险", 'detail' => "描述"]
     **/
    public function checkFifteenCallTime($data, $params){
        $loan_person = $data['loan_person'];
        //借款用户id
        $person_id=$loan_person->id;
        $raw_data=self::getJxlRawRuleData($person_id);
        if(isset($raw_data['fifteen_time'])){
            $result = ['risk' => self::HIGH_RISK, 'detail' => '近15天通话时长', 'value' => $raw_data['fifteen_time']];
        }else{
            $result = ['risk' => self::LOW_RISK, 'detail' => '近15天通话时长没有相关信息', 'value' =>-1 ];
        }
        return $result;
    }

    /**
     * 获得新增加6条规则数据
     * @param int $person_id
     * @return array
    **/
    private function getJxlRawRuleData($person_id){
        $redius_key=CreditJxl::RAW_JXL_RULE.$person_id;
        $raw_rule=RedisQueue::get(['key'=>$redius_key]);
        $is_update=true;
        if(!empty($raw_rule)){
            $raw_rule=json_decode($raw_rule,true);
            $update_date=$raw_rule['update_date'];
            //如果没有超过6个月将不更新(1天=86400秒)
            if($update_date<time()+6*30*86400){
                $is_update=false;
            }
        }

        if($is_update){
            $raw_rule=['update_date'=>time()];
            $credit_jxl=CreditJxl::find()
                ->where(['person_id'=>$person_id,'raw_status'=>CreditJxl::RAW_STATUS_TRUE])
                ->select('raw_data,updated_at,created_at')->one();
            if($credit_jxl){
                $updated_at=$credit_jxl->updated_at;
                if($updated_at=='' || empty($updated_at)){
                    $updated_at=$credit_jxl->created_at;
                }
                //聚信立运营商原始数据
                $raw_data=$credit_jxl->raw_data;
                if($raw_data!=''&&!empty($raw_data)){
                    $raw_data=json_decode($raw_data,true);
                    foreach ($raw_data as $k=>$v){
                        //对方号码
                        $other_cell_phone=trim($v['other_cell_phone']);
                        //通话时间（年-月-日 时:分:秒）
                        $start_time=trim($v['start_time']);
                        //通话时长（秒）
                        $use_time=floatval(trim($v['use_time']));
                        //与110通话次数、通话时长
                        if($other_cell_phone=='110'){
                            $raw_rule['police_quantity']=(isset($raw_rule['police_quantity'])?$raw_rule['police_quantity']:0)+1;
                            $raw_rule['police_time']=(isset($raw_rule['police_time'])?$raw_rule['police_time']:0)+$use_time;
                        }
                        //近7天通话次数、通话时长
                        if($updated_at<=strtotime($start_time)+7*86400){
                            $raw_rule['seven_quantity']=(isset($raw_rule['seven_quantity'])?$raw_rule['seven_quantity']:0)+1;
                            $raw_rule['seven_time']=(isset($raw_rule['seven_time'])?$raw_rule['seven_time']:0)+$use_time;
                        }

                        //近15天通话次数、通话时长
                        if($updated_at<=strtotime($start_time)+15*86400){
                            $raw_rule['fifteen_quantity']=(isset($raw_rule['fifteen_quantity'])?$raw_rule['fifteen_quantity']:0)+1;
                            $raw_rule['fifteen_time']=(isset($raw_rule['fifteen_time'])?$raw_rule['fifteen_time']:0)+$use_time;
                        }
                    }
                    //判断110通话是否存在
                    if(!isset($raw_rule['police_quantity'])){
                        $raw_rule['police_quantity']=0;
                        $raw_rule['police_time']=0;
                    }
                    //判断近7天通话是否存在
                    if(!isset($raw_rule['seven_quantity'])){
                        $raw_rule['seven_quantity']=0;
                        $raw_rule['seven_time']=0;
                    }
                    //判断近15天通话是否存在
                    if(!isset($raw_rule['fifteen_quantity'])){
                        $raw_rule['fifteen_quantity']=0;
                        $raw_rule['fifteen_time']=0;
                    }
                }
            }
            //保存到redius中(保存时间6个月)
            $expire=6*30*86400;
            RedisQueue::set(['expire'=>$expire,'key'=>$redius_key,'value'=>json_encode($raw_rule)]);
        }
        return $raw_rule;
    }
    /**-----------新增加6条规则end------20181019**/
}
