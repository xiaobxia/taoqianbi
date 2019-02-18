<?php
/**
 * Created by PhpStorm.
 * User: wangwei
 * Date: 2018/4/20
 * Time: 17:44
 */

namespace common\services;

use Yii;
use yii\base\Component;
use common\helpers\CurlHelper;
use common\models\CreditQueryLog;
use common\models\CreditYx;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;


class Yxservice extends Component
{
    public $sign;
    //机构查询地址
    const QUERY_URL = 'https://www.zhichengcredit.com/echo-center/api/echoApi/v3';
    //致诚阿福
    const ZCAF_URL = 'https://www.zhichengcredit.com/echo-center/api/mixedRiskQuery/queryMixedRiskList/v3';
    const ZCAF_USER_ID = 'dichang';
    const ZCAF_USER_PWD = '44b81e206bd2b2b7';
    //用户信息
    const USER_ID = 'dichang';
    //加密字符串
    const RC4 = '44b81e206bd2b2b7';
    //测试的dfb686522d0cfcf2

    const QUERY_REASON_LOAN = 10;//贷款审批
    const QUERY_REASON_AFTER_LOAN = 11;//贷后管理
    const QUERY_REASON_CREDIT_CARD = 12;//信用卡审批
    const QUERY_REASON_DANBAO = 13;//担保资格审查
    const QUERY_REASON_BEFOR_DANTAO = 14;//保前审查

    //请求参数
    const REQUEST_QUERY_LOAN = 101;//主动查询业务
    const REQUEST_UPLOAD_INFO = 102;//上传证件号业务
    const REQUEST_PINGTAI_QUERY = 201;//平台查询
    //返回参数
    public static $risk_detail = [
        '长期拖欠',
        '伪冒',
        '丧失还款能力',
        '同行中介',
        '不良客户',
        '法院—失信',
        '法院—被执行',
    ];


    /**
     * $name 签名生成
     * @return string
     */
    public static function sign(){//todo test
        $sign = md5(self::USER_ID.self::RC4);
        return $sign;
    }

    /**
     * 组装查询测试接口
     */
    public static function query($params,$type = 1){
        $user_info['data']['name'] = $params['username'];
        $user_info['data']['idNo'] = $params['id_card'];
        //$user_info['data']['name'] = '邱锦东';
        //$user_info['data']['idNo'] = '441823199009113717';
        $user_info['data']['queryReason'] = self::QUERY_REASON_LOAN;
        $user_info['tx'] = self::REQUEST_QUERY_LOAN;
        $data['sign'] = self::sign();
        $data['userid'] = self::USER_ID;
        $url = self::QUERY_URL;
        if($type == 2){
            $data['userid'] = self::ZCAF_USER_ID;
            unset($user_info['tx']);
            unset( $user_info['data']['queryReason']);
            $user_info['queryReason']  = self::QUERY_REASON_LOAN;
            $user_info['data']['pricedAuthentification'] = "";
            $url = self::ZCAF_URL;
        }
        $data['params'] = json_encode($user_info);
        $data_res = CurlHelper::curlHttp($url, 'post', $data);
        return $data_res;
    }
    /**
     * 数据保存
     * $params LoanPerson
     */
    public static function saveUserInfo($params,$type){
        $user_id = $params->id;
        $user_info['data']['name'] = $params->name;
        $user_info['data']['idNo'] = $params->id_number;
        $user_info['data']['queryReason'] = self::QUERY_REASON_LOAN;
        $user_info['tx'] = self::REQUEST_QUERY_LOAN;
        $data['sign'] = self::sign();
        $data['userid'] = self::USER_ID;
        $url = self::QUERY_URL;
        if($type == 2){
            $data['userid'] = self::ZCAF_USER_ID;
            unset($user_info['tx']);
            unset( $user_info['data']['queryReason']);
            $user_info['queryReason']  = self::QUERY_REASON_LOAN;
            $user_info['data']['pricedAuthentification'] = "";
            $url = self::ZCAF_URL;
        }
        $data['params'] = json_encode($user_info);
        try {
            $transaction = Yii::$app->db->beginTransaction();
            $data = CurlHelper::curlHttp($url, 'post', $data);
            $credit_yx_data = new CreditYx();
            $credit_yx_data->user_id = $user_id;
            $credit_yx_data->data = json_encode($data);
            $credit_yx_data->type = $type;
            $credit_yx_data->user_id = $user_id;
            $credit_yx_data->over_time = time()+30*86400;
            $credit_yx_data->created_at = time();
            $credit_yx_data->updated_at = time();
            if($credit_yx_data->save()){
                $transaction->commit();
                return $code = [
                    'msg'=>'ok',
                    'code'=>1,
                ];
            }else{
                $transaction->rollBack();
                Yii::error('yx_save_info_error'.json_encode($data,JSON_UNESCAPED_UNICODE ),'yx_data_save');
                return $code = [
                    'msg'=>'fail',
                    'code'=>0,
                ];
            }
        }catch (\Exception $e){
            return $code = [
                'msg'=>'error',
                'code'=>-1,
            ];
        }
    }


    /*
     *判断用户数据是否过期
     */
    public static function getData($params,$type = 1){
        if(isset($params->id)){
            $data_res =  CreditYx::find()->where(['user_id'=>$params->id,'type'=>$type])->orderBy('id desc')->one();
            $now = time();
            if(empty($data_res) || $data_res->over_time - $now < 0){
                return self::saveUserInfo($params,$type);
            }else{
                return $data_res;
            }
        }
    }

    /**
     * 查询数据
     */
    public static function getLoanData($params,$test){
        //借款记录
        $loan_person = LoanPerson::find()->where(['name'=>$params['name'],'id_number'=>$params['idNo']])->one();
        $data_order = [];
        if($loan_person ){
            $orders = UserLoanOrder::find()
                ->where(['user_id'=>$loan_person->id])->all();
            foreach ($orders as $k=>$v){
                $data_order1['loanRecords'][$k]['periods'] = 1;
                $data_order1['loanRecords'][$k]['loanAmount'] = 1500;
                $data_order1['loanRecords'][$k]['name'] = $params['name'];
                $data_order1['loanRecords'][$k]['certNo'] = $params['idNo'];
                $data_order1['loanRecords'][$k]['loanDate'] = date('Ymd',$v['created_at']);
                $data_order1['loanRecords'][$k]['approvalStatusCode'] = self::getRepayment($v,1);
                $data_order1['loanRecords'][$k]['loanTypeCode'] = 21;
                if($data_order1['loanRecords'][$k]['approvalStatusCode'] == 202){//借款通过
                    $data_order1['loanRecords'][$k]['loanStatusCode'] = self::getRepayment($v,2);
                    if(self::getRepayment($v,2) == 302){
                        $status =  self::getRepayment($v,3);
                        $over_money =  self::getRepayment($v,4);
                    }else{
                        $status = "";
                        $over_money = "";
                    }
                    $data_order1['loanRecords'][$k]['overdueAmount'] = $over_money;
                    $data_order1['loanRecords'][$k]['overdueStatus'] = $status;
                    $data_order1['loanRecords'][$k]['overdueTotal'] =  self::getOverdueDay($v,1) != 0?self::getOverdueDay($v,1):"";
                    $data_order1['loanRecords'][$k]['overdueM3'] =  self::getOverdueDay($v,2) != 0?self::getOverdueDay($v,2):"";
                    $data_order1['loanRecords'][$k]['overdueM6'] =  self::getOverdueDay($v,3) != 0?self::getOverdueDay($v,3):"";
                }
                //风险项记录
                $data_reason =  self::getReason($v);
                if(!empty($data_reason) && !empty($data_reason['message']) && !empty($data_reason['time'])){
                    $data_order2['riskResults'][$k]['riskItemTypeCode'] = "101010";
                    $data_order2['riskResults'][$k]['riskItemValue'] = $params['idNo'];
                    $data_order2['riskResults'][$k]['riskDetail'] = $data_reason['message'];
                    $data_order2['riskResults'][$k]['riskTime'] = $data_reason['time'];
                }else{
                    $data_order2['riskResults'] = [];
                }
            }

        }
        if($test == 'test'){
            if(!empty($data_order1) && !empty($data_order2)){
                $arr = $data_order2['riskResults'];
                $data_order2_new = [];
                $data_order_res2 = [];
                $data = [];
                foreach ($arr as $k=>$v){
                    $data[$v['riskDetail']] = $k;
                }

                foreach ($data as $k1=>$v1){
                    $data_order2_new[$v1] = $arr[$v1];
                }
                //处理拒绝原因相同的用户
                $i = 0;
                foreach ($data_order2_new as $k2=>$v2){
                    $data_order_res2['riskResults'][$i] = $v2;
                    $i++;
                }
                $data_order = array_merge($data_order1,$data_order_res2);
                $data_arr['data'] = $data_order;
                $data_arr['tx'] = "202";
                $data_arr['version'] = "v3";
                return json_encode($data_arr);
            }else{
                return $data_order;
            }
        }else{
            if(!empty($data_order1) && !empty($data_order2)){
                $arr = $data_order2['riskResults'];
                $data_order2_new = [];
                $data_order_res2 = [];
                $data = [];
                foreach ($arr as $k=>$v){
                    $data[$v['riskDetail']] = $k;
                }

                foreach ($data as $k1=>$v1){
                    $data_order2_new[$v1] = $arr[$v1];
                }
                //处理拒绝原因相同的用户
                $i = 0;
                foreach ($data_order2_new as $k2=>$v2){
                    $data_order_res2['riskResults'][$i] = $v2;
                    $i++;
                }
                $data_order = array_merge($data_order1,$data_order_res2);
                $data_arr['data'] = $data_order;
                $data_arr['tx'] = "202";
                $data_arr['version'] = "v3";
                return json_encode($data_arr);
            }else{
                return $data_order;
            }
        }
    }
    /**
     * 查询还款记录
     */
    public static function getRepayment($order,$type){
        $res = UserLoanOrderRepayment::find()->where(['order_id'=>$order['id']])->one();
        if($type == 1){
            if(empty($res) && $order['status'] < 0){
                return 203;
            }else if(empty($res) && $order['status'] > 0){
                return 201;
            }else{
                return 202;
            }
        }elseif($type == 2){
            if($res->is_overdue == 1 && $res->status == 4 || $res->status == 4){
                return 303;
            }elseif($res->is_overdue == 1){
                return 302;
            }else{
                return 301;
            }
        }elseif($type == 3){
            if(!empty($res) && isset($res->overdue_day) && isset($res->is_overdue) && $res->is_overdue != 0){
                $M = $res->overdue_day;
                if($M<=30){
                    return 'M1';
                }elseif ($M>=30 && $M <60){
                    return 'M2';
                }elseif ($M>=60 && $M <90){
                    return 'M3';
                }elseif ($M>=90 && $M <120){
                    return 'M4';
                }elseif ($M>=120 && $M <150){
                    return 'M5';
                }elseif ($M>=150 && $M <180){
                    return 'M6';
                }elseif ($M>=180 && $M <210){
                    return 'M6+';
                }
            }else{
                return "";
            }
        }elseif($type == 4){
            return '(0,1000]';
        }
    }
    /**
     * 查询逾期次数
     */
    //overdue_day
    public static function getOverdueDay($order,$type){
        $where = [];
        if($type == 1){
            $where = ['is_overdue'=>1];
        }elseif($type == 2){
            $where = ['>','overdue_day',60];
        }elseif($type == 3){
            $where = ['>','overdue_day',150];
        }
        $res = UserLoanOrderRepayment::find()->where(['order_id'=>$order['id']])
            ->andWhere($where)->count();
        return $res;
    }
    /**
     * @name 查询拒绝原因
     */
    public static function getReason($order){
        $reason = UserOrderLoanCheckLog::find()->where(['order_id'=>$order['id']])->andWhere(['<','after_status',0])->orderBy('id desc')->one();
        $data['message'] ="";
        $data['time'] = "";
        if(!empty($reason)){
            if(in_array($reason->remark,self::$mg_list)){
                $data['message'] = "命中蜜罐黑名单";
                $data['time'] = date('Ym',$reason->created_at);
            }elseif (in_array($reason->remark,self::$jxl_list)){
                $data['message'] = "JXL数据不全";
                $data['time'] = date('Ym',$reason->created_at);
            }elseif (in_array($reason->remark,self::$other_list)){
                $data['message'] ="同盾高风险";
                $data['time'] = date('Ym',$reason->created_at);
            }elseif (in_array($reason->remark,self::$br_list)){
                $data['message'] ="命中百融黑明单";
                $data['time'] = date('Ym',$reason->created_at);
            }elseif (in_array($reason->remark,self::$jxl_error)){
                $data['message'] ="JXL建议拒绝";
                $data['time'] = date('Ym',$reason->created_at);
            }else{
                $data['message'] ="评分过低";
                $data['time'] = date('Ym',$reason->created_at);
            }
        }

        return $data;
    }
    //
    static $mg_list = [
        '手机运营商实名时间<8个月',
        '手机运营商实名时间<6个月',
        '扣款成功,查询操作更新',
        '详单与通讯录匹配较少',
        '存在多笔进行中订单',
        '黑中介分数高',
        '详单前10短号过多',
        '初审转人工全部拒绝',
        '高风险户籍区域',
        '连续静默天数>3',
        '命中禁止项',
        '通讯录异常号码较多',
        '蜜罐近1个月查询次数>30',
        'Undefined index: code',
    ];
    static $jxl_error = [
        '新用户通讯录手机号数量<=35',
        '蜜罐近1个月查询次数>25',
        '蜜罐黑中介分<8',
        '详单超过30分钟人数较少',
        '新用户通讯录手机号数量<38',
        '公积金单位信息不一致',
        '多次采集不到手机联系人信息 拒绝',
        '蜜罐信息获取失败',
        '高风险民族',
        '蜜罐黑中介分<12',
        '手机运营商实名时间<=6个月',
        '通讯录有效号码数量小于30',
        '详单超过10分钟人数较少',
        '新用户手机互通过电话的号码小于等于25',
        '通讯录有效号码数量小于35',
        '手机运营商实名时间<12个月',
        '历史借款订单逾期大于等于7天',
        '未抓取到公积金单位',
    ];
    static $jxl_list = [
        '新用户通讯录手机号数量<=30',
        '新用户通讯录手机号数量<=40',
        '新用户欺诈分>6.9',
        '高风险行业',
        '详单异常号码较多',
        '新用户借款金额大于授信额度445',
        '月均话费小于10元',
        '欺诈分高',
        '无本人照片',
        '本人照片不清晰',
        '新用户通讯录手机号数量<45',
        '蜜罐黑中介分<6',
    ];
    static $br_list = [
        '无特别原因，拒绝放款',
        '手机运营商实名时间<=8个月',
        '本人与身份证不符',
        '蜜罐近1个月查询次数>35',
        '空错停关',
        '公安局',
        '通讯录有效号码数量小于20',
        '未填写单位',
        '非白名单用户直接拒接',
        '未上传身份证',
        '月均话费小于15元',
        '宜信累计逾期次数较多',
        '百融命中次数较多',
        '蜜罐近1个月查询次数>20',
        '宜信近5条记录拒绝率较高',
        '现金白卡黑名单拒接',
    ];
    static $other_list = [
        '0点到6点的订单全部拒绝',
        '新用户欺诈分>7',
        '蜜罐黑中介分<10',
        '连续静默天数>4',
        '蜜罐黑中介分<9',
        '非白名单用户直接拒接-390',
        '新用户通讯录手机号数量<40',
        '新用户通讯录手机号数量<35',
        '采集不到聚信立数据 拒绝',
        '连续关机天数>4',
        '新用户手机运营商未实名',
        '第一，第二联系人都没有过联系',
        '新用户借款年龄小于20或者大于37',
        '详单前10与通讯录匹配较少',
    ];
}