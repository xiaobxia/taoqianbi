<?php

namespace common\services\statistics;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use common\models\CardInfo;
use common\models\CreditJxl;
use common\models\LoanPerson;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\Rule;
use common\models\SolrErrorLog;
use common\models\UserCreditTotal;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\UserQuotaPersonInfo;
use common\models\SolrUpdateLog;
use common\models\SolrInsertLog;
use console\models\MobileCity;

/*
*phaseOfSolr($flag) 选择调用selectSqlToArr的阶段
*createArr() 创建空数组
*insertYesterday($num) 插入昨天数据
*createOrderDetail($order_id) 执行插入动作
*insertOrderDetailToSolr($data) 将数组内容插入到Solr
*updateOrderDetailToSolr($num) 更新昨天以前的数据
*selectSqlToArr($order_id,$data,$flag) 查询MySQL中数据
*actionToInsert($num) 插入数据
*actionToUpdate($num) 更细数据
*insertSolrLog($data,$log_id)  solr插入日志创建和录入
*tryToUpdate($doc_arr)  将内容添加到Solr
*checkInsertSolrLog($update_date,$num) solr插入日志检查今日是否维护
 */
//class OrderDetailService extends Object{
class OrderDetailService extends Component
{//调试时候使用

    private $size = 200;

    /**
     * 创建空数组
     * @return array
     */
    public function createArr(){
        $data = [
            'id'=>'',//solr ID
            //订单信息
            'order_id'=>0,//订单ID
            'borrowing_platform_id'=>0,//借款平台
            'loan_time'=>'',//借款时间
            'loan_money'=>0,//借款金额
            'loan_fee'=>0,//借款费用
            'loan_term'=>0,//借款期限
            'order_sataus'=>0,//当前状态
            //审核信息
            'machine_check_result'=>0,//机审结果
            'machine_check_code'=>'',//机审审核码
            'machine_check_time'=>'',//机审时间
            'trial_check'=>false,//是否初审
            'trial_check_user_name'=>'',//初审人
            'trial_check_result'=>0,//初审结果
            'trial_check_time'=>'',//初审时间
            'trial_check_code'=>'',//初审审核码
            'review_check'=>false,//是否复审
            'review_check_user_name'=>'',//复审人
            'review_check_result'=>0,//复审结果
            'review_check_time'=>'',//复审时间
            'review_check_code'=>'',//复审审核码
            //用户信息
            'user_id'=>0,//用户ID
            'user_name'=>'',//姓名
            'user_id_number'=>'',//身份证号
            'user_sex'=>0,//性别
            'user_birthday'=>'',//出生日期
            'user_marriage'=>0,//婚姻
            'user_mobile'=>'',//手机号
            'user_mobile_operators'=>0,//手机号运营商
            'user_mobile_operators_city'=>0,//手机号运营商城市
            'user_mobile_real_name_status'=>false,//手机号实名
            'user_mobile_real_name_time'=>'',//手机号实名时间
            'user_register_time'=>'',//注册时间
            'user_register_source'=>0,//注册来源
            'user_card_bank_loan'=>'',//用户借款发卡行
            'user_card_bank_no_loan'=>'',//用户借款银行卡卡号
            'user_card_bank_mobile_loan'=>'',//用户借款银行卡预留手机号
            //注册设备信息
            'user_register_device_type'=>'',//设备类型
            'user_register_device_app_version'=>'',//App版本
            'user_register_device_name'=>'',//设备名称
            'user_register_device_os_version'=>'',//OS版本
            //公司信息
            'user_work_address'=>'',//公司地址
            'user_work_name'=>'',//公司名称
            //用户额度信息
            'credit_amount'=>0,    //授信额度
            //还款信息
            'plan_repayment_time'=>'',//应还日期
            'plan_repayment_principal'=>0,//应还本金
            'plan_repayment_late_fee'=>0,//应还滞纳金
            'true_repayment_time'=>'',//实际还款日期
            'true_repayment_money'=>0,//实际还款金额
            'repayment_status'=>0,//还款状态
            'overdue_day'=>0,//逾期天数
            //征信
            'credit_forbid_score'=>0,//禁止项得分
            'credit_anti_fraud_score'=>0,//反欺诈分数
            'credit_grade_score'=>0,//信用评分
        ];
        return $data;
    }

    /**
     * @param $mongo_user_id
     * @return array
     * 从mongoDB获取score值
     */
    public function tryFromMongoGetScore($mongo_user_id){
        $result = [];
        try{
            $result = RuleReportMongo::getScore($mongo_user_id);
        }catch (\Exception $e){
            return $result = ['mongoerr'];
        }
        return $result;
    }

    /**
     * 从mongoDB获取value值
     * @param $mongo_user_id
     * @return array
     */
    public function tryFromMongoGetValue($mongo_user_id){
        $result = [];
        try{
            $result = RuleReportMongo::getValue($mongo_user_id);

        }catch (\Exception $e){
            return $result = ['mongoerr'];
        }
        return $result;
    }

    /**
     * 将内容添加到Solr
     * @return array
     */
    public function tryToUpdate($doc_arr){
        try{
            $client = Yii::$app->solr;
            $update = $client->createUpdate();
            $update->addDocuments($doc_arr);
            $update->addCommit();
            $result = $client->update($update);
            $result_status = $result->getStatus();
            if(0 == $result_status){
                return [
                    'code'=>0,
                ];
            }else{
                return [
                    'code'=>-1,
                    'message'=>'保存失败'
                ];
            }
        }catch (\Exception $e){
            return [
                'code'=>$e->getCode(),
                'message'=>$e->getMessage()
            ];
        }
    }

    /**
     * @param $data
     * @param int $log_id
     * @return mixed
     * solr插入日志创建和录入
     */
    public function insertSolrLog($data,$log_id = 0){
        //有log_id则进行更新操作   无log_id则新建一条记录
        if($log_id&&$log = SolrInsertLog::findOne(['id'=>$log_id])){
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->id;
        }else{
            $log = new SolrInsertLog();
            foreach ($data as $key => $insert_log) {
                $log->$key = $insert_log;
            }
            $log->save();
            return $log->id;
        }
    }

    /**
     * @param $update_date
     * @param $num
     * @return mixed
     * 检查插入动作今天是否执行
     */
    public function checkInsertSolrLog($update_date,$num,$type){
        $log = SolrInsertLog::findBySql('select count(*) as total from `tb_solr_insert_log` where update_date = '.$update_date.' and num = '.$num.' and `type`=\''.$type.'\'');
        return $log->one()->total;
    }

    /**
     * @param $message
     * @param $doc_arr
     * @return array
     * 处理异常过程中成功和失败的数量计算
     */
    public function recordSucc($message,$doc_arr){
        $num_succ = 0;//成功插入数量
        $num_err = 0;//失败数量
        $num = 0;//成功计数
        $flag = 0;
        $str = '';//错误ID
        $err_id = (int)preg_replace('/-.*/','',preg_replace('/[^=]*=/','',$message));
        foreach ($doc_arr as $key => $arr_order_id) {
            $num++;
            if($arr_order_id['order_id']==$err_id){
                $num_succ = $num-1;
                $num_err = count($doc_arr)-$num_succ;
                $flag = 1;
                break;
            }
        }
        if(!$flag){//上面的正则没有成功提取到ID 则将整个$message 保存 并将该区域ID归为错误 供后续分析
            $num_succ = 0;
            $num_err = $this->size;
        }
        unset($key);
        $str = $err_id.'|'.$message.',,';
        return $data = ['succ'=>$num_succ,'err'=>$num_err,'err_message'=>$str,'err_id'=>$err_id];
    }

    /**
     * @param $data
     * @return array
     * 将数组内容装换成对象
     */
    public function insertOrderDetailToSolr($data){
        $client = Yii::$app->solr;
        $doc_arr = [];
        $update = $client->createUpdate();
        //$data 二维数组 下标为order_id
        foreach($data as $order_id => $data_child){
            $doc = $update->createDocument();
            foreach ($data_child as $key_d => $value_d){
                $doc->$key_d = $value_d;
            }
            $doc_arr[] = $doc;
        }
        return $doc_arr;
    }

    /**
     * @param $order_id
     * @param $data_e
     * @param $flag
     * @return array
     * @throws \Exception
     * 查询MySQL中数据
     * $flag 0:插入  1:更新     release时开启额度信息获取,开发环境关闭
     */
    public function selectSqlToArr($order_id,$data_e,$flag){
        /*----------------------------------------------------------------*/
        $user_loan_order_data = [];
        $user_id_arr = [];
        $loan_person_data = [];
        $user_quota_person_info_data = [];
        $creditJxl_data = [];
        $user_detail_data = [];
        $use_credit_total_data = [];
        $user_loan_order_machine_check_log_data = [];
        $user_loan_order_pri_check_log_data = [];
        $user_loan_order_check_log_data = [];
        $user_loan_order_repayment_data = [];
        $card_id_arr = [];
        $card_info_data = [];
        $rule_report_mongo_data = [];
        $user_feature_data = [];
        $user_feature_data_arr = [];
        $data = [];
        //订单表 UserLoanOrder
        $user_loan_order = UserLoanOrder::find()->select('`order_time`,`money_amount`,`counter_fee`,`loan_term`,`status`,`sub_order_type`,`id`,`user_id`')->where(['in','id',$order_id])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_loan_order)) {
            foreach ($user_loan_order as $key => $value_order) {
                $user_loan_order_data[$value_order['id']] = $value_order;//$user_loan_order_data[订单ID]
                $user_id_arr[$value_order['id']] = $value_order['user_id'];
            }
        }
        unset($user_loan_order);
        unset($key);
        unset($value_order);
        //用户表 LoanPerson
        $loan_person = LoanPerson::find()->where(['in','id',$user_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($loan_person)) {
            foreach ($loan_person as $key => $value_loan) {
                if(!empty($value_loan)) {
                    $loan_person_data[$value_loan['id']] = $value_loan;
                }
            }
        }
        unset($loan_person);
        unset($key);
        unset($value_loan);
        //婚否 UserQuotaPersonInfo
        $user_quota_person_info = UserQuotaPersonInfo::find()->where(['in','user_id',$user_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_quota_person_info)) {
            foreach ($user_quota_person_info as $key => $value_quota) {
                if(!empty($value_quota)) {
                    $user_quota_person_info_data[$value_quota['user_id']] = $value_quota;
                }
            }
        }
        unset($user_quota_person_info);
        unset($key);
        unset($value_quota);
        //
        $creditJxl = CreditJxl::find()->where(['in','person_id',$user_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_risk_rd'));
        if(!empty($creditJxl)) {
            foreach ($creditJxl as $key => $value_jxl) {
                if(!empty($value_jxl)) {
                    $creditJxl_data[$value_jxl['person_id']] = $value_jxl;
                }
            }
        }
        unset($creditJxl);
        unset($key);
        unset($value_jxl);
        //公司信息
        $user_detail = UserDetail::find()->where(['in','user_id',$user_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_detail)) {
            foreach ($user_detail as $key => $value_detail) {
                if(!empty($value_detail)) {
                    $user_detail_data[$value_detail['user_id']] = $value_detail;
                }
            }
        }
        unset($user_detail);
        unset($value_detail);
        unset($user_detail);
        //用户额度信息
        $use_credit_total = UserCreditTotal::find()->where(['in','user_id',$user_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($use_credit_total)) {
            foreach ($use_credit_total as $key => $value_total) {
                if(!empty($value_total)) {
                    $use_credit_total_data[$value_total['user_id']] = $value_total;
                }
            }
        }
        unset($use_credit_total);
        unset($key);
        unset($value_total);
        //判断是否机审 auto shell
        $user_loan_order_machine_check_log = UserOrderLoanCheckLog::find()->where(['in','order_id',$order_id,'before_status'=>UserLoanOrder::STATUS_CHECK,'operator_name'=>'auto shell'])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_loan_order_machine_check_log)) {
            foreach ($user_loan_order_machine_check_log as $key => $value_auto) {
                if(!empty($value_auto)) {
                    $user_loan_order_machine_check_log_data[$value_auto['order_id']] = $value_auto;
                }
            }
        }
        unset($user_loan_order_machine_check_log);
        unset($key);
        unset($value_auto);
        //需要初审
        $user_loan_order_pri_check_log = UserOrderLoanCheckLog::find()->where(['in','order_id',$order_id,'before_status'=>UserLoanOrder::STATUS_CHECK])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_loan_order_pri_check_log)) {
            foreach ($user_loan_order_pri_check_log as $key => $value_pri) {
                if(!empty($value_pri)) {
                    $user_loan_order_pri_check_log_data[$value_pri['order_id']] = $value_pri;
                }
            }
        }
        unset($user_loan_order_pri_check_log);
        unset($value_pri);
        unset($user_loan_order_pri_check_log);
        //复审信息
        $user_loan_order_check_log = UserOrderLoanCheckLog::find()->where(['in','order_id',$order_id,'before_status'=>UserLoanOrder::STATUS_REPEAT_TRAIL])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_loan_order_check_log)) {
            foreach ($user_loan_order_check_log as $key => $value_again) {
                if(!empty($value_again)) {
                    $user_loan_order_check_log_data[$value_again['order_id']] = $value_again;
                }
            }
        }
        unset($key);
        unset($value_again);
        //还款信息
        $user_loan_order_repayment = UserLoanOrderRepayment::find()->where(['in','order_id',$order_id])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($user_loan_order_repayment)) {
            foreach ($user_loan_order_repayment as $key => $value_repay) {
                if(!empty($value_repay)) {
                    $user_loan_order_repayment_data[$value_repay['order_id']] = $value_repay;
                    $card_id_arr[$value_repay['order_id']] = $value_repay['card_id'];//$card_id_arr[订单ID] = 放款卡ID
                }
            }
        }
        unset($user_loan_order_repayment);
        unset($key);
        unset($value_repay);
        //放款卡信息
        $card_info = CardInfo::find()->where(['in','id',$card_id_arr])->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        if(!empty($card_info)) {
            foreach ($card_info as $key => $value_card) {
                if(!empty($value_card)) {
                    $card_info_data[$value_card['id']] = $value_card;
                }
            }
        }
        unset($card_info);
        unset($key);
        unset($value_card);
        //征信分 $value_user:$user_id
        foreach($user_id_arr as $key => $value_user){
            $result = $this->tryFromMongoGetScore($value_user);
            if($result == ['mongoerr']||!is_array($result)){
                sleep(10);
                while($this->tryFromMongoGetScore($value_user) == ['mongoerr']||!is_array($this->tryFromMongoGetScore($value_user))){sleep(10);}
                $result = $this->tryFromMongoGetScore($value_user);
                $rule_report_mongo_data[$value_user] = $result;
            }else{
                $rule_report_mongo_data[$value_user] = $result;
            }
        }
        unset($key);
        unset($value_user);
        //征信数据 $value_user:$user_id
        foreach($user_id_arr as $key => $value_user) {
            $result = $this->tryFromMongoGetValue($value_user);
            if($result == ['mongoerr']||!is_array($result)){
                sleep(10);
                while($this->tryFromMongoGetValue($value_user) == ['mongoerr']||!is_array($this->tryFromMongoGetValue($value_user))){sleep(10);}
                $result = $this->tryFromMongoGetValue($value_user);
                $user_feature_data[$value_user] = $result;
            }else{
                $user_feature_data[$value_user] = $result;
            }
        }
        unset($key);
        unset($value_user);
        /*--------------------------------------------------------------------*/
        /*********************************************************************/
        //组合data
        foreach($order_id as $key => $data_order_id){//$order_id 订单ID数组
            //user_id order_id
            $data[$key] = $data_e;
            $user_id = $user_loan_order_data[$data_order_id]['user_id'];//$user_loan_order_data[订单ID][用户ID]
            $order_id = $data_order_id;//订单ID
            $data[$key]['order_id'] = $user_loan_order_data[$order_id]['id'];
            $borrowing_platform_id = $user_loan_order_data[$order_id]['sub_order_type'];
            $data[$key]['borrowing_platform_id'] = $borrowing_platform_id;
            $solr_id = $data[$key]['order_id']."-".$data[$key]['borrowing_platform_id']."-".$user_loan_order_data[$order_id]['user_id'];
            $data[$key]['id'] = $solr_id;
            if(isset($user_loan_order_data[$order_id]['order_time'])) {
                $loan_time = $user_loan_order_data[$order_id]['order_time'];
                $loan_time = empty($loan_time) ? "" : date("Y-m-d H:i:s", $loan_time);
                $data[$key]['loan_time'] = $loan_time;
            }
            if(isset($user_loan_order_data[$order_id]['money_amount'])) {
                $loan_money = $user_loan_order_data[$order_id]['money_amount'];
                $data[$key]['loan_money'] = $loan_money;
            }
            if(isset($user_loan_order_data[$order_id]['counter_fee'])) {
                $loan_fee = $user_loan_order_data[$order_id]['counter_fee'];
                $data[$key]['loan_fee'] = $loan_fee;
            }
            if(isset($user_loan_order_data[$order_id]['loan_term'])) {
                $loan_term = $user_loan_order_data[$order_id]['loan_term'];
                $data[$key]['loan_term'] = $loan_term;
            }
            if(isset($user_loan_order_data[$order_id]['status'])) {
                $order_sataus = $user_loan_order_data[$order_id]['status'];
                $data[$key]['order_sataus'] = $order_sataus;
            }

            if(isset($loan_person_data[$user_id])&&(!empty($loan_person_data[$user_id]))) {
                if(isset($loan_person_data[$user_id]['id'])) {
                    $data[$key]['user_id'] = $loan_person_data[$user_id]['id'];
                }
                if(isset($loan_person_data[$user_id]['name'])) {
                    $user_name = $loan_person_data[$user_id]['name'];
                    if(!empty($user_name)) {
                        $data[$key]['user_name'] = $user_name;
                    }else{
                        $data[$key]['user_name'] = "name";
                    }
                }else{
                    $data[$key]['user_name'] = "name";
                }
                if(isset($loan_person_data[$user_id]['id_number'])) {
                    $user_id_number = $loan_person_data[$user_id]['id_number'];
                    if(!empty($user_id_number)){
                        $data[$key]['user_id_number'] = $user_id_number;
                    }else{
                        $data[$key]['user_id_number'] = "user_id_number";
                    }
                }else{
                    $data[$key]['user_id_number'] = "user_id_number";
                }
                if(isset($loan_person_data[$user_id]['property'])) {
                    $user_sex = $loan_person_data[$user_id]['property'];
                    if (LoanPerson::$sexes[LoanPerson::SEX_MALE] == $user_sex) {
                        $user_sex = LoanPerson::SEX_MALE;
                    } else if (LoanPerson::$sexes[LoanPerson::SEX_FEMALE] == $user_sex) {
                        $user_sex = LoanPerson::SEX_FEMALE;
                    } else {
                        $user_sex = 0;
                    }
                    $data[$key]['user_sex'] = $user_sex;
                }
                if(isset($loan_person_data[$user_id]['birthday'])) {
                    $user_birthday = $loan_person_data[$user_id]['birthday'];
                    $user_birthday = empty($user_birthday) ? "" : date("Y-m-d", $user_birthday);
                    $data[$key]['user_birthday'] = $user_birthday;
                }
                if (isset($user_quota_person_info_data[$user_id])) {
                    $data[$key]['user_marriage'] = $user_quota_person_info_data[$user_id]['marriage'];
                }
                if(isset($loan_person_data[$user_id]['phone'])) {
                    $user_mobile = $loan_person_data[$user_id]['phone'];
                    $data[$key]['user_mobile'] = $user_mobile;
                }
                if(isset($loan_person_data[$user_id]['source_id'])) {
                    $user_register_source = $loan_person_data[$user_id]['source_id'];
                    $data[$key]['user_register_source'] = $user_register_source;
                }
                if(isset($loan_person_data[$user_id]['created_at'])) {
                    $user_register_time = $loan_person_data[$user_id]['created_at'];
                    $user_register_time = empty($user_register_time) ? "" : date('Y-m-d H:i:s', $user_register_time);
                    $data[$key]['user_register_time'] = $user_register_time;
                }
            }
            $mobile_detail = [];

            if(isset($creditJxl_data[$user_id])){
                $creditJxl = $creditJxl_data[$user_id];
                if(!is_null($creditJxl) && !empty($creditJxl['data'])){
                    $json_data = json_decode($creditJxl['data'],true);
                    if($json_data){
                        if(isset($json_data['data_source'])){
                            $mobile_detail = $json_data['data_source'];
                        } else if(isset($json_data['application_check']['2']['check_points'])){
                            $mobile_detail = $json_data['application_check']['2']['check_points'];
                        }
                    }
                }
            }


            if(isset($json_data['application_check']['2']['check_points'])){
                if($mobile_detail['reliability'] == '实名认证' && isset($mobile_detail['check_name']) && isset($mobile_detail['check_idcard'])){
                    $check_name = strpos($mobile_detail['check_name'], '匹配成功')>-1?1:0;
                    $check_idcard = (strpos($mobile_detail['check_idcard'], '匹配失败')>-1 || strpos($mobile_detail['check_idcard'], '不匹配')>-1)?0:1;
                    if($check_name == 1 && $check_idcard == 1){
                        $data[$key]['user_mobile_real_name_status'] = true;
                    }
                }
                if(isset($mobile_detail['reg_time']) && !empty($mobile_detail['reg_time'])){
                    $data[$key]['user_mobile_real_name_time'] = $mobile_detail['binding_time'];
                }
                if(isset($mobile_detail['website'])&&strpos('联通', $mobile_detail['website'])){
                    $data[$key]['user_mobile_operators'] = MobileCity::CHINA_UNICOM;

                }else if(isset($mobile_detail['website'])&&strpos('移动', $mobile_detail['website'])){
                    $data['user_mobile_operators'] = MobileCity::CHINA_MOBILE;
                    if(isset(MobileCity::$china_mobile_sub_name[$mobile_detail['website']])){
                        $data[$key]['user_mobile_operators_city'] = MobileCity::$china_mobile_sub_name[$mobile_detail['website']];
                    }
                }else if(isset($mobile_detail['website'])&&strpos('电信', $mobile_detail['website'])){
                    $data[$key]['user_mobile_operators'] = MobileCity::CHINA_TELECOMMUNICATIONS;
                    if(isset(MobileCity::$china_telecommunications_sub_name[$mobile_detail['website']])){
                        $data[$key]['user_mobile_operators_city'] = MobileCity::$china_telecommunications_sub_name[$mobile_detail['website']];
                    }
                }
            } else

                if(!empty($mobile_detail)){
                    if(isset($mobile_detail['reliability'])){
                        if("实名认证" == $mobile_detail['reliability']){
                            $data[$key]['user_mobile_real_name_status'] = true;
                        }
                    }
                    if(isset($mobile_detail['binding_time'])){
                        if(!empty($mobile_detail['binding_time'])){
                            $data[$key]['user_mobile_real_name_time'] = $mobile_detail['binding_time'];
                        }
                    }

                    if(isset($mobile_detail['name'])&&strpos('联通', $mobile_detail['name'])){
                        $data[$key]['user_mobile_operators'] = MobileCity::CHINA_UNICOM;

                    }else if(isset($mobile_detail['name'])&&strpos('移动', $mobile_detail['name'])){
                        $data['user_mobile_operators'] = MobileCity::CHINA_MOBILE;
                        if(isset(MobileCity::$china_mobile_sub_name[$mobile_detail['name']])){
                            $data[$key]['user_mobile_operators_city'] = MobileCity::$china_mobile_sub_name[$mobile_detail['name']];
                        }
                    }else if(isset($mobile_detail['name'])&&strpos('电信', $mobile_detail['name'])){
                        $data[$key]['user_mobile_operators'] = MobileCity::CHINA_TELECOMMUNICATIONS;
                        if(isset(MobileCity::$china_telecommunications_sub_name[$mobile_detail['name']])){
                            $data[$key]['user_mobile_operators_city'] = MobileCity::$china_telecommunications_sub_name[$mobile_detail['name']];
                        }
                    }
                }

            //公司信息
            if(isset($user_detail_data[$user_id])){
                $user_detail =  $user_detail_data[$user_id];
                if($user_detail){
                    if(isset($user_detail['company_name'])) {
                        $user_work_name = $user_detail['company_name'];
                        $data[$key]['user_work_name'] = $user_work_name;
                    }
                    if(isset($user_detail['company_address'])) {
                        $user_work_address = $user_detail['company_address'];
                        $data[$key]['user_work_address'] = $user_work_address;
                    }
                    //注册设备信息
                    if(isset($user_detail['reg_client_type'])) {
                        $user_register_device_type = $user_detail['reg_client_type'];
                        $data[$key]['user_register_device_type'] = $user_register_device_type;
                    }
                    if(isset($user_detail['reg_app_version'])) {
                        $user_register_device_app_version = $user_detail['reg_app_version'];
                        $data[$key]['user_register_device_app_version'] = $user_register_device_app_version;
                    }
                    if(isset($user_detail['reg_device_name'])) {
                        $user_register_device_name = $user_detail['reg_device_name'];
                        $data[$key]['user_register_device_name'] = $user_register_device_name;
                    }
                    if(isset($user_detail['reg_os_version'])) {
                        $user_register_device_os_version = $user_detail['reg_os_version'];
                        $data[$key]['user_register_device_os_version'] = $user_register_device_os_version;
                    }
                }
            }

            //用户额度信息

            if(isset($use_credit_total_data[$user_id])&&(!empty($use_credit_total_data[$user_id]))){
                $use_credit_total = $use_credit_total_data[$user_id];
                if(false == $use_credit_total){
                    throw new \Exception('用户额度信息获取失败');
                }
                if(isset($use_credit_total['amount'])) {
                    $credit_amount = $use_credit_total['amount'];
                    $data[$key]['credit_amount'] = $credit_amount;
                }
            }

            //判断是否机审 auto shell
            if(isset($user_loan_order_machine_check_log_data[$order_id])&&(!empty($user_loan_order_machine_check_log_data[$order_id]))){
                $user_loan_order_machine_check_log = $user_loan_order_machine_check_log_data[$order_id];
                //机审数据
                if(isset($user_loan_order_machine_check_log['after_status'])) {
                    $after_status = $user_loan_order_machine_check_log['after_status'];
                    $machine_check_result = ($after_status > 0) ? 1 : -1;
                    $data[$key]['machine_check_result'] = $machine_check_result;
                }
                if(isset($user_loan_order_machine_check_log['head_code'])) {
                    $machine_check_code = $user_loan_order_machine_check_log['head_code'] . '-' . $user_loan_order_machine_check_log['back_code'];
                    $data[$key]['machine_check_code'] = $machine_check_code;
                    $machine_check_time = date('Y-m-d H:i:s', $user_loan_order_machine_check_log['created_at']);
                    $data[$key]['machine_check_time'] = $machine_check_time;
                }
                //不需要初审
            }else{
                //需要初审
                if(isset($user_loan_order_pri_check_log_data[$order_id])&&(!empty($user_loan_order_pri_check_log_data[$order_id]))){
                    $user_loan_order_pri_check_log = $user_loan_order_pri_check_log_data[$order_id];
                    $data[$key]['trial_check'] = true;
                    $trial_check_user_name = $user_loan_order_pri_check_log['operator_name'];
                    $data[$key]['trial_check_user_name'] = $trial_check_user_name;
                    $after_status = $user_loan_order_pri_check_log['after_status'];
                    $trial_check_result = ($after_status>0)?1:-1;
                    $data[$key]['trial_check_result'] = $trial_check_result;
                    $trial_check_time = date('Y-m-d H:i:s',$user_loan_order_pri_check_log['created_at']);
                    $data[$key]['trial_check_time'] = $trial_check_time;
                    $trial_check_code = $user_loan_order_pri_check_log['head_code'].'-'.$user_loan_order_pri_check_log['back_code'];
                    $data[$key]['trial_check_code'] = $trial_check_code;
                }
            }
            //复审信息
            if(isset($user_loan_order_check_log_data[$order_id])&&(!empty($user_loan_order_check_log_data[$order_id]))){
                $user_loan_order_check_log = $user_loan_order_check_log_data[$order_id];
                $data[$key]['review_check'] = true;
                $review_check_user_name = $user_loan_order_check_log['operator_name'];
                $data[$key]['review_check_user_name'] = $review_check_user_name;
                $after_status = $user_loan_order_check_log['after_status'];
                $review_check_result = ($after_status>0)?1:-1;
                $data[$key]['review_check_result'] = $review_check_result;
                $review_check_time = date('Y-m-d H:i:s',$user_loan_order_check_log['created_at']);
                $data[$key]['review_check_time'] = $review_check_time;
                $review_check_code = $user_loan_order_check_log['head_code'].'-'.$user_loan_order_check_log['back_code'];
                $data[$key]['review_check_code'] = $review_check_code;
            }
            //还款信息
            if(isset($user_loan_order_repayment_data[$order_id])&&(!empty($user_loan_order_repayment_data[$order_id]))){
                //表示已经生成还款计划
                $user_loan_order_repayment = $user_loan_order_repayment_data[$order_id];
                $plan_repayment_time = date('Y-m-d H:i:s',$user_loan_order_repayment['plan_repayment_time']);
                $data[$key]['plan_repayment_time'] = $plan_repayment_time;
                $plan_repayment_principal= $user_loan_order_repayment['principal'];
                $data[$key]['plan_repayment_principal'] = $plan_repayment_principal;
                $plan_repayment_late_fee = $user_loan_order_repayment['late_fee'];
                $data[$key]['plan_repayment_late_fee'] = $plan_repayment_late_fee;
                if($user_loan_order_repayment['true_repayment_time']>0) {
                    $true_repayment_time = date('Y-m-d H:i:s', $user_loan_order_repayment['true_repayment_time']);
                    $data[$key]['true_repayment_time'] = $true_repayment_time;
                }
                $true_repayment_money = $user_loan_order_repayment['true_total_money'];
                $data[$key]['true_repayment_money'] = $true_repayment_money;
                $repayment_status = $user_loan_order_repayment['status'];
                $data[$key]['repayment_status'] = $repayment_status;
                $overdue_day = $user_loan_order_repayment['overdue_day'];
                $data[$key]['overdue_day'] = $overdue_day;

                //放款卡信息
                if(isset($card_id_arr[$order_id])&&(!empty($card_id_arr[$order_id]))){
                    $card_id =  $card_id_arr[$order_id];
                    if(isset($card_info_data[$card_id])&&(!empty($card_info_data[$card_id]))) {
                        $card_info = $card_info_data[$card_id];
                        if ($card_info) {
                            $user_card_bank_loan = $card_info['bank_name'];
                            $data[$key]['user_card_bank_loan'] = $user_card_bank_loan;
                            $user_card_bank_no_loan = $card_info['card_no'];
                            $data[$key]['user_card_bank_no_loan'] = $user_card_bank_no_loan;
                            $user_card_bank_mobile_loan = $card_info['phone'];
                            $data[$key]['user_card_bank_mobile_loan'] = $user_card_bank_mobile_loan;
                        }
                    }
                }
            }
            //征信分
            if(isset($rule_report_mongo_data[$user_id])&&(!empty($rule_report_mongo_data[$user_id]))){
                $rule_report_mongo = $rule_report_mongo_data[$user_id];
                if(isset($rule_report_mongo[0])&&!empty($rule_report_mongo[0])){
                    $data[$key]['credit_forbid_score'] = $rule_report_mongo[0];
                }
                if(isset($rule_report_mongo[1])&&!empty($rule_report_mongo[1])){
                    $data[$key]['credit_grade_score'] = $rule_report_mongo[1];
                }
                if(isset($rule_report_mongo[2])&&!empty($rule_report_mongo[2])){
                    $data[$key]['credit_anti_fraud_score'] = $rule_report_mongo[2];
                }
            }
            //征信数据
            if(isset($user_feature_data[$user_id])&&(!empty($user_feature_data[$user_id]))){
                $user_feature_data_arr = $user_feature_data[$user_id];
                $arr_user_feature['user_feature'] = $user_feature_data_arr;
                if(is_array($arr_user_feature['user_feature'])){//将user_feature数组拆解
                    foreach($arr_user_feature['user_feature'] as $user_feature => $item){
                        $user_feature_ = 'user_feature_'.$user_feature;
                        if(!is_array($item['value'])) {
                            $data[$key][$user_feature_] = (string)$item['value'];
                        }else{
                            $data[$key][$user_feature_] = json_encode($item['value']);
                        }
                    }
                }
            }
        }
        /*********************************************************************/
        return $data;
    }

    /**
     * @param $update_result
     * @param $doc_arr
     * @param $fail_result
     * @param $result_total
     * @param $result_succ
     * @param $result_fau
     * @param $log_id
     * @return array
     * 跳过错误记录函数
     */
    public function skipErrOrderId($update_result,$doc_arr,$fail_result,$result_succ,$result_fau,$log_id){
        $str = '';
        $arr = [];
        while(($update_result['code'] != 0)&&($doc_arr!=[])){
            $doc_arr_new = [];
            if($fail_result['succ']!=0) {
                $un_num = $fail_result['succ'];
            }else{
                $un_num = 0;
            }
            for($y = $un_num+1;$y<count($doc_arr);$y++){
                $doc_arr_new[] = $doc_arr[$y];
            }
            if(!empty($doc_arr_new)){
                $update_result = $this->tryToUpdate($doc_arr_new);
                if(!empty($update_result)&&isset($update_result['code'])){
                    if($update_result['code'] == 0){
                        $result_succ+=count($doc_arr_new);
                        $result_fau-=count($doc_arr_new);
                    }else{
                        if(isset($update_result['message'])) {
                            $fail_result = $this->recordSucc($update_result['message'], $doc_arr_new);
                            if(!empty($fail_result)) {
                                $result_fau -= $fail_result['succ'];
                                $result_succ += $fail_result['succ'];
                                $str = $fail_result['err_message'];
                            }
                            $log_last = SolrInsertLog::findOne(['id' => $log_id]);
                            $str = $log_last->fail_id . $str;
                            $data = [
                                'fail_id' => $str,
                            ];
                            $log_id = $this->insertSolrLog($data, $log_id);
                            $str = '';
                        }
                    }
                }
            }
            $doc_arr = $doc_arr_new;
        }
        $arr['result_succ'] = $result_succ;
        $arr['result_fau'] = $result_fau;
        $arr['log_id'] = $log_id;
        return $arr;
    }
    /**
     * 选择调用selectSqlToArr的阶段
     * @param  $order_id  array 用户ID
     * @param  $data array
     * @param  $flag  int   0 || 1   0:插入  1:更新
     * @return array
     */
    public function phaseOfSolr($order_id,$data,$flag = 0){
        if($flag){
            return $this->selectSqlToArr($order_id,$data,$flag);
        }else{
            return $this->selectSqlToArr($order_id,$data,$flag);
        }
    }

    /**
     * 创建所要插入的内容
     * @param  $order_arr   array  用户ID
     * @return array
     */
    public function createOrderDetail($order_arr){
        //创建数组并从数据库中获取数据
        $data = $this->phaseOfSolr($order_arr,$this->createArr(),0);
        //$data 二维数组 下标为order_id
        //将数组内容插入到Solr
        if($data!=''){
            return $doc_arr = self::insertOrderDetailToSolr($data);
        }else{return $doc_arr = [];}
    }

    /**
     * 插入昨天数据
     * 开发环境下关闭维护检测
     * @param  $num int 离散参数
     * @return array
     */
    public function insertYesterday($num,$again = 0){
        $s_start = time();//函数起始时间
        $today = date('Ymd');
        //if($this->checkInsertSolrLog($today,$num,'OrderInsert')){return;}
        $log_id = 0;//日志ID
        //起始时间
        $t1 = strtotime(date('Y-m-d',strtotime('-53 day'))." 00:00:00");
        //结束时间
        $t2 = strtotime(date('Y-m-d',strtotime('-48 day'))." 00:00:00");
        $start = 0;
        $size = 300;//分页量
        //$last_order = UserLoanOrder::find()->where("created_at>={$t1} and created_at<={$t2}")->one();isset($last_order->id)
        if(1){
            $result_succ=0;$result_fau=0;$result_mis=0;$result_err=0;$result_total=0;$flag=2;$str='';//维护统计变量集合
            //$data_total = UserLoanOrder::find()->select("id")->where("id%10={$num} and created_at<={$t2} and created_at>={$t1}")->orderBy("id asc")->count();
            $data_total = UserLoanOrder::find()->select("id")->where("id%10={$num} and created_at>={$t1} and created_at<={$t2}")->orderBy("id desc")->count("id",Yii::$app->get('db_kdkj_rd'));
            //创建本次插入日志记录和填入的部分数据
            $data_insert = [
                'update_date'=>$today,
                'begin_at'=>$s_start,
                'total'=>$data_total,
                'num'=>$num,
                'type'=>'OrderInsert'
            ];$doc_total = 0;
            if(!$again) {
                $log_id = $this->insertSolrLog($data_insert, $log_id);
            }
            $size_flag = 0;
            while($start<$data_total){
                try{
                    $doc_arr = [];
                    //$data = UserLoanOrder::find()->where("id%10={$num} and created_at<={$t2} and created_at>={$t1}")->orderBy("id desc")->limit($size)->offset($start)->all();
                    $data = UserLoanOrder::find()->select('id')->where("id%10={$num} and created_at>={$t1} and created_at<={$t2}")->orderBy("id desc")->limit($size)->offset($start)->all(Yii::$app->get('db_kdkj_rd'));
                    if(!empty($data)) {
                        foreach ($data as $key => $order_id_sql) {
                            $data_arr[] = $order_id_sql->id;
                        }
                    }//分页id集合 $data_arr
                    if(isset($data_arr)){
                        $doc_arr = $this->createOrderDetail($data_arr,$log_id);
                        $data_arr = [];$doc_total+=count($doc_arr);
                    }
                    //插入到Solr中
                    if(!empty($doc_arr)){//成功从数据库中提取到关系数据
                        $update_result = $this->tryToUpdate($doc_arr);
                        //统计返回结果
                        if(!empty($update_result)&&isset($update_result['code'])){
                            if($update_result['code'] == 0){
                                $result_succ+=count($doc_arr);
                            }else{
                                if(isset($update_result['message'])) {
                                    $fail_result = $this->recordSucc($update_result['message'], $doc_arr);
                                    if(!empty($fail_result)) {
                                        $str = $fail_result['err_message'];
                                        $str_id = $fail_result['err_id'];
                                        if($str>100){
                                            $result_fau += $fail_result['err'];
                                            $result_succ += $fail_result['succ'];
                                        }else{
                                            $times = 0;
                                            while($str_id<100&&$str_id!=''&&$times<10){//没有获取到订单ID 说明遇到了其他内容 等待一段时间后重新插入该组数据
                                                sleep(5);
                                                try{
                                                    $times++;
                                                    //echo 'sleep'.$times;
                                                    $update_result = $this->tryToUpdate($doc_arr);
                                                    if($update_result['code'] == 0){
                                                        $result_succ+=count($doc_arr);
                                                        $str_id = '';
                                                        $str = '';
                                                    }else{
                                                        if(isset($update_result['message'])) {
                                                            $fail_result = $this->recordSucc($update_result['message'], $doc_arr);
                                                            if(!empty($fail_result)){
                                                                $str_id = $fail_result['err_id'];
                                                                $str = $fail_result['err_message'];
                                                                if($str_id>100){
                                                                    $result_fau += $fail_result['err'];
                                                                    $result_succ += $fail_result['succ'];
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }catch (\Exception $e){
                                                    $str_id = 1;
                                                    continue;
                                                }
                                            }
                                        }
                                    }
                                    $log_last = SolrInsertLog::findOne(['id' => $log_id]);
                                    $str = $log_last->fail_id . $str;
                                    $data = [
                                        'fail_id' => $str,
                                    ];
                                    if(!$again) {
                                        $log_id = $this->insertSolrLog($data, $log_id);
                                    }
                                    $str = '';
                                    //
                                    $arr_skip = $this->skipErrOrderId($update_result,$doc_arr,$fail_result,$result_succ,$result_fau,$log_id);
                                    $result_succ = $arr_skip['result_succ'];
                                    $result_fau = $arr_skip['result_fau'];
                                    $log_id = $arr_skip['log_id'];
                                    //
                                    $data = [];
                                }
                            }
                            $result_total+=count($doc_arr);
                        }
                    }
                    if(($result_total==($start+$size))||($size_flag>10)||($result_total == $data_total)){
                        $start = $start+$size;
                        $size_flag = 0;
                    }else{
                        $size_flag++;
                    }
                    var_dump($start);
                }catch (\Exception $e){
                    file_put_contents('/tmp/lfj/err_value_log.txt',$e->getTraceAsString());
                    var_dump($e->getMessage());
                    sleep(10);
                }

            }
            //结束时间
            $s1 = time();
            $flag = 1;
            /*echo "维护完成 共用时".($s1-$s_start)."秒 共维护".$result_total.'条';
            echo "成功".$result_succ.'条;失败'.$result_fau.'条;用户不存在'.$result_mis.'条;添加失败'.$result_err.'条';*/
            $result_fau = SolrInsertLog::findOne(['id'=>$log_id])->fail+$result_fau;
            $data_insert = [
                'finish_at'=>$s1,
                'success'=>$result_succ,
                'fail'=>$result_fau,
                'flag'=>$flag,
            ];
            if(!$again) {
                $this->insertSolrLog($data_insert, $log_id);
            }
        }else{/*echo "无记录增加";*/
            $s1 = time();
            $data_insert = [
                'update_date'=>$today,
                'begin_at'=>$s_start,
                'finish_at'=>$s1,
                'flag'=>1,
                'num'=>$num
            ];
            if(!$again) {
                $this->insertSolrLog($data_insert, $log_id);
            }
        }
        return true;
    }

    /**
     * @param int $num
     * 更新昨天以前的数据
     * 开发环境下关闭维护检测
     */
    public function updateOrderDetailToSolr($num = 0){
        $s = time();//程序开始时间
        $log_id = 0;
        $t_client = Yii::$app->solr;
        $today = date("Ymd");
        //if($this->checkInsertSolrLog($today,$num,'OrderUpdate')){return;}
        $f_str = '(!order_sataus:(\-3) AND !order_sataus:6)';
        $t_select = [
            'query'=>$f_str,
            'fields'=>'id,order_id,user_id,order_sataus,loan_time',
            'start'=>0,
            'rows'=>1,
            'sort'=>['order_id'=>'desc'],
        ];
        $t_query = $t_client->createSelect($t_select);
        $data = $t_client->select($t_query);
        //全Solr中sataus不等于6的总数
        $total = $data->getNumFound();
        $data = [];//清内存
        //创建本次更新日志记录和填入的部分数据
        $log_data = [
            'update_date'=>$today,
            'begin_at'=>$s,
            'total'=>$total,
            'num'=>$num,
            'type'=>'OrderUpdate'
        ];
        $log_id = $this->insertSolrLog($log_data,$log_id);
        $result_succ=0;$result_fau=0;$result_mis=0;$result_err=0;$result_total=0;$str='';$flag = 2;
        //有数量再处理
        if($total){
            $page_size = 2000;//每页显示记录数
            $list_start = 0;//分页起始记录位置(下标)
            $doc_arr = [];
            $data_key = [];
            //遍历分页内容
            while($list_start<$total){
                try {
                    $data_key = [];//用于存放从solr中遍历出来的用户ID和订单ID以及solrID
                    $select = [
                        'query' => $f_str,
                        'fields' => 'order_id,loan_time',
                        'start' => $list_start,
                        'rows' => $page_size,
                        'sort' => ['order_id' => 'desc'],
                    ];
                    $client = Yii::$app->solr;
                    $query = $client->createSelect($select);
                    $data = $client->select($query);
                    $list_start = $list_start + $page_size;//更新$list_start
                    //var_dump($list_start);
                    $doc_s = $data->getDocuments();
                    //遍历solr数据
                    foreach ($doc_s as $key => $value) {
                        $arr = $value->getfields();
                        //获取orderID(订单ID) ID(SolrID) order_sataus(订单状态) userID(用户ID) created_at订单创建时间
                        if (isset($arr['order_id'])&& isset($arr['loan_time'])) {
                            $preg_loan_time = preg_replace('/(\D)/', '', $arr['loan_time']);
                            $preg_loan_time = substr($preg_loan_time, 0, strlen($preg_loan_time) - 6);
                            if ($preg_loan_time < $today) {
                                $data_key[] = [
                                    'order_id' => $arr['order_id'],
                                ];
                            }
                        }
                    }
                    //清内存
                    $arr = [];$doc_data = [];$doc_s = [];$data = [];
                    //有满足条件的数据则开始处理
                    if (!empty($data_key)) {
                        $solr_id_data = [];
                        //更新部分begin
                        //填充数组
                        $data_demo = $this->createArr();
                        foreach ($data_key as $key => $value) {
                            if ($value['order_id'] % 10 == $num) {
                                $solr_id_data[] = $value['order_id'];
                            }
                        }//foreach结束
                        //清内存
                        $data_key = [];
                        //对比数据库信息
                        $data = $this->phaseOfSolr($solr_id_data, $data_demo, 1);
                        //把$data数组 转换成对象
                        $solr_id_data = [];
                        $doc_arr = [];
                        $update_doc = $client->createUpdate();
                        foreach ($data as $child_key => $data_child) {
                            $doc = $update_doc->createDocument();
                            foreach ($data_child as $k => $val) {
                                $doc->$k = $val;
                            }
                            $doc_arr[] = $doc;
                        }
                        //尝试去更新数据到Solr
                        if ($doc_arr) {
                            $update_result = $this->tryToUpdate($doc_arr);
                            if (!empty($update_result) && isset($update_result['code'])) {
                                $result_total += count($doc_arr);
                                if ($update_result['code'] == 0) {
                                    $result_succ += count($doc_arr);
                                } else {
                                    if (isset($update_result['message'])) {
                                        $arr_skip = [];
                                        $fail_result = $this->recordSucc($update_result['message'], $doc_arr);
                                        if (!empty($fail_result)) {
                                            $str = $fail_result['err_message'];
                                            $str_id = $fail_result['err_id'];
                                            if ($str > 100) {
                                                $result_fau += $fail_result['err'];
                                                $result_succ += $fail_result['succ'];
                                            } else {
                                                $times = 0;
                                                while ($str_id < 100 && $str_id != '' && $times < 10) {//没有获取到订单ID 说明遇到了其他内容 等待一段时间后重新插入该组数据
                                                    sleep(5);
                                                    try {
                                                        $times++;
                                                        echo 'sleep' . $times;
                                                        $update_result = $this->tryToUpdate($doc_arr);
                                                        if ($update_result['code'] == 0) {
                                                            $result_succ += count($doc_arr);
                                                            $str_id = '';
                                                            $str = '';
                                                            break;
                                                        } else {
                                                            if (isset($update_result['message'])) {
                                                                $fail_result = $this->recordSucc($update_result['message'], $doc_arr);
                                                                if (!empty($fail_result)) {
                                                                    $str_id = $fail_result['err_id'];
                                                                    $str = $fail_result['err_message'];
                                                                    if ($str_id > 100) {
                                                                        $result_fau += $fail_result['err'];
                                                                        $result_succ += $fail_result['succ'];
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        $str_id = 1;
                                                        continue;
                                                    }
                                                }
                                            }
                                        }
                                        $log_last = SolrInsertLog::findOne(['id' => $log_id]);
                                        $str = $log_last->fail_id . $str;
                                        $data = [
                                            'fail_id' => $str,
                                        ];
                                        $log_id = $this->insertSolrLog($data, $log_id);
                                        $str = '';
                                        //
                                        $arr_skip = $this->skipErrOrderId($update_result, $doc_arr, $fail_result, $result_succ, $result_fau, $log_id);
                                        $result_succ = $arr_skip['result_succ'];
                                        $result_fau = $arr_skip['result_fau'];
                                        $log_id = $arr_skip['log_id'];
                                        //
                                        $data = [];
                                    }
                                }
                            }
                            $doc_arr = [];
                        }
                    }//更新部分end
                }
                catch(\Exception $e){
                    //file_put_contents('/tmp/lfj/err_update_log.txt',$e->getTraceAsString());
                    //file_put_contents('/tmp/lfj/err_update_log.txt',json_encode(($doc_arr)),FILE_APPEND);
                    //var_dump($e->getMessage());
                    sleep(10);
                    //exit;
                }
            }//while 分页循环
        }
        $flag = 1;
        $s1 = time();//程序结束时间
        /*echo "维护完成 共用时".($s1-$s).'秒;用维护'.$result_total.'条数据;成功'.$result_succ.'条;失败'.$result_fau.'条';*/
        //更新本次更新日志记录和补全数据
        $log_data = [
            'finish_at'=>$s1,
            'success'=>$result_succ,
            'fail'=>$result_fau,
            'flag'=>$flag,
        ];
        $this->insertSolrLog($log_data,$log_id);
    }

    /**
     * 更新数据
     * @param  $num  int 离散参数
     */
    public function actionToUpdate($num = 0){
        $this->updateOrderDetailToSolr($num);//更新昨天以前的数据
    }

    /**
     * 插入数据
     * @param  $num  int 离散参数
     */
    public function actionToInsert($num = 0){
        if($this->insertYesterday($num)){//更新昨天以前的数据
            $this->insertYesterday($num);
        }
    }


    /**
     * @param $num
     * @return array|bool
     * @throws yii\base\InvalidConfigException
     * 检查插入的数据总数是否相同
     */
    public function checkInsertRecord($num){
        //起始时间
        $t1 = strtotime(date('Y-m-d',strtotime('-53 day'))." 00:00:00");
        //结束时间
        $t2 = strtotime(date('Y-m-d',strtotime('-48 day'))." 00:00:00");
        $solr_t1 = date('Y-m-d',strtotime('-53 day'))."T00:00:00Z";
        $solr_t2 = date('Y-m-d',strtotime('-48 day'))."T00:00:00Z";
        $data_total = UserLoanOrder::find()->select("id")->where("created_at>={$t1} and created_at<={$t2}")->orderBy("id desc")->count('id',Yii::$app->get('db_kdkj_rd'));
        $t_client = Yii::$app->solr;
        $f_str = 'loan_time:['.$solr_t1.' TO '.$solr_t2.']';
        $t_select = [
            'query'=>$f_str,
            'fields'=>'order_id',
            'start'=>0,
            'rows'=>1,
            'sort'=>['order_id'=>'desc'],
        ];
        $query = $t_client->createSelect($t_select);
        $data = $t_client->select($query);
        //Solr查询总数
        $total = $data->getNumFound();
        if($total!=(int)$data_total){//总数不等
            $id_start = 0;
            $id_size = 1000;
            $arr = [];
            while($total>$id_start){
                $t_select = [
                    'query'=>$f_str,
                    'fields'=>'order_id',
                    'start'=>$id_start,
                    'rows'=>$id_size,
                    'sort'=>['order_id'=>'desc'],
                ];
                $query = $t_client->createSelect($t_select);
                $data = $t_client->select($query);
                $doc_s = $data->getDocuments();
                foreach ($doc_s as $key => $value) {
                    $value_arr = $value->getfields();
                    $arr[] = $value_arr['order_id'];
                }
                $id_start+=$id_size;
            }
            $doc_total = 0;
            $mis_total = UserLoanOrder::find()->select("id")->where(["not in","id",$arr])->andWhere("created_at>={$t1} and created_at<={$t2}")->count('id',Yii::$app->get('db_kdkj_rd'));
            $mis_start = 0;
            $mis_size = 300;
            //var_dump($mis_total);
            $succ = 0;
            while((int)$mis_total>$mis_start){
                try{
                    $data_arr = [];
                    $mis_user_id = UserLoanOrder::find()->select("id")->where(["not in","id",$arr])->andWhere("created_at>={$t1} and created_at<={$t2}")->limit($mis_size)->offset($mis_start)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
                    if(!empty($mis_user_id)) {
                        foreach ($mis_user_id as $key => $order_id_sql) {
                            $data_arr[] = $order_id_sql['id'];
                        }
                    }//分页id集合 $data_arr
                    if(isset($data_arr)){
                        $doc_arr = $this->createOrderDetail($data_arr);
                        $doc_total+=count($doc_arr);
                    }
                    //插入到Solr中
                    if(!empty($doc_arr)){//成功从数据库中提取到关系数据
                        $update_result = $this->tryToUpdate($doc_arr);
                        //统计返回结果
                        if(!empty($update_result)&&isset($update_result['code'])){
                            if($update_result['code'] == 0){
                                $succ+=count($doc_arr);
                            }else{
                                //var_dump($update_result['message']);
                            }
                        }
                    }
                    //var_dump($mis_start);
                    $mis_start+=$mis_size;
                }catch (\Exception $e){
                    //var_dump($e->getMessage());
                    sleep(10);
                }
            }
            //var_dump($succ);
        }
    }


    /**
     * @param int $num
     */
    public function actionCheck($num){
        $this->checkInsertRecord($num);
    }
}
?>